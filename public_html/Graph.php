<?php


class xGraph{
	
	private $font_color       = '#0044CC';
	private $background_color = '#DDFFFF';
	private $axis_color       = '#0066CC';
	private $grid_color       = '#3366CC';
	
	
	static function makePieGoogle( $data, $title = NULL ){
	
		$ff = array_values($data);
		$sum = array_sum( $ff );
		foreach( array_values($data) as $value ){
			$pctdata[] = number_format( ($value / $sum)*100 , 1);
		}
		
		foreach( array_keys($data) as $nsid ){
			$colors[] = str_replace("#", "", self::GetColorList( $nsid ));
		} 
		
		$chartbase = "//chart.googleapis.com/chart?";
		$chdata = array(
				"cht" => "p",
				"chs" => "250x250",
				"chp" => '-1.55',
				"chf" => "bg,s,00000000",
				"chd" => "t:".implode(",", $pctdata),
				"chl" => "", //implode("|", array_keys($data)),
				"chco" => implode("|", $colors)
				
			);
		
		return $chartbase.http_build_query($chdata);
	}
	
	/**
	 * Page history: TOP Editors
	 */
	static function makePieTopEditors( $title, $total, &$data ){
		
		$i =0;
		foreach ($data as $user => $details){
			$val = number_format( ($details["all"] / $total)*100,1);
			$users[] = $user."  ($val%)";
			$values[] = $val;
			$colors[] = str_replace("#", "", self::GetColorList( $i ));
			$i++;
			if ($i == 9) break;
		}
		$users[] = "others";
		$colors[] = str_replace("#", "", self::GetColorList( 100 ));
		$values[] = 100-array_sum($values);
		
		$chartbase = "//chart.googleapis.com/chart?";
		$chdata = array(
				"cht" => "p",
				"chtt" => $title,
				"chs" => "500x250",
				"chf" => "bg,s,00000000",
				"chp" => '-1.55',
				"chd" => "t:".implode(",", $values),
				"chdl" => implode("|", $users),
				"chdls" => '737373,13',
				'chdlp'=> 'r|l',
				"chco" => implode("|", $colors)
		
		);
		
		return $chartbase.http_build_query($chdata);
	}
	
	function makePieTest(){
		
		$ff = XtoolsTheme::GetColorList();
		$i=0;
		$offset = 0;
		foreach ($ff as $num => $color){
			$i++;
			if ($i < $offset ) {continue;}
			$values[]=10;
			$colors[] = str_replace("#", "", $color);
			$legends[] = $num."-".str_replace("#", "", $color);
			
			if ( ($i-$offset) == 20) break;
		}
		
		$chartbase = "//chart.googleapis.com/chart?";
		$chdata = array(
				"cht" => "p",
				"chs" => "600x300",
				"chf" => "bg,s,00000000",
				"chp" => '-1.55',
				"chd" => "t:".implode(",", $values),
				"chdl" => implode("|", $legends),
				"chdls" => '737373,13',
				'chdlp'=> 'r|l',
				"chco" => implode("|", $colors)
		
		);
		
		return $chartbase.http_build_query($chdata);
	}
	
	static function makeColorTable(){
		
		$ff = self::GetColorList();
		$i=0;
		$list ='<table>';
		$tds ="";
		foreach ($ff as $num => $color){
			$i++;
			$tds .= "<td style='min-width:250px; background-color:$color ' > </td><td>$num</td><td>$color</td>";
			if ( $i % 3 == 0){
				$list .= '<tr>'.$tds.'</tr>';
				$tds ="";
			}
		}
		$list .='</table>';
		
		return $list;
	}
	
