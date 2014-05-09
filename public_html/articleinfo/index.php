<?php
	
//Requires
	require_once( '../WebTool.php' );
	require_once( 'base.php' );


//Load WebTool class
	$wt = new WebTool( 'ArticleInfo', 'articleinfo', array("smarty", "sitenotice", "replag") );
	$wt->setMemLimit();
	
	$base = new ArticleInfoBase();
	$wt->content = $base->tmplPageForm;
	$wt->assign("lang", "en");
	$wt->assign("wiki", "wikipedia");
	
	$lang = $wgRequest->getSafeVal( "lang");
	$wiki = $wgRequest->getSafeVal( "wiki");
	$url = $lang.".".$wiki;

//Show form if &article parameter is not set (or empty)
	if( !$wgRequest->getSafeVal( 'getBool', 'article' ) ) {
		$wt->showPage($wt);
	}
	
//Now load configs for the graph templates
// 	$linegraph = new Smarty();
// 	$sizegraph = new Smarty();
// 	if( is_file( 'configs/' . $curlang . '.conf' ) ) {
// 		$linegraph->config_load( $curlang . '.conf', 'articleinfo' );
// 		$sizegraph->config_load( $curlang . '.conf', 'articleinfo' );
// 	}
// 	else {
// 		$linegraph->config_load( 'en.conf', 'articleinfo' );
// 		$sizegraph->config_load( 'en.conf', 'articleinfo' );
// 	}


//Set the article variables
	$article = trim( str_replace( array('&#39;','%20'), array('\'',' '), $wgRequest->getSafeVal( 'article' ) ) );
	$article = urldecode($article);


//Initialize Peachy
	try {
#		$site = Peachy::newWiki( null, null, null, 'http://'.$url.'/w/api.php' );

		$pageClass = $site->initPage( $article, null, !$wgRequest->getSafeVal( 'getBool', 'nofollowredir' ) );
	} catch( BadTitle $e ) {
		WebTool::toDie( $phptemp->get_config_vars( 'nosuchpage', $e->getTitle() ) );
	} catch( Exception $e ) {
		WebTool::toDie( $e->getMessage() );
	}


//Check for page existance
	$wt->assign( "title", $pageClass->get_title() );
	
	if( !$pageClass->exists() ) {
		$wt->error= $I18N->msg( 'nosuchpage' )." ".$article;
		$wt->showPage($wt);
	}

//Start doing the DB request
	$history = $base->getVars( 
		$pageClass, 
		$site, 
		$wgRequest->getSafeVal( 'getBool', 'nofollowredir' ),
		( $wgRequest->getSafeVal( 'getBool', 'begin' ) ) ? $wgRequest->getSafeVal( 'begin' ) : false, 
		( $wgRequest->getSafeVal( 'getBool', 'end' ) ) ? $wgRequest->getSafeVal( 'end' ) : false
	);
	
	if( !count( $history ) ) {
		$wt->error = $I18N->msg( 'norevisions' ); 
		$wt->showPage($wt);
	}
	if( count( $history ) == 50000 ) {
		$wt->error = $I18N->msg( 'toomanyrevisions' ) ;
		$wt->showPage($wt);
	}


//Get logs, for Edits over Time graph
	
	$data = $base->parseHistory( 
		$history, 
		( $wgRequest->getSafeVal( 'getBool', 'begin' ) ) ? $wgRequest->getSafeVal( 'begin' ) : false, 
		( $wgRequest->getSafeVal( 'getBool', 'end' ) ) ? $wgRequest->getSafeVal( 'end' ) : false, 
		$site, 
		$pageClass 
	);


