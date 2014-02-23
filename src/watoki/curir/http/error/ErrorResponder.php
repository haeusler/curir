<?php
namespace watoki\curir\http\error;

use watoki\curir\http\Request;
use watoki\curir\http\Response;
use watoki\curir\Resource;
use watoki\curir\Responder;

class ErrorResponder extends Responder {

    /** @var \Exception */
    private $exception;

    /** @var Resource */
    private $root;

    function __construct(Resource $root, \Exception $e) {
        $this->root = $root;
        $this->exception = $e;
    }

    /**
     * @param \watoki\curir\http\Request $request
     * @return \watoki\curir\http\Response
     */
    public function createResponse(Request $request) {
        $status = Response::STATUS_SERVER_ERROR;
        $userMessage = '';
        if ($this->exception instanceof HttpError) {
            $status = $this->exception->getStatus();
            $userMessage = $this->exception->getUserMessage();
        }

        if (in_array('html', $request->getFormats())) {
            $template = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'error.html');
            $template = str_replace('$status', $status, $template);
            $template = str_replace('$rootUrl', $this->root->getUrl(), $template);
            $template = str_replace('$timestamp', date('Y-m-d H:i:s'), $template);
            $template = str_replace('$userMessage', $userMessage, $template);
            $template = str_replace('$type', get_class($this->exception), $template);
            $template = str_replace('$message', $this->exception->getMessage(), $template);
            $template = str_replace('$trace', $this->exception->getTraceAsString(), $template);
            $response = new Response($template);
        } else {
            $response = new Response($this->exception->getMessage());
        }

        $response->setStatus($status);
        return $response;
    }
}