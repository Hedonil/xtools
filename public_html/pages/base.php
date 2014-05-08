<?php 

class PagesBase{
	
	public $tmplPageForm;
	public $tmplPageResult;
	
	function __construct(){
		$this->loadPageTemplates();
	}
	
	public function getUserData( $dbr, $username ){
		$query = "
			SELECT user_name, user_id 
			FROM user 
			WHERE user_name = '$username';
		";
		
		$result = $dbr->query( $query );
		$userdata = $result[0];
		
		return $userdata;
	}
	

	public function getCreatedPages( $dbr, $user_id, $lang, $wiki, $namespace, $redirects ){
		
		$namespaceCondition = ($namespace == "all") ? "" : " and page_namespace = '".intval($namespace)."' ";
		$redirectCondition = "";
		if ( $redirects == "onlyredirects" ){ $redirectCondition = " and page_is_redirect = '1' "; }
		if ( $redirects == "noredirects" ){ $redirectCondition = " and page_is_redirect = '0' "; }
		
		$query = "
			SELECT DISTINCT page_namespace, page_title, page_is_redirect, page_id, UNIX_TIMESTAMP(rev_timestamp) as timestamp
			FROM page
			JOIN revision_userindex on page_id = rev_page
			WHERE rev_user = '$user_id' AND rev_parent_id = '0'  $namespaceCondition  $redirectCondition
			ORDER BY page_namespace ASC, rev_timestamp DESC;
		";
		
		$items = $dbr->query( $query );
		$items = $items->endArray;
		
		$nsnames = self::getNamespaceNames( $lang, $wiki );
		
		$result = new stdClass(
				$filter 	 = null,
				$namespaces  = null,
				$list 		 = null
			);
		$currentNamespace = "";
		$currentNumber = 0;

		foreach ( $items as $i => $item ){
			$pageurl  = urlencode( $item["page_title"] );
			$page 	  = str_replace("_", " ", $item["page_title"]);
			$date 	  = date("Y-m-d", $item["timestamp"]);
			$ns 	  = $item["page_namespace"];
			$prefix   = ( $nsnames[$ns] != "Mainspace" ) ? $nsnames[$ns].":" : ""; 
			$redirect = ( $item["page_is_redirect"] == 1 ) ? "(redirect)" : "";
			

			//create a new header if namespace changes
			if( $ns != $currentNamespace){
				
				$result->list .= "<tr ><td colspan=4 ><h3 id=$ns >".$nsnames[$ns]."</h3></td></tr>";
				$result->namespaces[$ns]["name"] = $nsnames[$ns];

				$currentNamespace = $ns;
				$currentNumber = 0;
			}

			$result->namespaces[$ns]["num"]  += 1;
			if ($redirect != "") { $result->namespaces[$ns]["redir"]  += 1; }
			$currentNumber++;

			$result->list .= "
				<tr>
					<td>$currentNumber.</td>
					<td><a href=\"//$lang.$wiki.org/wiki/$prefix$pageurl?redirect=no\">$page</a> <small> $redirect</small></td>
					<td style='font-size:95%' >$date</td>
				</tr> 
			 ";
		}
	
		$result->filterns = $namespace;
		$result->filterredir = $redirects;
		$result->total = count($items);
		unset($items, $nsnames);

		//make serialized lists for graphics & toptable
		foreach ( $result->namespaces as $num => $ns ){
			$result->listns .= "|".$ns["name"];
			$result->listnum .= ",".intval((intval($ns["num"])/intval($result->total))*100);
			
			$result->listnamespaces .='
				<tr>
				<td style="padding-right:5px; text-align:center;">'.$num.'</td>
				<td style="padding-right:10px"><a href="#{$number}" >'.$ns["name"].'</a></td>
				<td style="text-align:right" >'.$ns["num"].'</td>
				<td style="text-align:right" >'.$ns["redir"].'</td>
				</tr>
			';
		}
		$result->listns = urlencode( substr($result->listns, 1) );
		$result->listnum = urlencode( substr($result->listnum, 1) );

		return $result;
	}
	
	function getNamespaceNames( $lang, $wiki ) {
		$http = new HTTP();
		$namespaces = $http->get( "http://$lang.$wiki.org/w/api.php?action=query&meta=siteinfo&siprop=namespaces&format=php" );
		$namespaces = unserialize( $namespaces );
		$namespaces = $namespaces['query']['namespaces'];
		 
		unset( $namespaces[-2] );
		unset( $namespaces[-1] );
	
		$namespaces[0]['*'] = "Mainspace";
		 
		$namespacenames = array();
		foreach ($namespaces as $value => $ns) {
			$namespacenames[$value] = $ns['*'];
		}

		return $namespacenames;
	}

	
private function loadPageTemplates(){
		
$tmp_selectns ='
	<select name="namespace">
		<option value="all">-All-</option>
		<option value="0">Main</option>
		<option value="1">Talk</option>
		<option value="2">User</option>
		<option value="3">User talk</option>
		<option value="4">Wikipedia</option>
		<option value="5">Wikipedia talk</option>
		<option value="6">File</option>
		<option value="7">File talk</option>
		<option value="8">MediaWiki</option>
		<option value="9">MediaWiki talk</option>
		<option value="10">Template</option>
		<option value="11">Template talk</option>
		<option value="12">Help</option>
		<option value="13">Help talk</option>
		<option value="14">Category</option>
		<option value="15">Category talk</option>
		<option value="100">Portal</option>
		<option value="101">Portal talk</option>
		<option value="108">Book</option>
		<option value="109">Book talk</option>
	</select><br />
';
		
$tmp_selectredir ='
	<select name="redirects">
		<option value="none">{#include_redirects#}</option>
		<option value="onlyredirects">{#only_redirects#}</option>
		<option value="noredirects">{#exclude_redirects#}</option>
	</select><br />
';
		
$this->tmplPageForm = '
	<form action="?" method="get" accept-charset="utf-8">
	<table>
		<tr><td>{#user#}: </td><td><input type="text" name="user" /></td></tr>
		<tr><td>{#wiki#}: </td><td><input type="text" value="{$lang}" name="lang" size="9" />.<input type="text" value="{$wiki}" size="10" name="wiki" />.org</td></tr>
		<tr><td>{#namespace#}: </td><td>'.$tmp_selectns.'</td></tr>
		<tr><td>{#redirects#}: </td><td>'.$tmp_selectredir.'</td></tr>
		<!--
		<tr><td>{#start#}: </td><td><input type="text" name="begin" /></td></tr>
		<tr><td>{#end#}: </td><td><input type="text" name="end" /></td></tr>
		-->
		<tr><td colspan="2"><input type="submit" value="{#submit#}" /></td></tr>
	</table>
	</form><br />
';
		
$this->tmplPageResult = '
	<span>{$totalcreated}&nbsp;({#namespace#}: {$nsFilter}, {#redirects#}: {$redirFilter} )</span>
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
			{$namespace_overview}
		</table>
		</td>
		<td><img src="//chart.googleapis.com/chart?cht=p3&amp;chd=t:{$chartValues}&amp;chs=550x140&amp;chl={$chartText}&amp;chco=599ad3|f1595f|79c36a|f9a65a|727272|9e66ab|cd7058|ff0000|00ff00&amp;chf=bg,s,00000000" alt="minor" /></td>
		</tr>
	</table>
		
	<table class="sortable" >
	{$resultDetails}
	</table>
';

}
	
}