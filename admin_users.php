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


if (isset($_GET['ip_stats']))
{
	$ip_stats = intval($_GET['ip_stats']);
	if ($ip_stats < 1)
		message($lang_common['Bad request']);


	$page_title = htmlspecialchars($options['board_title']).' / Admin / Users';
	require 'header.php';

	if ($cur_user['status'] > 1)
		admin_menu('users');
	else
		moderator_menu('users');

?>
<table class="punmain" cellspacing="1" cellpadding="4">
	<tr class="punhead">
		<td class="punhead" style="width: 19%">IP address</td>
		<td class="punhead" style="width: 31%">Hostname</td>
		<td class="punhead" style="width: 20%">Last used</td>
		<td class="punhead" style="width: 12%">Times found</td>
		<td class="punhead" style="width: 18%">Action</td>
	</tr>
<?php

	$result = $db->query('SELECT poster_ip, posted FROM '.$db->prefix.'posts WHERE poster_id='.$ip_stats.' ORDER BY posted DESC') or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
	$num_posts = $db->num_rows($result);

	if ($num_posts)
	{
		// Fetch the first hit and add it to hit_list
		$cur_hit = $db->fetch_row($result);
		$hit_list = array($cur_hit[0] => array($cur_hit[1], 1));

		// Loop through hits and update hit_list
		for ($i = 1; $i < $num_posts; $i++)
		{
			$cur_hit = $db->fetch_row($result);

			if (isset($hit_list[$cur_hit[0]]))
			{
				$hit_list[$cur_hit[0]][1]++;

				if ($cur_hit[1] > $hit_list[$cur_hit[0]][0])
					$hit_list[$cur_hit[0]][0] = $cur_hit[1];
			}
			else
				$hit_list[$cur_hit[0]] = array($cur_hit[1], 1);
		}

		foreach ($hit_list as $key => $value)
		{

?>
	<tr class="puncon2">
		<td><?php print $key ?></td>
		<td><?php print gethostbyaddr($key) ?></td>
		<td><?php print format_time($value[0]) ?></td>
		<td><?php print $value[1] ?></td>
		<td><a href="admin_users.php?show_users=<?php print $key ?>">Find more users for this ip</a></td>
	</tr>
<?php

		}
	}
	else
		print "\t".'<tr class="puncon2"><td colspan="5">There are currently no posts by that user in the forum.</td></tr>'."\n";

?>

<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>
<?php

	require 'footer.php';
}


