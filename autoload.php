<?php

use Zadorin\BitrixPsalmPlugin\Plugin;

if (!$_SERVER['DOCUMENT_ROOT']) {
    $_SERVER['DOCUMENT_ROOT'] = Plugin::getDocumentRoot();
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/cli/bootstrap.php');

Plugin::loadModules();
