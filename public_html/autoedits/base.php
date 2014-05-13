<?php

class AutoEditsBase {


	public function getMatchingEdits( &$dbr, $username, $begin, $end, $count, $api = false ) {
		
		$AEBTypes = array(
				'Twinkle' => array( 'type' => 'LIKE', 'query' => '%WP:TW%', 'shortcut' => 'WP:TW' ),
				'AutoWikiBrowser' => array( 'type' => 'RLIKE', 'query' => '.*(AutoWikiBrowser|AWB).*', 'shortcut' => 'WP:AWB' ),
				'Friendly' => array( 'type' => 'LIKE', 'query' => '%WP:FRIENDLY%', 'shortcut' => 'WP:FRIENDLY' ),
				'FurMe' => array( 'type' => 'RLIKE', 'query' => '.*(User:AWeenieMan/furme|FurMe).*', 'shortcut' => 'WP:FURME' ),
				'Popups' => array( 'type' => 'LIKE', 'query' => '%Wikipedia:Tools/Navigation_popups%', 'shortcut' => 'Wikipedia:Tools/Navigation_popups' ),
				'MWT' => array( 'type' => 'LIKE', 'query' => '%User:MichaelBillington/MWT%', 'shortcut' => 'User:MichaelBillington/MWT' ),
				'Huggle' => array( 'type' => 'RLIKE', 'query' => '.*(\[\[WP:HG\|HG\]\]|WP:Huggle).*', 'shortcut' => 'WP:HG' ),
				'NPWatcher' => array( 'type' => 'LIKE', 'query' => '%WP:NPW%', 'shortcut' => 'WP:NPW' ),
				'Amelvand' => array( 'type' => 'LIKE', 'query' => 'Reverted % edit% by % (%) to last revision by %', 'shortcut' => 'User:Gracenotes/amelvand.js' ),
				'Igloo' => array( 'type' => 'RLIKE', 'query' => '.*(User:Ale_jrb/Scripts/igloo|GLOO).*', 'shortcut' => 'WP:IGL' ),
				'HotCat' => array( 'type' => 'LIKE', 'query' => '%(using [[WP:HOTCAT|HotCat]])%', 'shortcut' => 'WP:HOTCAT' ),
				'STiki' => array( 'type' => 'LIKE', 'query' => '%STiki%', 'shortcut' => 'WP:STiki' ),
				'Dazzle!' => array( 'type' => 'LIKE', 'query' => '%Dazzle!%', 'shortcut' => 'WP:Dazzle!' ),
				'Articles For Creation tool' => array( 'type' => 'LIKE', 'query' => '%([[WP:AFCH|AFCH]])%', 'shortcut' => 'WP:AFCH' ),
		);
		
		$cond_begin = ( $begin ) ? 'AND UNIX_TIMESTAMP(rev_timestamp) > ' . $dbr->strencode( strtotime( $begin )) : null;
		$cond_end 	= ( $end ) ? 'AND UNIX_TIMESTAMP(rev_timestamp) < ' . $dbr->strencode( strtotime( $end )) : null;
		
		$contribs = array();
		$error = false;
		$query = "";
		foreach( $AEBTypes as $name => $check ) {
			
			$cond_tool = 'AND rev_comment ' . $check['type'] . ' \'' . $check['query'] . '\'';
			
			$query .= "UNION
					SELECT '$name' as toolname, count(*) as count 
					FROM revision_userindex 
					WHERE rev_user_text = '$username' $cond_begin $cond_end $cond_tool
				";
		}
		$query = substr( $query, 6 );
		$res = $dbr->query( $query );

		$sum = 0;
		foreach ( $res->endArray as $i => $item ){
			$contribs["tools"][$i]["toolname"] = $item['toolname'];
			$contribs["tools"][$i]["count"] = $item['count'];
			$contribs["tools"][$i]["shortcut"] = $AEBTypes[ $item["toolname"] ]["shortcut"];
			$sum += $item["count"];
		}
		
		$contribs["total"] = $sum;
		$contribs["pct"] = number_format( ( ( $count ? $sum / $count : 0 ) *100 ), 2);
		$contribs["editcount"] = $count;
		
		return $contribs;
   }

}  
