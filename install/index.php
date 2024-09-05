<?php

use Bitrix\Main\ModuleManager;

class izifir_mindbox extends CModule
{
    public $MODULE_ID = 'izifir.mindbox';

    public function __construct()
    {
        $arVersion = [];
        include (dirname(__FILE__) . '/version.php');

        $this->MODULE_VERSION = $arVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arVersion['VERSION_DATE'];

        $this->MODULE_NAME = 'Интеграция с mindbox';
        $this->MODULE_DESCRIPTION = 'Интеграция с mindbox';

        $this->PARTNER_NAME = 'IZIFIR';
        $this->PARTNER_URI = 'https://izifir.ru';
    }

    function DoInstall()
    {
        ModuleManager::registerModule($this->MODULE_ID);
    }

    function DoUninstall()
    {
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }
}
