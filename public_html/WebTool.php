<?php
DEFINE('STARTTIME', microtime(True) );
DEFINE('STARTMEM', memory_get_usage(true) );
DEFINE('PEACHY_BASE_SYS_DIR', '/data/project/newwebtest' );
DEFINE('XTOOLS_BASE_SYS_DIR', '/data/project/newwebtest' );
DEFINE('XTOOLS_BASE_WEB_DIR', '//tools.wmflabs.org/newwebtest' );

class Perflog {
	public $stack = array();
	
	function add( $modul, $time ){
		array_push( $this->stack, array("modul" => $modul, "time" => number_format($time*1000, 2) ));
	}
	function getOutput(){
		echo "<pre>"; print_r($this->stack); echo "</pre>";
	}
}
$perflog = new Perflog();

// Incudes
	echo "<!--";
	require_once( PEACHY_BASE_SYS_DIR . '/Peachy/Init.php' );
	echo "-->";
	require_once( 'I18N.php' );
	include( 'sitenotice.php' );

/**
 * Main class for all xtools subtools
 * @author jb
 *
 */
class WebTool {
	public $basePath = XTOOLS_BASE_WEB_DIR;
	
	public $toolname;
	public $moreheader;
	
	public $sitenotice;
	public $alert;
	public $error;
	public $replag;
	
	public $replagtime;
	
	public $title;
	
	public $content;
	
	public $sourcecode;
	public $bugreport;
	public $executed;
	public $memused;
	
	public $uselang;
	public $translate;
	public $langLinks;
	
	public $webRequest;
	
	private $numberFormater;
	private $dateFormater;
	private $mOutput;

	function __construct( $viewtitle = null, $configtitle = null, $options = array() ) {
		global $wgRequest, $starttime, $startmem, $I18N, $sitenotice, $lang, $wiki, $url, $dbr, $site;
		
		//old style -> global object
		$wgRequest = new WebRequest();
		
		$this->webRequest = &$wgRequest;
		$this->getWikiInfo();
		
		$this->uselang = $wgRequest->getSafeVal( 'uselang', 'en');
		$I18N->setLang( $this->uselang );
		
		$this->toolname = $viewtitle;
		$this->sourcecode = '<a href="//github.com/x-Tools/xtools/" >'.$I18N->msg('source').'</a> |';
		$this->bugreport = '<a href="//github.com/x-Tools/xtools/issues" >'.$I18N->msg('bugs').'</a> |';
		$this->translate = $I18N->msg('translatelink');
		$this->langLinks = $I18N->langLinks;
		
		$this->numberFormater = new NumberFormatter( $I18N->getLang(), NumberFormatter::DECIMAL);
		$this->dateFormater   = new IntlDateFormatter( $I18N->getLang(), IntlDateFormatter::MEDIUM, IntlDateFormatter::MEDIUM, "UTC", IntlDateFormatter::GREGORIAN);
		
		$this->sitenotice = ( $sitenotice ) ? $sitenotice : null;
		
		
		mb_internal_encoding("utf-8"); 
		header('content-type: text/html; charset: utf-8'); 
		
#		error_reporting(E_ALL|E_STRICT);
#		ini_set("display_errors", 1);

		
		if( in_array( 'showonlyerrors', $options ) ) { error_reporting(E_ERROR); }
		
		if( in_array( 'database', $options ) ) { $dbr = $this->loadDatabasePeachy( $lang, $wiki ); }
		if( in_array( 'databaseCustom', $options ) ) { $dbr = $this->loadDatabaseCustom( $lang, $wiki ); }
			
		if( in_array( 'api', $options ) ) { $site = $this->loadPeachy( $url ); }

// 		if( !in_array( 'addstat', $dont ) ) {
// 			require_once( '/data/project/xtools/stats.php' );
// 			addStatV3( $toolname );
// 		}
		
// 		$this->replagtime = $dbr->replagtime;
// 		if ( $this->replagtime > 120 ){
// 			$minutes = number_format( $this->replagtime / 60, 1);
// 			$timemsg = $I18N->msg( 'minutes', array( "variables" => array($minutes)));
// 			$this->replag = $I18N->msg('highreplag', array("variables" => array( $timemsg) ) );
// 		}
		
	
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
      
      $this->loadDatabase();
      
#			require_once( '/data/project/xtools/stats.php' );
#			addStatV3( $toolname );
      
      $this->loadPeachy();
      
   }
   
	function setMemLimit( $mb = 512 ) {
		ini_set("memory_limit", $mb . 'M' );
	}
   
	public function loadPeachy( $url ) {
		global $pgVerbose;
		
		$pgVerbose = array();

		return Peachy::newWiki( null, null, null, 'http://'.$url.'/w/api.php' );
	}
	
	function loadDBCredentials(){
		global $dbUser, $dbPwd;
		
		try{
			$inifile = XTOOLS_BASE_SYS_DIR . "/replica.my.cnf";
			$iniVal = parse_ini_file($inifile);
			$dbUser = $iniVal["user"];
			$dbPwd  = $iniVal["password"];
			unset($iniVal);
		}
		catch (Exception $e){
			;
		}
	}
	
