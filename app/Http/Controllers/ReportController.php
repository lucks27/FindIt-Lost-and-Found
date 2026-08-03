<?php

namespace App\Http\Controllers;

use App\Models\DeviceToken;
use App\Models\Notification;
use App\Models\Report;
use App\Models\Reward;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Report::query();

        if ($request->filled('type')) {
            $query->where('report_type', $request->input('type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('mine')) {
            $query->where('student_id', $user->id);
        }

        if ($request->filled('search')) {
            $search = strtolower($request->input('search'));
            $query->where(function ($sub) use ($search) {
                $sub->whereRaw('LOWER(title) LIKE ?', ["%{$search}%"]);
                $sub->orWhereRaw('LOWER(description) LIKE ?', ["%{$search}%"]);
                $sub->orWhereRaw('LOWER(location) LIKE ?', ["%{$search}%"]);
                $sub->orWhereRaw('LOWER(category) LIKE ?', ["%{$search}%"]);
            });
        }

        $reports = $query->with('student')->latest()->paginate(20);

        return response()->json(['status' => 'success', 'data' => $reports]);
    }

    public function mine(Request $request)
    {
        $user = $request->user();
        $reports = Report::where('student_id', $user->id)
            ->with('rewards')
            ->latest()
            ->get();

        return response()->json(['status' => 'success', 'data' => $reports]);
    }

    public function show($id)
    {
        $report = Report::with(['student', 'rewards'])->findOrFail($id);
        return response()->json(['status' => 'success', 'data' => $report]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'report_type' => 'required|in:lost,found',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:120',
            'location' => 'nullable|string|max:255',
            'event_date' => 'nullable|date',
            'reward_amount' => 'nullable|numeric|min:0',
            'reward_type' => 'nullable|in:reward,fine',
            'image' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
        ]);

        $report = Report::create(array_merge($validated, [
            'student_id' => $user->id,
            'status' => 'open',
        ]));

        if (!empty($validated['reward_amount'])) {
            Reward::create([
                'report_id' => $report->id,
                'student_id' => $user->id,
                'title' => $validated['title'] . ' Reward',
                'description' => $validated['description'] ?? 'Reward posted for the report',
                'amount' => $validated['reward_amount'],
                'type' => $validated['reward_type'] ?? 'reward',
                'status' => 'pending',
            ]);
        }

        return response()->json(['status' => 'success', 'message' => 'Report created successfully.', 'data' => $report], 201);
    }

    public function matches($id)
    {
        $report = Report::findOrFail($id);
        $oppositeType = $report->report_type === 'lost' ? 'found' : 'lost';

        $candidates = Report::where('report_type', $oppositeType)
            ->where('status', 'open')
            ->where('id', '<>', $report->id)
            ->get();

        $matches = $candidates->map(function ($candidate) use ($report) {
            $score = 0;

            if (!empty($report->category) && !empty($candidate->category) && Str::lower($report->category) === Str::lower($candidate->category)) {
                $score += 20;
            }

            if (!empty($report->location) && !empty($candidate->location)) {
                if (Str::contains(Str::lower($candidate->location), Str::lower($report->location)) || Str::contains(Str::lower($report->location), Str::lower($candidate->location))) {
                    $score += 15;
                }
            }

            $reportKeywords = $this->extractKeywords($report->title . ' ' . $report->description);
            $candidateKeywords = $this->extractKeywords($candidate->title . ' ' . $candidate->description);
            $keywordMatches = array_intersect($reportKeywords, $candidateKeywords);
            $score += count($keywordMatches) * 5;

            if ($report->reward_amount && $candidate->reward_amount) {
                $score += 5;
            }

            return [
                'report' => $candidate,
                'score' => $score,
            ];
        })->filter(fn($item) => $item['score'] > 0)
            ->sortByDesc('score')
            ->values();

        return response()->json(['status' => 'success', 'data' => $matches]);
    }

    public function confirmMatch(Request $request, $id, $matchId)
    {
        $user = $request->user();
        $report = Report::findOrFail($id);
        $matchedReport = Report::findOrFail($matchId);

        if ($report->student_id !== $user->id) {
            return response()->json(['status' => 'error', 'message' => 'You do not own this report.'], 403);
        }

        if ($report->status !== 'open' || $matchedReport->status !== 'open') {
            return response()->json(['status' => 'error', 'message' => 'One of the reports is not open for matching.'], 422);
        }

        $report->status = 'matched';
        $matchedReport->status = 'matched';
        $report->save();
        $matchedReport->save();

        $this->createMatchNotification($report, $matchedReport);
        $this->createMatchNotification($matchedReport, $report);

        $this->sendPushNotificationToUser($report->student_id, 'Match Found', "A potential match has been confirmed for your report '{$report->title}'.", [
            'report_id' => $report->id,
            'matched_report_id' => $matchedReport->id,
        ]);

        $this->sendPushNotificationToUser($matchedReport->student_id, 'Match Found', "A potential match has been confirmed for your report '{$matchedReport->title}'.", [
            'report_id' => $matchedReport->id,
            'matched_report_id' => $report->id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Match confirmed successfully.',
            'data' => [
                'report' => $report,
                'matched_report' => $matchedReport,
            ]
        ]);
    }

    protected function extractKeywords(?string $text): array
    {
        if (empty($text)) {
            return [];
        }

        $text = Str::lower(preg_replace('/[^a-z0-9\s]+/', ' ', $text));
        $words = array_filter(array_map('trim', explode(' ', $text)), fn($word) => strlen($word) >= 3);
        return array_values(array_unique($words));
    }

    protected function createMatchNotification(Report $report, Report $otherReport)
    {
        return Notification::create([
            'student_id' => $report->student_id,
            'title' => 'Potential Match Found',
            'message' => "Your '{$report->report_type}' report '{$report->title}' has a possible match: '{$otherReport->title}'.",
            'type' => 'lost_found_match',
            'action' => 'matched',
            'recipient_id' => $report->student_id,
            'recipient_role' => 'student',
            'subject_type' => Report::class,
            'subject_id' => $report->id,
            'data' => [
                'report_id' => $report->id,
                'matched_report_id' => $otherReport->id,
            ],
            'is_read' => false,
        ]);
    }

    protected function sendPushNotificationToUser(int $studentId, string $title, string $body, array $data = [])
    {
        $tokens = DeviceToken::where('student_id', $studentId)
            ->pluck('token')
            ->filter()
            ->toArray();

        if (empty($tokens)) {
            return false;
        }

        $expoTokens = collect($tokens)->filter(fn($token) => Str::startsWith($token, 'ExponentPushToken'))->values()->all();
        $fcmTokens = collect($tokens)->filter(fn($token) => !Str::startsWith($token, 'ExponentPushToken'))->values()->all();

        $success = true;

        if (!empty($expoTokens)) {
            $expoPayloads = array_map(fn($token) => [
                'to' => $token,
                'title' => $title,
                'body' => $body,
                'data' => $data,
            ], $expoTokens);

            $expoResponse = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post('https://exp.host/--/api/v2/push/send', $expoPayloads);

            if (!$expoResponse->successful()) {
                $success = false;
            }
        }

        if (!empty($fcmTokens)) {
            $serverKey = env('FCM_SERVER_KEY');
            if (!empty($serverKey)) {
                $fcmPayload = [
                    'registration_ids' => $fcmTokens,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => $data,
                ];

                $fcmResponse = Http::withHeaders([
                    'Authorization' => "key={$serverKey}",
                    'Content-Type' => 'application/json',
                ])->post('https://fcm.googleapis.com/fcm/send', $fcmPayload);

                if (!$fcmResponse->successful()) {
                    $success = false;
                }
            } else {
                $success = false;
            }
        }

        return $success;
    }
}
