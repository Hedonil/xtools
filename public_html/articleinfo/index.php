<?php
	
//Requires
	require_once( '../WebTool.php' );
	require_once( '../ArticleInfo.php' );
	require_once( '../Graph.php' );

//Load WebTool class
	$wt = new WebTool( 'Page history statistics', 'articleinfo', array() );
	$wt->setLimits();
	
	$wt->content = getPageTemplate( "form" );
	$wt->assign("lang", "en");
	$wt->assign("wiki", "wikipedia");
	
	$article = $wgRequest->getVal( 'article' );
	$article = $wgRequest->getVal( 'page', $article );
	
	$begintime = $wgRequest->getVal( 'begin' );
	$endtime = $wgRequest->getVal( 'end' );
	$nofollow = !$wgRequest->getBool( 'nofollowredir');
	
	$wi = $wt->getWikiInfo();
		$lang = $wi->lang;
		$wiki = $wi->wiki;
		$domain = $wi->domain;

//Show form if &article parameter is not set (or empty)
	if( !$article ) {
		$wt->showPage();
	}
	
	
//Start dbr, site = global Objects init in WebTool
	$m1 = microtime( true );

	$dbr = $wt->loadDatabase( $lang, $wiki );
	
	$ai = new ArticleInfo( $dbr, $wi, $article, $begintime, $endtime, $nofollow );
	
	$perflog->add('create ai', microtime(true) - $m1 ); 
	
	if( $ai->historyCount == 0 ) {
		$wt->toDie( 'norevisions', $article ); 
	}
	if( $ai->historyCount == 50000 ) {
		$wt->alert = $I18N->msg( 'toomanyrevisions');
	}
	if( $ai->error ) {
		$wt->toDie( 'error' , $ai->error ) ;
	}


