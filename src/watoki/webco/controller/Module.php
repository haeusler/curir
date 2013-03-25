<?php
namespace watoki\webco\controller;

use watoki\collections\Liste;
use watoki\webco\Controller;
use watoki\webco\MimeTypes;
use watoki\webco\Path;
use watoki\webco\Request;
use watoki\webco\Response;
use watoki\webco\Router;
use watoki\webco\router\FileRouter;
use watoki\webco\router\StaticRouter;

abstract class Module extends Controller {

    public static $CLASS = __CLASS__;

    /**
     * @var Liste|Router[]
     */
    private $routers;

    /**
     * @return \watoki\collections\Liste|Router[]
     */
    protected function createRouters() {
        return new Liste();
    }

    /**
     * @param Request $request
     * @throws \Exception
     * @return Response
     */
    public function respond(Request $request) {
        $this->cutAbsoluteBase($request->getResource());
        $controller = $this->resolveController($request);
        if ($controller) {
            return $controller->respond($request);
        }

        if ($request->getResource()) {
            $file = $this->getDirectory() . '/' . $request->getResource()->toString();
            if (file_exists($file) && is_file($file) && $request->getResource()->getLeafExtension() != 'php') {
                return $this->createFileResponse($request);
            }
        }

        throw new \Exception('Could not resolve request [' . $request->getResource()->toString() . '] in [' . get_class($this) . ']');
    }

    /**
     * @param Request $request
     * @return Response
     */
    protected function createFileResponse(Request $request) {
        $response = $this->getResponse();
        $mimeType = MimeTypes::getType($request->getResource()->getLeafExtension());
        if ($mimeType) {
            $response->getHeaders()->set(Response::HEADER_CONTENT_TYPE, $mimeType);
        }

        $response->setBody(file_get_contents($this->getDirectory() . '/' . $request->getResource()->toString()));
        return $response;
    }

    protected function resolveController(Request $request) {
        $resource = $request->getResource();

        for ($i = $resource->getNodes()->count(); $i > 0; $i--) {
            $route = new Path($resource->getNodes()->slice(0, $i));
            foreach ($this->getRouters() as $router) {
                if ($router->matches($route)) {
                    return $router->resolve($request);
                }
            }
        }
        return null;
    }

    private function getRouters() {
        if (!$this->routers) {
            $this->routers = $this->createRouters();
            $this->routers->append(new FileRouter());

            foreach ($this->routers as $router) {
                $router->inject($this->factory, $this);
            }
        }
        return $this->routers;
    }

    /**
     * @param Path $route
     * @return Controller
     */
    public function resolve(Path $route) {
        $route = $route->copy();
        $this->cutAbsoluteBase($route);
        return $this->resolveController(new Request('', $route));
    }

    /**
     * Searches all static routes for given Controller
     *
     * @param string $controllerClass
     * @return Controller|null
     */
    public function findController($controllerClass) {
        return $this->findInRouters($controllerClass) ?: $this->findInFolders($controllerClass);
    }

    /**
     * @param $controllerClass
     * @return null|\watoki\webco\Controller
     */
    private function findInRouters($controllerClass) {
        foreach ($this->getRouters() as $router) {
            if (!$router instanceof StaticRouter) {
                continue;
            }

            $controller = $router->resolve(new Request('', $router->getRoute()->copy()));

            if ($router->getControllerClass() == $controllerClass) {
                return $controller;
            }

            if ($controller instanceof Module) {
                $foundChild = $controller->findController($controllerClass);
                if ($foundChild) {
                    return $foundChild;
                }
            }
        }
        return null;
    }

    /**
     * @param $controllerClass
     * @return null|\watoki\webco\Controller
     */
    private function findInFolders($controllerClass) {
        $commonNamespace = $this->findCommonNamespace($controllerClass, get_class($this));
        if ($commonNamespace) {
            $path = new Path(Liste::split('\\', substr($controllerClass, strlen($commonNamespace) + 1)));
            $request = new Request('', $path);
            try {
                return $this->resolveController($request);
            } catch (\Exception $e) {
            }
        }

        return null;
    }

    private function findCommonNamespace($class1, $class2) {
        $namespace1 = explode('\\', $class1);
        $namespace2 = explode('\\', $class2);

        $common = '';
        for ($i = 1; $i <= count($namespace1); $i++) {
            $nextCommon1 = implode('\\', array_slice($namespace1, 0, $i));
            $nextCommon2 = implode('\\', array_slice($namespace2, 0, $i));
            if ($nextCommon2 != $nextCommon1) {
                break;
            }
            $common = $nextCommon1;
        }
        return $common;
    }

    private function cutAbsoluteBase(Path $path) {
        $route = $this->getRoute();
        if ($path->getNodes()->slice(0, $route->getNodes()->count()) == $route->getNodes()) {
            $path->getNodes()->splice(0, $route->getNodes()->count());
        }
   }

}
