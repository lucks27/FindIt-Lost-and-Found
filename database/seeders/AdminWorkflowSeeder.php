<?php

namespace Database\Seeders;

use App\Models\AdminAnnouncement;
use App\Models\AdminSetting;
use App\Models\AuditLog;
use App\Models\ClaimReview;
use App\Models\EventFine;
use App\Models\ItemRelease;
use App\Models\ItemVerification;
use App\Models\PotentialMatch;
use App\Models\Report;
use App\Models\Student;
use App\Models\SurrenderRequest;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        $admin = \App\Models\Admin::query()->where('role', 'super_admin')->first();
        $studentA = Student::first();
        $studentB = Student::skip(1)->first();

        if (!$admin || !$studentA || !$studentB) {
            return;
        }

        $reportLost = Report::create([
            'student_id' => $studentA->id,
            'report_type' => 'lost',
            'category' => 'wallet',
            'title' => 'Lost navy wallet',
            'description' => 'Black wallet lost near the library entrance.',
            'location' => 'Library entrance',
            'event_date' => now()->subDay(),
            'status' => 'open',
            'reward_amount' => 150,
            'reward_type' => 'reward',
            'metadata' => ['priority' => 'high'],
        ]);

        $reportFound = Report::create([
            'student_id' => $studentB->id,
            'report_type' => 'found',
            'category' => 'wallet',
            'title' => 'Found navy wallet',
            'description' => 'Found a navy wallet with student ID and cash.',
            'location' => 'Library entrance',
            'event_date' => now()->subHours(2),
            'status' => 'open',
            'reward_amount' => 150,
            'reward_type' => 'reward',
            'metadata' => ['priority' => 'medium'],
        ]);

        AdminAnnouncement::create([
            'admin_id' => $admin->id,
            'title' => 'Campus lost-and-found cleanup drive',
            'message' => 'Please submit all unclaimed items to the main office before Friday.',
            'audience' => 'all',
            'status' => 'published',
            'published_at' => now(),
            'expires_at' => now()->addDays(7),
            'metadata' => ['priority' => 'normal'],
        ]);

        AuditLog::create([
            'actor_admin_id' => $admin->id,
            'action' => 'logged_in',
            'subject_type' => 'admin',
            'subject_id' => $admin->id,
            'subject_label' => 'Super Admin',
            'details' => ['module' => 'auth', 'summary' => 'Super admin signed in to the dashboard.'],
            'ip_address' => '127.0.0.1',
        ]);

        ClaimReview::create([
            'report_id' => $reportLost->id,
            'claimant_student_id' => $studentB->id,
            'review_status' => 'pending',
            'evidence' => ['id_photo' => true, 'description_match' => true],
            'notes' => 'Awaiting photo confirmation.',
            'reviewed_by_admin_id' => $admin->id,
            'reviewed_at' => now(),
        ]);

        ItemVerification::create([
            'report_id' => $reportFound->id,
            'verifier_admin_id' => $admin->id,
            'status' => 'pending',
            'checklist' => ['photo_checked' => true, 'serial_number_checked' => false],
            'notes' => 'Validate the item against the claimant description.',
            'verified_at' => now(),
        ]);

        ItemRelease::create([
            'report_id' => $reportFound->id,
            'claimant_student_id' => $studentA->id,
            'approved_by_admin_id' => $admin->id,
            'release_code' => 'REL-' . now()->format('YmdHis'),
            'status' => 'pending',
            'scheduled_at' => now()->addHours(2),
            'checklist' => ['receipt_signed' => false, 'owner_verified' => true],
            'receipt_number' => 'RCPT-1001',
        ]);

        PotentialMatch::create([
            'source_report_id' => $reportLost->id,
            'matched_report_id' => $reportFound->id,
            'score' => 85,
            'status' => 'pending',
            'notes' => 'Strong category and location similarity.',
        ]);

        SurrenderRequest::create([
            'report_id' => $reportFound->id,
            'student_id' => $studentB->id,
            'status' => 'pending',
            'deadline_at' => now()->addDay(),
            'received_by_admin_id' => $admin->id,
            'notes' => 'Item needs to be surrendered to the office within the deadline.',
        ]);

        EventFine::create([
            'student_id' => $studentA->id,
            'report_id' => $reportLost->id,
            'event_name' => 'Late report submission',
            'reference_number' => 'FINE-1001',
            'amount' => 75,
            'status' => 'open',
            'reason' => 'Delayed processing of the item report.',
            'issued_by_admin_id' => $admin->id,
            'due_at' => now()->addDays(5),
        ]);

        AdminSetting::create([
            'key' => 'super_admin_dashboard',
            'value' => ['theme' => 'dark', 'compact_cards' => true],
            'description' => 'Default dashboard preferences for the super admin.',
            'updated_by_admin_id' => $admin->id,
        ]);
    }
}
