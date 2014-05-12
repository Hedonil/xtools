<?php

//Requires
	require_once( '../WebTool.php' );
	require_once( 'base.php' );


//Load WebTool class
	$wt = new WebTool( 'RfX Vote', 'rfap', array("smarty", "sitenotice", "replag") );
	$wt->setMemLimit();

	$base = new RfapBase();
	$wt->content = $base->tmplPageForm;
	$wt->assign("lang", "en");
	$wt->assign("wiki", "wikipedia");
	
	$name = $wt->webRequest->getSafeVal( 'name' );

//Show form if username is not set (or empty)
	if( !$wt->webRequest->getSafeVal( 'getBool', 'name' ) ) {
		$wt->showPage($wt);
	}
//Check if the user is an IP address
	if( preg_match( '/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $name ) ) {
		$wt->error = "User cannot be an IP.";
		$wt->showPage($wt);
	}
	
	
	if( isset( $_GET['rfb'] ) ) {
		$aorb = 'bureaucratship';
	}
	else {
		$aorb = 'adminship';
	}
	
// Calculate all the things	
	$votes = $base->get_rfap( $dbr, $name, $aorb );


// Generate the output
	$output = '<h2>How did a user vote?</h2>';
	
	$output .= '<table><tr><td><p>Considered usernames:</p><ul>';
	foreach ( $votes["altnames"] as $i => $altname ){
		$output .= '<li><a href="//en.wikipedia.org/wiki/User:'.$altname.'" >'.$altname.'</a></li> ';
	}
	$output .= '</ul>';
	
	$total = count($votes["support"]) + count($votes["oppose"]) + count($votes["neutral"]) + count($votes["unknown"]);
	$output .= '
		<span>'.$votes["altnames"][0].' has edited '.$total.' RfA\'s!</span><br />
		<span> 
			Supported: '.count($votes["support"]).',  
			Opposed: '.count($votes["oppose"]).', 
			Neutral: '.count($votes["neutral"]).', 
			Unknown: '.count($votes["unknown"]).'
		</span></td>
	  ';
	
	$rs = ( count($votes["support"]) / $total ) * 100;
	$ro = ( count($votes["oppose"]) / $total ) * 100;
	$rn = ( count($votes["neutral"]) / $total ) * 100;
	$ru = ( count($votes["unknown"]) / $total ) * 100;
	$output .= '<td><img src="//chart.googleapis.com/chart?cht=p3&amp;chd=t:'."$rs,$ro,$rn,$ru".'&amp;chs=300x120&amp;chdl=Suppport|Oppose|Neutral|Unknown&amp;chco=55FF55|FF5555|CEC7C7|E6E68A&amp;chf=bg,s,00000000" alt="chart" /></td>';
	$output .= '</tr></table>';
	
	foreach ( $votes as $type => $voteresults ){
		
		if ( $type == "altnames" ){ continue; }

		$output .= '<h2>'.ucfirst($type).'</h2><table>';
		
		foreach ( $voteresults as $i => $item ){
			$pagetitle = str_replace('_', ' ', preg_replace( '/^.*\/(.*)$/', '\1', $item["page"] ));
			$output .= '
					<tr>
					<td>'.($i+1).'. &nbsp; </td>
					<td style="font-size:90%" >'.$item["startdate"].' &middot; </td>
					<td style="font-size:90%" >(<span style="color:green">'.$item["pro"].'</span>, <span style="color:red">'.$item["contra"].'</span>, <span style="color:gray">'.$item["neutral"].'</span>)</td>
					<td><a href="//en.wikipedia.org/wiki/'.$item["page"].'" > &middot; '.$pagetitle.'</a></td>
					</tr>
				';
		}
		$output .= '</table>';
	}	

	
	$wt->content = $output;
	unset( $base, $output );
	$wt->showPage($wt);
	
	
	