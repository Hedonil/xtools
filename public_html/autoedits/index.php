<?php

//Requires
   require_once( '../WebTool.php' );
   require_once( 'base.php' );

//Load WebTool class
   $wt = new WebTool( 'AutoEdits', 'autoedits', array("smarty", "sitenotice", "replag") );
   $wt->setMemLimit();
   
   $wt->content = getPageTemplate( "form" );
   $wt->assign( 'lang', 'en');
   $wt->assign( 'wiki', 'wikipedia');

//Show form if &article parameter is not set (or empty)
   if( !$wgRequest->getSafeVal( 'getBool', 'user' ) ) {
      $wt->showPage($wt);
   }
   
   $user = $wt->prettyTitle( $wgRequest->getSafeVal( 'user' ), true );

//Initialize Peachy
   try {
      $userClass = $site->initUser( $user );
   } 
   catch( Exception $e ) {
      $wt->error = $e->getMessage() ;
      $wt->showPage($wt);
   }

#   $phptemp->assign( "page", $user );
   
	if( !$userClass->exists() ) {
		$wt->error = $I18N->msg( 'nosuchuser');
		$wt->showPage($wt);
	}
	
	$useLabs = true;
	$count = $userClass->get_editcount( false, $dbr );

//Start doing the DB request
   $data = AutoEditsBase::getMatchingEdits( 
   		$dbr,
   		$user,
   		( $wgRequest->getSafeVal( 'getBool', 'begin' ) ) ? $wgRequest->getSafeVal( 'begin' ) : false,
   		( $wgRequest->getSafeVal( 'getBool', 'end' ) ) ? $wgRequest->getSafeVal( 'end' ) : false,
   		$count
   );
 
	$list = '<ul>';
	foreach ( $data["tools"] as $i => $tool  ){
		$list .= '<li><a href="//'.$url.'/wiki/'.$tool["shortcut"].'">'.$tool["toolname"].'</a> &ndash; '.$wt->numFmt($tool["count"]).'</li>';
	}
	$list .= '</ul>';
	
	$wt->content = getPageTemplate( "result" );
	$wt->assign( 'list', $list);
	$wt->assign( 'totalauto', $wt->numFmt($data['total']) );
	$wt->assign( 'totalall', $wt->numFmt($data['editcount']) );
	$wt->assign( 'pct', $data['pct'] );
	

unset( $data, $list );
$wt->showPage($wt);



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