if (isset($_GET['show_users']))
{
	$ip = $_GET['show_users'];

	if (!preg_match('/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/', $ip))
		message('The supplied IP address is not correctly formatted.');


	$page_title = htmlspecialchars($options['board_title']).' / Admin / Users';
	require 'header.php';

	if ($cur_user['status'] > 1)
		admin_menu('users');
	else
		moderator_menu('users');

?>
<table class="punmain" cellspacing="1" cellpadding="4">
	<tr class="punhead">
		<td class="punhead" style="width: 11%; white-space: nowrap">Username</td>
		<td class="punhead" style="width: 21%; white-space: nowrap">E-mail</td>
		<td class="punhead" style="width: 13%; white-space: nowrap">Title</td>
		<td class="punhead" style="width: 10%; white-space: nowrap">Registered</td>
		<td class="punhead" style="width: 10%; white-space: nowrap">Last post</td>
		<td class="punhead" style="width: 5%; white-space: nowrap">Posts</td>
		<td class="punhead" style="width: 14%">Admin note</td>
		<td class="punhead" style="white-space: nowrap">Actions</td>
	</tr>
<?php

	$result = $db->query('SELECT DISTINCT poster_id, poster FROM '.$db->prefix.'posts WHERE poster_ip=\''.escape($ip).'\' ORDER BY poster DESC') or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
	$num_posts = $db->num_rows($result);

	if ($num_posts)
	{
		// Loop through users and print out some info
		for ($i = 0; $i < $num_posts; $i++)
		{
			list($poster_id, $poster) = $db->fetch_row($result);

			$result2 = $db->query('SELECT id, username, email, title, num_posts, status, last_post, registered, admin_note FROM '.$db->prefix.'users WHERE id>1 AND id='.$poster_id) or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());

			if (($user_data = $db->fetch_assoc($result2)))
			{
				$user_title = get_title($user_data);

				$actions = '<a href="admin_users.php?ip_stats='.$user_data['id'].'">View IP stats</a> - <a href="search.php?action=show_user&amp;user_id='.$user_data['id'].'">Show posts</a>';

?>
	<tr class="puncon2">
		<td style="white-space: nowrap"><?php print '<a href="profile.php?id='.$user_data['id'].'">'.htmlspecialchars($user_data['username']).'</a>' ?></td>
		<td style="white-space: nowrap"><a href="mailto:<?php print $user_data['email'] ?>"><?php print $user_data['email'] ?></a></td>
		<td style="white-space: nowrap"><?php print $user_title ?></td>
		<td style="white-space: nowrap"><?php print format_time($user_data['registered'], true) ?></td>
		<td style="white-space: nowrap"><?php print format_time($user_data['last_post'], true) ?></td>
		<td style="white-space: nowrap"><?php print $user_data['num_posts'] ?></td>
		<td><?php print ($user_data['admin_note'] != '') ? $user_data['admin_note'] : '&nbsp;' ?></td>
		<td style="white-space: nowrap"><?php print $actions ?></td>
	</tr>
<?php

			}
			else
			{

?>
	<tr class="puncon2">
		<td style="white-space: nowrap"><?php print htmlspecialchars($poster) ?></td>
		<td>&nbsp;</td>
		<td>Guest</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
	</tr>
<?php

			}
		}
	}
	else
		print "\t".'<tr class="puncon2"><td colspan="8">The supplied IP address could not be found in the database.</td></tr>'."\n";

?>

<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>
<?php

	require 'footer.php';
}


