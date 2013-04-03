<?php
namespace spec\watoki\curir;

/**
 * @property RoutingTest_When when
 * @property RoutingTest_Then then
 */
use spec\watoki\curir\steps\Then;
use spec\watoki\curir\steps\When;
use watoki\factory\Factory;
use watoki\curir\Controller;
use watoki\curir\Path;
use watoki\curir\Request;
use watoki\curir\Response;
use \watoki\curir\controller\Module;

/**
 * @property RoutingTest_Given given
 * @property RoutingTest_When when
 * @property RoutingTest_Then then
 */
class RoutingTest extends Test {

    function testRequestToModule() {
        $this->given->theFolder('defaultcomp');
        $this->given->theFolder('defaultcomp/inner');
        $this->given->theClass_In_Extending_WithTheBody('defaultcomp\Module', 'defaultcomp', '\watoki\curir\controller\Module', '');
        $this->given->theClass_In_Extending_WithTheBody('defaultcomp\inner\Inner', 'defaultcomp/inner', '\watoki\curir\controller\Module', '');

        $this->given->theRequestMethodIs(Request::METHOD_GET);
        $this->given->theRequestResourceIs('inner');
        $this->when->iTryToSendTheRequestTo('defaultcomp\Module');

        $this->then->anExceptionContaining_ShouldBeThrown('defaultcomp\inner\Inner');
    }

    function testChildModule() {
        $this->given->theFolder('childmodule');
        $this->given->theFolder('childmodule/child');

        $this->given->theModule_In('childmodule\Module', 'childmodule');
        $this->given->theModule_In('childmodule\child\Child', 'childmodule/child');
        $this->given->theComponent_In_Returning('childmodule\child\Index', 'childmodule/child', '"hi there"');

        $this->given->theRequestMethodIs(Request::METHOD_GET);
        $this->given->theRequestResourceIs('child/index');
        $this->when->iSendTheRequestTo('childmodule\Module');

        $this->then->theResponseBodyShouldBe('hi there');
    }

    function testRouteToSister() {
        $this->given->theFolder('siblings');
        $this->given->theFolder('siblings/brother');
        $this->given->theFolder('siblings/sister');

        $this->given->theModule_In_WithAStaticRouterFrom_To('siblings\brother\Module', 'siblings/brother',
            'adopted', 'siblings\sister\Module');
        $this->given->theModule_In('siblings\sister\Module', 'siblings/sister');
        $this->given->theComponent_In_Returning('siblings\sister\Index', 'siblings/sister', '"hello world"');

        $this->given->theRequestMethodIs(Request::METHOD_GET);
        $this->given->theRequestResourceIs('adopted/index');
        $this->when->iSendTheRequestTo('siblings\brother\Module');

        $this->then->theResponseBodyShouldBe('hello world');
    }

    function testSpecificOverridesGeneral() {
        $this->given->theFolder('override');
        $this->given->theFolder('override/brother');
        $this->given->theFolder('override/sister');

        $this->given->theModule_In_WithTheRouters('override\brother\Module', 'override/brother', array(
            'adopted/' => 'override\sister\NotExisting',
            'adopted/yeah/' => 'override\sister\Module'
        ));
        $this->given->theModule_In('override\sister\Module', 'override/sister');
        $this->given->theComponent_In_Returning('override\sister\Index', 'override/sister', '"hello world"');

        $this->given->theRequestMethodIs(Request::METHOD_GET);
        $this->given->theRequestResourceIs('adopted/yeah/index');
        $this->when->iSendTheRequestTo('override\brother\Module');

        $this->then->theResponseBodyShouldBe('hello world');
    }

    function testFindChild() {
        $this->given->theFolder('findchild');
        $this->given->theClass_In_Extending_WithTheBody('findchild\ParentModule', 'findchild', '\watoki\curir\controller\Module', '');
        $this->given->theClass_In_Extending_WithTheBody('findchild\ChildModule', 'findchild', '\watoki\curir\controller\Module', '');

        $this->when->iAsk_ToFind('findchild\ParentModule', 'findchild\ChildModule');

        $this->then->itShouldReturnAnInstanceOf('findchild\ChildModule');
    }

    function testFindGrandChild() {
        $this->given->theFolder('findgrand');
        $this->given->theFolder('findgrand/grand');
        $this->given->theClass_In_Extending_WithTheBody('findgrand\ParentModule', 'findgrand', '\watoki\curir\controller\Module', '');
        $this->given->theClass_In_Extending_WithTheBody('findgrand\grand\ChildModule', 'findgrand/grand', '\watoki\curir\controller\Module', '');

        $this->when->iAsk_ToFind('findgrand\ParentModule', 'findgrand\grand\ChildModule');

        $this->then->itShouldReturnAnInstanceOf('findgrand\grand\ChildModule');
    }

