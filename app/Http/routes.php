<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$app->get('/', function () {
    return redirect('/index.html');
});

$app->get('/student', 'StudentController@get');
$app->get('/marks', 'SemesterController@getLast');
$app->get('/marks/{semester_id}', 'SemesterController@get');
$app->get('/evaluation/{id}', 'EvaluationController@get');
$app->get('/skills/{semester_id}', 'SkillController@get');
$app->get('/uv/{id}', 'UvController@get');