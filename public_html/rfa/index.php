<?php

//Requires
	require_once( '../WebTool.php' );

	$wt = new WebTool( 'RfX Analysis', 'rfa', array() );
	$wt->setLimits();

	$wt->content = getPageTemplate( 'form' );
	$input_default = 'Wikipedia:Requests for adminship/Name of user';
	$wt->assign( 'input_default', $input_default );
	$wt->assign( 'recentrfx', getRecentRfXs() );
	
	$p1 = $wgRequest->getVal( 'p' );
	$p2 = $wgRequest->getVal( 'p2');
	$page = ( $p2 ) ? $p2 : $p1;
	

	if( !$page || $page == $input_default ){
		$wt->showPage();
	}

	$page = str_replace(' ', '_', $page);
	$page = preg_replace('/^(Wikipedia:)/', '', $page);
	$page = 'Wikipedia:'.$page;

print_r($page);	
	
//Create an RFA object & analyze
	$site = $wt->loadPeachy( 'en', 'wikipedia' );
	$myRFA = new RFA( $site, $page );

	$wt->content = getRfaResults( $myRFA, $page );

$wt->content = "<div style='width:75%; margin-left:100px' >". $wt->content ."</div>";
$wt->showPage();


function getRecentRfXs(){
	global $wt, $redis;
	
	$dbr = $wt->loadDatabase( 'en', 'wikipedia' );
	
	$queryA ="
			SELECT 'rfa' as type, page_title
			FROM page 
			WHERE page_namespace = '4'
			AND page_title LIKE 'Requests_for_adminship/%'
			AND page_title != 'Requests_for_adminship/RfA_and_RfB_Report'
			AND page_title != 'Requests_for_adminship/BAG'
			AND page_title NOT LIKE 'Requests_for_adminship/Nomination_cabal%'
			AND page_title != 'Requests_for_adminship/Front_matter'
			AND page_title != 'Requests_for_adminship/RfB_bar'
			AND page_title NOT LIKE 'Requests_for_adminship/%/%'
			AND page_title != 'Requests_for_adminship/nominate'
			AND page_title != 'Requests_for_adminship/desysop_poll'
			AND page_title != 'Requests_for_adminship/Draft'
			AND page_title != 'Requests_for_adminship/'
			AND page_title != 'Requests_for_adminship/Sample_Vote_on_sub-page_for_User:Jimbo_Wales'
			AND page_title != 'Requests_for_adminship/Promotion_guidelines'
			AND page_title != 'Wikipedia:Requests_for_adminship/Standards'
			ORDER BY page_id DESC
			LIMIT 100
		";		
	$queryB = "
			SELECT 'rfb' as type, page_title
			FROM page
			WHERE page_namespace = '4'
			AND page_title LIKE 'Requests_for_bureaucratship/%'
			AND page_title NOT LIKE 'Requests_for_bureaucratship/%/Bureaucrat_discussion'
			AND page_title != 'Requests_for_bureaucratship/Wikipedia:Requests_for_adminship'
			AND page_title != 'Requests_for_bureaucratship/Candidate_questions'
			ORDER BY page_id DESC
			Limit 100;
		";
	
	$res = $dbr->query( $queryA );
	$list = '<optgroup label="Requests for Adminship" >';
	foreach ($res as $i => $page ){
		$list .= '<option value="'.$page["page_title"].'" >'.$page["page_title"].'</option>';
	}
	$list .= '</optgroup>';
	
	$res = $dbr->query( $queryB );
	$list .= '<optgroup label="Requests for Bureaucratship" >';
	foreach ($res as $i => $page ){
		$list .= '<option value="'.$page["page_title"].'" >'.$page["page_title"].'</option>';
	}
	$list .= '</optgroup>';
	
	$dbr->close();
	return $list;
}
    
