<?php

//Requires
	require_once( '../WebTool.php' );
	require_once( '../Graph.php' );
	require_once( '../Counter.php' );

	
//Load WebTool class
	$wt = new WebTool( 'Edit counter classic', 'ec', array() );
	$wt->setLimits( 650, 60 );
	
	$wt->content = getPageTemplate( "form" );
	$wt->assign("lang", "en");
	$wt->assign("wiki", "wikipedia");
	
	$wi = $wt->getWikiInfo();
		$lang = $wi->lang;
		$wiki = $wi->wiki;
		$domain = $wi->domain;
	
	$ui = $wt->getUserInfo();
		$user = $ui->user;
	
//Show form if user is not set (or empty)
	if( !$user || !$lang || !$wiki ) {
		$wt->showPage();
	}

		
//Create new Counter object
	$dbr = $wt->loadDatabase( $lang, $wiki);
	
	$cnt = new Counter( $dbr, $user, $domain );

	$graphNS = xGraph::makePieGoogle( $cnt->getNamespaceTotals() );
	$legendNS = xGraph::makeLegendTable(  $cnt->getNamespaceTotals(), $cnt->getNamespaces() );
	
	$graphMonths = xGraph::makeHorizontalBar( "month", $cnt->getMonthTotals(), 800);
	$graphYears = xGraph::makeHorizontalBar( "year", $cnt->getMonthTotals(), 800);


//Output stuff
	$wt->content = getPageTemplate( "result" );

//Make list of TopEdited Pages
	$wgNamespaces = $cnt->getNamespaces();
	
	$uniqueEdits = $cnt->getUniqueArticles();
	ksort($uniqueEdits['namespace_specific']);
	
	$num_to_show = 15;

	$out = "<table>";
	foreach( $uniqueEdits['namespace_specific'] as $namespace_id => $articles ) {

		$out .= '<tr><td colspan=22 ><h3>' . $wgNamespaces['names'][$namespace_id] . '</h3></td></tr>';
	
		asort( $articles );
		$articles = array_reverse( $articles );
	
		$i = 0;
		foreach ( $articles as $article => $count ) {
			if( $i == $num_to_show ) {
				$out .= "<tr><td colspan=22 style='padding-left:50px; padding-top:10px;'><a href=\"//".XTOOLS_BASE_WEB_DIR."/topedits/?lang=$lang&wiki=$wiki&user=$user&namespace=${namespace_id}\" >-".$I18N->msg('more')."-</a></td></tr>";
				break;
			}
			
			$nscolon = '';
			if( $namespace_id != 0 ) {
				$nscolon = $wgNamespaces['names'][$namespace_id].":";
			}

			$articleencoded = rawurlencode( str_replace(" ", "_", $nscolon.$article ) );
			$articleencoded = str_replace( array('%2F', '%3A'), array('/', ':'), $articleencoded );
			$article = str_replace("_", " ", $nscolon.$article);
			
			$out .= "
				<tr>
				<td class=tdnum >$count</td>
				<td><a href=\"//$domain/wiki/$articleencoded\" >$article</a></td>
				<td><a href=\"//$domain/w/index.php?title=Special:Log&type=&page=$articleencoded\" ><small>log</small></a> &middot; </td>
				<td><a href=\"//".XTOOLS_BASE_WEB_DIR."/articleinfo/?lang=$lang&wiki=$wiki&page=$articleencoded\" ><small>page history</small></a> &middot; </td>
				<td><a href=\"//".XTOOLS_BASE_WEB_DIR."/topedits/?lang=$lang&wiki=$wiki&user=${user}&page=$articleencoded\" ><small>topedits</small></a></td>
			 ";
							
			$i++;
		}
		
	} 
	$out .= "</table><br />";
	
