<?php

//Requires
	require_once( '../WebTool.php' );
	require_once( 'base.php' );

//Load WebTool class
	$wt = new WebTool( 'Range contributions', 'rangecontribs', array("smarty", "sitenotice", "replag") );
	$wt->setMemLimit();
	$base = new RangecontribsBase();
	$wt->content = getPageTemplate( "form" );
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

	
$wt->content = getPageTemplate( "result" );
	
$wt->assign( "cidr", $cidr_info['begin']."/".$cidr_info['suffix']);
$wt->assign( "ip_start", $cidr_info['begin']);
$wt->assign( "ip_end", $cidr_info['end']);
$wt->assign( "ip_number", $cidr_info['count']);
$wt->assign( "ip_found", count($matchingIPs));
$wt->assign( "ipList", $ipList);
$wt->assign( "list", $list);

unset( $base, $ipList, $list );
$wt->showPage($wt);


/**************************************** templates ****************************************
 * 
 */
function getPageTemplate( $type ){

	$templateForm = '
			
	<span>{#rc_usage_0#}</span>
	<ol>
	<li>{#ip_range#}: &nbsp;{#rc_usage_1#} 0.0.0.0/0</li>
	<li>{#ip_list#}: &nbsp;{#rc_usage_2#}</li>
	</ol><br />
	<form action="?" method="get">
	<table>
	<tr>
		<td style="padding-left:5px" >Wiki: <input type="text" value="{$lang}" name="lang" size="9" />.<input type="text" value="{$wiki}" size="10" name="wiki" />.org</td>
	</tr>
	<tr>
		<td style="padding-left:5px" >Limit:
		<select name="limit">
		<option value="50">50</option>
		<option selected value="500" >500</option>
		<option value="5000">5000</option>
		</select>
		</td>
	</tr>
	<tr></tr>
	<tr>
		<td style="padding-left:5px; display:inline" >
		<span style="padding-right:20px">{#ip_range#}<input type="radio" name="type" value="range" /></span>
		<span>{#ip_list#}<input type="radio" name="type" value="list" /></span>
		</td>
	</tr>
	<tr>
		<td><textarea name="ips" rows="10" cols="40"></textarea></td>
	</tr>
	<tr>
		<td><input type="submit" value="{#submit#}"/></td>
	</tr>
	</table>
	</form>
	<br />
	<hr />
	';

	
	$templateResult = '
			
	<table>
		<tr><td><b>{#cidr#}: 	 </b></td><td>{$cidr}</td></tr>
		<tr><td><b>{#ip_start#}: </b></td><td>{$ip_start}</td></tr>
		<tr><td><b>{#ip_end#}:   </b></td><td>{$ip_end}</td></tr>
		<tr><td><b>{#ip_number#}:</b></td><td>{$ip_number}</td></tr>
		<tr><td><b>{#ip_found#}: </b></td><td>{$ip_found}</td></tr>
	</table>
		{$ipList}
	<table>
		{$list}
	</table>
	<br />
	';
	
	if( $type == "form" ) { return $templateForm; }
	if( $type == "result" ) { return $templateResult; } 

}
