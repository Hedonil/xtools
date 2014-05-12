<?php

class perflog {
	public $stack = array();
	
	function add( $modul, $time ){
		array_push( $this->stack, array("modul" => $modul, "time" => number_format($time, 3) ));
	}
	function getOutput(){
		if( count($this->stack) == 0){ return null;}
		$out = "<div><table style='margin:50px;'><tr><td>modul</td><td>time(sec)</td></tr>";
		foreach ($this->stack as $val){
			$out .= "<tr><td>".$val["modul"]."</td><td>".$val["time"]."</td></tr>";
		}
		$out .= "</table>";
		return $out;
	}
}
$perflog = new perflog();

echo "<!--";
 	require_once( '/data/project/newwebtest/Peachy/Init.php' );
echo "-->";
	require_once( 'I18N.php' );


/**
 * Main class for all xtools subtools
 * @author jb
 *
 */
class WebTool {
	public $basePath = "//tools.wmflabs.org/newwebtest";
	
	public $toolname;
	public $moreheader;
	public $alert;
	public $error;
	
	public $title;
	public $content;
	
	public $sourcecode;
	public $bugreport;
	
	public $uselang;
	public $translate;
	public $langLinks;
	
	public $webRequest;
	
	private $mOutput;

	function __construct( $toolname = null, $smarty_name = null, $dont = array() ) {
		global $wgRequest, $wtConfigTitle, $phptemp, $content, $time, $I18N;
		
		$time = microtime( 1 );
		
		//Get new WebReuest object (Peachy)
		if( is_null( $wgRequest ) ) {

			//old style -> global object
			$wgRequest = new WebRequest();

			//new style -> part of WebTool object
			$this->webRequest = &$wgRequest;
		}
		
		$this->uselang = $wgRequest->getSafeVal('uselang');
		$I18N->setLang( $this->uselang );
		
		$this->toolname = $toolname;
		$this->sourcecode = '<a href="//github.com/x-Tools/xtools/" >'.$I18N->msg('source').'</a> |';
		$this->bugreport = '<a href="//github.com/x-Tools/xtools/issues" >'.$I18N->msg('bugs').'</a> |';
		$this->translate = $I18N->msg('translatelink');
		$this->langLinks = $I18N->langLinks;
		
		$wtConfigTitle = $smarty_name;
		
		mb_internal_encoding("utf-8"); 
		header('content-type: text/html; charset: utf-8'); 
		
		error_reporting(E_ALL|E_STRICT);
		ini_set("display_errors", 1);
		
		if( !in_array( 'showonlyerrors', $dont ) ) { 
			error_reporting(E_ERROR);
		}
		
		if( !in_array( 'smarty', $dont ) ) {
			require_once( "/data/project/xtools/public_html/Smarty/languages.class.php" );
			require_once( "/data/project/xtools/public_html/Smarty/Smarty.class.php" );
			self::initSmarty( $smarty_name );
		}
		
		if( !in_array( 'sitenotice', $dont ) ) {
			require_once( '/data/project/xtools/public_html/sitenotice.php' );
			self::checkSitenotice();
		}
		
		if( !in_array( 'getwikiinfo', $dont ) ) self::getWikiInfo();
		
		self::setDBVars();
		
		if( !in_array( 'peachy', $dont ) ) {
			self::loadPeachy();
		}
		
		if( !in_array( 'database', $dont ) ) {
			self::loadDatabase();
			
			if( !in_array( 'replag', $dont ) ) {
				$replag = self::getReplag();
				if ($replag[0] > 120) {
					$content->assign( 'replag', $phptemp->get_config_vars( 'highreplag', $replag[1] ) );
				}
			}
		}
		
		if( !in_array( 'addstat', $dont ) ) {
			require_once( '/data/project/xtools/stats.php' );
			addStatV3( $toolname );
		}
	
	}
   
	
   static function loadForApi( $toolname, $showonlyerrors = true, $db = true ) {
      
      mb_internal_encoding("utf-8"); 
      header('content-type: text/html; charset: utf-8'); 

      error_reporting(E_ALL|E_STRICT);
      ini_set("display_errors", 1);
      
      if( $showonlyerrors ) { 
         error_reporting(E_ERROR);
      }
      
      define( 'WEBTOOL_API_TRUE', 1 );
      
      if( !$db ) return;
      
      self::setDBVars();
      self::loadDatabase();
      
			require_once( '/data/project/xtools/stats.php' );
			addStatV3( $toolname );
      
      self::loadPeachy();
      
   }
   
