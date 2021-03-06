<?php
namespace watoki\curir\http\decoder;

use watoki\collections\Map;
use watoki\curir\http\ParameterDecoder;

class ImageDecoder implements ParameterDecoder {

    private $targetParameter;

    public function __construct($targetParameter = "bodyAsImage") {
        $this->targetParameter = $targetParameter;
    }

    /**
     * @param string $body
     * @return Map
     */
    public function decode($body) {
        $params = array();

        $image = @imagecreatefromstring($body);
        if ($image !== false) {
            $params[$this->targetParameter] = $image;
        }

        return new Map($params);
    }

}