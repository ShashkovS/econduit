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
    
    public function price() {
        if ($this->mark == '+') {
            return 1.0;
        } elseif ($this->mark == '4') {
            return 1.0;
        } elseif ($this->mark == '3') {
            return 0.75;
        } elseif ($this->mark == '2') {
            return 0.5;
        } elseif ($this->mark == '1') {
            return 0.25;
        } elseif ($this->mark == self::unichr(10789)) {
            return 0.99;
        } elseif ($this->mark == self::unichr(177)) {
            return 0.7;
        } elseif ($this->mark == self::unichr(10791)) {
            return 0.5;
        } elseif ($this->mark == self::unichr(8723)) {
            return 0.3;
        } elseif ($this->mark == self::unichr(10794)) {
            return 0.01;
        } else {
            return 0.0;
        }
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
    
    private static function unichr($u) {
        return mb_convert_encoding('&#' . intval($u) . ';', 'UTF-8', 'HTML-ENTITIES');
    }

}

?>