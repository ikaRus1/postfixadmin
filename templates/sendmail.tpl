<div id="edit_form">
<form name="mailbox" method="post" action="">
<table>
	<tr>
		<th colspan="3">{$PALANG.pSendmail_welcome}</th>
	</tr>
	<tr>
		<td class="label"><label>{$PALANG.pSendmail_admin}:</label></td>
		<td><em>{$smtp_from_email}</em></td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class="label"><label>{$PALANG.pSendmail_to}:</label></td>
		<td><input class="flat" type="text" name="fTo" /></td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class="label"><label>{$PALANG.pSendmail_subject}:</label></td>
		<td><input class="flat" type="text" name="fSubject" value="{$PALANG.pSendmail_subject_text}" /></td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class="label"><label>{$PALANG.pSendmail_body}:</label></td>
		<td>
		<textarea class="flat" rows="10" cols="60" name="fBody">{$CONF.welcome_text}</textarea>
		</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class="label">&nbsp;</td>
		<td colspan="2"><input class="button" type="submit" name="submit" value="{$PALANG.pSendmail_button}" /></td>
	</tr>
</table>
</form>
</div>
