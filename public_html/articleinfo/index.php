<?php
	
//Requires
	require_once( '../WebTool.php' );
	require_once( '../Graph.php' );
	require_once( '../ArticleInfo.php' );


//Load WebTool class
	$wt = new WebTool( 'ArticleInfo', 'articleinfo', array("database", "api") );
	$wt->setMemLimit();
	set_time_limit ( 60 );
	
	$wt->content = getPageTemplate( "form" );
	$wt->assign("lang", "en");
	$wt->assign("wiki", "wikipedia");
	
	$article = $wgRequest->getVal( 'article' );
	$begintime = $wgRequest->getVal( 'begin' );
	$endtime = $wgRequest->getVal( 'end' );
	$nofollow = !$wgRequest->getBool( 'nofollowredir');
	
	$lang = $wgRequest->getVal( "lang");
	$wiki = $wgRequest->getVal( "wiki");
	$url = $lang.".".$wiki;

//Show form if &article parameter is not set (or empty)
	if( !$wgRequest->getVal( 'article' ) ) {
		$wt->showPage();
	}
	
	
//Start dbr, site = global Objects init in WebTool
	$ai = new ArticleInfo( $dbr, $site, $article, $begintime, $endtime, $nofollow );
	
	if( $ai->historyCount == 0 ) {
		$wt->toDie( 'norevisions', $article ); 
	}
	if( $ai->error ) {
		$wt->toDie( 'error' , $ai->error ) ;
	}


//Now we can assign the Smarty variables!
	$wt->content = getPageTemplate( "result" );

	$wt->assign( "page", $ai->pagetitleFull );
	$wt->assign( "urlencodedpage", str_replace( '+', '_', urlencode( $ai->pagetitleFull ) ) );
	$wt->assign( 'pageid', $ai->pageid );
	$wt->assign( "totaledits", $wt->numFmt( $ai->data['count'] ) );
	$wt->assign( "editorcount", $wt->numFmt( $ai->data['editor_count'] ) );
	$wt->assign( "minoredits", $wt->numFmt( $ai->data['minor_count'] ) );
	$wt->assign( "minoredits", $wt->numFmt( $ai->data['minor_count'] ) );
	$wt->assign( "anonedits", $wt->numFmt( $ai->data['anon_count'] ) );
	$wt->assign( "minorpct", $wt->numFmt( ( $ai->data['minor_count'] / $ai->data['count'] ) * 100, 1 ) );
	$wt->assign( "anonpct", $wt->numFmt( ( $ai->data['anon_count'] / $ai->data['count'] ) * 100, 1 ) );
	$wt->assign( "firstedit", date( 'd F Y, H:i:s', strtotime( $ai->data['first_edit']['timestamp'] ) ) );
	$wt->assign( "firstuser", $ai->data['first_edit']['user'] );
	$wt->assign( "lastedit", date( 'd F Y, H:i:s', strtotime( $ai->data['last_edit'] ) ) );
	$wt->assign( "timebwedits", $ai->data['average_days_per_edit'] );
	$wt->assign( "editspermonth", $ai->data['edits_per_month'] );
	$wt->assign( "editsperyear", $ai->data['edits_per_year'] );
	$wt->assign( "lastday", $wt->numFmt( $ai->data['count_history']['today'] ) );
	$wt->assign( "lastweek", $wt->numFmt( $ai->data['count_history']['week'] ) );
	$wt->assign( "lastmonth", $wt->numFmt( $ai->data['count_history']['month'] ) );
	$wt->assign( "lastyear", $wt->numFmt( $ai->data['count_history']['year'] ) );
	$wt->assign( "editsperuser", $ai->data['edits_per_editor'] );
	$wt->assign( "toptencount", $wt->numFmt( $ai->data['top_ten']['count'] ) );
	$wt->assign( "toptenpct", $wt->numFmt( ( $ai->data['top_ten']['count'] / $ai->data['count'] ) * 100, 1 ) );

	$wt->assign( "graphanonpct", number_format( ( $ai->data['anon_count'] / $ai->data['count'] ) * 100, 1 ) );
	$wt->assign( "graphuserpct", number_format( 100 - ( ( $ai->data['anon_count'] / $ai->data['count'] ) * 100 ), 1 ) );
	$wt->assign( "graphminorpct", number_format( ( $ai->data['minor_count'] / $ai->data['count'] ) * 100, 1 ) );
	$wt->assign( "graphmajorpct", number_format( 100 - ( ( $ai->data['minor_count'] / $ai->data['count'] ) * 100 ), 1 ) );
	$wt->assign( "graphtoptenpct", number_format( ( $ai->data['top_ten']['count'] / $ai->data['count'] ) * 100, 1 ) );
	$wt->assign( "graphbottomninetypct", number_format( 100 - ( ( $ai->data['top_ten']['count'] / $ai->data['count'] ) * 100 ), 1 ) );

	$wt->assign( "wikidata", $ai->wikidatalink );
	$wt->assign( "totalauto", $ai->data["automated_count"] );

	$pixelcolors = array( 'all' => '4D89F9', 'anon' => '55FF55', 'minor' => 'ff00ff' );
	$wt->assign( "pixelcolors", $pixelcolors );
		
