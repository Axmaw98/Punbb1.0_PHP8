<?php

require 'config.php';
require 'include/common.php';


if ($cookie['is_guest'] && $permissions['guests_read'] == '0' && !isset($_GET['key']))
	message($lang_common['Login required'].' <a href="login.php">'.$lang_common['Login'].'</a> '.$lang_common['or'].' <a href="register.php">'.$lang_common['register'].'</a>.');


$action = isset($_GET['action']);
$id = intval($_GET['id']);
if ($id < 2)
	message($lang_common['Bad request']);

// Load the profile.php/register.php language file
require 'lang/'.$language.'/'.$language.'_prof_reg.php';

// Load the profile.php language file
require 'lang/'.$language.'/'.$language.'_profile.php';


if ($action == 'change_pass')
{
	if (isset($_GET['key']))
	{
		// If the user is already logged in we shouldn't be here :)
		if (!$cookie['is_guest'])
			header('Location: index.php');

		$key = $_GET['key'];

		$result = $db->query('SELECT activate_string, activate_key FROM '.$db->prefix.'users WHERE id='.$id) or error('Unable to fetch new password', __FILE__, __LINE__, $db->error());
		list($new_password, $new_password_key) = $db->fetch_row($result);

		if (strcmp($key, $new_password_key))
			message($lang_profile['Pass key bad'].' <a href="mailto:'.$options['admin_email'].'">'.$options['admin_email'].'</a>.');
		else
		{
			$db->query('UPDATE '.$db->prefix.'users SET password=\''.$new_password.'\', activate_string=NULL, activate_key=NULL WHERE id='.$id) or error('Unable to update password', __FILE__, __LINE__, $db->error());

			message($lang_profile['Pass updated']);
		}
	}

	// Make sure we are allowed to change this users password
	if ($cur_user['id'] != $id)
	{
		if ($cur_user['status'] < 1)	// A regular user trying to change another users password?
			message($lang_common['No permission']);
		else if ($cur_user['status'] == 1)	// A moderator trying to change an admin/mod's password?
		{
			$result = $db->query('SELECT status FROM '.$db->prefix.'users WHERE id='.$id) or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());
			if (!$db->num_rows($result))
				message($lang_common['Bad request']);
			else if ($db->result($result) > 0)
				message($lang_common['No permission']);
		}
	}

	if (isset($_POST['form_sent']))
	{
		$old_password = un_escape(isset($_POST['req_old_password']));
		$new_password1 = un_escape(isset($_POST['req_new_password1']));
		$new_password2 = un_escape(isset($_POST['req_new_password2']));

		if (strlen($new_password1) < 4)
			message($lang_prof_reg['Pass too short']);
		if ($new_password1 != $new_password2)
			message($lang_prof_reg['Pass not match']);

		$result = $db->query('SELECT password, save_pass FROM '.$db->prefix.'users WHERE id='.$id) or error('Unable to fetch password', __FILE__, __LINE__, $db->error());
		list($correct_password, $save_pass) = $db->fetch_row($result);

		if ($correct_password != NULL && !strcmp($correct_password, md5($old_password)) || $cur_user['status'] > 0)
		{
			$db->query('UPDATE '.$db->prefix.'users SET password=\''.md5($new_password1).'\' WHERE id='.$id) or error('Unable to update password', __FILE__, __LINE__, $db->error());

			if ($cur_user['id'] == $id)
			{
				$expire = ($save_pass == '1') ? time() + 31536000 : 0;

				list(, , $last_action, $last_timeout) = unserialize(un_escape($_COOKIE['punbb_cookie']));

				setcookie('punbb_cookie', serialize(array($cookie['username'], md5($new_password1), $last_action, $last_timeout)), $expire, $cookie_path, $cookie_domain, $cookie_secure);
			}

			redirect('profile.php?id='.$id, $lang_profile['Pass updated redirect']);
		}
		else
			message($lang_profile['Wrong pass']);
	}
	else
	{
		$page_title = htmlspecialchars($options['board_title']).' / '.$lang_profile['Profile'];
		$validate_form = true;
		$form_name = 'change_pass';
		$focus_element = ($cur_user['status'] < 1) ? 'req_old_password' : 'req_new_password1';

		require 'header.php';

?>
<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>

<form method="post" action="profile.php?action=change_pass&amp;id=<?php print $id ?>" id="change_pass" onsubmit="return process_form(this)">
	<input type="hidden" name="form_sent" value="1">
	<table class="punmain" cellspacing="1" cellpadding="4">
		<tr class="punhead">
			<td class="punhead" colspan="2"><?php print $lang_profile['Change pass'] ?></td>
		</tr>
<?php if ($cur_user['status'] < 1): ?>		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><b><?php print $lang_profile['Old pass'] ?></b>&nbsp;&nbsp;</td>
			<td class="puncon2">&nbsp;<input type="password" name="req_old_password" size="16" maxlength="16"></td>
		</tr>
<?php endif; ?>		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><b><?php print $lang_profile['New pass'] ?></b>&nbsp;&nbsp;</td>
			<td class="puncon2">
				&nbsp;<input type="password" name="req_new_password1" size="16" maxlength="16"><br>
				&nbsp;<input type="password" name="req_new_password2" size="16" maxlength="16">&nbsp;&nbsp;<?php print $lang_prof_reg['Re-enter pass'] ?>
			</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_common['Actions'] ?>&nbsp;&nbsp;</td>
			<td class="puncon2"><br>&nbsp;&nbsp;<input type="submit" name="update" value="<?php print $lang_common['Submit'] ?>"><br><br></td>
		</tr>
	</table>
</form>

<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>
<?php

		require 'footer.php';
	}
}


