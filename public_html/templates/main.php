<!DOCTYPE html>
<html>
<head>
	<title><?php echo $wt->toolname ?> - X's tools</title>
	<link rel="stylesheet" type="text/css" href="//tools.wmflabs.org/newwebtest/style.css" />
	<?php echo $wt->moreheader ?>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>

<body>
<div id="wrap">

	<div id="header">
		<span>X!'s tools</span>
	</div>
	
	<div id="content">
		<div id="navigation" class="center container">
			<a href="<?php echo $wt->basePath?>/ec">Home</a> &middot; 
			<a href="<?php echo $wt->basePath?>/ec">Edit counter<sup style="color:green; font-size:70%; position:relative;left:-27px; top:-5px; margin-right:-30px"> classic</sup></sup></a> &middot; 
			<a href="<?php echo $wt->basePath?>/pages">Pages created</a> &middot; 
			<a href="<?php echo $wt->basePath?>/autoedits">Autmated edits</a> &middot; 
			<a href="<?php echo $wt->basePath?>/articleinfo/">Page history</a> &middot; 
			<a href="<?php echo $wt->basePath?>/blame">Article blamer</a> &middot; 
			<a href="<?php echo $wt->basePath?>/rangecontribs">CIDR</a> &middot; 
			<a href="<?php echo $wt->basePath?>/autoblock">Autoblock</a> &middot; 
			<a href="<?php echo $wt->basePath?>/rfa">RfX</a> &middot; 
			<a href="<?php echo $wt->basePath?>/rfap">RfX Vote</a> &middot; 
			<a href="<?php echo $wt->basePath?>/bash">Random quote</a> &middot;
			<a href="<?php echo $wt->basePath?>/ipcalc">IP calculator</a> &middot; 
		</div>

		<div id="alerts">
			<?php echo ($wt->alert) ? "<h2 class='alert'> $wt->alert </h2>" : "" ?>
			<?php echo ($wt->error) ? "<h2 class='error'> $wt->error </h2>" : "" ?>
			<h2><?php echo $wt->title ?></h2>
		</div>
		
		<div id="contentmain">
			<?php echo $wt->content ?>
		</div>
		
		<br />
		<span><small><?php echo $wt->executed ?></small></span>
	</div>

	<div id="footer">
		<hr />
		<div style="float:right; display:inline-block">
			<span >
				<!-- <a href="//validator.w3.org/check?uri=referer"><img src="//tools.wmflabs.org/xtools/images/xhtml.png" alt="Valid XHTML 1.0 Transitional" height="31" width="88" /></a> -->
				<a style="margin-right:5px;" href="//translatewiki.net/?setlang=<?php echo $wt->uselang ?> "><img height="36px" src="//upload.wikimedia.org/wikipedia/commons/5/51/Translatewiki.net_logo.svg" alt="translatewiki.net logo"/></a>
				<!-- <a href="//anybrowser.org/campaign"><img height="40px" src="//tools.wmflabs.org/xtools/images/anybrowser.png" alt="AnyBrowser compliant" /></a> -->
				<a href="//tools.wmflabs.org"><img height="40px" src="//tools.wmflabs.org/xtools/images/labs.png" alt="Powered by WMF Labs" /></a>
			</span>
		</div>	
		<div style="float:left; display:inline-block; line-height:1.5em;">
			<span>&copy;2014 
				<a href="//en.wikipedia.org/wiki/User:Cyberpower678">Cyberpower678</a> &middot;
				<a href="//de.wikipedia.org/wiki/User:Hedonil">Hedonil</a> &middot;
				<a href="//en.wikipedia.org/wiki/User:TParis">TParis</a> &middot;
				<a href="//en.wikipedia.org/wiki/User:X!">X!</a> | 
				<?php echo $wt->sourcecode ?>
				<?php echo $wt->bugreport ?>
				<a href="irc://irc.freenode.net/#wikimedia-labs" >#wikimedia-labs</a>
				<sup><a  style="color:green" href="https://webchat.freenode.net/?channels=#wikimedia-labs">WebChat</a></sup>
			</span>
			<br />
			<span>
				<span><a href="//translatewiki.net/wiki/Special:Translate?group=tsint-supercount&amp;filter=%21translated&amp;action=translate&amp;setlang=<?php echo $wt->uselang?>" >(<?php echo $wt->translate ?>)</a></span>
				<span style="margin-left:5px"><?php echo $wt->langLinks ?></span>
			</span>
		</div>
	</div>
</div>

<script type="text/javascript">if (window.runOnloadHook) runOnloadHook();</script>

</body>

</html>
