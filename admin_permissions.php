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


if ($cur_user['status'] < 2)
	message($lang_common['No permission']);


if (isset($_POST['form_sent']))
{
	confirm_referer('admin_permissions.php');

	foreach ($_POST['form'] as $key => $input)
	{
		if (trim($input ) != '')
			$value = '\''.escape($input).'\'';
		else
			$value = 'NULL';

		$temp[] = $key.'='.$value;
	}

	$db->query('UPDATE '.$db->prefix.'permissions SET '.implode(',', $temp)) or error('Unable to update permissions', __FILE__, __LINE__, $db->error());

	redirect('admin_permissions.php', 'Permissions updated. Redirecting ...');
}


$page_title = htmlspecialchars($options['board_title']).' / Admin / Permissions';
require 'header.php';
admin_menu('permissions');

?>
<form method="post" action="admin_permissions.php">
	<input type="hidden" name="form_sent" value="1">
	<table class="punmain" cellspacing="1" cellpadding="4">
		<tr class="punhead">
			<td class="punhead" colspan="2">Permissions</td>
		</tr>
		<tr>
			<td class="puncon2cent" colspan="2"><br><input type="submit" name="submit" value="Submit"><br><br></td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap">Guests&nbsp;&nbsp;</td>
			<td class="puncon2">
				<table class="punplain" cellpadding="6">
					<tr>
						<td class="punright" style="width: 35%"><b>Guests may read forum</b><br>Allow guests (not registered users) to read the forum.</td>
						<td style="width: 65%"><input type="radio" name="form[guests_read]" value="1"<?php if ($permissions['guests_read'] == '1') print ' checked' ?>>&nbsp;Yes&nbsp;&nbsp;&nbsp;<input type="radio" name="form[guests_read]" value="0"<?php if ($permissions['guests_read'] == '0') print ' checked' ?>>&nbsp;No</td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Guests may post replies</b><br>Allow guests (not registered users) to post replies to topics in the forum.</td>
						<td style="width: 65%"><input type="radio" name="form[guests_post]" value="1"<?php if ($permissions['guests_post'] == '1') print ' checked' ?>>&nbsp;Yes&nbsp;&nbsp;&nbsp;<input type="radio" name="form[guests_post]" value="0"<?php if ($permissions['guests_post'] == '0') print ' checked' ?>>&nbsp;No</td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Guests may post topics</b><br>Allow guests (not registered users) to post new topics.</td>
						<td style="width: 65%"><input type="radio" name="form[guests_post_topic]" value="1"<?php if ($permissions['guests_post_topic'] == '1') print ' checked' ?>>&nbsp;Yes&nbsp;&nbsp;&nbsp;<input type="radio" name="form[guests_post_topic]" value="0"<?php if ($permissions['guests_post_topic'] == '0') print ' checked' ?>>&nbsp;No</td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Guests may use search</b><br>Allow guests (not registered users) to use the forum search engine.</td>
						<td style="width: 65%"><input type="radio" name="form[guests_search]" value="1"<?php if ($permissions['guests_search'] == '1') print ' checked' ?>>&nbsp;Yes&nbsp;&nbsp;&nbsp;<input type="radio" name="form[guests_search]" value="0"<?php if ($permissions['guests_search'] == '0') print ' checked' ?>>&nbsp;No</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap">Users&nbsp;&nbsp;</td>
			<td class="puncon2">
				<table class="punplain" cellpadding="6">
					<tr>
						<td class="punright" style="width: 35%"><b>Users may post replies</b><br>Allow users to post replies to topics in the forum.</td>
						<td style="width: 65%"><input type="radio" name="form[users_post]" value="1"<?php if ($permissions['users_post'] == '1') print ' checked' ?>>&nbsp;Yes&nbsp;&nbsp;&nbsp;<input type="radio" name="form[users_post]" value="0"<?php if ($permissions['users_post'] == '0') print ' checked' ?>>&nbsp;No</td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Users may post topics</b><br>Allow users to post new topics.</td>
						<td style="width: 65%"><input type="radio" name="form[users_post_topic]" value="1"<?php if ($permissions['users_post_topic'] == '1') print ' checked' ?>>&nbsp;Yes&nbsp;&nbsp;&nbsp;<input type="radio" name="form[users_post_topic]" value="0"<?php if ($permissions['users_post_topic'] == '0') print ' checked' ?>>&nbsp;No</td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Users may edit posts</b><br>Allow users to edit their own posts.</td>
						<td style="width: 65%"><input type="radio" name="form[users_edit_post]" value="1"<?php if ($permissions['users_edit_post'] == '1') print ' checked' ?>>&nbsp;Yes&nbsp;&nbsp;&nbsp;<input type="radio" name="form[users_edit_post]" value="0"<?php if ($permissions['users_edit_post'] == '0') print ' checked' ?>>&nbsp;No</td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Users may delete posts</b><br>Allow users to delete their own posts.</td>
						<td style="width: 65%"><input type="radio" name="form[users_del_post]" value="1"<?php if ($permissions['users_del_post'] == '1') print ' checked' ?>>&nbsp;Yes&nbsp;&nbsp;&nbsp;<input type="radio" name="form[users_del_post]" value="0"<?php if ($permissions['users_del_post'] == '0') print ' checked' ?>>&nbsp;No</td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Users may delete topics</b><br>Allow users to delete their own topics.</td>
						<td style="width: 65%"><input type="radio" name="form[users_del_topic]" value="1"<?php if ($permissions['users_del_topic'] == '1') print ' checked' ?>>&nbsp;Yes&nbsp;&nbsp;&nbsp;<input type="radio" name="form[users_del_topic]" value="0"<?php if ($permissions['users_del_topic'] == '0') print ' checked' ?>>&nbsp;No</td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Users may set title</b><br>Allow users to set their title.</td>
						<td style="width: 65%"><input type="radio" name="form[users_set_title]" value="1"<?php if ($permissions['users_set_title'] == '1') print ' checked' ?>>&nbsp;Yes&nbsp;&nbsp;&nbsp;<input type="radio" name="form[users_set_title]" value="0"<?php if ($permissions['users_set_title'] == '0') print ' checked' ?>>&nbsp;No</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap">Posting&nbsp;&nbsp;</td>
			<td class="puncon2">
				<table class="punplain" cellpadding="6">
					<tr>
						<td class="punright" style="width: 35%"><b>HTML</b><br>Allow HTML in posts (not recommended).</td>
						<td style="width: 65%"><input type="radio" name="form[message_html]" value="1"<?php if ($permissions['message_html'] == '1') print ' checked' ?>>&nbsp;Yes&nbsp;&nbsp;&nbsp;<input type="radio" name="form[message_html]" value="0"<?php if ($permissions['message_html'] == '0') print ' checked' ?>>&nbsp;No</td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>BBCode</b><br>Allow BBCode in posts (recommended).</td>
						<td style="width: 65%"><input type="radio" name="form[message_bbcode]" value="1"<?php if ($permissions['message_bbcode'] == '1') print ' checked' ?>>&nbsp;Yes&nbsp;&nbsp;&nbsp;<input type="radio" name="form[message_bbcode]" value="0"<?php if ($permissions['message_bbcode'] == '0') print ' checked' ?>>&nbsp;No</td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Image tag</b><br>Allow the BBCode [img][/img] tag in posts.</td>
						<td style="width: 65%"><input type="radio" name="form[message_img_tag]" value="1"<?php if ($permissions['message_img_tag'] == '1') print ' checked' ?>>&nbsp;Yes&nbsp;&nbsp;&nbsp;<input type="radio" name="form[message_img_tag]" value="0"<?php if ($permissions['message_img_tag'] == '0') print ' checked' ?>>&nbsp;No</td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>All caps message</b><br>Allow a message to contain only capital letters.</td>
						<td style="width: 65%"><input type="radio" name="form[message_all_caps]" value="1"<?php if ($permissions['message_all_caps'] == '1') print ' checked' ?>>&nbsp;Yes&nbsp;&nbsp;&nbsp;<input type="radio" name="form[message_all_caps]" value="0"<?php if ($permissions['message_all_caps'] == '0') print ' checked' ?>>&nbsp;No</td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>All caps subject</b><br>Allow a subject to contain only capital letters.</td>
						<td style="width: 65%"><input type="radio" name="form[subject_all_caps]" value="1"<?php if ($permissions['subject_all_caps'] == '1') print ' checked' ?>>&nbsp;Yes&nbsp;&nbsp;&nbsp;<input type="radio" name="form[subject_all_caps]" value="0"<?php if ($permissions['subject_all_caps'] == '0') print ' checked' ?>>&nbsp;No</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap">Signatures&nbsp;&nbsp;</td>
			<td class="puncon2">
				<table class="punplain" cellpadding="6">
					<tr>
						<td class="punright" style="width: 35%"><b>HTML in signatures</b><br>Allow HTML in user signatures (not recommended).</td>
						<td style="width: 65%"><input type="radio" name="form[sig_html]" value="1"<?php if ($permissions['sig_html'] == '1') print ' checked' ?>>&nbsp;Yes&nbsp;&nbsp;&nbsp;<input type="radio" name="form[sig_html]" value="0"<?php if ($permissions['sig_html'] == '0') print ' checked' ?>>&nbsp;No</td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>BBCodes in signatures</b><br>Allow BBCodes in user signatures.</td>
						<td style="width: 65%"><input type="radio" name="form[sig_bbcode]" value="1"<?php if ($permissions['sig_bbcode'] == '1') print ' checked' ?>>&nbsp;Yes&nbsp;&nbsp;&nbsp;<input type="radio" name="form[sig_bbcode]" value="0"<?php if ($permissions['sig_bbcode'] == '0') print ' checked' ?>>&nbsp;No</td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Image tag in signatures</b><br>Allow the BBCode [img][/img] tag in user signatures (not recommended).</td>
						<td style="width: 65%"><input type="radio" name="form[sig_img_tag]" value="1"<?php if ($permissions['sig_img_tag'] == '1') print ' checked' ?>>&nbsp;Yes&nbsp;&nbsp;&nbsp;<input type="radio" name="form[sig_img_tag]" value="0"<?php if ($permissions['sig_img_tag'] == '0') print ' checked' ?>>&nbsp;No</td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>All caps signature</b><br>Allow a signature to contain only capital letter.</td>
						<td style="width: 65%"><input type="radio" name="form[sig_all_caps]" value="1"<?php if ($permissions['sig_all_caps'] == '1') print ' checked' ?>>&nbsp;Yes&nbsp;&nbsp;&nbsp;<input type="radio" name="form[sig_all_caps]" value="0"<?php if ($permissions['sig_all_caps'] == '0') print ' checked' ?>>&nbsp;No</td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Maximum signature length</b><br>The maximum number of characters a user signature may contain.</td>
						<td style="width: 65%"><input type="text" name="form[sig_length]" size="5" maxlength="5" value="<?php print $permissions['sig_length'] ?>"></td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Maximum signature lines</b><br>The maximum number of lines a user signature may contain.</td>
						<td style="width: 65%"><input type="text" name="form[sig_lines]" size="3" maxlength="3" value="<?php print $permissions['sig_lines'] ?>"></td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap">Registration&nbsp;&nbsp;</td>
			<td class="puncon2">
				<table class="punplain" cellpadding="6">
					<tr>
						<td class="punright" style="width: 35%"><b>Allow banned e-mail addresses</b><br>Allow users to register with or change to a banned e-mail address/domain. If left at it's default setting (yes) this action will be allowed, but an alert e-mail will be sent to the mailing list (an effective way of detecting multiple registrations).</td>
						<td style="width: 65%"><input type="radio" name="form[allow_banned_email]" value="1"<?php if ($permissions['allow_banned_email'] == '1') print ' checked' ?>>&nbsp;Yes&nbsp;&nbsp;&nbsp;<input type="radio" name="form[allow_banned_email]" value="0"<?php if ($permissions['allow_banned_email'] == '0') print ' checked' ?>>&nbsp;No</td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Allow duplicate e-mail addresses</b><br>Controls whether users should be allowed to register with an e-mail address that another user already has. If allowed, an alert e-mail will be sent to the mailing list if a duplicate is detected.</td>
						<td style="width: 65%"><input type="radio" name="form[allow_dupe_email]" value="1"<?php if ($permissions['allow_dupe_email'] == '1') print ' checked' ?>>&nbsp;Yes&nbsp;&nbsp;&nbsp;<input type="radio" name="form[allow_dupe_email]" value="0"<?php if ($permissions['allow_dupe_email'] == '0') print ' checked' ?>>&nbsp;No</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td class="puncon2cent" colspan="2"><br><input type="submit" name="submit" value="Submit"><br><br></td>
		</tr>
	</table>
</form>

<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>
<?php

require 'footer.php';