$m[0] = microtime(true);
$m[1] = microtime(true);
//Now we can assign the Smarty variables!
	$wt->content = getPageTemplate( "result" );

	$wt->assign( "page", $ai->pagetitleFull );
	$wt->assign( "urlencodedpage", $ai->pagetitleFullUrl );
	$wt->assign( 'pageid', $ai->pageid );
	$wt->assign( "totaledits", $wt->numFmt( $ai->data['count'] ) );
	$wt->assign( "editorcount", $wt->numFmt( $ai->data['editor_count'] ) );
	$wt->assign( "pagesize", $wt->numFmt( $ai->data['current_size'] ) );
	$wt->assign( "minoredits", $wt->numFmt( $ai->data['minor_count'] ) );
	$wt->assign( "minoredits", $wt->numFmt( $ai->data['minor_count'] ) );
	$wt->assign( "anonedits", $wt->numFmt( $ai->data['anon_count'] ) );
	$wt->assign( "minorpct", $wt->numFmt( ( $ai->data['minor_count'] / $ai->data['count'] ) * 100, 1 ) );
	$wt->assign( "anonpct", $wt->numFmt( ( $ai->data['anon_count'] / $ai->data['count'] ) * 100, 1 ) );
	$wt->assign( "autoedits", $wt->numFmt( ( $ai->data['automated_count']) ) );
	$wt->assign( "autoeditspct", $wt->numFmt( ( $ai->data['automated_count'] / $ai->data['count'] ) * 100, 1 ) );
	$wt->assign( "firstedit", $wt->dateFmt( date('Y-m-d H:i:s', strtotime($ai->data['first_edit']['timestamp']) ) ) );
	$wt->assign( "firstuser", $ai->data['first_edit']['user'] );
	$wt->assign( "latestedit", $wt->dateFmt( date( 'Y-m-d H:i:s', strtotime( $ai->data['last_edit']['timestamp'] ) ) ) );
	$wt->assign( "latestuser", $ai->data['last_edit']['user'] );

	$wt->assign( "maxadd", $wt->dateFmt( date( 'Y-m-d H:i:s', strtotime( $ai->data['max_add']['timestamp'] ) ) ) );
	$wt->assign( "maxadduser", $ai->data['max_add']['user'] );
	$wt->assign( "maxaddnum", $wt->numFmt($ai->data['max_add']['size'] ) );
	$wt->assign( "maxadddiff", $ai->data['max_add']['revid'] );
	$wt->assign( "maxdel", $wt->dateFmt( date( 'Y-m-d H:i:s', strtotime( $ai->data['max_del']['timestamp'] ) ) ) );
	$wt->assign( "maxdeluser", $ai->data['max_del']['user'] );
	$wt->assign( "maxdelnum", $wt->numFmt($ai->data['max_del']['size'] ) );
	$wt->assign( "maxdeldiff", $ai->data['max_del']['revid'] );
	
	$wt->assign( "timebwedits", $wt->numFmt($ai->data['average_days_per_edit'] ),1 );
	$wt->assign( "editspermonth", $wt->numFmt($ai->data['edits_per_month'] ), 1);
	$wt->assign( "editsperyear", $wt->numFmt($ai->data['edits_per_year'] ), 1);
	$wt->assign( "lastday", $wt->numFmt( $ai->data['count_history']['today'] ) );
	$wt->assign( "lastweek", $wt->numFmt( $ai->data['count_history']['week'] ) );
	$wt->assign( "lastmonth", $wt->numFmt( $ai->data['count_history']['month'] ) );
	$wt->assign( "lastyear", $wt->numFmt( $ai->data['count_history']['year'] ) );
	$wt->assign( "editsperuser", $wt->numFmt( $ai->data['edits_per_editor'],1 ));
	$wt->assign( "toptencount", $wt->numFmt( $ai->data['top_ten']['count'] ) );
	$wt->assign( "toptenpct", $wt->numFmt( ( $ai->data['top_ten']['count'] / $ai->data['count'] ) * 100, 1 ) );
	
	$wt->assign( 'pagewatchers', $ai->pagewatchers );
	$wt->assign( 'pageviews60', $wt->numFmt( $ai->pageviews->sumhits ) );
	$wt->assign( "totalauto", $ai->data["automated_count"] );
	
	$wdlink = "–";
	if ( $ai->wikidataItemID ){
		$wdlink = '<span><a href="//www.wikidata.org/wiki/'.$ai->wikidataItemID.'#sitelinks-wikipedia" >'.$ai->wikidataItemID.'</a> &middot; '.count($ai->wikidataItems).' Items</span>';
	}
	$wt->assign( 'wikidatalink', $wdlink );
	$wt->assign( 'linkreasonator', ( $ai->wikidataItemID )  ? '<a href="//tools.wmflabs.org/reasonator/?q='.$ai->wikidataItemID.'" >Reasonator (Wikidata)</a>' : '' );

$m[2] = microtime(true);
//Colors
	$pixelcolors = array( 
				'all' => '3399FF', 
				'anon' => '66CC00', // 55FF55', 
				'minor' => 'cc9999',
				'size' => '999999',
				'protect' => 'ff3300',
			);
	$wt->assign( "pixelcolors", $pixelcolors );

