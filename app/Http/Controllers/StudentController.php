<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Laravel\Lumen\Routing\Controller;

class StudentController extends Controller
{
    public function get()
    {
        $student = Student::where('login', $_SERVER["PHP_AUTH_USER"])->first();
        $student['semesters'] = $student->semesters;
        return response()->json($student);
    }
}