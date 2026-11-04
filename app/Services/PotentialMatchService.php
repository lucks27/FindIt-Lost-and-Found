<?php

namespace App\Services;

use App\Models\PotentialMatch;
use App\Models\Report;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PotentialMatchService
{
    /*
    |--------------------------------------------------------------------------
    | ELIGIBILITY
    |--------------------------------------------------------------------------
    |
    | Only approved Lost reports and verified Found reports participate in
    | matching. Reports with a previously confirmed potential match stay
    | eligible so USC may still review other possible matches.
    |
    */

    public function isEligible(
        Report $report
    ): bool {
        if ($report->isLost()) {
            return in_array(
                $report->status,
                [
                    Report::STATUS_APPROVED,
                    Report::STATUS_POTENTIAL_MATCH_FOUND,
                ],
                true
            );
        }

        if ($report->isFound()) {
            return in_array(
                $report->status,
                [
                    Report::STATUS_VERIFIED,
                    Report::STATUS_POTENTIAL_MATCH_FOUND,
                ],
                true
            );
        }

        return false;
    }


    /*
    |--------------------------------------------------------------------------
    | CANDIDATES
    |--------------------------------------------------------------------------
    */

    public function candidatesFor(
        Report $report
    ): Collection {
        if (!$this->isEligible($report)) {
            return collect();
        }

        if ($report->isLost()) {
            return Report::query()
                ->where(
                    'type',
                    Report::TYPE_FOUND
                )
                ->whereIn(
                    'status',
                    [
                        Report::STATUS_VERIFIED,
                        Report::STATUS_POTENTIAL_MATCH_FOUND,
                    ]
                )
                ->where(
                    'id',
                    '<>',
                    $report->id
                )
                ->with([
                    'student',
                    'images',
                    'categoryRelation',
                ])
                ->get();
        }

        return Report::query()
            ->where(
                'type',
                Report::TYPE_LOST
            )
            ->whereIn(
                'status',
                [
                    Report::STATUS_APPROVED,
                    Report::STATUS_POTENTIAL_MATCH_FOUND,
                ]
            )
            ->where(
                'id',
                '<>',
                $report->id
            )
            ->with([
                'student',
                'images',
                'categoryRelation',
            ])
            ->get();
    }


    /*
    |--------------------------------------------------------------------------
    | AUTOMATIC MATCH GENERATION
    |--------------------------------------------------------------------------
    |
    | A score above zero follows the same threshold already used by the
    | existing mobile matching endpoint. Newly created matches stay PENDING.
    | No report status changes here. USC must still review the match.
    |
    */

    public function generateForReport(
        Report $report
    ): Collection {
        if (!$this->isEligible($report)) {
            return collect();
        }

        return $this
            ->candidatesFor($report)
            ->map(
                function (
                    Report $candidate
                ) use ($report) {
                    $lostReport =
                        $report->isLost()
                            ? $report
                            : $candidate;

                    $foundReport =
                        $report->isFound()
                            ? $report
                            : $candidate;

                    $breakdown =
                        $this->calculateBreakdown(
                            $lostReport,
                            $foundReport
                        );

                    $score =
                        (int) $breakdown['score'];

                    if ($score <= 0) {
                        return null;
                    }

                    $existing =
                        PotentialMatch::query()
                            ->where(
                                'lost_report_id',
                                $lostReport->id
                            )
                            ->where(
                                'found_report_id',
                                $foundReport->id
                            )
                            ->first();

                    if ($existing) {
                        return [
                            'match' =>
                                $existing,

                            'created' =>
                                false,

                            'score' =>
                                $score,

                            'reasons' =>
                                $breakdown[
                                    'reasons'
                                ],
                        ];
                    }

                    $match =
                        PotentialMatch::create([
                            'lost_report_id' =>
                                $lostReport->id,

                            'found_report_id' =>
                                $foundReport->id,

                            'score' =>
                                $score,

                            'status' =>
                                'pending',

                            'review_notes' =>
                                $this->buildReviewNotes(
                                    $score,
                                    $breakdown[
                                        'reasons'
                                    ],
                                    'System generated'
                                ),
                        ]);

                    return [
                        'match' =>
                            $match,

                        'created' =>
                            true,

                        'score' =>
                            $score,

                        'reasons' =>
                            $breakdown[
                                'reasons'
                            ],
                    ];
                }
            )
            ->filter()
            ->values();
    }


    /*
    |--------------------------------------------------------------------------
    | STUDENT-SUGGESTED MATCH
    |--------------------------------------------------------------------------
    |
    | Student suggestions are sent to USC as PENDING. They do not change the
    | Lost/Found workflow status and do not notify another student yet.
    |
    */

    public function submitSuggestion(
        Report $first,
        Report $second
    ): array {
        if (
            !$this->isEligible($first) ||
            !$this->isEligible($second)
        ) {
            throw new \RuntimeException(
                'One of the reports is not eligible for a potential match.'
            );
        }

        if ($first->type === $second->type) {
            throw new \RuntimeException(
                'Potential matches must contain one lost report and one found report.'
            );
        }

        $lostReport =
            $first->isLost()
                ? $first
                : $second;

        $foundReport =
            $first->isFound()
                ? $first
                : $second;

        $breakdown =
            $this->calculateBreakdown(
                $lostReport,
                $foundReport
            );

        $score =
            (int) $breakdown['score'];

        if ($score <= 0) {
            throw new \RuntimeException(
                'The selected reports do not have enough matching information to create a potential match.'
            );
        }

        $existing =
            PotentialMatch::query()
                ->where(
                    'lost_report_id',
                    $lostReport->id
                )
                ->where(
                    'found_report_id',
                    $foundReport->id
                )
                ->first();

        if ($existing) {
            return [
                'match' =>
                    $existing,

                'created' =>
                    false,

                'score' =>
                    $score,

                'reasons' =>
                    $breakdown[
                        'reasons'
                    ],
            ];
        }

        $match =
            PotentialMatch::create([
                'lost_report_id' =>
                    $lostReport->id,

                'found_report_id' =>
                    $foundReport->id,

                'score' =>
                    $score,

                'status' =>
                    'pending',

                'review_notes' =>
                    $this->buildReviewNotes(
                        $score,
                        $breakdown[
                            'reasons'
                        ],
                        'Student suggested'
                    ),
            ]);

        return [
            'match' =>
                $match,

            'created' =>
                true,

            'score' =>
                $score,

            'reasons' =>
                $breakdown[
                    'reasons'
                ],
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | SCORE
    |--------------------------------------------------------------------------
    |
    | Category       20
    | Item name      20
    | Description    20
    | Color          15
    | Location       15
    | Date           10
    | ----------------
    | Total         100
    |
    */

    public function calculateScore(
        Report $first,
        Report $second
    ): int {
        return (int)
            $this->calculateBreakdown(
                $first,
                $second
            )['score'];
    }


    public function calculateBreakdown(
        Report $first,
        Report $second
    ): array {
        $score = 0;
        $reasons = [];

        /*
        |--------------------------------------------------------------------------
        | CATEGORY - 20
        |--------------------------------------------------------------------------
        */

        if (
            !empty($first->category) &&
            !empty($second->category) &&
            Str::lower(
                trim(
                    (string)
                    $first->category
                )
            ) ===
            Str::lower(
                trim(
                    (string)
                    $second->category
                )
            )
        ) {
            $score += 20;
            $reasons[] =
                'Same item category (+20)';
        }

        /*
        |--------------------------------------------------------------------------
        | ITEM NAME - 20
        |--------------------------------------------------------------------------
        */

        $firstTitle =
            Str::lower(
                trim(
                    (string)
                    $first->title
                )
            );

        $secondTitle =
            Str::lower(
                trim(
                    (string)
                    $second->title
                )
            );

        if (
            $firstTitle !== '' &&
            $secondTitle !== ''
        ) {
            if (
                $firstTitle ===
                $secondTitle
            ) {
                $score += 20;
                $reasons[] =
                    'Same item name (+20)';
            } elseif (
                Str::contains(
                    $firstTitle,
                    $secondTitle
                ) ||
                Str::contains(
                    $secondTitle,
                    $firstTitle
                )
            ) {
                $score += 15;
                $reasons[] =
                    'Very similar item name (+15)';
            } else {
                $firstTitleWords =
                    $this->extractKeywords(
                        $firstTitle
                    );

                $secondTitleWords =
                    $this->extractKeywords(
                        $secondTitle
                    );

                $commonTitleWords =
                    array_values(
                        array_intersect(
                            $firstTitleWords,
                            $secondTitleWords
                        )
                    );

                $titlePoints =
                    min(
                        10,
                        count(
                            $commonTitleWords
                        ) * 5
                    );

                if ($titlePoints > 0) {
                    $score +=
                        $titlePoints;

                    $reasons[] =
                        'Shared item-name keywords (+'
                        . $titlePoints
                        . ')';
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | DESCRIPTION - 20
        |--------------------------------------------------------------------------
        */

        $descriptionMatches =
            array_values(
                array_intersect(
                    $this->extractKeywords(
                        (string)
                        $first->description
                    ),
                    $this->extractKeywords(
                        (string)
                        $second->description
                    )
                )
            );

        $descriptionPoints =
            min(
                20,
                count(
                    $descriptionMatches
                ) * 4
            );

        if ($descriptionPoints > 0) {
            $score +=
                $descriptionPoints;

            $reasons[] =
                'Matching description details (+'
                . $descriptionPoints
                . ')';
        }

        /*
        |--------------------------------------------------------------------------
        | COLOR - 15
        |--------------------------------------------------------------------------
        */

        if (
            !empty($first->color) &&
            !empty($second->color)
        ) {
            $firstColor =
                Str::lower(
                    trim(
                        (string)
                        $first->color
                    )
                );

            $secondColor =
                Str::lower(
                    trim(
                        (string)
                        $second->color
                    )
                );

            if (
                $firstColor ===
                $secondColor
            ) {
                $score += 15;
                $reasons[] =
                    'Same color (+15)';
            } elseif (
                Str::contains(
                    $firstColor,
                    $secondColor
                ) ||
                Str::contains(
                    $secondColor,
                    $firstColor
                )
            ) {
                $score += 10;
                $reasons[] =
                    'Similar color (+10)';
            }
        }

        /*
        |--------------------------------------------------------------------------
        | LOCATION - 15
        |--------------------------------------------------------------------------
        */

        if (
            !empty($first->location) &&
            !empty($second->location)
        ) {
            $firstLocation =
                Str::lower(
                    trim(
                        (string)
                        $first->location
                    )
                );

            $secondLocation =
                Str::lower(
                    trim(
                        (string)
                        $second->location
                    )
                );

            if (
                $firstLocation ===
                $secondLocation
            ) {
                $score += 15;
                $reasons[] =
                    'Same location (+15)';
            } elseif (
                Str::contains(
                    $firstLocation,
                    $secondLocation
                ) ||
                Str::contains(
                    $secondLocation,
                    $firstLocation
                )
            ) {
                $score += 10;
                $reasons[] =
                    'Similar location area (+10)';
            }
        }

        /*
        |--------------------------------------------------------------------------
        | DATE - 10
        |--------------------------------------------------------------------------
        */

        if (
            $first->date_occurred &&
            $second->date_occurred
        ) {
            $difference =
                abs(
                    $first
                        ->date_occurred
                        ->diffInDays(
                            $second
                                ->date_occurred,
                            false
                        )
                );

            $datePoints = 0;

            if ($difference === 0) {
                $datePoints = 10;
            } elseif ($difference <= 1) {
                $datePoints = 8;
            } elseif ($difference <= 3) {
                $datePoints = 5;
            } elseif ($difference <= 7) {
                $datePoints = 2;
            }

            if ($datePoints > 0) {
                $score +=
                    $datePoints;

                $reasons[] =
                    'Close report dates (+'
                    . $datePoints
                    . ')';
            }
        }

        return [
            'score' =>
                min(
                    100,
                    $score
                ),

            'reasons' =>
                $reasons,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | REPORT STATUS RESTORATION
    |--------------------------------------------------------------------------
    */

    public function hasOtherConfirmedMatch(
        Report $report,
        int $excludeMatchId
    ): bool {
        $query =
            PotentialMatch::query()
                ->where(
                    'status',
                    'confirmed'
                )
                ->where(
                    'id',
                    '<>',
                    $excludeMatchId
                );

        if ($report->isLost()) {
            $query->where(
                'lost_report_id',
                $report->id
            );
        } else {
            $query->where(
                'found_report_id',
                $report->id
            );
        }

        return $query->exists();
    }


    public function restoreBaseStatusAfterReview(
        Report $report,
        int $excludeMatchId
    ): void {
        if (
            $report->status !==
            Report::STATUS_POTENTIAL_MATCH_FOUND
        ) {
            return;
        }

        if (
            $this->hasOtherConfirmedMatch(
                $report,
                $excludeMatchId
            )
        ) {
            return;
        }

        $report->update([
            'status' =>
                $report->isLost()
                    ? Report::STATUS_APPROVED
                    : Report::STATUS_VERIFIED,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    protected function extractKeywords(
        ?string $text
    ): array {
        if (empty($text)) {
            return [];
        }

        $text =
            Str::lower(
                preg_replace(
                    '/[^a-z0-9\s]+/',
                    ' ',
                    $text
                )
            );

        $words =
            array_filter(
                array_map(
                    'trim',
                    preg_split(
                        '/\s+/',
                        $text
                    )
                ),
                fn ($word) =>
                    strlen(
                        $word
                    ) >= 3
            );

        return array_values(
            array_unique(
                $words
            )
        );
    }


    protected function buildReviewNotes(
        int $score,
        array $reasons,
        string $source
    ): string {
        $reasonText =
            $reasons
                ? implode(
                    '; ',
                    $reasons
                )
                : 'No strong field-level reason recorded.';

        return
            "{$source}. Similarity score: {$score}/100. {$reasonText}";
    }
}
