<?php
/**
 * Data migration from Oasis to Elyko
 *
 * The provided view of Oasis containing only current students but all courses, evaluations and marks.
 * The database of destination is Elyko, on the cagiva server.
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
require('credentials.php');
// To measure the execution time
$timeStart = microtime(true);
// Connexion to Oasis DB
$connOasis = sqlsrv_connect(OasisHost, ["Database" => OasisDB, "UID" => OasisUser, "PWD" => OasisPwd]);
// Select students
$request = "SELECT intIdUtilisateur AS 'id', strNom AS 'last_name', strPrenom AS 'name', strEmail AS 'email', strLogin AS 'login'
        FROM eleves";
$result = sqlsrv_query($connOasis, $request);
$students = [];
while ($row = sqlsrv_fetch_array($result))
    $students[] = $row;
// Select semesters
$request = "SELECT DISTINCT sem.intIdProcess AS 'id', sem.strNom AS 'name'
        FROM eleves student
        -- Inscription of student to a semester
        INNER JOIN inscription_process isem ON isem.intIdUser = student.intIdUtilisateur
        -- Semester: process of ENS type and level 2
        INNER JOIN process sem ON sem.intIdProcess = isem.intIdProcess AND sem.strTypeReferentiel = 'ENS' AND sem.intNiveau = 2
        ORDER BY id DESC";
$result = sqlsrv_query($connOasis, $request);
$semesters = [];
while ($row = sqlsrv_fetch_array($result))
    $semesters[] = $row;
// Select semester_student
$request = "SELECT sem.intIdProcess AS 'semester_id', student.intIdUtilisateur AS 'student_id'
        FROM eleves student
        -- Inscription of student to a semester
        INNER JOIN inscription_process isem ON isem.intIdUser = student.intIdUtilisateur
        -- Semester: process of ENS type and level 2
        INNER JOIN process sem ON sem.intIdProcess = isem.intIdProcess AND sem.strTypeReferentiel = 'ENS' AND sem.intNiveau = 2
        ORDER BY semester_id DESC";
$result = sqlsrv_query($connOasis, $request);
$semester_student = [];
while ($row = sqlsrv_fetch_array($result))
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
        INNER JOIN dyn_valeurs credits ON credits.intIdChamp = 1143 AND credits.intIdRef = CAST(uv.intIdProcess AS NVARCHAR(255))
        -- Where the semester is the one of the UV
        WHERE uv.strLstParents LIKE sem.strLstParents + CAST(sem.intIdProcess AS NVARCHAR(255)) + ',%'
        ORDER BY id DESC, credits DESC";
$result = sqlsrv_query($connOasis, $request);
$uvs = [];
$i = 0;
while ($row = sqlsrv_fetch_array($result))
    // Remove UVs with 0 or 1 credit
    if (is_numeric($row['credits']) and $row['credits'] > 1) {
        // There are sometimes two credits associated to a same UV, we keep the most important
        if ($i == 0 || $row['id'] != $uvs[$i - 1]['id']) {
            $uvs[] = $row;
            $i++;
        }
    }
// Select student_uv
$request = "SELECT student.intIdUtilisateur AS 'student_id', UV.intIdProcess AS 'uv_id', computedGrade.strValeur AS 'computedGrade', forcedGrade.strGrade AS 'forcedGrade'
        FROM eleves student
        -- Inscription of student to UV
        INNER JOIN Inscription_process iUV ON iUV.intIdUser = student.intIdUtilisateur
        -- UV: process of FPC type with children (permit to not select skills)
        INNER JOIN process UV ON UV.intIdProcess = iUV.intIdProcess AND UV.strTypeReferentiel = 'FPC' AND UV.intNbFils > 0
        -- Computed grade: store in dyn_valeurs with intIdChamp = 1383 and inscription id in string format
        LEFT OUTER JOIN dyn_valeurs computedGrade ON computedGrade.intIdChamp = 1383 AND computedGrade.intIdRef = CAST(iUV.intIdInscription AS NVARCHAR(255))
        -- Forced grade (if existed): store in bdn_buletin
        LEFT OUTER JOIN bdn_bulletin forcedGrade ON forcedGrade.intIdEleve = student.intIdUtilisateur AND forcedGrade.intIdProcess = UV.intIdProcess
        ORDER BY uv_id";
$result = sqlsrv_query($connOasis, $request);
$student_uv = [];
$i = 0;
$uvs_ids = array_column($uvs, 'id');
while ($row = sqlsrv_fetch_array($result))
    if (in_array($row['uv_id'], $uvs_ids)) {
        $student_uv[$i]['student_id'] = $row['student_id'];
        $student_uv[$i]['uv_id'] = $row['uv_id'];
        $student_uv[$i]['grade'] = $row['forcedGrade'] ? $row['forcedGrade'] : $row['computedGrade'];
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
$result = sqlsrv_query($connOasis, $request);
$evaluations = [];
while ($row = sqlsrv_fetch_array($result)) {
    if (in_array($row['uv_id'], $uvs_ids))
        $evaluations[] = $row;
}
// Select evaluation_student
$request = "SELECT assessment.intIdEvaluation AS 'evaluation_id', assessment.intIdEleve AS 'student_id', assessment.strvaleur AS 'mark'
        FROM eleves student
        INNER JOIN bdn_notes assessment ON assessment.intIdEleve = student.intIdUtilisateur
        -- Without skills
        WHERE assessment.strvaleur NOT IN ('-', '=', '+')
        ORDER BY evaluation_id DESC";
$result = sqlsrv_query($connOasis, $request);
$evaluation_student = [];
$evaluation_ids = array_column($evaluations, 'id');
while ($row = sqlsrv_fetch_array($result)) {
    if (in_array($row['evaluation_id'], $evaluation_ids))
        $evaluation_student[] = $row;
}
// Select skillsAssessed
$request = "SELECT skill.strNom AS 'skill_name', sem.intIdProcess AS 'semester_id', mark.intIdEleve AS 'student_id', mark.strvaleur AS 'value'
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
        ORDER BY semester_id DESC, skill_name, value";
$result = sqlsrv_query($connOasis, $request);
$skillsAssessed = [];
while ($row = sqlsrv_fetch_array($result)) {
    $row['skill_name'] = substr($row['skill_name'], 0, strpos($row['skill_name'], " "));
    $skillsAssessed[] = $row;
}
// Disconnection of Oasis DB
sqlsrv_close($connOasis);
// Display extraction time
$intermediaryTime = microtime(true);
echo "Extraction time: " . number_format($intermediaryTime - $timeStart, 1) . "s (usual value: 5s) </br>";
// Display array sizes
echo "Arrays sizes: </br>";
echo "Evaluations: " . count($evaluations) . " (2000) </br>";
echo "Evaluation_student: " . count($evaluation_student) . " (70000) </br>";
echo "SkillsAssessed: " . count($skillsAssessed) . " (15000) </br>";
echo "Students: " . count($students) . " (1300) </br>";
echo "Semesters: " . count($semesters) . " (150) </br>";
echo "Semester_student: " . count($semester_student) . " (4700) </br>";
echo "Uvs: " . count($uvs) . " (1100) </br>";
echo "Student_uv: " . count($student_uv) . " (29000) </br>";
// Connexion to Elyko DB
$connElyko = mysqli_connect(ElykoHost, ElykoUser, ElykoPwd, ElykoDB);
// Set up_to_date to false
mysqli_query($connElyko, 'UPDATE evaluations SET up_to_date = FALSE');
mysqli_query($connElyko, 'UPDATE evaluation_student SET up_to_date = FALSE');
mysqli_query($connElyko, 'UPDATE skillsAssessed SET up_to_date = FALSE');
mysqli_query($connElyko, 'UPDATE students SET up_to_date = FALSE');
mysqli_query($connElyko, 'UPDATE semesters SET up_to_date = FALSE');
mysqli_query($connElyko, 'UPDATE semester_student SET up_to_date = FALSE');
mysqli_query($connElyko, 'UPDATE uvs SET up_to_date = FALSE');
mysqli_query($connElyko, 'UPDATE student_uv SET up_to_date = FALSE');
// Disabled autocommit
mysqli_autocommit($connElyko, FALSE);
// Insert students
$sql = [];
foreach ($students as $student)
    $sql[] = '(' . $student['id'] . ', "' . $student['name'] . '", "' . $student['last_name'] . '", "' . $student['login'] . '", "' . $student['email'] . '", true)';
$queryReplace = mysqli_query($connElyko, 'REPLACE INTO students (id, NAME, last_name, LOGIN, email, up_to_date) VALUES ' . implode(',', $sql));
$queryDelete = mysqli_query($connElyko, 'DELETE FROM students WHERE up_to_date = FALSE');
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
$queryReplace = mysqli_query($connElyko, 'REPLACE INTO semesters (id, NAME, up_to_date) VALUES ' . implode(',', $sql));
$queryDelete = mysqli_query($connElyko, 'DELETE FROM semesters WHERE up_to_date = FALSE');
if ($queryReplace && $queryDelete) {
    mysqli_commit($connElyko);
    echo "Semesters: succeed </br>";
} else {
    mysqli_rollback($connElyko);
    echo "Semesters: fail </br>";
}
// Insert semester_student
$sql = [];
foreach ($semester_student as $inscription)
    $sql[] = '(' . $inscription['semester_id'] . ', "' . $inscription['student_id'] . '", true)';
$queryReplace = mysqli_query($connElyko, 'REPLACE INTO semester_student (semester_id, student_id, up_to_date) VALUES ' . implode(',', $sql));
$queryDelete = mysqli_query($connElyko, 'DELETE FROM semester_student WHERE up_to_date = FALSE');
if ($queryReplace && $queryDelete) {
    mysqli_commit($connElyko);
    echo "Semester_student: succeed </br>";
} else {
    mysqli_rollback($connElyko);
    echo "Semester_student: fail </br>";
}
// Insert uvs
$sql = [];
foreach ($uvs as $uv)
    $sql[] = '(' . $uv['id'] . ', "' . $uv['name'] . '", ' . $uv['semester_id'] . ', ' . $uv['credits'] . ', true)';
$queryReplace = mysqli_query($connElyko, 'REPLACE INTO uvs (id, NAME, semester_id, credits, up_to_date) VALUES ' . implode(',', $sql));
$queryDelete = mysqli_query($connElyko, 'DELETE FROM uvs WHERE up_to_date = FALSE');
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
$queryDelete = mysqli_query($connElyko, 'DELETE FROM student_uv WHERE up_to_date = FALSE');
if ($queryReplace && $queryDelete) {
    mysqli_commit($connElyko);
    echo "Student_uv: succeed </br>";
} else {
    mysqli_rollback($connElyko);
    echo "Student_uv: fail </br>";
}
// Insert evaluations
$sql = [];
foreach ($evaluations as $evaluation) {
    // Remove " in name to avoid exceptions
    $evaluation['name'] = str_replace("\"", "", $evaluation['name']);
    $sql[] = '(' . $evaluation['id'] . ', ' . $evaluation['uv_id'] . ', "' . $evaluation['name'] . '", ' . $evaluation['coefficient'] . ', ' . $evaluation['locked'] . ', true)';
}
$queryReplace = mysqli_query($connElyko, 'REPLACE INTO evaluations (id, uv_id, NAME, coefficient, locked, up_to_date) VALUES ' . implode(',', $sql));
$queryDelete = mysqli_query($connElyko, 'DELETE FROM evaluations WHERE up_to_date = FALSE');
if ($queryReplace && $queryDelete) {
    mysqli_commit($connElyko);
    echo "Evaluations: succeed </br>";
} else {
    mysqli_rollback($connElyko);
    echo "Evaluations: fail </br>";
}
// Insert evaluation_student
$sql = [];
foreach ($evaluation_student as $assessment) {
    $assessment['mark'] = str_replace(",", ".", $assessment['mark']);
    $sql[] = '(' . $assessment['evaluation_id'] . ', ' . $assessment['student_id'] . ', "' . $assessment['mark'] . '", true)';
}
$sql = array_chunk($sql, 15000);
foreach ($sql as $query)
    $queryReplace = mysqli_query($connElyko, 'REPLACE INTO evaluation_student (evaluation_id, student_id, MARK, up_to_date) VALUES ' . implode(',', $query));
$queryDelete = mysqli_query($connElyko, 'DELETE FROM evaluation_student WHERE up_to_date = FALSE');
if ($queryReplace && $queryDelete) {
    mysqli_commit($connElyko);
    echo "Evaluation_student: succeed </br>";
} else {
    mysqli_rollback($connElyko);
    echo "Evaluation_student: fail </br>";
}
// Insert skillsAssessed
$sql = [];
foreach ($skillsAssessed as $assessment)
    $sql[] = '( "' . $assessment['skill_name'] . '" , ' . $assessment['semester_id'] . ', ' . $assessment['student_id'] . ', "' . $assessment['value'] . '", true)';
$queryReplace = mysqli_query($connElyko, 'REPLACE INTO skillsAssessed (skill_name, semester_id, student_id, VALUE, up_to_date) VALUES ' . implode(',', $sql));
$queryDelete = mysqli_query($connElyko, 'DELETE FROM skillsAssessed WHERE up_to_date = FALSE');
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