<?php

//Requires
	require_once( '../WebTool.php' );
	require_once( 'base.php' );

//Load WebTool class
	$wt = new WebTool( 'Range contributions', 'rangecontribs', array() );
	$wt->setLimits();
	
	$wt->content = getPageTemplate( "form" );
	$wt->assign("lang", "en");
	$wt->assign("wiki", "wikipedia");
	$wt->assign( 'begin', date('Y')."-01-01");
	
//Checks for alternative requests for compatibility (ips = legacy)
	$list  = $wgRequest->getText( 'ips' );
	$list  = $wgRequest->getBool('list') ? $wgRequest->getVal('list') : $list; 
	
	$limit = $wgRequest->getVal( 'limit', '20');

	$lang  = $wgRequest->getVal( 'lang' );
	$wiki  = $wgRequest->getVal( 'wiki' );
	
	$begin = $wt->checkDate( $wgRequest->getVal('begin') );
	$end   = $wt->checkDate( $wgRequest->getVal('end') );
	

	if( !$list ){
		$wt->showPage();
	}
	
	if( $begin == 'error' || $end == 'error'){
		$wt->toDie( 'invalid_date' );
	}

	
//Create exec object
	$dbr = $wt->loadDatabase( $lang, $wiki );
	$rc = new RangeContribs( $dbr, $list, $begin, $end, $limit );
	

//Get a list of unique, matching (existing) IP's
	$http = new HTTP();
	$ipList = $rc->getIPInformation( $http );

//Start the calculation
	$site = $wt->loadPeachy( $lang, $wiki );
	$namespaces = $site->get_namespaces();
	$list = makeListByName( $rc->getContribs(), $namespaces );

	
//Output stuff	
	$wt->content = getPageTemplate( "result" );
		
	$wt->assign( "cidr", $cidr_info['begin']."/".$cidr_info['suffix']);
	$wt->assign( "ip_start", $cidr_info['begin']);
	$wt->assign( "ip_end", $cidr_info['end']);
	$wt->assign( "ip_number", $cidr_info['count']);
	$wt->assign( "ip_found", count($matchingIPs));
	$wt->assign( "ipList", $ipList);
	$wt->assign( "list", $list);

unset( $base, $ipList, $list );
$wt->showPage();


function makeListByName( $contribs, $namespaces ){
	global $lang, $wiki;
	$wikibase = $lang.".".$wiki.".org";

#	if( count( $contribs ) == 0 ) { return "no results"; }

	$c = 0;
	$list = "<table>";
	$oldip = "";
	$seccount = 0;

	foreach ( $contribs as $row ){

#		if( $c >= $limit ) { $continue = $row['rev_timestamp'];break; }
#		if( isset( $_GET['continue'] ) && $_GET['continue'] < $row['rev_timestamp'] ) continue;
			
		#			$tmp1 = substr( RangecontribsBase::addZero( decbin( ip2long( $row['rev_user_text'] ) ) ), 0, $cidr_info['suffix'] );
		#			$tmp2 = $cidr_info['shortened'];
		#			if( $tmp1 !== $tmp2 ) { continue; }
		
		$ns = ($row['page_namespace'] == 0) ? "" : $namespaces[ $row['page_namespace'] ].":";
		$title = $ns.$row['page_title'];
		$urltitle = $ns.urlencode($row['page_title']);
		$date = date('H:i, d.m.Y ', $row['rev_timestamp']);

		//create a new header if namespace changes
		if( $oldip != $row['rev_user_text'] ){

			$list .= "<tr ><td colspan=8 ><h4 id='".$row['rev_user_text']."' style='margin:15 0 5 0;'>";
			$list .= '<a href="//'.$wikibase.'/wiki/User:'.$row['rev_user_text'].'" >'.$row['rev_user_text'].'</a>';
			$list .= ' (<a href="//'.$wikibase.'/wiki/User_talk:'.$row['rev_user_text'].'" title="User talk:'.$row['rev_user_text'].'">talk</a>)';
			$list .= ' <span style="font-weight:normal"> &middot; total: '.$row["sum"].'</span>';
			$list .= '</h4></td></tr>';

			$oldip = $row['rev_user_text'];
			$seccount = 0;
		}
			
		$list .= "<tr>";
		$list .= "<td>&nbsp;&nbsp;&nbsp;</td>";
		$list .= '<td style="font-size:95%; white-space:nowrap;">'.$date.' &middot; </td> ';
		$list .= '<td>(<a href="//'.$wikibase.'/w/index.php?title='.$urltitle.'&amp;diff=prev&amp;oldid='.urlencode($row['rev_id']).'" title="'.$title.'">diff</a>)</td>';
		$list .= '<td>(<a href="//'.$wikibase.'/w/index.php?title='.$urltitle.'&amp;action=history" title="'.$title.'">hist</a>)</td>';
		//if( $row['rev_minor_edit'] == '1' ) { $list .= '<span class="minor">m</span>'; }
		$list .= '<td> &middot; <a href="//'.$wikibase.'/wiki/'.$urltitle.'" title="'.$title.'">'.$title.'</a>‎ ('.$row['rev_comment'].')</td> ';
		$list .= "</tr>";

		$seccount++;
		if ( $seccount == 20 && $row["sum"] > 20 ){
			$list .= '<tr><td colspan=5 style="text-align:center; font-weight:bolder ">MORE</td></tr>';
		}

		$c++;
	}
	$list .= '</table>';
	
	return $list;
}


/**************************************** templates ****************************************
 * 
 */
function getPageTemplate( $type ){

	$templateForm = '
	<br />		
	<span>{#rc_usage_0#}</span>
	<ol>
	<li>{#rc_usage_1#} 0.0.0.0/0</li>
	<li>{#rc_usage_2#}</li>
	<li>{#rc_usage_3#}</li>
	</ol><br />
	<form action="?" method="get">
	<table>
	<tr>
		<td style="padding-left:5px" >Wiki:</td> 
		<td><input type="text" value="{$lang}" name="lang" size="9" />.<input type="text" value="{$wiki}" size="10" name="wiki" />.org</td>
	</tr>
	<tr></tr>
		<tr><td colspan=2 ><textarea name="ips" rows="10" cols="40"></textarea></td></tr>
		<tr>
			<td style="padding-left:5px" >Limit:</td>
			<td>
			<select name="limit">
			<option value="5">5</option>
			<option selected value="20" >20</option>
			<option value="50">50</option>
			</select>
			</td>
		</tr>
		<tr><td style="padding-left:5px">{#start#}: </td><td><input type="text" name="begin" value="{$begin}" /></td></tr>
		<tr><td style="padding-left:5px">{#end#}: </td><td><input type="text" name="end" /></td></tr>
		<tr><td><input type="submit" value="{#submit#}"/></td></td></tr>
	</table>
	</form>
	<br />
	<hr />
	';

	
	$templateResult = '
			
	<table>
		<tr><td><b>{#cidr#}: 	 </b></td><td>{$cidr}</td></tr>
		<tr><td><b>{#ip_start#}: </b></td><td>{$ip_start}</td></tr>
		<tr><td><b>{#ip_end#}:   </b></td><td>{$ip_end}</td></tr>
		<tr><td><b>{#ip_number#}:</b></td><td>{$ip_number}</td></tr>
		<tr><td><b>{#ip_found#}: </b></td><td>{$ip_found}</td></tr>
	</table>
	<h3>{#summary#}</h3>
		{$ipList}
	<h3>{#detailed_results#}</h3>
		{$list}
	<br />
	';
	
	if( $type == "form" ) { return $templateForm; }
	if( $type == "result" ) { return $templateResult; } 

}
