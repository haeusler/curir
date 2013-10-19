<?php
namespace spec\watoki\curir\resources;
 
use spec\watoki\curir\fixtures\FileFixture;
use spec\watoki\curir\fixtures\ResourceFixture;
use watoki\scrut\Specification;

/**
 * @property ResourceFixture resource <-
 * @property FileFixture file <-
 */
class ContainerTest extends Specification {

    function testRespondsItself() {
        $this->resource->givenTheContainer_WithTheBody('MySelf', 'function doGet() {
            return new \watoki\curir\responder\DefaultPresenter("Hello World");
        }');

        $this->resource->whenISendTheRequestToThatResource();
        $this->resource->thenTheResponseShouldHaveTheBody('"Hello World"');
    }

    function testNotExistingChild() {
        $this->resource->givenTheRequestHasTheTarget('notexisting');
        $this->resource->givenTheContainer('Childless');

        $this->resource->whenITryToSendTheRequestToThatResource();
        $this->resource->thenTheRequestShouldFailWith('Resource [notexisting] not found in container [ChildlessResource]');
    }

    function testForwardToStaticChild() {
        $this->file->givenTheFile_WithTheContent('StaticChild/test.txt', 'Hello World');
        $this->resource->givenTheStaticResourceFor('test.txt');
        $this->resource->givenTheRequestHasTheTarget('test');
        $this->resource->givenIRequestTheFormat('txt');
        $this->resource->givenTheContainer('StaticChild');

        $this->resource->whenISendTheRequestToThatResource();
        $this->resource->thenTheResponseShouldHaveTheBody('Hello World');
    }

    function testForwardToDynamicChild() {
        $this->resource->givenTheDynamicResource_In_WithTheBody('Child', 'test/WithDynamicChild', 'function doGet() {
            return new \watoki\curir\responder\DefaultPresenter("Found it");
        }');
        $this->resource->givenTheRequestHasTheTarget('Child');
        $this->resource->givenTheContainer_In('WithDynamicChild', 'test');

        $this->resource->whenISendTheRequestToThatResource();
        $this->resource->thenTheResponseShouldHaveTheBody('"Found it"');
    }

    function testForwardToGrandChild() {
        $this->resource->givenTheDynamicResource_In_WithTheBody('GrandChild', 'WithGrandChild/test/folder', 'function doGet() {
            return new \watoki\curir\responder\DefaultPresenter("Found me");
        }');
        $this->resource->givenTheRequestHasTheTarget('test/folder/GrandChild');
        $this->resource->givenTheContainer('WithGrandChild');

        $this->resource->whenISendTheRequestToThatResource();
        $this->resource->thenTheResponseShouldHaveTheBody('"Found me"');
    }

    function testForwardToDynamicContainer() {
        $this->resource->givenTheContainer_In_WithTheBody('Dynamic', 'DynamicParent',
            'public function respond(\watoki\curir\http\Request $r) {
                return new \watoki\curir\http\Response("Found");
            }');
        $this->resource->givenTheRequestHasTheTarget('Dynamic');
        $this->resource->givenTheContainer('DynamicParent');

        $this->resource->whenISendTheRequestToThatResource();
        $this->resource->thenTheResponseShouldHaveTheBody('Found');
    }

    function testDynamicChildIsPreferred() {
        $this->file->givenTheFile_WithTheContent('Test.json', 'The file');
        $this->resource->givenTheDynamicResource_In_WithTheBody('Test', 'PrefersDynamicChild', 'function doGet() {
            return new \watoki\curir\responder\DefaultPresenter("Dynamic content");
        }');
        $this->resource->givenTheRequestHasTheTarget('Test');
        $this->resource->givenTheContainer('PrefersDynamicChild');

        $this->resource->whenISendTheRequestToThatResource();
        $this->resource->thenTheResponseShouldHaveTheBody('"Dynamic content"');
    }

    function testDynamicContainerIsPreferred() {
        $this->resource->givenTheDynamicResource_In_WithTheBody('Neglected', 'Overwritten', 'function doGet() {}');
        $this->resource->givenTheRequestHasTheTarget('Test/Neglected');
        $this->resource->givenTheContainer_In_WithTheBody('Overwritten', '', 'public function respond(\watoki\curir\http\Request $r) {
            return new \watoki\curir\http\Response("Me first");
        }');

        $this->resource->whenISendTheRequestToThatResource();
        $this->resource->thenTheResponseShouldHaveTheBody('Me first');
    }

    function testForwardToInheritedChild() {
        $this->markTestIncomplete();
        $this->resource->givenTheContainer_In('BaseResource', 'other/place');
        $this->resource->givenTheDynamicResource_In_WithTheBody('InheritedChildResource', 'other/place/Base', 'function doGet() {
            return new \watoki\curir\responder\DefaultPresenter("I am inherited");
        }');
        $this->resource->givenTheContainer_Extending('SubResource', '\other\place\BaseResource');
        $this->resource->givenTheRequestHasTheTarget('Sub/InheritedChild');

        $this->resource->whenISendTheRequestToThatResource();
        $this->resource->thenTheResponseShouldHaveTheBody('"I am inherited"');
    }

}
 