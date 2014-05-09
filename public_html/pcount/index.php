<?php
//**************
  $start1 = microtime(true);
//**************

//Requires
	require_once( '../WebTool.php' );
	require_once( 'base.php' );
	require_once( 'counter.php' );
	require_once( '../../Graph.php' );
	
	
//Load WebTool class
	$wt = new WebTool( 'Pages', 'pages', array("smarty", "sitenotice", "replag") );
	WebTool::setMemLimit();
	$base = new PcountBase();
	$wt->content = $base->tmplPageForm;
	$wt->assign("lang", "en");
	$wt->assign("wiki", "wikipedia");
	
//Show form if &article parameter is not set (or empty)
	if( !$wt->webRequest->getSafeVal( 'getBool', 'user' ) ) {
		$wt->showPage($wt);
	}


$opt_in = array();
$opt_out = array();
$no_opt = array();
$default = 'optin';


$name = str_replace("_", " ", urldecode($wgRequest->getVal('user')));
$wiki = $wgRequest->getVal('wiki');
$lang = $wgRequest->getVal('lang');

$url = $lang.'.'.$wiki.'.org';
$wikibase = $url;
if( $wiki == 'wikidata' ) {
    $lang = 'www';
    $wiki = 'wikidata';
    $wikibase= $url = 'www.wikidata.org';
}

$base->http = new HTTP();
$base->baseurl = 'http://'.$wikibase.'/w/';
$wgNamespaces = $base->getNamespaces();

//***************
  $perflog->add('1- init', microtime(true)- $start1);
//**************
  $start2 = microtime(true);
//**************

$cnt = new Counter( $name );

$graphArray = array(
   'colors' => array(
      0 => 'FF5555',
      1 => '55FF55',
      2 => 'FFFF55',
      3 => 'FF55FF',
      4 => '5555FF',
      5 => '55FFFF',
      6 => 'C00000',
      7 => '0000C0',
      8 => '008800',
      9 => '00C0C0',
      10 => 'FFAFAF',
      11 => '808080',
      12 => '00C000',
      13 => '404040',
      14 => 'C0C000',
      15 => 'C000C0',
      100 => '75A3D1',
      101 => 'A679D2',
      102 => '660000',
      103 => '000066',
      104 => 'FAFFAF',
      105 => '408345',
      106 => '5c8d20',
      107 => 'e1711d',
      108 => '94ef2b',
      109 => '756a4a',
      110 => '6f1dab',
      111 => '301e30',
      112 => '5c9d96',
      113 => 'a8cd8c',
      114 => 'f2b3f1',
      115 => '9b5828',
      118 => '99FFFF',
      119 => '99BBFF',
      120 => 'FF99FF',
      121 => 'CCFFFF',
      122 => 'CCFF00',
      123 => 'CCFFCC',
      200 => '33FF00',
      201 => '669900',
      202 => '666666',
      203 => '999999',
      204 => 'FFFFCC',
      205 => 'FF00CC',
      206 => 'FFFF00',
      207 => 'FFCC00',
      208 => 'FF0000',
      209 => 'FF6600',
      446 => '06DCFB',
      447 => '892EE4',
	  460 => '99FF66',
      461 => '99CC66',
      470 => 'CCCC33',
      471 => 'CCFF33',
      480 => '6699FF',
      481 => '66FFFF',
	  490 => '995500',
	  491 => '998800',
      710 => 'FFCECE',
      711 => 'FFC8F2',
      828 => 'F7DE00',
      829 => 'BABA21',
      866 => 'FFFFFF',
      867 => 'FFCCFF',
      1198 => 'FF34B3',
      1199 => '8B1C62',
   ),
   'names' => $wgNamespaces['names'],
   'monthly' => $cnt->getMonthTotals(),
   'gross' => $cnt->getNamespaceTotals(),
);

$graph = new Graph( $graphArray, IPHONE );

$uniqueEdits = $cnt->getUniqueArticles();

if( !$cnt->getExists() ) {
   $wt->error = $I18N->msg('nosuchuser')." ".$cnt->getName();
   $wt->showPage($wt);
}

//**************************
  $perflog->add('2- after-init', microtime(true)-$start2 );
//**************************
  $start3 = microtime(true);
//**************************

$wt->content = $base->tmplPageResult;
#$phptemp->assign( "page", $cnt->getName() );
$wt->assign( "username", $cnt->getName() ); 
$wt->assign( "usernameurl", rawurlencode($cnt->getName()) );
$wt->assign( "url", $url );
$wt->assign( "loadwiki", "&wiki=$wiki&lang=$lang" );