//Year counts table
	$yearpixels = $ai->getYearPixels();
	
	$list = '
		<tr>
		<th>{#year#}</th>
		<th>{#count#}</th>
		<th>{#ips#}</th>
		<th>{#ips#} %</th>
		<th>{#minor#}</th>
		<th>{#minor#} %</th>
		<th>
			<span class=legendicon style="background-color:#'.$pixelcolors["all"].'"> </span> {#alledits#} &nbsp;&bull;&nbsp; 
			<span class=legendicon style="background-color:#'.$pixelcolors["anon"].'"> </span> {#ips#} &nbsp;&bull;&nbsp; 
			<span class=legendicon style="background-color:#'.$pixelcolors["minor"].'"> </span> {#minor#} 
		</th>
		</tr>
	  ';
	foreach ( $ai->data['year_count'] as $key => $val ){
		$list .= '
			<tr>
			<td class=date >'.$key.'</td>
			<td class=tdnum >'.$val["all"].'</td>
			<td class=tdnum >'.$val["anon"].'</td>
			<td class=tdnum >'.$wt->numFmt( $val["pcts"]["anon"],1 ).'%</td>
			<td class=tdnum >'.$val["minor"].'</td>
			<td class=tdnum >'.$wt->numFmt( $val["pcts"]["minor"],1 ).'%</td>
			<td>
	 	';
		if ( $val["all"] != 0 ){
			$list .= '
			<div class="bar" style="height:40%;background-color:#'.$pixelcolors["all"].';width:'.$yearpixels[$key]["all"].'px;"></div>
			<div class="bar" style="height:30%;border-left:'.$yearpixels[$key]["anon"].'px solid #'.$pixelcolors["anon"].'"></div>
			<div class="bar" style="height:30%;border-left:'.$yearpixels[$key]["minor"].'px solid #'.$pixelcolors["minor"].'"></div>
		  ';
		}
	
		$list .= '</td></tr>';
	}
	$wt->assign( "yearcountlist", $list);
	unset( $list, $yearpixels );
		
	
//Month graphs	
	$monthpixels = $ai->getMonthPixels();
	$wt->assign( "monthpixels", $monthpixels );
	$wt->assign( "evenyears", $ai->getEvenYears() );
	
	$list = '';
	foreach ( $ai->data['year_count'] as $key => $val ){
		$list .= '
			<tr>
			<th>{#month#}</th>
			<th>{#count#}</th>
			<th>{#ips#}</th>
			<th>{#ips#} %</th>
			<th>{#minor#}</th>
			<th>{#minor#} %</th>
			<th>
				<span class=legendicon style="background-color:#'.$pixelcolors["all"].'"> </span> {#alledits#} &nbsp;&bull;&nbsp; 
				<span class=legendicon style="background-color:#'.$pixelcolors["anon"].'"> </span> {#ips#} &nbsp;&bull;&nbsp; 
				<span class=legendicon style="background-color:#'.$pixelcolors["minor"].'"> </span> {#minor#} 
			</th>
			</tr>
		  ';
		foreach ( $val["months"] as $month => $info ){
			$list .= '
				<tr>
				<td class="date">'.$key.' / '.$month.'</td>
				<td class=tdnum >'.$info["all"].'</td>
				<td class=tdnum >'.$info["anon"].'</td>
				<td class=tdnum >'.$wt->numFmt( $info["pcts"]["anon"],1 ).'%</td>
				<td class=tdnum >'.$info["minor"].'</td>
				<td class=tdnum >'.$wt->numFmt( $info["pcts"]["minor"],1 ).'%</td>
				<td>
		 	';
			if ( $info["all"] != 0 ){
				$list .= '
				<div class="bar" style="height:30%;background-color:#'.$pixelcolors["all"].';width:'.$monthpixels[$key][$month]["all"].'px;"></div>
				<div class="bar" style="height:30%;border-left:'.$monthpixels[$key][$month]["anon"].'px solid #'.$pixelcolors["anon"].'"></div>
				<div class="bar" style="height:30%;border-left:'.$monthpixels[$key][$month]["minor"].'px solid #'.$pixelcolors["minor"].'"></div>
			  ';
			}
			$list .= '</td></tr>';
		}
		$list .= '<tr class=monthsep style="border:none"; ><td colspan=20 style="border:none" ></td></tr>';
	}
	$wt->assign( "monthcountlist", $list);
	unset( $list, $monthpixels );

	$chartImgMonth = xGraph::makeArticleChartGoogle("month", $ai->data['year_count'] );
	$wt->assign('chartImgMonth', "<img src='$chartImgMonth' alt='bla' />" );


//usertable	
	$list = '';
	foreach( $ai->data['editors'] as $user => $info ){
		if ( $wt->iin_array( $user, $ai->data['top_fifty'] ) ){
			$list .= '
			<tr>
			<td class="date"><a href="//{$url}/wiki/User:'.$info["urlencoded"].'" >'.$user.'</a></td>
			<td> <a title="edit count" href="../ec/?user='.$info["urlencoded"].'&amp;lang='.$lang.'&amp;wiki='.$wiki.'" >ec</a></td>
			<td class=tdnum >'.$info["all"].'</td>
			<td class=tdnum >'.$info["minor"].'</td>
			<td class=tdnum >'.$wt->numFmt( $info["minorpct"],1 ).'%</td>
			<td>'.$info["first"].'</td>
			<td>'.$info["last"].'</td>
			<td class=tdnum >'.$wt->numFmt( $info["atbe"],1 ).' {#days#}</td>
			<td class=tdnum >'.$info["size"].' KB</td>
			</tr>
			';
		}					
	}
	$wt->assign( "usertable", $list );
	$chartImgTopEditor = xGraph::makePieGoogleTopEditors( $ai->data["count"], $ai->data["editors"] );
	$wt->assign( 'chartTopEditors', "<img src='$chartImgTopEditor' alt='bla' />" );


//tools list
	$list = '<table>';
	foreach( $ai->data["tools"] as $tool => $count ){
		$list .= '<tr><td>'.$tool.'</td><td> &middot; '.$wt->numFmt($count).'</td></tr>';
	}
	$list .= '</table>';			
	$wt->assign( 'toolslist', $list );
	
	unset($list);

	$wt->assign( "url", $url.".org" );
	$wt->assign( "lang", $lang );
	$wt->assign( "wiki", $wiki );

unset( $ai, $list );
$wt->showPage();



/**************************************** templates ****************************************
 *
*/
function getPageTemplate( $type ){

	$templateForm = '
			
	<script type="text/javascript">
		function switchShow( id ) {
			if(document.getElementById(id).style.display == "none") {
				document.getElementById(id).style.display = "block";
			}
			else{
				document.getElementById(id).style.display = "none";
			}
		}
	</script>
	
	<br />
	<form action="?" method="get" accept-charset="utf-8">
	<table>
		<tr><td>{#article#}: </td><td><input type="text" name="article" /> <input type="checkbox" name="nofollowredir" /> {#nofollowredir#}</td></tr>
		<tr><td>{#wiki#}: </td><td><input type="text" value="{$lang}" name="lang" size="9" />.<input type="text" value="{$wiki}" size="10" name="wiki" />.org</td></tr>
		<tr><td>{#start#}: </td><td><input type="text" name="begin" /></td></tr>
		<tr><td>{#end#}: </td><td><input type="text" name="end" /></td></tr>
		<tr><td colspan="2"><input type="submit" value="{#submit#}" /></td></tr>
	</table>
	</form><br />
	';
	
	
	$templateResult = '
			
	<script type="text/javascript">
	function switchShow( id ) {
		if(document.getElementById(id).style.display == "none") {
			document.getElementById(id).style.display = "block";
		}
		else{
			document.getElementById(id).style.display = "none";
		}
	}
	</script>
	<div style="text-align:center; font-weight:bold; " >
			<span style="padding-right:10px;" >{#page#} &nbsp;&bull; </span>
			<a style=" font-size:2em; "href="//{$url}/wiki/{$urlencodedpage}">{$page}</a> 
			<span style="padding-left:10px;" > &bull;&nbsp; {$url} </span>
	</div>
	<h3  style="margin-top:0px;">{#generalstats#} &nbsp;&nbsp;<span style="font-size:75%;">[<a href="javascript:switchShow( \'generalstats\' )">show/hide</a>]</span></h3>
	<div id = "generalstats">
	<table>
	<tr><td style="vertical-align:top">
		<table>
			<tr><td>ID:</td><td><a href="//{$url}/w/index.php?title={$urlencodedpage}&action=info" >{$pageid}</a></td></tr>
			<tr><td>Wikidata:</td><td>{$wikidata}</td></tr>
			<tr><td>{#totaledits#}:</td><td>{$totaledits}</td></tr>
			<tr><td>{#editorcount#}:</td><td>{$editorcount}</td></tr>
			<tr><td colspan=20 ></td></tr>	
			<tr><td>{#firstedit#}:</td><td>{$firstedit}</td></tr>
			<tr><td>{#firstedit#} {#user#}:</td><td><a href="//{$url}/wiki/User:{$firstuser}" >{$firstuser}</a></td></tr>
			<tr><td>{#lastedit#}:</td><td>{$lastedit}</td></tr>
			<tr><td colspan=20 ></td></tr>	
			<tr><td>{#minoredits#}:</td><td>{$minoredits} ({$minorpct}%)</td></tr>
			<tr><td>{#anonedits#}:</td><td>{$anonedits} ({$anonpct}%)</td></tr>
			<tr><td colspan=20 ></td></tr>	
			<tr><td>{#timebwedits#}:</td><td>{$timebwedits} {#days#}</td></tr>
			<tr><td>{#editspermonth#}:</td><td>{$editspermonth}</td></tr>
			<tr><td>{#editsperyear#}:</td><td>{$editsperyear}</td></tr>
			<tr><td colspan=20 ></td></tr>	
			<tr style="border-top:1px solid silver;" ><td>{#lastday#}:</td><td>{$lastday}</td></tr>
			<tr><td>{#lastweek#}:</td><td>{$lastweek}</td></tr>
			<tr><td>{#lastmonth#}:</td><td>{$lastmonth}</td></tr>
			<tr><td>{#lastyear#}:</td><td>{$lastyear}</td></tr>
			<tr><td colspan=20 ></td></tr>		
			<tr><td>{#editsperuser#}:</td><td>{$editsperuser}</td></tr>
			<tr><td style="max-width:300px" >{#toptencount#}:&nbsp;&nbsp;&nbsp;</td><td>{$toptencount} ({$toptenpct}%)</td></tr>
		</table>
		</td>
		<td style="vertical-align:top" >
		<table>
			<tr><td style="min-width:150px"></td><td></td>
			<tr><td >{#totalauto#}:</td><td>{$totalauto}</td>
			<tr><td></td><td>{$toolslist}</td>
		</table>
		</td>
	</tr>
	</table>
	</div>

	<div >
	<table>
		<tr>
		<td><img src="//chart.googleapis.com/chart?cht=p&amp;chd=t:{$graphuserpct},{$graphanonpct}&amp;chs=280x100&amp;chdl={#users#}%20%28{$graphuserpct}%%29|{#ips#}%20%28{$graphanonpct}%%29&amp;chco=FF5555|55FF55&amp;chf=bg,s,00000000" alt="{#anonalt#}" /></td>
		<td><img src="//chart.googleapis.com/chart?cht=p&amp;chd=t:{$graphminorpct},{$graphmajorpct}&amp;chs=280x100&amp;chdl={#minor#}%20%28{$graphminorpct}%%29|{#major#}%20%28{$graphmajorpct}%%29&amp;chco=FFFF55|FF55FF&amp;chf=bg,s,00000000" alt="{#minoralt#}" /></td>
		<td><img src="//chart.googleapis.com/chart?cht=p&amp;chd=t:{$graphtoptenpct},{$graphbottomninetypct}&amp;chs=280x100&amp;chdl={#topten#}%20%28{$graphtoptenpct}%%29|{#bottomninety#}%20%28{$graphbottomninetypct}%%29&amp;chco=5555FF|55FFFF&amp;chf=bg,s,00000000" alt="{#toptenalt#}" /></td>
		</tr>
	</table>
	</div>

	<div style="padding:20px">
		{$chartImgMonth}
	</div>

	<!-- yeargraphs -->
	<h3>{#yearcounts#} &nbsp;&nbsp;<span style="font-size:75%;">[<a href="javascript:switchShow( \'yearcounts\' )">show/hide</a>]</span></h3>
	<div id="yearcounts">
	<table class="months wikitable sortable">
		{$yearcountlist}	
	</table>
	</div>

	<!-- $usertable -->
	<h3>{#usertable#} &nbsp;&nbsp;<span style="font-size:75%;">[<a href="javascript:switchShow( \'usertable\' )">show/hide</a>]</span></h3>
	<div>{$chartTopEditors}</div>
	<div id="usertable">
	<table class="months wikitable sortable">
		<tr>
			<th>{#user#}</th>
			<th></th>
			<th>{#count#}</th>
			<th>{#minor#}</th>
			<th>%</th>
			<th>{#firstedit#}</th>
			<th>{#lastedit#}</th>
			<th>{#atbe#}</th>
			<th>{#avgsize#}</th>
		</tr>
		{$usertable}
	</table>
	</div>
			
	<!-- monthgraphs -->
	<h3>{#monthcounts#} &nbsp;&nbsp;<span style="font-size:75%;">[<a href="javascript:switchShow( \'monthcounts\' )">show/hide</a>]</span></h3>
	<div id="monthcounts">
	<table class="months wikitable sortable">
		{$monthcountlist}
	</table>
	</div>
	';
	
	if( $type == "form" ) { return $templateForm; }
	if( $type == "result" ) { return $templateResult; }
}




