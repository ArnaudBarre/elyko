<?php
namespace App\Http\Controllers;

use App\Models\Evaluation;
use App\Models\EvaluationStudent;
use Laravel\Lumen\Routing\Controller;

class EvaluationController extends Controller
{
    public function get($id)
    {
        $values = ['A' => 18.5, 'B' => 15.5, 'C' => 13, 'D' => 11, 'E' => 9, 'FX' => 6.5, 'F' => 2.5];
        $evaluation = Evaluation::find($id);
        $assessments = EvaluationStudent::where(['evaluation_id' => $id])->get();
        $detail = ['name' => $evaluation->name, 'coefficient' => $evaluation->coefficient,
            'marks' => ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'FX' => 0, 'F' => 0]];
        $total = $marksNumber = 0;
        $withDigits = false;
        foreach ($assessments as $assessment) {
            $mark = $assessment['mark'];
            if (is_numeric($mark)) {
                $withDigits = true;
                $total += $mark;
                $marksNumber++;
                $mark = $this->digitToLetter($mark);
                $detail['marks'][$mark]++;
            } else if (array_key_exists($mark, $values)) {
                $detail['marks'][$mark]++;
                $total += $values[$mark];
                $marksNumber++;
            }
        }
        if ($withDigits && $marksNumber)
            $detail['average'] = round($total / $marksNumber, 2);
        else if ($marksNumber)
            $detail['average'] = $this->digitToLetter($total / $marksNumber);
        return response()->json($detail);
    }

    public static function digitToLetter($mark)
    {
        if ($mark < 5) $letter = "F";
        else if ($mark < 8) $letter = "FX";
        else if ($mark < 10) $letter = "E";
        else if ($mark < 12) $letter = "D";
        else if ($mark < 14) $letter = "C";
        else if ($mark < 17) $letter = "B";
        else $letter = "A";
        return $letter;
    }
}