$m[3] = microtime(true);
//make minicharts
	$graphanonpct = number_format( ( $ai->data['anon_count'] / $ai->data['count'] ) * 100, 1 );
	$graphuserpct = number_format( 100 - $graphanonpct, 1 );
	$graphminorpct = number_format( ( $ai->data['minor_count'] / $ai->data['count'] ) * 100, 1 );
	$graphmajorpct = number_format( 100 - $graphminorpct, 1 );
	$graphtoptenpct = number_format( ( $ai->data['top_ten']['count'] / $ai->data['count'] ) * 100, 1 );
	$graphbottomninetypct = number_format( 100 - $graphtoptenpct, 1 );
	
	$gcolor1 = '99CCFF';
	$gcolor2 = '99CC00';
	
	$anonalt = 'Pie Chart comparing the amount of user edits vs. anonymous edits';
	$minoralt = 'Pie Chart comparing the amount of major edits vs. minor edits';
	$toprenalt = 'Pie Chart comparing the amount of edits made by the top 10% of contributors vs. the bottom 90%';
	
	$graphuser = "<img src=\"//chart.googleapis.com/chart?cht=p&amp;chd=t:$graphuserpct,$graphanonpct&amp;chs=280x100&amp;chdl={#users#}%20%28$graphuserpct%%29|{#ips#}%20%28$graphanonpct%%29&amp;chco=$gcolor1|$gcolor2&amp;chf=bg,s,00000000 \" alt=\"$anonalt \" />";
	$graphminor = "<img src=\"//chart.googleapis.com/chart?cht=p&amp;chd=t:$graphminorpct,$graphmajorpct&amp;chs=280x100&amp;chdl={#minor#}%20%28$graphminorpct%%29|{#major#}%20%28$graphmajorpct%%29&amp;chco=$gcolor1|$gcolor2&amp;chf=bg,s,00000000 \" alt=\"$minoralt \" />";
	$graphtopten = "<img src=\"//chart.googleapis.com/chart?cht=p&amp;chd=t:$graphtoptenpct,$graphbottomninetypct&amp;chs=280x100&amp;chdl={#topten#}%20%28$graphtoptenpct%%29|{#bottomninety#}%20%28$graphbottomninetypct%%29&amp;chco=$gcolor1|$gcolor2&amp;chf=bg,s,00000000 \" alt=\"$toptenalt\" />";
	$wt->assign( 'graphuser', $graphuser );
	$wt->assign( 'graphminor', $graphminor );
	$wt->assign( 'graphtopten', $graphtopten );
	

