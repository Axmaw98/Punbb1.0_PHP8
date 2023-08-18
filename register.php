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
require 'include/common.php';


// If we are logged in, we shouldn't be here
if (!$cookie['is_guest'])
	header('Location: index.php');

// Load the register.php language file
require 'lang/'.$language.'/'.$language.'_register.php';

// Load the register.php/profile.php language file
require 'lang/'.$language.'/'.$language.'_prof_reg.php';

if ($options['regs_allow'] == '0')
	message($lang_register['No new regs']);


// User pressed the cancel button
if (isset($_POST['cancel']))
	redirect('index.php', $lang_register['Reg cancel redirect']);


else if ($options['rules'] == '1' && !isset($_POST['accept']) && !isset($_POST['form_sent']))
{
	$page_title = htmlspecialchars($options['board_title']).' / '.$lang_register['Register'];
	require 'header.php';

?>
<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>

<form method="post" action="register.php">
	<table class="punmain" cellspacing="1" cellpadding="4">
		<tr class="punhead">
			<td class="punhead"><?php print $lang_register['Forum rules'] ?></td>
		</tr>
		<tr>
			<td class="puncon2">
				<?php print $options['rules_message'] ?>
				<br><br><br><div style="text-align: center"><input type="submit" name="accept" value="<?php print $lang_register['Accept'] ?>">&nbsp;&nbsp;<input type="submit" name="cancel" value="<?php print $lang_register['Cancel'] ?>"></div><br>
			</td>
		</tr>
	</table>
</form>

<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>
<?php

	require 'footer.php';
}


