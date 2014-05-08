<?php 

class RangecontribsBase{
	
	static function getMatchingIPs( $dbr, $ip_prefix ){
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
	static function getIPInformation( &$matchingIPs, $http ){
		
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
	
	public static function getRangeContribs( $dbr, $lang, $wiki, $matchingIPs, $ip_prefix, $cidr_info, $limit  ){
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
	
	public static function calcCIDR( $cidr ) {
		$cidr = explode('/', $cidr);
	
		$cidr_base = $cidr[0];
		$cidr_range = $cidr[1];
	
		$cidr_base_bin = self::addZero( decbin( ip2long( $cidr_base ) ) );
	
		$cidr_shortened = substr( $cidr_base_bin, 0, $cidr_range );
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
	
	public static function calcRange( $iparray ) {

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
	
	static function addZero ( $string ) {
		$count = 32 - strlen( $string );
		for( $i = $count; $i>0; $i-- ) {
			$string = "0" . $string;
		}
		return $string;
	}
	
	static function removeZero ( $string ) {
		$string = str_split( $string, 1 );
		foreach( $string as $val => $strchar ) {
			if( $strchar == 1 ) break;
	
			unset( $string[$val] );
		}
		 
		$string = implode( "", $string );
		return $string;
	}
	
	static function findMatch( $ip1, $ip2 ) {
		$ip1 = str_split( $ip1, 1 );
		$ip2 = str_split( $ip2, 1 );
		 
		$match = null;
		foreach ( $ip1 as $val => $char ) {
			if( $char != $ip2[$val] ) break;
	
			$match .= $char;
		}
		 
		return $match;
	}
	
	static function getPageForm( $lang="en", $wiki="wikipedia"){
		global $I18N;
		
		$iprange = $I18N->msg('ip_range').": ";
		$iplist = $I18N->msg('ip_list').": ";
		$usage0 = $I18N->msg('rc_usage_0');
		$usage1 = $I18N->msg('rc_usage_1');
		$usage2 = $I18N->msg('rc_usage_2'); 
		
		$pageForm = '

		<span>'.$usage0.'</span>
		<ol>
		<li>'.$iprange.$usage1.' 0.0.0.0/0</li>
		<li>'.$iplist.$usage2.'</li>
		</ol><br />
		<form action="?" method="get">
		<table>
		<tr>
			<td style="padding-left:5px" >Wiki: <input type="text" value="'.$lang.'" name="lang" size="9" />.<input type="text" value="'.$wiki.'" size="10" name="wiki" />.org</td>
		</tr>
		<tr>
			<td style="padding-left:5px" >Limit:
			<select name="limit">
			<option value="50">50</option>
			<option selected value="500" >500</option>
			<option value="5000">5000</option>
			</select>
			</td>
		</tr>
		<tr></tr>
		<tr>
			<td style="padding-left:5px; display:inline" >
			<span style="padding-right:20px">'.$iprange.'<input type="radio" name="type" value="range" /></span>
			<span>'.$iplist.'<input type="radio" name="type" value="list" /></span>
			</td>
		</tr>
		<tr>
			<td><textarea name="ips" rows="10" cols="40"></textarea></td>
		</tr>
		<tr>
			<td><input type="submit" value="'.$I18N->msg('submit').'"/></td>
		</tr>
		</table>
		</form>
		<br />
		<hr />
		';
		
		return $pageForm;
	}
}