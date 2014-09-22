<?php

define('IN_CONDUIT', true);
define('IN_PHPBB', true);
define('AJAX', true);

require_once('UserManagement.inc.php');
// Пользователь должен иметь доступ к админке
$ConduitUser->must_manage('Lists');

require_once('AjaxError.inc.php');

?>
<?php

class TProblem {
    public $number;
    public $type;
    public $name;
    public $group;
    
    private static $typesArray;
    private static $stmt;
    
    function __construct(DOMElement $node, $internalNumber) {
        if ($node->tagName !== 'problem'){
            throw new Exception('Tag name not equal to \'problem\'');
        }
        $this->number = (int)$internalNumber;
        $this->name = $node->textContent;
        $this->type = (int)($node->getAttribute('type'));
        if (!self::checkType($this->type)) {
            throw new Exception('Invalid problem type');
        }
        $this->group = (int)($node->getAttribute('group'));
    }
    
    function write2SQL($ListID) {
        global $conduit_db;
        if (!isset(self::$stmt)) {
            $sql = 'INSERT INTO `PProblem` (`ProblemTypeID`, `ListID`, `Number`, `Group`, `Name`) 
                        VALUES (:type, :list, :number, :group, :name)';
            self::$stmt = $conduit_db->prepare($sql);
        }
        self::$stmt->execute(array(
            ':type'   => $this->type, 
            ':list'   => $ListID, 
            ':number' => $this->number, 
            ':group'  => $this->group, 
            ':name'   => $this->name
        ));
    }
    
    static function checkType($t) {
        if (!isset(self::$typesArray)) {
            global $conduit_db;
            $sql = 'SELECT `ID` FROM `PProblemType`';
            $stmt = $conduit_db->query($sql);
            self::$typesArray = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        }
        return in_array($t, self::$typesArray);
    }
}

class TListok {
    public $number;
    public $type;
    public $description;
    public $date;
    public $problems;
    public $problemCount;
    public $class;
    
    private static $typesArray;
    
    function __construct(DOMElement $node, $class) {
        // Инициализируем сам листок
        if ($node->tagName !== 'listok'){
            throw new Exception('Tag name not equal to \'listok\'');
        }
        $this->number = $node->getAttribute('number');
        if ($this->number === '') {
            throw new Exception('Missing listok attribute \'number\'');
        }
        $this->type = (int)($node->getAttribute('type'));
        if (!self::checkType($this->type)) {
            throw new Exception('Invalid listok type');
        }
        $this->description = $node->getAttribute('description');
        $this->date = $node->getAttribute('date');
        if (isset($class)) {
            $this->class = $class;
        } else {
            $this->class = 'NULL';
        }       
        
        // Инициализируем задачи
        $problemList = $node->getElementsByTagName('problem');
        $this->problemCount = $problemList->length;
        for ($i = 0; $i < $this->problemCount; $i++) {
            $this->problems[] = new TProblem($problemList->item($i), $i);
        }
    }
    
    function write2SQL() {
        global $conduit_db;
        $sql = 'INSERT INTO `PList` (`ListTypeID`, `ClassID`, `Number`, `Description`, `Date`) 
                    VALUES (:type, :class, :number, :desc, :date)';
        $stmt = $conduit_db->prepare($sql);
        $stmt->execute(array(
            ':type'   => $this->type, 
            ':class'  => $this->class,
            ':number' => $this->number,
            ':desc'   => $this->description,
            ':date'   => $this->date
        ));
        $ListID = $conduit_db->lastInsertId(); // узнаём ID добавленной записи
        foreach ($this->problems as $problem) {
            $problem->write2SQL($ListID);
        }
    }
    
    static function checkType($t) {
        if (!isset(self::$typesArray)) {
            global $conduit_db;
            $sql = 'SELECT `ID` FROM `PListType`';
            $stmt = $conduit_db->query($sql);
            self::$typesArray = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        }
        return in_array($t, self::$typesArray);
    }
}

function errorHandler($type, $message, $file, $line) {
    if (substr($message, 0, 22) === 'DOMDocument::loadXML()') {
        // ошибка парсинга XML
        throw new Exception('Invalid XML structure: ' . substr($message, 24));
    } else {
        // стандартный обработчик для AJAX
        ajaxErrorHandler($type, $message, $file, $line);
    }
}

function parseListok($xml, $class) {
    global $conduit_db;
    
    set_error_handler('errorHandler');

    $doc = new DOMDocument('1.0', 'utf8');
    $doc->loadXML($xml);
    $listok = new TListok($doc->documentElement, $class);
    
    $conduit_db->beginTransaction();
    try {
        $listok->write2SQL();
        $conduit_db->commit();
    } catch (Exception $e) {
        $conduit_db->rollBack();
        error_log("Cannot upload to database. " . $e->getMessage());
        throw new Exception('SQL error');
    }
}


// Обрабатываем запрос
try {
    // Заменяем все косые апострофы (’, ‘, `) на нормальные (')
    mb_internal_encoding("UTF-8");
    $XML = mb_ereg_replace("’|‘|`", "'", $_POST['XML']);
    
    // Парсим XML
	parseListok($XML, $Class['ID']);
	$Response['code']    = 0;
	$Response['message'] = 'Listok uploaded successfully!';
    
} catch (Exception $e) {
    $Response['code']    = 1;
    $Response['message'] = 'Upload process failed. ' . $e->getMessage();
}

// Возвращаем ответ
echo json_encode($Response);
?>