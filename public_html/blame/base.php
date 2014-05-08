<?php 

class BlameBase{
	
	public function getBlameResult( $wiki, $article, $nofollowredir, $text ){
		
		try {
			$site = Peachy::newWiki( null, null, null, 'http://'.$wiki.'/w/api.php' );
		} catch (Exception $e) {
			return null;
		}
		
		$pageClass = $site->initPage( $article, null, !$nofollowredir );

		$title = $pageClass->get_title();
		$history = $pageClass->history( null, 'older', true );
		
		$revs = array();
		foreach( $history as $id => $rev ) {
			if( ( $id + 1 ) == count( $history ) ) {
				if( in_string( $text, $rev['*'] , true ) ) $revs[] = self::parseRev( $rev, $wiki, $title );
			}
			else {
				if( in_string( $text, $rev['*'], true ) && !in_string( $text, $history[$id+1]['*'], true ) ) $revs[] = self::parseRev( $rev, $wiki, $title );
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
	
	public function getPageForm( $lang="en", $wiki="wikipedia" ){
		global $I18N;
		
		$pageForm ='
		<br />
		'.$I18N->msg('welcome').'
		<br /><br />
		<form action="?" method="get" accept-charset="utf-8">
		<table>
		<tr><td>'.$I18N->msg('article').': </td><td><input type="text" name="article" /> <input type="checkbox" name="nofollowredir" />'.$I18N->msg('nofollowredir').'</td></tr>
		<tr><td>'.$I18N->msg('wiki').': </td><td><input type="text" value="'.$lang.'" name="lang" size="9" />.<input type="text" value="'.$wiki.'" size="10" name="wiki" />.org</td></tr>
		<tr><td>'.$I18N->msg('tosearch').': </td><td><textarea name="text" rows="10" cols="40"></textarea></td></tr>
		<tr><td colspan="2"><input type="submit" value="'.$I18N->msg('submit').'" /></td></tr>
		</table>
		</form>
		';
		
		return $pageForm;
	}
}