    function testNonExistingChild() {
        $this->given->theFolder('findnone');
        $this->given->theClass_In_Extending_WithTheBody('findnone\ParentModule', 'findnone', '\watoki\curir\controller\Module', '');

        $this->when->iAsk_ToFind('findnone\ParentModule', 'findnone\grand\ChildModule');

        $this->then->itShouldNotFindIt();
    }

    function testFindAdoptedChild() {
        $this->given->theFolder('findadopted');
        $this->given->theFolder('findadopted/brother');
        $this->given->theFolder('findadopted/sister');

        $this->given->theModule_In_WithAStaticRouterFrom_To('findadopted\brother\Module', 'findadopted/brother',
            'adopted', 'findadopted\sister\Index');
        $this->given->theComponent_In_Returning('findadopted\sister\Index', 'findadopted/sister', '"hello you"');

        $this->when->iAsk_ToFind('findadopted\brother\Module', 'findadopted\sister\Index');

        $this->then->itShouldReturnAnInstanceOf('findadopted\sister\Index');
        $this->then->theRouteOfTheFoundControllerShouldBe('/base/adopted');
    }

    function testFindChildOfAdoptedChild() {
        $this->given->theFolder('findadoptedgrand');
        $this->given->theFolder('findadoptedgrand/brother');
        $this->given->theFolder('findadoptedgrand/sister');

        $this->given->theModule_In('findadoptedgrand\sister\Module', 'findadoptedgrand/sister');
        $this->given->theModule_In_WithAStaticRouterFrom_To('findadoptedgrand\brother\Module', 'findadoptedgrand/brother',
            'adopted/', 'findadoptedgrand\sister\Module');
        $this->given->theComponent_In_Returning('findadoptedgrand\sister\Index', 'findadoptedgrand/sister', '"hello you"');

        $this->when->iAsk_ToFind('findadoptedgrand\brother\Module', 'findadoptedgrand\sister\Index');

        $this->then->itShouldReturnAnInstanceOf('findadoptedgrand\sister\Index');
        $this->then->theRouteOfTheFoundControllerShouldBe('/base/adopted/Index');
    }

    function testRedirectRouter() {
        $this->given->theFolder('reroute');
        $this->given->theClass_In_Extending_WithTheBody('reroute\Module', 'reroute', '\watoki\curir\controller\Module', '
            protected function createRouters() {
                return new \watoki\collections\Liste(array(
                    new \watoki\curir\router\RedirectRouter(\watoki\curir\Path::parse("notHere"), "somewhere/else.html")
                ));
            }
        ');

        $this->given->theRequestResourceIs('notHere');
        $this->when->iSendTheRequestTo('reroute\Module');

        $this->then->theResponseHeader_ShouldBe(Response::HEADER_LOCATION, '/base/somewhere/else.html');
    }

}

class RoutingTest_Given extends steps\Given {

    public function theModule_In($className, $folder) {
        $this->theClass_In_Extending_WithTheBody($className, $folder, '\watoki\curir\controller\Module', '');
    }

    public function theComponent_In_Returning($className, $folder, $retval) {
        $this->theClass_In_Extending_WithTheBody($className, $folder, '\watoki\curir\controller\Component', '
            public function doGet() {return ' . $retval . ';}
            public function doRender($model, $template) {}
        ');
    }

    public function theModule_In_WithAStaticRouterFrom_To($className, $folder, $route, $controllerClass) {
        $this->theModule_In_WithTheRouters($className, $folder, array($route => $controllerClass));
    }

    public function theModule_In_WithTheRouters($className, $folder, $routers) {
        $routerDefs = '';
        foreach ($routers as $route => $target) {
            $routerDefs .= 'new \watoki\curir\router\StaticRouter(\watoki\curir\Path::parse("' . $route . '"), \'' . $target . '\'),';
        }

        $this->theClass_In_Extending_WithTheBody($className, $folder, '\watoki\curir\controller\Module', '
            function createRouters() {
                return new \watoki\collections\Liste(array(
                    ' . $routerDefs . '
                ));
            }
        ');
    }
}

class RoutingTest_When extends When {

    /**
     * @var Controller
     */
    public $found;

    public function iAsk_ToFind($parentClass, $childClass) {
        $factory = new Factory();
        /** @var $parent Module */
        $parent = $factory->getInstance($parentClass, array('route' => Path::parse('/base/')));
        $this->found = $parent->findController($childClass);
    }
}

/**
 * @property RoutingTest test
 */
class RoutingTest_Then extends Then {

    public function itShouldReturnAnInstanceOf($class) {
        $this->test->assertInstanceOf($class, $this->test->when->found);
    }

    public function itShouldNotFindIt() {
        $this->test->assertNull($this->test->when->found);
    }

    public function theRouteOfTheFoundControllerShouldBe($route) {
        $this->test->assertEquals($route, $this->test->when->found->getRoute()->toString());
    }
}