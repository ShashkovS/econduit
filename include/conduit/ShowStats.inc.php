<?php

if (!defined('IN_CONDUIT')) {
    exit(0);
}

?>
<?php

class TList {

    public $ID;
    public $Number;
    public $ProblemCount;
    
    // Сдал ли хоть кто-нибудь хоть что-нибудь
    public $Empty;
    
    public function __construct() {
        $this->Empty = true;
    }
    
    public function add_result($result) {
        $this->Empty = false;
    }
}

class TPupil {

    public $ID;
    public $Name;
    
    private $ListResults;
    private $TotalResult;
    
    public function __construct() {
        $this->ListResults = array();
        $this->TotalResult = 0.0;
    }
    
    public function add_result($result) {
        if (!isset($this->ListResults[$result['List']])) {
            $this->ListResults[$result['List']] = 1;
        } else {
            $this->ListResults[$result['List']] += 1;
        }
        
        if ($result['ListType'] == 2 || $result['ProblemType'] == 1 || $result['ProblemType'] == 2) {
            $this->TotalResult += 1.2;
        } else {
            $this->TotalResult += 1.0;
        }
    }
    
    public function getTotalResult() {
        return $this->TotalResult;
    }
    
    public function getListResult($ListID) {
        return isset($this->ListResults[$ListID]) ? $this->ListResults[$ListID] : '';
    }
}

// На входе массив объектов, у которых есть уникальное поле ID.
// На выходе ассоциативный массив с теми же значениями и ключами равными ID.
function extract_id($obj) {
    return $obj->ID;
}
function rebase_to_id($array) {
    if (count($array)) {
        return array_combine(array_map("extract_id", $array), $array);
    } else {
        return array();
    }
}

function is_not_empty($list) {
    return !$list->Empty;
}

function compare_pupils($a, $b) {
    if ($a->getTotalResult() == $b->getTotalResult()) {
        return strcmp($a->Name, $b->Name);
    }
    return ($a->getTotalResult() < $b->getTotalResult()) ? 1 : -1;
}

function ShowStats($StartDate, $EndDate) {
    global $Class;
    global $conduit_db;

    // Получаем список всех листков класса
    $sql = "SELECT
                `PList`.`ID`, `PList`.`Number`, count(1) AS `ProblemCount`
            FROM
                `PList` INNER JOIN `PProblem` ON `PProblem`.`ListID` = `PList`.`ID`
                        INNER JOIN `PListType` ON `PListType`.`ID` = `PList`.`ListTypeID`
            WHERE
                (`PList`.`ClassID` = :class OR `PList`.`ClassID` IS NULL)
                AND `PListType`.`InStats`
            GROUP BY
                `PList`.`ID`, `PList`.`Number`
            ORDER BY
                `PList`.`ID`
            ";
    $stmt = $conduit_db->prepare($sql);
    $stmt->execute(array('class' => $Class['ID']));
    $stmt->setFetchMode(PDO::FETCH_CLASS, 'TList');
    $Lists = rebase_to_id($stmt->fetchAll());
    
    
    
    // Получаем список школьников 
    $sql = "SELECT
                `PPupil`.`ID`, TRIM(CONCAT(`PPupil`.`Name1`,' ',`PPupil`.`Name2`,' ',`PPupil`.`Name3`)) AS `Name`
            FROM
                `PPupil`
            WHERE
                `PPupil`.`ClassID` = :class
            ORDER BY
                `PPupil`.`ID`
            ";
    $stmt = $conduit_db->prepare($sql);
    $stmt->execute(array('class' => $Class['ID']));
    $stmt->setFetchMode(PDO::FETCH_CLASS, 'TPupil');
    $Pupils = rebase_to_id($stmt->fetchAll());
    
    // Обрабатываем результаты школьников
    $sql = "SELECT
                `PResult`.`PupilID` AS `Pupil`, `PProblem`.`ProblemTypeID` AS `ProblemType`, `PList`.`ID` AS `List`, `PList`.`ListTypeID` AS `ListType`
            FROM
                `PResult` INNER JOIN `PProblem` ON `PResult`.`ProblemID` = `PProblem`.`ID`
                          INNER JOIN `PList` ON `PProblem`.`ListID` = `PList`.`ID`
                          INNER JOIN `PListType` ON `PListType`.`ID` = `PList`.`ListTypeID`
                          INNER JOIN `PPupil` ON `PResult`.`PupilID` = `PPupil`.`ID`
            WHERE
                `PPupil`.`ClassID` = :class
                AND `PListType`.`InStats`
                AND `PResult`.`Mark` LIKE '__/__/____'
                AND STR_TO_DATE(`PResult`.`Mark`, '%d/%m/%Y') BETWEEN STR_TO_DATE(:StartDate, '%d/%m/%Y') AND STR_TO_DATE(:EndDate, '%d/%m/%Y')
            ";
    $stmt = $conduit_db->prepare($sql);
    $stmt->execute(array('class' => $Class['ID'], 'StartDate' => $StartDate, 'EndDate' => $EndDate));
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $Pupils[$row['Pupil']]->add_result($row);
        $Lists[$row['List']]->add_result($row);
    }

    // Оставляем только те листки, в которых были сданные задачи
    $Lists = array_filter($Lists, "is_not_empty");
    
    // Сортируем школьников по убыванию результатов (TotalResult)
    usort($Pupils, "compare_pupils");
    
    // Собираем таблицу
    // Заголовочная строка
    $hRow  = '<tr class="headerRow">';
    $ColGroup = '<colgroup>';
    // Столбец школьников
    $hRow .= '<th scope="col">ФИО</th>';
    $ColGroup .= '<col/>';
    // Столбец итогов
    $hRow .= '<th scope="col" class="list">Сумма</th>';
    $ColGroup .= '<col class="total"/>';
    // Столбцы для каждого листка
    foreach ($Lists as $List) {
        $hRow .= '<th scope="col" class="list">' . $List->Number . ' (' . $List->ProblemCount . ')</th>';
        $ColGroup .= '<col/>';
    }
    $hRow .= '</tr>';
    $ColGroup .= '</colgroup>';
    
    // Результаты школьников
    $TBody  = '<tbody>';
    foreach ($Pupils as $Pupil) {
        $Row  = '<tr>';
        $Row .= '<th scope="row" class="pupilName">' . $Pupil->Name . '</th>';
        $Row .= '<td>' . $Pupil->getTotalResult() . '</td>';
        foreach ($Lists as $List) {
            $Row .= '<td>' . $Pupil->getListResult($List->ID) . '</td>';
        }
        $Row .= '</tr>';
        $TBody .= $Row;
    }
    $TBody .= '</tbody>';

    // Собираем таблицу целиком
    $Table = '<table class="stats">' . $ColGroup . '<thead>' . $hRow . '</thead><tfoot>' . $hRow . '</tfoot>' . $TBody . '</table>';
    
    // Собираем плавающую шапку
    //$FloatTable = '<table class="stats">' . $ColGroup . '<thead>' . $hRow . '</thead></table>';
    //$FloatDiv = '<div class="floatHeader" style="display:none;">' . $FloatTable . '</div>';

    
    return $Table;// . $FloatDiv;

}

?>