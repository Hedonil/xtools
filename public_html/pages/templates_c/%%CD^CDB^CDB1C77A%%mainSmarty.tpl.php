<?php /* Smarty version 2.6.18, created on 2014-05-03 23:46:46
         compiled from ../../templates/mainSmarty.tpl */ ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "//www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="//www.w3.org/1999/xhtml" xml:lang="en" lang="en" >
<head>
	<title><?php if ($this->_tpl_vars['page'] != ""): ?><?php echo $this->_tpl_vars['page']; ?>
 -<?php endif; ?> <?php echo $this->_config[0]['vars']['tool']; ?>
 - <?php echo $this->_config[0]['vars']['title']; ?>
</title>
	<link rel="stylesheet" type="text/css" href="//tools.wmflabs.org/newwebtest/style.css" />
	<?php if ($this->_tpl_vars['moreheader'] != ""): ?><?php echo $this->_tpl_vars['moreheader']; ?>
<?php endif; ?>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>

<body>
<div id="wrap">
<div id="header">
<?php if ($this->_tpl_vars['page'] != ""): ?><?php echo $this->_tpl_vars['page']; ?>
 -<?php endif; ?> <?php echo $this->_config[0]['vars']['tool']; ?>
 - <?php echo $this->_config[0]['vars']['title']; ?>


</div>
<div id="content">
<div id="navigation" class="center container">
	<a href="//tools.wmflabs.org/xtools/">Home</a> &middot; 
	<a href="//tools.wmflabs.org/xtools/ec">Edit counter</a> &middot; 
	<a href="//tools.wmflabs.org/xtools/articleinfo/">Page History Statistics</a> &middot; 
	<a href="//tools.wmflabs.org/xtools/blame">Article blamer</a> &middot; 
	<a href="//tools.wmflabs.org/xtools/rangecontribs">CIDR</a> &middot; 
	<a href="//tools.wmflabs.org/xtools/ipcalc">IP calculator</a> &middot; 
	<a href="//wiki.toolserver.org/view/User:TParis/Index">Index</a>
</div>
<?php if ($this->_tpl_vars['alert'] != ""): ?><br /><h2 class="alert"><?php echo $this->_tpl_vars['alert']; ?>
</h2><?php endif; ?>
<h2><?php echo $this->_config[0]['vars']['header']; ?>
</h2>
<?php if ($this->_tpl_vars['error'] != ""): ?><?php echo $this->_tpl_vars['error']; ?>
<?php endif; ?>
<?php echo $this->_tpl_vars['content']; ?>

<?php if ($this->_tpl_vars['executedtime'] != ""): ?>
<br />
<hr />
<span style="font-size:100%;">
<?php echo $this->_tpl_vars['executedtime']; ?>

</span>
<?php endif; ?>
</div>

<div id="footer">
<div style="float:right; display:inline-block">
	<span >
		<!-- <a href="//validator.w3.org/check?uri=referer"><img src="//tools.wmflabs.org/xtools/images/xhtml.png" alt="Valid XHTML 1.0 Transitional" height="31" width="88" /></a> -->
		<a href="//anybrowser.org/campaign"><img src="//tools.wmflabs.org/xtools/images/anybrowser.png" alt="AnyBrowser compliant" /></a>
		<a href="//tools.wmflabs.org"><img src="//tools.wmflabs.org/xtools/images/labs.png" alt="Powered by WMF Labs" /></a>
	</span>
</div>
<div style="float:left; display:inline-block; line-height:1.5em;">
	<span>&copy;2014 
		<a href="//en.wikipedia.org/wiki/User:Cyberpower678">Cyberpower678</a>&middot;<a href="//de.wikipedia.org/wiki/User:Hedonil">Hedonil</a>&middot;<a href="//en.wikipedia.org/wiki/User:TParis">TParis</a>&middot;<a href="//en.wikipedia.org/wiki/User:X!">X!</a>| 
		<?php if ($this->_tpl_vars['source'] != ""): ?><a href="//github.com/x-Tools/xtools/tree/master/public_html/<?php echo $this->_tpl_vars['source']; ?>
" ><?php echo $this->_config[0]['vars']['source']; ?>
</a><?php else: ?><a href="//github.com/x-Tools/xtools/" ><?php echo $this->_config[0]['vars']['source']; ?>
</a><?php endif; ?>|<a href="//github.com/x-Tools/xtools/issues" ><?php echo $this->_config[0]['vars']['bugs']; ?>
</a>|<a href="irc://irc.freenode.net/#wikimedia-labs" >#wikimedia-labs</a>
		<sup><a  style="color:green" href="https://webchat.freenode.net/?channels=#wikimedia-labs">WebChat</a></sup>
	</span><br />
	<span>
		<?php if ($this->_tpl_vars['curlang'] != ""): ?><?php echo $this->_config[0]['vars']['language']; ?>
<?php endif; ?><?php if ($this->_tpl_vars['curlang'] != ""): ?>: <?php echo $this->_tpl_vars['curlang']; ?>
 | <span ><?php echo $this->_tpl_vars['langlinks']; ?>
</span><?php endif; ?>
	</span>
</div>
</div>
</div>

<!-- currently ice'd <?php if ($this->_tpl_vars['translate'] != ""): ?> (<a href="<?php echo $this->_tpl_vars['translate']; ?>
"><?php echo $this->_config[0]['vars']['translatelink']; ?>
</a>)<?php endif; ?> -->

<script type="text/javascript">if (window.runOnloadHook) runOnloadHook();</script>

</body>

</html>