<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Lesson;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LessonSchedulePrintController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = Auth::user();
        abort_unless($user !== null, 401);

        $displayTz = config('booking.display_timezone', 'Europe/Minsk');
        $rawWeek = $request->query('week');

        try {
            $weekStart = $rawWeek
                ? Carbon::parse((string) $rawWeek, $displayTz)->startOfWeek(Carbon::MONDAY)
                : Carbon::now($displayTz)->startOfWeek(Carbon::MONDAY);
        } catch (\Throwable $e) {
            $weekStart = Carbon::now($displayTz)->startOfWeek(Carbon::MONDAY);
        }

        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $prevWeek = $weekStart->copy()->subWeek()->format('Y-m-d');
        $nextWeek = $weekStart->copy()->addWeek()->format('Y-m-d');
        $currentWeek = Carbon::now($displayTz)->startOfWeek(Carbon::MONDAY)->format('Y-m-d');

        // Safety margin for UTC database conversion
        $startUtc = $weekStart->copy()->subDays(1)->startOfDay()->utc();
        $endUtc = $weekEnd->copy()->addDays(1)->endOfDay()->utc();

        $lessons = Lesson::query()
            ->with(['tutor.tutorProfile', 'student', 'parent'])
            ->when($user->isTutor(), fn ($q) => $q->where('tutor_id', $user->id))
            ->when($user->isStudent(), fn ($q) => $q->where('student_id', $user->id))
            ->when($user->isParent(), fn ($q) => $q->where('parent_id', $user->id))
            ->where('start_time', '>=', $startUtc)
            ->where('start_time', '<=', $endUtc)
            ->where('status', '!=', Lesson::STATUS_CANCELLED)
            ->orderBy('start_time')
            ->get();

        $days = [];
        $dayNamesRu = [
            1 => 'Понедельник',
            2 => 'Вторник',
            3 => 'Среда',
            4 => 'Четверг',
            5 => 'Пятница',
            6 => 'Суббота',
            7 => 'Воскресенье',
        ];

        $totalLessons = 0;
        $totalMinutes = 0;
        $uniqueStudents = [];

        for ($i = 0; $i < 7; $i++) {
            $dayCarbon = $weekStart->copy()->addDays($i);

            $dayLessons = $lessons->filter(function (Lesson $lesson) use ($dayCarbon, $displayTz): bool {
                return $lesson->start_time->setTimezone($displayTz)->isSameDay($dayCarbon);
            })->values();

            foreach ($dayLessons as $l) {
                $totalLessons++;
                $totalMinutes += (int) ($l->duration_minutes ?: 60);
                if ($l->student_id) {
                    $uniqueStudents[$l->student_id] = true;
                }
            }

            $days[] = [
                'date' => $dayCarbon,
                'date_formatted' => $dayCarbon->translatedFormat('j F Y'),
                'day_name' => $dayNamesRu[$dayCarbon->dayOfWeekIso],
                'is_today' => $dayCarbon->isSameDay(Carbon::now($displayTz)),
                'is_weekend' => $dayCarbon->dayOfWeekIso >= 6,
                'lessons' => $dayLessons,
            ];
        }

        $totalHours = round($totalMinutes / 60, 1);
        $totalStudentsCount = count($uniqueStudents);

        return view('lessons.print-weekly', [
            'user' => $user,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'prevWeek' => $prevWeek,
            'nextWeek' => $nextWeek,
            'currentWeek' => $currentWeek,
            'days' => $days,
            'totalLessons' => $totalLessons,
            'totalHours' => $totalHours,
            'totalStudentsCount' => $totalStudentsCount,
            'displayTz' => $displayTz,
        ]);
    }
}
