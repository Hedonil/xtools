<?php 

class RangecontribsBase{
	
		
	public function getMatchingIPs( $dbr, $ip_prefix ){
		$query = "
			SELECT rev_user_text, count(rev_user_text) as sum
			FROM revision_userindex
			WHERE rev_user_text LIKE '{$ip_prefix}%' AND rev_user = '0'
			Group by rev_user_text
			Order by INET_ATON(rev_user_text)
		";
		
		$result = $dbr->query( $query );
		
		return $result->endArray;
	}
	
	/**
	 * Get some ripe information about the IP 
	 * @param Array $matchingIPs by ref! as we modify this array here
	 * 
	 * @return array $matchingIPs
	 */
	public function getIPInformation( &$matchingIPs, $http ){
		
		$ranges = array();
		$i = 0;
		
		foreach ( $matchingIPs as $e => $matchingIP ){
			
			$ip = $matchingIP["rev_user_text"];
#echo $ip;
			$ipval = ip2long($ip);
#echo $ipval;			
			$match = false;
			foreach ( $ranges as $range ){
				if ( $ipval >= $range->minval && $ipval <= $range->maxval  ) {
					$match = true;
					break;
				}
			}
			
			if ( $match ) { continue; }
			
			$apiUrl = 'http://rest.db.ripe.net/search.json?query-string='.trim($ip).'&flags=no-irt&flags=no-referenced&flags=resource';
			$result = json_decode( $http->get( $apiUrl ) );
			$ripeAttributes = $result->objects->object[0]->attributes->attribute ;
			$i++;

			foreach ( $ripeAttributes as $u => $attribute ){
				
				if ($attribute->name == "inetnum") { 
					
					$tmpRange = explode(" - ", $attribute->value );
					$ranges[$i] = new stdClass();
					$ranges[$i]->inetnum  = $attribute->value; 
					$ranges[$i]->min 	= $tmpRange[0];
					$ranges[$i]->minval = ip2long($tmpRange[0]);
					$ranges[$i]->max 	= $tmpRange[1];
					$ranges[$i]->maxval = ip2long($tmpRange[1]);
				}
				if ($attribute->name == "netname") { $ranges[$i]->netname = $attribute->value; }
				if ($attribute->name == "descr")   { $ranges[$i]->descr   = $attribute->value; }
				if ($attribute->name == "country") { $ranges[$i]->country = $attribute->value; }
			}

			if( strval($ranges[$i]->country) == "" ){
				$apiUrl = "http://api.hostip.info/get_json.php?ip=".$ip;
				$result = json_decode( $http->get( $apiUrl ) );
				$ranges[$i]->country = $result->country_code;
			}
			
		}
#print_r($ranges);
		
		//Loop again and assign values to the ip's
		$list = "<table>";
		$oldnet = "";
		foreach ( $matchingIPs as $u => $matchingIP ){
			$ip = $matchingIP["rev_user_text"];
			$ipval = ip2long($ip);
			foreach ( $ranges as $range ){
				if ( $ipval >= $range->minval && $ipval <= $range->maxval  ) {
					$matchingIPs[$u]["inetnum"] = $range->inetnum;
					$matchingIPs[$u]["netname"] = $range->netname;
					$matchingIPs[$u]["descr"] = $range->descr;
					$matchingIPs[$u]["country"] = $range->country;
					
					if ( $oldnet != $range->inetnum ){
						$list .= "</table>";
						$list .= "<br /><span><b>Range:</b> $range->inetnum &nbsp; <b>Provider:</b> $range->netname &middot; $range->descr &middot; $range->country &nbsp;<img src=../images/flags/png/".strtolower($range->country).".png /></span>";
						$list .= "<table style='margin-left:20px;'>";
						$oldnet = $range->inetnum;
					}
					
		
					$list .= "<tr><td><a href='#".$matchingIP["rev_user_text"]."' >".$matchingIP["rev_user_text"]."</a></td><td style='text-align:center'>&nbsp;&middot;</td><td style='text-align:center'>(".$matchingIP["sum"].")</td></tr>";
					break;
				}
			}
			
		}
		$list .= "</table>";
#print_r( $matchingIPs); die;		
		return $list;
	}
	
