<?php
namespace spec\watoki\webco\steps;

use watoki\collections\Liste;

class CompositionTestGiven extends Given {

    public function theFolder_WithModule($folder) {
        $this->theFolder($folder);
        $this->theModule_In($folder . '\Module', $folder);
    }

    public function theComponent_In_WithTemplate($className, $folder, $template) {
        $shortClassName = Liste::split('\\', $className)->pop();
        $this->theComponent_In_WithTheBody($className, $folder, '
        function doGet() {
            return array("msg" => "World");
        }');
        $this->theFile_In_WithContent(lcfirst($shortClassName) . '.html', $folder, $template);
    }

    public function theComponent_In_WithTheBody($className, $folder, $body) {
        $this->theClass_In_Extending_WithTheBody($className, $folder, '\watoki\webco\controller\Component', "
            protected function doRender(\$model, \$template) {
                foreach (\$model as \$key => \$value) {
                    \$template = str_replace('%' . \$key . '%', \$value, \$template);
                }
                return \$template;
            }

            $body
        ");
    }

    public function theSuperComponent_In_WithTheBody($className, $folder, $body) {
        $this->theClass_In_Extending_WithTheBody($className, $folder, '\watoki\webco\controller\SuperComponent', "
            protected function doRender(\$model, \$template) {
                foreach (\$model as \$key => \$value) {
                    \$template = str_replace('%' . \$key . '%', \$value, \$template);
                }
                return \$template;
            }

            $body
        ");
    }

    public function theModule_In($moduleClass, $folder) {
        $this->theClass_In_Extending_WithTheBody($moduleClass, $folder, '\watoki\webco\controller\Module', '');
    }

}