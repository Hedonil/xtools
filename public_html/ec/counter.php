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
*/

class Counter {
	
	private $mName;
	private $mIP;
	private $mExists;
	private $mUID;
	private $mDeleted;
	private $mLive;
	private $mTotal;
	private $mGroups;
	private $mMonthTotals = array();
	private $mNamespaceTotals = array();
	private $mUniqueArticles = array( 'total', 'namespace_specific' );
	private $mFirstEdit;
	private $mAveragePageEdits;
	
	function __construct( $user ) {
		$this->mName = $user;
		
		$this->checkIP();
		
		$this->checkExists();
		
		$this->getCounts();
		
		$this->getGroups();
	
		$this->getStats();

	}

	
	function checkIP() {
		$this->mIP = ( long2ip( ip2long( $this->mName ) ) == $this->mName ) ? true : false;
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
		
		$res = $dbr->query("SELECT COUNT(*) AS count FROM archive_userindex WHERE ar_user_text = '$this->mName' "); 
		$res = $res->endArray;
				
		$this->mDeleted = $res[0]['count'];
		
		$res = $dbr->query("SELECT COUNT(*) AS count FROM revision_userindex WHERE rev_user = '$this->mUID' AND rev_timestamp > 1 ");
		$res = $res->endArray;
		
		$this->mLive = $res[0]['count'];
		
		$this->mTotal = $this->mLive + $this->mDeleted;
		
		unset($res);
		$perflog->add(__FUNCTION__, microtime(true)-$start );
	}
	
	function getGroups() {
		global $perflog; $start = microtime(true);
		global $dbr;
		
		if( $this->mIP || !$this->mExists ) {
			$this->mGroups = array();
		}
		else {
			
			$res = $dbr->query("SELECT ug_group FROM user_groups WHERE ug_user = '$this->mUID' ");
			$res = $res->endArray;
			
			if( !count( $res ) ) {
				$this->mGroups = array();
			}
			else {
				$this->mGroups = array();
				foreach( $res as $group ) {
					$this->mGroups[] = $group['ug_group'];
				}
			}
			
			unset($res);
		}
		$perflog->add(__FUNCTION__, microtime(true)-$start );
	}
	
	function getStats() {
		global $perflog; $start = microtime(true);
		global $dbr, $wgNamespaces;
		
		$res = $dbr->query("
				SELECT rev_timestamp, page_title, page_namespace, rev_comment 
				FROM revision_userindex 
				JOIN page ON page_id = rev_page 
				WHERE rev_user_text = '$this->mName' /*SLOW_OK RUN_LIMIT 60 NM*/ 
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
				$year = substr($row['rev_timestamp'], 0, 4);
				$month = substr($row['rev_timestamp'], 4, 2);
				$day = substr($row['rev_timestamp'], 6, 2);
				$hour = substr($row['rev_timestamp'], 8, 2);
				$minute = substr($row['rev_timestamp'], 10, 2);
				$second = substr($row['rev_timestamp'], 12, 2);
				$this->mFirstEdit = date('M d, Y H:i:s', mktime($hour, $minute, $second, $month, $day, $year));
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

		unset($res);
		$perflog->add(__FUNCTION__, microtime(true)-$start );
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
