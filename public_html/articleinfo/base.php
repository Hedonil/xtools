<?php

class ArticleInfoBase {
	
	public $tmplPageForm;
	public $tmplPageResult;
	
	function __construct(){
		$this->loadPageTemplates();
	}

	public function parseHistory( &$history, $start, $end, &$site, &$pageClass, $api = false ) {
		if( !$api ) {
			$logsi = $site->logs( null, false, $pageClass->get_title(), strtotime( $start ), strtotime( $end ), 'older', false, array( 'type', 'timestamp', 'user', 'details' ) ); 
			$logs = array();
			
			foreach( $logsi as $log ) {
				if( in_array( $log['type'], array( 'delete', 'move', 'protect' ) ) && !in_array( $log['action'], array( 'revision' ) ) ) {
					if( !isset( $logs[date('nY', strtotime( $log['timestamp'] ))][$log['action']] ) ) {
						$logs[date('nY', strtotime( $log['timestamp'] ))][$log['action']] = 0;
					}
					
					$logs[date('nY', strtotime( $log['timestamp'] ))][$log['action']]++;
				}
			}
			
			unset( $logsi );
			
			foreach( $logs as $date => $log ) {
				arsort( $log );
				$logs[$date] = ''; //actionParse( $date, $log );
			}
		}
	
	
	//Now we can start our master array. This one will be HUGE!
		$data = array(
			'first_edit' => array(
				'timestamp' => $history[0]['rev_timestamp'],
				'user' => $history[0]['rev_user_text']
			),
			'year_count' => array(),
			'count' => 0,
			'editors' => array(),
			'anons' => array(),
			'year_count' => array(),
			'minor_count' => 0,
			'count_history' => array( 'today' => 0, 'week' => 0, 'month' => 0, 'year' => 0 )
		);
		
		$first_edit_parse = date_parse( $data['first_edit']['timestamp'] );
	
	
	
	
	//And now comes the logic for filling said master array
		foreach( $history as $id => $rev ) {
			$data['last_edit'] = $rev['rev_timestamp'];
			$data['count']++;
			
			//Sometimes, with old revisions (2001 era), the revisions from 2002 come before 2001
			if( strtotime( $rev['rev_timestamp'] ) < strtotime( $data['first_edit']['timestamp'] ) ) {	
				$data['first_edit'] = array(
					'timestamp' => $rev['rev_timestamp'],
					'user' => htmlspecialchars( $rev['rev_user_text'] )
				);
				
				$first_edit_parse = date_parse( $data['first_edit']['timestamp'] );
			}
			
			
			$timestamp = date_parse( $rev['rev_timestamp'] );
			
			
			//Fill in the blank arrays for the year and 12 months
			if( !isset( $data['year_count'][$timestamp['year']] ) ) {
				$data['year_count'][$timestamp['year']] = array( 'all' => 0, 'minor' => 0, 'anon' => 0, 'months' => array() );
				
				for( $i = 1; $i <= 12; $i++ ) {
					$data['year_count'][$timestamp['year']]['months'][$i] = array( 'all' => 0, 'minor' => 0, 'anon' => 0, 'size' => array() );
				}
			}
			
			//Increment counts
			$data['year_count'][$timestamp['year']]['all']++;
			$data['year_count'][$timestamp['year']]['months'][$timestamp['month']]['all']++;
			$data['year_count'][$timestamp['year']]['months'][$timestamp['month']]['size'][] = number_format( ( $rev['rev_len'] / 1024 ), 2 );
			
			
			//Now to fill in various user stats
			$username = htmlspecialchars($rev['rev_user_text']);
			if( !isset( $data['editors'][$username] ) ) {
				$data['editors'][$username] = array( 	
					'all' => 0, 
					'minor' => 0, 
					'first' => date( 'd F Y, H:i:s', strtotime( $rev['rev_timestamp'] ) ), 
					'last' => null, 
					'atbe' => null, 
					'minorpct' => 0, 
					'size' => array(), 
					'urlencoded' => str_replace( array( '+' ), array( '_' ), urlencode( $rev['rev_user_text'] ) )
				);
			}
			
			//Increment these counts...
			$data['editors'][$username]['all']++;	
			$data['editors'][$username]['last'] = date( 'd F Y, H:i:s', strtotime( $rev['rev_timestamp'] ) );	
			$data['editors'][$username]['size'][] = number_format( ( $rev['rev_len'] / 1024 ), 2 );
			
			if( !$rev['rev_user'] ) {
				//Anonymous, increase counts
				$data['anons'][] = $username;
				$data['year_count'][$timestamp['year']]['anon']++;
				$data['year_count'][$timestamp['year']]['months'][$timestamp['month']]['anon']++;
			}
			
			if( $rev['rev_minor_edit'] ) {
				//Logged in, increase counts
				$data['minor_count']++;
				$data['year_count'][$timestamp['year']]['minor']++;
				$data['year_count'][$timestamp['year']]['months'][$timestamp['month']]['minor']++;
				$data['editors'][$username]['minor']++;	
			}
			
			
			//Increment "edits per <time>" counts
			if( strtotime( $rev['rev_timestamp'] ) > strtotime( '-1 day' ) ) $data['count_history']['today']++;
			if( strtotime( $rev['rev_timestamp'] ) > strtotime( '-1 week' ) ) $data['count_history']['week']++;
			if( strtotime( $rev['rev_timestamp'] ) > strtotime( '-1 month' ) ) $data['count_history']['month']++;
			if( strtotime( $rev['rev_timestamp'] ) > strtotime( '-1 year' ) ) $data['count_history']['year']++;
			
		}
	
	
	//Fill in years with no edits
		for( $year = $first_edit_parse['year']; $year <= date( 'Y' ); $year++ ) {
			if( !isset( $data['year_count'][$year] ) ) {
				$data['year_count'][$year] = array( 'all' => 0, 'minor' => 0, 'anon' => 0, 'months' => array() );
				
				for( $i = 1; $i <= 12; $i++ ) {
					$data['year_count'][$year]['months'][$i] = array( 'all' => 0, 'minor' => 0, 'anon' => 0, 'size' => array() );
				}
			}
		}
	
	
	//Add more general statistics
		$data['totaldays'] = floor( ( strtotime( $data['last_edit'] ) - strtotime( $data['first_edit']['timestamp'] ) ) / 60 / 60 / 24 );
		$data['average_days_per_edit'] = number_format( $data['totaldays'] / $data['count'], 2 );
		$data['edits_per_month'] = ( $data['totaldays'] ) ? number_format( $data['count'] / ( $data['totaldays'] / ( 365/12 ) ), 2 ) : 0;
		$data['edits_per_year'] =( $data['totaldays'] ) ? number_format( $data['count'] / ( $data['totaldays'] / 365 ) , 2 ) : 0;
		$data['edits_per_editor'] = number_format( $data['count'] / count( $data['editors'] ) , 2 );
		$data['editor_count'] = count( $data['editors'] );
		$data['anon_count'] = count( $data['anons'] );
	
	
	//Various sorts
		arsort( $data['editors'] );
		ksort( $data['year_count'] );
	
	
	
	//Fix the year counts
		$num = 0;
		$cum = 0;
		$scum = 0;
		
		foreach( $data['year_count'] as $year => $months ) {
			
			//Unset months before the first edit and after the last edit
			foreach( $months['months'] as $month => $tmp ) {
				if( $year == $first_edit_parse['year'] ) {
					if( $month < $first_edit_parse['month'] ) unset( $data['year_count'][$year]['months'][$month] );
				}
				if( $year == date( 'Y' ) ) {
					if( $month > date( 'm' ) ) unset( $data['year_count'][$year]['months'][$month] );
				}
			}
			
			
			//Calculate anon/minor percentages
			$data['year_count'][$year]['pcts']['anon'] = ( $data['year_count'][$year]['all'] ) ? number_format( ( $data['year_count'][$year]['anon'] / $data['year_count'][$year]['all'] ) * 100, 2 ) : 0.00;
			$data['year_count'][$year]['pcts']['minor'] = ( $data['year_count'][$year]['all'] ) ? number_format( ( $data['year_count'][$year]['minor'] / $data['year_count'][$year]['all'] ) * 100, 2 ) : 0.00;
			
			
			//Continue with more stats...
			foreach( $data['year_count'][$year]['months'] as $month => $tmp ) {
			
				//More percentages...
				$data['year_count'][$year]['months'][$month]['pcts']['anon'] = ( $tmp['all'] ) ? number_format( ( $tmp['anon'] / $tmp['all'] ) * 100, 2 ) : 0.00;
				$data['year_count'][$year]['months'][$month]['pcts']['minor'] = ( $tmp['all'] ) ? number_format( ( $tmp['minor'] / $tmp['all'] ) * 100, 2 ): 0.00;
				
				//XID and cumulative are used in the flash graph
				$data['year_count'][$year]['months'][$month]['xid'] = $num;
				$data['year_count'][$year]['months'][$month]['cumulative'] = $cum + $tmp['all'];
				
				if( count( $tmp['size'] ) ) {
					$data['year_count'][$year]['months'][$month]['size'] = number_format( ( array_sum( $tmp['size'] ) / count( $tmp['size'] ) ), 2 );
				}
				else {
					$data['year_count'][$year]['months'][$month]['size'] = 0;
				}
				
				$data['year_count'][$year]['months'][$month]['sizecumulative'] = $scum + $data['year_count'][$year]['months'][$month]['size'];
				$num++;
				$cum += $tmp['all'];
				$scum += $data['year_count'][$year]['months'][$month]['size'];
			}
		}
	
	
	//Top 10% info
		$data['top_ten'] = array( 'editors' => array(), 'count' => 0 );
		$data['top_fifty'] = array();
	
	
	//Now to fix the user info...
		$tmp = $tmp2 = 0;
		foreach( $data['editors'] as $editor => $info ) {
			
			//Is the user in the top 10%?
			if( $tmp <= (int)( count( $data['editors'] ) * 0.1 ) ) {
				$data['top_ten']['editors'][] = $editor;
				$data['top_ten']['count'] += $info['all'];
				
				$tmp++;
			}
			
			//Is the user in the 50 highest editors?
			if( $tmp < 50 ) {
				$data['top_fifty'][] = $editor;
			}
			
			$data['editors'][$editor]['minorpct'] = ( $info['all'] ) ? number_format( ( $info['minor'] / $info['all'] ) * 100, 2 ): 0.00;
			
			if( $info['all'] > 1 ) {
				$data['editors'][$editor]['atbe'] = WebTool::getTimeString( (int)( ( strtotime( $info['last'] ) - strtotime( $info['first'] ) ) / $info['all'] ));
			}
			
			if( count( $info['size'] ) ) {
				$data['editors'][$editor]['size'] = number_format( ( array_sum( $info['size'] ) / count( $info['size'] ) ), 2 );
			}
			else {
				$data['editors'][$editor]['size'] = 0;
			}
			
			$tmp2++;
		}
		
		return $data;
	}
	
	
	/**
	 * Generate the log actions infobox for the flash graph 
	 * @param unknown $date
	 * @param unknown $logs
	 * @return string
	 */
	function actionParse( $date, $logs ) {
		global $content;
	
		if( strlen( $date ) == 5 ) {
			$parseddate = '0' . substr( $date, 0, 1 ) . '/' . substr( $date, 1 );
		}
		else {
			$parseddate = substr( $date, 0, 2 ) . '/' . substr( $date, 2 );
		}
	
		$ret = $content->get_config_vars( 'duringdate', $parseddate );
	
		$ret .= "<ul>";
	
		foreach( $logs as $type => $count ) {
			switch( $type ) {
				case 'modify':
					$ret .= "<li>" . $content->get_config_vars( 'linegraphmodify', $count ) . "</li>";
					break;
				case 'protect':
					$ret .= "<li>" . $content->get_config_vars( 'linegraphprotect', $count ) . "</li>";
					break;
				case 'unprotect':
					$ret .= "<li>" . $content->get_config_vars( 'linegraphunprotect', $count ) . "</li>";
					break;
				case 'move':
					$ret .= "<li>" . $content->get_config_vars( 'linegraphmove' . $type, $count ) . "</li>";
					break;
				case 'move_redir':
					$ret .= "<li>" . $content->get_config_vars( 'linegraphmoveredir' . $type, $count ) . "</li>";
					break;
				case 'move_prot':
					$ret .= "<li>" . $content->get_config_vars( 'linegraphmoveprot' . $type, $count ) . "</li>";
					break;
				case 'delete':
					$ret .= "<li>" . $content->get_config_vars( 'linegraphdelete' . $type, $count ) . "</li>";
					break;
				case 'restore':
					$ret .= "<li>" . $content->get_config_vars( 'linegraphundelete' . $type, $count ) . "</li>";
					break;
				default:
					break;
			}
		}
	
		$ret .= "</ul>";
	
		return htmlentities( $ret );
	
	}
	
	
	public function getVars( $pageClass, $site, $followredir, $begin, $endvar ) {
		global $dbr;
		
		$conds = array( 'rev_page = ' . $dbr->strencode( $pageClass->get_id() ) );
		$start = $end = false;
		
		if( $begin ) {
			$conds[] = 'UNIX_TIMESTAMP(rev_timestamp) > ' . $dbr->strencode( strtotime( $begin ) );
			$start = $begin;
		}
		if( $endvar ) {
			$conds[] = 'UNIX_TIMESTAMP(rev_timestamp) < ' . $dbr->strencode( strtotime( $end ) );
			$end = $endvar;
		}
		
		try {
			$history = $dbr->select( 
				array( 'revision_userindex' ),
				array( 'rev_user_text', 'rev_user', 'rev_timestamp', 'rev_comment', 'rev_minor_edit', 'rev_len' ),
				$conds,
				array( 'LIMIT' => 50000 )
			);
		} catch( Exception $e ) {
			return array( 'error' => 'dberror', 'info' => $e->getMessage() );
		}
		
		return $history;
	}
	
