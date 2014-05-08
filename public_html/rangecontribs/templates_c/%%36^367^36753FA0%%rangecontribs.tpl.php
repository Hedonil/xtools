<?php /* Smarty version 2.6.18, created on 2014-05-04 19:34:33
         compiled from rangecontribs.tpl */ ?>
<?php if ($this->_tpl_vars['error'] != ""): ?><br /><h2 class="alert"><?php echo $this->_tpl_vars['error']; ?>
</h2><?php endif; ?>
<?php if ($this->_tpl_vars['notice'] != ""): ?><br /><h2 class="notice"><?php echo $this->_tpl_vars['notice']; ?>
</h2><?php endif; ?>
<?php if ($this->_tpl_vars['replag'] != ""): ?><br /><h2 class="alert"><?php echo $this->_tpl_vars['replag']; ?>
</h2><?php endif; ?>

<?php if ($this->_tpl_vars['form'] != ""): ?>
There are two ways to use this tool
<ol>
<li>IP range: Enter a CIDR range into the box, in the format 0.0.0.0/0</li>
<li>IP list: Enter a list of IPs into the box, separated by newlines.</li>
</ol><br />
<form action="?" method="get">
<table>
<tr>
	<td style="padding-left:5px" ><?php echo $this->_config[0]['vars']['wiki']; ?>
: <input type="text" value="<?php echo $this->_tpl_vars['form']; ?>
" name="lang" size="9" />.<input type="text" value="wikipedia" size="10" name="wiki" />.org</td>
</tr>
<tr>
	<td style="padding-left:5px" >Limit: 
	<select name="limit"> 
	<option value="50">50</option> 
	<option selected value="500" >500</option> 
	<option value="5000">5000</option>
	</select>
	</td> 
</tr>
<tr></tr>
<tr>
	<td style="padding-left:5px; display:inline" >
	<span style="padding-right:20px">IP range: <input type="radio" name="type" value="range" /></span> 
	<span>IP list: <input type="radio" name="type" value="list" /></span>
	</td> 
</tr>
<tr>
	<td><textarea name="ips" rows="10" cols="40"></textarea></td>
</tr>
<tr>
	<td><input type="submit" /></td>
</tr>
</table>
</form><br /><hr />
<?php endif; ?>

<?php if ($this->_tpl_vars['showresult'] != ""): ?>
<table>
<tr><td><b><?php echo $this->_config[0]['vars']['cidr']; ?>
:</b></td><td><?php echo $this->_tpl_vars['cidr']; ?>
</td></tr>
<tr><td><b><?php echo $this->_config[0]['vars']['ip_start']; ?>
:</b></td><td><?php echo $this->_tpl_vars['ip_start']; ?>
</td></tr>
<tr><td><b><?php echo $this->_config[0]['vars']['ip_end']; ?>
:</b></td><td><?php echo $this->_tpl_vars['ip_end']; ?>
</td></tr>
<tr><td><b><?php echo $this->_config[0]['vars']['ip_number']; ?>
:</b></td><td><?php echo $this->_tpl_vars['ip_number']; ?>
</td></tr>
<tr><td><b><?php echo $this->_config[0]['vars']['ip_found']; ?>
:</b></td><td><?php echo $this->_tpl_vars['ip_found']; ?>
</td></tr>
</table>

<?php echo $this->_tpl_vars['list2']; ?>


<table>
<?php echo $this->_tpl_vars['list']; ?>

</table>
<br />
<hr />
<?php endif; ?>