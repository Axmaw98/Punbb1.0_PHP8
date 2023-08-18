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
require 'include/commonadmin.php';


if ($cur_user['status'] < 1)
	message($lang_common['No permission']);


// Add a ban (stage 1)
if (isset($_REQUEST['add_ban']))
{
	// If the id of the user to ban was provided through GET (a link from profile.php)
	if (isset($_GET['add_ban']))
	{
		$add_ban = intval($_GET['add_ban']);
		if (empty($add_ban))
			message($lang_common['Bad request']);

		$ban_id = $add_ban;

		$result = $db->query('SELECT username, email FROM '.$db->prefix.'users WHERE id='.$ban_id) or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());
		if ($db->num_rows($result))
		{
			list($ban_user, $ban_email) = $db->fetch_row($result);

			$result = $db->query('SELECT poster_ip FROM '.$db->prefix.'posts WHERE poster_id='.$ban_id.' ORDER BY posted DESC LIMIT 1') or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
			if ($db->num_rows($result))
				$ban_ip = $db->result($result, 0);
		}
		else
			message('No user by that ID registered.');
	}
	else	// Otherwise the username is in POST
	{
		$ban_user = trim($_POST['new_ban_user']);

		if ($ban_user != '')
		{
			$result = $db->query('SELECT id, username, email FROM '.$db->prefix.'users WHERE username=\''.escape(strtolower($ban_user)).'\'') or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());
			if ($db->num_rows($result))
			{
				list($ban_id, $ban_user, $ban_email) = $db->fetch_row($result);

				$result = $db->query('SELECT poster_ip FROM '.$db->prefix.'posts WHERE poster_id='.$ban_id.' ORDER BY posted DESC LIMIT 1') or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
				if ($db->num_rows($result))
					$ban_ip = $db->result($result, 0);
			}
			else
				message('No user by that username registered. If you want to add a ban not tied to a specific username just leave the username blank.');
		}
	}


	$page_title = htmlspecialchars($options['board_title']).' / Admin / Bans';
	$form_name = 'bans2';
	$focus_element = 'new_ban_ip';
	require 'header.php';

	if ($cur_user['status'] > 1)
		admin_menu('bans');
	else
		moderator_menu('bans');


?>
<form method="post" action="admin_bans.php" id="bans2">
	<input type="hidden" name="new_ban_user" value="<?php print htmlspecialchars($ban_user) ?>">
	<table class="punmain" cellspacing="1" cellpadding="4">
		<tr class="punhead">
			<td class="punhead" colspan="2">Supplement ban with IP and e-mail</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap">IP and e-mail&nbsp;&nbsp;</td>
			<td class="puncon2">
				<table class="punplain" cellpadding="6">
					<tr>
						<td class="punright" style="width: 35%"><b>Username</b></td>
						<td style="width: 35%"><?php print ($ban_user != '') ? htmlspecialchars($ban_user) : 'No user' ?></td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>IP</b><br>The IP or partiall IP you wish to ban (e.g. 150.11.110.1 or 150.11.110). If an IP is entered already it is the last known IP of this user in the database.<?php if ($ban_user != '') print ' Click <a href="admin_users.php?ip_stats='.$ban_id.'">here</a> to see IP statistics for this user.' ?></td>
						<td style="width: 35%"><input type="text" name="new_ban_ip" size="20" maxlength="15" value="<?php print $ban_ip ?>" tabindex="1"></td>
						<td class="puncent" style="width: 30%" rowspan="2"><input type="submit" name="add_ban2" value=" Add " tabindex="4"></td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>E-mail/domain</b><br>The e-mail or e-mail domain you wish to ban (e.g. someone@somewhere.com or somewhere.com). See option "Allow banned e-mail addresses" in Admin/Options for more info.</td>
						<td style="width: 35%"><input type="text" name="new_ban_email" size="35" maxlength="50" value="<?php print strtolower($ban_email) ?>" tabindex="2"></td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Expire date</b><br>The date when this ban should be removed (format: yyyy-mm-dd). Leave blank to remove manually.</td>
						<td style="width: 35%"><input type="text" name="new_ban_expire" size="17" maxlength="10" tabindex="3"></td>
					</tr>
					<tr>
						<td colspan="3">You should be very careful when banning partial IP's because of the possibility of multiple users matching the same partial IP.</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</form>

<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>
<?php

	require 'footer.php';
}


// Add a ban (stage 2)
else if (isset($_POST['add_ban2']))
{
	confirm_referer('admin_bans.php');

	$ban_user = $_POST['new_ban_user'];
	$ban_ip = trim($_POST['new_ban_ip']);
	$ban_email = strtolower(trim($_POST['new_ban_email']));
	$ban_expire = trim($_POST['new_ban_expire']);

	if ($ban_user == '' && $ban_ip == '' && $ban_email == '')
		message('You must enter either a username, an IP address or an e-mail address (at least).');

	require 'include/email.php';
	if ($ban_email != '' && !is_valid_email($ban_email))
	{
		if (!preg_match('/^[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/', $ban_email))
			message('The e-mail address (e.g. user@domain.com) or partial e-mail address domain (e.g. domain.com) you entered is invalid.');
	}

	if ($ban_expire != '' && $ban_expire != 'Never')
	{
		$ban_expire = strtotime($ban_expire);

		if ($ban_expire == -1 || $ban_expire <= time())
			message('You entered an invalid expire date. The format should be yyyy-mm-dd and the date must be at least one day forward from today.');
	}
	else
		$ban_expire = 'NULL';

	$ban_user = ($ban_user != '') ? '\''.escape($ban_user).'\'' : 'NULL';
	$ban_ip = ($ban_ip != '') ? '\''.escape($ban_ip).'\'' : 'NULL';
	$ban_email = ($ban_email != '') ? '\''.escape($ban_email).'\'' : 'NULL';

	$db->query('INSERT INTO '.$db->prefix.'bans (username, ip, email, expire) VALUES('.$ban_user.', '.$ban_ip.', '.$ban_email.', '.$ban_expire.')') or error('Unable to add ban', __FILE__, __LINE__, $db->error());

	redirect('admin_bans.php', 'Ban added. Redirecting ...');
}