else if ($action == 'change_email')
{
	if ($cookie['is_guest'] || $cur_user['id'] != $id && $cur_user['status'] < 1)
		message($lang_common['No permission']);

	if (isset($_GET['key']))
	{
		$key = $_GET['key'];

		$result = $db->query('SELECT activate_string, activate_key FROM '.$db->prefix.'users WHERE id='.$id) or error('Unable to fetch activation data', __FILE__, __LINE__, $db->error());
		list($new_email, $new_email_key) = $db->fetch_row($result);

		if (strcmp($key, $new_email_key))
			message($lang_profile['E-mail key bad'].' <a href="mailto:'.$options['admin_email'].'">'.$options['admin_email'].'</a>.');
		else
		{
			$db->query('UPDATE '.$db->prefix.'users SET email=\''.$new_email.'\', activate_string=NULL, activate_key=NULL WHERE id='.$id) or error('Unable to update e-mail address', __FILE__, __LINE__, $db->error());

			message($lang_profile['E-mail updated']);
		}
	}
	else if (isset($_POST['form_sent']))
	{
		require 'include/email.php';

		// Validate the email-address
		$new_email = strtolower(trim($_POST['req_new_email']));
		if (!is_valid_email($new_email))
			message($lang_common['Invalid e-mail']);

		// Check it it's a banned e-mail address
		if (is_banned_email($new_email))
		{
			if ($permissions['allow_banned_email'] == '0')
				message($lang_prof_reg['Banned e-mail']);
			else if ($options['mailing_list'] != '')
			{
				$mail_subject = 'Alert - Banned e-mail detected';
				$mail_message = 'User "'.$cur_user['username'].'" changed to banned e-mail address: '.$new_email."\r\n\r\n".'User profile: '.$options['base_url'].'/profile.php?id='.$id;
				$mail_extra = 'From: '.$options['board_title'].' Mailer <'.$options['webmaster_email'].'>';

				require 'include/email.php';
				pun_mail($options['mailing_list'], $mail_subject, $mail_message, $mail_extra);
			}
		}

		// Check if someone else already has registered with that e-mail address
		$result = $db->query('SELECT id, username FROM '.$db->prefix.'users WHERE email=\''.$new_email.'\'') or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());
		$num_dupes = $db->num_rows($result);

		if ($num_dupes)		// We found duplicate e-mail addresses
		{
			if ($permissions['allow_dupe_email'] == '0')
				message($lang_prof_reg['Dupe e-mail']);
			else if ($options['mailing_list'] != '')
			{
				while ($cur_dupe = $db->fetch_assoc($result))
					$dupe_list[] = $cur_dupe['username'];

				$mail_subject = 'Alert - Duplicate e-mail detected';
				$mail_message = 'User "'.$cur_user['username'].'" changed to an e-mail address that also belongs to: '.implode(', ', $dupe_list)."\r\n\r\n".'User profile: '.$options['base_url'].'/profile.php?id='.$id;
				$mail_extra = 'From: '.$options['board_title'].' Mailer <'.$options['webmaster_email'].'>';

				require 'include/email.php';
				pun_mail($options['mailing_list'], $mail_subject, $mail_message, $mail_extra);
			}
		}


		$new_email_key = random_pass(8);

		$db->query('UPDATE '.$db->prefix.'users SET activate_string=\''.$new_email.'\', activate_key=\''.$new_email_key.'\' WHERE id='.$id) or error('Unable to update activation data', __FILE__, __LINE__, $db->error());

		$mail_subject = $lang_profile['Change mail 1'];
		$mail_message = $lang_profile['Change mail 2'].' '.$cur_user['username'].','."\r\n\r\n".$lang_profile['Change mail 3'].' '.$options['base_url'].'/. '.$lang_profile['Change mail 4']."\r\n\r\n".$lang_profile['Change mail 5']."\r\n".$options['base_url'].'/profile.php?action=change_email&id='.$id.'&key='.$new_email_key."\r\n\r\n".'/Forum Mailer'."\r\n".'('.$lang_profile['Change mail 6'].')';
		$mail_extra = 'From: '.$options['board_title'].' Mailer <'.$options['webmaster_email'].'>';

		pun_mail($new_email, $mail_subject, $mail_message, $mail_extra);

		message($lang_profile['Change mail 7'].' '.$new_email.' '.$lang_profile['Change mail 8'].' <a href="mailto:'.$options['admin_email'].'">'.$options['admin_email'].'</a>.');
	}
	else
	{
		$page_title = htmlspecialchars($options['board_title']).' / '.$lang_profile['Profile'];
		$validate_form = true;
		$form_name = 'change_email';
		$focus_element = 'req_new_email';

		require 'header.php';

?>
<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>

<table class="punmain" cellspacing="1" cellpadding="4">
	<tr class="punhead">
		<td class="punhead"><?php print $lang_common['Info'] ?></td>
	</tr>
	<tr>
		<td class="puncon2"><?php print $lang_profile['E-mail instructions'] ?></td>
	</tr>
</table>

<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>

<form method="post" action="profile.php?action=change_email&amp;id=<?php print $id ?>" id="change_email" onsubmit="return process_form(this)">
	<input type="hidden" name="form_sent" value="1">
	<table class="punmain" cellspacing="1" cellpadding="4">
		<tr class="punhead">
			<td class="punhead" colspan="2"><?php print $lang_profile['Change e-mail'] ?></td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><b><?php print $lang_profile['New e-mail'] ?></b>&nbsp;&nbsp;</td>
			<td class="puncon2">&nbsp;<input type="text" name="req_new_email" size="50" maxlength="50"></td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_common['Actions'] ?>&nbsp;&nbsp;</td>
			<td class="puncon2"><br>&nbsp;&nbsp;<input type="submit" name="new_email" value="<?php print $lang_common['Submit'] ?>"><br><br></td>
		</tr>
	</table>
</form>

<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>
<?php

		require 'footer.php';
	}
}