function getRfaResults( $myRFA, $getpage ){
	
	$result = "<h2>Voters for <a href=\"//en.wikipedia.org/wiki/{$getpage}\">{$getpage}</a></h2>";

    if (preg_match("/#redirect:?\s*?\[\[\s*?(.*?)\s*?\]\]/i",$buffer,$match)) {
        $result .= "<h3>Fatal Error</h3><p>Page redirects to ". $match[1] ."<br />
					<a href=\"".$_SERVER['PHP_SELF']."?p=".urlencode($match[1])." \" >Click here to analyze it</a>";
        return $result;
    }

    

    $enddate = $myRFA->get_enddate();
    $tally = count( $myRFA->get_support() ).'/'.count( $myRFA->get_oppose() ).'/'.count( $myRFA->get_neutral() );

    $totalVotes = count( $myRFA->get_support() ) + count( $myRFA->get_oppose() );
    if( $totalVotes != 0 ) {
      $tally .= ", " . number_format( ( count($myRFA->get_support()) / $totalVotes ) * 100, 2 ) . "%";
    }

    $result .= '<a href="//en.wikipedia.org/wiki/User:'.$myRFA->get_username().'">'.$myRFA->get_username().'</a>\'s RfA ('.$tally.'); End date: '.$enddate.'<br /><br />';
	$result .= 'Found <strong>'.count($myRFA->get_duplicates()).'</strong> duplicate votes (highlighted in <span class="dup">red</span>).'
    .' Votes the tool is unsure about are <span class="iffy1">italicized</span>.';

    $result .= "<h3>Support</h3>";
    $result .= get_h_l($myRFA->get_support(),$myRFA->get_duplicates());
    $result .= "<h3>Oppose</h3>";
    $result .= get_h_l($myRFA->get_oppose(),$myRFA->get_duplicates());
    $result .= "<h3>Neutral</h3>";
    $result .= get_h_l($myRFA->get_neutral(),$myRFA->get_duplicates());
    
    return $result;
}

function get_h_l( $var, $searchlist ) {
	$result = "";
	
	if (empty($var)) {
		$result .= "<ul><li>No items in list</li></ul>";
	}
	
	$result .= "<ol>";
	foreach ($var as $vr) {
		$iffy = False;

		if (isset($vr['iffy'])) {
			$iffy = $vr['iffy'];
		}
		
		if (isset($vr['error'])) {
			$text = "<strong>Error parsing signature:</strong> <em>".htmlspecialchars($vr['context'])."</em>";
		} 
		else {
			$text = $vr['name'];
		}

		if (isset($vr['name']) && in_array($vr['name'],$searchlist)) {
			if ($iffy == 1)
				echo "<li class=\"dup iffy1\">{$text}</li>\n";
				else
				echo "<li class=\"dup\">{$text}</li>\n";
		} 
		else {
			if ($iffy == 1){
				$result .= "<li class=\"iffy1\">{$text}</li>\n";
			}
			else{
				$result .= "<li>{$text}</li>\n";
			}
		}
	}
	$result .= "</ol>";
	
	return $result;
}


/**************************************** templates ****************************************
 *
*/
function getPageTemplate( $type ){

	$templateForm = '
	<br />
	<p>This tool identifies duplicate voters in a <a href="//en.wikipedia.org/wiki/Wikipedia:Requests_for_adminship">Request for adminship</a> on the English Wikipedia. <br />This tool can also analyze Requests for bureaucratship pages.</p>
	<form method="get" action="?" >
		<table>
			<tr><td>RfX page:&nbsp;</td><td><input type="text" name="p" size="50" value="{$input_default}" /></td></tr>
			<tr><td>or: </td><td>
				<select name="p2"> 
				<option value="" >Select from most recent RfA\'s / RfB\'s</option>
				{$recentrfx}
				</select></td></tr>
			<tr><td colspan=2><input type="submit" value="Analyze" /></td></tr>
		</table>
	</form>
	<br />
	';
	
	if( $type == "form" ) { return $templateForm; }
	if( $type == "result" ) { return $templateResult; }
}