<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\CharacterRecord;
use App\Models\CharacterReflection;
use App\Models\CharacterBadge;
use App\Models\CharacterDimension;
use App\Models\StudentBadge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PortfolioController extends Controller
{
    /**
     * Show student portfolio overview.
     */
    public function index()
    {
        $student = Auth::guard('student')->user();
        $dimensions = CharacterDimension::forOwner($student->user_id)->active()->get();
        $records = CharacterRecord::where('student_id', $student->id)
            ->with('dimension')
            ->latest('record_date')
            ->paginate(20);

        $badges = StudentBadge::where('student_id', $student->id)
            ->with('badge')
            ->get();

        $reflections = CharacterReflection::where('student_id', $student->id)
            ->latest('reflection_date')
            ->take(5)
            ->get();

        // Calculate stats
        $stats = [
            'total_records' => CharacterRecord::where('student_id', $student->id)->count(),
            'positive' => CharacterRecord::where('student_id', $student->id)->where('type', 'positive')->count(),
            'negative' => CharacterRecord::where('student_id', $student->id)->where('type', 'negative')->count(),
            'earned_badges' => StudentBadge::where('student_id', $student->id)->where('is_earned', true)->count(),
        ];

        return view('student.portfolio.index', [
            'student' => $student,
            'dimensions' => $dimensions,
            'records' => $records,
            'badges' => $badges,
            'reflections' => $reflections,
            'stats' => $stats,
        ]);
    }

    /**
     * Show dimension details.
     */
    public function dimension(CharacterDimension $dimension)
    {
        $student = Auth::guard('student')->user();

        // Dimensi milik sekolah lain tidak boleh terbuka lewat tebakan id di URL.
        abort_unless($dimension->user_id === $student->user_id, 404);

        $records = CharacterRecord::where('student_id', $student->id)
            ->where('character_dimension_id', $dimension->id)
            ->latest('record_date')
            ->paginate(20);

        return view('student.portfolio.dimension', [
            'student' => $student,
            'dimension' => $dimension,
            'records' => $records,
        ]);
    }

    /**
     * Show reflection form.
     */
    public function createReflection(CharacterDimension $dimension = null)
    {
        $student = Auth::guard('student')->user();
        $dimensions = CharacterDimension::forOwner($student->user_id)->active()->get();

        abort_if($dimension && $dimension->user_id !== $student->user_id, 404);

        return view('student.portfolio.reflection-create', [
            'student' => $student,
            'dimension' => $dimension,
            'dimensions' => $dimensions,
        ]);
    }

    /**
     * Store reflection.
     */
    public function storeReflection(Request $request)
    {
        $student = Auth::guard('student')->user();

        $data = $request->validate([
            // Disaring ke dimensi milik wali kelas si siswa. Aturan exists polos
            // menerima id dimensi sekolah lain begitu saja.
            'character_dimension_id' => [
                'nullable',
                Rule::exists('character_dimensions', 'id')->where('user_id', $student->user_id),
            ],
            'reflection_date' => ['required', 'date', 'before_or_equal:today'],
            'self_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'period' => ['required', Rule::in(['daily', 'weekly', 'monthly'])],
            'what_went_well' => ['nullable', 'string', 'max:1000'],
            'what_to_improve' => ['nullable', 'string', 'max:1000'],
            'action_plan' => ['nullable', 'string', 'max:1000'],
        ]);

        $data['student_id'] = $student->id;
        // class_id diisi seperti jalur pembuatan lainnya di controller ini.
        // Tanpa ini refleksi yang ditulis siswa sendiri tidak punya kelas, dan
        // laporan mana pun yang menyaring per kelas akan melewatkannya.
        $data['class_id'] = $student->class_id;
        $data['status'] = CharacterReflection::STATUS_SUBMITTED;
        $data['submitted_at'] = now();

        /*
         * user_id diisi tegas dari wali kelas si siswa.
         *
         * Siswa masuk lewat guard 'student', sedangkan BelongsToTenant hanya
         * membaca guard bawaan — jadi trait itu tidak punya siapa pun untuk
         * dijadikan pemilik di sini. Ditambah user_id memang tidak masuk
         * $fillable, create() akan membuangnya dan refleksi tersimpan tanpa
         * pemilik: tersaring habis oleh TenantScope, sehingga wali kelas tidak
         * pernah melihat refleksi yang ditulis siswanya.
         */
        (new CharacterReflection)->forceFill($data + ['user_id' => $student->user_id])->save();

        return redirect()->route('student.portfolio')->with('success', 'Refleksi berhasil disimpan.');
    }

    /**
     * Store achievement (student's own positive record).
     */
    public function storeAchievement(Request $request)
    {
        $student = Auth::guard('student')->user();

        $data = $request->validate([
            'character_dimension_id' => [
                'required',
                Rule::exists('character_dimensions', 'id')->where('user_id', $student->user_id),
            ],
            'record_date' => ['required', 'date', 'before_or_equal:today'],
            'title' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        // Disaring ke pemilik, sama seperti aturan validasinya di atas — bukan
        // find() polos yang menerima dimensi sekolah mana pun.
        $dimension = CharacterDimension::forOwner($student->user_id)
            ->find($data['character_dimension_id']);

        $score = $dimension?->positive_score ?? 5;

        $record = CharacterRecord::create([
            'student_id' => $student->id,
            'class_id' => $student->class_id,
            'user_id' => $student->user_id,
            'character_dimension_id' => $data['character_dimension_id'],
            'type' => 'achievement',
            'score' => $score,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'record_date' => $data['record_date'],
        ]);

        /*
         * Lewat update(), BUKAN increment().
         *
         * increment() adalah operasi query builder: ia menembus mutator
         * disciplinePoints() pada model Student yang mengurung poin di rentang
         * 0-100. Akibatnya poin bisa melewati 100 dan seluruh ambang yang
         * bersandar padanya — badge disciplineTone() (80/50), needsAttention()
         * (<50), rata-rata di halaman analitik — kehilangan artinya.
         */
        $student->update(['discipline_points' => $student->discipline_points + $score]);

        // Check badges
        $this->checkBadges($student);

        return redirect()
            ->route('student.portfolio')
            ->with('success', 'Pencapaian berhasil dicatat. +' . $score . ' poin karakter.');
    }

    /**
     * Store observation (student's own observation).
     */
    public function storeObservation(Request $request)
    {
        $student = Auth::guard('student')->user();

        $data = $request->validate([
            'character_dimension_id' => [
                'required',
                Rule::exists('character_dimensions', 'id')->where('user_id', $student->user_id),
            ],
            'record_date' => ['required', 'date', 'before_or_equal:today'],
            'type' => ['required', Rule::in(['positive', 'negative', 'observation'])],
            'title' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        /*
         * Poin ditentukan bobot dimensinya, BUKAN dikirim dari formulir.
         *
         * Aturan lama menerima score apa pun antara -10 dan 10 dari request,
         * padahal catatan ini ditulis siswa untuk dirinya sendiri dan poinnya
         * langsung menambah discipline_points. Artinya siswa bisa memberi
         * dirinya sepuluh poin sekali kirim, berkali-kali.
         */
        $dimension = CharacterDimension::forOwner($student->user_id)
            ->find($data['character_dimension_id']);

        $score = match ($data['type']) {
            'positive' => $dimension?->positive_score ?? 5,
            'negative' => $dimension?->negative_score ?? -5,
            default => 0, // 'observation' bersifat netral: dicatat, tidak berpoin.
        };

        CharacterRecord::create([
            'student_id' => $student->id,
            'class_id' => $student->class_id,
            'user_id' => $student->user_id,
            'character_dimension_id' => $data['character_dimension_id'],
            'type' => $data['type'],
            'score' => $score,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'record_date' => $data['record_date'],
            'context' => 'self-reported',
        ]);

        /*
         * Sama seperti pencapaian: lewat update() supaya mutator pengurung
         * 0-100 pada Student ikut berlaku.
         *
         * Untuk catatan negatif ini bukan sekadar kerapian. decrement() menulis
         * langsung ke kolom `discipline_points` yang bertipe UNSIGNED, dan
         * MySQL di sini berjalan dengan STRICT_TRANS_TABLES: siswa berpoin 3
         * yang mencatat hal negatif bernilai -5 membuat kolomnya menuju -2 dan
         * kueri itu gagal — halaman galat 500, catatannya sudah terlanjur
         * tersimpan, poinnya tidak pernah berubah.
         */
        if ($score !== 0) {
            $student->update(['discipline_points' => $student->discipline_points + $score]);
        }

        // Check badges
        $this->checkBadges($student);

        return redirect()
            ->route('student.portfolio')
            ->with('success', 'Catatan berhasil disimpan.');
    }

    /**
     * Check and update badges.
     */
    private function checkBadges($student): void
    {
        // Disaring ke pemilik: lencana milik sekolah lain tidak boleh ikut
        // diperiksa, apalagi diberikan, kepada siswa di sini.
        $badges = CharacterBadge::where('user_id', $student->user_id)
            ->where('is_active', true)
            ->get();

        foreach ($badges as $badge) {
            $studentBadge = StudentBadge::firstOrCreate([
                'student_id' => $student->id,
                'character_badge_id' => $badge->id,
            ]);

            $progress = 0;
            if ($badge->criteria_type === CharacterBadge::CRITERIA_COUNT) {
                $progress = CharacterRecord::where('student_id', $student->id)
                    ->where('character_dimension_id', $badge->character_dimension_id)
                    ->where('type', 'positive')
                    ->count();
            } elseif ($badge->criteria_type === CharacterBadge::CRITERIA_SCORE) {
                $progress = CharacterRecord::where('student_id', $student->id)
                    ->where('character_dimension_id', $badge->character_dimension_id)
                    ->where('type', 'positive')
                    ->sum('score');
            }

            $isEarned = $badge->checkCriteria($progress);

            $studentBadge->update([
                'current_progress' => $progress,
                'is_earned' => $isEarned,
                'earned_at' => $isEarned && !$studentBadge->earned_at ? now() : $studentBadge->earned_at,
            ]);
        }
    }
}
