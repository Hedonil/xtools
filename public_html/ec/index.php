<?php

//Requires
	require_once( '../WebTool.php' );
	require_once( 'counter.php' );
#	require_once( '../../Graph.php' );
	require_once( '../../Graph2.php' );
	
	
//Load WebTool class
	$wt = new WebTool( 'Edit counter classic', 'pcount', array("smarty", "sitenotice", "replag") );
	WebTool::setMemLimit();
	
	$wt->content = getPageTemplate( "form" );
	$wt->assign("lang", "en");
	$wt->assign("wiki", "wikipedia");
	
//Show form if &article parameter is not set (or empty)
	if( !$wt->webRequest->getSafeVal( 'getBool', 'user' ) ) {
		$wt->showPage($wt);
	}


$opt_in = array();
$opt_out = array();
$no_opt = array();
$default = 'optin';


$name = str_replace("_", " ", urldecode($wgRequest->getVal('user')));
$wiki = $wgRequest->getVal('wiki');
$lang = $wgRequest->getVal('lang');

$url = $lang.'.'.$wiki.'.org';
$wikibase = $url;
if( $wiki == 'wikidata' ) {
    $lang = 'www';
    $wiki = 'wikidata';
    $wikibase= $url = 'www.wikidata.org';
}

$cnt = new Counter( $name, $wikibase );

#print_r($cnt->getMonthTotals());die;
$wgNamespaces = $cnt->getNamespaces();

// $graphArray = array(
//    'names' => $wgNamespaces['names'],
//    'monthly' => $cnt->getMonthTotals(),
//    'gross' => $cnt->getNamespaceTotals(),
// );



// $gdata = array();
// foreach ( $cnt->getNamespaceTotals() as $ns => $count){
// 	$gdata[ $wgNamespaces["names"][$ns] ] = $count;
// }
// #print_r($gdata);
// $graphNS = xGraph::makePie( $gdata );
// unset($gdata);

$graphNS = xGraph::makePieGoogle( $cnt->getNamespaceTotals() );
$legendNS = xGraph::makeLegendTable(  $cnt->getNamespaceTotals(), $cnt->getNamespaces() );
$graphMonths = xGraph::makeHorizontalBar( "month", $cnt->getMonthTotals(), 800);
$graphYears = xGraph::makeHorizontalBar( "year", $cnt->getMonthTotals(), 800);


if( !$cnt->getExists() ) {
   $wt->error = $I18N->msg('nosuchuser')." ".$cnt->getName();
   $wt->showPage($wt);
}


// Get TopEdited Pages
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



$wt->content = getPageTemplate( "result" );

$wt->assign( "username", $cnt->getName() ); 
$wt->assign( "usernameurl", rawurlencode($cnt->getName()) );
$wt->assign( "url", $url );
$wt->assign( "loadwiki", "&wiki=$wiki&lang=$lang" );
$wt->assign( "groups", implode( ', ', $cnt->getGroupList() ) );

if( $cnt->getLive() > 0) {}
	
$wt->assign( "firstedit", $wt->dateFmt( $cnt->getFirstEdit() ) );
$wt->assign( "unique", 	  $wt->numFmt( count($uniqueEdits['total']) ) );
$wt->assign( "average",   $cnt->getAveragePageEdits() );
$wt->assign( "live", 	  $wt->numFmt( intval( $cnt->getLive() ) ) );
$wt->assign( "deleted",   $wt->numFmt( intval( $cnt->getDeleted() ) ) );
$wt->assign( "reverted",  $wt->numFmt( intval( $cnt->getReverted() ) ) );
$wt->assign( "total", 	  $wt->numFmt( intval( $cnt->getTotal() ) ) );

$wt->assign( "namespace_legend", $legendNS );
#$wt->assign( "graph", $graph->pie( $I18N->msg('namespacetotals') ) );
$wt->assign( "namespace_graph", '<img src="'.$graphNS.'"  />' );

if( $cnt->isOptedIn( $cnt->getName() ) ) {
	$wt->assign( "yearcounts", $graphYears );
	$wt->assign( "monthcounts", $graphMonths );
	$wt->assign( "topedited", $out );
}
else {
	$wt->assign( "monthcounts", $I18N->msg( "nograph", array( "variables"=> array( $cnt->getName(), $url) )) );
	$wt->assign( "topedited", $I18N->msg( "nograph", array( "variables"=> array( $cnt->getName(), $url) )) );
}


// $wt->moreheader =
//    '<link rel="stylesheet" href="//tools.wmflabs.org/xtools/counter_commons/NavFrame.css" type="text/css" />' . "\n\t" .
//    '<script src="//bits.wikimedia.org/skins-1.5/common/wikibits.js?urid=257z32_1264870003" type="text/javascript"></script>' . "\n\t" .
//    '<script src="//tools.wmflabs.org/xtools/counter_commons/NavFrame.js" type="text/javascript"></script>'
// ;
$wt->assign( "popup", true );
unset( $out, $graph, $cnt );
$wt->showPage($wt);


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
	var collapseCaption = "{#hide#}";
	var expandCaption = "{#show#}";
	</script>

	<table>
		<tr>
			<td>{#user#}:</td><td style="padding-left:10px;" ><a href="http://{$url$}/wiki/User:{$usernameurl$}">{$username$}</a></td>
		</tr>
		<tr>
			<td>{#groups#}:</td><td style="padding-left:10px;" >{$groups$}</td>
		</tr>
		<tr>
			<td>{#firstedit#}:</td><td style="padding-left:10px;" >{$firstedit$}</td>
		</tr>
	</table>
	<table>
		<tr>
			<td  style="padding-top:10px;">{#unique#}:</td><td><span style="display:inline-block; width:50px;text-align:right;" >{$unique$}</span></td>
		</tr>
		<tr>
			<td>{#average#}:</td><td><span style="display:inline-block; width:50px;text-align:right;" >{$average$}</span></td>
		</tr>
		<tr>
			<td>{#reverted#}:</td><td><span style="display:inline-block; width:50px;text-align:right;" >{$reverted$}</span></td>
		</tr>
		<tr>
			<td>{#live#}:</td><td><span style="display:inline-block; width:50px;text-align:right;" >{$live$}</span></td>
		</tr>
		<tr>
			<td>{#deleted#}:</td><td><span style="display:inline-block; width:50px;text-align:right;" >{$deleted$}</span></td>
		</tr>
		<tr>
			<td><b>{#total#}:</b>&nbsp;&nbsp;</td><td><span style="display:inline-block; width:50px;text-align:right;" ><b>{$total$}</b></span></td>
		</tr>
	</table>
	<br />
	<h3>{#namespacetotals#}</h3>
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