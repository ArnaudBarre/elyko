<?php

/**
 * Data migration from Oasis to Elyko
 *
 * The Upload from the Oasis view provided containing only students but all this process, evaluations and marks.
 * The base of destination is Elyko, on the cagiva server. The tables are: evaluation, and evaluation_student skillsAssessed.
 * Key steps:
 * - Request SELECT
 * - Iteration on the result ($ row = mssql_fetch_assoc ($ result)) to store it in PHP array
 * - Set up_to_date to false
 * - Transaction containing:
 *     - Insert via SQL Replace (with up_to_date = true)
 *     - Remove of data not up_to_date
 *
 * Different ways to check if the script works:
 * On display:
 * - Array sizes are close to usual values
 * - The execution time is close to the usual time
 * - Transactions are committed
 * In the database:
 * - All data have up_to_date set to true
 */

define('OasisHost', 'bddoasis.emn.fr:1433');
define('OasisUser', 'elyko');
define('OasisPwd', env('OASIS_PASSWORD'));
define('OasisDB', 'Notes-eleves');
define('ElykoHost', env('DB_HOST'));
define('ElykoUser', env('DB_USERNAME'));
define('ElykoPwd', env('DB_PASSWORD'));
define('ElykoDB', env('DB_DATABASE'));

// To measure the execution time
$timeStart = microtime(true);

// Connexion to Oasis DB
$connOasis = mssql_connect(OasisHost, OasisUser, OasisPwd, OasisDB);

// Select students
$request = "SELECT intIdUtilisateur AS 'id', strNom AS 'last_name', strPrenom AS 'name', strEmail AS 'email', strLogin AS 'login'
        FROM eleves";
$result = mssql_query($request, $connOasis);
$students = [];
while ($row = mssql_fetch_assoc($result))
    $students[] = $row;

// Select semesters
$request = "SELECT DISTINCT sem.intIdProcess AS 'id', sem.strNom AS 'name'
        FROM eleves student
        -- Inscription of student to a semester
        INNER JOIN inscription_process isem ON isem.intIdUser = student.intIdUtilisateur
        -- Semester: process of ENS type and level 2
        INNER JOIN process sem ON sem.intIdProcess = isem.intIdProcess AND sem.strTypeReferentiel = 'ENS' AND sem.intNiveau = 2
        ORDER BY id DESC";
$result = mssql_query($request, $connOasis);
$semesters = [];
while ($row = mssql_fetch_assoc($result))
    $semesters[] = $row;

// Select semester_student
$request = "SELECT sem.intIdProcess AS 'semester_id', eleves.intIdUtilisateur AS 'student_id'
        FROM eleves student
        -- Inscription of student to a semester
        INNER JOIN inscription_process isem ON isem.intIdUser = student.intIdUtilisateur
        -- Semester: process of ENS type and level 2
        INNER JOIN process sem ON sem.intIdProcess = isem.intIdProcess AND sem.strTypeReferentiel = 'ENS' AND sem.intNiveau = 2
        ORDER BY semester_id DESC";
$result = mssql_query($request, $connOasis);
$semester_student = [];
while ($row = mssql_fetch_assoc($result))
    $semester_student[] = $row;

// Select uvs
$request = "SELECT DISTINCT uv.intIdProcess AS 'id', uv.strNom AS 'name', sem.intIdProcess AS 'semester_id', credits.strValeur AS 'credits'
        FROM eleves student
        -- Inscription of student to a semester
        INNER JOIN Inscription_process isem ON isem.intIdUser = student.intIdUtilisateur
        -- Semester: process of ENS type and level 2
        INNER JOIN process sem ON sem.intIdProcess = isem.intIdProcess AND sem.strTypeReferentiel = 'ENS' AND sem.intNiveau = 2
        -- Inscription of student to an UV
        INNER JOIN inscription_process iuv ON iuv.intIdUser = student.intIdUtilisateur
        -- UV: process of FPC type with children (permit to not select skills)
        INNER JOIN process uv ON uv.intIdProcess = iuv.intIdProcess AND uv.strTypeReferentiel = 'FPC' AND uv.intNbFils > 0
        -- Credits: store in dyn_valeurs with intIdChamp = 1143 and idProcess in string format
        LEFT OUTER JOIN dyn_valeurs credits ON credits.intIdChamp = 1143 AND credits.intIdRef = CAST(uv.intIdProcess AS NVARCHAR(255))
        -- Where the semester is the on of the UV
        WHERE uv.strLstParents LIKE sem.strLstParents + CAST(sem.intIdProcess AS NVARCHAR(255)) + ',%'
        -- We keep only process with numeric credits
        AND ISNUMERIC(credits.strValeur) > 0
        ORDER BY id DESC, credits DESC";
