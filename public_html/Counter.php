<?php

/*
Soxred93's Edit Counter
Copyright (C) 2010 Soxred93

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

Completely rewritten and adjusted
(C) 2014 Hedonil
 
*/

class Counter {
	
	public $baseurl;
	public $apibase;
#	public $http;
	public $mNamespaces = array( 'ids' => array(),	'names' => array() );
	
	private $mIP;
	private $mExists;
	
	public $mUID;
	public $mName;
	public $mRegistration;
	public $mGroups;
	public $mGroupsGlobal;
	public $mHomeWiki;
	public $mRegisteredWikis;

	public $mFirstEdit;
	public $mLatestEdit;
	
	public $mUnique;
	public $mReverted;
	public $mDeleted;
	public $mAutoEdits;
	public $mUploaded;
	public $mUploadedCommons;
	
	public $mMoved;
	public $mApprove;
	public $mUnapprove;
	public $mThanked;
	public $mPatrol;
	public $mBlock;
	public $mUnblock;
	public $mProtect;
	public $mUnprotect;
	public $mDeletePage;
	public $mDeleteRev;
	
	public $mCreated;
	public $mLive;
	public $mTotal;
	public $mTotalGlobal;
	public $mAveragePageEdits;
	
	private $mMonthTotals = array();
	private $mNamespaceTotals = array();
	private $mUniqueArticles = array( 'total', 'namespace_specific' );
	
	public $mAutoEditTools = array();

	private $logs = array();
	private $revs = array();
	
	private $optinPages = array();
	public $optin = false;
	
	public $wikis = array();

	private $perflog;
	
	private $data;
	
	function __construct( &$dbr, $user, $wikibase, $noautorun=false ) {
		
#		$this->http = new HTTP();
		$this->baseurl = 'http://'.$wikibase;
		$this->apibase = $this->baseurl.'/w/api.php?';
		
		$this->mName = $user;
		
		$this->getUserInfo( $dbr );
		$this->checkOptin();
		
		if ( !$this->mExists || $noautorun ) return ;
		
		$this->fetchData($dbr);
		$this->parseRevisions();
		$this->parseLogs();
		
#		$this->fillMonthList();


		global $perflog;
		array_push( $perflog->stack, $this->perflog);
	}

	
	function getUserInfo( $dbr ){
		$pstart = microtime(true);
		
		$this->mIP = ( long2ip( ip2long( $this->mName ) ) == $this->mName ) ? true : false;
		
		if( $this->mIP ) {
			$this->mExists = true;
			$this->mUID = 0;
		}
		else{
			
			//Get lokal user info
			$data = array(
					'action' => 'query',
					'list' 	 => 'users',
					'format' => 'json',
					'usprop' => 'blockinfo|groups|implicitgroups|editcount|registration',
					'ususers'=> $this->mName
				);
			$query[] = array( "type" => "api", "src" => "", "query" => $this->apibase.http_build_query( $data ) );
			
			//GEt global suser info
			$data = array(
					'action' => 'query',
					'meta' => 'globaluserinfo',
					'format' => 'json',
					'guiprop' => 'groups|rights|merged|unattached|editcount',
					'guiuser' => $this->mName,
				);
			$query[] = array( "type" => "api", "src" => "", "query" => $this->apibase.http_build_query( $data ) );

			//Get Namespaces
			$data = array(
					'action' => 'query',
					'meta' => 'siteinfo',
					'format' => 'json',
					'siprop' => 'namespaces',
				);
			$query[] = array( "type" => "api", "src" => "", "query" => $this->apibase.http_build_query( $data )	);
			
			//Get Optin page local
			$data = array(
					'action' => 'query',
					'prop' => 'revisions',
					'format' => 'json',
					'rvprop' => 'content',
					'rvlimit' => '1',
					'rvdir' => 'older',
					'titles' => 'User:'.$this->mName.'/EditCounterOptIn.js|User:'.$this->mName.'/EditCounterOptOut.js'
				);
			$query[] = array( "type" => "api", "src" => "", "query" => $this->apibase.http_build_query( $data )	);
			
			//Get Optin page global meta
			$data = array(
					'action' => 'query',
					'prop' => 'revisions',
					'format' => 'json',
					'rvprop' => 'content',
					'rvlimit' => '1',
					'rvdir' => 'older',
					'titles' => 'User:'.$this->mName.'/EditCounterGlobalOptIn.js'
			);
			$query[] = array( "type" => "api", "src" => "", "query" => 'http://meta.wikimedia.org/w/api.php?'.http_build_query( $data )	);

			
			$multires = $dbr->multiquery ($query );
			
			
			$res = $multires[0]->query->users[0];
			
			if ( isset( $res->userid) ){
				$this->mUID = $res->userid;
				$this->mName = urldecode($res->name);
				$this->mGroups = $res->groups;
					unset($this->mGroups[ array_search("*", $this->mGroups) ]);
				$this->mRegistration = $res->registration;
				$this->mExists = true;
			}
			else {
				$this->mExists = false;
			}
			unset($res);
			
			
			$res = $multires[1]->query->globaluserinfo;
			
			if ( isset( $res->id) ){
				$this->mGroupsGlobal = $res->groups;
				$this->mHomeWiki = $res->home;
				
				foreach ( $res->merged as $wiki ){
					$this->wikis[ $wiki->url ] = $wiki->editcount;
				}
				arsort($this->wikis);
				unset($res);
				
				$this->mRegisteredWikis = count($this->wikis);
				$this->mTotalGlobal = array_sum($this->wikis);
			}
			unset($res);
			
			$res = $multires[2]->query->namespaces;

			foreach( $res as $id => $ns ) {
				$nsname = ( $ns->{'*'} == "" ) ? 'Main' : $ns->{'*'};
				$this->mNamespaces['ids'][$nsname] = $id;
				$this->mNamespaces['names'][$id] =  $nsname;
			}
			
			$this->optinPages[] = $multires[3]->query->pages ;
			$this->optinPages[] = $multires[4]->query->pages ;
			
#print_r($this->optinPages);			
		}
		
		$this->perflog[] = array(__FUNCTION__, microtime(true)-$pstart );
	}

//
// ****************************           fetch data          **********************
//	