	static function makeChartArticle( $type, $data, $events, $colors ){

		
		$maxsizeTotal = 0;
		$maxeditTotal = 0;
		foreach( $data as $year => $values){
			$years[] = $year; 
			$all[] = $values["all"];
			$minor[] = $values["minor"];
			$anon[] = $values["anon"];
			
			$maxsize=0;
			foreach ( $values["months"] as $i => $mdetails ){
				if( $mdetails["size"] > $maxsize ){ $maxsize = $mdetails["size"]; }  
			}
			$tmpssize[] = $maxsize;
			
			if ( $values["all"] > $maxeditTotal ){ $maxeditTotal = $values["all"]; }
			if ( $maxsize > $maxsizeTotal ) { $maxsizeTotal = $maxsize; }
		}
		
		//scaling edits to size
		$factor = $maxeditTotal / $maxsizeTotal;
		foreach ( $tmpssize as $value){
			$size[] = intval( $value * $factor );
		}
	
		$chartbase = "//chart.googleapis.com/chart?";
		$chdata = array(
				'cht' => 'bvg',
				'chs' => '1000x200',
				"chf" => "bg,s,00000000",
				'chco' => $colors["all"].','.$colors["anon"].','.$colors["minor"].','.$colors["size"],
				'chd' => 't3:'.implode(',', $all).'|'.implode(',', $anon).'|'.implode(',', $minor).'|'.implode(',', $size).'|2,3,0,0,4',
				'chdl' => 'All|IP|Minor|Article size',
				'chdlp'=> 'r|l',
				'chds' => 'a',
				'chbh' => '10,1,15',
				'chxt' => 'y,y,x,r,r',
				'chxl' => '1:||Edits||2:|'.implode('|', $years).'|4:||Size (kb)|',
				'chxr' => '0,0,'.$maxeditTotal.'|3,0,'.$maxsizeTotal,
				'chm' => 'D,737373,3,0,1,1',
				'chem' => 'y;s=cm_repeat_color;ds=3;dp=all;d=flag,4,5,V,12,0,F00,0F0,00F,000,2,hv'
		);
		
		return $chartbase.http_build_query($chdata);
	}

		
	/**
	 * Legend for for edit counter namespace edits
	 * @param unknown $data
	 * @param unknown $namespaces
	 * @return string
	 */
	static function makeLegendTable( &$data, &$namespaces ){
		global $wt;
		
		$sum = array_sum( $data );
		$i = 0;
		$legendNS = '<table style="font-size:85%;" >';
		foreach ( $data as $nsid => $count ){

			$color = self::GetColorList($nsid);
			$legendNS .= '
			<tr>
			<td><span style="display:inline-block; border-radius:2px; height:14px; width:14px; background-color:'.$color.' "></span></td>
			<td>'.$namespaces["names"][$nsid].'</td>
			<td style="text-align:right; padding-left:15px;">'.$wt->numFmt($count).'</td>
			<td style="text-align:right; padding-left:10px;">'.$wt->numFmt( ($count/$sum)*100,1).'%</td>
			</tr>';
			
			$i++;
		}
		$legendNS .= "</table>";
		
		return $legendNS;
	}
	
	
	static function makeHorizontalBar( $type, $monthTotals, $width = 500 ) {
		global $wt;

#		if( $this->miPhone ) $width = 150;

		if ($type == "year"){
			
			$tmp = array();
			foreach( $monthTotals as $month => $edits ) {
				$year = substr( $month, 0, 4);
				foreach( $monthTotals[$month] as $nsid => $count ) {
					$tmp[$year][$nsid] += $count;
				}
			}
			$monthTotals = $tmp;
		}
		
		$month_total_edits = array();
		foreach( $monthTotals as $month => $edits ) {
			$month_total_edits[$month] = ($edits == array()) ? 0 : array_sum($edits);
		}
	
		$max_width = max( $month_total_edits );
	
		$pixels = array();
		foreach( $monthTotals as $month => $nsdata ) {
			if( count( $nsdata ) == 0 ) $pixels[$month] = array();
			
			foreach( $nsdata as $nsid => $count ) {
				$pixels[$month][$nsid] = ceil(($width * $count) / $max_width);
			}
		}
		
		$msg = '<table class="months">';
		$imsg = '<table>';
	
		foreach( $pixels as $month => $namespace_counts ) {
			$msg .= '<tr>';
			$imsg .= '<tr class="months">';
			
			$mtem = $month_total_edits[$month];	
			if( $mtem != "0" ) {
				$msg .= '<td title="'.htmlentities( self::getMonthPopup( $monthTotals[$month], $month ), ENT_QUOTES, 'UTF-8').'" class="date">'.$month.'</td>
						 <td style="text-align:right; padding-right:5px;" >'. $wt->numFmt( $mtem ) .'</td>';
				$imsg .= '<td class="date" >'.$month.'</td><td>'.$month_total_edits[$month].'</td>';
			}
			else {
				$msg .= '<td class="date" >'.$month.'</td><td>'. $wt->numFmt( $mtem ) .'</td>';
				$imsg .= '<td class="date" >'.$month.'</td><td>'. $wt->numFmt( $mtem ) .'</td>';
			}
				
			ksort( $namespace_counts );
				
			$msg .= "<td>";
			$imsg .= "<td>";
				
			if( $month_total_edits[$month] != "0" ) {
				$msg .= '<div class="outer_bar" title="'.htmlentities( self::getMonthPopup( $monthTotals[$month], $month ), ENT_QUOTES, 'UTF-8').'" >';
				$imsg .= '<div class="outer_bar">';
			}
				
			foreach( $namespace_counts as $namespace_id => $pixel ) {
				$msg .= '<div class="bar" style="border-left:' . $pixel . 'px solid ' . self::GetColorList($namespace_id) . '" >';
				$imsg .= '<div class="bar" style="border-left:' . $pixel . 'px solid ' . self::GetColorList($namespace_id) . '" >';
			}
				
			$msg .= str_repeat( "</div>", count( $namespace_counts ) );
			$imsg .= str_repeat( "</div>", count( $namespace_counts ) );
				
			if( $month_total_edits[$month] != "0" ) {
				$msg .= "</div>";
				$imsg .= "</div>";
			}
				
			$msg .= "</td></tr>";
			$imsg .= "</td></tr>";
		}
	
		$msg .= "</table>";
		$imsg .= "</table>";

		return $msg;
#		if( $this->miPhone === true ) { return $imsg; }
#		else { return $msg; }
	}
	
