<?php

//Requires
	require_once( '../WebTool.php' );
	require_once( '../Counter.php' );
	require_once( '../Graph.php' );
	
//Load WebTool class
	$wt = new WebTool( 'Pages', 'pages', array() );
	$wt->setLimits();
	
	$wt->content = getPageTemplate( 'form' );
	$wt->assign("lang", "en");
	$wt->assign("wiki", "wikipedia");
	
	$namespace = $wgRequest->getVal('namespace');
	$redirects = $wgRequest->getVal('redirects'); 
	
	$wi = $wt->getWikiInfo();
		$lang = $wi->lang;
		$wiki = $wi->wiki;
		$domain = $wi->domain;

	$ui = $wt->getUserInfo();
		$user = $ui->user;

//Show form if &article parameter is not set (or empty)
	if( !$user || !$wiki || !$lang ) {
		$wt->showPage();
	}
	
//Get username & userid, quit if not exist
	
	$dbr = $wt->loadDatabase( $lang, $wiki );
	$cnt = new Counter( $dbr, $user, $domain );

//Execute main logic & Construct output
	$items = $cnt->getCreatedPages( $dbr, $ui, $domain, $namespace, $redirects );
	$namespaceList = $cnt->mNamespaces;
	$result = makeList( $items, $namespaceList, xGraph::GetColorList(), $wi, $ui, $namespace, $redirects );
	
//Output stuff
	$filtertextNS = ( $namespace == "all" ) ? $I18N->msg('all') : $namespaceList["names"][ $namespace ]." ($namespace)";
	
	$wt->content = getPageTemplate( 'result' );
		$wt->assign( 'username', $ui->user );
		$wt->assign( 'lang', $lang );
		$wt->assign( 'wiki', $wiki );
		$wt->assign( 'usernameurl', $ui->userUrl );
		$wt->assign( 'xtoolsbase', XTOOLS_BASE_WEB_DIR );
		$wt->assign( 'domain', $wi->domain );
		$wt->assign( 'eclink', $eclink );
		$wt->assign( "redirFilter", $I18N->msg('redirfilter_'.$redirects ) );
		$wt->assign( "nsFilter", $filtertextNS );
		$wt->assign( "namespace_overview", $result->listnamespaces );
		$wt->assign( "nschart", $result->nschart );
		$wt->assign( "resultDetails", $result->list );

unset( $cnt, $items, $result, $namespaceList );
$wt->showPage();


/**************************************** stand alone functions ****************************************
 *
*/
	
