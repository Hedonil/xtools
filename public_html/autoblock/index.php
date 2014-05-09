<?php

//Requires
	require_once( '../WebTool.php' );
	
//Load WebTool class
	$wt = new WebTool( 'Autoblock', 'autoblock', array("smarty", "sitenotice", "replag") );
	WebTool::setMemLimit();
	$wt->content = getPageForm();
	$wt->assign("lang", "en");
	$wt->assign("wiki", "wikipedia");
	
//Show form if &article parameter is not set (or empty)
	if( !$wt->webRequest->getSafeVal( 'getBool', 'user' ) ) {
		$wt->showPage($wt);
	}

	$user = $wgRequest->getSafeVal( 'user' );
	
	$query = "
   		SELECT ipb_id, ipb_by_text, UNIX_TIMESTAMP(ipb_expiry) as ipb_expiry, ipb_user 
   		FROM ipblocks 
   		WHERE ipb_auto = 1 AND ipb_reason LIKE '%$user%'
   	";
	
	$result = $dbr->query( $query );
	
	$autoblockList = "<h2>Autoblock result for search: $user</h2><ol>";
	foreach( $result->endArray as $i => $out ) {
		$autoblockList .= '<li><strong>#' . $out['ipb_id'] . '</strong> - blocked by <a href="//en.wikipedia.org/wiki/User:' . htmlspecialchars( $out['ipb_by_text'] ) . '">' . htmlspecialchars( $out['ipb_by_text'] ) . '</a> :: <a href="//en.wikipedia.org/w/index.php?title=Special:BlockList&action=unblock&id=' . $out['ipb_id'] . '">Lift block</a><br />';
	}
	$autoblockList .= "</ol>";
	
	$wt->content = $autoblockList;
	$wt->showPage($wt);
	

 function getPageForm(){ 
	 $pageForm = '
		<form action="?" method="get" accept-charset="utf-8">
		<table>
			<tr><td>{#user#}: </td><td><input type="text" name="user" /></td></tr>
			<tr><td>{#wiki#}: </td><td><input type="text" value="{$lang}" name="lang" size="9" />.<input type="text" value="{$wiki}" size="10" name="wiki" />.org</td></tr>
			<tr><td colspan="2"><input type="submit" value="{#submit#}" /></td></tr>
		</table>
		</form><br />
	'; 
	 
	 return $pageForm;
 }