else if (isset($_POST['find_user']))
{
	$form = $_POST['form'];
	$form['username'] = $_POST['username'];

	// trim() all elements in $form
	$form = array_map('trim', $form);

	$posts_greater = trim($_POST['posts_greater']);
	$posts_less = trim($_POST['posts_less']);
	$last_post_after = trim($_POST['last_post_after']);
	$last_post_before = trim($_POST['last_post_before']);
	$registered_after = trim($_POST['registered_after']);
	$registered_before = trim($_POST['registered_before']);
	$order_by = $_POST['order_by'];
	$direction = $_POST['direction'];
	$user_group = $_POST['user_group'];

	if (preg_match('/[^0-9]/', $posts_greater.$posts_less))
		message('You entered a non-numeric value into a numeric only column.');

	// Try to convert date/time to timestamps
	if ($last_post_after != '')
		$last_post_after = strtotime($last_post_after);
	if ($last_post_before != '')
		$last_post_before = strtotime($last_post_before);
	if ($registered_after != '')
		$registered_after = strtotime($registered_after);
	if ($registered_before != '')
		$registered_before = strtotime($registered_before);

	if ($last_post_after == -1 || $last_post_before == -1 || $registered_after == -1 || $registered_before == -1)
		message('You entered an invalid date/time.');

	if ($last_post_after != '')
		$conditions[] = 'last_post>'.$last_post_after;
	if ($last_post_before != '')
		$conditions[] = 'last_post<'.$last_post_before;
	if ($registered_after != '')
		$conditions[] = 'registered>'.$registered_after;
	if ($registered_before != '')
		$conditions[] = 'registered<'.$registered_before;

	foreach ($form as $key => $input)
	{
		if ($input != '')
			$conditions[] = $key.' LIKE \''.un_escape(str_replace('*', '%', $input)).'\'';
	}

	if ($posts_greater != '')
		$conditions[] = 'num_posts>'.$posts_greater;
	if ($posts_less != '')
		$conditions[] = 'num_posts<'.$posts_less;

	if ($user_group != 'all')
		$conditions[] = 'status='.$user_group;

	if (!isset($conditions))
		message('You didn\'t enter any search terms.');


	$page_title = htmlspecialchars($options['board_title']).' / Admin / Users';
	require 'header.php';

	if ($cur_user['status'] > 1)
		admin_menu('users');
	else
		moderator_menu('users');

?>
<table class="punmain" cellspacing="1" cellpadding="4">
	<tr class="punhead">
		<td class="punhead" style="width: 11%; white-space: nowrap">Username</td>
		<td class="punhead" style="width: 21%; white-space: nowrap">E-mail</td>
		<td class="punhead" style="width: 13%; white-space: nowrap">Title</td>
		<td class="punhead" style="width: 10%; white-space: nowrap">Registered</td>
		<td class="punhead" style="width: 10%; white-space: nowrap">Last post</td>
		<td class="punhead" style="width: 5%; white-space: nowrap">Posts</td>
		<td class="punhead" style="width: 14%">Admin note</td>
		<td class="punhead" style="white-space: nowrap">Actions</td>
	</tr>
<?php

	$sql = 'SELECT id, username, email, title, num_posts, status, last_post, registered, admin_note FROM '.$db->prefix.'users WHERE id>1 AND '.implode(' AND ', $conditions).' ORDER BY '.$order_by.' '.$direction;

	$result = $db->query($sql) or error('Unable to search for users', __FILE__, __LINE__, $db->error());
	$num_users = $db->num_rows($result);

	if ($num_users)
	{
		// Loop through users and print out some info
		for ($i = 0; $i < $num_users; $i++)
		{
			$user_data = $db->fetch_assoc($result);

			$user_title = get_title($user_data);

			// This script is a special case in that we want to display "Not validated" for non-validated users
			if ($user_data['status'] == -1 && $user_title != $lang_common['Banned'])
				$user_title = '<span class="punhot">Not validated</span>';

			$actions = '<a href="admin_users.php?ip_stats='.$user_data['id'].'">View IP stats</a> - <a href="search.php?action=show_user&amp;user_id='.$user_data['id'].'">Show posts</a>';

?>
	<tr class="puncon2">
		<td style="white-space: nowrap"><?php print '<a href="profile.php?id='.$user_data['id'].'">'.htmlspecialchars($user_data['username']).'</a>' ?></td>
		<td style="white-space: nowrap"><a href="mailto:<?php print $user_data['email'] ?>"><?php print $user_data['email'] ?></a></td>
		<td style="white-space: nowrap"><?php print $user_title ?></td>
		<td style="white-space: nowrap"><?php print format_time($user_data['registered'], true) ?></td>
		<td style="white-space: nowrap"><?php print format_time($user_data['last_post'], true) ?></td>
		<td style="white-space: nowrap"><?php print $user_data['num_posts'] ?></td>
		<td><?php print ($user_data['admin_note'] != '') ? $user_data['admin_note'] : '&nbsp;' ?></td>
		<td style="white-space: nowrap"><?php print $actions ?></td>
	</tr>
<?php

		}
	}
	else
		print "\t".'<tr class="puncon2"><td colspan="8">No match.</td></tr>'."\n";

?>
</table>

<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>
<?php

	require 'footer.php';
}