	public function getRangeContribs( $dbr, $lang, $wiki, $matchingIPs, $ip_prefix, $cidr_info, $limit  ){
		$wikibase = $lang.".".$wiki.".org";
		
		foreach ( $matchingIPs as $matchingIP ){
			$ip = $matchingIP["rev_user_text"];
			$sum = $matchingIP["sum"];
			$query .= "UNION
				SELECT '$sum' as sum, b.page_title, b.rev_id, b.rev_user_text, UNIX_TIMESTAMP(b.rev_timestamp) as rev_timestamp, b.rev_minor_edit, b.rev_comment, b.page_namespace
				From(
				SELECT page_title, rev_id, rev_user_text, rev_timestamp, rev_minor_edit, rev_comment, page_namespace
				FROM revision_userindex
				JOIN page ON page_id = rev_page
				WHERE rev_user_text = '$ip' AND rev_user = '0'
				ORDER BY rev_user_text ASC, rev_timestamp DESC
				LIMIT 20
				) as b
			\n";
		}
		$query = substr($query, 5);
		
		$result = $dbr->query( $query );
	
		if( count($result->endArray) == 0 ) { return null; }
		
		$c = 0;
		$list = "";
		$oldip = "";
		$seccount = 0;
		foreach ( $result->endArray as $row ){

			if( $c >= $limit ) { $continue = $row['rev_timestamp'];break; }
			if( isset( $_GET['continue'] ) && $_GET['continue'] < $row['rev_timestamp'] ) continue;
			
			$tmp1 = substr( RangecontribsBase::addZero( decbin( ip2long( $row['rev_user_text'] ) ) ), 0, $cidr_info['suffix'] );
			$tmp2 = $cidr_info['shortened'];

#			if( $tmp1 !== $tmp2 ) { continue; }

			$title = $namespaces[$row['page_namespace']].$row['page_title'];
			$urltitle = $namespaces[$row['page_namespace']].urlencode($row['page_title']);
			$date = date('H:i, d.m.Y ', $row['rev_timestamp']);

			//create a new header if namespace changes
			if( $oldip != $row['rev_user_text'] ){
			
				$list .= "<tr ><td colspan=8 ><h3 id='".$row['rev_user_text']."' style='margin:15 0 5 0;'>";
				$list .= '<a href="//'.$wikibase.'/wiki/User:'.$row['rev_user_text'].'" >'.$row['rev_user_text'].'</a>';
				$list .= ' (<a href="//'.$wikibase.'/wiki/User_talk:'.$row['rev_user_text'].'" title="User talk:'.$row['rev_user_text'].'">talk</a>)';
				$list .= ' <span style="font-weight:normal"> &middot; total: '.$row["sum"].'</span>';
				$list .= '</h3></td></tr>';
				
				$oldip = $row['rev_user_text'];
				$seccount = 0;
			}
			
			$list .= "<tr>";
			$list .= "<td>&nbsp;&nbsp;&nbsp;</td>";
			$list .= '<td style="font-size:95%; white-space:nowrap;">'.$date.' &middot; </td> ';
			$list .= '<td>(<a href="//'.$wikibase.'/w/index.php?title='.$urltitle.'&amp;diff=prev&amp;oldid='.urlencode($row['rev_id']).'" title="'.$title.'">diff</a>)</td>';
			$list .= '<td>(<a href="//'.$wikibase.'/w/index.php?title='.$urltitle.'&amp;action=history" title="'.$title.'">hist</a>)</td>';
#			if( $row['rev_minor_edit'] == '1' ) { $list .= '<span class="minor">m</span>'; }
			$list .= '<td> &middot; <a href="//'.$wikibase.'/wiki/'.$urltitle.'" title="'.$title.'">'.$title.'</a>‎ ('.$row['rev_comment'].')</td> ';
			$list .= "</tr>";

			$seccount++;
			if ( $seccount == 20 && $row["sum"] > 20 ){
				$list .= '<tr><td colspan=5 style="text-align:center; font-weight:bolder ">MORE</td></tr>';
			}
			
			$c++;
		}

		return $list;
	}
	
