<?php 

class RfapBase {
	
	public $tmplPageForm =' 
		<p>Welcome to the RfX Vote Calculator!</p>
	    <form action="index.php" method="get">
	    Username: <input type="text" name="name" /><br />
	    Get RfBs: <input type="checkbox" name="rfb" /><br />
	    <input type="submit" />
	    </form>
	';
	
	function get_rfap( $dbr, $name, $aorb ){
		global $site;
		
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
	
		$query = "
			SELECT pl_from , (select b.page_title from page as b where b.page_id = pl_from) as altname
			FROM page 
			JOIN pagelinks on pl_from=page_id and pl_namespace=page_namespace
			WHERE page_is_redirect = 1 AND page_namespace = 2 AND  pl_title = '$name'
		";
		
		$result = $dbr->query( $query );
		
		foreach ( $result->endArray as $alternatives ){
			$output["altnames"][] = $alternatives["altname"];
		}
		unset( $result );

		
		// Get all pages where the user has voted
		$query = "
			SELECT page_latest, UNIX_TIMESTAMP(rev_timestamp) as timestamp, page_title, COUNT(*)
			FROM revision_userindex
			JOIN page on page_id = rev_page
			WHERE rev_user_text = '$name'
				AND page_namespace = '4'
				AND page_title LIKE 'Requests_for_".$aorb."/%'
				AND page_title NOT LIKE '%$name%'
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
		
		foreach ( $result->endArray as $u => $rfas ) {
				
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
	


/********************************** legacy code below *****************************************/
		
/*
	function get_rfap_old( $dbr, $name, $aorb ){
		
		$query = "
			SELECT page_latest, page_title, COUNT(*) 
			FROM revision_userindex 
			JOIN page on page_id = rev_page 
			WHERE rev_user_text = '$name' 
				AND page_namespace = '4'  
				AND page_title LIKE 'Requests_for_".$aorb."/%' 
				AND page_title NOT LIKE '%$name%' 
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
				GROUP by page_title 
				ORDER BY COUNT(*) ASC
			";
		
		$result = $dbr->query( $query );
print_r($result);die;

		$allrfa = 0;
		$rfastoupdate = array();
		$thisrfas = array();

		foreach ( $result->endArray as $u => $rfas ) {
			$updated = 0;
			$allrfa++;
			$count = $rfas['COUNT(*)'];
			$title = utf8_decode($rfas['page_title']);
			
			$ts = "
				SELECT rev_timestamp 
				FROM revision_userindex 
				WHERE rev_id = '".$rfas['page_latest']."'
			  ";
			$resultrts = $dbr->query( $ts );
			$rts = mysql_fetch_assoc( $resultrts );
			
			$tenagos = time() - 864000;
			$tenago = date("YmdHis", $tenagos);
			array_push($thisrfas, $title);
			
			$title = mysql_real_escape_string($title);
			$indb = "
				SELECT * 
				FROM rfap 
				WHERE name = '$title'
			  ";
			$isindb = mysql_query($indb, $tools);
			$indb = mysql_fetch_assoc($isindb);

			if($indb['id'] == "" && $updated == 0) { array_push($rfastoupdate, $title); $updated = 1; }
			if($updated == 0) {
				$pullsten = $indb['pulls'] / 10;
				$pullsfivehund = $indb['pulls'] / 500;

				if($tenago < $rts['rev_timestamp'] && is_int($pullsten)) { array_push($rfastoupdate, $title); $updated = 1; }
				if(is_int($pullsfivehund) && $updated == 0) { array_push($rfastoupdate, $title); $updated = 1; }
				if($_GET['force'] == "1" && $updated == 0) { array_push($rfastoupdate, $title); $updated = 1; }
				
				if(isset($_GET['debug'])) {
					echo "$title - $updated<br />\n";
				}
			}
		}
		$upd = "RF".strtoupper( $aorb[0] )."s updated this run:";
		$nupd = 0;
			
		foreach ( $rfastoupdate as $rfatoup ) {
			$nupd++;
			$title = htmlentities($rfatoup);
			$how = self::update_rfa( $title, $name );
			$how2db = mysql_real_escape_string(serialize($how));
		    $md5 = md5($how2db);
		    
		    $existq = "DELETE FROM s51187_xtools.rfap WHERE name = '$rfatoup';";
		    $existr = mysql_query($existq, $tools);
		    $insert = "INSERT INTO s51187_xtools.rfap (name , md5 , pulls , data ) VALUES ( '$rfatoup' , '$md5', '0' , '$how2db' );";
		    $foo = mysql_query($insert, $tools);

		    if(!$foo) toDie("ERROR: No result returned.<br />$insert");
		    $upd .= " $title";
	    }
		if($nupd == 0) { $upd .= " None!"; }
		$ns = 0;
		$no = 0;
		$nn = 0;
		$nu = 0;
		$name_s = stripslashes($name);
		$name_a = rawurldecode(preg_replace('/ /', '_', $name_s));
		$name_b = rawurldecode(preg_replace('/_/', ' ', $name_s));
		
		$query = "
			SELECT ug_group 
			FROM user_groups 
			JOIN user ON ug_user = user_id 
			WHERE user_name = '$name' AND ug_group = 'bot'
		  ";
		$result = mysql_query($query, $mysql);
		$isbot = mysql_fetch_assoc($result);
		
		if(isset($_GET['debug'])) {
			echo "<pre>\n";
			echo "user = $name , user_s = $name_s , user_a = $name_a\n";
			print_r($thisrfas);
			echo "</pre>\n";
		}
	    echo "<h2>Supported:</h2><ol>\n";

	    foreach ( $thisrfas as $key => $arfa ) {
			$arfa_s = mysql_real_escape_string($arfa);
			//echo $arfa_s."\n\n";
			$query = "SELECT * FROM rfap WHERE name = '$arfa_s';";
			$result = mysql_query($query, $tools);
			
			if(!$result) toDie("ERROR: No result returned.");
			
			$rfad = mysql_fetch_assoc($result);
			$data = unserialize(stripslashes($rfad['data']));
			$views = $rfad['pulls'];
	
			foreach( $data['support'] as $od ) {
				$od = ucfirst($od);
#!mod,* /!		if( preg_match( '/(.*)\#.* /' , $od , $matchme ) > 0 ) { $od = $matchme[1];} //A fix just for keeper :)
				
				if($od == $name_s || $od == $name_a || $od == $name_b || in_array('User:'.$od, $names_old)) {
					$stripped_arfa = preg_replace("/Requests_for_$aorb\//i", "" ,$arfa);
					if(isset($_GET['debug'])) { $viewout = " (Views: $views|Key: $key)"; } else { $viewout = ""; }
					
					if(in_array("Wikipedia:Requests for $aorb/$stripped_arfa", $successful ) ) {
						$endresult = "<b>(successful)</b>";
					}
					else {
						$endresult = "";
					}
					echo "<li><a href = \"//en.wikipedia.org/wiki/Wikipedia:$arfa\">$stripped_arfa</a>{$viewout} {$endresult}</li>\n";
					
					$pid = $rfad['id'];
					$pulls = $rfad['pulls'];
					$pullsnew = $pulls + 1;
					$ud = "UPDATE rfap SET pulls = '$pullsnew' WHERE id = '$pid';";
					$udr = mysql_query($ud, $tools);
					if(!$udr) toDie("ERROR: No result returned.");
					
					unset($thisrfas[$key]);
					$ns++;
				}
			}
		}
		
		if(isset($_GET['debug'])) {
			echo "<pre>\n";
			print_r($thisrfas);
			echo "</pre>\n";
		}
		
		echo "</ol><h2>Neutral:</h2><ol>\n";
		foreach ($thisrfas as $key => $arfa) {
			$arfa_s = mysql_real_escape_string($arfa);
			$query = "SELECT * FROM rfap WHERE name = '$arfa_s';";
			$result = mysql_query($query, $tools);
			
			if(!$result) toDie("ERROR: No result returned.");
			
			$rfad = mysql_fetch_assoc($result);
			$data = unserialize(stripslashes($rfad['data']));
			$views = $rfad['pulls'];
			
			foreach($data['neutral'] as $od) {
				$od = ucfirst($od);
#!mod .* /!		if( preg_match( '/(.*)\#.* /' , $od , $matchme ) > 0 ) { $od = $matchme[1]; } //A fix just for keeper :)
				if($od == $name_s || $od == $name_a || $od == $name_b || in_array('User:'.$od, $names_old)) {
				$stripped_arfa = preg_replace("/Requests_for_$aorb\//i", "" ,$arfa);
				if(isset($_GET['debug'])) { $viewout = " (Views: $views|Key: $key)"; } else { $viewout = ""; }
				
				if(in_array("Wikipedia:Requests for $aorb/$stripped_arfa", $successful ) ) {
					$endresult = "<b>(successful)</b>";
				}
				else {
					$endresult = "";
				}
				echo "<li><a href = \"//en.wikipedia.org/wiki/Wikipedia:$arfa\">$stripped_arfa</a>{$viewout} {$endresult}</li>\n";
				
				$pid = $rfad['id'];
				$pulls = $rfad['pulls'];
				$pullsnew = $pulls + 1;
				$ud = "UPDATE rfap SET pulls = '$pullsnew' WHERE id = '$pid';";
				$udr = mysql_query($ud, $tools);
				
				if(!$udr) toDie("ERROR: No result returned.");
					unset($thisrfas[$key]);
					$nn++;
				}
			}
		    
		}
		
		if(isset($_GET['debug'])) {
			echo "<pre>\n";
			print_r($thisrfas);
			echo "</pre>\n";
		}
		echo "</ol><h2>Opposed:</h2><ol>\n";
		
		foreach ($thisrfas as $key => $arfa) {
			
		    $arfa_s = mysql_real_escape_string($arfa);
			$query = "SELECT * FROM rfap WHERE name = '$arfa_s';";
			$result = mysql_query($query, $tools);
			
			if(!$result) toDie("ERROR: No result returned.");
			$rfad = mysql_fetch_assoc($result);
			$data = unserialize(stripslashes($rfad['data']));
			$views = $rfad['pulls'];
			
			foreach($data['oppose'] as $od) {
				$od = ucfirst($od);
#!mod .* /!		if( preg_match( '/(.*)\#.* /' , $od , $matchme ) > 0 ) { $od = $matchme[1]; } //A fix just for keeper :)
				if($od == $name_s || $od == $name_a || $od == $name_b || in_array('User:'.$od, $names_old)) {
					$stripped_arfa = preg_replace("/Requests_for_$aorb\//i", "" ,$arfa);
					if(isset($_GET['debug'])) { $viewout = " (Views: $views|Key: $key)"; } else { $viewout = ""; }

					if(in_array("Wikipedia:Requests for $aorb/$stripped_arfa", $successful ) ) {
						$endresult = "<b>(successful)</b>";
					}
					else {
						$endresult = "";
					}
					
					echo "<li><a href = \"//en.wikipedia.org/wiki/Wikipedia:$arfa\">$stripped_arfa</a>{$viewout} {$endresult}</li>\n";

					$pid = $rfad['id'];
					$pulls = $rfad['pulls'];
					$pullsnew = $pulls + 1;
					$ud = "UPDATE rfap SET pulls = '$pullsnew' WHERE id = '$pid';";
					$udr = mysql_query($ud, $tools);
					if(!$udr) toDie("ERROR: No result returned.");

					unset($thisrfas[$key]);
					$no++;
				}
			}
		}

		if(isset($_GET['debug'])) {
			echo "<pre>\n";
			print_r($thisrfas);
			echo "</pre>\n";
		}
		echo "</ol><h2>Did not comment / Could not parse:</h2><ol>\n";

		foreach ($thisrfas as $key => $arfa) {
			
			$arfa_s = mysql_real_escape_string($arfa);
			$query = "SELECT * FROM rfap WHERE name = '$arfa_s';";
			$result = mysql_query($query, $tools);
			
			if(!$result) toDie("ERROR: No result returned.");
			$rfad = mysql_fetch_assoc($result);
			$stripped_arfa = preg_replace("/Requests_for_$aorb\//i", "" ,$arfa);
			$views = $rfad['pulls'];
			
			if(isset($_GET['debug'])) { $viewout = " (Views: $views|Key: $key)"; } else { $viewout = ""; }
			echo "<li><a href = \"//en.wikipedia.org/wiki/Wikipedia:$arfa\">$stripped_arfa</a>$viewout</li>\n";
			$pid = $rfad['id'];
			$pulls = $rfad['pulls'];
			$pullsnew = $pulls + 1;
			$ud = "UPDATE rfap SET pulls = '$pullsnew' WHERE id = '$pid';";
			$udr = mysql_query($ud, $tools);
			if(!$udr) toDie("ERROR: No result returned.");
		
			$nu++;
		}
		echo "</ol>\n";
		$ar = $ns + $nn + $no;
		
		if($ns > 0) {
			$sp = round($ns / $ar, 3) * 100;
		}
		else {
			$sp = 0;
		}
		
		if($nn > 0) {
			$np = round($nn / $ar, 3) * 100;
		}
		else {
			$np = 0;
		}
		
		if($no > 0) {
			$op = round($no / $ar, 3) * 100;
		} else {
			$op = 0;
		}
		
		echo "<br />$name has edited $allrfa RF".strtoupper( $aorb[0] )."'s! (Supported: $ns [$sp%], Neutral: $nn [$np%], Opposed: $no [$op%], Unknown $nu)<br />\n<small><center>$upd</small></center>\n";
	}
	
	function update_rfa( $title, $name ) {
		global $wpq;
		
		$results['support']=array();
		$results['oppose']=array();
		$results['neutral']=array();
		
		$myRFA = new RFA();
		
		$title = stripslashes($title);
		if(isset($_GET['debug'])) { echo "title = $title\n<br />\n"; }
		$buffer = $wpq->getpage("Wikipedia:$title",false);
		
		$result = $myRFA->analyze($buffer);
		$d_support =$myRFA->support;
		$d_oppose = $myRFA->oppose;
		$d_neutral = $myRFA->neutral;
		
		foreach ( $d_support as $support ) {
			if( !isset($support['name']) ) {
				if( isset($support['error']) ) $support['name'] = "Error: Unable to parse signature";
				else $support['name'] = "";
			}
			array_push($results['support'], $support['name'] );
		}
		foreach ( $d_neutral as $neutral ) {
			if( !isset($neutral['name']) ) {
				if( isset($neutral['error']) ) $neutral['name'] = "Error: Unable to parse signature";
				else $neutral['name'] = "";
			}
			array_push($results['neutral'], $neutral['name'] );
		}
		foreach ( $d_oppose as $oppose ) {
			if( !isset($oppose['name']) ) {
				if( isset($oppose['error']) ) $oppose['name'] = "Error: Unable to parse signature";
				else $oppose['name'] = "";
			}
			array_push($results['oppose'], $oppose['name'] );
		}
		return $results;
	}
	
	
	function check_user_bot(){
		
		$query = "SELECT user_id,user_editcount FROM user WHERE user_name = '$name';";
		
		$result = mysql_query($query, $mysql);
		$uinfo = mysql_fetch_assoc($result);
		
		if($uinfo['user_id'] == "") {
			toDie("<br />Invalid user!<br />");
		}
		
		$query = "SELECT ug_group FROM user_groups JOIN user ON ug_user = user_id WHERE user_name = '$name' AND ug_group = 'bot';";
		
		$result = mysql_query($query, $mysql);
		$isbot = mysql_fetch_assoc($result);
		if($isbot['ug_group'] == "bot") {
			toDie("<br />Why would a bot comment at RFA?<br />Not wasting server time with this query.<br />");
		}
	}
*/	
	
}