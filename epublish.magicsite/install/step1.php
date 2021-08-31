<?php

  use \Bitrix\Main\Localization\Loc;
  use \Epublish\Magicsite\Integration;

  Loc::loadMessages(__FILE__);

  $warnings = array();
?>
<form action="<?php echo $APPLICATION->GetCurPage(); ?>" name="blog_install">
<?php echo bitrix_sessid_post(); ?>
  <input type="hidden" name="lang" value="<?php echo LANG ?>">
  <input type="hidden" name="id" value="epublish.magicsite">
  <input type="hidden" name="install" value="Y">
  <input type="hidden" id="error-main" name="error[main]" value="Y">
  <input type="hidden" id="error-curl" name="error[curl]" value="Y">
  <input type="hidden" id="error-dom" name="error[dom]" value="Y">
  <input type="hidden" name="step" value="2">

  <script language="JavaScript">
    document.addEventListener('DOMContentLoaded', function(){
      ChangeInstallPublic();
    });

    function ChangeInstallPublic() {
      var disabled = false;
      if (document.getElementById('error-main').value == "Y") {
        disabled = true;
      } else if (document.getElementById('error-curl').value == "Y") {
        disabled = true;
      } else if (document.getElementById('error-dom').value == "Y") {
        disabled = true;
      } else if (typeof(document.getElementById('overwrite-sections')) != 'undefined' && document.getElementById('overwrite-sections') != null){
        if (document.getElementById('overwrite-sections').checked == false) {
          disabled = true;
        }
      }
      document.getElementById('inst').disabled = disabled;
    }
  </script>

  <div class="inst-cont-title-wrap">
    <div class="inst-cont-title"><?php echo Loc::getMessage('EPUBLISH_MAGICSITE_INSTALL_CHECK_TITLE'); ?></div>

  </div>
  <div class="step-content">
    <?php echo Loc::getMessage('EPUBLISH_MAGICSITE_INSTALL_CHECK_DESC'); ?><br>
    <table border="0" class="data-table data-table-multiple-column">
      <tr>
        <td class="header"><?php echo Loc::getMessage('EPUBLISH_MAGICSITE_INSTALL_CHECK_HEADER_PARAM'); ?></td>
        <td class="header"><?php echo Loc::getMessage('EPUBLISH_MAGICSITE_INSTALL_CHECK_HEADER_NEED'); ?></td>
        <td class="header"><?php echo Loc::getMessage('EPUBLISH_MAGICSITE_INSTALL_CHECK_HEADER_CURRENT'); ?></td>
      </tr>
      <tr>
        <td valign="top"><?php echo Loc::getMessage('EPUBLISH_MAGICSITE_INSTALL_CHECK_MAIN_MODULE_VERSION_TITLE'); ?></td>
        <td valign="top"><?php echo Loc::getMessage('EPUBLISH_MAGICSITE_INSTALL_CHECK_MAIN_MODULE_VERSION_NEED'); ?></td>
        <td valign="top">
          <?php if (CheckVersion(\Bitrix\Main\ModuleManager::getVersion('main'), '14.00.00')) : ?>
            <script language="JavaScript">document.getElementById('error-main').value = "N"</script>
            <b><span style="color:green"><?php echo \Bitrix\Main\ModuleManager::getVersion('main'); ?></span></b>
          <?php else : ?>
            <b><span style="color:red"><?php echo \Bitrix\Main\ModuleManager::getVersion('main'); ?></span></b>
          <?php endif; ?>
        </td>
      </tr>
      <tr>
        <td colspan="3"><b><?php echo Loc::getMessage('EPUBLISH_MAGICSITE_INSTALL_CHECK_PHP_EXTENSION_TITLE'); ?>:</b></td>
      </tr>
      <tr>
        <td valign="top"><a href="https://www.php.net/manual/en/book.curl.php" target="_blank"><?php echo Loc::getMessage('EPUBLISH_MAGICSITE_INSTALL_CHECK_PHP_EXTENSION_CURL_TITLE'); ?></a></td>
        <td valign="top">Установлен</td>
        <td valign="top">
          <?php if (extension_loaded('curl')) : ?>
            <script language="JavaScript">document.getElementById('error-curl').value = "N"</script>
            <b><span style="color:green"><?php echo Loc::getMessage('EPUBLISH_MAGICSITE_INSTALL_CHECK_PHP_EXTENSION_LOADED'); ?></span></b>
          <?php else : ?>
            <b><span style="color:green"><?php echo Loc::getMessage('EPUBLISH_MAGICSITE_INSTALL_CHECK_PHP_EXTENSION_UNLOADED'); ?></span></b>
          <?php endif; ?>
        </td>
      </tr>
      <tr>
        <td valign="top">
          <a href="https://www.php.net/manual/ru/book.dom.php" target="_blank"><?php echo Loc::getMessage('EPUBLISH_MAGICSITE_INSTALL_CHECK_PHP_EXTENSION_DOM_TITLE'); ?></a>
        </td>
        <td valign="top">Установлен</td>
        <td valign="top">
          <?php if (extension_loaded('dom')) : ?>
            <script language="JavaScript">document.getElementById('error-dom').value = "N"</script>
            <b><span style="color:green"><?php echo Loc::getMessage('EPUBLISH_MAGICSITE_INSTALL_CHECK_PHP_EXTENSION_LOADED'); ?></span></b>
          <?php else : ?>
            <b><span style="color:green"><?php echo Loc::getMessage('EPUBLISH_MAGICSITE_INSTALL_CHECK_PHP_EXTENSION_UNLOADED'); ?></span></b>
          <?php endif; ?>
        </td>
      </tr>
      <tr>
        <td colspan="3"><b>Разделы сайта:</b></td>
      </tr>
      <?php $sections = epublish_magicsite::getSections(); ?>
      <?php foreach ($sections as $section ) : ?>
        <tr>
          <td valign="top"><?php echo $section['title']; ?> (<b>/<?php echo $section['path']; ?>/</b>)</td>
          <td valign="top"><?php echo Loc::getMessage('EPUBLISH_MAGICSITE_INSTALL_CHECK_SESCTION_NOT_EXISTS'); ?></td>
          <td valign="top">
          <?php if (file_exists($_SERVER["DOCUMENT_ROOT"].'/'.$section['path'].'/index.php')) :
            $warnings['sections'][] = $section['path']; ?>
            <b><span style="color:brown"><?php echo Loc::getMessage('EPUBLISH_MAGICSITE_INSTALL_CHECK_SESCTION_EXISTS'); ?></span></b>
          <?php else : ?>
            <b><span style="color:green"><?php echo Loc::getMessage('EPUBLISH_MAGICSITE_INSTALL_CHECK_SESCTION_NOT_EXISTS'); ?></span></b>
          <?php endif; ?>
          </td>
        </tr>
        <?php if (isset($section['below'])) : ?>
          <?php foreach ($section['below'] as $subsection ) : ?>
            <tr>
              <td valign="top"><span><?php echo $subsection['title']; ?> (<b>/<?php echo $subsection['path']; ?>/</b>)</span></td>
              <td valign="top"><?php echo Loc::getMessage('EPUBLISH_MAGICSITE_INSTALL_CHECK_SESCTION_NOT_EXISTS'); ?></td>
              <td valign="top">
              <?php if (file_exists($_SERVER["DOCUMENT_ROOT"].'/'.$subsection['path'].'/index.php')) :
                $warnings['sections'][] = $section['path']; ?>
                <b><span style="color:brown"><?php echo Loc::getMessage('EPUBLISH_MAGICSITE_INSTALL_CHECK_SESCTION_EXISTS'); ?></span></b>
              <?php else : ?>
                <b><span style="color:green"><?php echo Loc::getMessage('EPUBLISH_MAGICSITE_INSTALL_CHECK_SESCTION_NOT_EXISTS'); ?></span></b>
              <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      <?php endforeach; ?>
    </table>
    <?php if (count($warnings)) : ?>
      <p>
        <input type="checkbox" name="overwrite_sections" value="Y" id="overwrite-sections" onclick="ChangeInstallPublic();">&nbsp;<label for="overwrite-sections"><?php echo Loc::getMessage('EPUBLISH_MAGICSITE_INSTALL_OVERWRITE_SESCTION'); ?></label><br />
      </p>
      <br>
    <?php endif; ?>

    <table class="data-table">
      <tr>
        <td width="0%">
          <font color="green"><?php echo Loc::getMessage('EPUBLISH_MAGICSITE_INSTALL_LEGEND_GREEN'); ?><br></font>
          <font color="red"><?php echo Loc::getMessage('EPUBLISH_MAGICSITE_INSTALL_LEGEND_RED'); ?><br></font>
          <font color="brown"><?php echo Loc::getMessage('EPUBLISH_MAGICSITE_INSTALL_LEGEND_BROWN'); ?></font>
        </td>
      </tr>
    </table>
    <p>
      <input type="checkbox" name="top_menu" value="Y" id="top-menu" checked>&nbsp;<label for="top-menu"><?php echo Loc::getMessage('EPUBLISH_MAGICSITE_INSTALL_TOP_MENU'); ?></label><br />
      <sub><?php echo Loc::getMessage('EPUBLISH_MAGICSITE_INSTALL_TOP_MENU_DESC'); ?></sub>
    </p>
    <p>
      <input type="checkbox" name="left_menu" value="Y" id="left-menu" checked>&nbsp;<label for="left-menu"><?php echo Loc::getMessage('EPUBLISH_MAGICSITE_INSTALL_LEFT_MENU'); ?></label><br />
      <sub><?php echo Loc::getMessage('EPUBLISH_MAGICSITE_INSTALL_LEFT_MENU_DESC'); ?></sub>
    </p>
    <br>

    <input type="submit" name="inst" id="inst" value="<?= GetMessage("MOD_INSTALL")?>">
  </div>
