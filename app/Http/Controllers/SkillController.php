<?php

namespace App\Http\Controllers;

use App\Models\SkillAssessed;
use App\Models\Student;
use Laravel\Lumen\Routing\Controller;

class SkillController extends Controller
{
    public function get($semester_id)
    {
        $student_id = Student::where(['login' => $_SERVER["PHP_AUTH_USER"]])->first()->id;
        $detail = ['IGA' => [1, 0], 'IGB' => [1, 0], 'interpA' => [1, 0], 'interpB' => [1, 0],
            'intraA' => [1, 0], 'intraB' => [1, 0], 'intraC' => [1, 0],
            'STA' => [1, 0], 'STB' => [1, 0], 'STC' => [1, 0]];
        $assessments = SkillAssessed::where(['semester_id' => $semester_id, 'student_id' => $student_id])->get();
        foreach ($assessments as $assessment) {
            $name = $assessment->skill_name;
            if (array_key_exists($name, $detail)) {
                $value = ($assessment->value == "+") ? 2 : (($assessment->value == "=") ? 1 : 0);
                $detail[$name][1]++;
                $detail[$name][0] = round(($detail[$name][0] * ($detail[$name][1] - 1) + $value) / $detail[$name][1], 1);
            }
        }
        return response()->json($detail);
    }
}