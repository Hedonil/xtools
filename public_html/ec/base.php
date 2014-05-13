<?php 

class PcountBase{
	
	public $tmplPageForm;
	public $tmplPageResult;
	
	public $baseurl;
	public $http;
	
	function __construct(){
		$this->loadPageTemplates();
	}
	
	/**
	 * 
	 * @param object $http 
	 * @return Ambigous <multitype:multitype: , unknown>
	 */
	function getNamespaces() {
		#global $phptemp;
	
		$x = unserialize( $this->http->get( $this->baseurl . 'api.php?action=query&meta=siteinfo&siprop=namespaces&format=php' ) );
	
		unset( $x['query']['namespaces'][-2] );
		unset( $x['query']['namespaces'][-1] );
	
		$res = array( 
				'ids' => array(), 
				'names' => array() 
			);
	
		foreach( $x['query']['namespaces'] as $id => $ns ) {
			$res['ids'][$ns['*']] = $id;
			$res['names'][$id] = $ns['*'];
		}
	
		#if( isset( $phptemp ) ) $res['ids'][''] = $res['names'][0] = $phptemp->getConf( 'mainspace' );
	
		return $res;
	}
	
	function isOptedOut( $http, $user ) {
		$x = unserialize( $this->http->get( $this->baseurl . 'api.php?action=query&prop=revisions&titles=User:'.urlencode($user).'/EditCounterOptOut.js&rvprop=content&format=php' ) );
	
		foreach( $x['query']['pages'] as $page ) {
			if( !isset( $page['revisions'] ) ) {
	
				$x = unserialize( $this->http->get( '//meta.wikimedia.org/w/api.php?action=query&prop=revisions&titles=User:'.urlencode($user).'/EditCounterGlobalOptOut.js&rvprop=content&format=php' ) );
				foreach( $x['query']['pages'] as $page ) {
					if( !isset( $page['revisions'] ) ) {
						$x = unserialize( $this->http->get( $this->baseurl . 'api.php?action=query&prop=revisions&titles=User:'.urlencode($user).'/Editcounter&rvprop=content&format=php' ) );
						foreach( $x['query']['pages'] as $page ) {
							if( !isset( $page['revisions'] ) ) {
								return false;
							}
							elseif( strpos( $page['revisions'][0]['*'], "Month-Graph:no" ) !== FALSE ) {
								return true;
							}
						}
					}
					elseif( $page['revisions'][0]['*'] != "" ) {
						return true;
					}
				}
			}
			elseif( $page['revisions'][0]['*'] != "" ) {
				return true;
			}
		}
	}
	
	function isOptedIn( $user ) {
		$x = unserialize( $this->http->get( $this->baseurl . 'api.php?action=query&prop=revisions&titles=User:'.urlencode($user).'/EditCounterOptIn.js&rvprop=content&format=php' ) );
	
		foreach( $x['query']['pages'] as $page ) {
			if( !isset( $page['revisions'] ) ) {
	
				$x = unserialize( $this->http->get( 'http://meta.wikimedia.org/w/api.php?action=query&prop=revisions&titles=User:'.urlencode($user).'/EditCounterGlobalOptIn.js&rvprop=content&format=php' ) );
				foreach( $x['query']['pages'] as $page ) {
					if( !isset( $page['revisions'] ) ) {
						$x = unserialize( $this->http->get( $this->baseurl . 'api.php?action=query&prop=revisions&titles=User:'.urlencode($user).'/Editcounter&rvprop=content&format=php' ) );
						foreach( $x['query']['pages'] as $page ) {
							if( !isset( $page['revisions'] ) ) {
								return false;
							}
							elseif( strpos( $page['revisions'][0]['*'], "Month-Graph:yes" ) !== FALSE ) {
								return true;
							}
						}
					}
					elseif( $page['revisions'][0]['*'] != "" ) {
						return true;
					}
				}
			}
			elseif( $page['revisions'][0]['*'] != "" ) {
				return true;
			}
		}
	
		return false;
	}
	