$m[4] = microtime(true);
//Year counts 
	//$yearpixels = $ai->getYearPixels();
	$chartImgYears = xGraph::makeChartArticle("year", $ai->data['year_count'], $ai->pageLogs["years"], $pixelcolors );
	$wt->assign('chartImgYears', "<img src='$chartImgYears' alt='bla' />" );
	
	$list = '
		<tr>
		<th>{#year#}</th>
		<th><span class=legendicon style="background-color:#'.$pixelcolors["all"].'"> </span> {#all#}</th>
		<th><span class=legendicon style="background-color:#'.$pixelcolors["anon"].'"> </span> {#ips#}</th>
		<th><span class=legendicon style="background-color:#'.$pixelcolors["anon"].'"> </span> {#ips#} <small>%</small></th>
		<th><span class=legendicon style="background-color:#'.$pixelcolors["minor"].'"> </span> {#minor#}</th>
		<th><span class=legendicon style="background-color:#'.$pixelcolors["minor"].'"> </span> {#minor#} <small>%</small></th>
		<th> {#events#}</th>
		</tr>
	  ';
	foreach ( $ai->data['year_count'] as $year => $val ){
		$list .= '
			<tr>
			<td class=date >'.$year.'</td>
			<td class=tdnum >'.$val["all"].'</td>
			<td class=tdnum >'.$val["anon"].'</td>
			<td class=tdnum >'.$wt->numFmt( $val["pcts"]["anon"],1 ).'<small>%</small></td>
			<td class=tdnum >'.$val["minor"].'</td>
			<td class=tdnum >'.$wt->numFmt( $val["pcts"]["minor"],1 ).'<small>%</small></td>
		';
			$actions = "";
			ksort($ai->pageLogs["years"][ $year ]);
			foreach ( $ai->pageLogs["years"][ $year ] as $logaction => $count ){
				$actions[] = "$logaction: $count";
			}
			$list .= "<td>".implode(" &middot; ", $actions)."</td>";
		$list .= '</tr>';
	}
	$wt->assign( "yearcountlist", $list);
	unset( $list, $yearpixels );
		
	
$m[5] = microtime(true);	
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
				<td style="height:10px;">
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


$m[6] = microtime(true);	
//usertable	
	$list = '';
	foreach( $ai->data['editors'] as $user => $info ){
		if ( $wt->iin_array( $user, $ai->data['top_fifty'] ) ){
			$list .= '
			<tr>
			<td><a href="//{$domain}/wiki/User:'.$info["urlencoded"].'" >'.$user.'</a></td>
			<td>
				<a title="edit count" href="'.XTOOLS_BASE_WEB_DIR.'/ec/?user='.$info["urlencoded"].'&amp;lang='.$lang.'&amp;wiki='.$wiki.'" >ec</a>
				<a title="edit count" href="'.XTOOLS_BASE_WEB_DIR.'/topedits/?user='.$info["urlencoded"].'&amp;lang='.$lang.'&amp;wiki='.$wiki.'&amp;page='.$ai->pagetitleFullUrl.'" > &middot topedits</a>
			</td>
			<td class=tdnum >'.$info["all"].'</td>
			<td class=tdnum >'.$info["minor"].'</td>
			<td class=tdnum >'.$wt->numFmt( $info["minorpct"],1 ).'<small>%</small></td>
			<td class=tddate >'.$info["first"].'</td>
			<td class=tddate >'.$info["last"].'</td>
			<td class=tdnum >'.$wt->numFmt( $info["atbe"],1 ).'</td>
			<td class=tdnum >'.$wt->numFmt( $ai->data["textshares"][$user]["all"]).'</td>
			</tr>
			';
		}					
	}
#<td class=tdnum >'.$info["size"].' KB</td>
	$wt->assign( "usertable", $list );
	$chartImgTopEditors = xGraph::makePieTopEditors( "Top 10 by Editcount", $ai->data["count"], $ai->data["editors"] );
	$wt->assign( 'chartTopEditorsByCount', "<img src='$chartImgTopEditors' alt='bla' />" );
	$chartImgTopEditors = xGraph::makePieTopEditors( "Top 10 by added text", $ai->data["textshare_total"], $ai->data["textshares"] );
	$wt->assign( 'chartTopEditorsByText', "<img src='$chartImgTopEditors' alt='bla' />" );
	

$m[7] = microtime(true);	
//Pageviews
	$charttitle = $I18N->msg('pageviews')." (60 ".$I18N->msg('days')."):  ".$wt->numFmt( $ai->pageviews->sumhits );
	$chartbase = "//chart.googleapis.com/chart?";
	$chdata = array(
			'cht' => 'bvg',
			"chtt" => $charttitle,
			'chs' => '1000x250',
			"chf" => "bg,s,00000000",
			'chco' => $gcolor1,
			'chd' => 't1:'.implode(",", $ai->pageviews->series->s0->Data),
			'chds' => 'a',
			'chbh' => '10,1,5',
			'chxt' => 'y,y,x',
			'chxl' => '1:||Hits||2:|'.implode('|', $ai->pageviews->series->Time->Data),
//			'chxr' => '0,0,'.$maxeditTotal.'|3,0,'.$maxsizeTotal,
		);

	$chartImgPageviews = "<a href=\"//tools.wmflabs.org/wikiviewstats/?latest=60&amp;lang=$lang&amp;wiki=$wiki&amp;page=$ai->pagetitleFullUrl\" ><img src=\"".$chartbase.http_build_query($chdata)."\" alt='bla' /></a>";
	$wt->assign( 'chartImgPageviews', $chartImgPageviews);
	

//Assign links
	foreach ( $ai->links as $link ){
		$wt->assign( $link["type"], $wt->numFmt( $link["value"] ) );
	}
	
$m[8] = microtime(true);
//tools list
	$list = '<table>';
	foreach( $ai->data["tools"] as $tool => $count ){
		$list .= '<tr><td>'.$tool.'</td><td> &middot; '.$wt->numFmt($count).'</td></tr>';
	}
	$list .= '</table>';			
	$wt->assign( 'toolslist', $list );
	
	unset($list);

	$wt->assign( "domain", $domain );
	$wt->assign( "lang", $lang );
	$wt->assign( "wiki", $wiki );
	
$m[9] = microtime(true);

unset( $ai, $list );

foreach ($m as $i => $mark ){
	if ($i >0 )
	$perflog->add( "output $i", $m[$i] - $m[$i-1]);
}
$perflog->add( "output total", microtime(true) - $m[0]);

$wt->showPage();



/**************************************** templates ****************************************
 *
*/
function getPageTemplate( $type ){

	$templateForm = '
			
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
			
	<div class="caption" >
			<a style=" font-size:1.5em; " href="http://{$domain}/wiki/{$urlencodedpage}">{$page}</a>
			<span style="padding-left:10px;" > &bull;&nbsp; {$domain} </span>
				<p style="margin-bottom:0px; margin-top: 5px; font-weight:normal;" >Links: &nbsp;
				<a href="//{$domain}/w/index.php?title=Special:Log&type=&page={$urlencodedpage}" >Page log</a> &middot; 
				{$linkreasonator}
			</p>
	</div>
	<h3  style="">{#generalstats#} <span class="showhide">[<a href="javascript:switchShow( \'generalstats\' )">show/hide</a>]</span></h3>
	<div id = "generalstats">
		<table><tr>
			<td style="vertical-align:top;" >
			<table>
				<tr><td>ID:</td><td class="tdtop1" ><a href="//{$domain}/w/index.php?title={$urlencodedpage}&action=info" >{$pageid}</a></td></tr>
				<tr><td>Wikidata ID:</td><td class="tdtop1" >{$wikidatalink}</td></tr>
				<tr><td>{#totaledits#}:</td><td class="tdtop1" >{$totaledits}</td></tr>
				<tr><td>{#editorcount#}:</td><td class="tdtop1" >{$editorcount}</td></tr>
				<tr><td>{#pagesize#}:</td><td class="tdtop1" >{$pagesize} Bytes</td></tr>
				<tr><td colspan=20 >&nbsp;</td></tr>
				
				<tr><td>{#minoredits#}:</td><td class="tdtop1" >{$minoredits} &nbsp;&middot;&nbsp;<small>({$minorpct}%)<small></td></tr>
				<tr><td>{#anonedits#}:</td><td class="tdtop1" >{$anonedits} &nbsp;&middot;&nbsp;<small>({$anonpct}%)</small></td></tr>
			<!--	<tr><td>{#autoedits#}:</td><td class="tdtop1" >{$autoedits} &nbsp;<small>({$autoeditspct}%)</small></td> -->
				<tr><td colspan=20 ></td></tr>	
				<tr><td>{#timebwedits#}:</td><td class="tdtop1" >{$timebwedits} {#days#}</td></tr>
				<tr><td>{#editspermonth#}:</td><td class="tdtop1" >{$editspermonth}</td></tr>
				<tr><td>{#editsperyear#}:</td><td class="tdtop1" >{$editsperyear}</td></tr>
				<tr><td colspan=20 ></td></tr>	
				<tr><td>{#lastday#}:</td><td class="tdtop1" >{$lastday}</td></tr>
				<tr><td>{#lastweek#}:</td><td class="tdtop1" >{$lastweek}</td></tr>
				<tr><td>{#lastmonth#}:</td><td class="tdtop1" >{$lastmonth}</td></tr>
				<tr><td>{#lastyear#}:</td><td class="tdtop1" >{$lastyear}</td></tr>
				<tr><td colspan=20 ></td></tr>		
				<tr><td>{#editsperuser#}:</td><td class="tdtop1" >{$editsperuser}</td></tr>
				<tr><td>{#toptencount#}:</td><td class="tdtop1" >{$toptencount} &nbsp;&middot;&nbsp;<small>({$toptenpct}%)</small></td></tr>
			</table>
			</td>
			<td style="vertical-align:top; padding-left:70px;" >
			<table>
				<tr><td colspan=20 >&nbsp;</td></tr>
				<tr><td>{#firstedit#}:</td><td class="tdtop1" ><span style="font-size:97%" >{$firstedit}</span> &nbsp;&bull;&nbsp; <a href="//{$domain}/wiki/User:{$firstuser}" >{$firstuser}</a></td></tr>
				<tr><td>{#latestedit#}:</td><td class="tdtop1" ><span style="font-size:97%" >{$latestedit}</span> &nbsp;&bull;&nbsp; <a href="//{$domain}/wiki/User:{$latestuser}" >{$latestuser}</a></td></tr>
				<tr><td>{#maxadd#}:</td><td class="tdtop1" ><span style="font-size:97%" >{$maxadd}</span> &nbsp;&bull;&nbsp; <a href="//{$domain}/wiki/User:{$maxadduser}" >{$maxadduser}</a> &nbsp;&bull;&nbsp; <a style="color:green" href="//{$lang}.{$wiki}.org/w/index.php?diff=prev&oldid={$maxadddiff} " >+{$maxaddnum}</td></tr>
				<tr><td>{#maxdel#}:</td><td class="tdtop1" ><span style="font-size:97%" >{$maxdel}</span> &nbsp;&bull;&nbsp; <a href="//{$domain}/wiki/User:{$maxdeluser}" >{$maxdeluser}</a> &nbsp;&bull;&nbsp; <a style="color:#cc0000" href="//{$lang}.{$wiki}.org/w/index.php?diff=prev&oldid={$maxdeldiff} " >{$maxdelnum}</a></td></tr>
				<tr><td colspan=20 >&nbsp;</td></tr>	
				<tr><td>{#links_in#}:</td><td class="tdtop1" ><a href="//{$domain}/w/index.php?title=Special:WhatLinksHere/{$urlencodedpage}&hidetrans=1" >{$links_in}</a></td></tr>
				<tr><td>{#redirects#}:</td><td class="tdtop1" ><a href="//{$domain}/w/index.php?title=Special:WhatLinksHere/{$urlencodedpage}&hidetrans=1&hidelinks=1&limit=500" >{$redirects}</td></tr>
				<tr><td>{#links_out#}:</td><td class="tdtop1" >{$links_out}</td></tr>
				<tr><td>{#links_ext#}:</td><td class="tdtop1" >{$links_ext}</td></tr>
				<tr><td>{#pagewatchers#}:</td><td class="tdtop1" >{$pagewatchers}</td></tr>
				<tr><td>{#pageviews#} (60 {#days#}):</td><td class="tdtop1" ><a href="//tools.wmflabs.org/wikiviewstats/?page={$urlencodedpage}&amp;latest=60&amp;lang={$lang}&amp;wiki={$wiki}" >{$pageviews60}</a></td></tr>
			</table>
			</td>
			</tr>
		</table>
		<table>
			<tr>
			<td>{$graphuser}</td>
			<td>{$graphminor}</td>
			<td>{$graphtopten}</td>
			</tr>
		</table>
	</div>

	<!-- yeargraphs -->
	<h3>{#yearcounts#} <span class="showhide" >[<a href="javascript:switchShow( \'yearcounts\' )">show/hide</a>]</span></h3>
	<div id="yearcounts">
		<div style="padding:20px">
			{$chartImgYears}
		</div>
		<table class="leantable2 months sortable" style="margin-left:60px;">
			{$yearcountlist}	
		</table>
		<br />
	</div>
	
	<!-- pageviews -->
	<h3>{#pageviews#} <span class="showhide" >[<a href="javascript:switchShow( \'pageviews\' )">show/hide</a>]</span></h3>
	<div id="pageviews" style="padding:20px" >
		{$chartImgPageviews}
		<br />
	</div>

	<!-- $usertable -->
	<h3>{#usertable#} <span class="showhide" >[<a href="javascript:switchShow( \'topeditors\' )">show/hide</a>]</span></h3>
	<div id="topeditors">
		<table>
			<tr>
			<td>{$chartTopEditorsByCount}</td>
			<td>{$chartTopEditorsByText}</td>
			</tr>
		</table>
		<span><sup>1</sup> {#atbe#}</span>
		<table class="leantable2 months sortable" style="width:95%">
			<tr>
				<th>{#username#}</th>
				<th></th>
				<th>{#count#}</th>
				<th>{#minor#}</th>
				<th>%</th>
				<th>{#firstedit#}</th>
				<th>{#latestedit#}</th>
				<th>atbe <sup>1</sup> ({#days#})</th>
				<th>{#added#} (Bytes)</th>
			</tr>
			{$usertable}
		</table>
	</div>
			
	<!-- monthgraphs -->
	<h3>{#monthcounts#} <span class="showhide" >[<a href="javascript:switchShow( \'monthcounts\' )">show/hide</a>]</span></h3>
	<div id="monthcounts">
		<table class="months leantable2">
			{$monthcountlist}
		</table>
	</div>
	';
	
	if( $type == "form" ) { return $templateForm; }
	if( $type == "result" ) { return $templateResult; }
}




