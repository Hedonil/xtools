<?php

//Requires
	require_once( '../WebTool.php' );
	require_once( 'base.php' );

//Load WebTool class
	$wt = new WebTool( 'Range contributions', 'rangecontribs', array("smarty", "sitenotice", "replag") );
	$wt->setMemLimit();
	$base = new RangecontribsBase();
	$wt->content = $base->getPageForm();
	
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

	
// 	if ( $list ) {
// 		$content->assign( 'showresult', true );
// 		$content->assign( "list", $list );
// 	}else {
// 		$content->assign( "nocontribs", "1" );
// 	}

$cidr = $cidr_info['begin']."/".$cidr_info['suffix'];
$ip_start = $cidr_info['begin'];
$ip_end = $cidr_info['end'];
$ip_number = $cidr_info['count'];
$ip_found = count($matchingIPs);


$pageResult = '
<table>
<tr><td><b>'.$I18N->msg('cidr').':	   </b></td><td>'.$cidr.'</td></tr>
<tr><td><b>'.$I18N->msg('ip_start').': </b></td><td>'.$ip_start.'</td></tr>
<tr><td><b>'.$I18N->msg('ip_end').':   </b></td><td>'.$ip_end.'</td></tr>
<tr><td><b>'.$I18N->msg('ip_number').':</b></td><td>'.$ip_number.'</td></tr>
<tr><td><b>'.$I18N->msg('ip_found').': </b></td><td>'.$ip_found.'</td></tr>
</table>

'.$ipList.'

<table>
'.$list.'
</table>
<br />
';

unset( $base, $ipList, $list );
$wt->content = $pageResult;
$wt->showPage($wt);


