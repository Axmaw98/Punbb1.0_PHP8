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


// This particular function doesn't require forum-based moderator access. It can be used
// by all moderators and admins.
if (isset($_GET['get_host']))
{
	$get_host = intval($_GET['get_host']);
	if (empty($get_host))
		message($lang_common['Bad request']);

	if ($cur_user['status'] < 1)
		message($lang_common['No permission']);

	$result = $db->query('SELECT poster_ip FROM '.$db->prefix.'posts WHERE id='.$get_host) or error('Unable to fetch post IP address', __FILE__, __LINE__, $db->error());
	$ip = $db->result($result, 0);

	message('The IP address is: '.$ip.'<br>The host name is: '.gethostbyaddr($ip).'<br><br><a href="admin_users.php?show_users='.$ip.'">Show more users for this IP</a>');
}


// All other functions require forum-based moderator access
$fid = intval($_GET['fid']);
if (empty($fid))
	message($lang_common['Bad request']);

if (!is_admmod($fid, $foo, $foo))
	message($lang_common['No permission']);


if (isset($_GET['move']))
{
	if (isset($_POST['move_to']))
	{
		confirm_referer('moderate.php');

		$move = intval($_GET['move']);
		$move_to_forum = intval($_POST['move_to_forum']);
		if (empty($move) || empty($move_to_forum))
			message($lang_common['Bad request']);

		// Delete a redirect topic if there is one (only if we moved/copied the topic back to where it where it was once moved from) (start transaction)
		$db->query('DELETE FROM '.$db->prefix.'topics WHERE forum_id='.$move_to_forum.' AND moved_to='.$move, PUN_TRANS_START) or error('Unable to delete redirect topic', __FILE__, __LINE__, $db->error());

		// Move the topic
		$db->query('UPDATE '.$db->prefix.'topics SET forum_id='.$move_to_forum.' WHERE id='.$move) or error('Unable to move topic', __FILE__, __LINE__, $db->error());

		if ($_POST['with_redirect'] == '1')
		{
			// Fetch info for the redirect topic
			$result = $db->query('SELECT poster, subject, posted, last_post FROM '.$db->prefix.'topics WHERE id='.$move) or error('Unable to fetch topic info', __FILE__, __LINE__, $db->error());
			$moved_to = $db->fetch_assoc($result);

			// Create the redirect topic
			$db->query('INSERT INTO '.$db->prefix.'topics (poster, subject, posted, last_post, moved_to, forum_id) VALUES(\''.$moved_to['poster'].'\', \''.$moved_to['subject'].'\', '.$moved_to['posted'].', '.$moved_to['last_post'].', '.$move.', '.$fid.')') or error('Unable to create moved_to topic', __FILE__, __LINE__, $db->error());
		}

		update_forum($fid);				// Update last_post in the forum FROM which the topic was moved/copied
		update_forum($move_to_forum, PUN_TRANS_END);	// Update last_post in the forum TO which the topic was moved/copied (end transaction)

		redirect('viewforum.php?id='.$move_to_forum, 'Topic moved/copied. Redirecting ...');
	}
	else
	{
		$move = intval($_GET['move']);
		if (empty($move))
			message($lang_common['Bad request']);

		$page_title = htmlspecialchars($options['board_title']).' / Moderate';
		require 'header.php';

?>
<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>

<form method="post" action="moderate.php?fid=<?php print $fid ?>&amp;move=<?php print $move ?>">
	<table class="punmain" cellspacing="1" cellpadding="4">
		<tr class="punhead">
			<td class="punhead">Move topic</td>
		</tr>
		<tr>
			<td class="puncon2">
				<br>&nbsp;Move to&nbsp;&nbsp;<select name="move_to_forum">
<?php

		$result = $db->query('SELECT c.id AS cid, c.cat_name, f.id AS fid, f.forum_name FROM '.$db->prefix.'categories AS c INNER JOIN '.$db->prefix.'forums AS f ON c.id=f.cat_id'.$extra.' ORDER BY c.position, cid, f.position') or error('Unable to fetch category/forum list', __FILE__, __LINE__, $db->error());

		while ($cur_forum = $db->fetch_assoc($result))
		{
			if ($cur_forum['cid'] != $cur_category)	// A new category since last iteration?
			{
				if (!empty($cur_category))
					print "\t\t\t\t\t".'</optgroup>'."\n";

				print "\t\t\t\t\t".'<optgroup label="'.htmlspecialchars($cur_forum['cat_name']).'">'."\n";
				$cur_category = $cur_forum['cid'];
			}

			if ($cur_forum['fid'] != $fid)
				print "\t\t\t\t\t\t".'<option value="'.$cur_forum['fid'].'">'.htmlspecialchars($cur_forum['forum_name']).'</option>'."\n";
		}

?>
					</optgroup>
				</select><br><br>
				<input type="checkbox" name="with_redirect" value="1" checked>&nbsp;Move with redirect (leave a redirect topic)<br><br>
				&nbsp;&nbsp;<input type="submit" name="move_to" value="Move"><br><br>
			</td>
		</tr>
	</table>
</form>

<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>
<?php

		require 'footer.php';
	}
}


