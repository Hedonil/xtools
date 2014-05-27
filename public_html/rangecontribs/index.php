<?php

//Requires
	require_once( '../WebTool.php' );
	require_once( '../RangeContribs.php' );

//Load WebTool class
	$wt = new WebTool( 'Range contributions', 'rangecontribs', array() );
	$wt->setLimits();
	
	$wt->content = getPageTemplate( "form" );
	$wt->assign("lang", "en");
	$wt->assign("wiki", "wikipedia");
	$wt->assign( 'begin', date('Y')."-01-01");
	
//Checks for alternative requests for compatibility (ips = legacy)
	$list  = $wgRequest->getText( 'ips' );
	$list  = $wgRequest->getText( 'list', $list ); 
	
	$limit = $wgRequest->getVal( 'limit', '20');
	$begin = $wt->checkDate( $wgRequest->getVal('begin') );
	$end   = $wt->checkDate( $wgRequest->getVal('end') );
	
	$wi = $wt->getWikiInfo();
		$lang  = $wi->lang;
		$wiki  = $wi->wiki;
		$domain = $wi->domain;

		
	if( !$list || !$wiki || !$lang ){
		$wt->showPage();
	}
	
	if( $begin == 'error' || $end == 'error'){
		$wt->toDie( 'invalid_date' );
	}

	
//Create exec object
	$dbr = $wt->loadDatabase( $lang, $wiki );
	$rc = new RangeContribs( $dbr, $list, $begin, $end, $limit );
	

//Make output
	$site = $wt->loadPeachy( $lang, $wiki );
	$namespaces = $site->get_namespaces();
	
	$listsum = makeListSum( $rc->getItems() ); 
	$listcontribs = makeListByName( $rc->getContribs(), $namespaces );

	
//Output stuff	
	$wt->content = getPageTemplate( "result" );
	
	$wt->assign( "listsum", $listsum );
	$wt->assign( "listdetail", $listcontribs );
	
	$wt->assign( 'xtoolsbase', XTOOLS_BASE_WEB_DIR );
	$wt->assign( 'domain', $domain );
	$wt->assign( 'lang', $lang );
	$wt->assign( 'wiki', $wiki );
	
unset( $base, $ipList, $list );
$wt->showPage();




function makeListSum( $items ){
	global $wt;
	
	$list = "<table><tr>";
	foreach( $items["cidr"] as $i => $cidr ){
		$list .= '
			<td style="padding-right:15px;" >
			<table>
			<tr><td>{#cidr#}: 	  </td><td>'.$i.'</td></tr>
			<tr><td>{#ip_start#}: </td><td>'.$cidr["cidrinfo"]["begin"].'</td></tr>
			<tr><td>{#ip_end#}:   </td><td>'.$cidr["cidrinfo"]["end"].'</td></tr>
			<tr><td>{#ip_number#}:</td><td>'.$cidr["cidrinfo"]["count"].'</td></tr>
			</table>
			</td>
		';
	}
	$list .= "</tr></table><br />";
	
	foreach ( $items["byrange"] as $group => $item ){
		
		$header = "<p><b>$group</b></p> ";
		if ( isset($item["rangeinfo"]) ) {
			$range = $item["rangeinfo"];
			$header = "
				<p><b>Range:</b> $range->inetnum &nbsp; Provider: $range->netname &middot; $range->descr &middot; $range->country &nbsp;<img src=../images/flags/png/".strtolower($range->country).".png /></p>
			";
		}
		$list .= $header;
		
		$list .= "<table>";
		foreach ( $item["list"] as $user => $count ){
			
			$usernameurl = rawurlencode( $user );
			
			$list .= '
				<tr>
				<td><a href="#'.$usernameurl.'" >'.$user.'</a></td>
				<td class="tdnum" style="padding-left:1em; padding-right:1em" >'.$wt->numFmt( $count ).'</td>
				<td><small>
					<a href="//{$domain}/w/index.php?title=Special%3ALog&type=block&user=&page=User%3A'.$usernameurl.'&year=&month=-1&tagfilter=" >block log</a> &middot; 
					<a href="//{$xtoolsbase}/ec/?lang={$lang}&wiki={$wiki}&user='.$usernameurl.'" >ec</a> &middot; 
					<a href="//tools.wmflabs.org/guc/?user='.$usernameurl.'" >guc</a> &middot; 
				</td></small>
				</tr>
			';
		}
		$list .= "</table>";
	}
	
	return $list;
}

function makeListByName( $contribs, $namespaces ){
	

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
			$list .= '<a href="//{$domain}/wiki/User:'.$row['rev_user_text'].'" >'.$row['rev_user_text'].'</a>';
			$list .= ' (<a href="//{$domain}/wiki/User_talk:'.$row['rev_user_text'].'" title="User talk:'.$row['rev_user_text'].'">talk</a>)';
			$list .= ' <span style="font-weight:normal"> &middot; total: '.$row["sum"].'</span>';
			$list .= '</h4></td></tr>';

			$oldip = $row['rev_user_text'];
			$seccount = 0;
		}
			
		$list .= "<tr>";
		$list .= "<td>&nbsp;&nbsp;&nbsp;</td>";
		$list .= '<td style="font-size:95%; white-space:nowrap;">'.$date.' &middot; </td> ';
		$list .= '<td>(<a href="//{$domain}/w/index.php?title='.$urltitle.'&amp;diff=prev&amp;oldid='.urlencode($row['rev_id']).'" title="'.$title.'">diff</a>)</td>';
		$list .= '<td>(<a href="//{$domain}/w/index.php?title='.$urltitle.'&amp;action=history" title="'.$title.'">hist</a>)</td>';
		//if( $row['rev_minor_edit'] == '1' ) { $list .= '<span class="minor">m</span>'; }
		$list .= '<td> &middot; <a href="//{$domain}/wiki/'.$urltitle.'" title="'.$title.'">'.$title.'</a>‎ ('.$row['rev_comment'].')</td> ';
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

	<h3>{#summary#}</h3>
		{$listsum}
	<h3>{#detailed_results#}</h3>
		{$listdetail}
	<br />
	';
	
	if( $type == "form" ) { return $templateForm; }
	if( $type == "result" ) { return $templateResult; } 

}
