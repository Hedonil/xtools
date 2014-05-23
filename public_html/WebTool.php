<?php
DEFINE('STARTTIME', microtime(True) );
DEFINE('STARTMEM', memory_get_usage(true) );

DEFINE('PEACHY_BASE_SYS_DIR', '/data/project/newwebtest' );
DEFINE('XTOOLS_BASE_SYS_DIR', '/data/project/newwebtest' );
DEFINE('XTOOLS_BASE_WEB_DIR', '//tools.wmflabs.org/newwebtest' );
DEFINE('XTOOLS_I18_TEXTFILE', '/data/project/newwebtest/i18n/Supercount.i18n.php');
DEFINE('XTOOLS_REDIS_FLUSH_TOKEN', 'x000000');

$perflog = new Perflog();

// Incudes
	require_once('/data/project/intuition/src/Intuition/ToolStart.php');
	require_once( PEACHY_BASE_SYS_DIR . '/Peachy/Init.php' );


/**
 * Main class for all xtools subtools
 * Requires the following Peachy classes (or equivalent):
 * HTTP, WebRequest, Wiki
 * @author jb
 *
 */
class WebTool {
	public $basePath = XTOOLS_BASE_WEB_DIR;
	
	public $moreheader;
	
	public $sitenotice;
	public $alert;
	public $error;
	public $replag;
	
	public $toolTitle;
	public $toolDesc;    //description
	
	public $content;
	
	public $sourcecode;
	public $bugreport;
	public $executed;
	public $memused;
	
	public $userinfo;
	
	public $i18Langs;
	public $uselang;
	public $translateMsg;
	public $langLinks;
	
	private $numberFormatter;
	private $dateFormatter;
	private $dateFormatterDate;
	private $dateFormatterTime;
	private $mOutput;

	function __construct( $viewtitle = null, $configtitle = null, $options = array() ) {
		global $wgRequest, $dbr, $site, $I18N, $redis;
		
		//Start session
#		session_cache_limiter("public");
		$lifetime = 86400;
		$path = preg_replace('/^(\/.*\/).*/', '\1', dirname($_SERVER['SCRIPT_NAME']) );
		session_name( 'xtools' );
		session_set_cookie_params ( $lifetime, $path, ".tools.wmflabs.org"); 
		session_start();
		
		//Init webRequest object
		$wgRequest = new WebRequest();
		
		//Init redis caching support
		$redis = $this->initRedis();
		
		//Init I18n language support
		$I18N = $this->initI18N();
		$this->checkLocale();
		$I18N->setLang( $this->uselang );
		
		//Init I18 specific number/date formatter
		$this->numberFormatter   = new NumberFormatter( $this->uselang, NumberFormatter::DECIMAL);
		$this->dateFormatter     = new IntlDateFormatter( $this->uselang, IntlDateFormatter::MEDIUM, IntlDateFormatter::MEDIUM, "UTC", IntlDateFormatter::GREGORIAN);
		$this->dateFormatterDate = new IntlDateFormatter( $this->uselang, IntlDateFormatter::MEDIUM, IntlDateFormatter::NONE, IntlDateFormatter::GREGORIAN);
		$this->dateFormatterTime = new IntlDateFormatter( $this->uselang, IntlDateFormatter::NONE, IntlDateFormatter::SHORT, "UTC", IntlDateFormatter::GREGORIAN);
		
		$this->toolTitle = $I18N->msg( 'tool_'.$configtitle );
		$this->toolDesc  = $I18N->msg( 'tool_'.$configtitle.'_desc' );
		
		$this->sourcecode = '<a href="//github.com/x-Tools/xtools/" >'.$I18N->msg('source').'</a> |';
		$this->bugreport = '<a href="//github.com/x-Tools/xtools/issues" >'.$I18N->msg('bugs').'</a> |';
		
		mb_internal_encoding("utf-8"); 
		
// 		if( !in_array( 'addstat', $dont ) ) {
// 			require_once( '/data/project/xtools/stats.php' );
// 			addStatV3( $toolname );
// 		}

	}
	
