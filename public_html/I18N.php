<?php 

require_once('/data/project/intuition/src/Intuition/ToolStart.php');
#require_once('/data/project/newwebtest/xtools/public_html/WebTool.php' );
$i18nPath = '/data/project/newwebtest';

#require_once('Init.php');

$I18N = i18n_init();

// $ttl = 86400;
// $i18nRefreshToken = "cd00016";
// $hash = hash(WVS_HASH_ALGO, "wikiviewstatsI18N".$i18nRefreshToken.$redisFlush);

// $lc = Init::query_cache_redis($hash, false);
// if ( $lc === false ) {
// 	$I18N = i18n_init(); 
// 	Init::write_cache_redis($hash, serialize($I18N), $ttl, false);
// }
// else {
// 	$I18N = unserialize($lc);
// 	unset($lc);
// }


function i18n_init(){
	global $i18nPath;
	
	$I18N = new TsIntuition();
	$I18N->loadTextdomainFromFile( $i18nPath.'/i18n/Supercount.i18n.php', 'supercount');
	$I18N->setDomain('supercount');

	$I18N->langLinks = generateLangLinks( $I18N->getAvailableLangs('supercount') );
		
	//non-tranlate messages
	$I18N->setMsg('wikidata', 'Wikidata', 'supercount', 'en');
	
	//set messages from General
	
	//set if translation for wikitable TOP 500 is complete to avoid mixture
	$I18N->wikitextTranslationComplete = array('en','de');
	
	return $I18N;
}


/**
 * Generates a list of languages that aren't currently selected
 * @return string $langlinks variable
 */
function generateLangLinks( $langArr ) {
	$langLinks = "";
	foreach( $langArr as $langCode => $langName ) {
#		if( $cur_lang != $this->mLang ) {
				
			$url = "//tools.wmflabs.org".$_SERVER['REQUEST_URI'];

			if( strpos( $url, 'uselang') > 0 ) {
				$url = preg_replace( '/uselang=(.*?)&?/', '', $url );
			}
			if( strpos( $url, '?') > 0 ) {
				$url = $url . "&uselang=".$langCode;
			}
			else {
				$url = $url . "?uselang=".$langCode;
			}

			$langLinks.="<a href=\"". $url."\" title=\"$langName\" >".$langCode."</a> ";
#		}
	}

	return $langLinks;
}