	static function setMemLimit( $mb = 512 ) {
		ini_set("memory_limit", $mb . 'M' );
	}
   
	static function loadPeachy() {
		global $url, $pgVerbose, $site;
		
		$pgVerbose = array();
#		echo "<!--";
		$site = Peachy::newWiki( null, null, null, 'http://'.$url.'/w/api.php' );
#		echo "-->";
	}
	
	static function checkSitenotice() {
		global $content;
		
		$siteNoticeClass = new siteNotice;
		$sitenotice = $siteNoticeClass->checkSiteNoticeRaw();
		
		if( $sitenotice ) {
			$content->assign( "alert", $sitenotice );
		}
	}
   
   static function setDBVars() {
      global $toolserver_username, $toolserver_password;

//      $toolserver_mycnf = parse_ini_file("/data/project/xtools/replica.my.cnf");
      $toolserver_mycnf = parse_ini_file("/data/project/newwebtest/replica.my.cnf");
      $toolserver_username = $toolserver_mycnf['user'];
      $toolserver_password = $toolserver_mycnf['password'];
      unset($toolserver_mycnf);
   }
   
   
	static function loadDatabase( $api = false ) {
		global $lang, $wiki, $url, $phptemp, $dbr, $toolserver_username, $toolserver_password;
		
		self::setDBVars();
		
		try {
			if( $wiki = 'wikipedia' || $wiki = 'wikimedia' ) $wiki = "wiki";
			$res['server'] = $lang.$wiki.".labsdb";
			$res['dbname'] = $lang.$wiki."_p";
			
			if ($wiki == "wikidata") {
				$res['dbname'] = 'wikidatawiki_p';
				$res['server'] = 'wikidatawiki.labsdb';
			}
			
			$dbr = new Database( 
					$res['server'], 
					$toolserver_username, 
					$toolserver_password, 
					$res['dbname']
			);
		} 
		catch( DBError $e ) {
			if( !$api ) self::toDie( $phptemp->get_config_vars( 'mysqlerror', $e->getMessage() ) );
			return array( 'error' => 'mysqlerror', 'info' => $e->getMessage() );
		}
	}
	
	static function getWikiInfo() {
		global $wgRequest, $lang, $wiki, $url;
		
		$wiki = $wgRequest->getSafeVal( 'wiki', 'wikipedia' );
		$lang = $wgRequest->getSafeVal( 'lang', 'en' );
		$url = $lang.'.'.$wiki.'.org';
	}
	
	static function toDie( $msg ) {
		global $content;
		
		$content->assign( "error", $msg );
		self::assignContent();
	}
	
	static function toDieMsg( $msg ) {
		global $phptemp;
		
		$text = call_user_func_array( array( $phptemp, 'get_config_vars' ), func_get_args() );
		self::toDie( $text );
	}
	
	static function pre( $array ) {
		echo "<pre>";
		print_r( $array );
		echo "</pre>";
	}
	
	static function getTimeString( $secs ) {
		$r = implode( ', ', self::getTimeArray( $secs ) );
		
		return $r;
	}
	
	static function getTimeArray( $secs ) {
		global $I18N;
		
		if( !$secs ) return array( '0 ' .  $I18N->msg( 's' ) );

		$second = 1;
		$minute = $second * 60;
		$hour = $minute * 60;
		$day = $hour * 24;
		$week = $day * 7;
		$month = $day * ( 365 / 12 );

		$r = array();
		if ($secs > $month) {
			$count = 0;
			for( $i = $month; $i <= $secs; $i += $month ) {
				$count++;
			}

			$r[] = $count . ' ' . $I18N->msg( 'mo' );
			$secs -= $month * $count;
		}
		if ($secs > $week) {
			$count = 0;
			for( $i = $week; $i <= $secs; $i += $week ) {
				$count++;
			}
			 
			$r[] = $count . ' ' . $I18N->msg( 'w' );
			$secs -= $week * $count;
		}
		if ($secs > $day) {
			$count = 0;
			for( $i = $day; $i <= $secs; $i += $day ) {
				$count++;
			}
			 
			$r[] = $count . ' ' . $I18N->msg( 'd' );
			$secs -= $day * $count;
		}
		if ($secs > $hour) {
			$count = 0;
			for( $i = $hour; $i <= $secs; $i += $hour ) {
				$count++;
			}
			 
			$r[] = $count . ' ' . $I18N->msg( 'h' );
			$secs -= $hour * $count;
		}
		if ($secs > $minute) {
			$count = 0;
			for( $i = $minute; $i <= $secs; $i += $minute ) {
				$count++;
			}
			 
			$r[] = $count . ' ' . $I18N->msg( 'm' );
			$secs -= $minute * $count;
		}
		if ($secs) {
			$r[] = $secs . ' ' . $I18N->msg( 's' );
		}
		
		return $r;
	}
	
