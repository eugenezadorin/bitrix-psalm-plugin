<?php

namespace Zadorin\BitrixPsalmPlugin;

use SimpleXMLElement;
use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;
use Bitrix\Main\Loader;

final class Plugin implements PluginEntryPointInterface
{
    /** @var SimpleXMLElement|null */
    private static $config = null;

    /** @var string[] List of paths to modules ORM annotations */
    private static $ormAnnotations = [];

    /** @return void */
    public function __invoke(RegistrationInterface $psalm, ?SimpleXMLElement $config = null): void
    {
        foreach ($this->getHookClasses() as $className) {
            $psalm->registerHooksFromClass($className);
        }        
        
        foreach ($this->getStubFiles() as $file) {
            $psalm->addStubFile($file);
        }

        $this->initPsalmConfiguration();
        $this->initGlobalVarsDefinitions();
    }

    /** @return list<string> */
    private function getHookClasses(): array
    {
        $result = [];
        $files = scandir(__DIR__ . '/Hooks') ?: [];
        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || $file === 'Hook.php') continue;
            [$name, $ext] = explode('.', $file);
            $result[] = '\\Zadorin\\BitrixPsalmPlugin\\Hooks\\' . $name;
        }
        return $result;
    }

    /** @return list<string> */
    private function getStubFiles(): array
    {
        $stubs = glob(__DIR__ . '/../stubs/*.phpstub') ?: [];
        $stubs = array_merge($stubs, self::$ormAnnotations);
        return $stubs;
    }

    private function initPsalmConfiguration()
    {
        $psalmConfig = \Psalm\Config::getInstance();
        
        $psalmConfig->use_phpdoc_method_without_magic_or_parent = true;
    }

    private function initGlobalVarsDefinitions()
    {
        $psalmConfig = \Psalm\Config::getInstance();
        $bitrixGlobals = [
            '$DB' => 'CDatabase',
            '$APPLICATION' => 'CMain',
            '$DBType' => 'string',
            '$GLOBALS' => 'array{DB: CDatabase, APPLICATION: CMain, DBType: string}'
        ];

        foreach ($bitrixGlobals as $varName => $varType) {
            if (!isset($psalmConfig->globals[$varName])) {
                $psalmConfig->globals[$varName] = $varType;    
            }
        }
    }

    public static function loadModules()
    {
        if (!class_exists(Loader::class)) {
            throw new \Exception('Bitrix core not initialized');
        }

        $modules = self::getRequiredModulesFromConfig();
        $ignoredModules = self::getIgnoredModulesFromConfig();

        if (count($modules) === 0) {
            $modules = self::getAllExistingModules();
        }

        foreach ($modules as $module) {
            if ($module === 'main') continue;
            if (in_array($module, $ignoredModules)) continue;

            Loader::includeModule($module);

            $ormAnnotationsPath = self::getBitrixDirectory() . "/modules/$module/meta/orm.php";
            if (file_exists($ormAnnotationsPath)) {
                self::$ormAnnotations[] = $ormAnnotationsPath;
            }
        }
    }

    private static function getRequiredModulesFromConfig(): array
    {
        $set = [];
        $config = self::getConfig();

        if (isset($config->includeModules) && isset($config->includeModules->module)) {
            foreach ($config->includeModules->module as $module) {
                $set[] = (string)$module['name'];
            }
        }

        $set = array_unique($set);
        $set = array_diff($set, ['']);
        return $set;
    }

    private static function getIgnoredModulesFromConfig(): array
    {
        $set = [];
        $config = self::getConfig();

        if (isset($config->ignoreModules) && isset($config->ignoreModules->module)) {
            foreach ($config->ignoreModules->module as $module) {
                $set[] = (string)$module['name'];
            }
        }

        $set = array_unique($set);
        $set = array_diff($set, ['']);
        return $set;
    }

    private static function getAllExistingModules(): array
    {
        $set = [];

        $modulesDirectory = self::getBitrixDirectory() . '/modules';
        if (!is_dir($modulesDirectory)) {
            throw new \Exception('bitrixDir/modules is not ad directory');
        }

        $modules = scandir($modulesDirectory);

        if (!is_array($modules)) {
            throw new \Exception('bitrixDir does not contain modules');
        }

        foreach ($modules as $module) {
            if ($module === '.' || $module === '..') continue;
            if (!is_dir("$modulesDirectory/$module")) continue;

            $set[] = $module;
        }

        return $set;
    }

    public static function getBitrixDirectory(): string
    {
        $config = self::getConfig();

        if ($config === null) {
            throw new \Exception('Plugin configuration is empty');
        }

        if (!isset($config->bitrixDir)) {
            throw new \Exception('bitrixDir option not specified');
        }

        $dir = realpath((string)$config->bitrixDir);
        
        if ($dir === false) {
            throw new \Exception('bitrixDir option value is invalid');
        }

        if (!is_dir($dir)) {
            throw new \Exception('bitrixDir option value is not a directory');
        }

        return $dir;
    }

    public static function getDocumentRoot(): string
    {
        return dirname(self::getBitrixDirectory());
    }

    public static function getLocalDirectory(): string
    {
        return self::getDocumentRoot() . '/local';
    }

    public static function getConfig(): ?SimpleXMLElement
    {
        if (self::$config === null) {
            $psalmConfig = \Psalm\Config::getInstance();
            foreach ($psalmConfig->getPluginClasses() as $plugin) {
                if ($plugin['class'] === '\\' . self::class) {
                    self::$config = $plugin['config'];
                }
            }
        }

        return self::$config;       
    }
}