	function getWhichOptIn( $user ) {
		$x = unserialize( $this->http->get( $this->baseurl . 'api.php?action=query&prop=revisions&titles=User:'.urlencode($user).'/EditCounterOptIn.js&rvprop=content&format=php' ) );
	
		foreach( $x['query']['pages'] as $page ) {
			if( !isset( $page['revisions'] ) ) {
	
				$x = unserialize( $this->http->get( 'http://meta.wikimedia.org/w/api.php?action=query&prop=revisions&titles=User:'.urlencode($user).'/EditCounterGlobalOptIn.js&rvprop=content&format=php' ) );
				foreach( $x['query']['pages'] as $page ) {
					if( !isset( $page['revisions'] ) ) {
						$x = unserialize( $this->http->get( $this->baseurl . 'api.php?action=query&prop=revisions&titles=User:'.urlencode($user).'/Editcounter&rvprop=content&format=php' ) );
						foreach( $x['query']['pages'] as $page ) {
							if( !isset( $page['revisions'] ) ) {
								return "false";
							}
							elseif( strpos( $page['revisions'][0]['*'], "Month-Graph:yes" ) !== FALSE ) {
								return "interiot";
							}
						}
					}
					elseif( $page['revisions'][0]['*'] != "" ) {
						return "globally";
					}
				}
			}
			elseif( $page['revisions'][0]['*'] != "" ) {
				return "locally";
			}
		}
	
		return "false";
	}

	
private function loadPageTemplates(){
	
$this->tmplPageForm = '
		
	<script type="text/javascript">
		var collapseCaption = "{#hide#}";
		var expandCaption = "{#show#}";
	</script>
	<br />
	{#welcome#}
	<br /><br />
	<form action="?" method="get">
		<table>
		<tr><td>{#user#}: </td><td><input type="text" name="user" /></td></tr>
		<tr><td>{#wiki#}: </td><td><input type="text" value="{$lang}" name="lang" size="9" />.<input type="text" value="{$wiki}" size="10" name="wiki" />.org</td></tr>
		<tr><td colspan="2"><input type="submit" value="{#submit#}" /></td></tr>
		</table>
	</form><br />
';


$this->tmplPageResult = '
		
	<script type="text/javascript">
	var collapseCaption = "{#hide#}";
	var expandCaption = "{#show#}";
	</script>

	<table>
		<tr>
			<td>{#username#}:</td><td><a href="http://{$url$}/wiki/User:{$usernameurl$}">{$username$}</a></td>
		</tr>
		<tr>
			<td>{#groups#}:</td><td>{$groups$}</td>
		</tr>
		<tr>
			<td>{#firstedit#}:</td><td>{$firstedit$}</td>
		</tr>
		<tr>
			<td>{#unique#}:</td><td>{$unique$}</td>
		</tr>
		<tr>
			<td>{#average#}:</td><td>{$average$}</td>
		</tr>
		<tr>
			<td>{#live#}:</td><td>{$live$}</td>
		</tr>
		<tr>
			<td>{#deleted#}:</td><td>{$deleted$}</td>
		</tr>
		<tr>
			<td><b>{#total#}:</b>&nbsp;&nbsp;</td><td><b>{$total$}</b></td>
		</tr>
	</table>
	<br />

	<h3>{#namespacetotals#}</h3>
	<br />
	<table>
		<tr><td>{$namespacetotals$}</td><td><div class="center">{$graph$}</div></td></tr>
	</table>

	<h3>{#monthcounts#}</h3>
		{$monthcounts$}
	<br />

	<div id="popup"></div>
		<script type="text/javascript"><!--
			var pop = document.getElementById("popup");
			var xoffset = 15;
			var yoffset = 10;

			document.onmousemove = function(e) {
	  			var x, y, right, bottom;
				try { x = e.pageX; y = e.pageY; } // FF
				catch(e) { x = event.x; y = event.y; } // IE
	
				right = (document.documentElement.clientWidth || document.body.clientWidth || document.body.scrollWidth);
				bottom = (window.scrollY || document.documentElement.scrollTop || document.body.scrollTop) + (window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight || document.body.scrollHeight);
	
				x += xoffset;
				y += yoffset;
	
				if(x > right-pop.offsetWidth)
				x = right-pop.offsetWidth;
	 
				if(y > bottom-pop.offsetHeight)
				y = bottom-pop.offsetHeight;
	  
				pop.style.top = y+"px";
				pop.style.left = x+"px"";

			}

			function popup(text) {
			  pop.innerHTML = text;
			  pop.style.display = "block";
			}

			function popout() {
			  pop.style.display = "none";
			}

		//--></script>


	<h3>{#topedited#}</h3>
		{$topedited$}
	<br />


';

}

}