else if (isset($_POST['form_sent']))
{
	$username = trim(un_escape($_POST['req_username']));
	$email1 = strtolower(trim($_POST['req_email1']));

	if ($options['regs_validate'] == '1')
	{
		$email2 = strtolower(trim($_POST['req_email2']));

		$password1 = random_pass(8);
		$password2 = $password1;
	}
	else
	{
		$password1 = trim(un_escape($_POST['req_password1']));
		$password2 = trim(un_escape($_POST['req_password2']));
	}

	// Validate username and passwords
	if (strlen($username) < 2)
		message($lang_prof_reg['Username too short']);
	else if (strlen($password1) < 4)
		message($lang_prof_reg['Pass too short']);
	else if ($password1 != $password2)
		message($lang_prof_reg['Pass not match']);
	else if (!strcasecmp($username, 'Guest') || !strcasecmp($username, $lang_common['Guest']))
		message($lang_prof_reg['Username guest']);
	else if (preg_match('/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/', $username))
		message($lang_prof_reg['Username IP']);
	else if (preg_match('#\[b\]|\[/b\]|\[u\]|\[/u\]|\[i\]|\[/i\]|\[color|\[/color\]|\[quote\]|\[/quote\]|\[code\]|\[/code\]|\[img\]|\[/img\]|\[url|\[/url\]|\[email|\[/email\]#i', $username))
		message($lang_prof_reg['Username BBCode']);

	// Check username for any censored words
	if ($options['censoring'] == '1')
	{
		$temp = censor_words($username);

		// If the censored username differs from the username
		if (strcmp($temp, $username))
			message($lang_register['Username censor']);
	}

	// Check that the username (or a too similar username) is not already registered
	$result = $db->query('SELECT username FROM '.$db->prefix.'users WHERE username=\''.addslashes($username).'\' OR username=\''.addslashes(preg_replace("/[^\w]/", '', $username)).'\'') or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());

	if ($db->num_rows($result))
	{
		$busy = $db->result($result, 0);
		message($lang_register['Username dupe 1'].' '.htmlspecialchars($busy).'. '.$lang_register['Username dupe 2']);
	}


	// Validate e-mail
	require 'include/email.php';

	if (!is_valid_email($email1))
		message($lang_common['Invalid e-mail']);
	else if ($options['regs_validate'] == '1' && $email1 != $email2)
		message($lang_register['E-mail not match']);

	// Check it it's a banned e-mail address
	if (is_banned_email($email1))
	{
		if ($permissions['allow_banned_email'] == '0')
			message($lang_prof_reg['Banned e-mail']);

		$banned_email = true;	// Used later when we send an alert e-mail
	}

	// Check if someone else already has registered with that e-mail address
	$result = $db->query('SELECT id, username FROM '.$db->prefix.'users WHERE email=\''.$email1.'\'') or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());
	$num_dupes = $db->num_rows($result);

	if ($num_dupes > 0 && $permissions['allow_dupe_email'] == '0')
		message($lang_prof_reg['Dupe e-mail']);


	$hide_email = (isset($_POST['hide_email']) != '1') ? '0' : '1';
	$save_pass = (isset($_POST['save_pass']) != '1') ? '0' : '1';

	// Insert the new user into the database. We have to do this now to get the last inserted id in order to
	// send out an add an alert e-mail with a link to the users profile (phew!)
	$now = time();

	$intial_status = ($options['regs_validate'] == '0') ? 0 : -1;

	// Add the user
	$db->query('INSERT INTO '.$db->prefix.'users (username, password, email, hide_email, save_pass, timezone, style, status, registered) VALUES(\''.addslashes($username).'\', \''.md5($password1).'\', \''.$email1.'\', '.$hide_email.', '.$save_pass.', '.$_POST['timezone'].' ,\''.$options['default_style'].'\' ,'.$intial_status.', '.$now.')') or error('Unable to create user', __FILE__, __LINE__, $db->error());
	$new_uid = $db->insert_id();


	// If we previously found out that the e-mail was banned
	if (isset($banned_email) && $options['mailing_list'] != '')
	{
		$mail_subject = 'Alert - Banned e-mail detected';
		$mail_message = 'User "'.$username.'" registered with banned e-mail address: '.$email1."\r\n\r\n".'User profile: '.$options['base_url'].'/profile.php?id='.$new_uid;
		$mail_extra = 'From: '.$options['board_title'].' Mailer <'.$options['webmaster_email'].'>';

		require 'include/email.php';
		pun_mail($options['mailing_list'], $mail_subject, $mail_message, $mail_extra);
	}

	// If we previously found out that the e-mail was a dupe
	if ($num_dupes && $options['mailing_list'] != '')
	{
		while ($cur_dupe = $db->fetch_assoc($result))
			$dupe_list[] = $cur_dupe['username'];

		$mail_subject = 'Alert - Duplicate e-mail detected';
		$mail_message = 'User "'.$username.'" registered with an e-mail address that also belongs to: '.implode(', ', $dupe_list)."\r\n\r\n".'User profile: '.$options['base_url'].'/profile.php?id='.$new_uid;
		$mail_extra = 'From: '.$options['board_title'].' Mailer <'.$options['webmaster_email'].'>';

		require_once 'include/email.php';
		pun_mail($options['mailing_list'], $mail_subject, $mail_message, $mail_extra);
	}


	// Must the user validate the registration or do we log him/her in right now?
	if ($options['regs_validate'] == '1')
	{
		$mail_subject = $lang_register['Reg e-mail 1'];
		$mail_message = $lang_register['Reg e-mail 2'].' '.$options['base_url'].'/'."\r\n\r\n".$lang_register['Reg e-mail 3'].': '.$username."\r\n".$lang_register['Reg e-mail 4'].': '.$password1."\r\n\r\n".$lang_register['Reg e-mail 5'].' '.$options['base_url'].'/login.php '.$lang_register['Reg e-mail 6']."\r\n\r\n".'/Forum Mailer'."\r\n".'('.$lang_register['Reg e-mail 7'].')';
		$mail_extra = 'From: '.$options['board_title'].' Mailer <'.$options['webmaster_email'].'>';

		pun_mail($email1, $mail_subject, $mail_message, $mail_extra);

		message($lang_register['Reg e-mail 8'].' '.$email1.'. '.$lang_register['Reg e-mail 9'].' <a href="mailto:'.$options['admin_email'].'">'.$options['admin_email'].'</a>.', true);
	}
	else
	{
		$expire = ($save_pass != '0') ? $now + 31536000 : 0;

		setcookie('punbb_cookie', serialize(array($username, md5($password1), $now, $now, $now)), $expire, $cookie_path, $cookie_domain, $cookie_secure);
	}

	redirect('index.php', $lang_register['Reg complete']);
}