	function fetchData ( &$dbr ){
		$pstart = microtime(true);
		
		$where = ( $this->mIP ) ? "rev_user_text = '$this->mName' " : "rev_user = '$this->mUID' AND rev_timestamp > 1 ";
		$query[] = array(
				"type" => "db",
				"src" => "this",
				"query" => "
					SELECT rev_timestamp, page_title, page_namespace, rev_comment, rev_parent_id
					FROM revision_userindex 
					JOIN page ON page_id = rev_page 
					WHERE $where
				"
			);
		
		$where = ( $this->mIP ) ? "log_user_text = '$this->mName' " : "log_user = '$this->mUID' AND log_timestamp > 1";
		$query[] = array(
				"type" => "db",
				"src" => "this",
				"query" => "
					SELECT log_type, log_action
					FROM logging_userindex
					WHERE $where
				"
			);

		$where = ( $this->mIP ) ? "ar_user_text = '$this->mName' " : "ar_user = '$this->mUID' AND ar_timestamp > 1";
		$query[] = array(
				"type" => "db",
				"src" => "this",
				"query" => "
					SELECT COUNT(*) AS count
					FROM archive_userindex
					WHERE $where
				"
			);
		
		$where = ( $this->mIP ) ? "frp_user_id = '999999999' " : "frp_user_id = '$this->mUID' ";
		$query[] = array(
				"type" => "db",
				"src" => "this",
				"query" => "
					SELECT frp_user_params 
					FROM flaggedrevs_promote 
					WHERE $where 
				"
			);
		
		$where = ( $this->mIP ) ? "username = '**999999999' " : "user_name= '".$dbr->strencode($this->mName)."' ";
		$query[] = array(
				"type" => "db",
				"src" => "commonswiki",
				"query" => "
					SELECT count(log_type) as count 
					FROM logging_userindex  
					JOIN user on user_id = log_user  
					WHERE log_type = 'upload' AND $where
				"
			);
#print_r($query);		
		$this->data = $dbr->multiquery( $query );		

		$this->revs = &$this->data[0];
		$this->logs = &$this->data[1];
		$this->mDeleted = $this->data[2][0]["count"];
		if ( 1 === preg_match('/revertedEdits=([0-9]+)/', $this->data[3][0]['frp_user_params'], $capt)){ $this->mReverted = $capt[1]; }
		$this->mUploadedCommons = $this->data[4][0]["count"];
#print_r($ff[1]);

		$this->perflog[] = array(__FUNCTION__, microtime(true)-$pstart );
	}
	
