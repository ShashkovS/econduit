<?php

if (!defined('IN_CONDUIT')) {
    exit(0);
}

?>
<?php

class Mark {

    public $text;
    public $user;
    public $ts;
    
    public function __construct($text, $user, $ts) {
        $this->text = $text;
        $this->user = $user;
        $this->ts   = $ts;
    }
}

class Cell {

    public $hint;
    public $mark;
    public $content;
    
    public function __construct($Mark) {
        $this->mark     = addslashes($Mark->text);
        $this->hint     = "Последнее изменение: $Mark->user, $Mark->ts";
        $this->content  = self::String2Frac(filter_var($Mark->text, FILTER_SANITIZE_SPECIAL_CHARS));
    }
    
    public function html() {
        return '<td data-mark="' . $this->mark . '" title="' . $this->hint . '">' . $this->content . '</td>';
    }
    
    // преобразуем строку вида 'n/d/r' к виду $\frac{n}{d}$ в нотации MathML
    private static function String2Frac($str) {
        $slash1 = strpos($str, '/');
        if ($slash1 === false) {
            return $str;
        } else {
            $numerator = substr($str, 0, $slash1);
            $slash2 = strpos($str, '/', $slash1+1);
            if ($slash2 === false) {
                $denominator = substr($str, $slash1 + 1);
            } else {
                $denominator = substr($str, $slash1+1, $slash2-$slash1-1);
            }
            return self::MakeFrac($numerator, $denominator);
        }
    }
    
    private static function MakeFrac($n, $d) {
        return "<math><mfrac><mn>$n</mn><mn>$d</mn></mfrac></math>";
    }
}

?>