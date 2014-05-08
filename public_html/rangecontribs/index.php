<?php

//Requires
	require_once( '../WebTool.php' );
	require_once( 'base.php' );

//Load WebTool class
	$wt = new WebTool( 'Range contributions', 'rangecontribs', array("smarty", "sitenotice", "replag") );
	$wt->setMemLimit();
	$base = new RangecontribsBase();
	$wt->content = $base->tmplPageForm;
	$wt->assign("lang", "en");
	$wt->assign("wiki", "wikipedia");
	
//Get query string params & make some checks
	$cidr  = $wt->webRequest->getSafeVal( 'ips' );
	$limit = intval($wt->webRequest->getVal( 'limit', '500' ));
	$type  = $wt->webRequest->getSafeVal( 'type' );
	$wiki  = $wt->webRequest->getSafeVal( 'wiki' );
	$lang  = $wt->webRequest->getSafeVal( 'lang' );

//Remove unwanted chars from string eg. $lrm; !
	$cidr = trim(preg_replace('/[^\d|\.|\n|\/]/','', $cidr));
	
	if( preg_match( '/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\/\d{1,2}$/', $cidr ) == 0 && $type == 'range' ) {
		$wt->error = $I18N->msg( 'no_valid_cidr_range' );
		$wt->showPage($wt);
	}

//Show form if & parameters are invalid/empty
	if( !$cidr ) {
		$wt->showPage($wt);
	}
	
	if( $type == 'range' ) {
		$cidr_info = $base->calcCIDR( $cidr );
	}
	elseif( $type == 'list' ) {
		$cidr = explode( '\n', $cidr );
		$cidr_info = $base->calcRange( $cidr );
	}
	else {
		$wt->error = $I18N->msg( 'invalid_type' );
		$wt->showPage($wt);
	}
	
	$ip_prefix = $base->findMatch( $cidr_info['begin'], $cidr_info['end'] );

//Get a list of unique, matching (existing) IP's
	$matchingIPs = $base->getMatchingIPs($dbr, $ip_prefix);
	$http = new HTTP();
	$ipList = $base->getIPInformation($matchingIPs, $http);

//Start the calculation
	$list = $base->getRangeContribs($dbr, $lang, $wiki, $matchingIPs, $ip_prefix, $cidr_info, $limit);

	
$wt->content = $base->tmplPageResult;
	
$wt->assign( "cidr", $cidr_info['begin']."/".$cidr_info['suffix']);
$wt->assign( "ip_start", $cidr_info['begin']);
$wt->assign( "ip_end", $cidr_info['end']);
$wt->assign( "ip_number", $cidr_info['count']);
$wt->assign( "ip_found", count($matchingIPs));
$wt->assign( "ipList", $ipList);
$wt->assign( "list", $list);

unset( $base, $ipList, $list );
$wt->showPage($wt);