// Update a ban
else if (isset($_POST['update']))
{
	confirm_referer('admin_bans.php');

	$id = key($_POST['update']);

	$ban_user = trim($_POST['ban_user'][$id]);
	$ban_ip = trim($_POST['ban_ip'][$id]);
	$ban_email = trim($_POST['ban_email'][$id]);
	$ban_expire = trim($_POST['ban_expire'][$id]);

	if ($ban_user == '' && $ban_ip == '' && $ban_email == '')
		message('You must enter eighter a username, an IP address or an e-mail address (at least).');

	require_once 'include/email.php';
	if ($ban_email != '' && !is_valid_email($ban_email))
	{
		if (!preg_match('/^[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/', $ban_email))
			message('The e-mail address (e.g. user@domain.com) or partial e-mail address domain (e.g. domain.com) you entered is invalid.');
	}

	if ($ban_expire != '' && $ban_expire != 'Never')
	{
		$ban_expire = strtotime($ban_expire);

		if ($ban_expire == -1 || $ban_expire <= time())
			message('You entered an invalid expire date. The format should be yyyy-mm-dd and the date must be at least one day forward from today.');
	}
	else
		$ban_expire = 'NULL';

	$ban_user = ($ban_user != '') ? '\''.escape($ban_user).'\'' : 'NULL';
	$ban_ip = ($ban_ip != '') ? '\''.escape($ban_ip).'\'' : 'NULL';
	$ban_email = ($ban_email != '') ? '\''.escape($ban_email).'\'' : 'NULL';

	$db->query('UPDATE '.$db->prefix.'bans SET username='.$ban_user.', ip='.$ban_ip.', email='.$ban_email.', expire='.$ban_expire.' WHERE id='.$id) or error('Unable to update ban', __FILE__, __LINE__, $db->error());

	redirect('admin_bans.php', 'Ban updated. Redirecting ...');
}


// Remove a ban
else if (isset($_POST['remove']))
{
	confirm_referer('admin_bans.php');

	$id = key($_POST['remove']);

	$db->query('DELETE FROM '.$db->prefix.'bans WHERE id='.$id) or error('Unable to delete ban', __FILE__, __LINE__, $db->error());

	redirect('admin_bans.php', 'Ban removed. Redirecting ...');
}


$page_title = htmlspecialchars($options['board_title']).' / Admin / Bans';
$form_name = 'bans';
$focus_element = 'new_ban_user';
require 'header.php';

if ($cur_user['status'] > 1)
	admin_menu('bans');
else
	moderator_menu('bans');

?>
<form method="post" action="admin_bans.php?action=more" id="bans">
	<table class="punmain" cellspacing="1" cellpadding="4">
		<tr class="punhead">
			<td class="punhead" colspan="2">Bans</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap">Add ban&nbsp;&nbsp;</td>
			<td class="puncon2">
				<table class="punplain" cellpadding="6">
					<tr>
						<td class="punright" style="width: 35%"><b>Username</b><br>The username to ban (case insensitive). The next page will let you enter a custom IP and e-mail. If you just want to ban a specific IP/IP-range or e-mail just leave it blank.</td>
						<td style="width: 35%"><input type="text" name="new_ban_user" size="25" maxlength="25"></td>
						<td style="width: 30%"><input type="submit" name="add_ban" value=" Add "></td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap">Edit/remove bans&nbsp;&nbsp;</td>
			<td class="puncon2">
				<table class="punplain" cellpadding="6">
					<tr>
						<td>
<?php

$result = $db->query('SELECT id, username, ip, email, expire FROM '.$db->prefix.'bans ORDER BY id') or error('Unable to ban list', __FILE__, __LINE__, $db->error());
if ($db->num_rows($result))
{
	while ($cur_ban = $db->fetch_assoc($result))
	{
		$expire = format_time($cur_ban['expire'], true);
		print "\t\t\t\t\t\t\t".'&nbsp;&nbsp;&nbsp;Username&nbsp;&nbsp;<input type="text" name="ban_user['.$cur_ban['id'].']" value="'.$cur_ban['username'].'" size="13" maxlength="25">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;IP&nbsp;&nbsp;<input type="text" name="ban_ip['.$cur_ban['id'].']" value="'.$cur_ban['ip'].'" size="17" maxlength="15">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;E-mail&nbsp;&nbsp;<input type="text" name="ban_email['.$cur_ban['id'].']" value="'.$cur_ban['email'].'" size="22" maxlength="50">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Expire&nbsp;&nbsp;<input type="text" name="ban_expire['.$cur_ban['id'].']" value="'.$expire.'" size="13" maxlength="10">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" name="update['.$cur_ban['id'].']" value="Update">&nbsp;<input type="submit" name="remove['.$cur_ban['id'].']" value="Remove"><br>'."\n";
	}
}
else
	print "\t\t\t\t\t\t\t".'No bans in list.'."\n";

?>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</form>

<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>
<?php

require 'footer.php';
