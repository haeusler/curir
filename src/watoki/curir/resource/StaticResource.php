<?php
namespace watoki\curir\resource;

use watoki\curir\http\MimeTypes;
use watoki\curir\http\Request;
use watoki\curir\http\Response;
use watoki\curir\http\Url;
use watoki\curir\Resource;

/**
 * A StaticResource is the implicit Resource associated with a static file.
 */
class StaticResource extends Resource {

    public static $CLASS = __CLASS__;

    private $file;

    public function __construct(Url $url, Resource $parent = null, $file) {
        parent::__construct($url, $parent);
        $this->file = $file;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function respond(Request $request) {
        $response = new Response();

        if (strpos(basename($this->file), '.')) {
            $parts = explode('.', basename($this->file));
            $extension = array_pop($parts);
            $contentType = MimeTypes::getType($extension);
        } else {
            $contentType = $this->getDefaultContentType();
        }

        $response->setBody(file_get_contents($this->file));
        $response->getHeaders()->set(Response::HEADER_CONTENT_TYPE, $contentType);

        return $response;
    }

    protected function getDefaultContentType() {
        return MimeTypes::getType('txt');
    }
}
 