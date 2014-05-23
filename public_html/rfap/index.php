<?php

//Requires
	require_once( '../WebTool.php' );


//Load WebTool class
	$wt = new WebTool( 'RfX Vote', 'rfap', array() );
	$wt->setLimits();

	$wt->content = getPageTemplate( 'form' );
	$wt->assign("lang", "en");
	$wt->assign("wiki", "wikipedia");
	
	$name = $wgRequest->getVal( 'name' );

//Show form if username is not set (or empty)
	if( !$wgRequest->getVal( 'name' ) ) {
		$wt->showPage();
	}
//Check if the user is an IP address
	if( preg_match( '/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $name ) ) {
		$wt->error = "User cannot be an IP.";
		$wt->showPage();
	}
	
	
	if( isset( $_GET['rfb'] ) ) {
		$aorb = 'bureaucratship';
	}
	else {
		$aorb = 'adminship';
	}
	
// Calculate all the things	
	$dbr = $wt->loadDatabase( 'en', 'wikipedia' );
	$site = $wt->loadPeachy( 'en', 'wikipedia' );
	$votes = get_rfap( $dbr, $site, $name, $aorb );


// Generate the output
	
	$output = '<table><tr><td><p>Considered usernames:</p><ul>';
	foreach ( $votes["altnames"] as $i => $altname ){
		$output .= '<li><a href="//en.wikipedia.org/wiki/User:'.$altname.'" >'.$altname.'</a></li> ';
	}
	$output .= '</ul>';
	
	$total = count($votes["support"]) + count($votes["oppose"]) + count($votes["neutral"]) + count($votes["unknown"]);
	$output .= '
		<span>'.$votes["altnames"][0].' has voted on '.$total.' Request for '.ucfirst($aorb).' pages!</span><br />
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
	$output .= '<td><img src="//chart.googleapis.com/chart?cht=p&amp;chd=t:'."$rs,$ro,$rn,$ru".'&amp;chs=300x150&amp;chdl=Suppport|Oppose|Neutral|Unknown&amp;chco=55FF55|FF5555|CEC7C7|E6E68A&amp;chf=bg,s,00000000" alt="chart" /></td>';
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
	unset( $output );
	$wt->showPage();
	
	
	
	
	function get_rfap( &$dbr, &$site, $name, $aorb ){
	
		$output = array(
				"altnames" => array(),
				"support"  => array(),
				"oppose"   => array(),
				"neutral"  => array(),
				"unknown"  => array(),
				"dupes"    => array(),
		);
	
		// Get alternative names
		$output["altnames"][] = $name;
		
		$sql_aorb = $dbr->strencode($aorb);
		$sql_name = $dbr->strencode($name);
		
		$query = "
			SELECT pl_from , (select b.page_title from page as b where b.page_id = pl_from) as altname
			FROM page
			JOIN pagelinks on pl_from=page_id and pl_namespace=page_namespace
			WHERE page_is_redirect = 1 AND page_namespace = 2 AND  pl_title = '$sql_name'
		";
	
		$result = $dbr->query( $query );
	
		foreach ( $result as $alternatives ){
			$output["altnames"][] = $alternatives["altname"];
		}
		unset( $result );
	
	
		// Get all pages where the user has voted
		$query = "
			SELECT page_latest, UNIX_TIMESTAMP(rev_timestamp) as timestamp, page_title, COUNT(*)
			FROM revision_userindex
			JOIN page on page_id = rev_page
			WHERE rev_user_text = '$sql_name'
			AND page_namespace = '4'
			AND page_title LIKE 'Requests_for_".$sql_aorb."/%'
			AND page_title NOT LIKE '%$sql_name%'
			AND page_title != 'Requests_for_adminship/RfA_and_RfB_Report'
			AND page_title != 'Requests_for_adminship/BAG'
			AND page_title NOT LIKE 'Requests_for_adminship/Nomination_cabal%'
			AND page_title != 'Requests_for_adminship/Front_matter'
			AND page_title != 'Requests_for_adminship/RfB_bar'
			AND page_title NOT LIKE 'Requests_for_adminship/%/%'
			AND page_title != 'Requests_for_adminship/nominate'
			AND page_title != 'Requests_for_adminship/desysop_poll'
			AND page_title != 'Requests_for_adminship/Draft'
			AND page_title != 'Requests_for_adminship/Header'
			AND page_title != 'Requests_for_adminship/?'
			AND page_title != 'Requests_for_adminship/'
			AND page_title != 'Requests_for_adminship/Sample_Vote_on_sub-page_for_User:Jimbo_Wales'
			AND page_title != 'Requests_for_adminship/Promotion_guidelines'
			AND page_title != 'Wikipedia:Requests_for_adminship/Standards'
			GROUP by page_title
			ORDER BY timestamp DESC
		";
	
		$result = $dbr->query( $query );
	
		foreach ( $result as $u => $rfas ) {
	
			$myRFA = null;
			$candidate = "";
			$page_title = "Wikipedia:".$rfas["page_title"];
			$timestamp = date("Y-m-d", $rfas["timestamp"] );
				
			//Create an RFA object & analyze
			$myRFA = new RFA( $site, $page_title );
		
			$candidate = html_entity_decode( $myRFA->get_username() );
			$subArr = array(
					"candidate" => $candidate,
					"page" => $page_title,
					"startdate" => $timestamp,
					"pro" => count( $myRFA->get_support() ),
					"contra" => count( $myRFA->get_oppose() ),
					"neutral" => count($myRFA->get_neutral() ),
				);
							
			foreach ( $myRFA->get_support() as $support ){
				if ( in_array( $support["name"], $output["altnames"] ) ){
					$output["support"][] = $subArr;
					continue(2);
				}
			}

			foreach ( $myRFA->get_oppose() as $oppose ){
				if ( in_array( $oppose["name"], $output["altnames"] ) ){
					$output["oppose"][] = $subArr;
					continue(2);
				}
			}

			foreach ( $myRFA->get_neutral() as $neutral ){
				if ( in_array( $neutral["name"], $output["altnames"] ) ){
					$output["neutral"][] = $subArr;
					continue(2);
				}
			}

			foreach ( $myRFA->get_duplicates() as $duplicates ){
				if ( in_array( $duplicates["name"], $output["altnames"] ) ){
					$output["dupes"][] = $subArr;
					continue(2);
				}
			}
			
			$output["unknown"][] = $subArr;
				
		}
	
		return $output;
	}
	
	
/**************************************** templates ****************************************
 *
*/
function getPageTemplate( $type ){

	$templateForm = '
			
	<br />
	<form action="index.php" method="get">
		<table>
		<tr><td>Username: </td><td><input type="text" name="name" /></td></tr>
		<tr><td>Get RfBs: </td><td><input type="checkbox" name="rfb" /></td></tr>
		<tr><td colspan=2 ><input type="submit" value="{#submit#}"/></td></tr>
		</table>
	</form>
	<br />
	';
	
	if( $type == "form" ) { return $templateForm; }
	if( $type == "result" ) { return $templateResult; }
	
}