else
{
	$page_title = htmlspecialchars($options['board_title']).' / '.$lang_register['Register'];
	$validate_form = true;
	$form_name = 'register';
	$focus_element = 'req_username';
	require 'header.php';

?>
<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>

<table class="punmain" cellspacing="1" cellpadding="4">
	<tr class="punhead">
		<td class="punhead"><?php print $lang_common['Info'] ?></td>
	</tr>
	<tr>
		<td class="puncon2">
			<?php print $lang_register['Desc 1'] ?><br><br>
			<?php print $lang_register['Desc 2'] ?>
		</td>
	</tr>
</table>

<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>

<form method="post" action="register.php?action=register" id="register" onsubmit="return process_form(this)">
	<input type="hidden" name="form_sent" value="1">
	<table class="punmain" cellspacing="1" cellpadding="4">
		<tr class="punhead">
			<td class="punhead" colspan="2"><?php print $lang_register['Register'] ?></td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><b><?php print $lang_common['Username'] ?></b>&nbsp;&nbsp;</td>
			<td class="puncon2">
				<div style="padding-left: 4px"><?php print $lang_register['Username info'] ?></div><br>
				&nbsp;<input type="text" name="req_username" size="25" maxlength="25">
			</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><b><?php print $lang_prof_reg['Password'] ?></b>&nbsp;&nbsp;</td>
			<td class="puncon2">
<?php

	if ($options['regs_validate'] == '1')
		print "\t\t\t\t".'<div style="padding-left: 4px">'.$lang_register['Pass info 2'].'</div>'."\n";
	else
	{

?>
				<div style="padding-left: 4px"><?php print $lang_register['Pass info 1'] ?></div><br>
				&nbsp;<input type="password" name="req_password1" size="16" maxlength="16"><br>
				&nbsp;<input type="password" name="req_password2" size="16" maxlength="16">&nbsp;&nbsp;<?php print $lang_prof_reg['Re-enter pass'] ?>
<?php

	}

?>
			</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><b><?php print $lang_common['E-mail'] ?></b>&nbsp;&nbsp;</td>
			<td class="puncon2">
<?php

	if ($options['regs_validate'] == '1')
		print "\t\t\t\t".'<div style="padding-left: 4px">'.$lang_register['E-mail info 1'].'</div><br>'."\n\t\t\t\t".'&nbsp;<input type="text" name="req_email1" size="50" maxlength="50"><br>'."\n\n\t\t\t\t".'&nbsp;<input type="text" name="req_email2" size="50" maxlength="50">&nbsp;&nbsp;'.$lang_register['Re-enter e-mail'];
	else
		print "\t\t\t\t".'<div style="padding-left: 4px">'.$lang_register['E-mail info 2'].'</div><br>'."\n\t\t\t\t".'&nbsp;<input type="text" name="req_email1" size="50" maxlength="50">';

?>
			</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_prof_reg['Timezone'] ?>&nbsp;&nbsp;</td>
			<td class="puncon2">
				<div style="padding-left: 4px"><?php print $lang_prof_reg['Timezone info'] ?></div><br>
				&nbsp;<select name="timezone">
					<option value="-12"<?php if ($options['server_timezone'] == -12 ) print ' selected' ?>>-12</option>
					<option value="-11"<?php if ($options['server_timezone'] == -11) print ' selected' ?>>-11</option>
					<option value="-10"<?php if ($options['server_timezone'] == -10) print ' selected' ?>>-10</option>
					<option value="-9"<?php if ($options['server_timezone'] == -9 ) print ' selected' ?>>-09</option>
					<option value="-8"<?php if ($options['server_timezone'] == -8 ) print ' selected' ?>>-08 PST</option>
					<option value="-7"<?php if ($options['server_timezone'] == -7 ) print ' selected' ?>>-07 MST</option>
					<option value="-6"<?php if ($options['server_timezone'] == -6 ) print ' selected' ?>>-06 CST</option>
					<option value="-5"<?php if ($options['server_timezone'] == -5 ) print ' selected' ?>>-05 EST</option>
					<option value="-4"<?php if ($options['server_timezone'] == -4 ) print ' selected' ?>>-04 AST</option>
					<option value="-3"<?php if ($options['server_timezone'] == -3 ) print ' selected' ?>>-03 ADT</option>
					<option value="-2"<?php if ($options['server_timezone'] == -2 ) print ' selected' ?>>-02</option>
					<option value="-1"<?php if ($options['server_timezone'] == -1) print ' selected' ?>>-01</option>
					<option value="0"<?php if ($options['server_timezone'] == 0) print ' selected' ?>>00 GMT</option>
					<option value="1"<?php if ($options['server_timezone'] == 1) print ' selected' ?>>+01 CET</option>
					<option value="2"<?php if ($options['server_timezone'] == 2 ) print ' selected' ?>>+02</option>
					<option value="3"<?php if ($options['server_timezone'] == 3 ) print ' selected' ?>>+03</option>
					<option value="4"<?php if ($options['server_timezone'] == 4 ) print ' selected' ?>>+04</option>
					<option value="5"<?php if ($options['server_timezone'] == 5 ) print ' selected' ?>>+05</option>
					<option value="6"<?php if ($options['server_timezone'] == 6 ) print ' selected' ?>>+06</option>
					<option value="7"<?php if ($options['server_timezone'] == 7 ) print ' selected' ?>>+07</option>
					<option value="8"<?php if ($options['server_timezone'] == 8 ) print ' selected' ?>>+08</option>
					<option value="9"<?php if ($options['server_timezone'] == 9 ) print ' selected' ?>>+09</option>
					<option value="10"<?php if ($options['server_timezone'] == 10) print ' selected' ?>>+10</option>
					<option value="11"<?php if ($options['server_timezone'] == 11) print ' selected' ?>>+11</option>
					<option value="12"<?php if ($options['server_timezone'] == 12 ) print ' selected' ?>>+12</option>
					<option value="13"<?php if ($options['server_timezone'] == 13 ) print ' selected' ?>>+13</option>
				</select>
			</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_common['Options'] ?>&nbsp;&nbsp;</td>
			<td class="puncon2">
				<div style="padding-left: 4px"><?php print $lang_prof_reg['Hide e-mail info'] ?></div>
				<input type="checkbox" name="hide_email" value="1">&nbsp;<?php print $lang_prof_reg['Hide e-mail'] ?><br><br>
				<div style="padding-left: 4px"><?php print $lang_prof_reg['Save user/pass info'] ?></div>
				<input type="checkbox" name="save_pass" value="1" checked>&nbsp;<?php print $lang_prof_reg['Save user/pass'] ?>
			</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_common['Actions'] ?>&nbsp;&nbsp;</td>
			<td class="puncon2"><br>&nbsp;&nbsp;<input type="submit" name="register" value="<?php print $lang_common['Submit'] ?>">&nbsp;&nbsp;&nbsp;<a href="javascript:history.go(-1)"><?php print $lang_common['Go back'] ?></a><br><br></td>
		</tr>
	</table>
</form>

<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>
<?php

	require 'footer.php';
}