if( count( $cnt->getGroupList() ) ) {
   $wt->assign( "groups", implode( ', ', $cnt->getGroupList() ) );
}
if( $cnt->getLive() > 0) {

   $wt->assign( "firstedit", $cnt->getFirstEdit() );
   $wt->assign( "unique", number_format( count($uniqueEdits['total']) ) );
   $wt->assign( "average", $cnt->getAveragePageEdits() );
   $wt->assign( "live", number_format( intval( $cnt->getLive() ) ) );
   $wt->assign( "deleted", number_format( intval( $cnt->getDeleted() ) ) );

   $wt->assign( "namespacetotals", $graph->legend() );
   $wt->assign( "graph", $graph->pie( $I18N->msg('namespacetotals') ) );
      
		if( in_array($lang.$wiki, $opt_in) ) {
				if( $base->isOptedIn( $cnt->getName() ) ) $wt->assign( "monthcounts", $graph->horizontalBar( 600 ) );
				else $wt->assign( "nograph", $I18N->msg( "nograph", array( "variables"=> array( $cnt->getName(), $url) )) );   
		} 
		elseif( in_array($lang.$wiki, $opt_out) ) {
				if( !$base->isOptedOut( $cnt->getName() ) ) $wt->assign( "monthcounts", $graph->horizontalBar( 600 ) );
				else $wt->assign( "nograph", $I18N->msg( "nograph2", array( "variables"=> array( $cnt->getName(), $url) )) );
		} 
		elseif( in_array($lang.$wiki, $no_opt) ) $wt->assign( "monthcounts", $graph->horizontalBar( 600 ) );
		else {
				//global default
				switch( $default ) {
						case 'optin':
							if( $base->isOptedIn( $cnt->getName() ) ) $wt->assign( "monthcounts", $graph->horizontalBar( 600 ) );
							else $wt->assign( "monthcounts", $I18N->msg( "nograph", array( "variables"=> array( $cnt->getName(), $url) )) );
							break;
						case 'optout':
							if( !$base->isOptedOut( $cnt->getName() ) ) $wt->assign( "monthcounts", $graph->horizontalBar( 600 ) );
							else $wt->assign( "monthcounts", $I18N->msg( "nograph2", array( "variables"=> array( $cnt->getName(), $url) )) );
							break;
						case 'noopt':
							$wt->assign( "monthcounts", $graph->horizontalBar( 600 ) );
							break;
						default:
							$wt->assign( "monthcounts", $graph->horizontalBar( 600 ) );
							break;
				}
		}

   $out = null;
   
   if( $cnt->getLive() < '500000' ) {
      ksort($uniqueEdits['namespace_specific']);

      $num_to_show = 10;

      foreach( $uniqueEdits['namespace_specific'] as $namespace_id => $articles ) {
         //$out .= "<h4>" . $wgNamespaces['names'][$namespace_id] . "</h4>\n";
         $out .= '<table class="collapsible collapsed"><tr><th>' . $wgNamespaces['names'][$namespace_id] . '</th></tr><tr><td>';
         $out .= "<ul>\n";

         asort( $articles );
         $articles = array_reverse( $articles );

         $i = 0;
         foreach ( $articles as $article => $count ) {
            if( $i == $num_to_show ) break;
            if( $namespace_id == 0 ) {
               $nscolon = '';
            }
            else {
               $nscolon = $wgNamespaces['names'][$namespace_id].":";
            }
            $articleencoded = urlencode( $article );
            $articleencoded = str_replace( '%2F', '/', $articleencoded );
            $trimmed = substr($article, 0, 50).'...';
                        $out .= '<li>'.$count." - <a href='//$lang.$wiki.org/wiki/".$nscolon.$articleencoded.'\'>';
            if(strlen(substr($article, 0, 50))<strlen($article)) {
               $out .= $trimmed;
            }
            else {
               $out .= $article;
            }
            $out .= "</a></li>\n";
            $i++;
         }
         $out .= "</ul></td></tr></table><br />";
      }
      
      if( in_array($lang.$wiki, $opt_in) ) {
          if( $base->isOptedIn( $cnt->getName() ) ) $wt->assign( "topedited", $out );
          else $wt->assign( "nograph", $I18N->msg( "nograph", array( "variables"=> array( $cnt->getName(), $url) )) );   
      } 
      elseif( in_array($lang.$wiki, $opt_out) ) {
          if( !$base->isOptedOut( $cnt->getName() ) ) $wt->assign( "topedited", $out );
          else $wt->assign( "nograph", $I18N->msg( "nograph2", array( "variables"=> array( $cnt->getName(), $url) )) );
      } 
      elseif( in_array($lang.$wiki, $no_opt) ) $wt->assign( "topedited", $out );
      else {
          //global default
          switch( $default ) {
              case 'optin':
                if( $base->isOptedIn( $cnt->getName() ) ) $wt->assign( "topedited", $out );
                else $wt->assign( "topedited", $I18N->msg( "nograph", array( "variables"=> array( $cnt->getName(), $url) )) );
                break;
              case 'optout':
                if( !$base->isOptedOut( $cnt->getName() ) ) $wt->assign( "topedited", $out );
                else $wt->assign( "topedited", $I18N->msg( "nograph2", array( "variables"=> array( $cnt->getName(), $url) )) );
                break;
              case 'noopt':
				$wt->assign( "topedited", $out );
                break;
              default:
                $wt->assign( "topedited", $out );
                break;
          }
      }
            
   }
   else {
      $wt->assign( "topedited", $I18N->msg('notopedit') );
   }
}
$wt->assign( "total", number_format( intval( $cnt->getTotal() ) ) );

$wt->moreheader =
   '<link rel="stylesheet" href="//tools.wmflabs.org/xtools/counter_commons/NavFrame.css" type="text/css" />' . "\n\t" .
   '<script src="//bits.wikimedia.org/skins-1.5/common/wikibits.js?urid=257z32_1264870003" type="text/javascript"></script>' . "\n\t" .
   '<script src="//tools.wmflabs.org/xtools/counter_commons/NavFrame.js" type="text/javascript"></script>'
;
$wt->assign( "popup", true );

//**************************
  $perflog->add('3- output', microtime(true)-$start3 );
//**************************

$wt->showPage($wt);