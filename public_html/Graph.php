<?php
#define('PATH_JPGRAPH', '/data/project/newwebtest/jpgraph');
#define('PATH_TMP_IMG', '/data/project/newwebtest/xtools/public_html/tmpimg');
#define("TTF_DIR", PATH_JPGRAPH.'/fonts/');
#require_once(PATH_JPGRAPH."/jpgraph.php");
#require_once(PATH_JPGRAPH."/jpgraph_pie.php");
#require_once(PATH_JPGRAPH."/jpgraph_pie3d.php");
class Theme {

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
				"chf" => "bg,s,00000000",
				"chd" => "t:".implode(",", $pctdata),
				"chl" => "", //implode("|", array_keys($data)),
				"chco" => implode("|", $colors)
				
			);
		
		return $chartbase.http_build_query($chdata);
	}

	static function makePie( $data, $title = NULL ){
		$filename = date('YmdHis').rand().".png";
		
		$graph = new PieGraph(800,300,"auto");
#		$graph->SetAntiAliasing(true);
		#$graph->SetShadow();
		#$graph->SetColor('#dde4ee@0.8');
		
		$graph->SetTheme( new XtoolsTheme() );
		$graph->img->SetTransparent("white");
		
		$arrVal = array_values($data);
		$p1 = new PiePlot3D( $arrVal );
		
		$p1->SetLabelType(PIE_VALUE_PERCENTAGE);
		$p1->value->SetFormat('%-.1f %%');
		$p1->SetLabelPos(1);
#		$p1->SetGuideLines();		
		$p1->value->Show();
		
		$p1->ExplodeSlice( array_search(max($arrVal), $arrVal) );
		$p1->SetCenter(0.3,0.5);
		
		$p1->SetLegends( array_keys($data) );
		
		$graph->legend->SetFont(FF_VERDANA, FS_NORMAL, 10 );
		$graph->legend->SetLayout(LEGEND_HOR);
		$graph->legend->SetColumns(1);
		$graph->legend->SetPos(0,0.5,'left','center');
		$graph->legend->Hide(true);
		
		$legend = self::makeLegendTable( $data );
		#$p1->SetAngle(20);
		
#	
		#$graph->title->Set("A simple 3D Pie plot");
		#$graph->title->SetFont(FF_FONT1,FS_BOLD);
		
		$graph->Add($p1);
#		$p1->SetSliceColors( array('#55FFFF','#5544F0') );

		$graph->Stroke(PATH_TMP_IMG.'/'.$filename);
		
		return array( "filename" => $filename, "legend" => $legend);
	}
	

	
	static function makeLegendTable( &$data, &$namespaces ){
#print_r($namespaces);		
		$sum = array_sum( $data );
		$i = 0;
		$legendNS = '<table style="font-size:85%;" >';
		foreach ( $data as $nsid => $count ){
#			$legend[] = "$key: $count · (%-.1f%%) ";
			$color = XtoolsTheme::GetColorList($nsid);
			$legendNS .= '
			<tr>
			<td><span style="display:inline-block; border-radius:2px; height:16px; width:16px; background-color:'.$color.' "></span></td>
			<td>'.$namespaces["names"][$nsid].'</td>
			<td style="text-align:right"> &nbsp; '.$count.'</td>
			<td style="text-align:right"> &nbsp; ('.number_format( ($count/$sum)*100, 1).'%)</td>
			</tr>';
			
			$i++;
		}
		$legendNS .= "</table>";
		
		return $legendNS;
	}
	
	
	static function makeHorizontalBar( $type, $monthTotals, $width = 500 ) {
#print_r($monthTotals);die;	
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
				
			if( $month_total_edits[$month] != "0" ) {
				$msg .= '<td title="'.htmlentities( self::getMonthPopup( $monthTotals[$month], $month ), ENT_QUOTES, 'UTF-8').'" class="date">'.$month.'</td>
						 <td style="text-align:right; padding-right:5px;" >'.$month_total_edits[$month].'</td>';
				$imsg .= '<td class="date" >'.$month.'</td><td>'.$month_total_edits[$month].'</td>';
			}
			else {
				$msg .= '<td class="date" >'.$month.'</td><td>'.$month_total_edits[$month].'</td>\n';
				$imsg .= '<td class="date" >'.$month.'</td><td>'.$month_total_edits[$month].'</td>';
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
				
			$msg .= "</td></tr>\n";
			$imsg .= "</td></tr>\n";
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
				0 => '#FF5555',
				1 => '#55FF55',
				2 => '#FFFF55',
				3 => '#FF55FF',
				4 => '#5555FF',
				5 => '#55FFFF',
				6 => '#C00000',
				7 => '#0000C0',
				8 => '#008800',
				9 => '#00C0C0',
				10 => '#FFAFAF',
				11 => '#808080',
				12 => '#00C000',
				13 => '#404040',
				14 => '#C0C000',
				15 => '#C000C0',
				100 => '#75A3D1',
				101 => '#A679D2',
				102 => '#660000',
				103 => '#000066',
				104 => '#FAFFAF',
				105 => '#408345',
				106 => '#5c8d20',
				107 => '#e1711d',
				108 => '#94ef2b',
				109 => '#756a4a',
				110 => '#6f1dab',
				111 => '#301e30',
				112 => '#5c9d96',
				113 => '#a8cd8c',
				114 => '#f2b3f1',
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
				461 => '#99CC66',
				470 => '#CCCC33',
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
		);
		
		if( $num === false ) {
			return $colors; 
		}
		else{
			return $colors[$num];
		}
// 		return array(
// 				'#FFFB11',
// 				'#005EBC',
// 				'#9AEB67',
// 				'#FF4A26',
// 				'#FDFF98',
// 				'#6B7EFF',
// 				'#BCE02E',
// 				'#E0642E',
// 				'#E0D62E',
// 				'#2E97E0',
// 				'#02927F',
// 				'#FF005A',
// 		);
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
					$plot->ShowBorder(false);
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
	
