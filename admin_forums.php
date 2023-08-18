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


// Add a "default" forum
if (isset($_POST['add_forum']))
{
	confirm_referer('admin_forums.php');

	$add_to_cat = intval($_POST['add_to_cat']);
	if (empty($add_to_cat))
		message($lang_common['Bad request']);

	$db->query('INSERT INTO '.$db->prefix.'forums (cat_id) VALUES('.$add_to_cat.')') or error('Unable to create forum', __FILE__, __LINE__, $db->error());

	redirect('admin_forums.php', 'Forum added. Redirecting ...');
}


// Delete a forum
else if (isset($_POST['del_forum']) || isset($_POST['comply']))
{
	confirm_referer('admin_forums.php');

	$forum_to_delete = intval($_POST['forum_to_delete']);
	if (empty($forum_to_delete))
		message($lang_common['Bad request']);

	if (isset($_POST['comply']))	// Delete a forum with all posts
	{
		@set_time_limit(0);

		// Prune all posts and topics (start transaction)
		prune($forum_to_delete, 1, -1);

		// Locate any "orphaned redirect topics" and delete them
		$result = $db->query('SELECT t1.id FROM '.$db->prefix.'topics AS t1 LEFT OUTER JOIN '.$db->prefix.'topics AS t2 ON t1.moved_to=t2.id WHERE t2.id IS NULL AND t1.moved_to IS NOT NULL') or error('Unable to fetch redirect topics', __FILE__, __LINE__, $db->error());
		$num_orphans = $db->num_rows($result);

		if ($num_orphans)
		{
			for ($i = 0; $i < $num_orphans; $i++)
				$orphans[] = $db->result($result, $i);

			$db->query('DELETE FROM '.$db->prefix.'topics WHERE id IN('.implode(',', $orphans).')') or error('Unable to delete redirect topics', __FILE__, __LINE__, $db->error());
		}

		// Delete the forum (end transaction)
		$db->query('DELETE FROM '.$db->prefix.'forums WHERE id='.$forum_to_delete, PUN_TRANS_END) or error('Unable to delete forum', __FILE__, __LINE__, $db->error());

		redirect('admin_forums.php', 'Forum deleted. Redirecting ...');
	}
	else	// If the user hasn't confirmed the delete
	{
		$page_title = htmlspecialchars($options['board_title']).' / Admin / Forums';
		require 'header.php';

		admin_menu('forums');

?>
<form method="post" action="admin_forums.php">
	<input type="hidden" name="forum_to_delete" value="<?php print $forum_to_delete ?>">
	<table class="punmain" cellspacing="1" cellpadding="4">
		<tr class="punhead">
			<td class="punhead">Confirm delete forum</td>
		</tr>
		<tr>
			<td class="puncon2">
				<br>&nbsp;Are you sure that you want to delete this forum?<br><br>
				&nbsp;WARNING! Deleting a forum will delete all posts (if any) in that forum!<br><br>
				&nbsp;<input type="submit" name="comply" value=" OK ">&nbsp;&nbsp;&nbsp;<a href="javascript:history.go(-1)">Go back</a><br><br>
			</td>
		</tr>
	</table>
</form>

<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>
<?php

		require 'footer.php';
	}
}


// Update one or more forums
else if (isset($_POST['update']) || isset($_POST['updateall']))
{
	confirm_referer('admin_forums.php');

	$forums_to_process = (isset($_POST['update'])) ? array_keys($_POST['update']) : array_keys($_POST['forum_name']);

	foreach ($forums_to_process as $id)
	{
		$cur_position = $_POST['position'][$id];
		$cur_forum_name = trim($_POST['forum_name'][$id]);
		$cur_forum_desc = trim($_POST['forum_desc'][$id]);
		$cur_admmod_only = isset($_POST['admmod_only'][$id]);
		$cur_closed = isset($_POST['closed'][$id]);
		$cur_cat_id = intval($_POST['cat_id'][$id]);

		if ($cur_forum_name == '')
			message('You must enter a forum name.');

		if ($cur_position == '' || preg_match('/[^0-9]/', $cur_position))
			message('Position must be a positive integer value.');

		if (empty($cur_cat_id))
			message($lang_common['Bad request']);

		if ($cur_closed != '1') $cur_closed = '0';
		if ($cur_admmod_only != '1') $cur_admmod_only = '0';

		$cur_forum_desc = ($cur_forum_desc != '') ? '\''.escape(str_replace("\r", "\n", str_replace("\r\n", "\n", $cur_forum_desc))).'\'' : 'NULL';

		$db->query('UPDATE '.$db->prefix.'forums SET forum_name=\''.escape($cur_forum_name).'\', forum_desc='.$cur_forum_desc.', closed=\''.$cur_closed.'\', admmod_only=\''.$cur_admmod_only.'\', position='.$cur_position.', cat_id='.$cur_cat_id.' WHERE id='.$id) or error('Unable to update forum', __FILE__, __LINE__, $db->error());
	}

	redirect('admin_forums.php', 'Forum(s) updated. Redirecting ...');
}


// Generate an array with all forums and their respective category (used frequently)
$result = $db->query('SELECT c.id AS cid, c.cat_name, f.id AS fid, f.forum_name, f.forum_desc, f.closed, f.admmod_only, f.position FROM '.$db->prefix.'categories AS c LEFT JOIN '.$db->prefix.'forums AS f ON c.id=f.cat_id ORDER BY c.position, cid, f.position') or error('Unable to fetch category/forum list', __FILE__, __LINE__, $db->error());
$num_forums = $db->num_rows($result);

$forum_list = array();
while ($num_forums--)
	$forum_list[] = $db->fetch_assoc($result);


