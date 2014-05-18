<?php

//Requires
	require_once( '../WebTool.php' );
	require_once( '../Graph.php' );
	require_once( '../Counter.php' );

	
	
//Load WebTool class
	$wt = new WebTool( 'Edit counter classic', 'pcount', array("database") );
	$wt->setMemLimit();
	set_time_limit ( 60 );
	
	$wt->content = getPageTemplate( "form" );
	$wt->assign("lang", "en");
	$wt->assign("wiki", "wikipedia");
	
	
	$user = $wgRequest->getVal('user');
	$user = $wgRequest->getVal('name', $user );
	
	$user = str_replace("_", " ", $user);
	
//Show form if &article parameter is not set (or empty)
	if( !$user ) {
		$wt->showPage($wt);
	}


	$opt_in = array();
	$opt_out = array();
	$no_opt = array();
	$default = 'optin';
	
	$wiki = $wgRequest->getVal('wiki');
	$lang = $wgRequest->getVal('lang');
	
	$url = $lang.'.'.$wiki.'.org';
	$wikibase = $url;
	if( $wiki == 'wikidata' ) {
	    $lang = 'www';
	    $wiki = 'wikidata';
	    $wikibase= $url = 'www.wikidata.org';
	}

//Create new Counter object
	$cnt = new Counter( $dbr, $user, $wikibase );

	if( !$cnt->getExists() ) {
	   $wt->toDie( 'nosuchuser', $cnt->getName() );
	}

	$graphNS = xGraph::makePieGoogle( $cnt->getNamespaceTotals() );
	#$graphNS = "../tmp/".xGraph::makePie( $cnt->getNamespaceTotals() );
	$legendNS = xGraph::makeLegendTable(  $cnt->getNamespaceTotals(), $cnt->getNamespaces() );
	
	$graphMonths = xGraph::makeHorizontalBar( "month", $cnt->getMonthTotals(), 800);
	$graphYears = xGraph::makeHorizontalBar( "year", $cnt->getMonthTotals(), 800);


// Get TopEdited Pages
	$wgNamespaces = $cnt->getNamespaces();
	
	$uniqueEdits = $cnt->getUniqueArticles();
	ksort($uniqueEdits['namespace_specific']);
	
	$num_to_show = 10;
	$out = null;
	
	foreach( $uniqueEdits['namespace_specific'] as $namespace_id => $articles ) {
		//$out .= "<h4>" . $wgNamespaces['names'][$namespace_id] . "</h4>\n";
		$out .= '<table class="collapsible collapsed"><tr><th>' . $wgNamespaces['names'][$namespace_id] . '</th></tr><tr><td>';
		$out .= "<ul>\n";
	
		asort( $articles );
		$articles = array_reverse( $articles );
	
		$i = 0;
		foreach ( $articles as $article => $count ) {
			if( $i == $num_to_show ) break;
			if( $namespace_id == 0 ) {
				$nscolon = '';
			}
			else {
				$nscolon = $wgNamespaces['names'][$namespace_id].":";
			}
			$articleencoded = urlencode( $article );
			$articleencoded = str_replace( '%2F', '/', $articleencoded );
			$trimmed = substr($article, 0, 50).'...';
			$out .= '<li>'.$count." - <a href='//$lang.$wiki.org/wiki/".$nscolon.$articleencoded.'\'>';
			if(strlen(substr($article, 0, 50))<strlen($article)) {
				$out .= $trimmed;
			}
			else {
				$out .= $article;
			}
			$out .= "</a></li>\n";
			$i++;
		}
		$out .= "</ul></td></tr></table><br />";
	}


//Output stuff
	$wt->content = getPageTemplate( "result" );
	
	$wt->assign( "username", $cnt->getName() ); 
	$wt->assign( "usernameurl", rawurlencode($cnt->getName()) );
	$wt->assign( "url", $url );
	$wt->assign( "loadwiki", "&wiki=$wiki&lang=$lang" );
	$wt->assign( "groups", implode( ', ', $cnt->getGroupList() ) );
	
	if( $cnt->getLive() > 0) {}
		
	$wt->assign( "firstedit", 		$wt->dateFmt( $cnt->getFirstEdit() ) );
	$wt->assign( "unique", 	  		$wt->numFmt( $cnt->getUnique() ) );
	$wt->assign( "average",   		$wt->numFmt( $cnt->getAveragePageEdits(),2 ) );
	$wt->assign( "pages_created",   $wt->numFmt( $cnt->getCreated() ) );
	$wt->assign( "pages_moved",   	$wt->numFmt( $cnt->getMoved() ) );
	$wt->assign( "reverted",  		$wt->numFmt( $cnt->getReverted() ) );
	$wt->assign( "live", 	  		$wt->numFmt( $cnt->getLive() ) );
	$wt->assign( "deleted",   		$wt->numFmt( $cnt->getDeleted() ) );
	$wt->assign( "total", 	  		$wt->numFmt( $cnt->getTotal() ) );
	
	$wt->assign( "namespace_legend", $legendNS );
	#$wt->assign( "graph", $graph->pie( $I18N->msg('namespacetotals') ) );
	$wt->assign( "namespace_graph", '<img src="'.$graphNS.'"  />' );
	
	$wt->assign( "yearcounts", $graphYears );
	
	if( $cnt->isOptedIn( $cnt->getName() ) ) {
		$wt->assign( "monthcounts", $graphMonths );
		$wt->assign( "topedited", $out );
	}
	else {
		$wt->assign( "monthcounts", $I18N->msg( "nograph", array( "variables"=> array( $cnt->getName(), $url) )));
		$wt->assign( "topedited", '');
	}

	$wt->assign( 'exp_color_table', ''); //xGraph::makeColorTable());

