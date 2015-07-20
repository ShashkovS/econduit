<?php

if (!defined('IN_CONDUIT')){
    exit(0);
}
require_once('Connect.inc.php');
require_once('RenderCell.inc.php');

?>
<?php

// Разбиваем имя задачи на 2 строки. Если первый символ --- цифра, то вторая строка начнётся с первой нецифры.
function SplitProblemName($str) {
    $pattern = "/^(\d+)(?!\d)(.+)/";
    if (preg_match($pattern, $str, $matches)) {
        return $matches[1] . "<br/>" . $matches[2];
    } else {
        return "$str<br/>&nbsp;";
    }
}

function TotalColor($value) {
    if ($value >= 1) return "background-color: rgb(0,255,0)";
    if ($value <= .5) return "background-color: rgb(" . round(248 + (255 - 248) * $value * 2) . ", " . round(105 + (235 - 105) * $value * 2) . ", " . round(107 + (132 - 107) * $value * 2) . ")";
    $value -= .5;
    return "background-color: rgb(" . round(255 - (255 - 99) * $value * 2) . ", " . round(235 + (240 - 235) * $value * 2) . ", " . round(132 - (132 - 123) * $value * 2) . ")";
}

function fillConduit($ClassID, $ListID) {
    global $conduit_db, $ConduitUser;
    
    // Готовим массив школьников
    $sql = "SELECT 
                `PPupil`.`ID` AS `ID`, 
                TRIM(CONCAT(`PPupil`.`Name1`,' ',`PPupil`.`Name2`,' ',`PPupil`.`Name3`)) AS `Name`,
                `PPupil`.`Teacher` AS `Teacher`
            FROM `PPupil`
            WHERE
                `PPupil`.`ClassID` = ?
            ORDER BY 
                2, 1
           ";
    $stmt = $conduit_db->prepare($sql);
    $stmt->execute(array($ClassID));
    $Pupils = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Готовим массив задач
    $sql = "SELECT 
                `PProblem`.`ID` AS `ID`, 
                `PProblem`.`Group` AS `Group`, 
                CONCAT(`PProblem`.`Name`, `PProblemType`.`Sign`) AS `Name`,
                TRIM(`PProblemType`.`Sign`) AS `Sign`
            FROM `PProblem` INNER JOIN `PProblemType`
                 ON `PProblem`.`ProblemTypeID` = `PProblemType`.`ID`
            WHERE 
                `PProblem`.`ListID` = ?
            ORDER BY
                `PProblem`.`Number`, `PProblem`.`Name`, `PProblem`.`ID`
           ";
    $stmt = $conduit_db->prepare($sql);
    $stmt->execute(array($ListID));
    $Problems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Готовим массив отметок
    $Marks = array();
    $sql = "SELECT 
                `PResult`.`PupilID` AS `PupilID`, 
                `PResult`.`ProblemID` AS `ProblemID`, 
                `PResult`.`Mark` AS `Text`, 
                COALESCE(`PUser`.`DisplayName`, `PResult`.`User`) AS `User`, 
                `PResult`.`TS` AS `DateTime`
            FROM `PResult` INNER JOIN `PPupil`
                 ON `PResult`.`PupilID` = `PPupil`.`ID` 
                         INNER JOIN `PProblem`
                 ON `PResult`.`ProblemID` = `PProblem`.`ID`
                         LEFT JOIN `PUser`
                 ON `PResult`.`User` = `PUser`.`User`
            WHERE
                `PPupil`.`ClassID` = :class AND `PProblem`.`ListID` = :list
           ";
    $stmt = $conduit_db->prepare($sql);
    $stmt->execute(array('class' => $ClassID, 'list' => $ListID));
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (!isset($Marks[$row['PupilID']])) {
            $Marks[$row['PupilID']] = array();
        }
        $Marks[$row['PupilID']][$row['ProblemID']] = new Mark($row['Text'], $row['User'], $row['DateTime']);
    }
    
    // Собираем заголовочную строку таблицы (с номерами задач) и одновременно colgroup
    $hRow = '<tr class="headerRow">';
    $ColGroup = '<colgroup>';
    // Ячейка над списком школьников
    if ($ConduitUser->may_manage('Marks')) {
        $hRow .= '<th class="printButton" title="Распечатать этот кондуит">Распечатать</th>';
    } else {
        $hRow .= '<th></th>';
    }
    $ColGroup .= '<col/>';
    // Номера задач
    $PrevGroup = null;
    $NumProblems = 0;
    $NumObligatory = 0;
    foreach ($Problems as $Problem) {
        if ($ConduitUser->may_manage('Marks')) {
            $hRow .= '<th scope="col" class="problemName" data-problem="' . $Problem['ID'] . '">';
        } else {
            $hRow .= '<th scope="col" class="problemName">';
        }
        $hRow .= SplitProblemName($Problem['Name']);
        $hRow .= '</th>';
        if($Problem['Group'] !== $PrevGroup) {
            $PrevGroup = $Problem['Group'];
            $class = ' class="problemStart"';
        } else {
            $class = '';
        }
        $ColGroup .= '<col' . $class . ' data-sign="' . addslashes($Problem['Sign']) . '"/>';
        $NumProblems += 1;
        if($Problem['Sign'] == "") {
            $NumObligatory += 1;
        }
    }

    if ($ConduitUser->may_manage('Marks')) {
        // Добавляем столбец для результатов. Его можно будет скрыть при необходимости
        $hRow .= '<th scope="col" class="problemName total" data-totalProblems="' . $NumProblems . '" data-obligatoryProblems="' . $NumObligatory . '">Сумма</th>';
        $ColGroup .= '<col class="total"/>';
    }

    $hRow .= '</tr>';
    $ColGroup .= '</colgroup>';
    
    // Собираем тело таблицы
    $TBody = '<tbody>';
    foreach ($Pupils as $Pupil) {
        if ($ConduitUser->may_manage('Marks')) {
            $Row = '<tr data-pupil="' . $Pupil['ID'] . '" data-teacher="' . $Pupil['Teacher'] . '">';
        } else {
            $Row = '<tr>';
        }
        // Имя школьника
        $Row .= '<th scope="row" class="pupilName">' . $Pupil['Name'] . '</th>';
        // Сданные задачи
        $TotalResult = 0.0;
        foreach ($Problems as $Problem) {
            if (isset($Marks[$Pupil['ID']][$Problem['ID']])) {
                $Cell = new Cell($Marks[$Pupil['ID']][$Problem['ID']]);
                $TotalResult += $Cell->price();
                $Cell = $Cell->html();
            } else {
                $Cell = '<td></td>';
            }
            $Row .= $Cell;
        }

        if ($ConduitUser->may_manage('Marks')) {
            // Записываем в ячейку текущее количество задач
            $Row .= '<td class="total" style="' . TotalColor($TotalResult / $NumObligatory) . '" data-obligatoryProblems="' . $NumObligatory . '">' . $TotalResult . '</td>';
        }

        $Row .= "</tr>";
        $TBody .= $Row;
    }
    $TBody .= "</tbody>";
    
    // Собираем таблицу с кондуитом
    $Table = "<table class=\"conduit\">$ColGroup<thead>$hRow</thead><tfoot>$hRow</tfoot>$TBody</table>";
    
    // Собираем плавающую шапку
    $FloatTable = "<table class=\"conduit\">$ColGroup<thead>$hRow</thead></table>";
    $FloatDiv = "<div class=\"floatHeader\" style=\"display:none;\">$FloatTable</div>";
    
    return $Table . $FloatDiv;
}

?>