else if ($action == 'upload_avatar' || $action == 'upload_avatar2')
{
    if ($pun_config['o_avatars'] == '0')
    {
        message($lang_profile['Avatars disabled']);
    }

    if ($cookie['is_guest'] || $cur_user['id'] != $id && $cur_user['status'] < PUN_MOD)
    {
        message($lang_common['No permission']);
    }

    if (isset($_POST['form_sent']))
    {
        $uploaded_file = $_FILES['req_file'];

        // Make sure the upload went smooth
        switch ($uploaded_file['error'])
        {
            case UPLOAD_ERR_INI_SIZE: // UPLOAD_ERR_INI_SIZE
            case UPLOAD_ERR_FORM_SIZE: // UPLOAD_ERR_FORM_SIZE
                message($lang_profile['Too large ini']);
                break;

            case UPLOAD_ERR_PARTIAL: // UPLOAD_ERR_PARTIAL
                message($lang_profile['Partial upload']);
                break;

            case UPLOAD_ERR_NO_FILE: // UPLOAD_ERR_NO_FILE
                message($lang_profile['No file']);
                break;

            default:
                // No error occured, but was something actually uploaded?
                if ($uploaded_file['size'] == 0)
                {
                    message($lang_profile['No file']);
                }
                break;
        }

        if (is_uploaded_file($uploaded_file['tmp_name']))
        {
            $allowed_types = array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/png', 'image/x-png');

            if (!in_array($uploaded_file['type'], $allowed_types))
            {
                message($lang_profile['Bad type']);
            }

            [$width, $height, ,] = getimagesize($uploaded_file['tmp_name']);

            if ($width > $pun_config['o_avatars_width'])
            {
                message($lang_profile['Too wide'].' '.$pun_config['o_avatars_width'].' '.$lang_profile['pixels'].'.');
            }
            if ($height > $pun_config['o_avatars_height'])
            {
                message($lang_profile['Too high'].' '.$pun_config['o_avatars_height'].' '.$lang_profile['pixels'].'.');
            }
            if ($uploaded_file['size'] > $pun_config['o_avatars_size'])
            {
                message($lang_profile['Too large'].' '.$pun_config['o_avatars_size'].' '.$lang_profile['bytes'].'.');
            }

            if ($uploaded_file['type'] == 'image/gif')
            {
                $temp = @move_uploaded_file($uploaded_file['tmp_name'], $pun_config['o_avatars_dir'].'/'.$id.'.gif');
                @chmod($pun_config['o_avatars_dir'].'/'.$id.'.gif', 0644);
                @unlink($pun_config['o_avatars_dir'].'/'.$id.'.jpg');
                @unlink($pun_config['o_avatars_dir'].'/'.$id.'.png');
            }
            else if ($uploaded_file['type'] == 'image/jpeg' || $uploaded_file['type'] == 'image/pjpeg')
            {
                $temp = @move_uploaded_file($uploaded_file['tmp_name'], $pun_config['o_avatars_dir'].'/'.$id.'.jpg');
                @chmod($pun_config['o_avatars_dir'].'/'.$id.'.jpg', 0644);
                @unlink($pun_config['o_avatars_dir'].'/'.$id.'.gif');
                @unlink($pun_config['o_avatars_dir'].'/'.$id.'.png');
            }
						else if ($uploaded_file['type'] == 'image/png' || $uploaded_file['type'] == 'image/x-png')
{
    $temp = @move_uploaded_file($uploaded_file['tmp_name'], $pun_config['o_avatars_dir'].'/'.$id.'.png');
    @chmod($pun_config['o_avatars_dir'].'/'.$id.'.png', 0644);
    @unlink($pun_config['o_avatars_dir'].'/'.$id.'.gif');
    @unlink($pun_config['o_avatars_dir'].'/'.$id.'.jpg');

    if (!$temp)
        message($lang_profile['Move failed'].' <a href="mailto:'.$pun_config['o_admin_email'].'">'.$pun_config['o_admin_email'].'</a>.');
}

else {
    message($lang_profile['Unknown failure']);
}

// Enable use_avatar (seems sane since the user just uploaded an avatar)
$result = $db->query('UPDATE '.$db->prefix.'users SET use_avatar=1 WHERE id='.$id);
if (!$result) {
    error('Unable to update avatar state', __FILE__, __LINE__, $db->error());
}

redirect('profile.php?id='.$id, $lang_profile['Avatar upload redirect']);
}
else {
    $page_title = pun_htmlspecialchars($pun_config['o_board_title']).' / '.$lang_profile['Profile'];
    $validate_form = true;
    $element_names = array('req_file' => $lang_profile['File']);
    $form_name = 'upload_avatar';
    $focus_element = 'req_file';
    require $pun_root.'header.php';
}

?>
<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>

<table class="punmain" cellspacing="1" cellpadding="4">
	<tr class="punhead">
		<td class="punhead"><?php print $lang_common['Info'] ?></td>
	</tr>
	<tr>
		<td class="puncon2"><?php print $lang_profile['Avatar desc'].' '.$options['avatars_width'].' x '.$options['avatars_height'].' '.$lang_profile['pixels'].' '.$lang_common['and'].' '.$options['avatars_size'].' '.$lang_profile['bytes'].' ('.ceil($options['avatars_size'] / 1024) ?> KB).</td>
	</tr>
</table>

<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>

<form method="post" enctype="multipart/form-data" action="profile.php?action=upload_avatar2&amp;id=<?php print $id ?>" id="upload_avatar" onsubmit="return process_form(this)">
	<input type="hidden" name="form_sent" value="1">
	<input type="hidden" name="MAX_FILE_SIZE" value="<?php print $options['avatars_size'] ?>">
	<table class="punmain" cellspacing="1" cellpadding="4">
		<tr class="punhead">
			<td class="punhead" colspan="2"><?php print $lang_profile['Upload avatar'] ?></td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><b><?php print $lang_profile['File'] ?></b>&nbsp;&nbsp;</td>
			<td class="puncon2">&nbsp;<input name="req_file" type="file" size="40"></td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_common['Actions'] ?>&nbsp;&nbsp;</td>
			<td class="puncon2"><br>&nbsp;&nbsp;<input type="submit" name="upload" value="<?php print $lang_profile['Upload'] ?>"><br><br></td>
		</tr>
	</table>
</form>

<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>
<?php

		require 'footer.php';
	}
}


else if (isset($_POST['update_status']))
{
	if ($cur_user['status'] < 2)
		message($lang_common['No permission']);

	confirm_referer('profile.php');

	$db->query('UPDATE '.$db->prefix.'users SET status='.$_POST['status'].' WHERE id='.$id) or error('Unable to update status', __FILE__, __LINE__, $db->error());

	redirect('profile.php?id='.$id, $lang_profile['Update status redirect']);
}


else if (isset($_POST['update_forums']))
{
	if ($cur_user['status'] < 2)
		message($lang_common['No permission']);

	confirm_referer('profile.php');

	// Get the username of the user we are processing
	$result = $db->query('SELECT username FROM '.$db->prefix.'users WHERE id='.$id) or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());
	$username = $db->result($result, 0);

	$moderator_in = (isset($_POST['moderator_in'])) ? array_keys($_POST['moderator_in']) : array();

	// Loop through all forums
	$result = $db->query('SELECT id, moderators FROM '.$db->prefix.'forums') or error('Unable to fetch forum list', __FILE__, __LINE__, $db->error());

	while ($cur_forum = $db->fetch_assoc($result))
	{
		$cur_moderators = ($cur_forum['moderators'] != '') ? unserialize($cur_forum['moderators']) : array();

		// If the user should have moderator access (and he/she doesn't already have it)
		if (in_array($cur_forum['id'], $moderator_in) && !in_array($id, $cur_moderators))
		{
			$cur_moderators[$username] = $id;
			ksort($cur_moderators);

			$db->query('UPDATE '.$db->prefix.'forums SET moderators=\''.addslashes(serialize($cur_moderators)).'\' WHERE id='.$cur_forum['id']) or error('Unable to update forum', __FILE__, __LINE__, $db->error());
		}
		// If the user shouldn't have moderator access (and he/she already has it)
		else if (!in_array($cur_forum['id'], $moderator_in) && in_array($id, $cur_moderators))
		{
			unset($cur_moderators[$username]);
			$cur_moderators = (!empty($cur_moderators)) ? '\''.addslashes(serialize($cur_moderators)).'\'' : 'NULL';

			$db->query('UPDATE '.$db->prefix.'forums SET moderators='.$cur_moderators.' WHERE id='.$cur_forum['id']) or error('Unable to update forum', __FILE__, __LINE__, $db->error());
		}
	}

	redirect('profile.php?id='.$id, $lang_profile['Update forums redirect']);
}


else if (isset($_POST['ban']))
{
	if ($cur_user['status'] < 1)
		message($lang_common['No permission']);

	redirect('admin_bans.php?add_ban='.$id, $lang_profile['Ban redirect']);
}


else if (isset($_POST['delete']) || isset($_POST['comply']))
{
	if ($cur_user['status'] < 2)
		message($lang_common['No permission']);

	confirm_referer('profile.php');

	if (isset($_POST['comply']))
	{
		// If the user is a moderator or an administrator, we remove him/her from the moderator list in all forums as well
		$result = $db->query('SELECT username, status FROM '.$db->prefix.'users WHERE id='.$id) or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());
		list($username, $status) = $db->fetch_row($status);

		if ($status > 0)
		{
			$result = $db->query('SELECT id, moderators FROM '.$db->prefix.'forums') or error('Unable to fetch forum list', __FILE__, __LINE__, $db->error());

			while ($cur_forum = $db->fetch_assoc($result))
			{
				$cur_moderators = ($cur_forum['moderators'] != '') ? unserialize($cur_forum['moderators']) : array();

				if (in_array($id, $cur_moderators))
				{
					unset($cur_moderators[$username]);
					$cur_moderators = (!empty($cur_moderators)) ? '\''.addslashes(serialize($cur_moderators)).'\'' : 'NULL';

					$db->query('UPDATE '.$db->prefix.'forums SET moderators='.$cur_moderators.' WHERE id='.$cur_forum['id']) or error('Unable to update forum', __FILE__, __LINE__, $db->error());
				}
			}
		}

		// Delete the user
		$db->query('DELETE FROM '.$db->prefix.'users WHERE id='.$id) or error('Unable to delete user', __FILE__, __LINE__, $db->error());

		// Set all his/her posts to guest
		$db->query('UPDATE '.$db->prefix.'posts SET poster_id=1 WHERE poster_id='.$id) or error('Unable to update posts', __FILE__, __LINE__, $db->error());

		redirect('index.php', $lang_profile['User delete redirect']);
	}
	else
	{
		$page_title = htmlspecialchars($options['board_title']).' / '.$lang_profile['Profile'];
		require 'header.php';

?>
<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>

<form method="post" action="profile.php?id=<?php print $id ?>">
	<table class="punmain" cellspacing="1" cellpadding="4">
		<tr class="punhead">
			<td class="punhead"><?php print $lang_profile['Confirm delete user'] ?></td>
		</tr>
		<tr>
			<td class="puncon2">
				<br>&nbsp;<?php print $lang_profile['Are you sure'] ?><br><br>
				&nbsp;<?php print $lang_profile['Warning'] ?><br><br>
				&nbsp;<input type="submit" name="comply" value="<?php print $lang_profile['OK'] ?>">&nbsp;&nbsp;&nbsp;<a href="javascript:history.go(-1)"><?php print $lang_common['Go back'] ?></a><br><br>
			</td>
		</tr>
	</table>
</form>

<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>
<?php

		require 'footer.php';
	}
}


