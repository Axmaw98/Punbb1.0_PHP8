<?php
/***********************************************************************

  Copyright (C) 2002, 2003  Rickard Andersson (punbb@telia.com)

  This file is part of PunBB.

  PunBB is free software; you can redistribute it and/or modify it
  under the terms of the GNU General Public License as published
  by the Free Software Foundation; either version 2 of the License,
  or (at your option) any later version.

  PunBB is distributed in the hope that it will be useful, but
  WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place, Suite 330, Boston,
  MA  02111-1307  USA

************************************************************************/


require 'config.php';

if (isset($_GET['action']))
	define('PUN_DONT_UPDATE_COOKIE', 1);

require 'include/common.php';


$action = isset($_GET['action']);


// Load the login.php language file
require 'lang/'.$language.'/'.$language.'_login.php';

if (isset($_POST['form_sent']) && $action == 'in')
{
	$username = un_escape(trim($_POST['req_username']));
	$password = un_escape(trim($_POST['req_password']));

	$result = $db->query('SELECT id, username, password, save_pass, status FROM '.$db->prefix.'users WHERE username=\''.addslashes($username).'\'') or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());
	list($user_id, $correct_username, $correct_password, $save_pass, $status) = $db->fetch_row($result);

	if ($correct_password == NULL || $correct_password != md5($password))
		message($lang_login['Wrong user/pass'].' <a href="login.php?action=forget">'.$lang_login['Forgotten pass'].'</a>');

	// Update the status if this is the first time the user logged in
	if ($status == -1)
		$db->query('UPDATE '.$db->prefix.'users SET status=0 WHERE id='.$user_id) or error('Unable to update user status', __FILE__, __LINE__, $db->error());

	$expire = ($save_pass == '1') ? time() + 31536000 : 0;

	if (isset($_COOKIE['punbb_cookie']))
	{
		list(, , $last_action, $last_timeout) = unserialize(un_escape($_COOKIE['punbb_cookie']));

		setcookie('punbb_cookie', serialize(array($correct_username, $correct_password, $last_action, $last_timeout)), $expire, $cookie_path, $cookie_domain, $cookie_secure);
	}
	else
	{
		$now = time();

		setcookie('punbb_cookie', serialize(array($correct_username, $correct_password, $now, $now)), $expire, $cookie_path, $cookie_domain, $cookie_secure);
	}

	redirect($_POST['redirect_url'], $lang_login['Login redirect']);
}


else if ($action == 'out')
{
	if ($cookie['is_guest'])
		header('Location: index.php');

	// Remove user from "users online" list.
	$db->query('DELETE FROM '.$db->prefix.'online WHERE ident=\''.addslashes($cookie['username']).'\'') or error('Unable to delete from online list', __FILE__, __LINE__, $db->error());

	// Remove any left over search results
	$db->query('DELETE FROM '.$db->prefix.'search_results WHERE ident=\''.addslashes($cookie['username']).'\'') or error('Unable to delete search results', __FILE__, __LINE__, $db->error());

	list(, , $last_action, $last_timeout) = unserialize(un_escape($_COOKIE['punbb_cookie']));

	setcookie('punbb_cookie', serialize(array('Guest', 'Guest', $last_action, $last_timeout)), time() + 31536000, $cookie_path, $cookie_domain, $cookie_secure);

	redirect('index.php', $lang_login['Logout redirect']);
}


