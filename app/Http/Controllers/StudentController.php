<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Laravel\Lumen\Routing\Controller;

class StudentController extends Controller
{
    public function get()
    {
        $student = Student::where('login', $_SERVER["PHP_AUTH_USER"])->first();
        $student->semesters = $this->semesters($student);
        return response()->json($student);
    }

    // To remove empty semesters due to double diploma
    public static function semesters($student) {
        $semestersObject = $student->semesters()->get()->filter(function ($sem) use ($student) {
            return count($student->uvs()->where('semester_id', $sem->id)->get()) > 0;
        });
        // all method not work here, so need to convert manually
        $semesterArray = [];
        foreach ($semestersObject as $sem) $semesterArray[] = $sem;
        return $semesterArray;
    }

}