$result = mssql_query($request, $connOasis);
$uvs = [];
while ($row = mssql_fetch_assoc($result)) {
    // Remove UVs without credits
    if ($row['credits'] > 0) {
        // There are sometimes two credits associated to a same UV, we keep the most important
        if ($i == 0 || $row['id'] != $uvs[$i - 1]['id'])
            $uvs[] = $row;
    }
}

// Select student_uv
$request = "SELECT eleves.intIdUtilisateur AS 'student_id', UV.intIdProcess AS 'uv_id', grade.strValeur AS 'gradeCalcule', gradeForce.strGrade AS 'gradeForce'
        FROM eleves student
        -- Inscription of student to UV
        INNER JOIN Inscription_process iUV ON iUV.intIdUser = student.intIdUtilisateur
        -- UV: process of FPC type with children (permit to not select skills)
        INNER JOIN process UV ON UV.intIdProcess = iUV.intIdProcess AND UV.strTypeReferentiel = 'FPC' AND UV.intNbFils > 0
        -- Computed grade: store in dyn_valeurs with intIdChamp = 1383 and inscription id in string format
        INNER JOIN dyn_valeurs grade ON grade.intIdChamp = 1383 AND grade.intIdRef = CAST(iUV.intIdInscription AS NVARCHAR(255))
        -- Forced grade (if existed): store in bdn_buletin
        LEFT OUTER JOIN bdn_bulletin gradeForce ON gradeForce.intIdEleve = eleves.intIdUtilisateur AND gradeForce.intIdProcess = UV.intIdProcess
        ORDER BY uv_id";
$result = mssql_query($request, $connOasis);
$student_uv = [];
$i = 0;
while ($row = mssql_fetch_assoc($result)) {
    $student_uv[$i]['student_id'] = $row['student_id'];
    $student_uv[$i]['uv_id'] = $row['uv_id'];
    if ($row['gradeForce'] == " " or $row['gradeForce'] == "") // Test if there is a forced grade
        $student_uv[$i]['grade'] = $row['gradeCalcule']; // No: take the computed grade
    else
        $student_uv[$i]['grade'] = $row['gradeForce']; // Yes: take the forced grade
    $i++;
}

// Select evaluations
$request = "SELECT DISTINCT eval.intIdEvaluation AS 'id', eval.strTitre AS 'name', module.intIdProcess AS 'uv_id', eval.decCoefficient AS 'coefficient', eval.boolBloque AS 'locked'
        FROM eleves student
        INNER JOIN bdn_notes mark ON mark.intIdEleve = student.intIdUtilisateur
        INNER JOIN evaluations eval ON eval.intIdEvaluation = mark.intIdEvaluation
        -- Associated to modules
        INNER JOIN evaluations module ON eval.intIdBlocParent = module.intIdEvaluation
        ORDER BY id DESC";
$result = mssql_query($request, $connOasis);
$evaluations = [];
while ($row = mssql_fetch_assoc($result))
    $evaluations[] = $row;

// Select evaluation_student
$request = "SELECT mark.intIdEvaluation AS 'evaluation_id', mark.intIdEleve AS 'student_id', mark.strvaleur AS 'value'
        FROM eleves student
        INNER JOIN bdn_notes mark ON mark.intIdEleve = student.intIdUtilisateur
        -- Without skills
        WHERE mark.strvaleur NOT IN ('-', '=', '+')
        ORDER BY evaluation_id DESC";
$result = mssql_query($request, $connOasis);
$evaluation_student = [];
while ($row = mssql_fetch_assoc($result))
    $evaluation_student[] = $row;

// Select skillsAssessed
$request = "SELECT skill.strNom AS 'name', sem.intIdProcess AS 'semester_id', mark.intIdEleve AS 'student_id', mark.strvaleur AS 'value'
        FROM eleves student
        INNER JOIN bdn_notes mark ON mark.intIdEleve = student.intIdUtilisateur
        -- Skill: process of FPC type without child
        INNER JOIN process skill ON skill.intIdProcess = mark.intIdProcess AND skill.strTypeReferentiel = 'FPC' AND skill.intNbFils = 0
        -- Semester: process of ENS type and level 2
        INNER JOIN process sem ON sem.strTypeReferentiel = 'ENS' AND sem.intNiveau = 2
        -- Keep only skills
        WHERE mark.strvaleur IN ('-', '=', '+')
        -- The semester is the one of the skill
        AND skill.strLstParents LIKE sem.strLstParents + CAST(sem.intIdProcess AS NVARCHAR(255)) + ',%'
        ORDER BY semester_id DESC, name, value";
