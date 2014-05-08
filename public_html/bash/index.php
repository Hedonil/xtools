<?php

//Requires
	require_once( '../WebTool.php' );
	require_once( 'base.php' );

//Load WebTool class
	$wt = new WebTool( 'Bash', 'bash', array( 'getwikiinfo', 'database', 'smarty', 'sitenotice', 'replag') );
	$base = new BashBase();
	$wt->content = $base->getPageForm();

//Show form if &article parameter is not set (or empty)
	if( !$wt->webRequest->getSafeVal( 'getBool', 'action' ) ) {
		$wt->showPage($wt);
	}
	

	switch( $wt->webRequest->getSafeVal( 'action' ) ) {
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
			$wt->showPage($wt);
	}

unset($base, $quotes);
$wt->content = $pageResult;
$wt->showPage($wt);
	
