<!DOCTYPE html>
<html>
<head>
	<title>X's tools</title>
	<link rel="stylesheet" type="text/css" href="//tools.wmflabs.org/newwebtest/style.css" />
	<script type="text/javascript" src="//tools.wmflabs.org/newwebtest/sortable.js"></script>
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
			<a href="//tools.wmflabs.org/supercount/">User Analysis Tool</a> &middot; 
			<a href="<?php echo $wt->basePath?>/ec">Edit counter<sup style="color:green; font-size:70%; position:relative;left:-27px; top:-5px; margin-right:-30px">classic</sup></a> &middot; 
			<a href="<?php echo $wt->basePath?>/articleinfo/">Page history</a> &middot; 
			<a href="<?php echo $wt->basePath?>/pages">Pages created</a> &middot;
			<a href="<?php echo $wt->basePath?>/topedits">Top edits</a> &middot; 
			<a href="<?php echo $wt->basePath?>/autoedits">Automated edits</a> &middot; 
			<a href="<?php echo $wt->basePath?>/blame">Article blamer</a> &middot; 
			<a href="<?php echo $wt->basePath?>/rangecontribs">Range contribs</a> &middot; 
			<a href="<?php echo $wt->basePath?>/autoblock">Autoblock</a> &middot; 
			<a href="<?php echo $wt->basePath?>/rfa">RfX</a> &middot; 
			<a href="<?php echo $wt->basePath?>/rfap">RfX Vote</a> &middot; 
			<a href="<?php echo $wt->basePath?>/bash">RQ</a> &middot;
			<a href="<?php echo $wt->basePath?>/sc">SC</a> &middot;
		</div>

		<div id="alerts">
			<h2 style="margin-bottom: 0.4em"><?php echo $wt->toolTitle ?><span style="font-size: 75%;font-weight:normal; "> &bull; <?php echo $wt->toolDesc ?></span></h2>
			<?php echo ($wt->alert) ? "<h3 class='alert'> $wt->alert </h3>" : "" ?>
			<?php echo ($wt->error) ? "<div class='alert'> $wt->error </div>" : "" ?>
			<?php echo ($wt->replag) ? "<div class='alert'> $wt->replag </div>" : "" ?>
		</div>
		
		<div id="contentmain">
			<?php echo $wt->content ?>
		</div>
		
		<br />
		<span><small><span><?php echo $wt->executed ?></span> &middot; <span><?php echo $wt->memused ?></span></small></span>
	</div>

	<div id="footer">
		<hr style="margin-top:0px;"/>
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
			<span><?php echo $wt->langLinks ?></span>
		</div>
	</div>
</div>

<script type="text/javascript">
	if (window.runOnloadHook) runOnloadHook(); 
	if (window.sortables_init) sortables_init();
</script>

</body>
</html>
