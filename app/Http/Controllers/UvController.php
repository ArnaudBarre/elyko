<?php
namespace App\Http\Controllers;

use App\Models\StudentUv;
use App\Models\Uv;
use Laravel\Lumen\Routing\Controller;

class UvController extends Controller
{
    public function get($id)
    {
        $values = ['A' => 18.5, 'B' => 15.5, 'C' => 13, 'D' => 11, 'E' => 9, 'FX' => 6.5, 'F' => 2.5];
        $uv = Uv::find($id);
        $inscriptions = StudentUv::where(['uv_id' => $id])->get();
        $detail = ['name' => $uv->name, 'credits' => $uv->credits,
            'grades' => ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'FX' => 0, 'F' => 0]];
        $total = $gradeNumber = 0;
        foreach ($inscriptions as $inscription) {
            $grade = $inscription['grade'];
            if (array_key_exists($grade, $values)) {
                $detail['grades'][$grade]++;
                $total += $values[$grade];
                $gradeNumber++;
            }
        }
        if ($gradeNumber)
            $detail['average'] = EvaluationController::digitToLetter($total / $gradeNumber);
        return response()->json($detail);
    }
}