$result = mssql_query($request, $connOasis);
$i = 0;
$skillsAssessed = [];
while ($row = mssql_fetch_assoc($result)) {
    $skillsAssessed[$i]['skill_name'] = substr($row['name'], 0, strpos($row['name'], " "));
    $skillsAssessed[$i]['semester_id'] = $row['semester_id'];
    $skillsAssessed[$i]['student_id'] = $row['student_id'];
    $skillsAssessed[$i]['value'] = $row['value'];
    $i++;
}

// Disconnection of Oasis DB
mssql_close($connOasis);

// Display extraction time
$intermediaryTime = microtime(true);
echo "Extraction time: " . number_format($intermediaryTime - $timeStart, 1) . "s (usual value: 5s) </br>";

// Display array sizes
echo "Arrays sizes: (usual value) </br>";
echo "Evaluations: " . count($evaluations) . " (2700) </br>";
echo "Evaluation_student: " . count($evaluation_student) . " (68000) </br>";
echo "SkillsAssessed: " . count($skillsAssessed) . " (13400) </br>";
echo "Students: " . count($students) . " (1100) </br>";
echo "Semesters: " . count($semesters) . " (?) </br>";
echo "Semester_student: " . count($semester_student) . " (?) </br>";
echo "Uvs: " . count($uvs) . " (1000) </br>";
echo "Student_uv: " . count($student_uv) . " (27000) </br>";

// Connexion to Elyko DB
$connElyko = mysqli_connect(ElykoHost, ElykoUser, ElykoPwd, ElykoDB);

// Set up_to_date to false
mysqli_query($connElyko, 'UPDATE evaluations SET up_to_date = false');
mysqli_query($connElyko, 'UPDATE evaluation_student SET up_to_date = false');
mysqli_query($connElyko, 'UPDATE skillsAssessed SET up_to_date = false');
mysqli_query($connElyko, 'UPDATE students SET up_to_date = false');
mysqli_query($connElyko, 'UPDATE semesters SET up_to_date = false');
mysqli_query($connElyko, 'UPDATE semester_student SET up_to_date = false');
mysqli_query($connElyko, 'UPDATE uvs SET up_to_date = false');
mysqli_query($connElyko, 'UPDATE student_uv SET up_to_date = false');

// Disabled autocommit
mysqli_autocommit($connElyko, FALSE);

// Insert students
$sql = [];
foreach ($students as $student)
    $sql[] = '(' . $student['id'] . ', "' . $student['name'] . '", "' . $student['last_name'] . '", "' . $student['login'] . '", "' . $student['email'] . '", true)';
$queryReplace = mysqli_query($connElyko, 'REPLACE INTO students (id, name, last_name, login, email, up_to_date) VALUES ' . implode(',', $sql));
$queryDelete = mysqli_query($connElyko, 'DELETE FROM students WHERE up_to_date = false');
if ($queryReplace && $queryDelete) {
    mysqli_commit($connElyko);
    echo "Students: succeed </br>";
} else {
    mysqli_rollback($connElyko);
    echo "Students: fail </br>";
}

// Insert semesters
$sql = [];
foreach ($semesters as $semester)
    $sql[] = '(' . $semester['id'] . ', "' . $semester['name'] . '", true)';
$queryReplace = mysqli_query($connElyko, 'REPLACE INTO semesters (id, name, up_to_date) VALUES ' . implode(',', $sql));
$queryDelete = mysqli_query($connElyko, 'DELETE FROM semesters WHERE up_to_date = false');
if ($queryReplace && $queryDelete) {
    mysqli_commit($connElyko);
    echo "Semesters: succeed </br>";
} else {
    mysqli_rollback($connElyko);
    echo "Semesters: fail </br>";
}

// Insert semester_student
$sql = [];
foreach ($semester_student as $inscription) {
    $sql[] = '(' . $inscription['semester_id'] . ', "' . $inscription['student_id'] . '", true)';
}
$queryReplace = mysqli_query($connElyko, 'REPLACE INTO semester_student (semester_id, student_id, up_to_date) VALUES ' . implode(',', $sql));
$queryDelete = mysqli_query($connElyko, 'DELETE FROM semester_student WHERE up_to_date = false');
if ($queryReplace && $queryDelete) {
    mysqli_commit($connElyko);
    echo "Semester_student: succeed </br>";
} else {
    mysqli_rollback($connElyko);
    echo "Semester_student: fail </br>";
}

