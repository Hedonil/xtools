<?php /* Smarty version 2.6.18, created on 2014-04-29 18:10:16
         compiled from pages.tpl */ ?>

<?php if ($this->_tpl_vars['error'] != ""): ?><br /><h2 class="alert"><?php echo $this->_tpl_vars['error']; ?>
</h2><?php endif; ?>
<?php if ($this->_tpl_vars['notice'] != ""): ?><br /><h2 class="notice"><?php echo $this->_tpl_vars['notice']; ?>
</h2><?php endif; ?>
<?php if ($this->_tpl_vars['replag'] != ""): ?><br /><h2 class="alert"><?php echo $this->_tpl_vars['replag']; ?>
</h2><?php endif; ?>

<?php if ($this->_tpl_vars['form'] != ""): ?>
<?php $this->assign('begintime', '1'); ?>
<br />
<form action="?" method="get" accept-charset="utf-8">
<table>
<tr><td><?php echo $this->_config[0]['vars']['user']; ?>
: </td><td><input type="text" name="user" /></td></tr>
<tr><td><?php echo $this->_config[0]['vars']['wiki']; ?>
: </td><td><input type="text" value="<?php echo $this->_tpl_vars['form']; ?>
" name="lang" size="9" />.<input type="text" value="wikipedia" size="10" name="wiki" />.org</td></tr>
<tr><td><?php echo $this->_config[0]['vars']['namespace']; ?>
: </td><td><?php echo $this->_tpl_vars['selectns']; ?>
</td></tr>
<tr><td><?php echo $this->_config[0]['vars']['redirects']; ?>
: </td><td><?php echo $this->_tpl_vars['selectredir']; ?>
</td></tr>
<!-- 
<tr><td><?php echo $this->_config[0]['vars']['start']; ?>
: </td><td><input type="text" name="begin" /></td></tr>
<tr><td><?php echo $this->_config[0]['vars']['end']; ?>
: </td><td><input type="text" name="end" /></td></tr>
-->
<tr><td colspan="2"><input type="submit" value="<?php echo $this->_config[0]['vars']['submit']; ?>
" /></td></tr>
</table>
</form><br /><hr />
<?php endif; ?>

<?php if ($this->_tpl_vars['showresult'] != ""): ?>

<?php echo $this->_tpl_vars['totalcreated']; ?>
&nbsp;(Redirect filter: <?php echo $this->_tpl_vars['filterredir']; ?>
)
<?php if ($this->_tpl_vars['graphs'] != ""): ?>
<table>
<tr>
<td>
	<table style="margin-top: 10px" ><?php echo $this->_tpl_vars['toptable']; ?>

		<tr>
			<th>NS</th>
			<th>NS name</th>
			<th>Pages</th>
			<th style="padding_left:5px">&nbsp;&nbsp;(Redirects)</th>
		</tr>
		<?php $_from = $this->_tpl_vars['namespaces']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['number'] => $this->_tpl_vars['ns']):
?>
			<tr>
				<td style="padding-right:5px; text-align:center;"><?php echo $this->_tpl_vars['number']; ?>
</td>
				<td style="padding-right:10px"><a href="#<?php echo $this->_tpl_vars['number']; ?>
" ><?php echo $this->_tpl_vars['ns']['name']; ?>
</a></td>
				<td style="text-align:right" ><?php echo $this->_tpl_vars['ns']['num']; ?>
</td>
				<td style="text-align:right" ><?php echo $this->_tpl_vars['ns']['redir']; ?>
</td>
			</tr>
		<?php endforeach; endif; unset($_from); ?>
	</table>
</td>
<td><img src="//chart.googleapis.com/chart?cht=p3&amp;chd=t:<?php echo $this->_tpl_vars['nstotals']; ?>
&amp;chs=550x140&amp;chl=<?php echo $this->_tpl_vars['nsnames']; ?>
&amp;chco=599ad3|f1595f|79c36a|f9a65a|727272|9e66ab|cd7058|ff0000|00ff00&amp;chf=bg,s,00000000" alt="<?php echo $this->_config[0]['vars']['minoralt']; ?>
" /></td>
</tr>
</table>

<?php endif; ?>
<table class="sortable" >
<?php echo $this->_tpl_vars['list']; ?>

</table>


<?php endif; ?> 