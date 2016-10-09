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
            },
        ])->where('id', $id)->first();
        $semester['gpa'] = $this->gpa($semester);
        return response()->json($semester);
    }

    public function getLast()
    {
        $semester_id = Student::where('login', $_SERVER['PHP_AUTH_USER'])->first()->semesters()->get()->last()->id;
        return $this->get($semester_id);
    }

    function gpa($semester)
    {
        $gpa = $total = $totalECTS = 0;
        foreach ($semester['uvs'] as $uv) {
            $grade = $uv['grade'];
            $credits = $uv['credits'];
            if (!is_numeric($grade))
                $grade = $this->letterToDigit($grade);
            $total += $grade * $credits;
            $totalECTS += $credits;
        }
        if ($totalECTS)
            $gpa = round($total / $totalECTS, 2);
        return $gpa;
    }

    function letterToDigit($grade)
    {
        switch ($grade) {
            case "A" :
                $digit = 4;
                break;
            case "B" :
                $digit = 3.5;
                break;
            case "C" :
                $digit = 3;
                break;
            case "D" :
                $digit = 2.5;
                break;
            case "E" :
                $digit = 2;
                break;
            default :
                $digit = 0;
        }
        return $digit;
    }
}