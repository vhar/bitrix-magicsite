<?php

namespace Epublish\Magicsite;

use \Bitrix\Main\Page\Asset;
use \Bitrix\Main\Config\Option;
use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Integration
{
  private function getPage( $uri, $header = FALSE ) {
    $response = [];
    $url = parse_url( $uri );
    $curlInit = curl_init( $uri );
    curl_setopt( $curlInit, CURLOPT_CONNECTTIMEOUT, 20 );
    if ( $header ) {
      curl_setopt( $curlInit, CURLOPT_HEADER, true );
      curl_setopt( $curlInit, CURLOPT_NOBODY, true );
    }
    if ( $url['scheme'] == 'https' ) {
      curl_setopt( $curlInit, CURLOPT_SSL_VERIFYHOST  , 2 );
    }
    curl_setopt( $curlInit, CURLOPT_SSL_VERIFYPEER, false );
    curl_setopt( $curlInit, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $curlInit, CURLOPT_FOLLOWLOCATION, true );
    curl_setopt( $curlInit, CURLOPT_COOKIEJAR, '-' );
    curl_setopt( $curlInit, CURLOPT_REFERER, $_SERVER['SERVER_NAME'] );
    curl_setopt( $curlInit, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64)" );
    $response['response']      = curl_exec( $curlInit );
    $response['effective_url'] = curl_getinfo( $curlInit, CURLINFO_EFFECTIVE_URL );
    $response['response_code'] = intval(curl_getinfo( $curlInit, CURLINFO_HTTP_CODE ) );
    curl_close( $curlInit );
    return $response;
  }

  public static function getUrl( $uri ) {
    $uri = trim( $uri );
    preg_match( '/^(https?:\/\/)?/', $uri, $proto );
    if ( ! isset( $proto[1] ) ) {
      $uri = "http://" . $uri;
    }
    $url = parse_url( $uri );
    if ( ! isset( $url['host'] ) ) {
      return FALSE;
    }
    if ( checkdnsrr( $url['host'], "A" ) && gethostbyname( $url['host'] ) == gethostbyname( 'edusite.ru' ) ) {
      $output = $url['scheme'] . '://';
      $output .= $url['host'];
      if ( isset( $url['port'] ) ) {
        $output .= ':' . $url['port'];
      }
      if ( isset( $url['path'] ) ) {
        $output .= $url['path'];
      }
      $res = Integration::getPage( $output, 1 );

      if ( $res['response_code'] == 200 ) {
        return $string = rtrim($res['effective_url'], '/');
      }
    }
    return FALSE;
  }

  public function getContent($section, $page) {
    if ($url = Option::get("epublish.magicsite", "magicsite_url")) {
      $response = Integration::getPage($url . '/' . $section . '/' . $page . '.html' );
      if ( $response['response_code'] == 200 ) {
        Asset::getInstance()->addCss('https://js.edusite.ru/mmagicutf.css');
        Asset::getInstance()->addCss('https://js.edusite.ru/jquery.fancybox.min.css');
        Asset::getInstance()->addJs('https://js.edusite.ru/jquery.fancybox.min.js');
        Asset::getInstance()->addJs('https://api-maps.yandex.ru/2.1/?lang=ru_RU');

        $magicsite_content = '<div id="ajax-show-sign"></div>';

        $dom = new \DOMDocument();
        $dom->preserveWhiteSpace = false;
        libxml_use_internal_errors( true );
        $dom->loadHTML( $response['response'] );
        $xpath = new \DOMXPath( $dom );

        $ls_ads = $xpath->query( '//a' );
        foreach ( $ls_ads as $ad ) {
          if ( $ad->hasAttribute( 'href' ) ) {
            $ad_url = $ad->getAttribute( 'href' );
            $f = parse_url( $ad_url );
            if ( ! isset( $f['scheme'] ) && ! isset( $f['host'] ) && isset( $f['path'] ) ) {
              $ad->setAttribute( 'href', $url . '/' . $f['path'] );
            }
          }
        }

        $images = $dom->getElementsByTagName( 'img' );
        foreach ( $images as $image ) {
          $src = $image->getAttribute( 'src' );
          $f = parse_url( $src );
          if ( ! isset( $f['scheme'] ) && ! isset( $f['host'] ) && isset( $f['path'] ) ) {
            $image->setAttribute( 'src', $url .'/' . $f['path'] );
          }
        }

        $sections = $xpath->query( "//*[contains(@class, 'inner-page-block')]" );
        foreach ( $sections as $section ) {
          $magicsite_content .= $dom->saveHTML( $section );
        }
        libxml_clear_errors();
        $magicsite_content = str_replace( "\n", "", $magicsite_content );

        $sections = $xpath->query( "//*[contains(@class, '" . $post->post_name . "-page-script')]" );
        $magic_script ='';
        foreach ( $sections as $section ) {
          $magic_script .= $dom->saveHTML( $section );
        }
        libxml_clear_errors();

        Asset::getInstance()->addString($magic_script);
        Asset::getInstance()->addString('<script type="text/javascript">var magicsite_url = "' . $url . '"</script>');
        Asset::getInstance()->addString('<script type="text/javascript" src="/bitrix/js/epublish.magicsite/integration.js" />');
      } else {
        $magicsite_content = '<div>'.Loc::getMessage('EPUBLISH_MAGICSITE_ERROR_CODE').' '.$response['response_code'] ?? '0' . '</div>';
      }
    } else {
      $magicsite_content = '<div>'.Loc::getMessage('EPUBLISH_MAGICSITE_SETTINGS_ERROR').'</div>';
    }
    return $magicsite_content ?? '';
  }

  public function getMenu() {
    return array(
      'sveden' => array(
        'type'    => 'menu_section',
        'path'    => 'sveden',
        'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SVEDEN_TITLE'),
        'below'   => array(
          'common' => array(
            'type'    => 'menu_item',
            'path'    => 'sveden/common',
            'section' => 'sveden',
            'page'    => 'common',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SVEDEN_COMMON_TITLE'),
          ),
          'struct' => array(
            'type'    => 'menu_item',
            'path'    => 'sveden/struct',
            'section' => 'sveden',
            'page'    => 'struct',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SVEDEN_STRUCT_TITLE'),
          ),
          'document' => array(
            'type'    => 'menu_item',
            'path'    => 'sveden/document',
            'section' => 'sveden',
            'page'    => 'document',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SVEDEN_DOCUMENT_TITLE'),
          ),
          'education' => array(
            'type'    => 'menu_item',
            'path'    => 'sveden/education',
            'section' => 'sveden',
            'page'    => 'education',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SVEDEN_EDUCATION_TITLE'),
          ),
          'edustandarts' => array(
            'type'    => 'menu_item',
            'path'    => 'sveden/edustandarts',
            'section' => 'sveden',
            'page'    => 'edustandarts',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SVEDEN_EDUSTANDARTS_TITLE'),
          ),
          'employees' => array(
            'type'    => 'menu_item',
            'path'    => 'sveden/employees',
            'section' => 'sveden',
            'page'    => 'employees',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SVEDEN_EMPLOYEES_TITLE'),
          ),
          'objects' => array(
            'type'    => 'menu_item',
            'path'    => 'sveden/objects',
            'section' => 'sveden',
            'page'    => 'objects',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SVEDEN_OBJECTS_TITLE'),
          ),
          'grants' => array(
            'type'    => 'menu_item',
            'path'    => 'sveden/grants',
            'section' => 'sveden',
            'page'    => 'grants',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SVEDEN_GRANTS_TITLE'),
          ),
          'paid_edu' => array(
            'type'    => 'menu_item',
            'path'    => 'sveden/strupaid_educt',
            'section' => 'sveden',
            'page'    => 'strupaid_educt',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SVEDEN_PAID_EDU_TITLE'),
          ),
          'budget' => array(
            'type'    => 'menu_item',
            'path'    => 'sveden/budget',
            'section' => 'sveden',
            'page'    => 'budget',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SVEDEN_BUDGET_TITLE'),
          ),
          'vacant' => array(
            'type'    => 'menu_item',
            'path'    => 'sveden/vacant',
            'section' => 'sveden',
            'page'    => 'vacant',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SVEDEN_VACANT_TITLE'),
          ),
          'ovz' => array(
            'type'    => 'menu_item',
            'path'    => 'sveden/ovz',
            'section' => 'sveden',
            'page'    => 'ovz',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SVEDEN_OVZ_TITLE'),
          ),
          'inter' => array(
            'type'    => 'menu_item',
            'path'    => 'sveden/inter',
            'section' => 'sveden',
            'page'    => 'inter',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SVEDEN_INTER_TITLE'),
          ),
        ),
      ),
      'infosec' => array(
        'type'  => 'menu_section',
        'path'  => 'infosec',
        'title' => Loc::getMessage('EPUBLISH_MAGICSITE_INFOSEC_TITLE'),
        'below' => array(
          'common'   => array(
            'type'    => 'menu_item',
            'path'    => 'infosec/common',
            'section' => 'infosec',
            'page'    => 'common',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_INFOSEC_COMMON_TITLE'),
          ),
          'normreg'  => array(
            'type'    => 'menu_item',
            'path'    => 'infosec/normreg',
            'section' => 'infosec',
            'page'    => 'normreg',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_INFOSEC_NORMREG_TITLE'),
          ),
          'educator' => array(
            'type'    => 'menu_item',
            'path'    => 'infosec/educator',
            'section' => 'infosec',
            'page'    => 'educator',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_INFOSEC_EDUCATOR_TITLE'),
          ),
          'students' => array(
            'type'    => 'menu_item',
            'path'    => 'infosec/students',
            'section' => 'infosec',
            'page'    => 'students',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_INFOSEC_STUDENTS_TITLE'),
          ),
          'parents'  => array(
            'type'    => 'menu_item',
            'path'    => 'infosec/parents',
            'section' => 'infosec',
            'page'    => 'parents',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_INFOSEC_PARENTS_TITLE'),
          ),
          'sites' => array(
            'type'    => 'menu_item',
            'path'    => 'infosec/sites',
            'section' => 'infosec',
            'page'    => 'sites',
            'title'    => Loc::getMessage('EPUBLISH_MAGICSITE_INFOSEC_SITES_TITLE'),
          ),
        ),
      ),
      'anticorruption' => array(
        'type'  => 'menu_section',
        'path'  => 'anticorruption',
        'title' => Loc::getMessage('EPUBLISH_MAGICSITE_ANTICORRUPTION_TITLE'),
        'below' => array(
          'normativnieacti' => array(
            'type'    => 'menu_item',
            'path'    => 'anticorruption/normativnieacti',
            'section' => 'anticorruption',
            'page'    => 'normativnieacti',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_ANTICORRUPTION_NORMACTY_TITLE'),
          ),
          'expertise' => array(
            'type'    => 'menu_item',
            'path'    => 'anticorruption/expertise',
            'section' => 'anticorruption',
            'page'    => 'expertise',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_ANTICORRUPTION_EXPERTISE_TITLE'),
          ),
          'iniemetodmaterialy' => array(
            'type'    => 'menu_item',
            'path'    => 'anticorruption/iniemetodmaterialy',
            'section' => 'anticorruption',
            'page'    => 'iniemetodmaterialy',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_ANTICORRUPTION_MATERIALS_TITLE'),
          ),
          'forms' => array(
            'type'    => 'menu_item',
            'path'    => 'anticorruption/forms',
            'section' => 'anticorruption',
            'page'    => 'forms',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_ANTICORRUPTION_FORMS_TITLE'),
          ),
          'svedenodohodah' => array(
            'type'    => 'menu_item',
            'path'    => 'anticorruption/svedenodohodah',
            'section' => 'anticorruption',
            'page'    => 'svedenodohodah',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_ANTICORRUPTION_DOHODY_TITLE'),
          ),
          'commission' => array(
            'type'    => 'menu_item',
            'path'    => 'anticorruption/commission',
            'section' => 'anticorruption',
            'page'    => 'commission',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_ANTICORRUPTION_COMMISSION_TITLE'),
          ),
          'feedback' => array(
            'type'    => 'menu_item',
            'path'    => 'anticorruption/feedback',
            'section' => 'anticorruption',
            'page'    => 'feedback',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_ANTICORRUPTION_FEEDBACK_TITLE'),
          ),
          'responsibility' => array(
            'type'    => 'menu_item',
            'path'    => 'anticorruption/responsibility',
            'section' => 'anticorruption',
            'page'    => 'responsibility',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_ANTICORRUPTION_RESPONSIBILITY_TITLE'),
          ),
          'infomaterial' => array(
            'type'    => 'menu_item',
            'path'    => 'anticorruption/infomaterial',
            'section' => 'anticorruption',
            'page'    => 'infomaterial',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_ANTICORRUPTION_INFORM_TITLE'),
          ),
        ),
      ),
      'qualityassessment' => array(
        'type'    => 'menu_item',
        'path'    => 'qualityassessment',
        'section' => 'qualityassessment',
        'page'    => 'qualityassessment',
        'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_QUALITY_TITLE'),
      ),
      'distance_education' => array(
        'type'    => 'menu_item',
        'path'    => 'distance_education',
        'section' => 'shedule',
        'page'    => 'distance_education',
        'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_SHEDULE_TITLE'),
      ),
      'educative' => array(
        'type'  => 'menu_section',
        'path'  => 'educative',
        'title' => Loc::getMessage('EPUBLISH_MAGICSITE_EDUCATIVE_TITLE'),
        'below' => array(
          'edwpartdo' => array(
            'type'    => 'menu_item',
            'path'    => 'educative/edwpartdo',
            'section' => 'educative',
            'page'    => 'edwpartdo',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_EDUCATIVE_DO_TITLE'),
          ),
          'edwpartnoo' => array(
            'type'    => 'menu_item',
            'path'    => 'educative/edwpartnoo',
            'section' => 'educative',
            'page'    => 'edwpartnoo',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_EDUCATIVE_NOO_TITLE'),
          ),
          'edwpartooo' => array(
            'type'    => 'menu_item',
            'path'    => 'educative/edwpartooo',
            'section' => 'educative',
            'page'    => 'edwpartooo',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_EDUCATIVE_OOO_TITLE'),
          ),
          'edwpartsoo' => array(
            'type'    => 'menu_item',
            'path'    => 'educative/edwpartsoo',
            'section' => 'educative',
            'page'    => 'edwpartsoo',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_EDUCATIVE_SOO_TITLE'),
          ),
          'edwanaliz' => array(
            'type'    => 'menu_item',
            'path'    => 'educative/edwanaliz',
            'section' => 'educative',
            'page'    => 'edwanaliz',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_EDUCATIVE_ANALISE_TITLE'),
          ),
          'edwinfo' => array(
            'type'    => 'menu_item',
            'path'    => 'educative/edwinfo',
            'section' => 'educative',
            'page'    => 'edwinfo',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_EDUCATIVE_INFO_TITLE'),
          ),
          'edwevents' => array(
            'type'    => 'menu_item',
            'path'    => 'educative/edwevents',
            'section' => 'educative',
            'page'    => 'edwevents',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_EDUCATIVE_EVENTS_TITLE'),
          ),
        ),
      ),
      'gia' => array(
        'type'    => 'menu_item',
        'path'    => 'gia',
        'section' => 'sveden',
        'page'    => 'gia',
        'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_GIA_TITLE'),
      ),
      'meals' => array(
        'type'    => 'menu_section',
        'path' => 'meals',
        'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_MEALS_TITLE'),
        'below'   => array(
          'meals' => array(
            'type'    => 'menu_item',
            'path'    => 'meals/meals',
            'section' => 'sveden',
            'page'    => 'meals',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_MEALS_TITLE'),
          ),
          'index' => array(
            'type'    => 'menu_item',
            'path'    => 'food',
            'section' => 'food',
            'page'    => 'index',
            'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_FOOD_TITLE'),
          ),
        ),
      ),
      'labor_protection' => array(
        'type'    => 'menu_item',
        'path'    => 'labor_protection',
        'section' => 'sveden',
        'page'    => 'labor_protection',
        'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_LABOR_PROTECTION_TITLE'),
      ),
      'accounting_policy' => array(
        'type'    => 'menu_item',
        'path'    => 'accounting_policy',
        'section' => 'sveden',
        'page'    => 'accounting_policy',
        'title'   => Loc::getMessage('EPUBLISH_MAGICSITE_ACCOUNTING_POLICY_TITLE'),
      ),
    );
  }
}
