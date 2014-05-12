<?php

//Requires
	require_once( '../WebTool.php' );
	require_once( 'base.php' );

//Load WebTool class
	$wt = new WebTool( 'Blame', 'blame', array(  'database', "smarty", "sitenotice", "replag" ) );
	WebTool::setMemLimit();
	$base = new BlameBase();
	$wt->content = $base->getPageForm();
	
// get params from query string
	$lang = $wt->webRequest->getSafeVal( 'lang' );
	$wiki = $wt->webRequest->getSafeVal( 'wiki' );
	$article = $wt->webRequest->getSafeVal( 'article' );
	$nofollowredir = $wt->webRequest->getBool( 'nofollowredir' );
	$text = isset($_GET["text"]) ? urldecode($_GET["text"]) : "";
	
	$wikibase = $lang.'.'.$wiki.'.org';
	
//Show form if &article parameter is not set (or empty)
	if( $lang == "" || $wiki == "" || $article == "" || $text == "" ) {
		$wt->showPage($wt);
	}

// execute the main logic
	$revs = $base->getBlameResult( $wikibase, $article, $nofollowredir, $text);
	$result = '<p>'.$I18N->msg('added').'</p><ul> ';
	foreach ( $revs as $rev ){
		$result .= $rev;
	}
	$result .= "</ul>";
	$wt->content = $result;


unset( $base, $result);
$wt->showPage($wt);



