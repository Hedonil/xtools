<?php

//Requires
	require_once( '../WebTool.php' );
	require_once( '../Graph.php' );
	require_once( '../Counter.php' );

	
	
//Load WebTool class
	$wt = new WebTool( 'Edit counter classic', 'pcount', array("database") );
	$wt->setLimits( 650, 60 );
	
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


//Output stuff
	$wt->content = getPageTemplate( "result" );

//Make list of TopEdited Pages
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
	
//Make list of automated edits tools
	$list = '<div>{#autoedits_approx#}</div><br /><table>';
	foreach ( $cnt->AEBTypes as $tool => $sth){
		$list .= '
				<tr>
				</td><td class="tdnum" style="min-width: 50px; ">'.$wt->numFmt($cnt->mAutoEditTools[$tool]).'</td>
				<td style="padding-left:10px;"><a href="//en.wikipedia.org/wiki/'.$sth["shortcut"].'" >'.$tool.'</a>
				</tr>';
	}
	$list .= '</table>';
	
	$wt->assign( 'autoeditslist', $list);
	unset( $list );

//Make topten sulinfo table
	$list = '<table><tr><td colspan=2 style="color:gray;" ><a href="//en.wikipedia.org/wiki/Wikipedia:Unified_login" >SUL</a> editcounts (approx.):</td></tr>';
	$i = 0;
	foreach ( $cnt->wikis as $wiki => $editcount ){
		$list .= '<tr><td>'.preg_replace('/^.*\/\/(.*)\.org/', '\1', $wiki).'</td><td class="tdgeneral" >'.$wt->numFmt($editcount).'</td></tr>';
		$i++;
		if ($i > 10 )break;
	}
	$list .= '</table>';
	
	$wt->assign( 'sulinfotop', $list);
	unset( $list );

	$groupsGlobal = ($cnt->mGroupsGlobal) ? " &bull; global: ".implode(", ", $cnt->mGroupsGlobal) : ""; 
	
	
	$wt->assign( "lang", $lang);
	$wt->assign( "wiki", $wiki);
	$wt->assign( "userid", $cnt->mUID );
	$wt->assign( "username", $cnt->getName() ); 
	$wt->assign( "usernameurl", rawurlencode($cnt->getName()) );
	$wt->assign( "url", $url );
	$wt->assign( "loadwiki", "&wiki=$wiki&lang=$lang" );
	$wt->assign( "groups", implode( ', ', $cnt->mGroups) . $groupsGlobal );
	
	if( $cnt->getLive() > 0) {}
		
	$wt->assign( "firstedit", 		$wt->dateFmt( $cnt->mFirstEdit) );
	$wt->assign( "latestedit", 		$wt->dateFmt( $cnt->mLatestEdit) );
	$wt->assign( "unique", 	  		$wt->numFmt( $cnt->getUnique() ) );
	$wt->assign( "average",   		$wt->numFmt( $cnt->getAveragePageEdits(),2 ) );
	$wt->assign( "pages_created",   $wt->numFmt( $cnt->mCreated ) );
	$wt->assign( "pages_moved",   	$wt->numFmt( $cnt->mMoved ) );
	$wt->assign( "uploaded",   		$wt->numFmt( $cnt->mUploaded ) );
	$wt->assign( "uploaded_commons",$wt->numFmt( $cnt->mUploadedCommons ) );
	$wt->assign( "autoedits",  		$wt->numFmt( $cnt->mAutoEdits ) );
	$wt->assign( "reverted",  		$wt->numFmt( $cnt->mReverted ) );
	$wt->assign( "live", 	  		$wt->numFmt( $cnt->mLive ) );
	$wt->assign( "deleted",   		$wt->numFmt( $cnt->mDeleted ) );
	$wt->assign( "total", 	  		$wt->numFmt( $cnt->mTotal ) );
	
	$wt->assign( "approve", 	  	$wt->numFmt( $cnt->mApprove ) );
	$wt->assign( "unapprove", 	  	$wt->numFmt( $cnt->mUnapprove ) );
	$wt->assign( "patrol",	 	  	$wt->numFmt( $cnt->mPatrol ) );
	$wt->assign( "thanked", 	  	$wt->numFmt( $cnt->mThanked ) );
	
	$wt->assign( "block",	 	  	$wt->numFmt( $cnt->mBlock ) );
	$wt->assign( "unblock", 	  	$wt->numFmt( $cnt->mUnblock ) );
	$wt->assign( "protect", 	  	$wt->numFmt( $cnt->mProtect ) );
	$wt->assign( "unprotect", 	  	$wt->numFmt( $cnt->mUnprotect ) );
	$wt->assign( "delete_page",   	$wt->numFmt( $cnt->mDeletePage ) );
	$wt->assign( "delete_rev", 	  	$wt->numFmt( $cnt->mDeleteRev ) );
	
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
	<h3  style="margin-top:-0.8em;">{#generalstats#} <span class="showhide">[<a href="javascript:switchShow( \'generalstats\' )">show/hide</a>]</span></h3>
	<div id = "generalstats">
		<table>
			<tr><td>{#userid#}:</td><td style="padding-left:10px;" >{$userid}</td></tr>
			<tr><td>{#groups#}:</td><td style="padding-left:10px;" >{$groups$}</td></tr>
			<tr><td>{#firstedit#}:</td><td style="padding-left:10px;" >{$firstedit$}</td></tr>
			<tr><td>{#latestedit#}:</td><td style="padding-left:10px;" >{$latestedit$}</td></tr>
			<tr><td colspan=20 ></td></tr>
		</table>
		<table><tr>
			<td style="vertical-align:top;">
			<table>
				<tr><td style="color:gray">Local Metrics:</td></td></tr>
				<tr><td>{#unique#}:</td><td><span class="tdgeneral" >{$unique$}</span></td>	</tr>
				<tr><td>{#average#}:</td><td><span class="tdgeneral" >{$average$}</span></td></tr>
				<tr><td colspan=20 ></td></tr>
				<tr><td>{#pages_created#}:</td><td><span class="tdgeneral" >{$pages_created}</span></td></tr>
				<tr><td>{#pages_moved#}:</td><td><span class="tdgeneral" >{$pages_moved}</span></td></tr>
				<tr><td>{#files_uploaded#}:</td><td><span class="tdgeneral" >{$uploaded}</span></td></tr>
				<tr><td>{#files_uploaded#} (Commons):</td><td><span class="tdgeneral" >{$uploaded_commons}</span></td></tr>
				<tr><td colspan=20 ></td></tr>
				<tr><td>{#autoedits_num#}:</td><td><span class="tdgeneral" ><a href="#autoeditslist">{$autoedits}</a></span></td></tr>
				<tr><td>{#reverted#}:</td><td><span class="tdgeneral" >{$reverted$}</span></td></tr>
				<tr><td colspan=20 ></td></tr>
				<tr><td>{#live#}:</td><td><span class="tdgeneral" >{$live$}</span></td></tr>
				<tr><td>{#deleted_edits#}:</td><td><span class="tdgeneral" >{$deleted$}</span></td></tr>
				<tr><td><b>{#total#}:</b>&nbsp;&nbsp;</td><td><span class="tdgeneral" ><b>{$total$}</b></span></td></tr>
			</table>
			</td>
			<td style="vertical-align:top; padding-left:70px;" >
			<table >
				<tr><td style="color:gray">Actions:</td></td></tr>
				<tr><td>{#thank#}:</td><td class="tdgeneral">{$thanked} <small>x</small></td></tr>
				<tr><td>{#approve#}:</td><td class="tdgeneral">{$approve} <small>x</small></td></tr>
				<tr><td>{#unapprove#}:</td><td class="tdgeneral">{$unapprove} <small>x</small></td></tr>
				<tr><td>{#patrol#}:</td><td class="tdgeneral">{$patrol} <small>x</small></td></tr>
				<tr><td colspan=2></td></tr>
				<tr><td style="color:gray">{#admin_actions#}</td></td></tr>
				<tr><td>{#block#}:</td><td class="tdgeneral">{$block} <small>x</small></td></tr>
				<tr><td>{#unblock#}:</td><td class="tdgeneral">{$unblock} <small>x</small></td></tr>
				<tr><td>{#protect#}:</td><td class="tdgeneral">{$protect} <small>x</small></td></tr>
				<tr><td>{#unprotect#}:</td><td class="tdgeneral">{$unprotect} <small>x</small></td></tr>
				<tr><td>{#delete#}:</td><td class="tdgeneral">{$delete_page} <small>x</small></td></tr>
				<tr><td>{#delete#} (revision):</td><td class="tdgeneral">{$delete_rev} <small>x</small></td></tr>
			</table>
			</td>
			<td style="vertical-align:top; padding-left:70px;" >
				{$sulinfotop}
			</td></tr>
		</table>
	</div>
	<br />
	<a name="nstotals"></a>		
	<h3>{#namespacetotals#} <span class="showhide" >[<a href="javascript:switchShow( \'nstotals\' )">show/hide</a>]</span></h3>
		<div id="nstotals">
		<table>
			<tr>
			<td>{$namespace_legend}</td>
			<td style="text-align:center;"><div class="center" style="padding-left:80px;">{$namespace_graph}</div></td>
			</tr>
		</table>
		</div>
	<a name="yearcounts"></a>
	<h3>{#yearcounts#} <span class="showhide">[<a href="javascript:switchShow( \'yearcounts\' )">show/hide</a>]</span></h3>
		<div id="yearcounts" >
		{$yearcounts$}
		<br />
		</div>
	<a name="monthcounts"></a>
	<h3>{#monthcounts#} <span class="showhide">[<a href="javascript:switchShow( \'monthcounts\' )">show/hide</a>]</span></h3>
		<div id="monthcounts" >
		{$monthcounts$}
		<br />
		</div>
	<a name="topedited"></a>
	<h3>{#topedited#} <span class="showhide" >[<a href="javascript:switchShow( \'topedited\' )">show/hide</a>]</span></h3>
		<div id="topedited">
		{$topedited$}
		<br />
		</div>
	<a name="autoeditslist"></a>
	<h3>{#autoedits_title#} <span class="showhide" >[<a href="javascript:switchShow( \'autoeditslist\' )">show/hide</a>]</span></h3>
		<div id="autoeditslist" >
		{$autoeditslist$}
		<br />
		</div>
	';
	
	if( $type == "form" ) { return $templateForm; }
	if( $type == "result" ) { return $templateResult; }
	
	}