<?php

//Requires
	require_once( '../WebTool.php' );
	require_once( 'base.php' );
	
//Load WebTool class
	$wt = new WebTool( 'Pages', 'pages', array("smarty", "sitenotice", "replag") );
	$wt->setMemLimit();
	
	$base = new PagesBase();
	$wt->content = $base->tmplPageForm;
	$wt->assign("lang", "en");
	$wt->assign("wiki", "wikipedia");
	
	$user = $wgRequest->getSafeVal('user');
	$user = ( isset($_GET["name"]) ) ? urldecode($_GET["name"]) : urldecode($user);

//Show form if &article parameter is not set (or empty)
	if( $user == "" ) {
		$wt->showPage($wt);
	}

//Get username & userid, quit if not exist
	$userData = $base->getUserData( $dbr, $user );
	if( !$userData ) { 
		$wt->error = $I18N->msg("No such user");
		$wt->showPage($wt);
	}

//Execute main logic	
	$result = $base->getCreatedPages( 
				$dbr, 
				$userData["user_id"], 
				$wgRequest->getSafeVal('lang'), 
				$wgRequest->getSafeVal('wiki'),
				$wgRequest->getSafeVal('namespace'),
				$wgRequest->getSafeVal('redirects')
			 );	

//Construct output
	$filtertextNS = ( $result->filterns == "all" ) ? $I18N->msg('all') : $wgRequest->getSafeVal('namespace');
	$wikibase = $wgRequest->getSafeVal('lang').".".$wgRequest->getSafeVal('wiki') ;
	
	$wt->content = $base->tmplPageResult;
	$wt->assign( "totalcreated", $I18N->msg('user_total_created', array("variables" => array($userData["user_name"], $result->total, $wikibase) ) ) );
	$wt->assign( "redirFilter", $I18N->msg('redirfilter_'.$wgRequest->getSafeVal('redirects') ) );
	$wt->assign( "nsFilter", $filtertextNS );
	$wt->assign( "namespace_overview", $result->listnamespaces );
	$wt->assign( "chartValues", $result->listnum );
	$wt->assign( "chartText", $result->listns );
	$wt->assign( "resultDetails", $result->list );

unset( $base, $userData, $result );
$wt->showPage($wt);