// $wt->moreheader =
//    '<link rel="stylesheet" href="//tools.wmflabs.org/xtools/counter_commons/NavFrame.css" type="text/css" />' . "\n\t" .
//    '<script src="//bits.wikimedia.org/skins-1.5/common/wikibits.js?urid=257z32_1264870003" type="text/javascript"></script>' . "\n\t" .
//    '<script src="//tools.wmflabs.org/xtools/counter_commons/NavFrame.js" type="text/javascript"></script>'
// ;

unset( $out, $graph, $cnt );
$wt->showPage();


/**************************************** templates ****************************************
 *
*/
function getPageTemplate( $type ){

	$templateForm = '
			
	<script type="text/javascript">
		var collapseCaption = "{#hide#}";
		var expandCaption = "{#show#}";
	</script>
	<br />
	{#welcome#}
	<br /><br />
	<form action="?" method="get">
		<table>
		<tr><td>{#user#}: </td><td><input type="text" name="user" /></td></tr>
		<tr><td>{#wiki#}: </td><td><input type="text" value="{$lang}" name="lang" size="9" />.<input type="text" value="{$wiki}" size="10" name="wiki" />.org</td></tr>
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
			<span style="padding-right:10px;" >{#user#} &nbsp;&bull; </span>
			<a style=" font-size:2em; " href="http://{$url$}/wiki/User:{$usernameurl$}">{$username$}</a>
			<span style="padding-left:10px;" > &bull;&nbsp; {$url} </span>
	</div>
	<h3  style="margin-top:-0.8em;">{#generalstats#} &nbsp;&nbsp;<span style="font-size:75%;">[<a href="javascript:switchShow( \'generalstats\' )">show/hide</a>]</span></h3>
	<div id = "generalstats">
		<table>
			<tr>
				<td>{#groups#}:</td><td style="padding-left:10px;" >{$groups$}</td>
			</tr>
			<tr>
				<td>{#firstedit#}:</td><td style="padding-left:10px;" >{$firstedit$}</td>
			</tr>
			<tr><td colspan=20 ></td></tr>
			<tr>
				<td>{#unique#}:</td><td><span style="display:inline-block; width:50px;text-align:right;" >{$unique$}</span></td>
			</tr>
			<tr>
				<td>{#average#}:</td><td><span style="display:inline-block; width:50px;text-align:right;" >{$average$}</span></td>
			</tr>
			<tr><td colspan=20 ></td></tr>
			<tr>
				<td>{#pages_created#}:</td><td><span style="display:inline-block; width:50px;text-align:right;" >{$pages_created}</span></td>
			</tr>
			<tr>
				<td>{#pages_moved#}:</td><td><span style="display:inline-block; width:50px;text-align:right;" >{$pages_moved}</span></td>
			</tr>
			<tr>
				<td>{#reverted#}:</td><td><span style="display:inline-block; width:50px;text-align:right;" >{$reverted$}</span></td>
			</tr>
			<tr><td colspan=20 ></td></tr>
			<tr>
				<td>{#live#}:</td><td><span style="display:inline-block; width:50px;text-align:right;" >{$live$}</span></td>
			</tr>
			<tr>
				<td>{#deleted_edits#}:</td><td><span style="display:inline-block; width:50px;text-align:right;" >{$deleted$}</span></td>
			</tr>
			<tr>
				<td><b>{#total#}:</b>&nbsp;&nbsp;</td><td><span style="display:inline-block; width:50px;text-align:right;" ><b>{$total$}</b></span></td>
			</tr>
		</table>
	</div>
	<br />
	<h3>{#namespacetotals#}</h3>
	{$exp_color_table}
	<table>
		<tr>
		<td>{$namespace_legend}</td>
		<td style="text-align:center;"><div class="center" style="padding-left:80px;">{$namespace_graph}</div></td>
		</tr>
	</table>
	<h3>{#yearcounts#}</h3>
		{$yearcounts$}
	<br />
	<h3>{#monthcounts#}</h3>
		{$monthcounts$}
	<br />

	<h3>{#topedited#}</h3>
		{$topedited$}
	<br />
	
	';
	
	if( $type == "form" ) { return $templateForm; }
	if( $type == "result" ) { return $templateResult; }
	
	}