	function getMonthPopup( $monthtotalsM, $month ) {
		global $wgNamespaces; 
		
		ksort($monthtotalsM);
		$out = '';
		foreach( $monthtotalsM as $ns_id => $count ) {
			$sum = number_format( ( ( $count / array_sum( $monthtotalsM ) ) * 100 ), 2 );
			$out .= $wgNamespaces['names'][$ns_id] . ": $count edits ($sum%) \n";
		}
		return $month."\n".$out;
	
	}
	
	static function GetColorList( $num = false ) {
		$colors = array(
				'0' => '#Cc0000',#'#FF005A', #red '#FF5555',
				'1' => '#F7b7b7',
	
				'2' => '#5c8d20',#'#008800', #green'#55FF55',
				'3' => '#85eD82',
	
				'4' => '#2E97E0', #blue
				'5' => '#B9E3F9',
	
				'6' => '#e1711d',  #orange
				'7' => '#ffc04c',
	
				'#FDFF98', #yellow
	
				'#5555FF',
				'#55FFFF',
	
				'#0000C0',  #
				'#008800',  # green
				'#00C0C0',
				'#FFAFAF',	# rosé
				'#808080',	# gray
				'#00C000',
				'#404040',
				'#C0C000',	# green
				'#C000C0',
				100 => '#75A3D1',	# blue
				101 => '#A679D2',	# purple
				102 => '#660000',
				103 => '#000066',
				104 => '#FAFFAF',	# caramel
					105 => '#408345',
				106 => '#5c8d20',
				107 => '#e1711d',	# red
				108 => '#94ef2b',	# light green
				109 => '#756a4a',	# brown
				110 => '#6f1dab',
				111 => '#301e30',
				112 => '#5c9d96',
				113 => '#a8cd8c',	# earth green
				114 => '#f2b3f1',	# light purple
				115 => '#9b5828',
				118 => '#99FFFF',
				119 => '#99BBFF',
				120 => '#FF99FF',
				121 => '#CCFFFF',
				122 => '#CCFF00',
				123 => '#CCFFCC',
				200 => '#33FF00',
				201 => '#669900',
				202 => '#666666',
				203 => '#999999',
				204 => '#FFFFCC',
				205 => '#FF00CC',
				206 => '#FFFF00',
				207 => '#FFCC00',
				208 => '#FF0000',
				209 => '#FF6600',
				446 => '#06DCFB',
				447 => '#892EE4',
				460 => '#99FF66',
				461 => '#99CC66',	# green
				470 => '#CCCC33',	# ocker
				471 => '#CCFF33',
				480 => '#6699FF',
				481 => '#66FFFF',
				490 => '#995500',
				491 => '#998800',
				710 => '#FFCECE',
				711 => '#FFC8F2',
				828 => '#F7DE00',
				829 => '#BABA21',
				866 => '#FFFFFF',
				867 => '#FFCCFF',
				1198 => '#FF34B3',
				1199 => '#8B1C62',
	
				'#61a9f3',#blue
				'#f381b9',#pink
				'#61E3A9',
	
				'#D56DE2',
				'#85eD82',
				'#F7b7b7',
				'#CFDF49',
				'#88d8f2',
				'#07AF7B',#green
				'#B9E3F9',
				'#FFF3AD',
				'#EF606A',#red
				'#EC8833',
				'#FFF100',
				'#87C9A5',
				'#FFFB11',
				'#005EBC',
				'#9AEB67',
				'#FF4A26',
				'#FDFF98',
				'#6B7EFF',
				'#BCE02E',
				'#E0642E',
				'#E0D62E',
	
				'#02927F',
				'#FF005A',
				'#61a9f3', #blue' #FFFF55',
		);
	
				if( $num === false ) {
			return $colors;
	}
	else{
		return $colors[$num];
	}
	
}
	
	
}



	
	
