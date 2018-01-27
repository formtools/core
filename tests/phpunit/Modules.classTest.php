<?php

namespace FormTools\Tests;

use PHPUnit\Framework\TestCase;
use FormTools\Modules;


class ModulesTest extends TestCase
{

    // Modules::getModuleNamespace()

    public function testGetModuleNamespace_SameValue()
    {
        $module_folder = "Modulename";
        $namespace = Modules::getModuleNamespace($module_folder);
        $this->assertEquals($namespace, $module_folder);
    }

    public function testGetModuleNamespace_Uppercases()
    {
        $module_folder = "modulename";
        $namespace = Modules::getModuleNamespace($module_folder);
        $this->assertEquals($namespace, "Modulename");
    }

    public function testGetModuleNamespace_ConvertsUnderscores()
    {
        $module_folder = "module_name";
        $namespace = Modules::getModuleNamespace($module_folder);
        $this->assertEquals($namespace, "ModuleName");
    }

    public function testGetModuleNamespace_MultipleWords()
    {
        $module_folder = "this_is_my_module_folder_name";
        $namespace = Modules::getModuleNamespace($module_folder);
        $this->assertEquals($namespace, "ThisIsMyModuleFolderName");
    }

}
