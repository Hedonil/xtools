<?php 

DEFINE('XTOOLS_I18_TEXTFILE', '/data/project/newwebtest/i18n/Supercount.i18n.php');
include(XTOOLS_I18_TEXTFILE);

#print_r($messages); 
$langin = $_GET["lang"];

$langs = array_keys($messages);
foreach($langs as $lang){
	$sel = ($lang == $langin) ? "selected" : ""; 
	$opts .= "<option $sel value='$lang' >$lang</option> ";
}

$list = "<table><tr>";
$list .= "<td style='vertical-align:top; '><table>";
$ff = array_intersect_key( $messages["en"], $messages[$langin]);
ksort($ff);
foreach( $ff as $key => $msg ){
	$list .= "<tr><td>match</td><td style='color:green'>$key</td></tr>"; 
}
$list .= "</table></td>";

$list .= "<td style='vertical-align:top; '><table>";
$ff = array_diff_key( $messages["en"], $messages[$langin]);
ksort($ff);
foreach( $ff as $key => $msg ){
	$list .= "<tr><td>en</td><td style='color:orange'>$key</td></tr>";
}
$list .= "</table></td>";

$list .= "<td style='vertical-align:top; '><table>";
$ff = array_diff_key( $messages[$langin], $messages["en"]);
ksort($ff);
foreach( $ff as $key => $msg ){
	$list .= "<tr><td>$langin</td><td style='color:blue'>$key</td></tr>";
}
$list .= "</table></td>";


$list .= "</tr></table>";
$output='
	<html>
	<body>
	<form action="?" >
		<select name="lang" onchange="submit()">'.$opts.'</select>
	</form>
	<div style="text-align:center; margin-left:100px;">
		'.$list.'
	</div>
	</body>
	</html>
		
';

echo $output;