else if ($action == 'forget' || $action == 'forget_2')
{
	if (isset($_POST['form_sent']))
	{
		require 'include/email.php';

		// Validate the email-address
		$email = strtolower(trim($_POST['req_email']));
		if (!is_valid_email($email))
			message($lang_common['Invalid e-mail']);

		$result = $db->query('SELECT id, username FROM '.$db->prefix.'users WHERE email=\''.escape($email).'\'') or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());

		if ($db->num_rows($result))
		{
			// Loop through users we found
			while ($cur_hit = $db->fetch_assoc($result))
			{
				$new_password = random_pass(8);
				$new_password_key = random_pass(8);

				$db->query('UPDATE '.$db->prefix.'users SET activate_string=\''.md5($new_password).'\', activate_key=\''.$new_password_key.'\' WHERE id='.$cur_hit['id']) or error('Unable to update activation data', __FILE__, __LINE__, $db->error());

				$mail_subject = $lang_login['Forget mail 1'];
				$mail_message = $lang_login['Forget mail 2'].' '.$cur_hit['username'].','."\r\r\n\n".$lang_login['Forget mail 3'].' '.$options['base_url'].'/. '.$lang_login['Forget mail 4']."\r\r\n\n".$lang_login['Forget mail 5']."\r\n".$options['base_url'].'/profile.php?id='.$cur_hit['id'].'&action=change_pass&key='.$new_password_key."\r\r\n\n".$lang_login['Forget mail 6'].' '.$new_password."\r\r\n\n".'/Forum Mailer'."\r\n".'('.$lang_login['Forget mail 7'].')';
				$mail_extra = 'From: '.$options['board_title'].' Mailer <'.$options['webmaster_email'].'>';

				pun_mail($email, $mail_subject, $mail_message, $mail_extra);
			}

			message($lang_login['Forget mail 8'].' '.$email.' '.$lang_login['Forget mail 9'].' <a href="mailto:'.$options['admin_email'].'">'.$options['admin_email'].'</a>.');
		}
		else
			message($lang_login['No e-mail match'].' '.$email.'.');
	}
	else
	{
		$page_title = htmlspecialchars($options['board_title']).' / '.$lang_login['Request pass'];
		$validate_form = true;
		$form_name = 'request_pass';
		$focus_element = 'req_email';
		require 'header.php';

?>
<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>

<table class="punmain" cellspacing="1" cellpadding="4">
	<tr class="punhead">
		<td class="punhead"><?php print $lang_common['Info'] ?></td>
	</tr>
	<tr>
		<td class="puncon2"><?php print $lang_login['Instructions'] ?></td>
	</tr>
</table>

<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>

<form method="post" action="login.php?action=forget_2" id="request_pass" onsubmit="return process_form(this)">
	<input type="hidden" name="form_sent" value="1">
	<table class="punmain" cellspacing="1" cellpadding="4">
		<tr class="punhead">
			<td class="punhead" colspan="2"><?php print $lang_login['Request pass'] ?></td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><b><?php print $lang_common['E-mail'] ?></b>&nbsp;&nbsp;</td>
			<td class="puncon2">&nbsp;<input type="text" name="req_email" size="50" maxlength="50"></td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_common['Actions'] ?>&nbsp;&nbsp;</td>
			<td class="puncon2"><br>&nbsp;&nbsp;<input type="submit" name="request_pass" value="<?php print $lang_common['Submit'] ?>">&nbsp;&nbsp;&nbsp;<a href="javascript:history.go(-1)"><?php print $lang_common['Go back'] ?></a><br><br></td>
		</tr>
	</table>
</form>

<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>
<?php

		require 'footer.php';
	}
}


else
{
	if (!$cookie['is_guest'])
		header('Location: index.php');

	$page_title = htmlspecialchars($options['board_title']).' / '.$lang_common['Login'];
	$validate_form = true;
	$form_name = 'login';
	$focus_element = 'req_username';
	require 'header.php';

?>
<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>

<form method="post" action="login.php?action=in" id="login" onsubmit="return process_form(this)">
	<input type="hidden" name="form_sent" value="1">
	<input type="hidden" name="redirect_url" value="<?php print $_SERVER["HTTP_REFERER"] ?>">
	<table class="punmain" cellspacing="1" cellpadding="4">
		<tr class="punhead">
			<td class="punhead" colspan="2"><?php print $lang_common['Login'] ?></td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><b><?php print $lang_common['Username'] ?></b>&nbsp;&nbsp;</td>
			<td class="puncon2">&nbsp;<input type="text" name="req_username" size="25" maxlength="25"></td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><b><?php print $lang_login['Password'] ?></b>&nbsp;&nbsp;</td>
			<td class="puncon2">&nbsp;<input type="password" name="req_password" size="16" maxlength="16"></td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_common['Actions'] ?>&nbsp;&nbsp;</td>
			<td class="puncon2">
				<br>&nbsp;&nbsp;<input type="submit" name="login" value="<?php print $lang_common['Login'] ?>"><br><br>
				&nbsp;<a href="register.php"><?php print $lang_login['Not registered'] ?></a><br>
				&nbsp;<a href="login.php?action=forget"><?php print $lang_login['Forgotten pass'] ?></a><br><br>
			</td>
		</tr>
	</table>
</form>

<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>
<?php

	require 'footer.php';
}