//Make list of automated edits tools
	$list = '<table>';
	foreach ( $cnt->getAEBTypes() as $tool => $sth){
		$num = ( isset($cnt->mAutoEditTools[$tool]) ) ? $wt->numFmt( $cnt->mAutoEditTools[$tool] ) : 0;
		$list .= '
				<tr>
				</td><td class="tdnum" style="min-width: 50px; ">'.$num.'</td>
				<td style="padding-left:10px;"><a href="//en.wikipedia.org/wiki/'.$sth["shortcut"].'" >'.$tool.'</a>
				</tr>';
	}
	$list .= '</table>';
	
	$wt->assign( 'autoeditslist', $list);
	unset( $list );
	

//Make topten sulinfo table
	$list = '<table><tr><td colspan=2 style="color:gray;" title="SUL = Single User Login / Unififed lgoin" >SUL editcounts ({#approximate#}):</td></tr>';
	$i = 0;
	foreach ( $cnt->wikis as $sulwiki => $editcount ){
		$suldomain = preg_replace('/^.*\/\/(.*)\.org/', '\1', $sulwiki);
		$sullang = preg_replace('/^.*\/\/(.*)\..*\.org/', '\1', $sulwiki);
		$sulwiki = preg_replace('/^.*\/\/.*\.(.*)\.org/', '\1', $sulwiki);
		$list .= '
			<tr>
			<td>'.$suldomain.'</td>
			<td class="tdgeneral" ><a href="//'.XTOOLS_BASE_WEB_DIR."/ec/?user=$ui->userUrl&lang=$sullang&wiki=$sulwiki".'" >'.$wt->numFmt($editcount).'</a></td>
			</tr>
		';
		
		$i++;
		if ($i > 10 )break;
	}
	$list .= '</table>';
	
	$wt->assign( 'sulinfotop', $list);
	unset( $list );

	
//Output stuff
	$groupsGlobal = ($cnt->mGroupsGlobal) ? " &bull; global: ".implode(", ", $cnt->mGroupsGlobal) : ""; 
	
	$wt->assign( 'xtoolsbase', XTOOLS_BASE_WEB_DIR );
	$wt->assign( "lang", $lang );
	$wt->assign( "wiki", $wiki );
	$wt->assign( "userid", $cnt->mUID );
	$wt->assign( "username", $cnt->getName() ); 
	$wt->assign( "usernameurl", rawurlencode($cnt->getName()) );
	$wt->assign( "userprefix", rawurlencode($cnt->mNamespaces["names"][2] ) );
	$wt->assign( "domain", $domain );
	$wt->assign( "loadwiki", "&wiki=$wiki&lang=$lang" );
	$wt->assign( "groups", implode( ', ', $cnt->mGroups) . $groupsGlobal );
	
	$wt->assign( "firstedit", 		$wt->dateFmt( $cnt->mFirstEdit) );
	$wt->assign( "latestedit", 		$wt->dateFmt( $cnt->mLatestEdit) );
	$wt->assign( "unique", 	  		$wt->numFmt( $cnt->getUnique() ) );
	$wt->assign( "average",   		$wt->numFmt( $cnt->getAveragePageEdits(),2 ) );
	$wt->assign( "pages_created",   $wt->numFmt( $cnt->mCreated + $cnt->mDeletedCreated ) );
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
	$wt->assign( "namespace_graph", '<img src="'.$graphNS.'"  />' );
	
	$wt->assign( "yearcounts", $graphYears );
	
	
	if( $cnt->optin ) {
		$wt->assign( "monthcounts", $graphMonths );
		$wt->assign( "topedited", $out );
	}
	else {
		$wt->assign( "monthcounts", $I18N->msg( "nograph", array( "variables"=> array( $cnt->getOptinLinkLocal(), $cnt->getOptinLinkGlobal() ) )));
		$wt->assign( "topedited", '');
	}



unset( $out, $graph, $cnt );
$wt->showPage();


