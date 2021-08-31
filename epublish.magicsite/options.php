<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Epublish\Magicsite\Integration;

Loc::loadMessages(__FILE__);

$module_id = 'epublish.magicsite';

Loc::loadMessages($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/options.php");
Loc::loadMessages(__FILE__);

if ($APPLICATION->GetGroupRight($module_id)<"S")
{
    $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
}

\Bitrix\Main\Loader::includeModule($module_id);

$request = \Bitrix\Main\HttpApplication::getInstance()->getContext()->getRequest();

$aTabs = array(
  array(
    'DIV' => 'edit1',
    'TAB' => Loc::getMessage('EPUBLISH_MAGICSITE_TAB_SETTINGS'),
    'OPTIONS' => array(
      array('magicsite_url', Loc::getMessage('EPUBLISH_MAGICSITE_URL_TITLE'),
      '',
      array('text', 128)),
    )
  ),
);

if ($request->isPost() && $request['Update'] && check_bitrix_sessid())
{
  foreach ($aTabs as $aTab)
  {
    foreach ($aTab['OPTIONS'] as $arOption)
    {
      if (!is_array($arOption))
        continue;

      if ($arOption['note'])
        continue;


      $optionName = $arOption[0];

      $optionValue = $request->getPost($optionName);

      if ($optionName == 'magicsite_url') {
        if (!$url = Integration::getUrl($optionValue)) {
          CAdminMessage::ShowMessage(Loc::getMessage("EPUBLISH_MAGICSITE_URL_ERROR"));
          continue;
        }
      }

      Option::set($module_id, $optionName, $url);
    }
  }
}

$tabControl = new CAdminTabControl('tabControl', $aTabs);

?>
<?php $tabControl->Begin(); ?>
<form method='post' action='<?php echo $APPLICATION->GetCurPage()?>?mid=<?php echo htmlspecialcharsbx($request['mid'])?>&amp;lang=<?php echo $request['lang']?>' name='epublish_magicsite_settings'>
  <?php foreach ($aTabs as $aTab) : ?>
    <?php if ($aTab['OPTIONS']) : ?>
      <?php $tabControl->BeginNextTab(); ?>
      <?php __AdmSettingsDrawList($module_id, $aTab['OPTIONS']); ?>
    <?php endif; ?>
  <?php endforeach; ?>

  <?php
    $tabControl->BeginNextTab();
    $tabControl->Buttons(); ?>

    <input type="submit" name="Update" value="<?php echo Loc::getMessage('MAIN_SAVE')?>">
    <input type="reset" name="reset" value="<?php echo Loc::getMessage('MAIN_RESET')?>">
    <?php echo bitrix_sessid_post(); ?>
</form>
<?php $tabControl->End(); ?>