else if (isset($_POST['form_sent']))
{
	if ($cur_user['id'] != $id && $cur_user['status'] < 1)
		message($lang_common['No permission']);

	$form = $_POST['form'];


	if ($cur_user['status'] > 0)
	{
		confirm_referer('profile.php');

		$username = trim(un_escape($_POST['username']));
		$old_username = trim(un_escape($_POST['old_username']));

		if (strlen($username) < 2)
			message($lang_prof_reg['Username too short']);
		else if (!strcasecmp($username, 'Guest') || !strcasecmp($username, $lang_common['Guest']))
			message($lang_prof_reg['Username guest']);
		else if (preg_match('/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/', $username))
			message($lang_prof_reg['Username IP']);
		else if (preg_match('#\[b\]|\[/b\]|\[u\]|\[/u\]|\[i\]|\[/i\]|\[color|\[/color\]|\[quote\]|\[/quote\]|\[code\]|\[/code\]|\[img\]|\[/img\]|\[url|\[/url\]|\[email|\[/email\]#i', $username))
			message($lang_prof_reg['Username BBCode']);

		// Check that the username is not already registered
		$result = $db->query('SELECT 1 FROM '.$db->prefix.'users WHERE username=\''.addslashes($username).'\' AND id!='.$id) or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());
		if ($db->num_rows($result))
			message($lang_profile['Dupe username']);
	}


	// Make sure all newlines are \n and not \r\n or \r
	$signature = str_replace("\r", "\n", str_replace("\r\n", "\n", trim(un_escape($_POST['signature']))));

	// Validate signature
	if (strlen($signature) > $permissions['sig_length'])
		message($lang_prof_reg['Sig too long'].' '.$permissions['sig_length'].' '.$lang_prof_reg['characters'].'.');
	else if (substr_count($signature, "\n") > ($permissions['sig_lines']-1))
		message($lang_prof_reg['Sig too many lines'].' '.$permissions['sig_lines'].' '.$lang_prof_reg['lines'].'.');
	else if ($signature && $permissions['sig_all_caps'] == '0' && !ereg( "[[:lower:]]", $signature))
		message($lang_prof_reg['Sig caps']);

	if ($permissions['sig_bbcode'] == '1')
	{
		// Change all BBCodes to lower case (this way a lot of regex searches can be case sensitive)
		$a = array('[B]', '[I]', '[U]', '[/B]', '[/I]', '[/U]');
		$b = array('[b]', '[i]', '[u]', '[/b]', '[/i]', '[/u]');
		$message = str_replace($a, $b, isset($message));

		$a = array('#\[colou?r=([a-zA-Z]*|\#?[0-9a-fA-F]{6})\]#i', '#\[/colou?r\]#i', '#\[img\]#i', '#\[/img\]#i', '#\[email\]#i', '#\[email=#i', '#\[/email\]#i', '#\[url\]#i', '#\[url=#i', '#\[/url\]#i');
		$b = array('[color=\\1]', '[/color]', '[img]', '[/img]', '[email]', '[email=', '[/email]', '[url]', '[url=', '[/url]');
		$message = preg_replace($a, $b, isset($message));

		if (preg_match('/\[quote\]|\[\/quote\]|\[code\]|\[\/code\]/i', $signature))
			message($lang_prof_reg['Signature quote/code']);
	}


	if ($options['regs_validate'] == '0' || $cur_user['status'] > 0)
	{
		require 'include/email.php';

		// Validate the email-address
		$email = strtolower(trim($_POST['req_email']));
		if (!is_valid_email($email))
			message($lang_common['Invalid e-mail']);
	}


	// Add http:// if the URL doesn't contain it already
	if ($form['url'] != '' && !stristr($form['url'], 'http://'))
		$form['url'] = 'http://'.$form['url'];

	// If the ICQ UIN contains anything other than digits it's invalid
	if ($form['icq'] != '' && preg_match('/[^0-9]/', $form[icq]))
		message($lang_prof_reg['Bad ICQ']);


	if ($form['disp_topics'] != '' && intval($form['disp_topics']) < 3) $form['disp_topics'] = 3;
	if ($form['disp_topics'] != '' && intval($form['disp_topics']) > 75) $form['disp_topics'] = 75;
	if ($form['disp_posts'] != '' && intval($form['disp_posts']) < 3) $form['disp_posts'] = 3;
	if ($form['disp_posts'] != '' && intval($form['disp_posts']) > 75) $form['disp_posts'] = 75;

	if (isset($form['use_avatar']) != '1') $form['use_avatar'] = '0';
	if (isset($form['hide_email']) != '1') $form['hide_email'] = '0';
	if ($form['save_pass'] != '1') $form['save_pass'] = '0';
	if ($form['smilies'] != '1') $form['smilies'] = '0';
	if ($form['show_img'] != '1') $form['show_img'] = '0';
	if ($form['show_sig'] != '1') $form['show_sig'] = '0';
	if ($form['link_to_new_win'] != '1') $form['link_to_new_win'] = '0';


	// Singlequotes around non-empty values and NULL for empty values
	foreach ($form as $key => $input)
	{
		$value = ($input != '') ? '\''.escape($input).'\'' : 'NULL';

		$temp[] = $key.'='.$value;
	}


	if ($cur_user['status'] < 1)
	{
		if ($permissions['users_set_title'] == '1')
		{
			$user_title = trim($_POST['title']);

			if ($user_title != '')
			{
				// A list of words that the title may not contain
				// If $language == 'en', there will be some duplicates, but it's not the end of the world
				$forbidden = array('Member', 'Moderator', 'Administrator', 'Banned', 'Guest', $lang_common['Member'], $lang_common['Moderator'], $lang_common['Administrator'], $lang_common['Banned'], $lang_common['Guest']);

				if (in_array($user_title, $forbidden))
					message($lang_profile['Forbidden title']);
			}

			$user_title_sql = ($user_title != '') ? 'title=\''.escape($user_title).'\', ' : 'title=NULL, ';
		}

		$email_sql = ($options['regs_validate'] == '0') ? 'email=\''.$email.'\', ' : '';

		$db->query('UPDATE '.$db->prefix.'users SET '.$email_sql.$user_title_sql.'signature=\''.addslashes($signature).'\', '.implode(',', $temp).' WHERE id='.$id) or error('Unable to update profile', __FILE__, __LINE__, $db->error());
	}
	else
	{
		$user_title = trim($_POST['title']);
		$admin_note = trim($_POST['admin_note']);

		$user_title = ($user_title != '') ? '\''.escape($user_title).'\'' : 'NULL';
		$admin_note = ($admin_note != '') ? '\''.escape($admin_note).'\'' : 'NULL';

		// We only allow administrators to update the post counter
		$posts_sql = ($cur_user['status'] > 1) ? 'num_posts='.intval($_POST['num_posts']).', ' : '';

		$db->query('UPDATE '.$db->prefix.'users SET username=\''.addslashes($username).'\', email=\''.$email.'\', title='.$user_title.', signature=\''.addslashes($signature).'\', '.implode(',', $temp).', '.$posts_sql.'admin_note='.$admin_note.' WHERE id='.$id) or error('Unable to update profile', __FILE__, __LINE__, $db->error());

		// If we changed the username we have to alter "poster" and "last_poster" for any posts, topics and forums
		if (strcmp($username, $old_username))
		{
			$db->query('UPDATE '.$db->prefix.'posts SET poster=\''.addslashes($username).'\' WHERE poster_id='.$id) or error('Unable to update posts', __FILE__, __LINE__, $db->error());
			$db->query('UPDATE '.$db->prefix.'topics SET poster=\''.addslashes($username).'\' WHERE poster=\''.addslashes($old_username).'\'') or error('Unable to update topics', __FILE__, __LINE__, $db->error());
			$db->query('UPDATE '.$db->prefix.'topics SET last_poster=\''.addslashes($username).'\' WHERE last_poster=\''.addslashes($old_username).'\'') or error('Unable to update topics', __FILE__, __LINE__, $db->error());
			$db->query('UPDATE '.$db->prefix.'forums SET last_poster=\''.addslashes($username).'\' WHERE last_poster=\''.addslashes($old_username).'\'') or error('Unable to update forums', __FILE__, __LINE__, $db->error());
		}
	}

	redirect('profile.php?id='.$id, $lang_profile['Profile redirect']);
}


