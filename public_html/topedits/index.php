<?php

//Requires
	require_once( '../WebTool.php' );
	require_once( '../Counter.php' );

//Load WebTool class
	$wt = new WebTool( 'Top edits', 'topedits', array() );
	$wt->setLimits();
	
	$wt->content = getPageTemplate( 'form' );
	$wt->assign("lang", "en");
	$wt->assign("wiki", "wikipedia");
	
	$namespace = $wgRequest->getVal('namespace');
	
	//kompatibility
	$page = $wgRequest->getVal('page');
	$page = $wgRequest->getVal('article', $page );
	
	$wi = $wt->getWikiInfo();
		$lang = $wi->lang;
		$wiki = $wi->wiki;
		$domain = $wi->domain;
	
	$ui = $wt->getUserInfo();
		$user = $ui->user;


//Show form if &article parameter is not set (or empty)
	if( !$user && ( !$page || !$lang || !$wiki || (strval($namespace) == "")  ) ) {
		$wt->showPage();
	}
	

	$dbr = $wt->loadDatabase( $lang, $wiki );
	

	if ($page){
		$wt->content = getPageTemplate( 'resultpage' );
		
		$site = $wt->loadPeachy( $lang, $wiki );
		$pageObj = new Page( $site , $page );
			$nsname = $pageObj->get_namespace(false);
			$nsid = $pageObj->get_namespace();
			$nscolon = ($nsid) ? $nsname.":" : "";
			$page_title = $pageObj->get_title(false);
			$page_id = $pageObj->get_id();
		
		$list = getPageEditsPerUser($dbr, $page_id, $domain, $ui, $wi );
	}
	else{
		$cnt = new Counter( $dbr, $user, $domain, true  );
		if ( $cnt->optin ) {
			$wt->content = getPageTemplate( 'resultns' );
			$nscolon = $page_title = "";
			
			$nsnames = $cnt->getNamespaces();
			$list = getTopEditsByNamespace( $dbr, $wi, $ui, $nsnames, $namespace );
		}
		else {
			$wt->content = getPageTemplate( 'resultns' );
			$list = $I18N->msg( "nograph", array( "variables"=> array( $cnt->getOptinLinkLocal(), $cnt->getOptinLinkGlobal() ) ) );
		}
	}
	
	$wt->assign( 'page', $nscolon.$page_title );
	$wt->assign( 'urlencodedpage', rawurlencode( str_replace(" ", "_", $nscolon.$page_title ) ) );
	$wt->assign( 'xtoolsbase', XTOOLS_BASE_WEB_DIR );
	$wt->assign( 'list', $list );
	$wt->assign( 'lang', $lang );
	$wt->assign( 'wiki', $wiki );
	$wt->assign( 'domain', $domain );
	$wt->assign( 'username', $user );
	$wt->assign( 'usernameurl', $ui->userUrl );
	
unset( $cnt, $list );
$wt->showPage();



/**************************************** stand alone functions ****************************************
 *
*/

