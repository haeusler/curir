<?php
namespace watoki\curir\http;
 
use watoki\collections\Liste;

class Path extends Liste {

    const SEPARATOR = '/';

    public static function parse($string) {
        $string = rtrim($string, self::SEPARATOR);
        if ($string === '') {
            return new Path();
        }
        return new Path(Liste::split(self::SEPARATOR, $string)->elements);
    }

    public function toString() {
        return $this->join(self::SEPARATOR);
    }

}
