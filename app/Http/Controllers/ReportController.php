<?php

namespace App\Http\Controllers;

use App\Models\DeviceToken;
use App\Models\ClaimReview;
use App\Models\Notification;
use App\Models\PotentialMatch;
use App\Models\Report;
use App\Models\ReportImage;
use App\Models\Reward;
use App\Models\SurrenderRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Report::query();

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->input('category'));
        }

        if ($request->filled('mine')) {
            $query->where('student_id', $user->id);
        } else {
            $query->where(function ($sub) {
                $sub->where(function ($q) {
                    $q->where('type', 'lost')
                        ->where('status', 'approved');
                })->orWhere(function ($q) {
                    $q->where('type', 'found')
                        ->where('status', 'verified');
                });
            });
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

        $reports = $query->with(['student', 'images'])->latest()->paginate(20);

        $reports->getCollection()->each->makeHidden('private_identifying_details');

        return response()->json(['status' => 'success', 'data' => $reports]);
    }

    public function mine(Request $request)
    {
        $user = $request->user();
        $reports = Report::where('student_id', $user->id)
            ->with(['student', 'images'])
            ->latest()
            ->get();

        $reports->each->makeHidden('private_identifying_details');

        return response()->json(['status' => 'success', 'data' => $reports]);
    }

    public function show(Request $request, $id)
    {
        $report = Report::with(['student', 'images'])->findOrFail($id);
        $user = $request->user();

        if ($report->student_id !== $user->id) {
            if ($report->type === 'lost' && $report->status !== 'approved') {
                return response()->json(['status' => 'error', 'message' => 'Report not found.'], 404);
            }

            if ($report->type === 'found' && $report->status !== 'verified') {
                return response()->json(['status' => 'error', 'message' => 'Report not found.'], 404);
            }
        }

        $report->makeHidden('private_identifying_details');

        return response()->json(['status' => 'success', 'data' => $report]);
    }

    public function submitClaim(Request $request, $id)
    {
        $user = $request->user();

        $validated = $request->validate([
            'proof_description' => 'required|string|max:3000',
            'proof_photo' => 'nullable|file|image|max:5120',
        ]);

        try {
            return DB::transaction(function () use ($request, $id, $user, $validated) {
                $report = Report::where('id', $id)->lockForUpdate()->firstOrFail();

                if ($report->type !== 'found' || $report->status !== 'verified') {
                    return response()->json(['status' => 'error', 'message' => 'Only verified found items can be claimed.'], 422);
                }

                $existingClaim = ClaimReview::where('report_id', $report->id)
                    ->where('claimant_student_id', $user->id)
                    ->whereIn('review_status', ['pending', 'additional_information_required', 'approved'])
                    ->exists();

                if ($existingClaim) {
                    return response()->json(['status' => 'error', 'message' => 'You already have an active claim for this item.'], 422);
                }

                $evidence = ['proof_description' => $validated['proof_description']];
                if ($request->hasFile('proof_photo')) {
                    $evidence['proof_photo'] = $request->file('proof_photo')->store('claim-evidence', 'public');
                }

                $claim = ClaimReview::create([
                    'report_id' => $report->id,
                    'claimant_student_id' => $user->id,
                    'review_status' => 'pending',
                    'evidence' => $evidence,
                ]);

                Notification::create([
                    'type' => 'claim_submitted',
                    'title' => 'New Claim Submitted',
                    'message' => "A student submitted an ownership claim for '{$report->title}'.",
                    'action' => 'claim_submitted',
                    'status' => 'pending',
                    'recipient_role' => 'admin',
                    'subject_type' => ClaimReview::class,
                    'subject_id' => $claim->id,
                    'is_read' => false,
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Your claim was submitted and is waiting for verification.',
                    'data' => $claim->load('report'),
                ], 201);
            });
        } catch (\Throwable $exception) {
            return response()->json(['status' => 'error', 'message' => $exception->getMessage()], 422);
        }
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'type' => 'required|in:lost,found',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'private_identifying_details' => 'required|string|max:5000',
            'category_id' => 'nullable|exists:item_categories,id',
            'location' => 'required|string|max:255',
            'date_occurred' => 'required|date',
            'event_date' => 'nullable|date',
            'reward_amount' => 'nullable|numeric|min:0',
            'reward_type' => 'nullable|in:reward,fine',
            'image' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
            'images' => 'required|array|min:1',
            'images.*' => 'required|file|image|max:5120',
        ], [
            'images.required' => 'At least one image is required to submit a report.',
            'images.min' => 'At least one image is required to submit a report.',
            'images.*.image' => 'All files must be valid images.',
            'images.*.max' => 'Images must not exceed 5MB each.',
        ]);

        $status = $validated['type'] === 'lost' ? 'pending_approval' : 'pending_surrender';

        $report = Report::create(array_merge($validated, [
            'student_id' => $user->id,
            'status' => $status,
            'requires_images' => true,
        ]));

        // Store uploaded images
        if ($request->hasFile('images')) {
            $isPrimary = true;
            foreach ($request->file('images') as $imageFile) {
                $path = $imageFile->store('report-images', 'public');

                ReportImage::create([
                    'report_id' => $report->id,
                    'file_path' => $path,
                    'original_name' => $imageFile->getClientOriginalName(),
                    'mime_type' => $imageFile->getMimeType(),
                    'file_size' => $imageFile->getSize(),
                    'is_primary' => $isPrimary,
                ]);

                $isPrimary = false;
            }
        }

        if ($status === 'pending_surrender') {
            $surrender = SurrenderRequest::create([
                'report_id' => $report->id,
                'student_id' => $user->id,
                'status' => 'pending',
                'deadline_at' => now()->addDays(3),
            ]);

            Notification::create([
                'student_id' => $user->id,
                'title' => 'Surrender Request Created',
                'message' => 'Please bring the found item to USC as soon as possible.',
                'action' => 'surrender_requested',
                'recipient_id' => $user->id,
                'recipient_role' => 'student',
                'subject_type' => Report::class,
                'subject_id' => $report->id,
                'is_read' => false,
            ]);

            Notification::create([
                'type' => 'pending_surrender',
                'title' => 'New Pending Surrender',
                'message' => "A found-item surrender request for '{$report->title}' is waiting for review.",
                'action' => 'pending_surrender',
                'status' => 'pending',
                'recipient_role' => 'admin',
                'subject_type' => SurrenderRequest::class,
                'subject_id' => $surrender->id,
                'is_read' => false,
            ]);
        } elseif ($status === 'pending_approval') {
            Notification::create([
                'type' => 'pending_report',
                'title' => 'New Pending Report',
                'message' => "A lost-item report for '{$report->title}' is waiting for review.",
                'action' => 'pending_report',
                'status' => 'pending',
                'recipient_role' => 'admin',
                'subject_type' => Report::class,
                'subject_id' => $report->id,
                'is_read' => false,
            ]);
        }

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

        $report->load(['student', 'images']);
        $report->makeHidden('private_identifying_details');
        return response()->json(['status' => 'success', 'message' => 'Report created successfully. Images uploaded.', 'data' => $report], 201);
    }

    public function uploadImages(Request $request, $reportId)
    {
        $user = $request->user();
        $report = Report::findOrFail($reportId);

        // Verify ownership
        if ($report->student_id !== $user->id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'images' => 'required|array|min:1',
            'images.*' => 'required|file|image|max:5120',
        ], [
            'images.required' => 'At least one image is required.',
            'images.min' => 'At least one image is required.',
            'images.*.image' => 'All files must be valid images.',
            'images.*.max' => 'Images must not exceed 5MB each.',
        ]);

        $uploadedImages = [];
        $isPrimary = !$report->images()->exists();

        foreach ($request->file('images') as $imageFile) {
            $path = $imageFile->store('report-images', 'public');

            $image = ReportImage::create([
                'report_id' => $report->id,
                'file_path' => $path,
                'original_name' => $imageFile->getClientOriginalName(),
                'mime_type' => $imageFile->getMimeType(),
                'file_size' => $imageFile->getSize(),
                'is_primary' => $isPrimary,
            ]);

            $uploadedImages[] = $image;
            $isPrimary = false;
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Images uploaded successfully.',
            'data' => $uploadedImages,
        ], 201);
    }

    public function deleteImage(Request $request, $reportId, $imageId)
    {
        $user = $request->user();
        $report = Report::findOrFail($reportId);

        // Verify ownership
        if ($report->student_id !== $user->id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $image = ReportImage::where('report_id', $report->id)->findOrFail($imageId);

        // Check if this is the last image and report requires images
        if ($report->requires_images && $report->images()->count() === 1) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete the last image. Reports must have at least one image.',
            ], 422);
        }

        // Delete file from storage
        Storage::disk('public')->delete($image->file_path);
        $image->delete();

        // If deleted image was primary, make the next one primary
        if ($image->is_primary) {
            $nextImage = $report->images()->first();
            if ($nextImage) {
                $nextImage->update(['is_primary' => true]);
            }
        }

        return response()->json(['status' => 'success', 'message' => 'Image deleted successfully.']);
    }

    public function matches(Request $request, $id)
    {
        $report = Report::findOrFail($id);
        $user = $request->user();

        if ($report->student_id !== $user->id) {
            if ($report->type === 'lost' && $report->status !== 'approved') {
                return response()->json(['status' => 'error', 'message' => 'Report not found.'], 404);
            }

            if ($report->type === 'found' && $report->status !== 'verified') {
                return response()->json(['status' => 'error', 'message' => 'Report not found.'], 404);
            }
        }

        $oppositeType = $report->type === 'lost' ? 'found' : 'lost';

        $validStatuses = $oppositeType === 'lost'
            ? ['approved']
            : ['verified'];

        $candidates = Report::where('type', $oppositeType)
            ->whereIn('status', $validStatuses)
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

        $validCurrentStatuses = ['approved', 'verified'];
        if (!in_array($report->status, $validCurrentStatuses, true) || !in_array($matchedReport->status, $validCurrentStatuses, true)) {
            return response()->json(['status' => 'error', 'message' => 'One of the reports is not eligible for a potential match.'], 422);
        }

        $existingMatch = PotentialMatch::where(function ($query) use ($report, $matchedReport) {
            $query->where('lost_report_id', $report->id)
                ->where('found_report_id', $matchedReport->id);
        })->orWhere(function ($query) use ($report, $matchedReport) {
            $query->where('lost_report_id', $matchedReport->id)
                ->where('found_report_id', $report->id);
        })->first();

        $score = 0;
        if ($report->category && $matchedReport->category && Str::lower($report->category) === Str::lower($matchedReport->category)) {
            $score += 20;
        }
        if ($report->location && $matchedReport->location && (Str::contains(Str::lower($matchedReport->location), Str::lower($report->location)) || Str::contains(Str::lower($report->location), Str::lower($matchedReport->location)))) {
            $score += 15;
        }
        $reportKeywords = $this->extractKeywords($report->title . ' ' . $report->description);
        $candidateKeywords = $this->extractKeywords($matchedReport->title . ' ' . $matchedReport->description);
        $keywordMatches = array_intersect($reportKeywords, $candidateKeywords);
        $score += count($keywordMatches) * 5;
        if ($report->reward_amount && $matchedReport->reward_amount) {
            $score += 5;
        }

        if (!$existingMatch) {
            $existingMatch = PotentialMatch::create([
                'lost_report_id' => $report->id,
                'found_report_id' => $matchedReport->id,
                'score' => min(100, $score),
                'status' => 'pending',
                'review_notes' => 'Potential match suggested by student.',
            ]);
        }

        Notification::create([
            'student_id' => $matchedReport->student_id,
            'title' => 'Potential Match Submitted',
            'message' => "A potential match has been created for your report '{$matchedReport->title}'.",
            'action' => 'potential_match_submitted',
            'recipient_id' => $matchedReport->student_id,
            'recipient_role' => 'student',
            'subject_type' => PotentialMatch::class,
            'subject_id' => $existingMatch->id,
            'is_read' => false,
            'data' => [
                'report_id' => $matchedReport->id,
                'matched_report_id' => $report->id,
            ],
        ]);

        Notification::create([
            'student_id' => $report->student_id,
            'title' => 'Potential Match Submitted',
            'message' => "A potential match has been created for your report '{$report->title}'.",
            'action' => 'potential_match_submitted',
            'recipient_id' => $report->student_id,
            'recipient_role' => 'student',
            'subject_type' => PotentialMatch::class,
            'subject_id' => $existingMatch->id,
            'is_read' => false,
            'data' => [
                'report_id' => $report->id,
                'matched_report_id' => $matchedReport->id,
            ],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Potential match submitted for review.',
            'data' => $existingMatch,
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
            'message' => "Your '{$report->type}' report '{$report->title}' has a possible match: '{$otherReport->title}'.",
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