	function getWikiInfo( $lang=null, $wiki=null ) {
		global $wgRequest;
		
		$obj = new stdClass();
			$obj->lang = null;
			$obj->wiki = null;
			$obj->domain = null;
			$obj->database = null;
			$obj->error = false;
	
		if (!$lang || !$wiki ){
			$lang = $wgRequest->getVal( 'lang');
			$wiki = $wgRequest->getVal( 'wiki');
		}
		if (!$lang || !$wiki ){
			$obj->error = 'no wiki specified';
			return $obj;
		}
		
		if ( $wiki == "wikipedia") {
			$obj->lang = $lang;
			$obj->wiki = $wiki;
			$obj->domain = $lang.'.'.$wiki.'.org';
			$obj->database = $lang.'wiki';
		}
		if ( $lang == "wikidata" || $wiki == "wikidata") {
			$obj->lang = "www";
			$obj->wiki = "wikidata";
			$obj->domain = $lang.'.'.$wiki.'.org';
			$obj->database = "wikidatawiki";
		}
		if ( $lang == "commons" || $wiki == "commons") {
			$obj->lang = "commons";
			$obj->wiki = "wikimedia";
			$obj->domain = $lang.'.'.$wiki.'.org';
			$obj->database = "commonswiki";
		}
		if ( $lang == "mediawiki" || $wiki == "mediawiki") {
			$obj->lang = "www";
			$obj->wiki = "mediawiki";
			$obj->domain = $lang.'.'.$wiki.'.org';
			$obj->database = "mediawikiwiki";
		}
		if ( $lang == "meta" || $wiki == "meta") {
			$obj->lang = "meta";
			$obj->wiki = "wikimedia";
			$obj->domain = $lang.'.'.$wiki.'.org';
			$obj->database = "metawiki";
		}
		
		return $obj;
	}
	
	function getUserInfo( $lang=null, $wiki=null, $user=null){
		global $wgRequest;
		
		$uio = new stdClass(
				$username = null,
				$usernameUrlEnc = null,
				$isIP = false,
				$userid = null
			);
		
		$username = ( !$user ) ? $wgRequest->getVal('user') : $user ;
		$username = ( !$username ) ? $wgRequest->getVal( 'name' ) : $username;
		
		$uio->isIP = ( long2ip( ip2long( $username ) ) == $username ) ? true : false;
		
	}
	
	function checkLocale(){
		global $wgRequest, $I18N;

		$this->uselang = "en";
		
		//1. check browser lang
		$bl = array_keys( $wgRequest->getAcceptLang() );
		$bl = explode("-", $bl[0]);
		if ( array_key_exists( $bl[0], $this->i18Langs ) ) {
			$this->uselang = $bl[0];
		}
		
		//2. check session
		$sl = $wgRequest->getSessionData( "uselang" );
		if ($sl) {
			$this->uselang = $sl;
		}
		
		//3. check uselang setting
		$ul = $wgRequest->getVal( "uselang" );
		if ( array_key_exists( $ul, $this->i18Langs ) ) {
			$wgRequest->setSessionData( "uselang", $ul);
			$this->uselang = $ul;
		}
		
		$this->generateLangLinks();
	}
   
	function setLimits( $mb = 512, $time = 30 ) {
		ini_set("memory_limit", $mb . 'M' );
		set_time_limit ( $time );
	}
   