	/**
	 * Calculate how many pixels each year should get for the Edits per Year table
	 * @param unknown $data
	 * @return Ambigous <multitype:multitype: , number>
	 */
	public function getYearPixels( &$data ) {
		$month_total_edits = array();

		foreach( $data as $year => $tmp ) {
			$month_total_edits[$year] = $tmp['all'];
		}
	
		$max_width = max( $month_total_edits );
	
		$pixels = array();
		foreach( $data as $year => $tmp ) {
			if( $tmp['all'] == 0 ) $pixels[$year] = array();
				
			$processarray = array( 'all' => $tmp['all'], 'anon' => $tmp['anon'], 'minor' => $tmp['minor'] );
				
			asort( $processarray );
				
			foreach( $processarray as $type => $count ) {
				$newtmp = ceil( 500 * ( $count ) / $max_width );
				$pixels[$year][$type] = $newtmp;
			}
		}
	
		return $pixels;
	}
	
	
	/**
	 * Calculate how many pixels each month should get for the Edits per Month table
	 * @param unknown $data
	 * @return multitype:
	 */
	function getMonthPixels( &$data ) {
		
		$month_total_edits = array();
		
		foreach( $data as $year => $tmp ) {
			foreach( $tmp['months'] as $month => $newdata ) {
				$month_total_edits[ $month.'/'.$year ] = $newdata['all'];
			}
		}
	
		$max_width = max( $month_total_edits );
	
		$pixels = array();
		foreach( $data as $year => $tmp ) {
			foreach( $tmp['months'] as $month => $newdata ) {
				if( $tmp['all'] == 0 ) $pixels[$year][$month] = array();
	
				$processarray = array( 'all' => $newdata['all'], 'anon' => $newdata['anon'], 'minor' => $newdata['minor'] );
	
				asort( $processarray );
	
				foreach( $processarray as $type => $count ) {
					$newtmp = ceil( ( 500 * ( $count ) / $max_width ) );
					$pixels[$year][$month][$type] = $newtmp;
				}
			}
		}
	
		return $pixels;
	}
	

