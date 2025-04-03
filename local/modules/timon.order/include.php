<?php 


$module_folder = \Bitrix\Main\Application::getDocumentRoot() . '/local/modules/timon.order/';

\Bitrix\Main\Loader::registerNamespace("Timon\Order", $module_folder . "/lib/");