	function parseLogs(){
		$pstart = microtime(true);
		
		foreach ($this->logs as $log ){
			
			if ( $log["log_type"] == "move"){
				$this->mMoved++;
			}
			if ( $log["log_type"] == "upload"){
				$this->mUploaded++;
			}
			if ( $log["log_type"] == "review" && $log["log_action"] == "approve" ){
				$this->mApprove++;
			}
			if ( $log["log_type"] == "review" && $log["log_action"] == "unapprove" ){
				$this->mUnapprove++;
			}
			if ( $log["log_type"] == "thanks" && $log["log_action"] == "thank" ){
				$this->mThanked++;
			}
			if ( $log["log_type"] == "patrol" && $log["log_action"] == "patrol" ){
				$this->mPatrol++;
			}
			if ( $log["log_type"] == "block" && $log["log_action"] == "block" ){
				$this->mBlock++;
			}
			if ( $log["log_type"] == "block" && $log["log_action"] == "unblock" ){
				$this->mUnblock++;
			}
			if ( $log["log_type"] == "protect" && $log["log_action"] == "protect" ){
				$this->mProtect++;
			}
			if ( $log["log_type"] == "protect" && $log["log_action"] == "unprotect" ){
				$this->mUnprotect++;
			}
			if ( $log["log_type"] == "delete" && $log["log_action"] == "delete" ){
				$this->mDeletePage++;
			}
			if ( $log["log_type"] == "delete" && $log["log_action"] == "revision" ){
				$this->mDeleteRev++;
			}
		}
		
		$this->perflog[] = array(__FUNCTION__, microtime(true)-$pstart );
	}
	