function getPageEditsPerUser( $dbr, $page_id, $domain, $ui, $wi ){
	global $wt;
	
	$page_id = intval( $page_id );
	
	if ( !$page_id ) 
		$wt->toDie('nosuchpage', $page_title);
	
	$revs = new stdClass();
		$user = array();
		$parent = array();
	
	$where = " rev_user_text = '$ui->userDb' AND rev_page = '$page_id' ";
	
	//Get da revisions
	$query = "
		SELECT /* SLOW_OK */  rev_id, rev_parent_id, rev_timestamp, rev_minor_edit, rev_len, rev_comment
		FROM revision_userindex 
		WHERE  $where
		ORDER BY rev_timestamp DESC
	";
	
	$revs->user = $dbr->query ( $query );
	
	
	//Get all da parentID's revs for length calc
	$query = "
		SELECT b.rev_id, b.rev_len
		FROM revision_userindex as b
		WHERE  rev_id in  ( SELECT rev_parent_id from revision_userindex WHERE  $where )
		ORDER BY b.rev_id
	";
	
	$res = $dbr->query ( $query );
	
	foreach ( $res as $i => $row ){
		$revs->parent[ $row["rev_id"] ] = $row["rev_len"];
	}
	unset($res);
	
	$prefix = ( $nsid === 0 ) ? "" : $nsname.":";
	$title = $prefix.$page_title;
	$urltitle = rawurlencode($prefix.$title);
	$totaladd = 0;
	$totaldelete = 0;
	
	$list = '<table class="leantable sortable" >';
	$list .= '
			<tr>
			<th>Date</th>
			<th>Diff</th>
			<th>Hist</th>
			<th>Size</th>
			<th>Comment</th>
			</tr>
		';
	foreach ( $revs->user as $i => $row ){
		
		$date = date('H:i, d.m.Y ', strtotime( $row['rev_timestamp']) );
		$year = date('Y', strtotime( $row['rev_timestamp']) );
		$month = date('m', strtotime( $row['rev_timestamp']) );
		$minor = ( $row['rev_minor_edit'] == '1' ) ? '<span class="minor" >m</span>' : '';
		$difflen = $row["rev_len"] - $revs->parent[ $row["rev_parent_id"] ];
		
		if ( $difflen >= 0 ) {
			$color = "green";
			$totaladd += $difflen;
		}
		else {
			$color = "red";
			$totaldelete += $difflen;
		}
		
		$list .= '
			<tr>
			<td style="font-size:95%; white-space:nowrap;">'.$date.' &middot; </td>
			<td>(<a href="//'.$domain.'/w/index.php?title='.$urltitle.'&amp;diff=prev&amp;oldid='.urlencode($row['rev_id']).'" title="'.$title.'">diff</a>)</td>
			<td>(<a href="//'.$domain.'/w/index.php?title='.$urltitle.'&amp;action=history&amp;year='.$year.'&amp;month='.$month.' " title="'.$title.'">hist</a>)</td>
			<td style="text-align:right;padding-right:5px;color:'.$color.'" >'.$wt->numFmt($difflen).'</td>
			<td style="font-size:85%" > &middot; '.$row['rev_comment'].'</td>
			</tr>
		';
	}
	$list .= "</table>";
	
	$info = '
			<table>
			<tr><td>{#user#}:</td><td><a title="Edit Counter" href="'.XTOOLS_BASE_WEB_DIR."/ec/?user=$ui->userUrl&amp;lang=$wi->lang&amp;wiki=$wi->wiki".'" >(ec) &middot; </a></td><td><a href="//'.$domain.'/wiki/User:'.$ui->userUrl.'" >'.$ui->user.'</a></td></tr>
			<tr><td>{#added#}:</td><td colspan=2 style="color:green" >+'.$wt->numFmt($totaladd).'</td></tr>
			<tr><td>{#deleted#}:</td><td colspan=2 style="color:red" >'.$wt->numFmt($totaldelete).'</td></tr>		
			</table>
			<br />
		';
	
	return $info.$list;
}


function getTopEditsByNamespace( $dbr, $wi, $ui, $nsnames, $namespace){

	if ( !$ui->userDb ) return null;

	$namespace = intval($namespace);
	$domain = $wi->domain;
	$lang = $wi->lang;
	$wiki = $wi->wiki;
	$user = $ui->user;
	
	$query = "
		SELECT /* SLOW_OK */ page_namespace, page_title, page_is_redirect, count(page_title) as count
		FROM page
		JOIN revision_userindex ON page_id = rev_page
		WHERE rev_user_text = '$ui->userDb' AND page_namespace = '$namespace'
		GROUP BY page_namespace, page_title
		ORDER BY count DESC
		LIMIT 100
	";
	
	$res = $dbr->query( $query );
	
	$list = '<table><tr><td colspan=22 ><h3>' . $nsnames['names'][$namespace] . '</h3></td></tr>';
	
	foreach ( $res as $i => $row ) {
	
		$nscolon = '';
		if( $row["page_namespace"] != 0 ) {
			$nscolon = $nsnames['names'][ $row["page_namespace"] ].":";
		}
		
		$articleencoded = rawurlencode( str_replace(" ", "_", $nscolon.$row["page_title"] ) );
		$articleencoded = str_replace( array('%2F', '%3A'), array('/', ':'), $articleencoded );
		$article = str_replace("_", " ", $nscolon.$row["page_title"] );
			
		$list .= "
			<tr>
			<td class=tdnum >".$row["count"]."</td>
			<td><a href=\"//$domain/wiki/$articleencoded\" >$article</a></td>
			<td><a href=\"//$domain/w/index.php?title=Special:Log&type=&page=$articleencoded\" ><small>log</small></a> &middot; </td>
			<td><a href=\"//".XTOOLS_BASE_WEB_DIR."/articleinfo/?lang=$lang&wiki=$wiki&page=$articleencoded\" ><small>page history</small></a> &middot; </td>
			<td><a href=\"//".XTOOLS_BASE_WEB_DIR."/topedits/?lang=$lang&wiki=$wiki&user=${user}&page=$articleencoded\" ><small>topedits</small></a></td>
			</tr>
		";
	}
	
	$list .= "</table>";

	return  $list;
}

/**************************************** templates ****************************************
 *
*/
function getPageTemplate( $type ){

	$templateForm = '
	<br />
	<form action="?" method="get" accept-charset="utf-8">
	<table>
		<tr><td>{#username#}: </td><td><input type="text" name="user" size="21" /></td></tr>
		<tr><td>{#wiki#}: </td><td><input type="text" value="{$lang}" name="lang" size="9" />.<input type="text" value="{$wiki}" size="10" name="wiki" />.org</td></tr>
		<tr><td>{#namespace#}: </td>
			<td>
				<select name="namespace">
					<option value="0">Main</option>
					<option value="1">Talk</option>
					<option value="2">User</option>
					<option value="3">User talk</option>
					<option value="4">Wikipedia</option>
					<option value="5">Wikipedia talk</option>
					<option value="6">File</option>
					<option value="7">File talk</option>
					<option value="8">MediaWiki</option>
					<option value="9">MediaWiki talk</option>
					<option value="10">Template</option>
					<option value="11">Template talk</option>
					<option value="12">Help</option>
					<option value="13">Help talk</option>
					<option value="14">Category</option>
					<option value="15">Category talk</option>
					<option value="100">Portal</option>
					<option value="101">Portal talk</option>
					<option value="108">Book</option>
					<option value="109">Book talk</option>
				</select> -{#or#}-
			</td>
		</tr>
		<tr><td>{#page#}<td><input type="text" name="article" size="40" /></td></tr>
		<tr><td colspan="2"><input type="submit" value="{#submit#}" /></td></tr>
	</table>
	</form><br />
	';


	$templateResultNS = '

	<div class="caption" >
			<a style=" font-size:2em; " href="http://{$domain}/wiki/User:{$usernameurl$}">{$username$}</a>
			<span style="padding-left:10px;" > &bull;&nbsp; {$domain} </span>
			<p>Links: &nbsp;
				<a href="//{$domain}/w/index.php?title=Special%3ALog&type=block&user=&page=User%3A{$usernameurl}&year=&month=-1&tagfilter=" >block log</a> &middot; 
				<a href="//tools.wmflabs.org/supercount/?user={$usernameurl}&project={$lang}.{$wiki}" >User Analysis Tool</a> &middot;
				<a href="//tools.wmflabs.org/guc/?user={$usernameurl}" >Global user contributions</a> &middot; 
				<a href="//tools.wmflabs.org/wikiviewstats/?lang={$lang}&wiki={$wiki}&page={$userprefix}:{$usernameurl}*" >Pageviews in userspace</a> &middot; 
			</p>
	</div>
	<h3>{#generalstats#} <span class="showhide" >[<a href="javascript:switchShow( \'nstotals\' )">show/hide</a>]</span></h3>
	<div id="nstotals">
	{$list}
	</div>
	';

	$templateResultPage = '
	
	<div class="caption" >
		<a style=" font-size:1.5em; " href="http://{$domain}/wiki/{$urlencodedpage}">{$page}</a>
		<span style="padding-left:10px;" > &bull;&nbsp; {$domain} </span>
			<p style="margin-bottom:0px; margin-top: 5px; font-weight:normal;" >Links: &nbsp;
				<a href="//{$domain}/w/index.php?title=Special:Log&type=&page={$urlencodedpage}" >Page log</a> &middot;
				<a href="{$xtoolsbase}/articleinfo/?article={$urlencodedpage}&amp;lang={$lang}&amp;wiki={$wiki}" >Page history</a>
			</p>
	</div>
	<h3>{#generalstats#} <span class="showhide" >[<a href="javascript:switchShow( \'nstotals\' )">show/hide</a>]</span></h3>
	<div id="nstotals">
	{$list}
	</div>
	';
	
	if( $type == "form" ) { return $templateForm; }
	if( $type == "resultns" ) { return $templateResultNS; }
	if( $type == "resultpage" ) { return $templateResultPage; }

}
