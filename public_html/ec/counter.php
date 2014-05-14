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
	public $http;
	public $iName;  //input username
	
	private $mName;
	private $mIP;
	private $mExists;
	private $mUID;
	private $mRegistration;
	
	private $mReverted;
	private $mDeleted;
	private $mLive;
	private $mTotal;
	private $mGroups;
	private $mMonthTotals = array();
	private $mNamespaceTotals = array();
	private $mUniqueArticles = array( 'total', 'namespace_specific' );
	private $mFirstEdit;
	private $mAveragePageEdits;
	
	private $chMonthly;
	
	function __construct( $user, $wikibase ) {
		
		$this->http = new HTTP();
		$this->baseurl = 'http://'.$wikibase;
		$this->apibase = $this->baseurl.'/w/api.php?';
		
		$this->iName = $user;
		
		$this->checkIP();
		
#		$this->checkExists();
		$this->getUserInfo();
		
		$this->getCounts();
		$this->getRevertedEdits();
	
		$this->getStats();
		
		$this->fillMonthList();

	}

	
	function checkIP() {
		$this->mIP = ( long2ip( ip2long( $this->mName ) ) == $this->mName ) ? true : false;
	}
	
	function getGlobalUserInfo(){
		$query = "/w/api.php?action=query&meta=globaluserinfo&format=json&guiuser=Hedonil&guiprop=groups%7Crights%7Cmerged%7Cunattached%7Ceditcount";
	}
	
	function getUserInfo(){

		$data = array(
			'action' => 'query',
			'list' 	 => 'users',
			'format' => 'json',
			'usprop' => 'blockinfo|groups|implicitgroups|editcount|registration',
			'ususers'=> $this->iName
		);

		$res = json_decode( $this->http->get( $this->apibase.http_build_query($data)) );
		
		if ( isset( $res->query->users[0]->userid) ){
			$this->mUID = $res->query->users[0]->userid;
			$this->mName = urldecode($res->query->users[0]->name);
			$this->mGroups = $res->query->users[0]->groups;
			$this->mRegistration = $res->query->users[0]->registration;
			$this->mExists = true;
		}
		else {
			$this->mExists = false;
		}

	}
		
	function checkExists() {
		global $perflog; $start = microtime(true);
		global $dbr;
		
		if( $this->mIP ) {
			$this->mExists = true;
			$this->mUID = 0;
		}
		else {
			
			$res = $dbr->query("Select user_id FROM user WHERE user_name = '$this->mName' ");
			$res = $res->endArray;

			if( !count( $res ) ) {
				$this->mExists = false;
				$this->mUID = 0;
			}
			else {
				$this->mExists = true;
				$this->mUID = $res[0]['user_id'];
			}
			
			unset($res);
		}
		$perflog->add('checkExists', microtime(true)-$start );
	}

	
	function getCounts() {
		global $perflog; $start = microtime(true);
		global $dbr;
		
		if ( intval($this->mUID) != 0 && $this->mUID == intval($this->mUID ) ){
			$res = $dbr->query("
					SELECT 'arc' AS text, COUNT(*) AS count FROM archive_userindex WHERE ar_user = '$this->mUID' AND ar_timestamp > 1 
					UNION
					SELECT 'rev' AS text, COUNT(*) AS count FROM revision_userindex WHERE rev_user = '$this->mUID' AND rev_timestamp > 1 
				");
		}
		elseif ($this->mIP ){
			$res = $dbr->query("
					SELECT 'arc' AS text, COUNT(*) AS count FROM archive_userindex WHERE ar_user_text = '$this->mName' 
					UNION
					SELECT 'rev' AS text, COUNT(*) AS count FROM revision_userindex WHERE rev_user_text = '$this->mName'
				");
		}
		$res = $res->endArray;
		
		$this->mDeleted = $res[0]['count'];
		$this->mLive = $res[1]['count'];
		
		$this->mTotal = $this->mLive + $this->mDeleted;
		
		unset($res);
		$perflog->add(__FUNCTION__, microtime(true)-$start );
	}
	
	function getRevertedEdits() {
		global $perflog; $start = microtime(true);
		global $dbr;

		$res = $dbr->query("SELECT frp_user_params FROM flaggedrevs_promote where frp_user_id = '$this->mUID' ");
		$res = $res->endArray;
		
		if ( 1 === preg_match('/revertedEdits=([0-9]+)/', $res[0]['frp_user_params'], $capt)){
			$this->mReverted = $capt[1];
		}
		
		unset($res);
		$perflog->add(__FUNCTION__, microtime(true)-$start );
	}
	
	function getStats() {
		global $perflog; $start = microtime(true);
		global $dbr, $wgNamespaces;
		
		$res = $dbr->query("
				SELECT rev_timestamp, UNIX_TIMESTAMP(rev_timestamp) as rev_timestamp2, page_title, page_namespace 
				FROM revision_userindex 
				JOIN page ON page_id = rev_page 
				WHERE rev_user = '$this->mUID' AND rev_timestamp > 1
				/*SLOW_OK RUN_LIMIT 60 NM*/ 
				ORDER BY rev_timestamp ASC
			");
		$res = $res->endArray;
		
		$base_ns = array();

		foreach( $wgNamespaces['names'] as $id => $name ) {
			$this->mNamespaceTotals[$id] = 0;
			$base_ns[$id] = 0;
		}
		
		foreach ( $res as $u => $row ) {
			$this->mNamespaceTotals[ $row['page_namespace'] ]++;
			
			$timestamp = substr( $row['rev_timestamp'], 0, 4 ) . '/' . substr( $row['rev_timestamp'], 4, 2 );
			
			if( !isset( $this->mMonthTotals[$timestamp] ) ) {
				$this->mMonthTotals[$timestamp] = $base_ns;
			}
			
			$this->mMonthTotals[$timestamp][ $row['page_namespace'] ]++;
			
			if( !$this->mFirstEdit ) {
				$this->mFirstEdit = date('Y-m-d H:i:s', $row["rev_timestamp2"] );
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
		}

		//print_r($this->mUniqueArticles);
		
		$this->mAveragePageEdits = number_format( ( $this->mTotal ? $this->mTotal / count( $this->mUniqueArticles['total'] ) : 0 ), 2 );
		
		//Well that sucked. This just fills the mMonthTotals array with all the months that have passed since the users last edit, if they haven't edited in over a month. Instead of appearing as though the user edited this month, it now is obvious they haven't edited in months
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
		$perflog->add(__FUNCTION__, microtime(true)-$start );
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

	
	function isOptedOut( $http, $user ) {
		$x = unserialize( $this->http->get( $this->apibase . 'action=query&prop=revisions&titles=User:'.urlencode($user).'/EditCounterOptOut.js&rvprop=content&format=php' ) );
	
		foreach( $x['query']['pages'] as $page ) {
			if( !isset( $page['revisions'] ) ) {
	
				$x = unserialize( $this->http->get( '//meta.wikimedia.org/w/api.php?action=query&prop=revisions&titles=User:'.urlencode($user).'/EditCounterGlobalOptOut.js&rvprop=content&format=php' ) );
				foreach( $x['query']['pages'] as $page ) {
					if( !isset( $page['revisions'] ) ) {
						$x = unserialize( $this->http->get( $this->baseurl . 'api.php?action=query&prop=revisions&titles=User:'.urlencode($user).'/Editcounter&rvprop=content&format=php' ) );
						foreach( $x['query']['pages'] as $page ) {
							if( !isset( $page['revisions'] ) ) {
								return false;
							}
							elseif( strpos( $page['revisions'][0]['*'], "Month-Graph:no" ) !== FALSE ) {
								return true;
							}
						}
					}
					elseif( $page['revisions'][0]['*'] != "" ) {
						return true;
					}
				}
			}
			elseif( $page['revisions'][0]['*'] != "" ) {
				return true;
			}
		}
	}
	
	function isOptedIn( $user ) {
		$x = unserialize( $this->http->get( $this->apibase . 'action=query&prop=revisions&titles=User:'.urlencode($user).'/EditCounterOptIn.js&rvprop=content&format=php' ) );
	
		foreach( $x['query']['pages'] as $page ) {
			if( !isset( $page['revisions'] ) ) {
	
				$x = unserialize( $this->http->get( 'http://meta.wikimedia.org/w/api.php?action=query&prop=revisions&titles=User:'.urlencode($user).'/EditCounterGlobalOptIn.js&rvprop=content&format=php' ) );
				foreach( $x['query']['pages'] as $page ) {
					if( !isset( $page['revisions'] ) ) {
						$x = unserialize( $this->http->get( $this->baseurl . 'api.php?action=query&prop=revisions&titles=User:'.urlencode($user).'/Editcounter&rvprop=content&format=php' ) );
						foreach( $x['query']['pages'] as $page ) {
							if( !isset( $page['revisions'] ) ) {
								return false;
							}
							elseif( strpos( $page['revisions'][0]['*'], "Month-Graph:yes" ) !== FALSE ) {
								return true;
							}
						}
					}
					elseif( $page['revisions'][0]['*'] != "" ) {
						return true;
					}
				}
			}
			elseif( $page['revisions'][0]['*'] != "" ) {
				return true;
			}
		}
	
		return false;
	}
	
	function getWhichOptIn( $user ) {
		$x = unserialize( $this->http->get( $this->baseurl . 'api.php?action=query&prop=revisions&titles=User:'.urlencode($user).'/EditCounterOptIn.js&rvprop=content&format=php' ) );
	
		foreach( $x['query']['pages'] as $page ) {
			if( !isset( $page['revisions'] ) ) {
	
				$x = unserialize( $this->http->get( 'http://meta.wikimedia.org/w/api.php?action=query&prop=revisions&titles=User:'.urlencode($user).'/EditCounterGlobalOptIn.js&rvprop=content&format=php' ) );
				foreach( $x['query']['pages'] as $page ) {
					if( !isset( $page['revisions'] ) ) {
						$x = unserialize( $this->http->get( $this->baseurl . 'api.php?action=query&prop=revisions&titles=User:'.urlencode($user).'/Editcounter&rvprop=content&format=php' ) );
						foreach( $x['query']['pages'] as $page ) {
							if( !isset( $page['revisions'] ) ) {
								return "false";
							}
							elseif( strpos( $page['revisions'][0]['*'], "Month-Graph:yes" ) !== FALSE ) {
								return "interiot";
							}
						}
					}
					elseif( $page['revisions'][0]['*'] != "" ) {
						return "globally";
					}
				}
			}
			elseif( $page['revisions'][0]['*'] != "" ) {
				return "locally";
			}
		}
	
		return "false";
	}
	
	/**
	 * @param object $http
	 * @return Ambigous <multitype:multitype: , unknown>
	 */
	function getNamespaces() {
	
		$x = unserialize( $this->http->get( $this->apibase . 'action=query&meta=siteinfo&siprop=namespaces&format=php' ) );
	
		unset( $x['query']['namespaces'][-2] );
		unset( $x['query']['namespaces'][-1] );
	
		$res = array(
				'ids' => array(),
				'names' => array()
		);
	
		foreach( $x['query']['namespaces'] as $id => $ns ) {
			$nsname = ( $ns['*'] == "" ) ? 'Main' : $ns['*'];
			$res['ids'][$nsname] = $id;
			$res['names'][$id] =  $nsname;
		}

		return $res;
		
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
	
	function getDeleted() {
		return $this->mDeleted;
	}
	
	function getReverted() {
		return $this->mReverted;
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
}