	function parseRevisions(){
		$pstart = microtime(true);
		
// 		$base_ns = array();

// 		foreach( $wgNamespaces['names'] as $id => $name ) {
// 			$this->mNamespaceTotals[$id] = 0;
// 			$base_ns[$id] = 0;
// 		}
	
#		$knownrevs = array();

		$this->mFirstEdit  = '20991231999999';
		$this->mLatestEdit = '00000000000000';
		foreach ( $this->revs as $u => $row ) {

#			//check for duplicates (eg. filemover)
#			$hash = hash( 'crc32', $row["rev_comment"].substr( $row["rev_comment"],0 , 11) );
#			if ( in_array( $hash, $knownrevs ) ) { continue; }
#			$knownrevs[] = $hash;

			$this->mNamespaceTotals[ $row['page_namespace'] ]++;
			
			$timestamp = substr( $row['rev_timestamp'], 0, 4 ) . '/' . substr( $row['rev_timestamp'], 4, 2 );
			
			if( !isset( $this->mMonthTotals[$timestamp] ) ) {
				$this->mMonthTotals[$timestamp] = $base_ns;
			}
			
			$this->mMonthTotals[$timestamp][ $row['page_namespace'] ]++;
			
			if( $row["rev_timestamp"] < $this->mFirstEdit ) {
				$this->mFirstEdit = $row["rev_timestamp"]; 
			}
			if( $row["rev_timestamp"] > $this->mLatestEdit ) {
				$this->mLatestEdit = $row["rev_timestamp"];
			}
				
			
			if( !isset( $this->mUniqueArticles['namespace_specific'][$row['page_namespace']] ) ) {
				$this->mUniqueArticles['namespace_specific'][$row['page_namespace']] = array();
			}
			if( !isset( $this->mUniqueArticles['namespace_specific'][$row['page_namespace']][$row['page_title']] ) ) {
				$this->mUniqueArticles['namespace_specific'][$row['page_namespace']][$row['page_title']] = 0;
			}
			if( !isset( $this->mUniqueArticles['total'][$row['page_title']] ) ) {
				$this->mUniqueArticles['total'][$row['page_title']] = 0;
			}
			$this->mUniqueArticles['namespace_specific'][$row['page_namespace']][$row['page_title']]++;
			$this->mUniqueArticles['total'][$row['page_title']]++;
			
			if ( $row["rev_parent_id"] == 0 ) { $this->mCreated++ ; }
			
			foreach ( self::getAEBTypes() as $tool => $signature ){
				if ( preg_match( $signature["regex"], $row['rev_comment']) ){
					$this->mAutoEdits++;
					$this->mAutoEditTools[$tool]++;
					break;
				}
			}
			
			$this->mLive++;
			
#			unset( $this->revs[$u] );
		}

		$this->mFirstEdit = date('Y-m-d H:i:s', strtotime( $this->mFirstEdit ) );
		$this->mUnique = count( $this->mUniqueArticles['total'] );
		$this->mTotal = $this->mLive + $this->mDeleted;
		
		//print_r($this->mUniqueArticles);
		
		$this->mAveragePageEdits = number_format( ( $this->mTotal ? $this->mTotal / count( $this->mUniqueArticles['total'] ) : 0 ), 2 );
		
		//Well that sucked. This just fills the mMonthTotals array with all the months that have passed since the users last edit, 
		//if they haven't edited in over a month. Instead of appearing as though the user edited this month, it now is obvious they haven't edited in months
		if( !isset( $this->mMonthTotals[date('Y/m')] ) ) {
			//echo date('Y/m');
			$month_totals = $this->mMonthTotals;
			$last_month = strtotime(array_pop(array_keys($month_totals)).'/01');
			$now_month = strtotime(date('Y/m') . '/01');
			
			for( $i = $last_month;  $i <= $now_month; $i = strtotime( date( 'Y-m-d', $i ) . ' +1 month' ) ) {
				if( !isset( $this->mMonthTotals[date('Y/m', $i )] ) ) {
					$this->mMonthTotals[date('Y/m', $i )] = array();
				}
			}
		}
		
		ksort( $this->mNamespaceTotals);
		
		
		unset($res);
		$this->perflog[] = array(__FUNCTION__, microtime(true)-$pstart );
	}
	
	
	/**
	 * add missing months (0 edits) to the month list
	 */
	function fillMonthList() {
		$new_monthlist = array();
		$last_monthkey = null;
	
		foreach( $this->mMonthTotals as $month => $null ) {
			$str = explode( '/', $month );
			$str = strtotime( $str[0] . "-" . $str[1] . "-01" );
			if( !isset( $first_month ) ) $first_month = $str;
			$last_month = $str;
		}
	
		for( $date = $first_month; $date <= $last_month; $date += 10*24*60*60 ) {
			$monthkey = date( 'Y/m', $date );
	
			if( $monthkey != $last_monthkey ) {
				$new_monthlist[] = $monthkey;
				$last_monthkey = $monthkey;
			}
		}
	
		$monthkey = date( 'Y/m', str_replace( '/', '', $last_month ) );
	
		if( $monthkey != $last_monthkey ) {
			$new_monthlist[] = $monthkey;
			$last_monthkey = $monthkey;
		}
	
		foreach( $new_monthlist as $month ) {
			if( !isset( $this->mMonthTotals[$month] ) ) {
				$this->mMonthTotals[$month] = array();
			}
		}
	
		ksort( $this->mMonthTotals );
	}