/**************************************** templates ****************************************
 *
*/
function getPageTemplate( $type ){

	$templateForm = '
			
	<br />
	<form action="?" method="get">
		<table>
		<tr><td>{#username#}: </td><td><input type="text" name="user" /></td></tr>
		<tr><td>{#wiki#}: </td><td><input type="text" value="{$lang}" name="lang" size="9" />.<input type="text" value="{$wiki}" size="10" name="wiki" />.org</td></tr>
		<tr><td colspan="2"><input type="submit" value="{#submit#}" /></td></tr>
		</table>
	</form><br />
	';
	
	
	$templateResult = '
		
	<div class="caption" >
			<a style=" font-size:2em; " href="http://{$domain}/wiki/User:{$usernameurl$}">{$username$}</a>
			<span style="padding-left:10px;" > &bull;&nbsp; {$domain} </span>
			<p>Links: &nbsp;
				<a href="//{$domain}/w/index.php?title=Special%3ALog&type=block&user=&page=User%3A{$usernameurl}&year=&month=-1&tagfilter=" >block log</a> &middot; 
				<a href="//tools.wmflabs.org/supercount/?user={$usernameurl}&project={$lang}.{$wiki}" >User Analysis Tool</a> &middot;
				<a href="//tools.wmflabs.org/guc/?user={$usernameurl}" >Global user contributions</a> &middot; 
				<a href="//tools.wmflabs.org/wikiviewstats/?lang={$lang}&wiki={$wiki}&page={$userprefix}:{$usernameurl}*" >Pageviews in userspace</a> &middot; 
			</p>
	</div>
	<h3  style="">{#generalstats#} <span class="showhide">[<a href="javascript:switchShow( \'generalstats\' )">show/hide</a>]</span></h3>
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
				<tr><td>{#pages_created#}:</td><td><span class="tdgeneral" ><a href="//{$xtoolsbase}/pages/?user={$usernameurl}&lang={$lang}&wiki={$wiki}&namespace=all&redirects=none" >{$pages_created}</a></span></td></tr>
				<tr><td>{#pages_moved#}:</td><td><span class="tdgeneral" >{$pages_moved}</span></td></tr>
				<tr><td>{#files_uploaded#}:</td><td><span class="tdgeneral" >{$uploaded}</span></td></tr>
				<tr><td>{#files_uploaded#} (Commons):</td><td><span class="tdgeneral" >{$uploaded_commons}</span></td></tr>
				<tr><td colspan=20 ></td></tr>
				<tr><td>{#autoedits#}:</td><td><span class="tdgeneral" ><a href="#autoeditslist">{$autoedits}</a></span></td></tr>
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
				<tr><td>{#thank#}:</td><td class="tdgeneral"><a href="//{$domain}/w/index.php?title=Special%3ALog&type=thanks&user={$usernameurl}&page=&year=&month=-1&tagfilter=" >{$thanked} <small>x</small></a></td></tr>
				<tr><td>{#approve#}:</td><td class="tdgeneral"><a href="//{$domain}/w/index.php?title=Special%3ALog&type=review&user={$usernameurl}&page=&year=&month=-1&tagfilter=&hide_patrol_log=1&hide_review_log=1&hide_thanks_log=1" >{$approve} <small>x</small></a></td></tr>
				<tr><td>{#unapprove#}:</td><td class="tdgeneral">{$unapprove} <small>x</small></td></tr>
				<tr><td>{#patrol#}:</td><td class="tdgeneral"><a href="//{$domain}/w/index.php?title=Special%3ALog&type=patrol&user={$usernameurl}&page=&year=&month=-1&tagfilter=" >{$patrol} <small>x</small></a></td></tr>
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
	<h3>{#autoedits#} <span class="showhide" >[<a href="javascript:switchShow( \'autoeditslist\' )">show/hide</a>]</span></h3>
		<div id="autoeditslist" >
		{$autoeditslist}
		<br />
		</div>
	';
	
	if( $type == "form" ) { return $templateForm; }
	if( $type == "result" ) { return $templateResult; }
	
	}