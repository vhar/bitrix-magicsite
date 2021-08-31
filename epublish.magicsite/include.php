<?php
\Bitrix\Main\Loader::registerAutoLoadClasses(
  "epublish.magicsite",
  array(
    'Epublish\\Magicsite\\Integration' => 'lib/integration.php',
  )
);
/*
$arJsConfig = array(
    'custom_main' => array(
        'js' => '/bitrix/js/custom/main.js',
        'css' => '/bitrix/js/custom/main.css',
        'rel' => array(),
    )
);

foreach ($arJsConfig as $ext => $arExt) {
    \CJSCore::RegisterExt($ext, $arExt);
}

//CUtil::InitJSCore(array('custom_main'));
*/