	/**
	 * Modul for standalone call from /autoedits
	 */
	public function calcAutoEditsDB( &$dbr, $begin, $end ) {
		global $perflog; $start = microtime(true);
		
		$user = $dbr->strencode( $this->mName );
		$AEBTypes = self::getAEBTypes();

	
		$cond_begin = ( $begin ) ? 'AND UNIX_TIMESTAMP(rev_timestamp) > ' . strtotime( $begin ) : null;
		$cond_end 	= ( $end ) ? 'AND UNIX_TIMESTAMP(rev_timestamp) < ' . strtotime( $end ) : null;
	
		foreach( $AEBTypes as $toolname => $check ) {
				
			$cond_tool = " AND rev_comment ".$check['type']." '".$check['query']. "' ";
				
			$query[] .= "
					SELECT '$toolname' as toolname, count(*) as count
					FROM revision_userindex
					WHERE rev_user_text = '$user' $cond_begin $cond_end $cond_tool
				";
		}
		$query[] = " SELECT 'live' as toolname ,count(*) as count from revision_userindex WHERE rev_user_text = '$user' ";
		$query[] = " SELECT 'deleted' as toolname, count(*) as count from archive_userindex WHERE ar_user_text = '$user' ";
		
		$res = $dbr->query( implode(" UNION ", $query ) );
		
		foreach ( $res as $i => $item ){
			$contribs["tools"][ $item['toolname'] ] = $item['count'];
		}

		$contribs["editcount"] = $contribs["tools"]["live"] + $contribs["tools"]["deleted"];
		unset( $contribs["tools"]["live"], $contribs["tools"]["deleted"] );
		
		$contribs["total"] = array_sum( $contribs["tools"] );
		$contribs["pct"] = ( $contribs["total"] / $contribs["editcount"] ) *100 ;
		
		
		$perflog->add(__FUNCTION__, microtime(true)-$start );
		
		return $contribs;
	}
	
	function checkOptin(){
		foreach ($this->optinPages as $site ){
			foreach ($site as $optinPage) {
				if ( strpos ($optinPage->title, "OptOut.js") && isset($optinPage->pageid) ){
					$this->optin = false;
					break(2);
				}
				if ( strpos ($optinPage->title, "OptIn.js") && isset($optinPage->pageid) ){
					$this->optin = true;
					break(2);
				}
			}
		}
	}
	
	
	function getNamespaces(){
		return $this->mNamespaces;
	}
		
	function getMonthTotals() {
		return $this->mMonthTotals;
	}
	
	function getNamespaceTotals() {
		return $this->mNamespaceTotals;
	}
	
	function getName() {
		return $this->mName;
	}
	
	function getIP() {
		return $this->mIP;
	}
	
	function getExists() {
		return $this->mExists;
	}
	
	function getUID() {
		return $this->mUID;
	}
	
	function getCreated() {
		return $this->mCreated;
	}
	
	function getUnique() {
		return $this->mUnique;
	}
	
	function getDeleted() {
		return $this->mDeleted;
	}
	
	function getReverted() {
		return $this->mReverted;
	}
	
	function getMoved() {
		return $this->mMoved;
	}
	
	function getApproved() {
		return $this->mApproved;
	}
	
	function getThanked() {
		return $this->mThanked;
	}
	
	function getPatroled() {
		return $this->mPatroled;
	}
	
	function getLive() {
		return $this->mLive;
	}
	
	function getTotal() {
		return $this->mTotal;
	}
	
	function getGroupList() {
		return $this->mGroups;
	}
	
	function getUniqueArticles() {
		return $this->mUniqueArticles;
	}
	
	function getFirstEdit() {
		return $this->mFirstEdit;
	}
	
	function getAveragePageEdits() {
		return $this->mAveragePageEdits;
	}
	function getAutoEdits(){
		return $this->mAutoEdits;
	}
	
	static function getAEBTypes(){
		return self::$AEBTypes;
	}
			