// Insert uvs
$sql = [];
foreach ($uvs as $uv) {
    $sql[] = '(' . $uv['id'] . ', "' . $uv['name'] . '", ' . $uv['semester_id'] . ', ' . $uv['credits'] . ', true)';
}
$queryReplace = mysqli_query($connElyko, 'REPLACE INTO uvs (id, name, semester_id, credits, up_to_date) VALUES ' . implode(',', $sql));
$queryDelete = mysqli_query($connElyko, 'DELETE FROM uvs WHERE up_to_date = false');
if ($queryReplace && $queryDelete) {
    mysqli_commit($connElyko);
    echo "Uvs: succeed </br>";
} else {
    mysqli_rollback($connElyko);
    echo "Uvs: fail </br>";
}

// Insert student_uv
$sql = [];
foreach ($student_uv as $inscription) {
    $inscription['grade'] = str_replace(",", ".", $inscription['grade']);
    $sql[] = '(' . $inscription['student_id'] . ', ' . $inscription['uv_id'] . ', "' . $inscription['grade'] . '", true)';
}
$queryReplace = mysqli_query($connElyko, 'REPLACE INTO student_uv (student_id, uv_id, grade, up_to_date) VALUES ' . implode(',', $sql));
$queryDelete = mysqli_query($connElyko, 'DELETE FROM student_uv WHERE up_to_date = false');
if ($queryReplace && $queryDelete) {
    mysqli_commit($connElyko);
    echo "Student_uv: succeed </br>";
} else {
    mysqli_rollback($connElyko);
    echo "Student_uv: fail </br>";
}
// Insert evaluation_student
$sql = [];
foreach ($evaluation_student as $mark) {
    $mark['value'] = str_replace(",", ".", $mark['value']);
    $sql[] = '(' . $mark['evaluation_id'] . ', ' . $mark['student_id'] . ', "' . $mark['value'] . '", true)';
}
$queryReplace = mysqli_query($connElyko, 'REPLACE INTO evaluation_student (evaluation_id, student_id, value, up_to_date) VALUES ' . implode(',', $sql));
$queryDelete = mysqli_query($connElyko, 'DELETE FROM evaluation_student WHERE up_to_date = false');
if ($queryReplace && $queryDelete) {
    mysqli_commit($connElyko);
    echo "Evaluation_student: succeed </br>";
} else {
    mysqli_rollback($connElyko);
    echo "Evaluation_student: fail </br>";
}

// Insert evaluations
$sql = [];
foreach ($evaluations as $evaluation) {
    // On retire les " du name pour eviter une exception
    $evaluation['name'] = str_replace("\"", "", $evaluation['name']);
    if ($evaluation['coefficient'] > 1)
        //Pour un affichage uni des coefficients
        $evaluation['coefficient'] /= 100;
    $sql[] = '(' . $evaluation['id'] . ', ' . $evaluation['uv_id'] . ', "' . $evaluation['name'] . '", ' . $evaluation['coefficient'] . ', ' . $evaluation['locked'] . ', true)';
}
$queryReplace = mysqli_query($connElyko, 'REPLACE INTO evaluations (id, uv_id, name, coefficient, locked, up_to_date) VALUES ' . implode(',', $sql));
$queryDelete = mysqli_query($connElyko, 'DELETE FROM evaluations WHERE up_to_date = false');
if ($queryReplace && $queryDelete) {
    mysqli_commit($connElyko);
    echo "IEvaluations: succeed </br>";
} else {
    mysqli_rollback($connElyko);
    echo "Evaluations: fail </br>";
}

// Insert skillsAssessed
$sql = [];
foreach ($skillsAssessed as $mark) {
    $sql[] = '( "' . $mark['skill_name'] . '" , ' . $mark['semester_id'] . ', ' . $mark['student_id'] . ', "' . $mark['value'] . '", true)';
}
$queryReplace = mysqli_query($connElyko, 'REPLACE INTO skillsAssessed (skill_name, semester_id, student_id, value, up_to_date) VALUES ' . implode(',', $sql));
$queryDelete = mysqli_query($connElyko, 'DELETE FROM skillsAssessed WHERE up_to_date = false');
if ($queryReplace && $queryDelete) {
    mysqli_commit($connElyko);
    echo "SkillsAssessed: succeed </br>";
} else {
    mysqli_rollback($connElyko);
    echo "SkillsAssessed: fail </br>";
}

// Disconnect of Elyko DB
mysqli_close($connElyko);

// Display insertion time
echo "Insertion time: " . number_format(microtime(true) - $intermediaryTime, 1) . "s  (usual value: 8s) </br>";