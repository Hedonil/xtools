<?php

//Requires
	require_once( '../WebTool.php' );
	require_once( 'base.php' );

//Load WebTool class
	$wt = new WebTool( 'Pages', 'pages', array("smarty", "sitenotice", "replag") );
	WebTool::setMemLimit();
	$base = new PagesBase();
	$wt->content = $base->getPageForm();
	
//Show form if &article parameter is not set (or empty)
	if( !$wt->webRequest->getSafeVal( 'getBool', 'user' ) ) {
		$wt->showPage($wt);
	}
	
//Get username & userid, quit if not exist
	$userData = $base->getUserData( $dbr, $wt->webRequest->getSafeVal('user') );
	if( !$userData ) { 
		$wt->error = $I18N->msg("No such user");
		$wt->showPage($wt);
	}

//Execute main logic	
	$result = $base->getCreatedPages( 
				$dbr, 
				$userData["user_id"], 
				$wgRequest->getSafeVal('lang'), 
				$wgRequest->getSafeVal('wiki'),
				$wgRequest->getSafeVal('namespace'),
				$wgRequest->getSafeVal('redirects')
			 );	

//Construct output
	$filtertextNS = ( $result->filterns == "all" ) ? " in all namespaces." : " in namespace ".$wgRequest->getSafeVal('namespace').".";
	$totalcreated = "User ".$userData["user_name"]." has created  $result->total  pages on ".$wgRequest->getSafeVal('lang').".".$wgRequest->getSafeVal('wiki').$filtertextNS ;

	$wt->content = '	
	<span>'.$totalcreated.'&nbsp;(Redirect filter: '.$result->filterredir.')</span>
	<table>
		<tr>
		<td>
		<table style="margin-top: 10px" >
			<tr>
				<th>NS</th>
				<th>NS name</th>
				<th>Pages</th>
				<th style="padding_left:5px">&nbsp;&nbsp;(Redirects)</th>
			</tr>
			'.$result->listnamespaces.'
		</table>
		</td>
		<td><img src="//chart.googleapis.com/chart?cht=p3&amp;chd=t:'.$result->listnum.'&amp;chs=550x140&amp;chl='.$result->listns.'&amp;chco=599ad3|f1595f|79c36a|f9a65a|727272|9e66ab|cd7058|ff0000|00ff00&amp;chf=bg,s,00000000" alt="minor" /></td>
		</tr>
	</table>
	
	<table class="sortable" >
	'.$result->list.'
	</table>
	';		

unset( $base, $result);
$wt->showPage($wt);