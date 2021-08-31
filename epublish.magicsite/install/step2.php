<?php

  use \Bitrix\Main\Localization\Loc;
  use \Epublish\Magicsite\Integration;

  Loc::loadMessages(__FILE__);

  if(!check_bitrix_sessid()) return;

  global $errors;

  if ($errors == '') {
    echo CAdminMessage::ShowNote(Loc::getMessage("MOD_INST_OK"));
    echo CAdminMessage::ShowMessage(Array("TYPE"=>"OK", "MESSAGE" =>Loc::getMessage('EPUBLISH_MAGICSITE_INSTALL_SETTINGS_TITLE'), "DETAILS"=>Loc::getMessage('EPUBLISH_MAGICSITE_INSTALL_SETTINGS_DESC'), "HTML"=>true));

  } else {
    for ($i=0; $i<count($errors); $i++) {
      $alErrors .= $errors[$i]."<br>";
    }
    echo CAdminMessage::ShowMessage(Array("TYPE"=>"ERROR", "MESSAGE" =>Loc::getMessage("MOD_INST_ERR"), "DETAILS"=>$alErrors, "HTML"=>true));
  }

  if ($ex = $APPLICATION->GetException()) {
    echo CAdminMessage::ShowMessage(Array("TYPE" => "ERROR", "MESSAGE" => GetMessage("MOD_INST_ERR"), "HTML" => true, "DETAILS" => $ex->GetString()));
  }
?>
<form action="<?php echo $APPLICATION->GetCurPage()?>">
  <input type="hidden" name="lang" value="<?php echo LANG?>">
  <input type="submit" name="" value="<?php echo Loc::getMessage("MOD_BACK")?>">
  <input type="button" name="" onClick="window.location.href='/bitrix/admin/settings.php?mid=epublish.magicsite&lang=<?php echo LANG ?>'") value="<?php echo Loc::getMessage('EPUBLISH_MAGICSITE_INSTALL_SETTINGS'); ?>">
<form>
