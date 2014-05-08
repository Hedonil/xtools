<?php

class BashBase {

	var $quotes;
	var $api = false;

	public function __construct( $api = false ) {
		 
		$pgHTTP = new HTTP();

		$text = $pgHTTP->get('http://meta.wikimedia.org/w/index.php?title=IRC/Quotes&action=raw&ctype=text/css', false);
		
		$text = explode('<pre><nowiki>', $text);
		$text = explode('</nowiki></pre>', $text[1]);
		$text = explode('%%', $text[0]);
		$text = substr($text[0], 2);
		$text = htmlspecialchars($text);
		$text = trim($text);
		$text = preg_replace('/\n/', '<br />', $text);
		
		$this->quotes = explode("%<br />", $text);
		$this->api = $api;

	}
	
	public function getRandomQuote() {
		return $this->getQuoteFromId( rand( 0, count( $this->quotes ) ) );
	}
	
	public function getQuoteFromId( $id ) {
		if( isset( $this->quotes[$id - 1 ] ) ) {
			return array( 'quote' => $this->quotes[$id - 1], 'id' => $id );
		}
		else {
			if( $api ) return array( 'error' => 'noquote', 'info' => 'No quote found' );
		}
	}
	
	public function getAllQuotes() {
		$retArr = array();
		foreach( $this->quotes as $id => $quote ) {
			$retArr[ $id + 1 ] = $quote;
		}
		
		return $retArr;
	}
	
	public function getQuotesFromSearch( $search, $regex = false ) {
		$retArr = array();

		foreach( $this->quotes as $id => $quote ) {
			if( $regex ) {
				if( preg_match( $search, html_entity_decode( $quote ) ) ) {
					$retArr[ $id + 1 ] = $quote;
				}
			}
			else {
				if( in_string( $search, $quote, false ) === true ) {
					$retArr[ $id + 1 ] = $quote;
				} 
			}
		}
		
		return $retArr;
	}
	
	public function getPageForm( $lang="en", $wiki="wikipedia"){
		global $I18N;
	
	$pageForm = '
	<form action="?" method="get" accept-charset="utf-8">
	<table class="wikitable">
	<tr>
		<td colspan="2"><input type="radio" name="action" value="random" checked="checked" />'.$I18N->msg('random').'</td>
	</tr>
	<tr>
		<td colspan="2"><input type="radio" name="action" value="showall" />'.$I18N->msg('showall').'</td>
	</tr>
	<tr>
		<td><input type="radio" name="action" value="search" />'.$I18N->msg('search').'<input type="text" name="search" /> <input type="checkbox" name="regex" />'.$I18N->msg('regex').'</td>
	</tr>
	<tr><td colspan="2"><input type="submit" value="'.$I18N->msg('submit').'" /></td></tr>
	</table>
	</form>
	';

	return $pageForm;
	
	}

}
