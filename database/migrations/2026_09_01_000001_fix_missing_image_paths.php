<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

return new class extends Migration {
    public function up(): void
    {
        // Get all actual image files from storage
        $storagePath = storage_path('app/public/report-images');

        if (!File::exists($storagePath)) {
            return;
        }

        $imageFiles = File::files($storagePath);

        if (empty($imageFiles)) {
            return;
        }

        // Clear existing report_images records with non-existent paths
        DB::table('report_images')
            ->where('file_path', 'like', 'reports/seed/%')
            ->delete();

        // Get reports that don't have images
        $reportsWithoutImages = DB::table('reports')
            ->leftJoin('report_images', 'reports.id', '=', 'report_images.report_id')
            ->whereNull('report_images.id')
            ->select('reports.id')
            ->distinct()
            ->get();

        $reportIds = $reportsWithoutImages->pluck('id')->toArray();

        // Assign actual image files to reports
        $fileIndex = 0;
        foreach ($reportIds as $reportId) {
            if ($fileIndex >= count($imageFiles)) {
                break;
            }

            $imageFile = $imageFiles[$fileIndex];
            $relativePath = 'report-images/' . $imageFile->getFilename();

            DB::table('report_images')->insert([
                'report_id' => $reportId,
                'file_path' => $relativePath,
                'file_size' => $imageFile->getSize(),
                'mime_type' => mime_content_type($imageFile->getRealPath()),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $fileIndex++;
        }
    }

    public function down(): void
    {
        // Cannot safely revert
    }
};