	/**
	 * Returns a list of even years, used to generate contrasting colors for the Edits/Month table 
	 * @param unknown $years
	 * @return Ambigous <string, multitype:>
	 */
	public function getEvenYears( $years ) {
		$years = array_flip( $years );
		foreach( $years as $year => $id ) {
			$years[$year] = "5";
			if( $year % 2 == 0 ) unset( $years[$year] );
		}
		return $years;
	}

	
private function loadPageTemplates(){
	
$this->tmplPageForm = '
	<script type="text/javascript">
		function switchShow( id ) {
			if(document.getElementById(id).style.display == "none") {
				document.getElementById(id).style.display = "block";
			}
			else{
				document.getElementById(id).style.display = "none";
			}
		}
	</script>
	
	<br />
	<form action="?" method="get" accept-charset="utf-8">
	<table>
		<tr><td>{#article#}: </td><td><input type="text" name="article" /> <input type="checkbox" name="nofollowredir" /> {#nofollowredir#}</td></tr>
		<tr><td>{#wiki#}: </td><td><input type="text" value="{$lang}" name="lang" size="9" />.<input type="text" value="{$wiki}" size="10" name="wiki" />.org</td></tr>
		<tr><td>{#start#}: </td><td><input type="text" name="begin" /></td></tr>
		<tr><td>{#end#}: </td><td><input type="text" name="end" /></td></tr>
		<tr><td colspan="2"><input type="submit" value="{#submit#}" /></td></tr>
	</table>
	</form><br />
';

$this->tmplPageResult = '
	<script type="text/javascript">
	function switchShow( id ) {
		if(document.getElementById(id).style.display == "none") {
			document.getElementById(id).style.display = "block";
		}
		else{
			document.getElementById(id).style.display = "none";
		}
	}
	</script>
	
	<span>{#added#}</span>
	<ul>
	{foreach from=$revs key=id item=i}
	{$i}
	{/foreach}	
	</ul>

	{if $info != ""}
	<h3>{#generalstats#} &nbsp;&nbsp;<span style="font-size:75%;">[<a href="javascript:switchShow(\'generalstats\')">show/hide</a>]</span></h3>
	<div id = "generalstats">
	<table>
		<tr><td>{#page#}:</td><td><a href="//{$url}/wiki/{$urlencodedpage}">{$page}</a></td></tr>
		<tr><td>{#totaledits#}:</td><td>{$totaledits}</td></tr>
		<tr><td>{#minoredits#}:</td><td>{$minoredits} ({$minorpct}%)</td></tr>
		<tr><td>{#anonedits#}:</td><td>{$anonedits} ({$anonpct}%)</td></tr>
		<tr><td>{#firstedit#}:</td><td>{$firstedit} ({#by#} {$firstuser})</td></tr>
		<tr><td>{#lastedit#}:</td><td>{$lastedit}</td></tr>
		<tr><td>{#timebwedits#}:</td><td>{$timebwedits} {#days#}</td></tr>
		<tr><td>{#editspermonth#}:</td><td>{$editspermonth}</td></tr>
		<tr><td>{#editsperyear#}:</td><td>{$editsperyear}</td></tr>
		<tr><td>{#lastday#}:</td><td>{$lastday}</td></tr>
		<tr><td>{#lastweek#}:</td><td>{$lastweek}</td></tr>
		<tr><td>{#lastmonth#}:</td><td>{$lastmonth}</td></tr>
		<tr><td>{#lastyear#}:</td><td>{$lastyear}</td></tr>
		<tr><td>{#editorcount#}:</td><td>{$editorcount}</td></tr>
		<tr><td>{#editsperuser#}:</td><td>{$editsperuser}</td></tr>
		<tr><td>{#toptencount#}:&nbsp;&nbsp;&nbsp;</td><td>{$toptencount} ({$toptenpct}%)</td></tr>
	</table>


	<table>
		<tr>
		<td><img src="//chart.googleapis.com/chart?cht=p3&amp;chd=t:{$graphuserpct},{$graphanonpct}&amp;chs=250x100&amp;chdl={#users#}%20%28{$graphuserpct}%%29|{#ips#}%20%28{$graphanonpct}%%29&amp;chco=FF5555|55FF55&amp;chf=bg,s,00000000" alt="{#anonalt#}" /></td>
		<td><img src="//chart.googleapis.com/chart?cht=p3&amp;chd=t:{$graphminorpct},{$graphmajorpct}&amp;chs=250x100&amp;chdl={#minor#}%20%28{$graphminorpct}%%29|{#major#}%20%28{$graphmajorpct}%%29&amp;chco=FFFF55|FF55FF&amp;chf=bg,s,00000000" alt="{#minoralt#}" /></td>
		<td><img src="//chart.googleapis.com/chart?cht=p3&amp;chd=t:{$graphtoptenpct},{$graphbottomninetypct}&amp;chs=280x100&amp;chdl={#topten#}%20%28{$graphtoptenpct}%%29|{#bottomninety#}%20%28{$graphbottomninetypct}%%29&amp;chco=5555FF|55FFFF&amp;chf=bg,s,00000000" alt="{#toptenalt#}" /></td>
		</tr>
	</table>
	</div>


	<h3>{#yearcounts#} &nbsp;&nbsp;<span style="font-size:75%;">[<a href="javascript:switchShow( \'yearcounts\' )">show/hide</a>]</span></h3>
	<div id="yearcounts">
	<table class="months wikitable">
		{$yearcountlist}	
	</table>
	</div>


	<h3>{#linegraph#} &nbsp;&nbsp;<span style="font-size:75%;">[<a href="javascript:switchShow( \'linegraph\' )">show/hide</a>]</span></h3>
	<div id="linegraph">
	<script type="text/javascript" src="//tools.wmflabs.org/xtools/articleinfo/amline/amline/swfobject.js"></script>
	<div id="flashcontent">
		<strong>{#upgrade#}</strong>
	</div>
	
	<script type="text/javascript">
		// <![CDATA[		
		var so = new SWFObject("//tools.wmflabs.org/xtools/articleinfo/amline/amline/amline.swf", "amline1", "760", "500", "8", "#D0E4EE");
		so.addVariable("path", "//tools.wmflabs.org/xtools/articleinfo/amline/amline/");
		{*so.addVariable("chart_data", encodeURIComponent("{$linegraphdata}"));*}
		so.addVariable("data_file", escape("//tools.wmflabs.org/xtools/articleinfo/data/{$linegraphdata}.xml"));
		so.addVariable("settings_file", escape("//tools.wmflabs.org/xtools/articleinfo/amline/amline/amline_settings_w.xml"));
	
		so.write("flashcontent");
		// ]]>
	</script>
	</div>
		

	<!-- monthgraph -->
	<h3>{#monthcounts#} &nbsp;&nbsp;<span style="font-size:75%;">[<a href="javascript:switchShow( \'monthcounts\' )">show/hide</a>]</span></h3>
	<div id="monthcounts">
	<table class="months wikitable">
		{$monthcountlist}
	</table>
	</div>


	{if $sizegraph != ""}
	<h3>{#sizegraph#} &nbsp;&nbsp;<span style="font-size:75%;">[<a href="javascript:switchShow( \'sizegraph\' )">show/hide</a>]</span></h3>
	<div id="sizegraph">
	<script type="text/javascript" src="//tools.wmflabs.org/xtools/articleinfo/amline/amline/swfobject.js"></script>
	<div id="flashcontent2">
		<strong>{#upgrade#}</strong>
	</div>
	
	<script type="text/javascript">
		// <![CDATA[		
		var so = new SWFObject("//tools.wmflabs.org/xtools/articleinfo/amline/amline/amline.swf", "amline2", "760", "500", "8", "#D0E4EE");
		so.addVariable("path", "//tools.wmflabs.org/xtools/articleinfo/amline/amline/");
		so.addVariable("data_file", escape("//tools.wmflabs.org/xtools/articleinfo/data/{$sizegraphdata}.xml"));
		so.addVariable("settings_file", escape("//tools.wmflabs.org/xtools/articleinfo/amline/amline/samline_settings_w.xml"));
	
		so.write("flashcontent2");
		// ]]>
	</script>
	</div>

	<!-- $usertable -->
	<h3>{#usertable#} &nbsp;&nbsp;<span style="font-size:75%;">[<a href="javascript:switchShow( \'usertable\' )">show/hide</a>]</span></h3>
	<div id="usertable">
	<table class="months wikitable">
		<tr>
			<th>{#user#}</th>
			<th>{#count#}</th>
			<th>{#minor#}</th>
			<th>{#firstedit#}</th>
			<th>{#lastedit#}</th>
			<th>{#atbe#}</th>
			<th>{#avgsize#}</th>
		</tr>
		{$usertable}
	</table>
	</div>
		
';
}

}