else if (isset($_GET['close']))
{
	confirm_referer('viewtopic.php');

	$close = intval($_GET['close']);
	if (empty($close))
		message($lang_common['Bad request']);

	$db->query('UPDATE '.$db->prefix.'topics SET closed=\'1\' WHERE id='.$close) or error('Unable to close topic', __FILE__, __LINE__, $db->error());

	redirect('viewtopic.php?id='.$close, 'Topic closed. Redirecting ...');
}


else if (isset($_GET['open']))
{
	confirm_referer('viewtopic.php');

	$open = intval($_GET['open']);
	if (empty($open))
		message($lang_common['Bad request']);

	$db->query('UPDATE '.$db->prefix.'topics SET closed=\'0\' WHERE id='.$open) or error('Unable to open topic', __FILE__, __LINE__, $db->error());

	redirect('viewtopic.php?id='.$open, 'Topic opened. Redirecting ...');
}


else if (isset($_GET['stick']))
{
	confirm_referer('viewtopic.php');

	$stick = intval($_GET['stick']);
	if (empty($stick))
		message($lang_common['Bad request']);

	$db->query('UPDATE '.$db->prefix.'topics SET sticky=\'1\' WHERE id='.$stick) or error('Unable to stick topic', __FILE__, __LINE__, $db->error());

	redirect('viewtopic.php?id='.$stick, 'Topic sticked. Redirecting ...');
}


else if (isset($_GET['unstick']))
{
	confirm_referer('viewtopic.php');

	$unstick = intval($_GET['unstick']);
	if (empty($unstick))
		message($lang_common['Bad request']);

	$db->query('UPDATE '.$db->prefix.'topics SET sticky=\'0\' WHERE id='.$unstick) or error('Unable to unstick topic', __FILE__, __LINE__, $db->error());

	redirect('viewtopic.php?id='.$unstick, 'Topic sticked. Redirecting ...');
}


else if (isset($_GET['edit_subscribers']))
{
	$edit_subscribers = intval($_GET['edit_subscribers']);
	if (empty($edit_subscribers))
		message($lang_common['Bad request']);

	if (isset($_POST['update']))
	{
		confirm_referer('moderate.php');

		$subscribers = strtolower(preg_replace("/[\s]+/", '', trim($_POST['subscribers'])));
		$subscribers = ($subscribers != '') ? '\''.$subscribers.'\'' : 'NULL';

		$db->query('UPDATE '.$db->prefix.'topics SET subscribers='.$subscribers.' WHERE id='.$edit_subscribers) or error('Unable to update topic subscribers', __FILE__, __LINE__, $db->error());

		redirect('viewtopic.php?id='.$edit_subscribers, 'Subscribers updated. Redirecting ...');
	}
	else
	{
		$page_title = htmlspecialchars($options['board_title']).' / Moderate';
		require 'header.php';

?>
<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>

<form method="post" action="moderate.php?fid=<?php print $fid ?>&amp;edit_subscribers=<?php print $edit_subscribers ?>">
	<table class="punmain" cellspacing="1" cellpadding="4">
		<tr class="punhead">
			<td class="punhead" colspan="2">Edit subscribers</td>
		</tr>
<?php

		$result = $db->query('SELECT subscribers FROM '.$db->prefix.'topics WHERE id='.$edit_subscribers) or error('Unable to fetch topic subscribers', __FILE__, __LINE__, $db->error());
		$subscribers = $db->result($result, 0);

?>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><b>Subscribers</b>&nbsp;&nbsp;</td>
			<td class="puncon2">
				A comma-separated list of subscribed e-mail addresses.<br><br>
				&nbsp;<textarea name="subscribers" rows="3" cols="80"><?php print $subscribers ?></textarea>
			</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap">Actions&nbsp;&nbsp;</td>
			<td class="puncon2">
				<br>&nbsp;<input type="submit" name="update" value="Update"><br><br>
			</td>
		</tr>
	</table>
</form>

<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>
<?php

		require 'footer.php';
	}
}
else
	message($lang_common['Bad request']);
