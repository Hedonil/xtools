<?php

//Requires
	require_once( '../WebTool.php' );

//Load WebTool class
	$wt = new WebTool( 'Blame', 'blame', array( "api") );
	$wt->setLimits();
	$wt->content = getPageTemplate( 'form' );
	
// get params from query string
	$lang = $wgRequest->getVal( 'lang', 'en' );
	$wiki = $wgRequest->getVal( 'wiki', 'wikipedia');
	$wt->assign( 'lang', $lang );
	$wt->assign( 'wiki', $wiki);
	
	$article = $wgRequest->getVal( 'article' );
	$nofollowredir = $wgRequest->getBool( 'nofollowredir' );
	$text = isset($_GET["text"]) ? urldecode($_GET["text"]) : "";
	
	$wikibase = $lang.'.'.$wiki.'.org';
	
//Show form if &article parameter is not set (or empty)
	if( $lang == "" || $wiki == "" || $article == "" || $text == "" ) {
		$wt->showPage();
	}

// execute the main logic
	$revs = getBlameResult( $site, $wikibase, $article, $nofollowredir, $text);
	$result = '<p>'.$I18N->msg('added').'</p><ul> ';
	foreach ( $revs as $rev ){
		$result .= $rev;
	}
	$result .= "</ul>";
	$wt->content = $result;


unset( $base, $result);
$wt->showPage();


	function getBlameResult( &$site, $wiki, $article, $nofollowredir, $text ){
	
		$pageClass = $site->initPage( $article, null, !$nofollowredir );
	
		$title = $pageClass->get_title();
		$history = $pageClass->history( null, 'older', true );
	
		$revs = array();
		foreach( $history as $id => $rev ) {
			if( ( $id + 1 ) == count( $history ) ) {
				if( in_string( $text, $rev['*'] , true ) ) $revs[] = parseRev( $rev, $wiki, $title );
			}
			else {
				if( in_string( $text, $rev['*'], true ) && !in_string( $text, $history[$id+1]['*'], true ) ) $revs[] = parseRev( $rev, $wiki, $title );
			}
		}
	
		return $revs;
	}
	
	function parseRev( $rev, $wiki, $title ) {
	
		$title = htmlspecialchars($title);
		$urltitle = urlencode($title);
	
		$timestamp = $rev['timestamp'];
		$date = date('M d, Y H:i:s', strtotime( $timestamp ) );
	
		$list = '(<a href="//'.$wiki.'/w/index.php?title='.$urltitle.'&amp;diff=prev&amp;oldid='.urlencode($rev['revid']).'" title="'.$title.'">diff</a>) ';
		$list .= '(<a href="//'.$wiki.'/w/index.php?title='.$urltitle.'&amp;action=history" title="'.$title.'">hist</a>) . . ';
	
		if( isset( $rev['minor'] ) ) {
			$list .= '<span class="minor">m</span>  ';
		}
	
		$list .= '<a href="//'.$wiki.'/wiki/'.$urltitle.'" title="'.$title.'">'.$title.'</a>‎; ';
		$list .= $date . ' . . ';
		$list .= '<a href="//'.$wiki.'/wiki/User:'.$rev['user'].'" title="User:'.$rev['user'].'">'.$rev['user'].'</a> ';
		$list .= '(<a href="//'.$wiki.'/wiki/User_talk:'.$rev['user'].'" title="User talk:'.$rev['user'].'">talk</a>) ';
		if( isset( $rev['comment'] ) ) $list .= '('.$rev['comment'].')';
		$list .= "<hr />\n</li>\n";
	
		return $list;
	}

/**************************************** templates ****************************************
 *
*/
function getPageTemplate( $type ){

	$templateForm = '

	<br />
	<form action="?" method="get" accept-charset="utf-8">
	<table>
	<tr><td>{#article#}: </td><td><input type="text" name="article" /> <input type="checkbox" name="nofollowredir" />{#nofollowredir#}</td></tr>
	<tr><td>{#wiki#}: </td><td><input type="text" value="{$lang}" name="lang" size="9" />.<input type="text" value="{$wiki}" size="10" name="wiki" />.org</td></tr>
	<tr><td>{#tosearch#}: </td><td><textarea name="text" rows="10" cols="40"></textarea></td></tr>
	<tr><td colspan="2"><input type="submit" value="{#submit#}" /></td></tr>
	</table>
	</form>
	';
	
	if( $type == "form" ) { return $templateForm; }
	if( $type == "result" ) { return $templateResult; }
}