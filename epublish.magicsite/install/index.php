<?php

use \Bitrix\Main\Loader;
use \Bitrix\Main\Application;
use \Bitrix\Main\Entity\Base;
use \Bitrix\Main\Config\Option;
use \Bitrix\Main\Config as Conf;
use \Bitrix\Main\Localization\Loc;
use \Epublish\Magicsite\Integration;

Loc::loadMessages(__FILE__);

Class epublish_magicsite extends CModule
{
  var $exclusionAdminFiles;
  var $errors = null;

  function __construct()
  {
    $arModuleVersion = array();
    include(__DIR__."/version.php");

    $this->MODULE_ID = 'epublish.magicsite';
    $this->MODULE_VERSION = $arModuleVersion["VERSION"];
    $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
    $this->MODULE_NAME = Loc::getMessage("EPUBLISH_MAGICSITE_MODULE_NAME");
    $this->MODULE_DESCRIPTION = Loc::getMessage("EPUBLISH_MAGICSITE_MODULE_DESC");

    $this->PARTNER_NAME = Loc::getMessage("EPUBLISH_MAGICSITE_PARTNER_NAME");
    $this->PARTNER_URI = Loc::getMessage("EPUBLISH_MAGICSITE_PARTNER_URI");

    $this->MODULE_SORT = 1;
    $this->SHOW_SUPER_ADMIN_GROUP_RIGHTS='Y';
    $this->MODULE_GROUP_RIGHTS = "Y";
  }

  function DoInstall()
  {
    global $APPLICATION, $step, $top_menu, $left_menu;
    $step = intval($step);

    if ($step < 2) {
      $APPLICATION->IncludeAdminFile(GetMessage("EPUBLISH_MAGICSITE_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/epublish.magicsite/install/step1.php");
    } elseif ($step==2) {
      \Bitrix\Main\ModuleManager::registerModule($this->MODULE_ID);

      if (isset($top_menu) && $top_menu == "Y") {
        $arParams['top_menu'] = true;
      }

      if (isset($left_menu) && $left_menu == "Y") {
        $arParams['left_menu'] = true;
      }

      $this->InstallFiles($arParams);

      $GLOBALS["errors"] = $this->errors;

      $APPLICATION->IncludeAdminFile(GetMessage("EPUBLISH_MAGICSITE_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/epublish.magicsite/install/step2.php");
    }
  }

  function DoUninstall()
  {
    global $APPLICATION;

    $context = Application::getInstance()->getContext();
    $request = $context->getRequest();

    $this->UnInstallFiles();
    $this->UnInstallDB();

    \Bitrix\Main\ModuleManager::unRegisterModule($this->MODULE_ID);
  }

  public function GetPath($notDocumentRoot=false)
  {
    if ($notDocumentRoot) {
      return str_ireplace(Application::getDocumentRoot(),'',dirname(__DIR__));
    } else {
      return dirname(__DIR__);
    }
  }

  public function isVersionD7()
  {
    return CheckVersion(\Bitrix\Main\ModuleManager::getVersion('main'), '14.00.00');
  }

  function InstallFiles($arParams = array())
  {
    $path=$this->GetPath()."/install/js";

    if (\Bitrix\Main\IO\Directory::isDirectoryExists($path)) {
      CopyDirFiles($path, $_SERVER["DOCUMENT_ROOT"]."/bitrix/js", true, true);
    } else {
      throw new \Bitrix\Main\IO\InvalidPathException($path);
    }

    Loader::includeModule("epublish.magicsite");
    $arMenu = Integration::getMenu();

    foreach ($arMenu as $arItem) {
      $this->createPage($arItem);
      if (isset($arItem['below'])) {
        foreach ($arItem['below'] as $arSubItem) {
          $this->createPage($arSubItem);
        }
        $this->createMenu('.left.menu', $arItem);
      }
    }

    if(isset($arParams['top_menu'])) {
      $this->createMenu('.top.menu',
        array(
          'path'  => '/',
          'title' => Loc::getMessage('EPUBLISH_MAGICSITE_TOP_MENU_TITLE'),
          'below' => $arMenu,
        ),
      );
    }

    if(isset($arParams['left_menu'])) {
      $this->createMenu('.left.menu',
        array(
          'path'  => '/',
          'title' => Loc::getMessage('EPUBLISH_MAGICSITE_TOP_MENU_TITLE'),
          'below' => $arMenu,
        ),
      );
    }

  }

  function UnInstallFiles()
  {
    Loader::includeModule("epublish.magicsite");

    $arMenu = Integration::getMenu();

    foreach ($arMenu as $arItem) {
      if (isset($arItem['below'])) {
        foreach ($arItem['below'] as $arSubItem) {
          if (\Bitrix\Main\IO\Directory::isDirectoryExists($_SERVER["DOCUMENT_ROOT"]."/".$arSubItem['path']."/")) {
            \Bitrix\Main\IO\Directory::deleteDirectory($_SERVER["DOCUMENT_ROOT"]."/".$arSubItem['path']."/");
          }
        }
      }
      if (\Bitrix\Main\IO\Directory::isDirectoryExists($_SERVER["DOCUMENT_ROOT"]."/".$arItem['path']."/")) {
        \Bitrix\Main\IO\Directory::deleteDirectory($_SERVER["DOCUMENT_ROOT"]."/".$arItem['path']."/");
      }
    }

    if (\Bitrix\Main\IO\Directory::isDirectoryExists($_SERVER["DOCUMENT_ROOT"]."/bitrix/js/epublish.magicsite")) {
      \Bitrix\Main\IO\Directory::deleteDirectory($_SERVER["DOCUMENT_ROOT"]."/bitrix/js/epublish.magicsite");
    }

    $this->clearMenu('.top.menu',
      array(
        'path'  => '/',
        'title' => Loc::getMessage('EPUBLISH_MAGICSITE_TOP_MENU_TITLE'),
        'below' => $arMenu,
      )
    );
    $this->clearMenu('.left.menu',
      array(
        'path'  => '/',
        'title' => Loc::getMessage('EPUBLISH_MAGICSITE_TOP_MENU_TITLE'),
        'below' => $arMenu,
      )
    );

    return true;
  }

  function UnInstallDB()
  {
    Option::delete($this->MODULE_ID);
  }

  function createPage($arItem) {
    $section = "<?php\n";
    $section .= '$sSectionName = "'.$arItem['title'].'";'."\n";
    $section .= '$arDirProperties = Array();'."\n";

    if ($arItem['type'] == 'menu_section') {
      $index = "<?php\n\n";
      $index .= "use \Bitrix\Main\Loader;\n";
      $index .= "use \Bitrix\Main\Config\Option;\n\n";
      $index .= 'require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");'."\n";
      $index .= '$APPLICATION->SetTitle("'.$arItem['title'].'");'."\n";
      $index .= 'if ($url = Option::get("epublish.magicsite", "magicsite_url")) : ?>'."\n";
      foreach ($arItem['below'] as $subPath => $subItem ) {
        $index .= '  <div calss="magicsite-menu-item"><a href="/'.$subItem['path'].'/">'.$subItem['title']."</a></div>\n";
      }
      $index .= "<?php else : ?>\n";
      $index .= "  <div>Ошибка настройки модуля интеграции с MagicSite</div>\n";
      $index .= "<?php endif;\n";
      $index .= 'require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");'."\n";
    } else {
      $index = "<?php\n";
      $index .= 'require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");'."\n";
      $index .= '$APPLICATION->SetTitle("'.$arItem['title'].'");'."\n";
      $index .= "CJSCore::Init(array('jquery2', 'popup'));\n";
      $index .= "\Bitrix\Main\Loader::includeModule('epublish.magicsite');\n";
      $index .= 'echo \Epublish\Magicsite\Integration::getContent("'.$arItem['section'].'", "'.$arItem['page'].'");'."\n";
      $index .= 'require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");'."\n";
    }
    $path = $_SERVER["DOCUMENT_ROOT"].'/'.$arItem['path'];

    if(!file_exists($path)) {
      mkdir($path, 0755, true);
    }

    file_put_contents($path.'/.section.php', $section);
    file_put_contents($path.'/index.php', $index);
  }

  function createMenu($menuName, $arMenu) {
    $aMenuLinks = array();
    $aLinksAdd = array();
    if (file_exists($_SERVER["DOCUMENT_ROOT"].'/'.$arMenu['path'].'/'.$menuName.'.php')) {
      include_once($_SERVER["DOCUMENT_ROOT"].'/'.$arMenu['path'].'/'.$menuName.'.php');
    }

    foreach ($arMenu['below'] as $arItem) {
      $aLinksAdd[] = array(
        $arItem['title'],
        '/'.$arItem['path'].'/',
        array(),
        array(),
        '',
      );
    }

    $diff = array_diff(array_map('json_encode', $aMenuLinks), array_map('json_encode', $aLinksAdd));
    $diff = array_map('json_decode', $diff);

    $aMenuLinks = array_merge($diff, $aLinksAdd);

    $menu = "<?php\n";
    $menu .= '$aMenuLinks = array('."\n";
    foreach ($aMenuLinks as $arItem) {
      $menu .= "  array(\n";
      foreach ($arItem as $item) {
        if (is_string($item)) {
          $menu .= "    '".$item."',\n";
        } elseif (is_array($item)) {
          if (count($item)) {
            $menu .= "    array(\n";
            foreach ($item as $el) {
              $menu .= "      '".$el."',\n";
            }
            $menu .= "    ),\n";
          } else {
            $menu .= "    array(),\n";
          }
        }
      }
      $menu .= "  ),\n";
    }
    $menu .= ");\n";
    file_put_contents($_SERVER["DOCUMENT_ROOT"].'/'.$arMenu['path'].'/'.$menuName.'.php', $menu);
  }

  function clearMenu($menuName, $arMenu) {
    $aMenuLinks = array();
    $aLinksRemove = array();
    if (file_exists($_SERVER["DOCUMENT_ROOT"].'/'.$arMenu['path'].'/'.$menuName.'.php')) {
      include_once($_SERVER["DOCUMENT_ROOT"].'/'.$arMenu['path'].'/'.$menuName.'.php');
    }

    foreach ($arMenu['below'] as $arItem) {
      $aLinksRemove[] = array(
        $arItem['title'],
        '/'.$arItem['path'].'/',
        array(),
        array(),
        '',
      );
    }

    $diff = array_diff(array_map('json_encode', $aMenuLinks), array_map('json_encode', $aLinksRemove));
    $diff = array_map('json_decode', $diff);

    if (count($diff)) {
      $menu = "<?php\n";
      $menu .= '$aMenuLinks = array('."\n";
      foreach ($diff as $arItem) {
        $menu .= "  array(\n";
        foreach ($arItem as $item) {
          if (is_string($item)) {
            $menu .= "    '".$item."',\n";
          } elseif (is_array($item)) {
            if (count($item)) {
              $menu .= "    array(\n";
              foreach ($item as $el) {
                $menu .= "      '".$el."',\n";
              }
              $menu .= "    ),\n";
            } else {
              $menu .= "    array(),\n";
            }
          }
        }
        $menu .= "  ),\n";
      }
      $menu .= ");\n";
      file_put_contents($_SERVER["DOCUMENT_ROOT"].'/'.$arMenu['path'].'/'.$menuName.'.php', $menu);
    } else {
      unlink($_SERVER["DOCUMENT_ROOT"].'/'.$arMenu['path'].'/'.$menuName.'.php');
    }
  }

  function getSections() {
    return array(
      'sveden' => array(
        'path'    => 'sveden',
        'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SECTION_SVEDEN_TITLE'),
        'below'   => array(
          'common' => array(
            'path'    => 'sveden/common',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SECTION_SVEDEN_COMMON_TITLE'),
          ),
          'struct' => array(
            'path'    => 'sveden/struct',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SECTION_SVEDEN_STRUCT_TITLE'),
          ),
          'document' => array(
            'path'    => 'sveden/document',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SECTION_SVEDEN_DOCUMENT_TITLE'),
          ),
          'education' => array(
            'path'    => 'sveden/education',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SECTION_SVEDEN_EDUCATION_TITLE'),
          ),
          'edustandarts' => array(
            'path'    => 'sveden/edustandarts',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SECTION_SVEDEN_EDUSTANDARTS_TITLE'),
          ),
          'employees' => array(
            'path'    => 'sveden/employees',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SECTION_SVEDEN_EMPLOYEES_TITLE'),
          ),
          'objects' => array(
            'path'    => 'sveden/objects',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SECTION_SVEDEN_OBJECTS_TITLE'),
          ),
          'grants' => array(
            'path'    => 'sveden/grants',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SECTION_SVEDEN_GRANTS_TITLE'),
          ),
          'paid_edu' => array(
            'path'    => 'sveden/strupaid_educt',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SECTION_SVEDEN_PAID_EDU_TITLE'),
          ),
          'budget' => array(
            'path'    => 'sveden/budget',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SECTION_SVEDEN_BUDGET_TITLE'),
          ),
          'vacant' => array(
            'path'    => 'sveden/vacant',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SECTION_SVEDEN_VACANT_TITLE'),
          ),
          'ovz' => array(
            'path'    => 'sveden/ovz',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SECTION_SVEDEN_OVZ_TITLE'),
          ),
          'inter' => array(
            'path'    => 'sveden/inter',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SECTION_SVEDEN_INTER_TITLE'),
          ),
        ),
      ),
      'infosec' => array(
        'path'  => 'infosec',
        'title' => Loc::getMessage('EPUBLISH_MAGICSITE_SECTION_INFOSEC_TITLE'),
        'below' => array(
          'common'   => array(
            'path'    => 'infosec/common',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SECTION_INFOSEC_COMMON_TITLE'),
          ),
          'normreg'  => array(
            'path'    => 'infosec/normreg',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SECTION_INFOSEC_NORMREG_TITLE'),
          ),
          'educator' => array(
            'path'    => 'infosec/educator',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SECTION_INFOSEC_EDUCATOR_TITLE'),
          ),
          'students' => array(
            'path'    => 'infosec/students',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SECTION_INFOSEC_STUDENTS_TITLE'),
          ),
          'parents'  => array(
            'path'    => 'infosec/parents',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SECTION_INFOSEC_PARENTS_TITLE'),
          ),
          'sites' => array(
            'path'    => 'infosec/sites',
            'title'    => Loc::getMessage('EPUBLISH_MAGICSITE_SECTION_INFOSEC_SITES_TITLE'),
          ),
        ),
      ),
      'anticorruption' => array(
        'path'  => 'anticorruption',
        'title' => Loc::getMessage('EPUBLISH_MAGICSITE_SECTION_ANTICORRUPTION_TITLE'),
        'below' => array(
          'normativnieacti' => array(
            'path'    => 'anticorruption/normativnieacti',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SECTION_ANTICORRUPTION_NORMACTY_TITLE'),
          ),
          'expertise' => array(
            'path'    => 'anticorruption/expertise',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SECTION_ANTICORRUPTION_EXPERTISE_TITLE'),
          ),
          'iniemetodmaterialy' => array(
            'path'    => 'anticorruption/iniemetodmaterialy',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SECTION_ANTICORRUPTION_MATERIALS_TITLE'),
          ),
          'forms' => array(
            'path'    => 'anticorruption/forms',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SECTION_ANTICORRUPTION_FORMS_TITLE'),
          ),
          'svedenodohodah' => array(
            'path'    => 'anticorruption/svedenodohodah',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SECTION_ANTICORRUPTION_DOHODY_TITLE'),
          ),
          'commission' => array(
            'path'    => 'anticorruption/commission',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SECTION_ANTICORRUPTION_COMMISSION_TITLE'),
          ),
          'feedback' => array(
            'path'    => 'anticorruption/feedback',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SECTION_ANTICORRUPTION_FEEDBACK_TITLE'),
          ),
          'responsibility' => array(
            'path'    => 'anticorruption/responsibility',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SECTION_ANTICORRUPTION_RESPONSIBILITY_TITLE'),
          ),
          'infomaterial' => array(
            'path'    => 'anticorruption/infomaterial',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SECTION_ANTICORRUPTION_INFORM_TITLE'),
          ),
        ),
      ),
      'qualityassessment' => array(
        'path'    => 'qualityassessment',
        'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SECTION_QUALITY_TITLE'),
      ),
      'distance_education' => array(
        'path'    => 'distance_education',
        'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SECTION_SHEDULE_TITLE'),
      ),
      'educative' => array(
        'path'  => 'educative',
        'title' => Loc::getMessage('EPUBLISH_MAGICSITE_SECTION_EDUCATIVE_TITLE'),
        'below' => array(
          'edwpartdo' => array(
            'path'    => 'educative/edwpartdo',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SECTION_EDUCATIVE_DO_TITLE'),
          ),
          'edwpartnoo' => array(
            'path'    => 'educative/edwpartnoo',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SECTION_EDUCATIVE_NOO_TITLE'),
          ),
          'edwpartooo' => array(
            'path'    => 'educative/edwpartooo',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SECTION_EDUCATIVE_OOO_TITLE'),
          ),
          'edwpartsoo' => array(
            'path'    => 'educative/edwpartsoo',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SECTION_EDUCATIVE_SOO_TITLE'),
          ),
          'edwanaliz' => array(
            'path'    => 'educative/edwanaliz',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SECTION_EDUCATIVE_ANALISE_TITLE'),
          ),
          'edwinfo' => array(
            'path'    => 'educative/edwinfo',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SECTION_EDUCATIVE_INFO_TITLE'),
          ),
          'edwevents' => array(
            'path'    => 'educative/edwevents',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SECTION_EDUCATIVE_EVENTS_TITLE'),
          ),
        ),
      ),
      'gia' => array(
        'path'    => 'gia',
        'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SECTION_GIA_TITLE'),
      ),
      'meals' => array(
        'path' => 'meals',
        'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SECTION_MEALS_TITLE'),
        'below'   => array(
          'meals' => array(
            'path'    => 'meals/meals',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SECTION_MEALS_TITLE'),
          ),
          'index' => array(
            'path'    => 'food',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SECTION_FOOD_TITLE'),
          ),
        ),
      ),
      'labor_protection' => array(
        'path'    => 'labor_protection',
        'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SECTION_LABOR_PROTECTION_TITLE'),
      ),
      'accounting_policy' => array(
        'path'    => 'accounting_policy',
        'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SECTION_ACCOUNTING_POLICY_TITLE'),
      ),
    );
  }
}