	public function loadPeachy( $lang, $wiki ) {
		global $pgVerbose;
		
		$pgVerbose = array();
		
		$wi = $this->getWikiInfo( $lang, $wiki );

		return Peachy::newWiki( null, null, null, "http://$wi->domain/w/api.php" );
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
	
	public function loadDatabase( $lang, $wiki, $dbnameIn=null) {
		global $dbUser, $dbPwd;
		
		$this->loadDBCredentials();
		
		$wi = $this->getWikiInfo( $lang, $wiki);
			$server = $wi->database.".labsdb";
			$dbname = $wi->database."_p";

		if ($dbnameIn){
			$server = $dbnameIn.".labsdb";
			$dbname = $dbnameIn."_p";
		}
		
		try {	
			$dbr = new Database2( $server, $dbUser, $dbPwd, $dbname );
			$this->checkReplag( $dbr );

			return $dbr;
		} 
		catch( DBError $e ) {
			#$this->toDie( 'mysqlerror', $e->getMessage() );
			return null;
		}
	}
	
	function checkReplag( $dbr ) {
		global $I18N;

		$res = $dbr->query("
				SELECT ( UNIX_TIMESTAMP() - UNIX_TIMESTAMP(rc_timestamp) ) AS replag
				FROM recentchanges
				ORDER BY rc_timestamp DESC
				LIMIT 1
			");
		
		$secs = floor($res[0]['replag']);
		if(  $secs > 120  ){
			$timeMin = (int)($secs / 60);
			$msgMin = $I18N->msg( 'minutes', array("variables" => array($timeMin)));
			$this->replag = $I18N->msg( 'highreplag', array("variables" => array($msgMin))); 
		}

	}
	
	function initI18N(){
		global $redis;
		
		$ttl = 86400;
		$hash = hash( 'crc32', "xtoolsI18N_".XTOOLS_REDIS_FLUSH_TOKEN );
		
		$lc = $redis->get( $hash );
		if ( $lc === false ) {
			
			$I18N = new Intuition();
			$I18N->loadTextdomainFromFile( XTOOLS_I18_TEXTFILE, 'supercount');
			$I18N->setDomain('supercount');
			
#			$redis->setex( $hash, $ttl, serialize($I18N) );
		}
		else {
			$I18N = unserialize($lc);
		 	unset($lc);
		}
		
		$this->i18Langs = $I18N->getAvailableLangs('supercount');
		$this->translateMsg = '
				<span  style="margin-right:5px" ><a href="//translatewiki.net/wiki/Special:Translate?group=tsint-supercount&amp;
							filter=%21translated&amp;action=translate&amp;setlang='.$this->uselang.'" >('.$I18N->msg('translatelink').')</a>
				</span>
				';
		
		return $I18N;
	}
	
	
	/**
	 * Generates a list of languages that aren't currently selected
	 * @return void
	 */
	function generateLangLinks() {
		
		$langLinks = "";
		foreach( $this->i18Langs as $langCode => $langName ) {
			
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
			
			if( $langCode == $this->uselang ) {
				$langLinks .= '<a title="'.$langName.'" >'.$langCode.'</a> ';
			}
			else{
				$langLinks .= '<a href="'.$url.'" title="'.$langName.'" >'.$langCode.'</a> ';
			}
		}
		
		
		$this->langLinks = $this->translateMsg . $langLinks;
	}
	

	
	/**
	 * Checks dates: Input format YYYY-MM-DD or YYYY-MM or YYYY
	 * @param string $date
	 * @return string
	 */
	function checkDate ( $date ){
		if ( !$date) return null;
		
		$len = strlen($date);
		switch ($len) {
			case 10:
				$year = substr($date,0,4);
				$mon  = substr($date,5,2);
				$day  = substr($date,8,2);				
				break;
			case 7:
				$year = substr($date,0,4);
				$mon  = substr($date,5,2);
				$day  = "01";				
				break;
			case 4:
				$year = substr($date,0,4);
				$mon  = "01";
				$day  = "01";
				break;
			
			default: 
				;
		}
		// check format month,day,year
		if ( checkdate( $mon, $day, $year ) ){
			$res = "$year-$mon-$day";
		}
		else{
			$res = 'error';
		}
		
		return $res;
	}
	
	public function toDie( $msgStr , $var=null ) {
		global $I18N;
		
		if( is_string($var) ){ $var = array($var); }
		
		$msg = $I18N->msg( $msgStr , array("variables" => $var) );		
		$this->error = $msg ;
		$this->showPage();
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
		$this->numberFormatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $decimal);
		
		return $this->numberFormatter->format($number);
	}
	
	public function dateFmt( $date ){
		$datetime = new DateTime($date);
		
		$mDate = $this->dateFormatterDate->format( $datetime );
		$mTime = $this->dateFormatterTime->format( $datetime );
		
		return $mDate.", ".$mTime; //$this->dateFormatter->format($datetime);
	}
	
	public function iin_array( $needle, $haystack ) {
		return in_array( strtoupper( $needle ), array_map( 'strtoupper', $haystack ) );
	}
	
	static function in_string( $needle, $haystack ) {
		return strpos( $haystack, $needle ) !== false;
	}
	
	function initRedis(){
		
		$redis = new Redis();
		if ($redis->connect('tools-redis', 6379)){
			try {
				$redis->info("server");
			}
			catch (Exception $e){
				$redis = new RedisFake();
			}
		}
		else {
			$redis = new RedisFake();
		}

		return $redis;
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
		header('content-type: text/html; charset: utf-8');
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

class Database2{
	
	public $dbo;
	public $dbotype;
	
	private $server;
	private $database;
	
	function __construct( $server, $dbUser, $dbPassword, $database, $persistant=false ){
		
		$p = ($persistant) ? "p:" : "";
		
		$this->dbo = new mysqli( $server, $dbUser, $dbPassword, $database);
		$this->dbo->set_charset("utf8");
		$this->dbotype = 'custom';
		$this->server = $server;
		$this->database = $database;
	}
	
	function query( $queryString ) {
		
		$retArr = null;

		if ( $result = $this->dbo->query( $queryString ) ){
			
			while( $row = $result->fetch_assoc() ){
				$retArr[] = $row;				
			}
			$result->close();
		}

		return $retArr;
	}
	
	function close(){
		$this->dbo->close();
	}
	function strencode( $String ){
		return $this->dbo->real_escape_string( $String );
	}
	
	function multiquery( $queries ){
		global $redis, $perflog;
		
		if ($redis){
			$sqlapi = "http://tools-webproxy/tools-info/sqlapi/api.php?";
			
			//Get the separate queries
			foreach ( $queries as $i => $query ){
				
				if( $query["type"] == "db" ) {
					
					$server = $this->server;
					$database = $this->database;
					
					if ( $query["src"] != "this" ){
						$server = $query["src"].".labsdb";
						$database = $query["src"]."_p";
					}
						
					$data = array(
							"token" => hash( "crc32", $query["query"]),
							"server" => $server,
							"database" => $database,
							"query" => $query["query"],
						);
					$request[] = $sqlapi.http_build_query($data);
				}
				
				if( $query["type"] == "api" ){
					$request[] = $query["query"];
				}
			}
			
			$apiresults = $this->multicurl($request);
#print_r($apiresults); 
			
			//Get the results from redis
			$error = false;
			foreach ($apiresults as $i=> $apiresult ){
				
				$obj = json_decode( $apiresult, false);
				
				if ( $queries[$i]["type"] == "api"){
					$result[$i] = $obj;
				}
				elseif ( !is_object($obj) || $obj->length == 0 ) {
					$start = microtime(true);
					
					$result[$i] = $this->query( $queries[$i]["query"] );
					 
					$perflog->add('dbr_local: ', (microtime(true) - $start) );
				}
				else{
					$result[$i] = json_decode( $redis->get( $obj->rediskey ), true );
					
					$perflog->add('sql_api: '.$obj->len, $obj->exectime );
				}
			}
			
			return $result;
			
		}
		
	}
	
	function multicurl( $urlArr, $method="GET", $request=null ){

		global $version;
		$res	= null;
		$err 	= null;
	
		if ( !is_array($urlArr) ) { $urlArr = array($urlArr); }
	
		//create multiple cUrl handler
		$mh = curl_multi_init();
	
		foreach ( $urlArr as $i => $url ) {
			$ch[$i] = curl_init();
			curl_setopt($ch[$i], CURLOPT_USERAGENT, 'WikiViewStats/'.$version.' (https://tools.wmflabs.org/wikiviewstats/; Hedonil)');
			curl_setopt($ch[$i], CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch[$i], CURLOPT_URL, $url);
			//echo curl_getinfo($handle, CURLINFO_HTTP_CODE);
	
			if ( $method == "POST") {
				curl_setopt( $ch[$i], CURLOPT_POST, true );
				curl_setopt( $ch[$i], CURLOPT_POSTFIELDS, http_build_query( $request ) );
				//curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
			}
			curl_multi_add_handle($mh, $ch[$i]);
		}
	
		$active = null;
		// execute the handles
		do {
			$mrc = curl_multi_exec($mh, $active);
		} while ($mrc == CURLM_CALL_MULTI_PERFORM);
	
		//check for results and execute until everything is done
		while ($active && $mrc == CURLM_OK) {
			if (curl_multi_select($mh) == -1) {
				usleep(50);
			}
			do {
				$mrc = curl_multi_exec($mh, $active);
			} while ($mrc == CURLM_CALL_MULTI_PERFORM);
		}
	
		//fetch the results
		foreach ($urlArr as $i => $url ) {
			$res[$i] = curl_multi_getcontent($ch[$i]);
			curl_close($ch[$i]);
		}
	
		#	$err = curl_error($mh);
		curl_multi_close($mh);
		$mh = null;
	
		if (count($res) == 1) { $res = $res[0]; }
	

		return $res;
	}
}

	class Perflog {
		public $stack = array();
	
		function add( $modul, $time ){
			array_push( $this->stack, array("modul" => $modul, "time" => $time ));
		}
		function getOutput(){
			echo "<pre>"; print_r($this->stack); echo "</pre>";
		}
	}
	
	/**
	 * dummy class, if redis is not availabe
	 * @author Hedonil
	 */
	class RedisFake{
		function get(){
			return false;
		}
		function set(){
			return false;
		}
		function setex(){
			return false;
		}
	}