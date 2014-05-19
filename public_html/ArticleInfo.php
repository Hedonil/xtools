<?php

class ArticleInfo {
	
	private $history = array();
	private $pageLogs = array();
	private $tmpLogs = array();
	private $wikidataItems = array();
	
	public $pagetitleFull = ""; //with namespace prefix
	public $pagetitle = "";
	public $pageid = -1;
	public $namespace = -1;
	public $data = array();
	
	public $begin;
	public $end;
	
	public $error = false;
	public $historyCount = 0;
	
	public $wikidatalink = "";
	
	private $AEBTypes;
	private $perflog;
	
	/**
	 * 
	 * @param Peachy::Database $dbr
	 * @param Peachy::Wiki $site
	 * @param string $article
	 * @param string $begin
	 * @param string $end
	 * @param unknown $noredirects
	 */
	function __construct( &$dbr, &$site, $article, $begin, $end, $noredirects ){
#echo $article;		
		try {
			$api = $site->initPage( $article, null, $noredirects );
		}
		catch( Exception $e ) {
			$this->error = $e->getMessage();
			return;
		}
		
		$this->pagetitle = $api->get_title( false );
		$this->pagetitleFull = $api->get_title( true );
		$this->pageid = $api->get_id();
		$this->namespace = $api->get_namespace();
		
		$this->begin = $begin;
		$this->end = $end;
		
		if( !$this->pagetitle || !$this->pageid ) {
			$this->error = 'nosuchpage' ;
			return;
		}
#print_r($this);		
		$this->fetchWikidataInfo( $api );
		
		$this->fetchHistoryRecordsDB( $dbr );
		$this->fetchLogRecordsDB( $dbr );
		$this->loadAEBTypes();

		$this->parseLogs();
		$this->parseHistory();

		unset( $pageClass );
		//not yet implemented in the object, so call it from outside
		#$this->parseHistory($history, $start, $end, $site, $pageClass);
		global $perflog;
		array_push( $perflog->stack, $this->perflog);
		
	}
	
