<?php
namespace watoki\curir\http;
 
use watoki\collections\Map;

class Url {

    public static $CLASS = __CLASS__;

    const HOST_PREFIX = '//';

    const SEPARATOR = '/';

    const PORT_SEPARATOR = ':';

    const SCHEME_SEPARATOR = ':';

    const QUERY_STRING_SEPARATOR = '?';

    const FRAGMENT_SEPARATOR = '#';

    /** @var Path */
    private $path;

    /** @var \watoki\collections\Map */
    private $parameters;

    /** @var string|null */
    private $fragment;

    /** @var null|string */
    private $scheme;

    /** @var null|string */
    private $host;

    /** @var null|int */
    private $port;

    function __construct($scheme, $host, $port, Path $path, Map $parameters = null, $fragment = null) {
        $this->scheme = $scheme;
        $this->host = $host;
        $this->port = $port;
        $this->path = $path;
        $this->parameters = $parameters ?: new Map();
        $this->fragment = $fragment;
    }

    /**
     * @return null|string
     */
    public function getScheme() {
        return $this->scheme;
    }

    /**
     * @param null|string $scheme
     */
    public function setScheme($scheme) {
        $this->scheme = $scheme;
    }

    /**
     * @return null|string
     */
    public function getHost() {
        return $this->host;
    }

    /**
     * @param null|string $host
     */
    public function setHost($host) {
        $this->host = $host;
    }

    /**
     * @return int|null
     */
    public function getPort() {
        return $this->port;
    }

    /**
     * @param int|null $port
     */
    public function setPort($port) {
        $this->port = $port;
    }

    /**
     * @return Path
     */
    public function getPath() {
        return $this->path;
    }

    /**
     * @param Path $path
     */
    public function setPath(Path $path) {
        $this->path = $path;
    }

    /**
     * @return \watoki\collections\Map
     */
    public function getParameters() {
        return $this->parameters;
    }

    /**
     * @return null|string
     */
    public function getFragment() {
        return $this->fragment;
    }

    public function setFragment($fragment) {
        $this->fragment = $fragment;
    }

    static public function parse($string) {
        $fragment = null;
        $fragmentPos = strpos($string, self::FRAGMENT_SEPARATOR);
        if ($fragmentPos !== false) {
            $fragment = substr($string, $fragmentPos + 1);
            $string = substr($string, 0, $fragmentPos);
        }

        $parameters = new Map();
        $queryPos = strpos($string, self::QUERY_STRING_SEPARATOR);
        if ($queryPos !== false) {
            $query = substr($string, $queryPos + 1);
            $string = substr($string, 0, $queryPos);

            if ($query) {
                $parameters = self::parseParameters($query);
            }
        }

        $scheme = null;
        $schemeSepPos = strpos($string, self::SCHEME_SEPARATOR . self::HOST_PREFIX);
        if ($schemeSepPos !== false) {
            $scheme = substr($string, 0, $schemeSepPos);
            $string = substr($string, $schemeSepPos + 1);
        }

        $host = null;
        $port = null;
        if (substr($string, 0, 2) == self::HOST_PREFIX) {
            $string = substr($string, 2);
            $hostPos = strpos($string, self::SEPARATOR) ?: strlen($string);
            $host = substr($string, 0, $hostPos);
            $string = substr($string, $hostPos);

            $portPos = strpos($host, self::PORT_SEPARATOR);
            if ($portPos !== false) {
                $port = intval(substr($host, $portPos + 1));
                $host = substr($host, 0, $portPos);
            }
        }

        $path = Path::parse($string);
        if ($path->isEmpty()) {
            $path->append('');
        }

        return new Url($scheme, $host, $port, $path, $parameters, $fragment);
    }

    /**
     * @param $query
     * @return Map
     */
    private static function parseParameters($query) {
        $parameters = new Map();
        foreach (explode('&', $query) as $pair) {
            if (strstr($pair, '=') !== false) {
                list($key, $value) = explode('=', $pair);
            } else {
                $key = $pair;
                $value = null;
            }
            if (preg_match('#\[.+\]#', $key)) {
                $paramsMap = $parameters;
                $mapKeys = explode('[', $key);
                foreach ($mapKeys as $mapKey) {
                    if ($mapKey == end($mapKeys)) {
                        $paramsMap->set(trim($mapKey, ']'), $value);
                    } else {
                        $mapKey = trim($mapKey, ']');
                        if (!$paramsMap->has($mapKey)) {
                            $paramsMap->set($mapKey, new Map());
                        }
                        $paramsMap = $paramsMap->get($mapKey);
                    }
                }
            } else {
                $parameters->set($key, $value);
            }
        }
        return $parameters;
    }

    public function toString() {
        $queries = array();
        foreach ($this->flattenParams($this->parameters) as $key => $value) {
            $queries[] = $key . '=' . urlencode($value);
        }

        $port = $this->port ? self::PORT_SEPARATOR . $this->port : '';
        $scheme = $this->scheme ? $this->scheme . self::SCHEME_SEPARATOR : '';

        $isAbsolutePath = $this->path->isEmpty() || $this->path->first() == '';
        return ($this->host && $isAbsolutePath ? $scheme . self::HOST_PREFIX . $this->host . $port : '')
                . $this->path->toString()
                . ($queries ? self::QUERY_STRING_SEPARATOR . implode('&', $queries) : '')
                . ($this->fragment ? self::FRAGMENT_SEPARATOR . $this->fragment : '');
    }

    public function __toString() {
        return $this->toString();
    }

    private function flattenParams(Map $parameters, $i = 0) {
        $flat = new Map();
        foreach ($parameters as $key => $value) {
            if ($value instanceof Map) {
                foreach ($this->flattenParams($value, $i+1) as $subKey => $subValue) {
                    $flatKey = $i ? "{$key}][{$subKey}" : "{$key}[{$subKey}]";
                    $flat->set($flatKey, $subValue);
                }
            } else {
                $flat->set($key, $value);
            }
        }
        return $flat;
    }

    /**
     * @return static
     */
    public function copy() {
        return new Url($this->scheme, $this->host, $this->port, $this->path->copy(), $this->parameters->deepCopy(), $this->fragment);
    }

    public function merge(Url $with) {
        if ($with->path->isAbsolute()) {
            $this->path = $with->path->copy();
        } else {
            foreach ($with->path as $resource) {
                $this->path->append($resource);
            }
        }

        if ($with->host) {
            $this->scheme = $with->scheme;
            $this->host = $with->host;
            $this->port = $with->port;
        }

        foreach ($with->parameters as $key => $value) {
            $this->parameters->set($key, $value);
        }

        if ($with->fragment) {
            $this->fragment = $with->fragment;
        }
    }

}