	public function calcCIDR( $cidr ) {
		$cidr = explode('/', $cidr);
	
		$cidr_base = $cidr[0];
		$cidr_range = $cidr[1];
	
		$cidr_base_bin = self::addZero( decbin( ip2long( $cidr_base ) ) );
#		$cidr_base_bin = self::ip2bin( $cidr_base );
	
		$cidr_shortened = substr( $cidr_base_bin, 0, $cidr_range );
#		$cidr_shortened = substr( implode( '', $cidr_base_bin ), 0, $cidr_range );
		$cidr_difference = 32 - $cidr_range;
	
		$cidr_begin = $cidr_shortened . str_repeat( '0', $cidr_difference );
		$cidr_end = $cidr_shortened . str_repeat( '1', $cidr_difference );
	
		$ip_begin = long2ip( bindec( self::removeZero( $cidr_begin ) ) );
		$ip_end = long2ip( bindec( self::removeZero( $cidr_end ) ) );
		$ip_count = bindec( $cidr_end ) - bindec( $cidr_begin ) + 1;
		 
		return array( 
				'begin' => $ip_begin, 
				'end' => $ip_end, 
				'count' => $ip_count, 
				'shortened' => $cidr_shortened, 
				'suffix' => $cidr_range 
			);
	}
	
	public function calcRange( $iparray ) {

		$iparray = array_unique($iparray);
		$iparray = array_map("ip2long",$iparray);
		sort($iparray);
		$iparray = array_map("long2ip",$iparray);
		 
		$ip_begin = $iparray[0];
		$ip_end = $iparray[ count($iparray) - 1 ];
		 
		$ip_begin_bin = self::addZero( decbin( ip2long( $ip_begin ) ) );
		$ip_end_bin = self::addZero( decbin( ip2long( $ip_end ) ) );
		 
		$ip_shortened = self::findMatch( $ip_begin_bin, $ip_end_bin );
		$cidr_range = strlen( $ip_shortened );
		$cidr_difference = 32 - $cidr_range;
		 
		$cidr_begin = $ip_shortened . str_repeat( '0', $cidr_difference );
		$cidr_end = $ip_shortened . str_repeat( '1', $cidr_difference );
		 
		$ip_count = bindec( $cidr_end ) - bindec( $cidr_begin ) + 1;
	
		return array( 
				'begin' => $ip_begin, 
				'end' => $ip_end, 
				'count' => $ip_count, 
				'shortened' => $ip_shortened, 
				'suffix' => $cidr_range 
			);
	}
	
