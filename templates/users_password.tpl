<div id="edit_form">
<form name="alias" method="post" action="">
<table>
	<tr>
		<td colspan="3"><h3>{$PALANG.pPassword_welcome}</h3></td>
	</tr>
	<tr>
		<td>{$PALANG.pPassword_admin}:</td>
		<td>{$USERID_USERNAME}</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td>{$PALANG.pPassword_password_current}:</td>
		<td><input class="flat" type="password" name="fPassword_current" /></td>
		<td>{$pPassword_password_current_text}</td>
	</tr>
	<tr>
		<td>{$PALANG.pPassword_password}:</td>
		<td><input class="flat" type="password" name="fPassword" /></td>
		<td>{$pPassword_password_text}</td>
	</tr>
	<tr>
		<td>{$PALANG.pPassword_password2}:</td>
		<td><input class="flat" type="password" name="fPassword2" /></td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td colspan="3" class="hlp_center">
		<input class="button" type="submit" name="submit" value="{$PALANG.pPassword_button}" />
		<input class="button" type="submit" name="fCancel" value="{$PALANG.exit}" />
		</td>
	</tr>
	<tr>
		<td colspan="3" class="standout">{$tMessage}</td>
	</tr>
</table>
</form>
</div>