	public static $AEBTypes = array(
				'Huggle' => array(
						'url' => '//en.wikipedia.org/wiki/WP',
						'type' => 'RLIKE',
						'query' => '.*(\[\[WP:HG\|HG\]\]|WP:Huggle).*',
						'regex' => '/.*(\[\[WP:HG\|HG\]\]|WP:Huggle).*/',
						'shortcut' => 'WP:HG'
					),
				
				'Twinkle' => array(
						'url' => '',
						'type' => 'LIKE',
						'query' => '%WP:TW%',
						'regex' => '/.*WP:TW.*/',
						'shortcut' => 'WP:TW'
					),

				'Articles For Creation tool' => array(
						'url' => '',
						'type' => 'LIKE',
						'query' => '%([[WP:AFCH|AFCH]])%',
						'regex' => '/.*\(\[\[WP:AFCH\|AFCH\]\]\).*/',
						'shortcut' => 'WP:AFCH'
					),
				
				'AutoWikiBrowser' => array(
						'url' => '',
						'type' => 'RLIKE',
						'query' => '.*(AutoWikiBrowser|AWB).*',
						'regex' => '/.*(AutoWikiBrowser|AWB).*/',
						'shortcut' => 'WP:AWB' 
					),
				
				'Friendly' => array( 
						'url' => '',
						'type' => 'LIKE', 
						'query' => '%WP:FRIENDLY%',
						'regex' => '/.*WP:FRIENDLY.*/',
						'shortcut' => 'WP:FRIENDLY'
					),
				
				'FurMe' => array( 
						'url' => '',
						'type' => 'RLIKE', 
						'query' => '.*(User:AWeenieMan/furme|FurMe).*',
						'regex' => '/.*(User:AWeenieMan\/furme|FurMe).*/',
						'shortcut' => 'WP:FURME' 
					),
				
				'Popups' => array( 
						'url' => '',
						'type' => 'LIKE', 
						'query' => '%Wikipedia:Tools/Navigation_popups%',
						'regex' => '/.*Wikipedia:Tools\/Navigation_popups.*/',
						'shortcut' => 'WP:POP' 
					),
				
				'MWT' => array( 
						'url' => '',
						'type' => 'LIKE', 
						'query' => '%User:MichaelBillington/MWT%',
						'regex' => '/.*User:MichaelBillington\/MWT.*/',
						'shortcut' => 'User:MichaelBillington/MWT' 
					),
				
				'NPWatcher' => array( 
						'url' => '',
						'type' => 'LIKE', 
						'query' => '%WP:NPW%',
						'regex' => '/.*WP:NPW.*/',
						'shortcut' => 'WP:NPW' 
					),
				
				'Amelvand' => array( 
						'url' => '',
						'type' => 'LIKE', 
						'query' => 'Reverted % edit% by % (%) to last revision by %',
						'regex' => '/^Reverted.*edit.*by .* \(.*\) to last revision by .*/',
						'shortcut' => 'User:Gracenotes/amelvand.js' 
					),
				
				'Igloo' => array( 
						'url' => '',
						'type' => 'RLIKE', 
						'query' => '.*(User:Ale_jrb/Scripts/igloo|GLOO).*',
						'regex' => '/.*(User:Ale_jrb\/Scripts\/igloo|GLOO).*/',
						'shortcut' => 'WP:IGL' 
					),
				
				'HotCat' => array( 
						'url' => '',
						'type' => 'LIKE', 
						'query' => '%(using [[WP:HOTCAT|HotCat]])%',
						'regex' => '/.*\(using \[\[WP:HOTCAT\|HotCat\]\]\).*/',
						'shortcut' => 'WP:HOTCAT' 
					),
				
				'STiki' => array( 
						'url' => '',
						'type' => 'LIKE', 
						'query' => '%STiki%',
						'regex' => '/.*STiki.*/',
						'shortcut' => 'WP:STiki' 
					),
				
				'Dazzle!' => array( 
						'url' => '',
						'type' => 'LIKE', 
						'query' => '%Dazzle!%',
						'regex' => '/.*Dazzle\!.*/',
						'shortcut' => 'WP:Dazzle!' 
					),
		);
		

}