$page_title = htmlspecialchars($options['board_title']).' / Admin / Forums';
require 'header.php';

admin_menu('forums');

?>
<form method="post" action="admin_forums.php?action=adddel">
	<table class="punmain" cellspacing="1" cellpadding="4">
		<tr class="punhead">
			<td class="punhead" colspan="2">Add/delete forums</td>
		</tr>
		<tr class="puncon1">
			<td class="puncent" style="width: 50%">
				<br>&nbsp;&nbsp;Add forum to category&nbsp;&nbsp;<select name="add_to_cat">
<?php

$cur_category = 0;
foreach ($forum_list as $cur_forum)
{
	if ($cur_forum['cid'] != $cur_category)	// A new category since last iteration?
	{
		print "\t\t\t\t\t".'<option value="'.htmlspecialchars($cur_forum['cid']).'">'.htmlspecialchars($cur_forum['cat_name']).'</option>'."\n";
		$cur_category = $cur_forum['cid'];
	}
}

?>
				</select>
				&nbsp;&nbsp;<input type="submit" name="add_forum" value=" Add "><br><br>
			</td>
			<td class="puncent" style="width: 50%">
				<br>&nbsp;&nbsp;Delete forum&nbsp;&nbsp;<select name="forum_to_delete">
<?php

$cur_category = 0;
@reset($forum_list);
foreach ($forum_list as $cur_forum)
{
	if ($cur_forum['cid'] != $cur_category)	// A new category since last iteration?
	{
		print "\t\t\t\t\t".'<optgroup label="'.htmlspecialchars($cur_forum['cat_name']).'">'."\n";
		$cur_category = $cur_forum['cid'];
	}

	if ($cur_forum['fid'] != '')
		print "\t\t\t\t\t\t".'<option value="'.$cur_forum['fid'].'">'.htmlspecialchars($cur_forum['forum_name']).'</option>'."\n";
}

?>
					</optgroup>
				</select>
				&nbsp;&nbsp;<input type="submit" name="del_forum" value="Delete"><br><br>
			</td>
		</tr>
	</table>
</form>

<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>

<form method="post" action="admin_forums.php?action=edit">
	<table class="punmain" cellspacing="1" cellpadding="4">
		<tr class="punhead">
			<td class="punhead">Edit forums</td>
		</tr>
<?php

$cur_category = 0;
foreach($forum_list as $cur_forum)	// We use foreach instead of each() because we iterate through $forum_list later in the code block
{
	if ($cur_forum['cid'] != $cur_category)	// A new category since last iteration?
	{
		print "\t\t".'<tr class="puncon3"><td>'.htmlspecialchars($cur_forum['cat_name']).'</td></tr>'."\n";
		$cur_category = $cur_forum['cid'];
	}

	if ($cur_forum['fid'] != '')
	{

?>
		<tr class="puncon1">
			<td>
				<table class="punplain">
					<tr>
						<td class="punright" style="width: 10%"><b>Position</b></td>
						<td style="width: 32%">&nbsp;<input type="text" name="position[<?php print $cur_forum['fid'] ?>]" size="3" maxlength="3" value="<?php print $cur_forum['position'] ?>"></td>
						<td class="punright" style="width: 10%" rowspan="2"><b>Options</b></td>
						<td style="width: 32%; white-space: nowrap">&nbsp;<input type="checkbox" name="admmod_only[<?php print $cur_forum['fid'] ?>]" value="1"<?php if ($cur_forum['admmod_only'] == '1') print ' checked'; ?>>&nbsp;Admins/moderators only</td>
						<td class="puncent" style="width: 16%" rowspan="3"><input type="submit" name="update[<?php print $cur_forum['fid'] ?>]" value="Update"></td>
					</tr>
					<tr>
						<td class="punright"><b>Name</b></td>
						<td>&nbsp;<input type="text" name="forum_name[<?php print $cur_forum['fid'] ?>]" size="35" maxlength="80" value="<?php print htmlspecialchars($cur_forum['forum_name']) ?>"></td>
						<td style="white-space: nowrap">&nbsp;<input type="checkbox" name="closed[<?php print $cur_forum['fid'] ?>]" value="1"<?php if ($cur_forum['closed'] == '1') print ' checked'; ?>>&nbsp;Closed</td>
					</tr>
					<tr>
						<td class="punright">Description<br>(HTML)</td>
						<td>&nbsp;<textarea name="forum_desc[<?php print $cur_forum['fid'] ?>]" rows="3" cols="50"><?php print htmlspecialchars($cur_forum['forum_desc']) ?></textarea></td>
						<td class="punright"><b>Category</b></td>
						<td>
							&nbsp;<select name="cat_id[<?php print $cur_forum['fid'] ?>]">
<?php

		$cur_category2 = 0;
		@reset($forum_list);
		foreach ($forum_list as $cur_forum2)
		{
			if ($cur_forum2['cid'] != $cur_category2)	// A new category since last iteration?
			{
				$selected = ($cur_forum['cid'] == $cur_forum2['cid']) ? ' selected' : '';

				print "\t\t\t\t\t\t\t\t".'<option value="'.$cur_forum2['cid'].'"'.$selected.'>'.htmlspecialchars($cur_forum2['cat_name']).'</option>'."\n";

				$cur_category2 = $cur_forum2['cid'];
			}
		}

?>
							</select>
						</td>
					</tr>
				</table>
			</td>
		</tr>
<?php

	}
}

?>
		<tr>
			<td class="puncon2cent"><br><input type="submit" name="updateall" value="Update all"><br><br></td>
		</tr>
	</table>
</form>

<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>
<?php

require 'footer.php';
