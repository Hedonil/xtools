{if $error != ""}<br /><h2 class="alert">{$error}</h2>{/if}
{if $notice != ""}<br /><h2 class="notice">{$notice}</h2>{/if}
{if $replag != ""}<br /><h2 class="alert">{$replag}</h2>{/if}

{if $form != ""}
There are two ways to use this tool
<ol>
<li>IP range: Enter a CIDR range into the box, in the format 0.0.0.0/0</li>
<li>IP list: Enter a list of IPs into the box, separated by newlines.</li>
</ol><br />
<form action="?" method="get">
<table>
<tr>
	<td style="padding-left:5px" >{#wiki#}: <input type="text" value="{$form}" name="lang" size="9" />.<input type="text" value="wikipedia" size="10" name="wiki" />.org</td>
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
{/if}

{if $showresult != ""}
<table>
<tr><td><b>{#cidr#}:</b></td><td>{$cidr}</td></tr>
<tr><td><b>{#ip_start#}:</b></td><td>{$ip_start}</td></tr>
<tr><td><b>{#ip_end#}:</b></td><td>{$ip_end}</td></tr>
<tr><td><b>{#ip_number#}:</b></td><td>{$ip_number}</td></tr>
<tr><td><b>{#ip_found#}:</b></td><td>{$ip_found}</td></tr>
</table>

{$list2}

<table>
{$list}
</table>
<br />
<hr />
{/if}