</form>
<style>
.inst-cont-title {
    background: #e7efc2;
    border: 1px solid;
    border-color: #d3e1c1 #cbdab4 #c5d4a7;
    border-radius: 3px;
    -webkit-box-shadow: 0 1px 0 #fff;
    box-shadow: inset 0 1px 0 #fff;
    color: #000;
    font-size: 16px;
    font-weight: bold;
    line-height: 37px;
    height: 37px;
    padding: 0 17px;
    text-shadow: 0 1px rgb(255 255 255 / 90%);
}

.inst-cont-title {
    background: #e7efc2;
    border: 1px solid;
    border-color: #d3e1c1 #cbdab4 #c5d4a7;
    border-radius: 3px;
    -webkit-box-shadow: 0 1px 0 #fff;
    box-shadow: inset 0 1px 0 #fff;
    color: #000;
    font-size: 16px;
    font-weight: bold;
    line-height: 37px;
    height: 37px;
    padding: 0 17px;
    text-shadow: 0 1px rgb(255 255 255 / 90%);
}

.step-content {
  background-color: #ffffff;
  color: #737373;
  text-align: left;
  vertical-align: top;
  padding: 25px 35px 25px 25px;
}

.data-table{
  border:1px solid #e7eff2;
  border-bottom:none;
  border-radius:5px;
  border-spacing:0;
  margin-bottom:20px;
  width: 100%;
}
.data-table th,
.data-table thead tr td,
.data-table td.header{
  background: #e3ebee;
  background:-webkit-linear-gradient(top, #e0eaec, #e5ecef);
  background:-moz-linear-gradient(top, #e0eaec, #e5ecef);
  background:-ms-linear-gradient(top, #e0eaec, #e5ecef);
  background:-o-linear-gradient(top, #e0eaec, #e5ecef);
  background:linear-gradient(to bottom, #e0eaec, #e5ecef);
  border-color: #f1f1f1 #e5e5e5 #bac0c3;
  box-shadow: inset 0 1px 0 #fff, inset 0 0 0 1px rgba(255,255,255,.3), 0 1px 0 #eaeded;
  color: #000;
  font-size: 14px;
  font-weight: bold;
  line-height: 39px;
  margin: 0 -1px 16px;
  overflow: hidden;
  text-overflow: ellipsis;
  padding: 0 17px;
  white-space: nowrap;
}
.data-table td{
  background:#fafcfd;
  border-bottom:1px solid #e6eaeb;
  color: #000;
  text-shadow:0 1px 1px #fff;
  font-size:14px;
  padding:10px 10px 10px 17px;
}

.data-table td span{
  padding-left: 40px;
}

.data-table-multiple-column th td,
.data-table-multiple-column thead tr td,
.data-table-multiple-column td.header {border-left: 1px solid #d3dddf;}

.data-table-multiple-column td {border-left: 1px solid #e6eaeb;}
.data-table-multiple-column td:first-child {border-left: none;}

.data-table td small {display: inline-block; padding-top: 5px;}

.data-table tr:last-child td:first-child{border-bottom-left-radius:5px;}

.data-table tr:last-child td:last-child{border-bottom-right-radius:5px;}

.data-table tr:first-child td:first-child{border-top-left-radius:5px; margin-top: -1px;}

.data-table tr:first-child td:last-child{border-top-right-radius:5px; margin-top: -1px;}

.data-table input[type="text"],
.data-table input[type="password"] {
  background: #fff;
  border: 1px solid;
  border-color: #c8ced3 #ccd2d7 #d2d8dc;
  border-radius: 3px;
  -webkit-box-shadow: inset 0 1px 0 #edf0f1, inset 0 2px 0 #f9fafb;
  box-shadow: inset 0 1px 0 #edf0f1, inset 0 2px 0 #f9fafb;
  font-size: 14px;
  font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
  height: 29px;
  outline: none;
  padding: 0 5px;
  width: 290px;
}

.data-table select {
  font-size: 14px;
  font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
  height: 29px;
  outline: none;
  padding: 5px 3px;
  width: 290px;
}


.data-table input[type="radio"]{
  margin-left: 0;
}
.data-table input[type="checkbox"]{
  margin-right: 8px;
}

.data-table input[type="checkbox"]+label {
  display: inline-block;
  padding-top: 1px;
  vertical-align: top;
}
</style>