	// from ipcalc, maybe outdated
	function calcRange2( $iparray ) {
		print_r($iparray);
		$iparray = array_unique($iparray);
		$iparray = array_map("ip2long",$iparray[0]);
		sort($iparray);
		$iparray = array_map("long2ip",$iparray);
	
		$ip_begin = $iparray[0];
		$ip_end = $iparray[ count($iparray) - 1 ];
	
		$ip_begin_bin = self::ip2bin( $ip_begin );
		$ip_end_bin = self::ip2bin( $ip_end );
	
		$ip_shortened = self::findMatch( implode( '', $ip_begin_bin ), implode( '', $ip_end_bin ) );
		$cidr_range = strlen( $ip_shortened );
		$cidr_difference = 32 - $cidr_range;
	
		$cidr_begin = $ip_shortened . str_repeat( '0', $cidr_difference );
		$cidr_end = $ip_shortened . str_repeat( '1', $cidr_difference );
	
		$ip_count = bindec( $cidr_end ) - bindec( $cidr_begin ) + 1;
	
		$ips = array();
		foreach( $iparray as $ip ) {
			$ips[] = array(
					'ip' => $ip,
					'bin' => implode( '.', self::ip2bin( $ip ) ),
					'rdns' => gethostbyaddr( $ip ),
					'long' => ip2long( $ip ),
					'hex' => implode( '.', self::ip2hex( $ip ) ),
					'octal' => implode( '.', self::ip2oct( $ip ) ),
					'radians' => implode( '/', self::ip2rad( $ip ) ),
					'base64' => implode( '.', self::ip264( $ip ) ),
					'alpha' => implode( '.', self::ip2alpha( $ip ) ),
			);
		}
	
		usort( $ips, array( 'IPCalc', 'ipsort' ) );
	
		$tmp = self::calcCIDR( $ip_begin . '/' . $cidr_range );
	
		return array(
				'begin' => $tmp['begin'],
				'end' => $tmp['end'],
				'count' => $tmp['count'],
				'suffix' => $cidr_range,
				'ips' => $ips
		);
	}
	
	public function addZero ( $string ) {
		$count = 32 - strlen( $string );
		for( $i = $count; $i>0; $i-- ) {
			$string = "0" . $string;
		}
		return $string;
	}
	
	function addZero2( $string, $len = 32 ) {
		$count = $len - strlen( $string );
		for( $i = $count; $i > 0; $i-- ) {
			$string = "0" . $string;
		}
		return $string;
	}
	
	public function removeZero ( $string ) {
		$string = str_split( $string, 1 );
		foreach( $string as $val => $strchar ) {
			if( $strchar == 1 ) break;
	
			unset( $string[$val] );
		}
		 
		$string = implode( "", $string );
		return $string;
	}
	
	public function findMatch( $ip1, $ip2 ) {
		$ip1 = str_split( $ip1, 1 );
		$ip2 = str_split( $ip2, 1 );
		 
		$match = null;
		foreach ( $ip1 as $val => $char ) {
			if( $char != $ip2[$val] ) break;
	
			$match .= $char;
		}
		 
		return $match;
	}

	
	
	function ipsort( $ip1, $ip2 ) {
		return strnatcmp( sprintf('%u', $ip1['long'] ), sprintf('%u', $ip2['long'] ) );
	}
	
	function ip2bin( $ip ) {
		$tmp = explode( '.', $ip );
	
		foreach( $tmp as $key => $val ) {
			$tmp[$key] = self::addZero( decbin( $val ), 8 ) ;
		}
	
		return $tmp;
	}
	
	function ip2hex( $ip ) {
		$tmp = explode( '.', $ip );
	
		foreach( $tmp as $key => $val ) {
			$tmp[$key] = self::addZero( dechex( $val ), 2 );
		}
	
		return $tmp;
	}
	
	function ip2oct( $ip ) {
		$tmp = explode( '.', $ip );
	
		foreach( $tmp as $key => $val ) {
			$tmp[$key] = self::addZero( decoct( $val ), 3 );
		}
	
		return $tmp;
	}
	
	function ip2rad( $ip ) {
		$tmp = explode( '.', $ip );
	
		foreach( $tmp as $key => $val ) {
			$tmp[$key] = deg2rad( $val );
		}
	
		return $tmp;
	}
	
	function ip264( $ip ) {
		$tmp = explode( '.', $ip );
	
		foreach( $tmp as $key => $val ) {
			$tmp[$key] = base64_encode( $val );
		}
	
		return $tmp;
	}
	
	function ip2alpha( $ip ) {
		$tmp = explode( '.', $ip );
	
		foreach( $tmp as $key => $val ) {
			$tmp[$key] = str_replace( array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 0 ), array( 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J' ), $val );
		}
	
		return $tmp;
	}

}