<?php

namespace Database\Seeders;

use App\Models\Admin;
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

class AdminWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | REQUIRED RECORDS
        |--------------------------------------------------------------------------
        */

        $admin = Admin::query()
            ->where('role', 'super_admin')
            ->first();

        $studentA = Student::query()
            ->orderBy('id')
            ->first();

        $studentB = Student::query()
            ->orderBy('id')
            ->skip(1)
            ->first();

        if (!$admin || !$studentA || !$studentB) {
            $this->command?->warn(
                'AdminWorkflowSeeder skipped because a super admin or two students are missing.'
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | LOST REPORT
        |--------------------------------------------------------------------------
        */

        $reportLost = Report::updateOrCreate(
            [
                'student_id' => $studentA->id,
                'type' => 'lost',
                'title' => 'Lost navy wallet',
            ],
            [
                'description' => 'Black wallet lost near the library entrance.',
                'location' => 'Library entrance',
                'date_occurred' => now()->subDay(),
                'status' => 'open',

                'metadata' => [
                    'priority' => 'high',
                ],
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | FOUND REPORT
        |--------------------------------------------------------------------------
        */

        $reportFound = Report::updateOrCreate(
            [
                'student_id' => $studentB->id,
                'type' => 'found',
                'title' => 'Found navy wallet',
            ],
            [
                'description' => 'Found a navy wallet with student ID and cash.',
                'location' => 'Library entrance',
                'date_occurred' => now()->subHours(2),
                'status' => 'open',

                'metadata' => [
                    'priority' => 'medium',
                ],
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | ADMIN ANNOUNCEMENT
        |--------------------------------------------------------------------------
        */

        AdminAnnouncement::updateOrCreate(
            [
                'admin_id' => $admin->id,
                'title' => 'Campus lost-and-found cleanup drive',
            ],
            [
                'message' => 'Please submit all unclaimed items to the main office before Friday.',
                'audience' => 'all',
                'status' => 'published',
                'published_at' => now(),
                'expires_at' => now()->addDays(7),

                'metadata' => [
                    'priority' => 'normal',
                ],
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | AUDIT LOG
        |--------------------------------------------------------------------------
        */

        AuditLog::updateOrCreate(
            [
                'actor_admin_id' => $admin->id,
                'action' => 'logged_in',
                'subject_type' => 'admin',
                'subject_id' => $admin->id,
            ],
            [
                'subject_label' => 'Super Admin',

                'description' => 'Super admin signed in to the dashboard.',

                'details' => [
                    'module' => 'auth',
                    'summary' => 'Super admin signed in to the dashboard.',
                ],

                'ip_address' => '127.0.0.1',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | CLAIM REVIEW
        |--------------------------------------------------------------------------
        */

        ClaimReview::updateOrCreate(
            [
                'report_id' => $reportLost->id,
                'claimant_student_id' => $studentB->id,
            ],
            [
                'review_status' => 'pending',

                'evidence' => [
                    'id_photo' => true,
                    'description_match' => true,
                ],

                'notes' => 'Awaiting photo confirmation.',
                'reviewed_by_admin_id' => $admin->id,
                'reviewed_at' => now(),
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | ITEM VERIFICATION
        |--------------------------------------------------------------------------
        */

        ItemVerification::updateOrCreate(
            [
                'report_id' => $reportFound->id,
            ],
            [
                'verifier_admin_id' => $admin->id,
                'status' => 'pending',

                'checklist' => [
                    'photo_checked' => true,
                    'serial_number_checked' => false,
                ],

                'notes' => 'Validate the item against the claimant description.',
                'verified_at' => now(),
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | ITEM RELEASE
        |--------------------------------------------------------------------------
        */

        ItemRelease::updateOrCreate(
            [
                'report_id' => $reportFound->id,
                'claimant_student_id' => $studentA->id,
            ],
            [
                'approved_by_admin_id' => $admin->id,

                'release_code' =>
                    'REL-' .
                    $reportFound->id .
                    '-' .
                    $studentA->id,

                'status' => 'pending',

                'scheduled_at' => now()->addHours(2),

                'checklist' => [
                    'receipt_signed' => false,
                    'owner_verified' => true,
                ],

                'receipt_number' => 'RCPT-1001',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | POTENTIAL MATCH
        |--------------------------------------------------------------------------
        */

        PotentialMatch::updateOrCreate(
            [
                'lost_report_id' => $reportLost->id,
                'found_report_id' => $reportFound->id,
            ],
            [
                'score' => 85,

                'matched_fields' => json_encode([
                    'category' => true,
                    'location' => true,
                    'description' => true,
                ]),

                'source' => 'system',

                'status' => 'pending',

                'review_notes' =>
                    'Strong category and location similarity.',

                'reviewed_by_admin_id' => null,
                'reviewed_at' => null,
                'created_by_student_id' => null,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | SURRENDER REQUEST
        |--------------------------------------------------------------------------
        */

        SurrenderRequest::updateOrCreate(
            [
                'report_id' => $reportFound->id,
                'student_id' => $studentB->id,
            ],
            [
                'status' => 'pending',

                'deadline_at' => now()->addDay(),

                'received_by_admin_id' => $admin->id,

                'notes' =>
                    'Item needs to be surrendered to the office within the deadline.',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | EVENT FINE
        |--------------------------------------------------------------------------
        |
        | Actual event_fines table columns:
        |
        | student_id
        | student_name
        | school_id
        | event_name
        | description
        | original_amount
        | remaining_amount
        | amount_to_pay
        | paid_amount
        | paid
        | due_date
        | status
        | created_by_admin_id
        | reference_number
        | amount
        | reason
        | issued_by_admin_id
        | due_at
        |
        | There is NO report_id column.
        |
        */

        $studentName = trim(
            implode(
                ' ',
                array_filter([
                    $studentA->first_name ?? null,
                    $studentA->middle_name ?? null,
                    $studentA->last_name ?? null,
                ])
            )
        );

        $fineAmount = 75.00;

        $fineDueDate = now()
            ->addDays(5)
            ->toDateString();

        EventFine::updateOrCreate(
            [
                'reference_number' => 'FINE-1001',
            ],
            [
                'student_id' => $studentA->id,

                'student_name' => $studentName,

                'school_id' =>
                    $studentA->student_id_number ?? null,

                'event_name' => 'Late report submission',

                'description' =>
                    'Fine issued for delayed processing of the lost item report.',

                'original_amount' => $fineAmount,

                'remaining_amount' => $fineAmount,

                'amount_to_pay' => $fineAmount,

                'paid_amount' => 0.00,

                'paid' => 0.00,

                'due_date' => $fineDueDate,

                'status' => 'unpaid',

                'created_by_admin_id' => $admin->id,

                'amount' => $fineAmount,

                'reason' =>
                    'Delayed processing of the item report.',

                'issued_by_admin_id' => $admin->id,

                'due_at' => $fineDueDate,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | ADMIN SETTING
        |--------------------------------------------------------------------------
        */

        AdminSetting::updateOrCreate(
            [
                'key' => 'super_admin_dashboard',
            ],
            [
                'value' => [
                    'theme' => 'dark',
                    'compact_cards' => true,
                ],

                'description' =>
                    'Default dashboard preferences for the super admin.',

                'updated_by_admin_id' => $admin->id,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | SUCCESS MESSAGE
        |--------------------------------------------------------------------------
        */

        $this->command?->info(
            'Admin workflow sample data seeded successfully.'
        );
    }
}
