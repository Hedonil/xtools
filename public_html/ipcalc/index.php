<?php

//Requires
	require_once( '../WebTool.php' );
	require_once( '../rangecontribs/base.php' );

//Load WebTool class
	$wt = new WebTool( 'IP calculator', 'ipcalc', array("smarty", "sitenotice", "replag") );
	$wt->setLimits();
	
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

//Start the calculation
	if( $type == 'range' ) {
		$cidr_info = $base->calcCIDR( $cidr );
	}
	elseif( $type == 'list' ) {
		#preg_match_all( '/((((25[0-5]|2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.){3}((25[0-5]|2[0-4][0-9])|([0-1]?[0-9]?[0-9])){1})/', $cidr, $m );
		
		$m = array("142.10.14.28","148.69.145.3");
		$cidr_info = $base->calcRange( $m );
		print_r($cidr_info);	
		$ips = array();
	
		foreach( $cidr_info['ips'] as $ip ) {
			$tmp = "<h3>{$ip['ip']}</h3>";
	
			$tmp .= "<ul>";/*'bin' => implode( '.', self::ip2bin( $ip ) ),
					'rdns' => gethostbyaddr( $ip ),
					'long' => ip2long( $ip ),
					'hex' => implode( '.', self::ip2hex( $ip ) ),
					'octal' => implode( '.', self::ip2oct( $ip ) ),
					'radians' => implode( '.', self::ip2rad( $ip ) ),
					'base64'*/
	
			$tmp .= "<li>Reverse DNS: {$ip['rdns']}</li>";
			$tmp .= "<li>Network address: {$ip['long']}</li>";
			$tmp .= "<li>Binary: {$ip['bin']}</li>";
	
			if( isset( $_GET['fun'] ) ) {
				$tmp .= "<li>Hexadecimal: {$ip['hex']}</li>";
				$tmp .= "<li>Octal: {$ip['octal']}</li>";
				$tmp .= "<li>Radians: {$ip['radians']}</li>";
				$tmp .= "<li>Base 64: {$ip['base64']}</li>";
				$tmp .= "<li>Letters: {$ip['alpha']}</li>";
			}
			$tmp .= "<li>More info: " .
				"<a href=\"//ws.arin.net/whois/?queryinput={$ip['ip']}\">WHOIS</a> · " .
				"<a href=\"//toolserver.org/~luxo/contributions/contributions.php?user={$ip['ip']}\">Global Contribs</a> · " .
				"<a href=\"//www.robtex.com/rbls/{$ip['ip']}.html\">RBLs</a> · " .
				"<a href=\"//www.dnsstuff.com/tools/tracert.ch?ip={$ip['ip']}\">Traceroute</a> · " .
				"<a href=\"//www.infosniper.net/index.php?ip_address={$ip['ip']}\">Geolocate</a> · " .
				"<a href=\"//toolserver.org/~overlordq/scripts/checktor.fcgi?ip={$ip['ip']}\">TOR</a> · " .
				"<a href=\"//www.google.com/search?hl=en&q={$ip['ip']}\">Google</a> · " 
				."</li>";
	
			$tmp .= "</ul>";
	
			$list = $tmp;
		}
	
	}
	else {
		$wt->error = 'Invalid type selected.' ;
	}
	
	
	

$wt->content = getPageTemplate( "result" );
	
$wt->assign( "cidr", $cidr_info['begin']."/".$cidr_info['suffix']);
$wt->assign( "ip_start", $cidr_info['begin']);
$wt->assign( "ip_end", $cidr_info['end']);
$wt->assign( "ip_number", $cidr_info['count']);
$wt->assign( "ipList", $ipList);
$wt->assign( "list", $list);

unset( $base, $tmp, $ips );
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
		<tr><td><b>{#ip_number#}:&nbsp;</b></td><td>{$ip_number}</td></tr>
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

