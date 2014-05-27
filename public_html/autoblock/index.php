<?php

//Requires
	require_once( '../WebTool.php' );
	
//Load WebTool class
	$wt = new WebTool( 'Autoblock', 'autoblock', array() );
	$wt->setLimits();

	$wt->content = getPageForm();
	$wt->assign("lang", "en");
	$wt->assign("wiki", "wikipedia");
	
	$wi = $wt->getWikiInfo();
		$lang = $wi->lang;
		$wiki = $wi->wiki;
		
	$ui = $wt->getUserInfo();
		$user = $ui->user;
	
//Show form if &article parameter is not set (or empty)
	if( !$user ) {
		$wt->showPage();
	}

	$dbr = $wt->loadDatabase($lang, $wiki);
	
	$userdb = $dbr->strencode ($user);
	$query = "
   		SELECT ipb_id, ipb_by_text, UNIX_TIMESTAMP(ipb_expiry) as ipb_expiry, ipb_user 
   		FROM ipblocks 
   		WHERE ipb_auto = 1 AND ipb_reason LIKE '%$userdb%'
   	";
	
	$result = $dbr->query( $query );
	
	$autoblockList = "<h2>Autoblock result for search: $user</h2><ol>";
	foreach( $result as $i => $out ) {
		$autoblockList .= '<li><strong>#' . $out['ipb_id'] . '</strong> - blocked by <a href="//en.wikipedia.org/wiki/User:' . htmlspecialchars( $out['ipb_by_text'] ) . '">' . htmlspecialchars( $out['ipb_by_text'] ) . '</a> :: <a href="//en.wikipedia.org/w/index.php?title=Special:BlockList&action=unblock&id=' . $out['ipb_id'] . '">Lift block</a><br />';
	}
	$autoblockList .= "</ol>";
	
	$wt->content = $autoblockList;
	$wt->showPage();
	

 function getPageForm(){ 
	 $pageForm = '
	 	<br />
		<form action="?" method="get" accept-charset="utf-8">
		<table>
			<tr><td>{#username#}: </td><td><input type="text" name="user" value="%"/></td></tr>
			<tr><td>{#wiki#}: </td><td><input type="text" value="{$lang}" name="lang" size="9" />.<input type="text" value="{$wiki}" size="10" name="wiki" />.org</td></tr>
			<tr><td colspan="2"><input type="submit" value="{#submit#}" /></td></tr>
		</table>
		</form><br />
	'; 
	 
	 return $pageForm;
 }


