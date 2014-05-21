<?php
define('PATH_JPGRAPH', '/data/project/newwebtest/jpgraph');
define('PATH_TMP_IMG', '/data/project/newwebtest/xtools/public_html/tmp');
define("TTF_DIR", PATH_JPGRAPH.'/fonts/');

#require_once(PATH_JPGRAPH."/jpgraph.php");
#require_once(PATH_JPGRAPH."/jpgraph_pie.php");
#require_once(PATH_JPGRAPH.'/jpgraph_line.php');
#require_once(PATH_JPGRAPH."/jpgraph_pie3d.php");

class Theme {
}
if(!function_exists('imageantialias'))
{
	function imageantialias($image, $enabled)
	{
		return false;
	}
}


class xGraph{
	
	static function makePieGoogle( $data, $title = NULL ){
	
		$ff = array_values($data);
		$sum = array_sum( $ff );
		foreach( array_values($data) as $value ){
			$pctdata[] = number_format( ($value / $sum)*100 , 1);
		}
		
		foreach( array_keys($data) as $nsid ){
			$colors[] = str_replace("#", "", XtoolsTheme::GetColorList( $nsid ));
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
	static function makePieGoogleTopEditors( $total, &$data ){
#print_r($data);		
		$i =0;
		foreach ($data as $user => $details){
			$val = number_format( ($details["all"] / $total)*100,1);
			$users[] = $user."  ($val%)";
			$values[] = $val;
			$colors[] = str_replace("#", "", XtoolsTheme::GetColorList( $i ));
			$i++;
			if ($i == 9) break;
		}
		$users[] = "others";
		$colors[] = str_replace("#", "", XtoolsTheme::GetColorList( 100 ));
		$values[] = 100-array_sum($values);
		
		$chartbase = "//chart.googleapis.com/chart?";
		$chdata = array(
				"cht" => "p",
				"chs" => "600x300",
				"chf" => "bg,s,00000000",
				"chp" => '-1.55',
				"chd" => "t:".implode(",", $values),
				"chdl" => implode("|", $users),
				"chdls" => '737373,13',
				'chdlp'=> 'r|l',
				"chco" => implode("|", $colors)
		
		);
		
		return $chartbase.http_build_query($chdata);
		#return self::makePieTest();
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
		
		$ff = XtoolsTheme::GetColorList();
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
	
	static function makeArticleChartGoogle( $type, $data ){
#print_r($data);

#		$numyears = count($data);
#		$grapwidth = ($numyears * 45)+250;
		
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
				'chco' => '4D89F9,55ff55,ff00ff,737373',
				'chd' => 't3:'.implode(',', $all).'|'.implode(',', $anon).'|'.implode(',', $minor).'|'.implode(',', $size),
				'chdl' => 'All|IP|Minor|Article size',
				'chdlp'=> 'r|l',
				'chds' => 'a',
				'chbh' => '10,1,15',
				'chxt' => 'y,y,x,r,r',
				'chxl' => '1:||Edits||2:|'.implode('|', $years).'|4:||Size (kb)|',
				'chxr' => '0,0,'.$maxeditTotal.'|3,0,'.$maxsizeTotal,

				'chm' => 'D,737373,3,0,1,1',
		);
		
		return $chartbase.http_build_query($chdata);
	}

	static function makePie( $data, $title = NULL ){
		$filename = date('YmdHis').rand().".png";
		
		$arrVal = array_values($data);
		foreach ( $data as $nsid => $value){
			$arrColor[] = XtoolsTheme::GetColorList($nsid);
		}
		
		$graph = new PieGraph(300,280,"auto");
		$graph->SetAntiAliasing(true);
#		$graph->SetClipping();
		
		$graph->SetTheme( new XtoolsTheme() );
		$graph->img->SetTransparent("white");
		
		
		$p1 = new PiePlot( $arrVal );
		$p1->ShowBorder(false, true);
		
		$p1->SetLabelType(PIE_VALUE_PERCENTAGE);
		$p1->value->SetFormat('%-.1f %%');
		$p1->SetLabelPos(1);
#		$p1->SetGuideLines();		
		$p1->value->Show();
		
		
		$p1->ExplodeSlice( array_search(max($arrVal), $arrVal) );
		$p1->SetCenter(0.5,0.5);
		
		$p1->SetLegends( array_keys($data) );
		
		$graph->legend->SetFont(FF_VERDANA, FS_NORMAL, 10 );
		$graph->legend->SetLayout(LEGEND_VERT);
		$graph->legend->SetColumns(1);
		$graph->legend->SetPos(0,0.5,'left','center');
		$graph->legend->Hide(true);
		#$p1->SetAngle(20);
		
#	
		#$graph->title->Set("A simple 3D Pie plot");
		#$graph->title->SetFont(FF_FONT1,FS_BOLD);
		$graph->Add($p1);
#		$p1->SetSliceColors( $arrColor );
		
		$graph->Stroke(PATH_TMP_IMG.'/'.$filename);
		
		return $filename;
	}
	
	static function makeLegendTable( &$data, &$namespaces ){
		global $wt;
		
		$sum = array_sum( $data );
		$i = 0;
		$legendNS = '<table style="font-size:85%;" >';
		foreach ( $data as $nsid => $count ){

			$color = XtoolsTheme::GetColorList($nsid);
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
	
	static function makeArticleChart( $type, $data ){
		$filename = date('YmdHis').rand().".png";
		
		$datay1 = array(20,15,23,15);
		$datay2 = array(12,9,42,8);
		$datay3 = array(5,17,32,24);
		
		// Setup the graph
		$graph = new Graph(300,250);
		$graph->img->SetAntiAliasing(false);
		$graph->SetScale("textlin");
		
		$theme_class=new XtoolsTheme();
		$graph->SetTheme($theme_class);
		
		$graph->title->Set('Filled Y-grid');
		$graph->SetBox(false);
		
		$graph->img->SetAntiAliasing();
		
		$graph->yaxis->HideZeroLabel();
		$graph->yaxis->HideLine(false);
		$graph->yaxis->HideTicks(false,false);
		
		$graph->xgrid->Show();
		$graph->xgrid->SetLineStyle("solid");
		$graph->xaxis->SetTickLabels(array('A','B','C','D'));
		$graph->xgrid->SetColor('#E3E3E3');
		
		// Create the first line
		$p1 = new LinePlot($datay1);
		$graph->Add($p1);
		$p1->SetColor("#6495ED");
		$p1->SetLegend('Line 1');
		
		// Create the second line
		$p2 = new LinePlot($datay2);
		$graph->Add($p2);
		$p2->SetColor("#B22222");
		$p2->SetLegend('Line 2');
		
		// Create the third line
		$p3 = new LinePlot($datay3);
		$graph->Add($p3);
		$p3->SetColor("#FF1493");
		$p3->SetLegend('Line 3');
		
		$graph->legend->SetFrameWeight(1);
		
		// Output line
		$graph->Stroke(PATH_TMP_IMG.'/'.$filename);
		
		return $filename;
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
				$msg .= '<div class="bar" style="border-left:' . $pixel . 'px solid ' . XtoolsTheme::GetColorList($namespace_id) . '" >';
				$imsg .= '<div class="bar" style="border-left:' . $pixel . 'px solid ' . XtoolsTheme::GetColorList($namespace_id) . '" >';
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
	
}



/**
 * Xtools Theme class for jpgraph
 */
class XtoolsTheme extends Theme
{
	private $font_color       = '#0044CC';
	private $background_color = '#DDFFFF';
	private $axis_color       = '#0066CC';
	private $grid_color       = '#3366CC';

	function GetColorList( $num = false ) {
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

	function SetupGraph($graph) {

		// graph
		/*
		$img = $graph->img;
		$height = $img->height;
		$graph->SetMargin($img->left_margin, $img->right_margin, $img->top_margin, $height * 0.25);
		*/
		$graph->SetFrame(false);
		$graph->SetMarginColor('white');
		$graph->SetBackgroundGradient($this->background_color, '#FFFFFF', GRAD_HOR, BGRAD_PLOT);
		
		// legend
		$graph->legend->SetFrameWeight(0);
		$graph->legend->Pos(0.5, 0.85, 'center', 'top');
		$graph->legend->SetFillColor('white');
		
		$graph->legend->SetLayout(LEGEND_HOR);
		$graph->legend->SetColumns(3);
		$graph->legend->SetShadow(false);
		$graph->legend->SetMarkAbsSize(5);
		
		// xaxis
		$graph->xaxis->title->SetColor($this->font_color);
		$graph->xaxis->SetColor($this->axis_color, $this->font_color);
		$graph->xaxis->SetTickSide(SIDE_BOTTOM);
		$graph->xaxis->SetLabelMargin(10);
		
		// yaxis
		$graph->yaxis->title->SetColor($this->font_color);
		$graph->yaxis->SetColor($this->axis_color, $this->font_color);
		$graph->yaxis->SetTickSide(SIDE_LEFT);
		$graph->yaxis->SetLabelMargin(8);
		$graph->yaxis->HideLine();
		$graph->yaxis->HideTicks();
		$graph->xaxis->SetTitleMargin(15);
		
		// grid
		$graph->ygrid->SetColor($this->grid_color);
		$graph->ygrid->SetLineStyle('dotted');
		
		
		// font
		$graph->title->SetColor($this->font_color);
		$graph->subtitle->SetColor($this->font_color);
		$graph->subsubtitle->SetColor($this->font_color);


		//$graph->img->SetAntiAliasing();
	}


	function SetupPieGraph($graph) {
		// graph
		$graph->SetFrame(false);
		
		// legend
		$graph->legend->SetFillColor('white');
		
		$graph->legend->SetFrameWeight(0);
		$graph->legend->Pos(0.5, 0.80, 'center', 'top');
		$graph->legend->SetLayout(LEGEND_HOR);
		$graph->legend->SetColumns(4);
		
		$graph->legend->SetShadow(false);
		$graph->legend->SetMarkAbsSize(5);
		
		// title
		$graph->title->SetColor($this->font_color);
		$graph->subtitle->SetColor($this->font_color);
		$graph->subsubtitle->SetColor($this->font_color);
		
		$graph->SetAntiAliasing();
	}
	
	function PreStrokeApply($graph) {
		if ($graph->legend->HasItems()) {
			$img = $graph->img;
			$height = $img->height;
			$graph->SetMargin($img->left_margin, $img->right_margin, $img->top_margin, $height * 0.25);
		}
	}
	
	function ApplyPlot($plot) {
	
		switch (get_class($plot))
		{
			case 'GroupBarPlot':
				{
					foreach ($plot->plots as $_plot) {
						$this->ApplyPlot($_plot);
					}
					break;
				}
	
			case 'AccBarPlot':
				{
					foreach ($plot->plots as $_plot) {
						$this->ApplyPlot($_plot);
					}
					break;
				}
	
			case 'BarPlot':
				{
					$plot->Clear();
	
					$color = $this->GetNextColor();
					$plot->SetColor($color);
					$plot->SetFillColor($color);
					$plot->SetShadow('red', 3, 4, false);
					break;
				}
	
			case 'LinePlot':
				{
					$plot->Clear();
					$plot->SetColor($this->GetNextColor().'@0.4');
					$plot->SetWeight(2);
					//                $plot->SetBarCenter();
					break;
				}
	
			case 'PiePlot':
				{
					$plot->SetCenter(0.5, 0.45);
					#$plot->ShowBorder(false);
					$plot->SetSliceColors($this->GetThemeColors());
					break;
				}
	
			case 'PiePlot3D':
				{
					$plot->SetSliceColors($this->GetThemeColors());
					break;
				}
	
			default:
				{
				}
		}
	}
}
	
