<?php
use App\Models\Evaluation;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Uv;
use App\Models\StudentUv;
use App\Models\SemesterStudent;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory('App\Models\Student', 3)->create();
        echo "students seeded \n";
        factory('App\Models\Semester', 4)->create();
        echo "semesters seeded \n";
        foreach (Student::all() as $student) {
            foreach (Semester::all() as $semester) {
                factory('App\Models\SemesterStudent')
                    ->create(['semester_id' => $semester->id, 'student_id' => $student->id]);
                factory('App\Models\SkillAssessed', random_int(8, 15))
                    ->create(['semester_id' => $semester->id, 'student_id' => $student->id]);
            }
        }
        echo "semester_student and skillsAssessed seeded \n";
        foreach (Semester::all() as $semester) {
            factory('App\Models\Uv', random_int(8, 12))->create((['semester_id' => $semester->id]));
        }
        echo "uvs seeded \n";
        foreach (Student::all() as $student) {
            foreach (Uv::all() as $uv) {
                if (rand() / getrandmax() < 0.8)
                    factory('App\Models\StudentUv')->create(['student_id' => $student->id, 'uv_id' => $uv->id]);
            }
        }
        echo "student_uv seeded \n";
        foreach (Uv::all() as $uv) {
            factory('App\Models\Evaluation', random_int(0, 3))->create(['uv_id' => $uv->id]);
            if (rand() / getrandmax() < 0.1)
                factory('App\Models\Evaluation')->create(['uv_id' => $uv->id, 'locked' => false]);
        }
        echo "evaluations seeded \n";
        foreach (StudentUv::all() as $studentUv) {
            foreach (Evaluation::where('uv_id', $studentUv->uv_id)->get() as $evaluation) {
                factory('App\Models\EvaluationStudent')->create(['evaluation_id' => $evaluation->id,
                    'student_id' => $studentUv->student_id]);
            }
        }
        echo "evaluation_student seeded \n";
    }
}
