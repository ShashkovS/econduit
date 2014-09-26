<?php

if (!defined('IN_CONDUIT')){
    // Попытка прямого доступа к файлу
    exit();
}

define('LOGIN', 'login');
define('HASH', 'hash');
define('REFERER', 'referer');

require_once('GetClass.inc.php');

class ConduitUser {
    public $name;
    public $display_name;
    private $allow_management;
    
    function __construct($Class) {
        $this->phpBB_login();
        $this->get_role($Class['ID']);
    }
    
    function phpBB_login() {
        global $user;   // пользователь phpBB
        global $auth;   // модуль аутентификации phpBB
        
        $user->session_begin();
        $auth->acl($user->data);
        //$user->setup();
        
        $this->name = $user->data['username'];
    }

    // Определяем уровень доступа пользователя в классе $class
    function get_role($class) {
        global $conduit_db;
        
        $stmt = $conduit_db->prepare('
            SELECT 
                `DisplayName`, `ManageMarks`, `ManageLists`, `ManageClasses`, `ManageUsers`  
            FROM `PUser` INNER JOIN `PUserRole` ON `PUser`.`User` = `PUserRole`.`User`
                         INNER JOIN `PRole` ON `PUserRole`.`Role` = `PRole`.`Name`
            WHERE 
                `PUser`.`User` = ? 
                AND `PUser`.`Disabled` = "N"
                AND `PUserRole`.`Class` = ?
        ');
        if (!$stmt->execute(array(
                $this->name,
                $class
            ))) {
            trigger_error('Selection error: ' . $stmt->errorInfo());
        }
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->display_name = $row['DisplayName'];
            $this->allow_management = array(
                'Marks'   => $row['ManageMarks'],
                'Lists'   => $row['ManageLists'],
                'Classes' => $row['ManageClasses'],
                'Users'   => $row['ManageUsers']
            );
        } else {
            $this->display_name = $this->name;
            $this->allow_management = array(
                'Marks'   => 0,
                'Lists'   => 0,
                'Classes' => 0,
                'Users'   => 0
            );
        }
    }
    
    // Проверяем, имеет ли пользователь доступ к $category
    function may_manage($category) {
        return $this->allow_management[$category];
    }
    
    // Если пользователь не имеет доступа к $category, возвращаем ошибку
    function must_manage($category) {
        if (!$this->may_manage($category)) {
            if (!defined('AJAX')) {
                header("HTTP/1.1 404 Not Found");
                require_once('404.html');
            }
            exit();
        }
    }
    
    // Ссылка на выход. Или на вход, если пользователь --- гость.
    function login_logout_link() {
        global $phpbb_forum_link;
        global $user;
        
        $redirect = explode('?', $_SERVER['REQUEST_URI'], 2);
        $redirect = $redirect[0];
        
        if ($user->data['is_registered']) {
            return '<a title="Выход [ ' . $this->name . ' ]" href="' . $phpbb_forum_link . 'ucp.php?mode=logout&amp;sid=' . $user->session_id . '&amp;redirect=' . $redirect . '">Выход [ ' . $this->name . ' ]</a>';
        } else {
            return '<a title="Вход" href="' . $phpbb_forum_link . 'ucp.php?mode=login&amp;redirect=' . $redirect . '">Вход</a>';
        }
    }
}

if (!isset($Class)) {
    $Class = GetClass();
}

$ConduitUser = new ConduitUser($Class);

?>