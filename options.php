<?php
/**
 * @global $APPLICATION CMain
 */

use Bitrix\Main\Config\Option;

$request = \Bitrix\Main\HttpApplication::getInstance()->getContext()->getRequest();

if (isset($_POST['Maindbox']) && check_bitrix_sessid()) {
    foreach ($_POST['Maindbox'] as $option => $value) {
        Option::set('izifir.mindbox', $option, $value);
    }
    LocalRedirect($request->getServer()->getRequestUri());
}

$aTabs = [
    [
        'DIV' => 'edit1',
        'TAB' => 'Основные настройки',
        'TITLE' => 'Основные настройки',
    ]
];

$tabControl = new CAdminTabControl('tabControl', $aTabs);

$arValues = [
    'secret_key' => Option::get('izifir.mindbox', 'secret_key', ''),
    'api_url' => Option::get('izifir.mindbox', 'api_url', ''),
    'endpoint_id' => Option::get('izifir.mindbox', 'endpoint_id', ''),
    'log_dir' => Option::get('izifir.mindbox', 'log_dir', ''),
];
?>
<?php $tabControl->Begin(); ?>
<form method='post'
      action='<? echo $APPLICATION->GetCurPage() ?>?mid=<?= htmlspecialcharsbx($request['mid']) ?>&amp;lang=<?= $request['lang'] ?>'
      name='admitad_tracking_settings' ENCTYPE="multipart/form-data">
    <?php $tabControl->BeginNextTab(); ?>
    <tr>
        <td valign="middle" width="40%">API URL:</td>
        <td valign="middle" width="60%">
            <input type="text" name="Maindbox[api_url]" value="<?= $arValues['api_url'] ?>">
        </td>
    </tr>
    <tr>
        <td valign="middle" width="40%">Уникальный идентификатор сайта:</td>
        <td valign="middle" width="60%">
            <input type="text" name="Maindbox[endpoint_id]" value="<?= $arValues['endpoint_id'] ?>">
        </td>
    </tr>
    <tr>
        <td valign="middle" width="40%">Секретный ключ:</td>
        <td valign="middle" width="60%">
            <input type="text" name="Maindbox[secret_key]" value="<?= $arValues['secret_key'] ?>">
        </td>
    </tr>
    <tr>
        <td valign="middle" width="40%">Директория для логов:</td>
        <td valign="middle" width="60%">
            <input type="text" name="Maindbox[log_dir]" value="<?= $arValues['log_dir'] ?>">
        </td>
    </tr>
    <?php $tabControl->Buttons(); ?>
    <input type="submit" name="Update" class="adm-btn adm-btn-save" value="Сохранить">
    <?php $tabControl->End(); ?>
    <?= bitrix_sessid_post() ?>
</form>