//Now we can assign the Smarty variables!
	$wt->content = $base->tmplPageResult;
		$wt->assign( "page", $pageClass->get_title() );
		$wt->assign( "urlencodedpage", str_replace( '+', '_', urlencode( $pageClass->get_title() ) ) );
		$wt->assign( "totaledits", number_format( $data['count'] ) );
		$wt->assign( "minoredits", number_format( $data['minor_count'] ) );
		$wt->assign( "minoredits", number_format( $data['minor_count'] ) );
		$wt->assign( "anonedits", number_format( $data['anon_count'] ) );
		$wt->assign( "minorpct", number_format( ( $data['minor_count'] / $data['count'] ) * 100, 2 ) );
		$wt->assign( "anonpct", number_format( ( $data['anon_count'] / $data['count'] ) * 100, 2 ) );
		$wt->assign( "firstedit", date( 'd F Y, H:i:s', strtotime( $data['first_edit']['timestamp'] ) ) );
		$wt->assign( "firstuser", $data['first_edit']['user'] );
		$wt->assign( "lastedit", date( 'd F Y, H:i:s', strtotime( $data['last_edit'] ) ) );
		$wt->assign( "timebwedits", $data['average_days_per_edit'] );
		$wt->assign( "editspermonth", $data['edits_per_month'] );
		$wt->assign( "editsperyear", $data['edits_per_year'] );
		$wt->assign( "lastday", number_format( $data['count_history']['today'] ) );
		$wt->assign( "lastweek", number_format( $data['count_history']['week'] ) );
		$wt->assign( "lastmonth", number_format( $data['count_history']['month'] ) );
		$wt->assign( "lastyear", number_format( $data['count_history']['year'] ) );
		$wt->assign( "editorcount", number_format( $data['editor_count'] ) );
		$wt->assign( "editsperuser", $data['edits_per_editor'] );
		$wt->assign( "toptencount", number_format( $data['top_ten']['count'] ) );
		$wt->assign( "toptenpct", number_format( ( $data['top_ten']['count'] / $data['count'] ) * 100, 2 ) );
	
		$wt->assign( "graphanonpct", number_format( ( $data['anon_count'] / $data['count'] ) * 100, 2 ) );
		$wt->assign( "graphuserpct", number_format( 100 - ( ( $data['anon_count'] / $data['count'] ) * 100 ), 2 ) );
		$wt->assign( "graphminorpct", number_format( ( $data['minor_count'] / $data['count'] ) * 100, 2 ) );
		$wt->assign( "graphmajorpct", number_format( 100 - ( ( $data['minor_count'] / $data['count'] ) * 100 ), 2 ) );
		$wt->assign( "graphtoptenpct", number_format( ( $data['top_ten']['count'] / $data['count'] ) * 100, 2 ) );
		$wt->assign( "graphbottomninetypct", number_format( 100 - ( ( $data['top_ten']['count'] / $data['count'] ) * 100 ), 2 ) );
	
		
		//Year counts
		$yearpixels = $base->getYearPixels( $data['year_count'] );
		$pixelcolors = array( 'all' => '008800', 'anon' => '55FF55', 'minor' => 'FFFF55' );
		$wt->assign( "pixelcolors", $pixelcolors );
		
		$list = '
			<tr>
			<th>{#year#}</th>
			<th>{#count#}</th>
			<th>{#ips#}</th>
			<th>{#minor#}</th>
			<th>{#graph#} &mdash; <span style="background-color:#'.$pixelcolors["anon"].';border: 1px solid #000;padding: 0 0.3em 0 0.3em;">{#ips#}</span> &mdash; <span style="background-color:#'.$pixelcolors["minor"].';border: 1px solid #000;padding: 0 0.3em 0 0.3em;">{#minor#}</span> &mdash; <span style="background-color:#'.$pixelcolors["all"].';border: 1px solid #000;padding: 0 0.3em 0 0.3em;">{#alledits#}</span></th>
			</tr>
		  ';
		foreach ( $data['year_count'] as $key => $val ){
			$list .= '
			<tr>
			<td class="date">'.$key.'</td>
			<td>'.$val["all"].'</td>
			<td>'.$val["anon"].' ('.$val["pcts"]["anon"].'%)</td>
			<td>'.$val["minor"].' ('.$val["pcts"]["minor"].'%)</td>
			<td>
		 ';
			if ( $val["all"] != 0 ){
				$list .= '
				<div class="outer_bar" style="height:150%;background-color:#'.$pixelcolors["all"].';width:'.$yearpixels[$key]["all"].'px;">
				<div class="bar" style="height:50%;border-left:'.$yearpixels[$key]["anon"].'px solid #'.$pixelcolors["anon"].'"></div>
				<div class="bar" style="height:50%;border-left:'.$yearpixels[$key]["minor"].'px solid #'.$pixelcolors["minor"].'"></div>
				</div>
			  ';
			}
		
			$list .= '</td></tr>';
		}
		$wt->assign( "yearcountlist", $list);
		unset($list);
		
	
// 	$content->assign( "linegraph", true );
// 		$linegraph->assign( "data", $data['year_count'] );
// 		$linegraph->assign( "eventdata", $logs );
// 		$content->assign( "linegraphdata", md5( $pageClass->get_title() . '-' . $pageClass->get_id() ) );
// 		file_put_contents( 'data/' . md5( $pageClass->get_title() . '-' . $pageClass->get_id() ) . '.xml', $linegraph->fetch( 'linegraph.tpl' ));
// 		chmod( 'data/' . md5( $pageClass->get_title() . '-' . $pageClass->get_id() ) . '.xml', 0775);
	
		$monthpixels = $base->getMonthPixels( $data['year_count'] );
		$wt->assign( "monthpixels", $monthpixels );
		$wt->assign( "evenyears", $base->getEvenYears( array_keys( $data['year_count'] ) ) );
		
		$list = '';
		foreach ( $data['year_count'] as $key => $val ){
			$list .= '
			<tr>
			<th>{#month#}</th>
			<th>{#count#}</th>
			<th>{#ips#}</th>
			<th>{#minor#}</th>
			<th>{#graph#} &mdash; <span style="background-color:#'.$pixelcolors["anon"].';border: 1px solid #000;padding: 0 0.3em 0 0.3em;">{#ips#}</span> &mdash; <span style="background-color:#'.$pixelcolors["minor"].';border: 1px solid #000;padding: 0 0.3em 0 0.3em;">{#minor#}</span> &mdash; <span style="background-color:#'.$pixelcolors["all"].';border: 1px solid #000;padding: 0 0.3em 0 0.3em;">{#alledits#}</span></th>
			</tr>
		   ';
			foreach ( $val["months"] as $month => $info ){
				$list .= '
				<tr>
				<td class="date">'.$month.'/'.$key.'</td>
				<td>'.$info["all"].'</td>
				<td>'.$info["anon"].' ('.$info["pcts"]["anon"].'%)</td>
				<td>'.$info["minor"].' ('.$info["pcts"]["minor"].'%)</td>
				<td>';
				if ( $info["all"] != 0 ){
					$list .= '
					<div class="outer_bar" style="height:150%;background-color:#'.$pixelcolors["all"].';width:'.$monthpixels[$key][$month]["all"].'px;">
					<div class="bar" style="height:50%;border-left:'.$monthpixels[$key][$month]["anon"].'px solid #'.$pixelcolors["anon"].'"></div>
					<div class="bar" style="height:50%;border-left:'.$monthpixels[$key][$month]["minor"].'px solid #'.$pixelcolors["minor"].'"></div>
					</div>
				  ';
				}
				$list .= '</td></tr>';
			}
		}
		$wt->assign( "monthcountlist", $list);
		unset($list);
	
// 	$content->assign( "sizegraph", true );
// 		$sizegraph->assign( "data", $data['year_count'] );
// 		$content->assign( "sizegraphdata", md5( $pageClass->get_title() . '-' . $pageClass->get_id() . '-line' ) );
// 		file_put_contents( 'data/' . md5( $pageClass->get_title() . '-' . $pageClass->get_id()  . '-line' ) . '.xml', $sizegraph->fetch( 'sizegraph.tpl' ));
// 		chmod( 'data/' . md5( $pageClass->get_title() . '-' . $pageClass->get_id()  . '-line' ) . '.xml', 0775);

	//usertable	
	$list = '';
	foreach( $data['editors'] as $user => $info ){
		if ( $wt->iin_array( $user, $data['top_fifty'] ) ){
			$list .= '
			<tr>
			<td class="date"><a href="//'.$url.'/wiki/User:'.$info["urlencoded"].'" >'.$user.'</a> (<a title="edit count" href="../pcount?user='.$info["urlencoded"].'&amp;lang='.$lang.'&amp;wiki='.$wiki.'" >ec</a>)</td>
				<td>'.$info["all"].'</td>
				<td>'.$info["minor"].' ('.$info["minorpct"].'%)</td>
				<td>'.$info["first"].'</td>
				<td>'.$info["last"].'</td>
				<td>'.$info["atbe"].'</td>
				<td>'.$info["size"].' KB</td>
			</tr>
			';
		}
		
	}
	$wt->assign( "usertable", $list );
	unset($list);

	$wt->assign( "url", $url );
	$wt->assign( "lang", $lang );
	$wt->assign( "wiki", $wiki );

unset( $base, $list );
$wt->showPage($wt);