	public function loadDatabasePeachy( $lang, $wiki) {
		global $dbUser, $dbPwd;
		
		$this->loadDBCredentials();
	
		if( $wiki = 'wikipedia' || $wiki = 'wikimedia' ) $wiki = "wiki";
		$server = $lang.$wiki.".labsdb";
		$dbname = $lang.$wiki."_p";
		
		if ($wiki == "wikidata") {
			$server = 'wikidatawiki.labsdb';
			$dbname = 'wikidatawiki_p';
		}

		try {	
			$dbr = new Database($server, $dbUser, $dbPwd, $dbname );
			#$dbr->__call( 'set_charset' , 'utf8');
			$dbr->classType = 'peachy';
			$dbr->replagtime = $this->getReplag( $dbr );
			
			return $dbr;
		} 
		catch( DBError $e ) {
			#$this->toDie( 'mysqlerror', $e->getMessage() );
			return null;
		}
	}
	
	public function loadDatabaseCustom ( $lang, $wiki ){
		global $dbUser, $dbPwd;
		
		$this->loadDBCredentials();
		
		if( $wiki = 'wikipedia' || $wiki = 'wikimedia' ) $wiki = "wiki";
		$server = $lang.$wiki.".labsdb";
		$dbname = $lang.$wiki."_p";
		
		if ($wiki == "wikidata") {
			$server = 'wikidatawiki.labsdb';
			$dbname = 'wikidatawiki_p';
		}
		
		$mysqli = new mysqli( 'p:'.$server, $dbUser, $dbPwd, $dbname);
		$mysqli->set_charset("utf8");
		$mysqli->classType = 'custom';
		
		return $mysqli;
	}

	function getReplag( &$dbr ) {

		$res = $dbr->query("
				SELECT ( UNIX_TIMESTAMP() - UNIX_TIMESTAMP(rc_timestamp) ) AS replag
				FROM recentchanges
				ORDER BY rc_timestamp DESC
				LIMIT 1
			");
		
		return floor( $res[0]['replag'] );
	}
	
	
	function getWikiInfo() {
		global $wgRequest, $lang, $wiki, $url;
		
		$wiki = $wgRequest->getSafeVal( 'wiki', 'wikipedia' );
		$lang = $wgRequest->getSafeVal( 'lang', 'en' );
		$url = $lang.'.'.$wiki.'.org';
	}
	
	public function toDie( $msgStr , $var=null ) {
		global $I18N;
		
		if( is_string($var) ){ $var = array($var); }
		
		$msg = $I18N->msg( $msgStr , array("variables" => $var) );		
		$this->assign( "error", $msg );
		$this->showPage();
	}
	
	
	static function pre( $array ) {
		echo "<pre>";
		print_r( $array );
		echo "</pre>";
	}
	
	public function prettyTitle( $s, $capital = false ) {
		$name = trim( str_replace( array('&#39;','%20'), array('\'',' '), $s ) );
		$name = urldecode($name);
		$name = str_replace('_', ' ', $name);
		$name = str_replace('/', '', $name);
		
		if( $capital ) $name = ucfirst( $name );
		
		return $name;
	}
	
	public function isIP( $name ) {
		return (bool) ( long2ip(ip2long($name)) == $name );
	}
	
	public function numFmt( $number, $decimal = 0, $noZero = false ) {
		if ( intval($number) == 0 && $noZero ){
			return null;
		}
		$this->numberFormater->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $decimal);
		
		return $this->numberFormater->format($number);
	}
	
	public function dateFmt( $date ){
		$datetime = new DateTime($date);
		return $this->dateFormater->format($datetime);
	}
	
	public function iin_array( $needle, $haystack ) {
		return in_array( strtoupper( $needle ), array_map( 'strtoupper', $haystack ) );
	}
	
	static function in_string( $needle, $haystack ) {
		return strpos( $haystack, $needle ) !== false;
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
	
	/**
	 * Finishes script, outputs the things, unsets the objects & vars
	 * @return void
	 */
	public function showPage(){
		global $I18N, $perflog;
		
		$this->translate_i18n();
		
		$exectime = $this->numFmt( (microtime(true) - STARTTIME),2 );
		$this->executed = $I18N->msg( 'executed', array( "variables" => array($exectime) ) );
		
		$mem = $this->numFmt( (memory_get_usage(true) - STARTMEM) /1024/1024, 2);
		$peak = $this->numFmt( (memory_get_peak_usage( true ) /1024/1024) , 2);
		$this->memused = $I18N->msg( 'memory', array( "variables" => array($mem)) )." (Peak: $peak)";
		
		$wt = &$this;
		include '../templates/main.php';
	
		echo $perflog->getOutput();
		$this->__destruct();
	}
	
	function __destruct(){
		global $dbr, $wgRequest, $site;
		
		if ( isset($dbr) ){ $dbr->close(); }
		unset( $dbr, $wgRequest, $site );
		exit(0);
	}

}