else
{
	$result = $db->query('SELECT username, email, title, realname, url, icq, aim, yahoo, location, use_avatar, signature, disp_topics, disp_posts, hide_email, save_pass, smilies, show_img, show_sig, link_to_new_win, timezone, style, num_posts, status, last_post, registered, admin_note FROM '.$db->prefix.'users WHERE id='.$id) or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result))
		message($lang_common['Bad request']);

	$user = $db->fetch_assoc($result);

	$last_post = format_time($user['last_post']);

	if ($user['signature'] != NULL)
	{
		require 'include/parser.php';

		$parsed_signature = parse_signature($user['signature']);
	}

	// Are we viewing our someone elses profile? (and are we not an admin/moderator)
	if (isset($cur_user['id']) != $id && isset($cur_user['status']) < 1)
	{
		if ($user['hide_email'] != '1')
			$email_field = '<a href="mailto:'.$user['email'].'">'.$user['email'].'</a>';
		else
			$email_field = $lang_profile['Not displayed'];

		$user_title_field = get_title($user);

		if ($user['url'] != '')
		{
			$user['url'] = htmlspecialchars($user['url']);

			if ($options['censoring'] == '1')
				$user['url'] = censor_words($user['url']);

			if ($cur_user['link_to_new_win'] != '0')
				$url = '<a href="'.$user['url'].'" target="_blank">'.$user['url'].'</a>';
			else
				$url = '<a href="'.$user['url'].'">'.$user['url'].'</a>';
		}

		if ($options['avatars'] == '1')
		{
			if ($user['use_avatar'] == '1')
			{
				if ($img_size = @getimagesize($options['avatars_dir'].'/'.$id.'.gif'))
					$avatar_field = '<img class="punavatar" src="'.$options['avatars_dir'].'/'.$id.'.gif" '.$img_size[3].' alt="">';
				else if ($img_size = @getimagesize($options['avatars_dir'].'/'.$id.'.jpg'))
					$avatar_field = '<img class="punavatar" src="'.$options['avatars_dir'].'/'.$id.'.jpg" '.$img_size[3].' alt="">';
				else if ($img_size = @getimagesize($options['avatars_dir'].'/'.$id.'.png'))
					$avatar_field = '<img class="punavatar" src="'.$options['avatars_dir'].'/'.$id.'.png" '.$img_size[3].' alt="">';
			}
			else
				$avatar_field = $lang_profile['No avatar'];
		}


		$page_title = htmlspecialchars($options['board_title']).' / '.$lang_profile['Profile'];
		require 'header.php';

?>
<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>

<table class="punmain" cellspacing="1" cellpadding="4">
	<tr class="punhead">
		<td colspan="2">
			<table class="punplain" cellspacing="0" cellpadding="0">
				<tr>
					<td class="punhead" style="white-space: nowrap"><?php print $lang_profile['Profile'] ?></td>
					<td class="punright"><a href="search.php?action=show_user&amp;user_id=<?php print $id ?>"><?php print $lang_profile['Show posts'] ?></a></td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_common['Username'] ?>&nbsp;&nbsp;</td>
		<td class="puncon2">&nbsp;<?php print htmlspecialchars($user['username']) ?></td>
	</tr>
	<tr>
		<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_common['E-mail'] ?>&nbsp;&nbsp;</td>
		<td class="puncon2">&nbsp;<?php print $email_field ?></td>
	</tr>
	<tr>
		<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_common['Title'] ?>&nbsp;&nbsp;</td>
		<td class="puncon2">&nbsp;<?php print ($options['censoring'] == '1') ? censor_words($user_title_field) : $user_title_field; ?></td>
	</tr>
	<tr>
		<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_profile['Realname'] ?>&nbsp;&nbsp;</td>
		<td class="puncon2">&nbsp;<?php print htmlspecialchars(($options['censoring'] == '1') ? censor_words($user['realname']) : $user['realname']) ?></td>
	</tr>
	<tr>
		<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_profile['Website'] ?>&nbsp;&nbsp;</td>
		<td class="puncon2">&nbsp;<?php print isset($url) ?></td>
	</tr>
	<tr>
		<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_profile['ICQ'] ?>&nbsp;&nbsp;</td>
		<td class="puncon2">&nbsp;<?php print htmlspecialchars(($options['censoring'] == '1') ? censor_words($user['icq']) : $user['icq']) ?></td>
	</tr>
	<tr>
		<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_profile['AOL IM'] ?>&nbsp;&nbsp;</td>
		<td class="puncon2">&nbsp;<?php print htmlspecialchars(($options['censoring'] == '1') ? censor_words($user['aim']) : $user['aim']) ?></td>
	</tr>
	<tr>
		<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_profile['Yahoo'] ?>&nbsp;&nbsp;</td>
		<td class="puncon2">&nbsp;<?php print htmlspecialchars(($options['censoring'] == '1') ? censor_words($user['yahoo']) : $user['yahoo']) ?></td>
	</tr>
	<tr>
		<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_profile['Location'] ?>&nbsp;&nbsp;</td>
		<td class="puncon2">&nbsp;<?php print htmlspecialchars(($options['censoring'] == '1') ? censor_words($user['location']) : $user['location']) ?></td>
	</tr>
<?php if ($options['avatars'] == '1'): ?>	<tr>
		<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_profile['Avatar'] ?>&nbsp;&nbsp;</td>
		<td class="puncon2">&nbsp;<?php print isset($avatar_field) ?></td>
	</tr>
<?php endif; ?>	<tr>
		<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_profile['Signature'] ?>&nbsp;&nbsp;</td>
		<td class="puncon2">
			<table class="punplain" style="width: 80%" cellspacing="0" cellpadding="4">
				<tr>
					<td>
						<?php print (isset($parsed_signature) != '') ? $parsed_signature."\n" : $lang_profile['No sig']."\n"; ?>
					</td>
				</tr>
			</table>
		</td>
	</tr>
<?php if ($options['show_post_count'] == '1' || $cur_user['status'] > 0): ?>	<tr>
		<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_common['Posts'] ?>&nbsp;&nbsp;</td>
		<td class="puncon2">&nbsp;<?php print $user['num_posts'] ?></td>
	</tr>
<?php endif; ?>	<tr>
		<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_common['Last post'] ?>&nbsp;&nbsp;</td>
		<td class="puncon2">&nbsp;<?php print $last_post ?></td>
	</tr>
	<tr>
		<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_common['Registered'] ?>&nbsp;&nbsp;</td>
		<td class="puncon2">&nbsp;<?php print format_time($user['registered'], true) ?></td>
	</tr>
</table>

<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>
<?php

	}
	else
	{
		$page_title = htmlspecialchars($options['board_title']).' / '.$lang_profile['Profile'];
		$validate_form = true;
		require 'header.php';

		if ($cur_user['status'] > 0)
		{
			$username_field = '<input type="hidden" name="old_username" value="'.htmlspecialchars($user['username']).'"><input type="text" name="username" value="'.htmlspecialchars($user['username']).'" size="25" maxlength="25">';
			$email_field = '<input type="text" name="req_email" value="'.$user['email'].'" size="40" maxlength="50">';
			$user_title_field = '<input type="text" name="title" value="'.$user['title'].'" size="30" maxlength="50">&nbsp;&nbsp;'.isset($lang_prof_reg['Leave blank']);
		}
		else
		{
			$username_field = htmlspecialchars($user['username']);

			if ($options['regs_validate'] == '1')
				$email_field = $user['email'].'&nbsp;-&nbsp;<a href="profile.php?action=change_email&amp;id='.$id.'">'.$lang_profile['Change e-mail'].'</a>';
			else
				$email_field = '<input type="text" name="req_email" value="'.$user['email'].'" size="40" maxlength="50">';

			if ($permissions['users_set_title'] == '1')
				$user_title_field = '<input type="text" name="title" value="'.$user['title'].'" size="30" maxlength="50">&nbsp;&nbsp;'.$lang_prof_reg['Leave blank'];
			else
			{
				$user_title_field = get_title($user);

				if ($options['censoring'] == '1')
					$user_title_field = censor_words($user_title_field);
			}
		}

		if ($img_size = @getimagesize($options['avatars_dir'].'/'.$id.'.gif'))
			$avatar_field = '<img class="punavatar" src="'.$options['avatars_dir'].'/'.$id.'.gif" '.$img_size[3].' alt=""><br>&nbsp;<a href="profile.php?action=upload_avatar&amp;id='.$id.'">'.$lang_profile['Change avatar'].'</a>';
		else if ($img_size = @getimagesize($options['avatars_dir'].'/'.$id.'.jpg'))
			$avatar_field = '<img class="punavatar" src="'.$options['avatars_dir'].'/'.$id.'.jpg" '.$img_size[3].' alt=""><br>&nbsp;<a href="profile.php?action=upload_avatar&amp;id='.$id.'">'.$lang_profile['Change avatar'].'</a>';
		else if ($img_size = @getimagesize($options['avatars_dir'].'/'.$id.'.png'))
			$avatar_field = '<img class="punavatar" src="'.$options['avatars_dir'].'/'.$id.'.png" '.$img_size[3].' alt=""><br>&nbsp;<a href="profile.php?action=upload_avatar&amp;id='.$id.'">'.$lang_profile['Change avatar'].'</a>';
		else
			$avatar_field = '<a href="profile.php?action=upload_avatar&amp;id='.$id.'">'.$lang_profile['Upload avatar'].'</a>';

		if ($cur_user['status'] < 2)
			$posts_field = $user['num_posts'];
		else
			$posts_field = '<input type="text" name="num_posts" value="'.$user['num_posts'].'" size="8" maxlength="8">';

		if ($user['signature'] != '')
			$preview = '&nbsp;'.$lang_profile['Sig preview'].'<br>'."\n\t\t\t\t".'&nbsp;_______________________________________<br>'."\n\t\t\t\t".'<table class="punplain" style="width: 80%" cellspacing="0" cellpadding="4">'."\n\t\t\t\t\t".'<tr>'."\n\t\t\t\t\t\t".'<td>'.$parsed_signature.'</td>'."\n\t\t\t\t\t".'</tr>'."\n\t\t\t\t".'</table><br>'."\n";
		else
			$preview = '&nbsp;'.$lang_profile['Sig preview'].'<br>'."\n\t\t\t\t".'&nbsp;_______________________________________<br>'."\n\t\t\t\t".'&nbsp;'.$lang_profile['No sig'].'<br><br>'."\n";

?>
<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>

<form method="post" action="profile.php?id=<?php print $id ?>" onsubmit="return process_form(this)">
	<input type="hidden" name="form_sent" value="1">
	<table class="punmain" cellspacing="1" cellpadding="4">
		<tr class="punhead">
			<td colspan="2">
				<table class="punplain" cellspacing="0" cellpadding="0">
					<tr>
						<td class="punhead" style="white-space: nowrap"><?php print $lang_profile['Profile'] ?></td>
						<td class="punright"><a href="search.php?action=show_user&amp;user_id=<?php print $id ?>"><?php print $lang_profile['Show posts'] ?></a></td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><b><?php print $lang_common['Username'] ?></b>&nbsp;&nbsp;</td>
			<td class="puncon2">&nbsp;<?php print $username_field ?></td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><b><?php print $lang_prof_reg['Password'] ?></b>&nbsp;&nbsp;</td>
			<td class="puncon2">&nbsp;<a href="profile.php?action=change_pass&amp;id=<?php print $id ?>"><?php print $lang_profile['Change pass'] ?></a></td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><b><?php print $lang_common['E-mail'] ?></b>&nbsp;&nbsp;</td>
			<td class="puncon2">&nbsp;<?php print $email_field ?></td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_common['Title'] ?>&nbsp;&nbsp;</td>
			<td class="puncon2">&nbsp;<?php print $user_title_field ?></td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_profile['Realname'] ?>&nbsp;&nbsp;</td>
			<td class="puncon2">&nbsp;<input type="text" name="form[realname]" value="<?php print htmlspecialchars($user['realname']) ?>" size="40" maxlength="40"></td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_profile['Website'] ?>&nbsp;&nbsp;</td>
			<td class="puncon2">&nbsp;<input type="text" name="form[url]" value="<?php print htmlspecialchars($user['url']) ?>" size="50" maxlength="80"></td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_profile['ICQ'] ?>&nbsp;&nbsp;</td>
			<td class="puncon2">&nbsp;<input type="text" name="form[icq]" value="<?php print $user['icq'] ?>" size="12" maxlength="12"></td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_profile['AOL IM'] ?>&nbsp;&nbsp;</td>
			<td class="puncon2">&nbsp;<input type="text" name="form[aim]" value="<?php print htmlspecialchars($user['aim']) ?>" size="20" maxlength="20"></td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_profile['Yahoo'] ?>&nbsp;&nbsp;</td>
			<td class="puncon2">&nbsp;<input type="text" name="form[yahoo]" value="<?php print htmlspecialchars($user['yahoo']) ?>" size="20" maxlength="20"></td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_profile['Location'] ?>&nbsp;&nbsp;</td>
			<td class="puncon2">&nbsp;<input type="text" name="form[location]" value="<?php print htmlspecialchars($user['location']) ?>" size="30" maxlength="30"></td>
		</tr>
<?php if ($options['avatars'] == '1'): ?>		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_profile['Avatar'] ?>&nbsp;&nbsp;</td>
			<td class="puncon2">
				<div style="padding-left: 4px"><?php print $lang_profile['Avatar info'] ?></div><br>
				&nbsp;<?php print $avatar_field ?><br><br>
				<input type="checkbox" name="form[use_avatar]" value="1"<?php if ($user['use_avatar'] == '1') print ' checked' ?>>&nbsp;<?php print $lang_profile['Use avatar'] ?>
			</td>
		</tr>
<?php endif; ?>		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap">
				<?php print isset($lang_prof_reg['Signature']) ?>&nbsp;&nbsp;<br><br>
				HTML: <?php print ($permissions['sig_html'] == '1') ? $lang_common['on'] : $lang_common['off']; ?>&nbsp;&nbsp;<br>
				<a href="help.php" target="_blank">BBCode</a>: <?php print ($permissions['sig_bbcode'] == '1') ? $lang_common['on'] : $lang_common['off']; ?>&nbsp;&nbsp;<br>
				<a href="help.php" target="_blank">[img] tag</a>: <?php print ($permissions['sig_img_tag'] == '1') ? $lang_common['on'] : $lang_common['off']; ?>&nbsp;&nbsp;<br>
				<a href="help.php" target="_blank">Smilies</a>: <?php print ($options['smilies_sig'] == '1') ? $lang_common['on'] : $lang_common['off']; ?>&nbsp;&nbsp;<br>
				<?php print $lang_profile['Sig max length'] ?>: <?php print $permissions['sig_length'] ?>&nbsp;&nbsp;<br>
				<?php print $lang_profile['Sig max lines'] ?>: <?php print $permissions['sig_lines'] ?>&nbsp;&nbsp;
			</td>
			<td class="puncon2">
				<div style="padding-left: 4px"><?php print $lang_profile['Signature info'] ?></div><br>
				&nbsp;<textarea name="signature" rows="7" cols="65"><?php print htmlspecialchars($user['signature']) ?></textarea><br><br>
				<?php print $preview ?>
			</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_profile['Topics per page'] ?>&nbsp;&nbsp;</td>
			<td class="puncon2">&nbsp;<input type="text" name="form[disp_topics]" value="<?php print $user['disp_topics'] ?>" size="3" maxlength="3">&nbsp;&nbsp;<?php print $lang_profile['Leave blank'] ?></td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_profile['Posts per page'] ?>&nbsp;&nbsp;</td>
			<td class="puncon2">&nbsp;<input type="text" name="form[disp_posts]" value="<?php print $user['disp_posts'] ?>" size="3" maxlength="3">&nbsp;&nbsp;<?php print $lang_profile['Leave blank'] ?></td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_prof_reg['Timezone'] ?>&nbsp;&nbsp;</td>
			<td class="puncon2">
				<div style="padding-left: 4px"><?php print $lang_prof_reg['Timezone info'] ?></div><br>
				&nbsp;<select name="form[timezone]">
					<option value="-12"<?php if ($user['timezone'] == -12 ) print ' selected' ?>>-12</option>
					<option value="-11"<?php if ($user['timezone'] == -11) print ' selected' ?>>-11</option>
					<option value="-10"<?php if ($user['timezone'] == -10) print ' selected' ?>>-10</option>
					<option value="-9"<?php if ($user['timezone'] == -9 ) print ' selected' ?>>-09</option>
					<option value="-8"<?php if ($user['timezone'] == -8 ) print ' selected' ?>>-08 PST</option>
					<option value="-7"<?php if ($user['timezone'] == -7 ) print ' selected' ?>>-07 MST</option>
					<option value="-6"<?php if ($user['timezone'] == -6 ) print ' selected' ?>>-06 CST</option>
					<option value="-5"<?php if ($user['timezone'] == -5 ) print ' selected' ?>>-05 EST</option>
					<option value="-4"<?php if ($user['timezone'] == -4 ) print ' selected' ?>>-04 AST</option>
					<option value="-3"<?php if ($user['timezone'] == -3 ) print ' selected' ?>>-03 ADT</option>
					<option value="-2"<?php if ($user['timezone'] == -2 ) print ' selected' ?>>-02</option>
					<option value="-1"<?php if ($user['timezone'] == -1) print ' selected' ?>>-01</option>
					<option value="0"<?php if ($user['timezone'] == 0) print ' selected' ?>>00 GMT</option>
					<option value="1"<?php if ($user['timezone'] == 1) print ' selected' ?>>+01 CET</option>
					<option value="2"<?php if ($user['timezone'] == 2 ) print ' selected' ?>>+02</option>
					<option value="3"<?php if ($user['timezone'] == 3 ) print ' selected' ?>>+03</option>
					<option value="4"<?php if ($user['timezone'] == 4 ) print ' selected' ?>>+04</option>
					<option value="5"<?php if ($user['timezone'] == 5 ) print ' selected' ?>>+05</option>
					<option value="6"<?php if ($user['timezone'] == 6 ) print ' selected' ?>>+06</option>
					<option value="7"<?php if ($user['timezone'] == 7 ) print ' selected' ?>>+07</option>
					<option value="8"<?php if ($user['timezone'] == 8 ) print ' selected' ?>>+08</option>
					<option value="9"<?php if ($user['timezone'] == 9 ) print ' selected' ?>>+09</option>
					<option value="10"<?php if ($user['timezone'] == 10) print ' selected' ?>>+10</option>
					<option value="11"<?php if ($user['timezone'] == 11) print ' selected' ?>>+11</option>
					<option value="12"<?php if ($user['timezone'] == 12 ) print ' selected' ?>>+12</option>
					<option value="13"<?php if ($user['timezone'] == 13 ) print ' selected' ?>>+13</option>
				</select>
			</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_common['Options'] ?>&nbsp;&nbsp;</td>
			<td class="puncon2">
				<div style="padding-left: 4px"><?php print $lang_prof_reg['Hide e-mail info'] ?></div>
				<input type="checkbox" name="form[hide_email]" value="1"<?php if ($user['hide_email'] == '1') print ' checked' ?>>&nbsp;<?php print $lang_prof_reg['Hide e-mail'] ?><br><br>
				<div style="padding-left: 4px"><?php print $lang_prof_reg['Save user/pass info'] ?></div>
				<input type="checkbox" name="form[save_pass]" value="1"<?php if ($user['save_pass'] == '1') print ' checked' ?>>&nbsp;<?php print $lang_prof_reg['Save user/pass'] ?><br><br>
				<div style="padding-left: 4px"><?php print $lang_profile['Use smilies info'] ?></div>
				<input type="checkbox" name="form[smilies]" value="1"<?php if ($user['smilies'] == '1') print ' checked' ?>>&nbsp;<?php print $lang_profile['Use smilies'] ?><br><br>
				<div style="padding-left: 4px"><?php print $lang_profile['Show images info'] ?></div>
				<input type="checkbox" name="form[show_img]" value="1"<?php if ($user['show_img'] == '1') print ' checked' ?>>&nbsp;<?php print $lang_profile['Show images'] ?><br><br>
				<div style="padding-left: 4px"><?php print $lang_profile['Show sigs info'] ?></div>
				<input type="checkbox" name="form[show_sig]" value="1"<?php if ($user['show_sig'] == '1') print ' checked' ?>>&nbsp;<?php print $lang_profile['Show sigs'] ?><br><br>
				<div style="padding-left: 4px"><?php print $lang_profile['Open links new win info'] ?></div>
				<input type="checkbox" name="form[link_to_new_win]" value="1"<?php if ($user['link_to_new_win'] == '1') print ' checked' ?>>&nbsp;<?php print $lang_profile['Open links new win'] ?>
			</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_profile['Style'] ?>&nbsp;&nbsp;</td>
			<td class="puncon2">
				<div style="padding-left: 4px"><?php print $lang_profile['Style info'] ?></div><br>
				&nbsp;<select name="form[style]">
<?php

		$d = dir('style');
		while (($entry = $d->read()) !== false)
		{
			if (substr($entry, strlen($entry)-4) == '.css')
				$styles[] = substr($entry, 0, strlen($entry)-4);
		}
		$d->close();

		foreach ($styles as $temp)
		{
			if ($user['style'] == $temp)
				print "\t\t\t\t\t".'<option value="'.$temp.'" selected>'.str_replace('_', ' ', $temp).'</option>'."\n";
			else
				print "\t\t\t\t\t".'<option value="'.$temp.'">'.str_replace('_', ' ', $temp).'</option>'."\n";
		}

?>
				</select>
			</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_common['Registered'] ?>&nbsp;&nbsp;</td>
			<td class="puncon2">&nbsp;<?php print format_time($user['registered'], true) ?></td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_common['Last post'] ?>&nbsp;&nbsp;</td>
			<td class="puncon2">&nbsp;<?php print $last_post ?></td>
		</tr>
<?php if ($options['show_post_count'] == '1' || $cur_user['status'] > 0): ?>		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_common['Posts'] ?>&nbsp;&nbsp;</td>
			<td class="puncon2">&nbsp;<?php print $posts_field ?></td>
<?php endif; ?>		</tr>
<?php if ($cur_user['status'] > 0): ?>		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_profile['Admin note'] ?>&nbsp;&nbsp;</td>
			<td class="puncon2">&nbsp;<input type="text" name="admin_note" value="<?php print htmlspecialchars($user['admin_note']) ?>" size="30" maxlength="30"></td>
		</tr>
<?php endif; ?>		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_common['Actions'] ?>&nbsp;&nbsp;</td>
			<td class="puncon2" style="white-space: nowrap">
				<br>&nbsp;&nbsp;<input type="submit" name="update" value="<?php print $lang_common['Submit'] ?>"><br><br>
				&nbsp;<?php print $lang_profile['Instructions'] ?><br><br>
			</td>
		</tr>
	</table>
</form>
<?php

	// Should we show user administration?
	if ($cur_user['status'] > 0)
	{

?>

<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>

<form method="post" action="profile.php?id=<?php print $id ?>&action=foo">
	<table class="punmain" cellspacing="1" cellpadding="4">
		<tr class="punhead">
			<td class="punhead" colspan="2"><?php print $lang_profile['User admin'] ?></td>
		</tr>
<?php

		if ($cur_user['status'] == 1)
		{

?>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_common['Actions'] ?>&nbsp;&nbsp;</td>
			<td class="puncon2" style="white-space: nowrap">
				<br>&nbsp;&nbsp;<input type="submit" name="ban" value="<?php print $lang_profile['Ban user'] ?>"><br><br>
			</td>
		</tr>
<?php

		}
		else
		{

?>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_profile['Choose status'] ?>&nbsp;&nbsp;</td>
			<td class="puncon2">
				<div style="padding-left: 4px"><?php print $lang_profile['Choose status info'] ?></div><br>
				&nbsp;&nbsp;<select name="status">
					<option value="0"<?php if ($user['status'] < 1) print ' selected'?>><?php print $lang_common['Member'] ?></option>
					<option value="1"<?php if ($user['status'] == 1) print ' selected'?>><?php print $lang_common['Moderator'] ?></option>
					<option value="2"<?php if ($user['status'] == 2 ) print ' selected'?>><?php print $lang_common['Administrator'] ?></option>
				</select>
				&nbsp;&nbsp;<input type="submit" name="update_status" value="<?php print $lang_profile['Update status'] ?>"><br><br>
			</td>
		</tr>
<?php if ($user['status'] == 1): ?>		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_profile['Moderator in'] ?>&nbsp;&nbsp;</td>
			<td class="puncon2">
				<div style="padding-left: 4px"><?php print $lang_profile['Moderator in info'] ?><br><br>
<?php

			$result = $db->query('SELECT c.id AS cid, c.cat_name, f.id AS fid, f.forum_name, f.moderators FROM '.$db->prefix.'categories AS c INNER JOIN '.$db->prefix.'forums AS f ON c.id=f.cat_id'.$extra.' ORDER BY c.position, c.id, f.position') or error('Unable to fetch category/forum list', __FILE__, __LINE__, $db->error());

			while ($cur_forum = $db->fetch_assoc($result))
			{
				if ($cur_forum['cid'] != $cur_category)	// A new category since last iteration?
				{
					echo "\t\t\t\t".$cur_forum['cat_name'].'<br>';
					$cur_category = $cur_forum['cid'];
				}

				$moderators = ($cur_forum['moderators'] != '') ? unserialize($cur_forum['moderators']) : array();

				print "\t\t\t\t".'<input type="checkbox" name="moderator_in['.$cur_forum['fid'].']" value="1"'.((in_array($id, $moderators)) ? ' checked' : '').'>&nbsp;'.htmlspecialchars($cur_forum['forum_name']).'<br>'."\n";
			}

?>
				</div>
				<br>&nbsp;&nbsp;<input type="submit" name="update_forums" value="<?php print $lang_profile['Update forums'] ?>"><br><br>
			</td>
<?php endif; ?>		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_common['Actions'] ?>&nbsp;&nbsp;</td>
			<td class="puncon2" style="white-space: nowrap">
				<br>&nbsp;&nbsp;<input type="submit" name="delete" value="<?php print $lang_profile['Delete user'] ?>">&nbsp;&nbsp;<input type="submit" name="ban" value="<?php print $lang_profile['Ban user'] ?>"><br><br>
			</td>
		</tr>

<?php

		}

?>
			</td>
		</tr>
	</table>
</form>
<?php

	}

?>

<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>
<?php

	}

	require 'footer.php';
}
