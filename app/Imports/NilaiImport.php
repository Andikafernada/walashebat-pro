<?php

namespace App\Imports;

use App\Models\Assessment;
use App\Models\AssessmentScore;
use App\Models\Classroom;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;

class NilaiImport implements ToCollection
{
    public int $updatedCount = 0;
    public array $errors = [];

    public function __construct(
        protected Classroom $classroom,
        protected Assessment $assessment
    ) {}

    public function collection(Collection $rows)
    {
        // Skip header row
        $dataRows = $rows->slice(1);
        $validStudentIds = $this->classroom->students()->pluck('id')->flip()->all();

        DB::transaction(function () use ($dataRows, $validStudentIds) {
            foreach ($dataRows as $index => $row) {
                // Kolom: [0: No, 1: Student_ID, 2: NIS, 3: Nama, 4: L/P, 5: Nilai]
                $studentId = (int) ($row[1] ?? 0);
                $rawNilai = $row[5] ?? null;

                if (!$studentId || !isset($validStudentIds[$studentId])) {
                    continue; // Lewati baris kosong / id tidak valid
                }

                if ($rawNilai === null || trim((string) $rawNilai) === '') {
                    continue; // Nilai belum diisi
                }

                $nilai = (float) str_replace(',', '.', (string) $rawNilai);
                if ($nilai < 0 || $nilai > 100) {
                    $this->errors[] = "Baris " . ($index + 2) . ": Nilai {$nilai} di luar batas (0-100).";
                    continue;
                }

                AssessmentScore::updateOrCreate(
                    [
                        'assessment_id' => $this->assessment->id,
                        'student_id' => $studentId,
                    ],
                    [
                        'user_id' => $this->classroom->user_id,
                        'nilai' => $nilai,
                    ]
                );

                $this->updatedCount++;
            }
        });
    }
}
