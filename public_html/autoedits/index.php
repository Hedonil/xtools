<?php

//Requires
	require_once( '../WebTool.php' );
	require_once( '../Counter.php' );

//Load WebTool class
	$wt = new WebTool( 'AutoEdits', 'autoedits', array("database") );
	$wt->setMemLimit();
	 
	$wt->content = getPageTemplate( "form" );
	$wt->assign( 'lang', 'en');
	$wt->assign( 'wiki', 'wikipedia');


	$user = $wt->prettyTitle( $wgRequest->getSafeVal( 'user' ), true );
	$lang = $wt->webRequest->getSafeVal( 'lang' );
	$wiki = $wt->webRequest->getSafeVal( 'wiki' );
	$wikibase = $lang.".".$wiki;
	$begin = $wt->webRequest->getSafeVal( 'begin' );
	$end = $wt->webRequest->getSafeVal( 'end' );

//Show form if &article parameter is not set (or empty)
	if( !$lang || !$wiki || !$user ) {
		$wt->showPage();
	}
	
	$cnt = new Counter( $user, $wikibase );
	
	if( !$cnt->getExists() ) {
		$wt->error = $I18N->msg( 'nosuchuser');
		$wt->showPage();
	}


//Start doing the DB request
	$data = $cnt->calcAutoEditsDB($dbr, $begin, $end);
	
	$list = '<ul>';
	foreach ( $data["tools"] as $i => $tool  ){
		$list .= '<li><a href="//'.$wikibase.'/wiki/'.$tool["shortcut"].'">'.$tool["toolname"].'</a> &ndash; '.$wt->numFmt($tool["count"]).'</li>';
	}
	$list .= '</ul>';
	
	$wt->content = getPageTemplate( "result" );
	$wt->assign( 'list', $list);
	$wt->assign( 'totalauto', $wt->numFmt($data['total']) );
	$wt->assign( 'totalall', $wt->numFmt($data['editcount']) );
	$wt->assign( 'pct', $data['pct'] );
	

unset( $cnt, $data, $list );
$wt->showPage();



/**************************************** templates ****************************************
 * 
 */
function getPageTemplate( $type ){

	$templateForm = '
			
	<form action="?" method="get" accept-charset="utf-8">
	<table>
		<tr><td>{#user#}: </td><td><input type="text" name="user" /></td></tr>
		<tr><td>{#wiki#}: </td><td><input type="text" value="{$lang}" name="lang" size="9" />.<input type="text" value="{$wiki}" size="10" name="wiki" />.org</td></tr>
		<tr><td>{#start#}: </td><td><input type="text" name="begin" /></td></tr>
		<tr><td>{#end#}: </td><td><input type="text" name="end" /></td></tr>
	
		<tr><td colspan="2"><input type="submit" value="{#submit#}" /></td></tr>
	</table>
	</form>
	';
	
	
	$templateResult = '
	
	{#approximate#}
	
	{$list}
	
	<table class="wikitable">
		<tr>
			<td>{#totalauto#}</td><td>{$totalauto}</td>
		</tr>
		<tr>
			<td>{#totalall#}</td><td>{$totalall}</td>
		</tr>
		<tr>
			<td>{#autopct#}</td><td>{$pct}%</td>
		</tr>
	</table>
	';
				
	if( $type == "form" ) { return $templateForm; }
	if( $type == "result" ) { return $templateResult; }
}