	static function getReplag( $conn = null ) {
		if( is_null( $conn ) ) {
			global $dbr;
			$conn = &$dbr;
		}
		
		$res = $conn->query("
				SELECT ( UNIX_TIMESTAMP() - UNIX_TIMESTAMP(rc_timestamp) ) AS replag
				FROM recentchanges
				ORDER BY rc_timestamp DESC
				LIMIT 1
			");
		$seconds = floor( $res[0]['replag'] );
		$text = self::getTimeString( $seconds );
		
		return array( $seconds, $text );
	}
	
	static function finishScript() {
		global $time, $phptemp;
		
		$exectime = number_format(microtime( 1 ) - $time, 2, '.', '');
		$phptemp->assign( "excecutedtime", $phptemp->get_config_vars( 'executed', $exectime ) );
		self::assignContent();
	}
   
	static function prettyTitle( $s, $capital = false ) {
		$name = trim( str_replace( array('&#39;','%20'), array('\'',' '), $s ) );
		$name = urldecode($name);
		$name = str_replace('_', ' ', $name);
		$name = str_replace('/', '', $name);
		
		if( $capital ) $name = ucfirst( $name );
		
		return $name;
	}
   
   static function isIP( $name ) {
      return (bool) ( long2ip(ip2long($name)) == $name );
   }
   
   static function number_format( $number, $decimal = 2 ) {
      global $phptemp;
      
      if( method_exists( $phptemp, 'get_config_vars' ) ) {
         return number_format( $number, $decimal, $phptemp->get_config_vars( 'decimal_separator' ), $phptemp->get_config_vars( 'thousands_separator' ) );
      }
      else {
         return number_format( $number, $decimal );
      }
   }
	
	public function iin_array( $needle, $haystack ) {
		return in_array( strtoupper( $needle ), array_map( 'strtoupper', $haystack ) );
	}
	
	static function in_string( $needle, $haystack ) {
		return strpos( $haystack, $needle ) !== false;
	}
	
	public function showPage( &$wt ){
		global $perflog;
		$this->translate_i18n();
		include '../templates/main.php';
		unset($wt);
		
		echo $perflog->getOutput();
		exit(0);
	}
	
	/**
	 * Loads Intuition I18N object. Replaces {#these#} with the messages.
	 * @param object $I18N - defined in class I18N
	 * @param string $texdomain Intuition registered textdomain eg. 'supercount', default already set in object
	 * @param string $currLang language to translate to, default set in object
	 * @return void
	 */
	function translate_i18n( $textdomain=null, $currLang=null ) {
		global $I18N;
		
		$textdomain = ( is_null($textdomain) ) ? $I18N->getDomain() : $textdomain;
		$currLang = ( is_null($currLang) ) ? $I18N->getLang() : $currLang;
	
		$i18KeyArr = $I18N->listMsgs( $textdomain );
		$i18opt = array(
				"domain" => $textdomain,
				"lang" => $currLang,
				"variables" => array(1),
				"parsemag" => true,
		);
		#	print_r($i18KeyArr);
		foreach( $i18KeyArr as $i => $i18Key ) {
			$this->content = str_ireplace( '{#'.$i18Key.'#}', $I18N->msg($i18Key, $i18opt ), $this->content );
		}
	}
	
	/**
	 * Replaces {$something$} with some string. Also parses the isset function
	 * @param string $name Variable to change
	 * @param string $value What to change it to.
	 * @return void
	 */
	function assign( $name, $value ) {
		$this->content = str_replace( '{$'.$name.'$}', $value, $this->content );
		$this->content = str_replace( '{$'.$name.'}', $value, $this->content );
	
		$this->content = str_ireplace( '{&isset: '.$name.' &}', '', $this->content );
	}
}


