<div id="edit_form">
<form name="broadcast-message" method="post" action="">
<table>
	<tr>
		<th colspan="2">{$PALANG.pBroadcast_title}</th>
	</tr>
	<tr>
		<td class="label"><label>{$PALANG.pBroadcast_from}:</label></td>
		<td><em>{$smtp_from_email}</em></td>
	</tr>
	<tr>
		<td class="label"><label>{$PALANG.pBroadcast_name}:</label></td>
		<td><input class="flat" size="43" type="text" name="name"/></td>
	</tr>
	<tr>
		<td class="label"><label>{$PALANG.pBroadcast_subject}:</label></td>
		<td><input class="flat" size="43" type="text" name="subject"/></td>
	</tr>
	<tr>
		<td class="label"><label>{$PALANG.pBroadcast_message}:</label></td>
		<td><textarea class="flat" cols="40" rows="6" name="message"></textarea></td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>
		<input class="button" type="submit" name="submit" value="{$PALANG.pBroadcast_send}" />
		</td>
	</tr>
</table>
</form>
</div>
