<?php

//Requires
	require_once( '../WebTool.php' );
	require_once( 'base.php' );

//Load WebTool class
	$wt = new WebTool( 'Bash', 'bash', array() );
	$base = new BashBase();
	$wt->content = getPageTemplate( 'form' );

//Show form if &article parameter is not set (or empty)
	if( !$wgRequest->getVal( 'action' ) ) {
		$wt->showPage();
	}
	

	switch( $wgRequest->getVal( 'action' ) ) {
		case 'random':
			$quote = $base->getRandomQuote();
			
			$otherurl = "//tools.wmflabs.org".$_SERVER['REQUEST_URI'];
			$pageResult = '
				<h3>'.$I18N->msg('quotenumber').' '.$quote['id'].'</h3>
				<pre>'.$quote['quote'].'</pre>
				<a href="'.$otherurl.'">'.$I18N->msg('showanother').'</a>
			';
			
			break;
			
		case 'showall':
			$quotes = $base->getAllQuotes();
			
			$pageResult = '<h3>'.$I18N->msg('allquotes').'</h3>';
			foreach ( $quotes as $id => $quote ){
				$pageResult .= '
						<h3>'.$I18N->msg('quotenumber').' '.$id.'</h3>
						<pre>'.$quote.'</pre>';
			}
			
			break;
			
		case 'search':
			$quotes = $base->getQuotesFromSearch( $wgRequest->getSafeVal( 'search' ), ( $wgRequest->getBool( 'regex' ) ) );
			
			$pageResult = '<h3>'.$I18N->msg('searchresults').'</h3>';
			foreach ( $quotes as $id => $quote ){
				$pageResult .= '
						<h3>'.$I18N->msg('quotenumber').' '.$id.'</h3>
						<pre>'.$quote.'</pre>';
			}
			if( !count( $quotes ) ) {
				$wt->error = $I18N->msg('noresults') ;
				$pageResult = $base->getPageForm();
			}
			break;
			
		default:
			$wt->showPage();
	}

unset($base, $quotes);
$wt->content = $pageResult;
$wt->showPage();
	

/**************************************** templates ****************************************
 *
*/
function getPageTemplate( $type ){

	$templateForm = '
			
	<form action="?" method="get" accept-charset="utf-8">
	<table class="wikitable">
	<tr>
	<td colspan="2"><input type="radio" name="action" value="random" checked="checked" />{#random#}</td>
	</tr>
	<tr>
	<td colspan="2"><input type="radio" name="action" value="showall" />{#showall#}</td>
	</tr>
	<tr>
	<td><input type="radio" name="action" value="search" />{#search#}<input type="text" name="search" /> <input type="checkbox" name="regex" />{#regex#}</td>
	</tr>
	<tr><td colspan="2"><input type="submit" value="{#submit#}" /></td></tr>
	</table>
	</form>
	';
	
	if( $type == "form" ) { return $templateForm; }
	if( $type == "result" ) { return $templateResult; }
}