else
{
	$page_title = htmlspecialchars($options['board_title']).' / Admin / Users';
	$form_name = 'find_user';
	$focus_element = 'username';
	require 'header.php';

	if ($cur_user['status'] > 1)
		admin_menu('users');
	else
		moderator_menu('users');

?>
<form method="post" action="admin_users.php?action=find_user" id="find_user">
	<table class="punmain" cellspacing="1" cellpadding="4">
		<tr class="punhead">
			<td class="punhead" colspan="2">Users</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap">Find users&nbsp;&nbsp;</td>
			<td class="puncon2">
				<table class="punplain" cellspacing="0" cellpadding="4">
					<tr>
						<td colspan="3">Search for users in the database. You can enter one or more terms to search for. Wildcards in the form of asterisks (*) are accepted.<br><br></td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%">Username</td>
						<td style="width: 35%"><input type="text" name="username" size="25" maxlength="25" tabindex="1"></td>
						<td style="width: 30%" rowspan="16"><input type="submit" name="find_user" value=" Find " tabindex="21"></td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%">E-mail address</td>
						<td style="width: 35%"><input type="text" name="form[email]" size="30" maxlength="50" tabindex="2"></td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%">Title</td>
						<td style="width: 35%"><input type="text" name="form[title]" size="30" maxlength="50" tabindex="3"></td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%">Real name</td>
						<td style="width: 35%"><input type="text" name="form[realname]" size="30" maxlength="40" tabindex="4"></td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%">Website</td>
						<td style="width: 35%"><input type="text" name="form[url]" size="35" maxlength="100" tabindex="5"></td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%">ICQ</td>
						<td style="width: 35%"><input type="text" name="form[icq]" size="12" maxlength="12" tabindex="6"></td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%">AOL IM</td>
						<td style="width: 35%"><input type="text" name="form[aim]" size="20" maxlength="20" tabindex="7"></td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%">Yahoo! Messenger</td>
						<td style="width: 35%"><input type="text" name="form[yahoo]" size="20" maxlength="20" tabindex="8"></td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%">Location</td>
						<td style="width: 35%"><input type="text" name="form[location]" size="30" maxlength="30" tabindex="9"></td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%">Signature</td>
						<td style="width: 35%"><input type="text" name="form[signature]" size="35" maxlength="512" tabindex="10"></td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%">Admin note</td>
						<td style="width: 35%"><input type="text" name="form[admin_note]" size="30" maxlength="30" tabindex="11"></td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%">Number of posts greater than</td>
						<td style="width: 35%"><input type="text" name="posts_greater" size="5" maxlength="8" tabindex="12"></td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%">Number of posts less than</td>
						<td style="width: 35%"><input type="text" name="posts_less" size="5" maxlength="8" tabindex="13"></td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%">Last post is after<br>(yyyy-mm-dd hh:mm:ss)</td>
						<td style="width: 35%"><input type="text" name="last_post_after" size="24" maxlength="19" tabindex="14"></td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%">Last post is before<br>(yyyy-mm-dd hh:mm:ss)</td>
						<td style="width: 35%"><input type="text" name="last_post_before" size="24" maxlength="19" tabindex="15"></td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%">Registered after<br>(yyyy-mm-dd hh:mm:ss)</td>
						<td style="width: 35%"><input type="text" name="registered_after" size="24" maxlength="19" tabindex="16"></td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%">Registered before<br>(yyyy-mm-dd hh:mm:ss)</td>
						<td style="width: 35%"><input type="text" name="registered_before" size="24" maxlength="19" tabindex="17"></td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%">Order by</td>
						<td style="width: 35%">
							<select name="order_by" tabindex="18">
								<option value="username" selected>username</option>
								<option value="email">e-mail</option>
								<option value="num_posts">posts</option>
								<option value="last_post">last post</option>
								<option value="registered">registered</option>
							</select>&nbsp;&nbsp;&nbsp;<select name="direction" tabindex="19">
								<option value="ASC" selected>ascending</option>
								<option value="DESC">descending</option>
							</select>
						</td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%">User group</td>
						<td style="width: 35%">
							<select name="user_group" tabindex="20">
								<option value="all" selected>All groups</option>
								<option value="0">Users</option>
								<option value="1">Moderators</option>
								<option value="2">Administrators</option>
								<option value="-1">Not validated</option>
							</select>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</form>

<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>

<form method="get" action="admin_users.php">
	<table class="punmain" cellspacing="1" cellpadding="4">
		<tr class="punhead">
			<td class="punhead" colspan="2">IP search</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap">Find users&nbsp;&nbsp;</td>
			<td class="puncon2">
				<table class="punplain" cellspacing="0" cellpadding="4">
					<tr>
						<td class="punright" style="width: 35%"><b>IP address</b><br>The IP address to search for in the post database.</td>
						<td style="width: 35%"><input type="text" name="show_users" size="18" maxlength="15" tabindex="22"></td>
						<td style="width: 30%"><input type="submit" value=" Find " tabindex="23"></td>
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
