<?php
/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/
$factory->define(App\Models\Student::class, function ($faker) {
    $firstName = $faker->firstName;
    $lastName = $faker->lastName;
    return [
        'id' => $faker->unique()->numberBetween(0, 10000),
        'name' => $firstName,
        'last_name' => $lastName,
        'login' => strtolower(substr($firstName, 0, 1) . substr($lastName, 0, 5)) . '14',
        'email' => $faker->email,
        'up_to_date' => true
    ];
});
$factory->define(App\Models\Uv::class, function ($faker) {
    return [
        'id' => $faker->unique()->numberBetween(0, 10000),
        'name' => $faker->lastName,
        'credits' => $faker->numberBetween(2, 5),
        'up_to_date' => true
    ];
});
$factory->define(App\Models\Evaluation::class, function ($faker) {
    return [
        'id' => $faker->unique()->numberBetween(0, 10000),
        'name' => $faker->lastName,
        'coefficient' => $faker->randomFloat(2, 0, 1),
        'locked' => true,
        'up_to_date' => true
    ];
});
$factory->define(App\Models\Semester::class, function ($faker) {
    return [
        'id' => $faker->unique()->numberBetween(0, 10000),
        'name' => $faker->lastName,
        'up_to_date' => true
    ];
});
$factory->define(App\Models\SkillAssessed::class, function ($faker) {
    return [
        'skill_name' => $faker->randomElement(['STA', 'STB', 'STC',
            'IGA', 'IGB','interpA', 'interpB', 'intraA', 'intraB', 'intraC']),
        'value' => $faker->randomElement(['-', '+', '=']),
        'up_to_date' => true
    ];
});
$factory->define(App\Models\SemesterStudent::class, function () {
    return [
        'up_to_date' => true
    ];
});
$factory->define(App\Models\StudentUv::class, function ($faker) {
    return [
        'grade' => $faker->randomElement(['A', 'B', 'C', 'D', 'E', 'F', 'FX']),
        'up_to_date' => true
    ];
});
$factory->define(App\Models\EvaluationStudent::class, function ($faker) {
    if (rand()/getrandmax() < 0.7)
        $mark = $faker->randomFloat(1, 0, 20);
    else
        $mark = $faker->randomElement(['A', 'B', 'C', 'D', 'E', 'F', 'FX']);
    return [
        'mark' => $mark,
        'up_to_date' => true
    ];
});