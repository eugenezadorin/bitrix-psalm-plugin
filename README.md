# Bitrix Psalm Plugin

**Very first prototype**

## Install

```
composer require zadorin/bitrix-psalm-plugin --dev
```

## Configure

Options `psalm.autoloader` and `bitrixDir` are important.

```xml
<!-- <project-dir>/bitrix/psalm.xml -->
<?xml version="1.0"?>
<psalm
    autoloader="vendor/Zadorin/bitrix-psalm-plugin/autoload.php"
    errorLevel="1"
    resolveFromConfigFile="true"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xmlns="https://getpsalm.org/schema/config"
    xsi:schemaLocation="https://getpsalm.org/schema/config vendor/vimeo/psalm/config.xsd"
>
    <projectFiles>
        <directory name="modules/crm/lib" />
    </projectFiles>
    
    <issueHandlers>
        <InvalidGlobal errorLevel="suppress" />
    </issueHandlers>

    <plugins>
        <pluginClass class="\Zadorin\BitrixPsalmPlugin\Plugin">
            <bitrixDir>.</bitrixDir>
            <includeModules>
                <module name="crm" />
                <module name="sale" />
            </includeModules>
            <ignoreModules>
                <module name="currency" />
            </ignoreModules>
        </pluginClass>
    </plugins>

</psalm>
```

## Execute

```
./vendor/bin/psalm --memory-limit=4G
```
