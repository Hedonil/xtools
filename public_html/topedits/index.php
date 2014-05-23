<?php

//Requires
	require_once( '../WebTool.php' );

//Load WebTool class
	$wt = new WebTool( 'Top edits', 'topedits', array() );
	$wt->setLimits();
	
	$wt->content = getPageTemplate( 'form' );
	$wt->assign("lang", "en");
	$wt->assign("wiki", "wikipedia");
	
	$lang = $wgRequest->getVal('lang');
	$wiki = $wgRequest->getVal('wiki');
	$namespace = $wgRequest->getVal('namespace');
	
	$username = $wgRequest->getVal('user');
	$username = $wgRequest->getBool('name') ? $wgRequest->getVal('name') : $username;


//Show form if &article parameter is not set (or empty)
	if( !$username ) {
		$wt->showPage();
	}

//Get username & userid, quit if not exist
	$dbr = $wt->loadDatabase( $lang, $wiki );
	$userData = checkUserData( $dbr, $username );

	if( !$userData ) {
		$wt->error = $I18N->msg("No such user");
		$wt->showPage();
	}
	
$wt->content = getTopEdits($dbr, $lang, $wiki, $username, $namespace);
$wt->showPage();



/**************************************** stand alone functions ****************************************
 *
*/
function checkUserData( $dbr, $username ){
	
	if ( long2ip( ip2long( $username ) ) == $username ) 
	 return true; 
	
	$user = $dbr->strencode($username);
	$query = "
		SELECT user_id
		FROM user
		WHERE user_name = '$user';
	";
	 
	$result = $dbr->query( $query );
	$userdata = $result[0]["user_id"];
	 
	return $userdata;
}

function getTopEdits( $dbr, $lang, $wiki, $username, $namespace ){
	
	$nsnames = getNamespaceNames( $lang, $wiki );

	$namespace = intval($namespace);
	$username = $dbr->strencode($username);
	
	$query = "
      		SELECT /* SLOW_OK */ page_namespace, page_title, page_is_redirect, count(page_title) as count
			FROM page
			JOIN revision_userindex ON page_id = rev_page
			WHERE rev_user_text = '$username' AND page_namespace = '$namespace'
			GROUP BY page_namespace, page_title
			ORDER BY count DESC
      		LIMIT 100
		";
	
	$res = $dbr-> query( $query ); 
	
	$list = '
		<br />
		<b>Top 100 namespace edits for: '.$username.'</b>
		<h3>'.$nsnames[$namespace].'</h3>
		<table style="font-size:85%; margin-left:50px;">
	';
	 
	foreach ( $res as $i => $page ) {
	
		$nscolon = '';
		if( $page["page_namespace"] != 0 ) {
			$nscolon = $nsnames[ $page["page_namespace"] ].":";
		}
	
		$list .= '<tr>
   			<td>'.$page["count"].'</td>
   			<td><a href="//'.$lang.'.'.$wiki.'.org/wiki/'.$nscolon.str_replace(array('%2F','_'),array('/',' '),urlencode( $page["page_title"] )).'" >'.str_replace('_', '',$page["page_title"]).'</a></td>
   			</tr>';
	}
	$list .= "</table>";
   
   return $list;
}

function getNamespaceNames( $lang, $wiki ) {

	$http = new HTTP();
	$namespaces = $http->get( "http://$lang.$wiki.org/w/api.php?action=query&meta=siteinfo&siprop=namespaces&format=php" );
	$namespaces = unserialize( $namespaces );
	$namespaces = $namespaces['query']['namespaces'];
	unset( $namespaces[-2] );
	unset( $namespaces[-1] );

	$namespaces[0]['*'] = "Main";


	$namespacenames = array();
	foreach ($namespaces as $value => $ns) {
		$namespacenames[$value] = $ns['*'];
	}

	return $namespacenames;
}

/**************************************** templates ****************************************
 *
*/
function getPageTemplate( $type ){

	$templateForm = '
	<br />
	<form action="?" method="get" accept-charset="utf-8">
	<table>
		<tr><td>{#username#}: </td><td><input type="text" name="user" /></td></tr>
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
				</select><br />
			</td>
		</tr>
		<tr><td colspan="2"><input type="submit" value="{#submit#}" /></td></tr>
	</table>
	</form><br />
	';


	$templateResult = '

	<p>{$totalcreated}&nbsp;({#namespace#}: {$nsFilter}, {#redirects#}: {$redirFilter} )</p>
	<table>
		<tr>
		<td>
		<table style="margin-top: 10px" >
			<tr>
			<th>NS</th>
			<th>NS name</th>
			<th>Pages</th>
			<th style="padding_left:5px">&nbsp;&nbsp;(Redirects)</th>
			</tr>
			{$namespace_overview}
		</table>
		</td>
		<td><img src="//chart.googleapis.com/chart?cht=p3&amp;chd=t:{$chartValues}&amp;chs=550x140&amp;chl={$chartText}&amp;chco=599ad3|f1595f|79c36a|f9a65a|727272|9e66ab|cd7058|ff0000|00ff00&amp;chf=bg,s,00000000" alt="minor" /></td>
		</tr>
	</table>

	<table>
		{$resultDetails}
	</table>
	';

	if( $type == "form" ) { return $templateForm; }
	if( $type == "result" ) { return $templateResult; }

}