function makeList( $items , $nsnames, $nscolors, $wi, $ui, $namespace, $redirects ){
#print_r($nsnames);
	$lang = $wi->lang;
	$wiki = $wi->wiki;
	$domain = $wi->domain;
	
	$rowLimit = ( $namespace == "all" ) ? 100 : 1000;
	
	$result = new stdClass(
			$filter 	 = null,
			$namespaces  = null,
			$list 		 = null
	);

	$currentNamespace = "-1";
	$currentNumber = 0;

	foreach ( $items as $i => $item ){
		$pageurl  = rawurlencode( $item["page_title"] );
		$page 	  = str_replace("_", " ", $item["page_title"]);
		$date 	  = date("Y-m-d", strtotime($item["timestamp"]));
		$ns 	  = $item["namespace"];
		$prefix   = ($ns) ? $nsnames["names"][$ns].":" : "";
		$redirect = ( $item["page_is_redirect"] == 1 ) ? "<small> &middot; (redirect)</small>" : "";
		$deleted  = ( $item["type"] == "arc" ) ? "<small style='color:red' > &middot; ({#deleted#}) </small>" : "";
	
	
		//create a new header if namespace changes
		if( $ns != $currentNamespace){
	
			$result->list .= "<tr ><td colspan=22 ><h3 id=$ns >".$nsnames["names"][$ns]."</h3></td></tr>";
			$result->namespaces[$ns]["name"] = $nsnames["names"][$ns];
	
			$currentNamespace = $ns;
			$currentNumber = 0;
			$currentLimit = false;
		}
	
		$result->namespaces[$ns]["num"]  += 1;
		if ($redirect) { $result->namespaces[$ns]["redir"]  += 1; }
		if ($deleted) { $result->namespaces[$ns]["deleted"]  += 1; }
		
		$currentNumber++;
		
		if ( $currentNumber > $rowLimit ){
			if ( $currentLimit ) { continue; }
				
			$result->list .= '
					<tr><td colspan=22 style="padding-left:50px; ">
					<a href="'.XTOOLS_BASE_WEB_DIR."/pages/?user=$ui->userUrl&lang=$lang&wiki=$wiki&namespace=$ns&redirects=$redirects".'" ><strong>-{#more#}-</strong></a>
					</td></tr>
				';
			$currentLimit = true;
		}
		else{

			$result->list .= "
					<tr>
					<td>$currentNumber.</td>
					<td style='max-width:50%; white-space:wrap; word-wrap:break-word' ><a href=\"//$domain/wiki/$prefix$pageurl?redirect=no\" >$page</a> $redirect $deleted</td>
					<td style='white-space: nowrap; font-size:95%; padding-right:10px;' >$date</td>
					<td style='white-space: nowrap' ><a href=\"//$domain/w/index.php?title=Special:Log&type=&page=$prefix$pageurl\" ><small>log</small></a> &middot; </td>
					<td style='white-space: nowrap' ><a href=\"//".XTOOLS_BASE_WEB_DIR."/articleinfo/?lang=$lang&wiki=$wiki&page=$prefix$pageurl\" ><small>page history</small></a> &middot; </td>
					<td style='white-space: nowrap' ><a href=\"//".XTOOLS_BASE_WEB_DIR."/topedits/?lang=$lang&wiki=$wiki&user=$ui->userUrl&page=$prefix$pageurl\" ><small>topedits</small></a></td>
					
					</tr>
				";
		}
	}

	$result->filterns = $namespace;
	$result->filterredir = $redirects;
	$result->total = count($items);
	unset($items, $nsnames);

	//make serialized lists for graphics & toptable
	$sum["num"] = 0;
	$sum["redir"] = 0;
	$sum["deleted"] = 0;
	
	foreach ( $result->namespaces as $num => $ns ){
			
		$result->listnamespaces .='
			<tr>
			<td style="padding-right:10px">
				<span class=legendicon style="background-color:'.$nscolors[$num].'"> </span>
				<a href="#'.$num.'" >'.$ns["name"].'</a>
			</td>
			<td class=tdnum >'.$ns["num"].'</td>
			<td class=tdnum >'.$ns["redir"].'</td>
			<td class=tdnum >'.$ns["deleted"].'</td>
			</tr>
		';
		$sum["num"] += $ns["num"];
		$sum["redir"] += $ns["redir"];
		$sum["deleted"] += $ns["deleted"];
		
		$chLabels[] = $ns["name"];
		$chValues[] = intval((intval($ns["num"])/intval($result->total))*100);
		$chColors[] = str_replace("#", "", $nscolors[$num] );
	}
	$result->listnamespaces .='
			<tr>
			<td style="border-top:3px double silver;" ></td>
			<td class=tdnum style="border-top:3px double silver" ><strong>'.$sum["num"].'</strong></td>
			<td class=tdnum style="border-top:3px double silver" >'.$sum["redir"].'</td>
			<td class=tdnum style="border-top:3px double silver" >'.$sum["deleted"].'</td>
			</tr>
			';
	
	$chData = array(
			'cht' => 'p',
			'chd' => 't:'.implode(',', $chValues ),
			'chs' => '400x150',
			'chl' => implode('|', $chLabels ),
			'chco' => implode('|', $chColors ),
			'chf' => 'bg,s,00000000',
		);
	
	$result->nschart = '<img src="//chart.googleapis.com/chart?'.http_build_query($chData).'" alt="some graph" />';

	return $result;
}
	
	

/**************************************** templates ****************************************
 *
*/
function getPageTemplate( $type ){

	$templateForm = '
	<br />		
	<form action="?" method="get" accept-charset="utf-8">
	<table>
		<tr><td>{#username#}: </td><td><input type="text" name="user" /></td></tr>
		<tr><td>{#wiki#}: </td><td><input type="text" value="{$lang}" name="lang" size="9" />.<input type="text" value="{$wiki}" size="10" name="wiki" />.org</td></tr>
		<tr><td>{#namespace#}: </td>
			<td>
				<select name="namespace">
					<option value="all">-All-</option>
					<option value="0">Main</option>
					<option value="1">Talk</option>
					<option value="2">User</option>
					<option value="3">User talk</option>
					<option value="4">Wikipedia</option>
					<option value="5">Wikipedia talk</option>
					<option value="6">File</option>
					<option value="7">File talk</option>
					<option value="8">MediaWiki</option>
					<option value="9">MediaWiki talk</option>
					<option value="10">Template</option>
					<option value="11">Template talk</option>
					<option value="12">Help</option>
					<option value="13">Help talk</option>
					<option value="14">Category</option>
					<option value="15">Category talk</option>
					<option value="100">Portal</option>
					<option value="101">Portal talk</option>
					<option value="108">Book</option>
					<option value="109">Book talk</option>
				</select><br />
			</td>
		</tr>
		<tr><td>{#redirects#}: 
			</td><td>
				<select name="redirects">
					<option value="none">{#redirfilter_none#}</option>
					<option value="onlyredirects">{#redirfilter_onlyredirects#}</option>
					<option value="noredirects">{#redirfilter_noredirects#}</option>
				</select><br />
			</td></tr>
		<!--
		<tr><td>{#start#}: </td><td><input type="text" name="begin" /></td></tr>
		<tr><td>{#end#}: </td><td><input type="text" name="end" /></td></tr>
		-->
		<tr><td colspan="2"><input type="submit" value="{#submit#}" /></td></tr>
	</table>
	</form><br />
	';

	
	$templateResult = '
	
	<div class="caption" >
			<a style=" font-size:2em; " href="http://{$domain}/wiki/User:{$usernameurl$}">{$username$}</a>
			<span style="padding-left:10px;" > &bull;&nbsp; {$domain} </span>
			<p>Links: &nbsp;
				<a href="//{$xtoolsbase}/ec/?lang={$lang}&wiki={$wiki}&user={$usernameurl$}" >ec</a>
			</p>
	</div>
	<h3>{#namespacetotals#} <span class="showhide" >[<a href="javascript:switchShow( \'nstotals\' )">show/hide</a>]</span></h3>
	<div id="nstotals">
		<p style="margin-top: 0px;" >{#namespace#}: {$nsFilter} &middot; {#redirects#}: {$redirFilter}</p>
		<table>
			<tr>
			<td>
			<table class="leantable" style="margin-top: 10px; table-layout:fixed" >
				<tr>
				<th>{#namespace#}</th>
				<th>Pages</th>
				<th style="padding_left:5px">&nbsp;&nbsp;{#redirects#}</th>
				<th style="padding_left:5px">&nbsp;&nbsp;{#deleted#}</th>
				</tr>
				{$namespace_overview}
			</table>
			</td>
			<td>
				{$nschart}
			</td>
			</tr>
		</table>
	</div>	
	<table>
		{$resultDetails}
	</table>
	<br />
	';
	
	if( $type == "form" ) { return $templateForm; }
	if( $type == "result" ) { return $templateResult; }

}
