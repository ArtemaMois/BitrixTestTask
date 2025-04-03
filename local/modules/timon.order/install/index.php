<?php 

class timon_order extends CModule
{
    var $MODULE_ID = 'timon.order';
    var $MODULE_NAME = 'Обновление заказов';
    var $MODULE_DESCRIPTION = "Модуль для сайта timon.order. Пример контроллера";
    var $MODULE_VERSION = "1.0";
    var $MODULE_VERSION_DATE = "2023-04-09 12:00:00";
    var $PARTNER_NAME = 'Artem Moiseev';
    var $PARTNER_URI = '';

    public function DoInstall()
    {
        \Bitrix\Main\ModuleManager::registerModule($this->MODULE_ID);
    }

    public function DoUninstall()
    {
        \Bitrix\Main\ModuleManager::unRegisterModule($this->MODULE_ID);
    }
}