	function fetchHistoryRecordsDB( &$dbr ) {
		$pstart = microtime(true);
		
		$start = $end = false;
	
		if( $this->begin ) {
			$conds[] = 'UNIX_TIMESTAMP(rev_timestamp) > ' . $dbr->strencode( strtotime( $this->begin ) );
		}
		if( $this->end) {
			$conds[] = 'UNIX_TIMESTAMP(rev_timestamp) < ' . $dbr->strencode( strtotime( $this->end ) );
		}
	
		try {
			$this->history = $dbr->query("
						SELECT rev_user_text, rev_user, rev_timestamp, rev_comment, rev_minor_edit, rev_len
						FROM revision_userindex
						WHERE rev_page = '$this->pageid' AND rev_timestamp > 1 
						LIMIT 20000
					");
		} catch( Exception $e ) {
			$this->error = $e->getMessage();
			return;
		}

		$this->historyCount = count( $this->history );	
		
		$this->perflog[] = array(__FUNCTION__, microtime(true)-$pstart );
	}
	
	function fetchLogRecordsDB( &$dbr ){
		$pstart = microtime(true);
		
		$title = $dbr->strencode( $this->pagetitle );
		$query = "
				SELECT log_action as action, log_timestamp as timestamp 
				FROM logging_logindex 
				WHERE log_namespace = '$this->namespace' AND log_title = '$title' AND log_timestamp > 1
					AND log_type in ('delete', 'move', 'protect')	
			";
		$this->tmpLogs = $dbr->query ( $query );
		
		
		$this->perflog[] = array(__FUNCTION__, microtime(true)-$pstart );
	}
	
	function fetchLogRecordsApi( $site ){
		#$logsi = $site->logs( null, false, $this->pagetitle, strtotime( $this->begin ), strtotime( $this->end ), 'older', false, array( 'type', 'timestamp', 'user', 'details' ) );
	}
	
	function fetchWikidataInfo( &$api ){
		$pstart = microtime(true);
		global $dbUser, $dbPwd;
		
		$this->wikidatalink = '–';
		
		$props = $api->get_properties();
		$wbitem = str_replace("Q", "", $props[0]["pageprops"]["wikibase_item"]);
		if ( intval($wbitem) == 0) { return; }
				
		try{
			$dbwikidata = new Database( "wikidatawiki.labsdb", $dbUser, $dbPwd, "wikidatawiki_p");
			
			$query = "
					SELECT ips_site_id, ips_site_page 
					From wb_items_per_site
					WHERE ips_item_id = '$wbitem' 
				";
			
			$this->wikidataItems = $dbwikidata->query( $query );
			$this->wikidatalink = '<span><a href="//www.wikidata.org/wiki/Q'.$wbitem.'#sitelinks-wikipedia" >Q'.$wbitem.'</a> &bull; ('.count($this->wikidataItems).')</span>';
			$dbwikidata->close();
		}
		catch (DBError $e){
			echo $e->getMessage();
		}
		
		$this->perflog[] = array(__FUNCTION__, microtime(true)-$pstart );
	}
	
	function parseLogs (){
		$pstart = microtime(true);
		
		foreach( $this->tmpLogs as $log ) {
			if( !in_array( $log['action'], array( 'revision' ) ) ) {
				
				$time = date('nY', strtotime( $log['timestamp'] ) );
				if( !isset( $this->pageLogs[ $time ][$log['action'] ] ) ) {
					$this->pageLogs[ $time ][$log['action']] = 0;
				}
				$this->pageLogs[ $time ][$log['action']]++;
			}
		}
		unset( $this->tmpLogs );
			
		foreach( $this->pageLogs as $date => $log ) {
			arsort( $log );
			$this->pageLogs[$date] = $this->actionParse( $date, $log );
		}
		
		$this->perflog[] = array(__FUNCTION__, microtime(true)-$pstart );
	}

	public function parseHistory() {
		$pstart = microtime(true);
		
		if ( count($this->history) == 0 ){ 
			$this->error = "no records";
			return;
		}
	
		//Now we can start our master array. This one will be HUGE!
		$data = array(
			'first_edit' => array(
				'timestamp' => $this->history[0]['rev_timestamp'],
				'user' => $this->history[0]['rev_user_text']
			),
			'year_count' => array(),
			'count' => 0,
			'editors' => array(),
			'anons' => array(),
			'year_count' => array(),
			'minor_count' => 0,
			'count_history' => array( 'today' => 0, 'week' => 0, 'month' => 0, 'year' => 0 )
		);
		
		$first_edit_parse = date_parse( $data['first_edit']['timestamp'] );
	
	
	
	
		//And now comes the logic for filling said master array
		foreach( $this->history as $id => $rev ) {
			$data['last_edit'] = $rev['rev_timestamp'];
			$data['count']++;
			
			//Sometimes, with old revisions (2001 era), the revisions from 2002 come before 2001
			if( strtotime( $rev['rev_timestamp'] ) < strtotime( $data['first_edit']['timestamp'] ) ) {	
				$data['first_edit'] = array(
					'timestamp' => $rev['rev_timestamp'],
					'user' => htmlspecialchars( $rev['rev_user_text'] )
				);
				
				$first_edit_parse = date_parse( $data['first_edit']['timestamp'] );
			}
			
			
			$timestamp = date_parse( $rev['rev_timestamp'] );
			
			
			//Fill in the blank arrays for the year and 12 months
			if( !isset( $data['year_count'][$timestamp['year']] ) ) {
				$data['year_count'][$timestamp['year']] = array( 'all' => 0, 'minor' => 0, 'anon' => 0, 'months' => array() );
				
				for( $i = 1; $i <= 12; $i++ ) {
					$data['year_count'][$timestamp['year']]['months'][$i] = array( 'all' => 0, 'minor' => 0, 'anon' => 0, 'size' => array() );
				}
			}
			
			//Increment counts
			$data['year_count'][$timestamp['year']]['all']++;
			$data['year_count'][$timestamp['year']]['months'][$timestamp['month']]['all']++;
			$data['year_count'][$timestamp['year']]['months'][$timestamp['month']]['size'][] = number_format( ( $rev['rev_len'] / 1024 ), 2 );
			
			
			//Now to fill in various user stats
			$username = htmlspecialchars($rev['rev_user_text']);
			if( !isset( $data['editors'][$username] ) ) {
				$data['editors'][$username] = array( 	
					'all' => 0, 
					'minor' => 0, 
					'first' => date( 'Y-m-d, H:i', strtotime( $rev['rev_timestamp'] ) ), 
					'last' => null, 
					'atbe' => null, 
					'minorpct' => 0, 
					'size' => array(), 
					'urlencoded' => str_replace( array( '+' ), array( '_' ), urlencode( $rev['rev_user_text'] ) )
				);
			}
			
			//Increment these counts...
			$data['editors'][$username]['all']++;	
			$data['editors'][$username]['last'] = date( 'Y-m-d, H:i', strtotime( $rev['rev_timestamp'] ) );	
			$data['editors'][$username]['size'][] = number_format( ( $rev['rev_len'] / 1024 ), 2 );
			
			if( !$rev['rev_user'] ) {
				//Anonymous, increase counts
				$data['anons'][] = $username;
				$data['year_count'][$timestamp['year']]['anon']++;
				$data['year_count'][$timestamp['year']]['months'][$timestamp['month']]['anon']++;
			}
			
			if( $rev['rev_minor_edit'] ) {
				//Logged in, increase counts
				$data['minor_count']++;
				$data['year_count'][$timestamp['year']]['minor']++;
				$data['year_count'][$timestamp['year']]['months'][$timestamp['month']]['minor']++;
				$data['editors'][$username]['minor']++;	
			}
			
			foreach ( $this->AEBTypes as $tool => $signature ){
				if ( preg_match( $signature["regex"], $rev["rev_comment"]) ){
					$data['automated_count']++;
					$data['year_count'][$timestamp['year']]['automated']++;
					$data['year_count'][$timestamp['year']]['months'][$timestamp['month']]['automated']++;
					$data['tools'][$tool]++;
					break;
				}
			}
			
			//Increment "edits per <time>" counts
			if( strtotime( $rev['rev_timestamp'] ) > strtotime( '-1 day' ) ) $data['count_history']['today']++;
			if( strtotime( $rev['rev_timestamp'] ) > strtotime( '-1 week' ) ) $data['count_history']['week']++;
			if( strtotime( $rev['rev_timestamp'] ) > strtotime( '-1 month' ) ) $data['count_history']['month']++;
			if( strtotime( $rev['rev_timestamp'] ) > strtotime( '-1 year' ) ) $data['count_history']['year']++;
			
		}
	
	
	//Fill in years with no edits
		for( $year = $first_edit_parse['year']; $year <= date( 'Y' ); $year++ ) {
			if( !isset( $data['year_count'][$year] ) ) {
				$data['year_count'][$year] = array( 'all' => 0, 'minor' => 0, 'anon' => 0, 'months' => array() );
				
				for( $i = 1; $i <= 12; $i++ ) {
					$data['year_count'][$year]['months'][$i] = array( 'all' => 0, 'minor' => 0, 'anon' => 0, 'size' => array() );
				}
			}
		}
	
	
	//Add more general statistics
		$data['totaldays'] = floor( ( strtotime( $data['last_edit'] ) - strtotime( $data['first_edit']['timestamp'] ) ) / 60 / 60 / 24 );
		$data['average_days_per_edit'] = number_format( $data['totaldays'] / $data['count'], 2 );
		$data['edits_per_month'] = ( $data['totaldays'] ) ? number_format( $data['count'] / ( $data['totaldays'] / ( 365/12 ) ), 2 ) : 0;
		$data['edits_per_year'] =( $data['totaldays'] ) ? number_format( $data['count'] / ( $data['totaldays'] / 365 ) , 2 ) : 0;
		$data['edits_per_editor'] = number_format( $data['count'] / count( $data['editors'] ) , 2 );
		$data['editor_count'] = count( $data['editors'] );
		$data['anon_count'] = count( $data['anons'] );
	
	
	//Various sorts
		arsort( $data['editors'] );
		ksort( $data['year_count'] );
	
	
	
	//Fix the year counts
		$num = 0;
		$cum = 0;
		$scum = 0;
		
		foreach( $data['year_count'] as $year => $months ) {
			
			//Unset months before the first edit and after the last edit
			foreach( $months['months'] as $month => $tmp ) {
				if( $year == $first_edit_parse['year'] ) {
					if( $month < $first_edit_parse['month'] ) unset( $data['year_count'][$year]['months'][$month] );
				}
				if( $year == date( 'Y' ) ) {
					if( $month > date( 'm' ) ) unset( $data['year_count'][$year]['months'][$month] );
				}
			}
			
			
			//Calculate anon/minor percentages
			$data['year_count'][$year]['pcts']['anon'] = ( $data['year_count'][$year]['all'] ) ? number_format( ( $data['year_count'][$year]['anon'] / $data['year_count'][$year]['all'] ) * 100, 2 ) : 0.00;
			$data['year_count'][$year]['pcts']['minor'] = ( $data['year_count'][$year]['all'] ) ? number_format( ( $data['year_count'][$year]['minor'] / $data['year_count'][$year]['all'] ) * 100, 2 ) : 0.00;
			
			
			//Continue with more stats...
			foreach( $data['year_count'][$year]['months'] as $month => $tmp ) {
			
				//More percentages...
				$data['year_count'][$year]['months'][$month]['pcts']['anon'] = ( $tmp['all'] ) ? number_format( ( $tmp['anon'] / $tmp['all'] ) * 100, 2 ) : 0.00;
				$data['year_count'][$year]['months'][$month]['pcts']['minor'] = ( $tmp['all'] ) ? number_format( ( $tmp['minor'] / $tmp['all'] ) * 100, 2 ): 0.00;
				
				//XID and cumulative are used in the flash graph
				$data['year_count'][$year]['months'][$month]['xid'] = $num;
				$data['year_count'][$year]['months'][$month]['cumulative'] = $cum + $tmp['all'];
				
				if( count( $tmp['size'] ) ) {
					$data['year_count'][$year]['months'][$month]['size'] = number_format( ( array_sum( $tmp['size'] ) / count( $tmp['size'] ) ), 2 );
				}
				else {
					$data['year_count'][$year]['months'][$month]['size'] = 0;
				}
				
				$data['year_count'][$year]['months'][$month]['sizecumulative'] = $scum + $data['year_count'][$year]['months'][$month]['size'];
				$num++;
				$cum += $tmp['all'];
				$scum += $data['year_count'][$year]['months'][$month]['size'];
			}
		}
	
	
	//Top 10% info
		$data['top_ten'] = array( 'editors' => array(), 'count' => 0 );
		$data['top_fifty'] = array();
	
	
	//Now to fix the user info...
		$tmp = $tmp2 = 0;
		foreach( $data['editors'] as $editor => $info ) {
			
			//Is the user in the top 10%?
			if( $tmp <= (int)( count( $data['editors'] ) * 0.1 ) ) {
				$data['top_ten']['editors'][] = $editor;
				$data['top_ten']['count'] += $info['all'];
				
				$tmp++;
			}
			
			//Is the user in the 50 highest editors?
			if( $tmp < 50 ) {
				$data['top_fifty'][] = $editor;
			}
			
			$data['editors'][$editor]['minorpct'] = ( $info['all'] ) ? number_format( ( $info['minor'] / $info['all'] ) * 100, 2 ): 0.00;
			
			if( $info['all'] > 1 ) {
#				##$data['editors'][$editor]['atbe'] = WebTool::getTimeString( (int)( ( strtotime( $info['last'] ) - strtotime( $info['first'] ) ) / $info['all'] ));
				$secs = intval( ( strtotime( $info['last'] ) - strtotime( $info['first'] ) ) / $info['all'] );
				$data['editors'][$editor]['atbe'] = number_format( $secs/(60*60*24),1 ).' {#days#}';
			}
			
			if( count( $info['size'] ) ) {
				$data['editors'][$editor]['size'] = number_format( ( array_sum( $info['size'] ) / count( $info['size'] ) ), 2 );
			}
			else {
				$data['editors'][$editor]['size'] = 0;
			}
			
			$tmp2++;
		}
		
		$this->data = $data;
#print_r($this->data);		
		$this->perflog[] = array(__FUNCTION__, microtime(true)-$pstart );
	}
	
	
	/**
	 * Generate the log actions infobox 
	 * @param string $date
	 * @param string $logs
	 * @return array
	 */
	private function actionParse( $date, $logs ) {
	
		if( strlen( $date ) == 5 ) {
			$parseddate = '0' . substr( $date, 0, 1 ) . '/' . substr( $date, 1 );
		}
		else {
			$parseddate = substr( $date, 0, 2 ) . '/' . substr( $date, 2 );
		}
	
		$ret['duringdate'] = $parseddate ;
	
		foreach( $logs as $type => $count ) {
			$ret[$type] = $count;
		}
	
		return $ret ;
	}
	
	
	/**
	 * Calculate how many pixels each year should get for the Edits per Year table
	 * @param unknown $data
	 * @return Ambigous <multitype:multitype: , number>
	 */
	public function getYearPixels() {
		
		$month_total_edits = array();

		foreach( $this->data['year_count'] as $year => $tmp ) {
			$month_total_edits[$year] = $tmp['all'];
		}
	
		$max_width = max( $month_total_edits );
	
		$pixels = array();
		foreach( $this->data['year_count'] as $year => $tmp ) {
			if( $tmp['all'] == 0 ) $pixels[$year] = array();
				
			$processarray = array( 'all' => $tmp['all'], 'anon' => $tmp['anon'], 'minor' => $tmp['minor'] );
				
			asort( $processarray );
				
			foreach( $processarray as $type => $count ) {
				$newtmp = ceil( 500 * ( $count ) / $max_width );
				$pixels[$year][$type] = $newtmp;
			}
		}
	
		return $pixels;
	}
	
	
	/**
	 * Calculate how many pixels each month should get for the Edits per Month table
	 * @param unknown $data
	 * @return multitype:
	 */
	function getMonthPixels() {
		
		$month_total_edits = array();
		
		foreach( $this->data['year_count'] as $year => $tmp ) {
			foreach( $tmp['months'] as $month => $newdata ) {
				$month_total_edits[ $month.'/'.$year ] = $newdata['all'];
			}
		}
	
		$max_width = max( $month_total_edits );
	
		$pixels = array();
		foreach( $this->data['year_count'] as $year => $tmp ) {
			foreach( $tmp['months'] as $month => $newdata ) {
				if( $tmp['all'] == 0 ) $pixels[$year][$month] = array();
	
				$processarray = array( 'all' => $newdata['all'], 'anon' => $newdata['anon'], 'minor' => $newdata['minor'] );
	
				asort( $processarray );
	
				foreach( $processarray as $type => $count ) {
					$newtmp = ceil( ( 500 * ( $count ) / $max_width ) );
					$pixels[$year][$month][$type] = $newtmp;
				}
			}
		}
	
		return $pixels;
	}
	

	/**
	 * Returns a list of even years, used to generate contrasting colors for the Edits/Month table 
	 * @param unknown $years
	 * @return Ambigous <string, multitype:>
	 */
	public function getEvenYears() {
		$years = array_keys( $this->data['year_count'] );
		$years = array_flip( $years );
		
		foreach( $years as $year => $id ) {
			$years[$year] = "5";
			if( $year % 2 == 0 ) unset( $years[$year] );
		}
		return $years;
	}
	
	/**
	 * Load AEB Types from counter, to parse (semi) automated edits
	 */
	function loadAEBTypes(){
		try {
			require_once 'Counter.php';
			$this->AEBTypes = Counter::getAEBTypes();
		}
		catch (Exception $e){
			$this->AEBTypes = null;
		}

		
	}
}