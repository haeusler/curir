<?php
namespace watoki\webco;
 
use watoki\collections\Liste;
use watoki\collections\Map;

class Request {

    public static $CLASS = __CLASS__;

    const METHOD_OPTIONS = 'options';
    const METHOD_GET = 'get';
    const METHOD_HEAD = 'head';
    const METHOD_POST = 'post';
    const METHOD_PUT = 'put';
    const METHOD_DELETE = 'delete';
    const METHOD_TRACE = 'trace';

    const HEADER_ACCEPT = 'Accept';
    const HEADER_ACCEPT_CHARSET = 'Accept-Charset';
    const HEADER_ACCEPT_ENCODING = 'Accept-Encoding';
    const HEADER_ACCEPT_LANGUAGE = 'Accept-Language';
    const HEADER_CACHE_CONTROL = 'Cache-Control';
    const HEADER_CONNECTION = 'Connection';
    const HEADER_PRAGMA = 'Pragme';
    const HEADER_USER_AGENT = 'User-Agent';

    private static $headerKeys = array(
        Request::HEADER_ACCEPT => 'HTTP_ACCEPT',
        Request::HEADER_ACCEPT_CHARSET => 'HTTP_ACCEPT_CHARSET',
        Request::HEADER_ACCEPT_ENCODING => 'HTTP_ACCEPT_ENCODING',
        Request::HEADER_ACCEPT_LANGUAGE => 'HTTP_ACCEPT_LANGUAGE',
        Request::HEADER_CACHE_CONTROL => 'HTTP_CACHE_CONTROL',
        Request::HEADER_CONNECTION => 'HTTP_CONNECTION',
        Request::HEADER_PRAGMA => 'HTTP_PRAGMA',
        Request::HEADER_USER_AGENT => 'HTTP_USER_AGENT'
    );

    /**
     * @var string Request::METHOD_*
     */
    private $method;

    /**
     * @var Liste List of parts of URL without query string
     */
    private $resourcePath;

    /**
     * @var Map Parameter keys and values parsed from query string or body
     */
    private $parameters;

    /**
     * @var Map Indexed by self::HEADER_*
     */
    private $headers;

    /**
     * @var string
     */
    private $body;

    public static function build($resource) {
        $method = $_SERVER['REQUEST_METHOD'];

        $params = Map::toCollections($_REQUEST);
        $body = file_get_contents('php://input');

        $headers = new Map();
        foreach (self::$headerKeys as $name => $key) {
            $headers->set($name, isset($_SERVER[$key]) ? $_SERVER[$key] : null);
        }

        return new Request($method, $resource, $params, $headers, $body);
    }

    /**
     * @param string $method
     * @param string $resource
     * @param \watoki\collections\Map $parameters
     * @param \watoki\collections\Map $headers
     * @param string $body
     */
    function __construct($method = Request::METHOD_GET, $resource = '', Map $parameters = null, Map $headers = null, $body = '') {
        $this->method = $method;
        $this->parameters = $parameters ?: new Map();
        $this->headers = $headers ?: new Map();
        $this->body = $body;
        $this->setResourcePath(Liste::split('/', $resource));
    }

    /**
     * @return string
     */
    public function getBody() {
        return $this->body;
    }

    /**
     * @return \watoki\collections\Map Indexed by self::HEADER_*
     */
    public function getHeaders() {
        return $this->headers;
    }

    /**
     * @return string Request::METHOD_*
     */
    public function getMethod() {
        return $this->method;
    }

    /**
     * @return \watoki\collections\Map
     */
    public function getParameters() {
        return $this->parameters;
    }

    /**
     * @param \watoki\collections\Liste $resourcePath
     */
    public function setResourcePath($resourcePath) {
        $this->resourcePath = $resourcePath;
    }

    /**
     * @return \watoki\collections\Liste
     */
    public function getResourcePath() {
        return $this->resourcePath;
    }

    /**
     * @return string
     */
    public function getResource() {
        return $this->resourcePath->join('/');
    }

    /**
     * @return string|null
     */
    public function getResourceExtension() {
        $baseExtension = explode('.', $this->getResourcePath()->last());
        return count($baseExtension) == 1 ? null : end($baseExtension);
    }

    public function getResourceName() {
        $baseExtension = explode('.', $this->getResourcePath()->last());
        return $baseExtension[0];
    }

}
