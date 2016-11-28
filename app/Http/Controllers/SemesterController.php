<?php
namespace App\Http\Controllers;

use App\Models\Semester;
use App\Models\Student;
use Laravel\Lumen\Routing\Controller;

class SemesterController extends Controller
{
    public function get($id)
    {
        $student_id = Student::where('login', $_SERVER['PHP_AUTH_USER'])->first()->id;
        $semester = Semester::with([
            'uvs' => function ($query) use ($student_id) {
                $query->join('student_uv', 'student_uv.uv_id', '=', 'uvs.id');
                $query->where('student_id', $student_id);
            },
            'uvs.evaluations' => function ($query) use ($student_id) {
                $query->where('locked', true);
                $query->join('evaluation_student', 'evaluation_student.evaluation_id', '=', 'evaluations.id');
                $query->where('student_id', $student_id);
                $query->orderBy('id', 'desc');
            },
        ])->where('id', $id)->first();
        $semester['gpa'] = $this->gpa($semester);
        $uvs = $semester->uvs->toArray();
        usort($uvs, function ($a, $b) {
            if ($a['evaluations'] && $b['evaluations'])
                return $a['evaluations'][0]['id'] < $b['evaluations'][0]['id'] ? 1 : -1;
            else
                return $a['evaluations'] ? -1 : 1;
        });
        unset($semester->uvs);
        $semester->uvs = $uvs;
        return response()->json($semester);
    }

    public function getLast()
    {
        $semesters = StudentController::semesters(Student::where('login', $_SERVER['PHP_AUTH_USER'])->first());
        return $this->get(end($semesters)->id);
    }

    function gpa($semester)
    {
        $values = ['A' => 4, 'B' => 3.5, 'C' => 3, 'D' => 2.5, 'E' => 2];
        $gpa = $total = $totalECTS = 0;
        foreach ($semester['uvs'] as $uv) {
            $grade = $uv['grade'];
            $credits = $uv['credits'];
            if (!is_numeric($grade))
                $grade = array_key_exists($grade, $values) ? $values[$grade] : 0;
            $total += $grade * $credits;
            $totalECTS += $credits;
        }
        if ($totalECTS)
            $gpa = round($total / $totalECTS, 2);
        return $gpa;
    }
}