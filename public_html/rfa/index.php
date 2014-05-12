<?php

//Requires
	require_once( '../WebTool.php' );

	$wt = new WebTool( 'RfX Analysis', 'rfa', array("smarty", "sitenotice", "replag") );
	$wt->setMemLimit();

	$pageForm = '
		<h1>RfA Analysis</h1>
			<p>This tool identifies duplicate voters in a <a href="//en.wikipedia.org/wiki/Wikipedia:Requests_for_adminship">Request for adminship</a> on the English Wikipedia. This tool can also analyze Requests for bureaucratship pages.</p>
		<h2>Analyze</h2>
		<form method="get" action="?" >
			<strong>RfA page:</strong>&nbsp;
			<input type="text" name="p" size="50" value="Wikipedia:Requests for adminship/Name of user" />
			<input type="submit" value="Analyze" />
		</form>
	  ';

	if (isset($_GET['p'])) {
		$targetpage = str_replace(' ','_',$_GET['p']);
		$targetpage = explode('?',$_GET['p']);
		$getpage = $targetpage[0];
		
		$wt->content = getRfaResults( $getpage );
	}
	else{
		$wt->content = $pageForm;
	}
	$wt->content = "<div style='width:75%; margin-left:100px' >". $wt->content ."</div>";
	$wt->showPage($wt);



    
function getRfaResults( $getpage ){
	global $site;
	
	$result = "<h2>Voters for <a href=\"//en.wikipedia.org/wiki/{$getpage}\">{$getpage}</a></h2>";

    if (preg_match("/#redirect:?\s*?\[\[\s*?(.*?)\s*?\]\]/i",$buffer,$match)) {
        $result .= "<h3>Fatal Error</h3><p>Page redirects to ". $match[1] ."<br />
					<a href=\"".$_SERVER['PHP_SELF']."?p=".urlencode($match[1])." \" >Click here to analyze it</a>";
        return $result;
    }

    //Create an RFA object & analyze
    $myRFA = new RFA( $site, $getpage );

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



