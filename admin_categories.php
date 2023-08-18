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


if (isset($_POST['add_cat']))	// Add a new category
{
	confirm_referer('admin_categories.php');

	$new_cat_name = trim($_POST['new_cat_name']);
	if ($new_cat_name == '')
		message('You must enter a name for the category.');

	$db->query('INSERT INTO '.$db->prefix.'categories (cat_name) VALUES(\''.escape($new_cat_name).'\')') or error('Unable to create category', __FILE__, __LINE__, $db->error());

	redirect('admin_categories.php', 'Category added. Redirecting ...');
}


else if (isset($_POST['del_cat']) || isset($_POST['comply']))
{
	confirm_referer('admin_categories.php');

	$cat_to_delete = intval($_POST['cat_to_delete']);
	if (empty($cat_to_delete))
		message($lang_common['Bad request']);

	if (isset($_POST['comply']))	// Delete a category with all forums and posts
	{
		@set_time_limit(0);

		$result = $db->query('SELECT id FROM '.$db->prefix.'forums WHERE cat_id='.$cat_to_delete) or error('Unable to fetch forum list', __FILE__, __LINE__, $db->error());
		$num_forums = $db->num_rows($result);

		for ($i = 0; $i < $num_forums; $i++)
		{
			$cur_forum = $db->result($result, $i);

			// Prune all posts and topics (start transaction)
			prune($cur_forum, 1, -1);

			// Delete the forum (end transaction)
			$db->query('DELETE FROM '.$db->prefix.'forums WHERE id='.$cur_forum, PUN_TRANS_END) or error('Unable to delete forum', __FILE__, __LINE__, $db->error());
		}

		// Locate any "orphaned redirect topics" and delete them
		$result = $db->query('SELECT t1.id FROM '.$db->prefix.'topics AS t1 LEFT OUTER JOIN '.$db->prefix.'topics AS t2 ON t1.moved_to=t2.id WHERE t2.id IS NULL AND t1.moved_to IS NOT NULL') or error('Unable to fetch redirect topics', __FILE__, __LINE__, $db->error());
		$num_orphans = $db->num_rows($result);

		if ($num_orphans)
		{
			for ($i = 0; $i < $num_orphans; $i++)
				$orphans[] = $db->result($result, $i);

			$db->query('DELETE FROM '.$db->prefix.'topics WHERE id IN('.implode(',', $orphans).')') or error('Unable to delete redirect topics', __FILE__, __LINE__, $db->error());
		}

		// Delete the category
		$db->query('DELETE FROM '.$db->prefix.'categories WHERE id='.$cat_to_delete) or error('Unable to delete category', __FILE__, __LINE__, $db->error());

		redirect('admin_categories.php', 'Category deleted. Redirecting ...');
	}
	else	// If the user hasn't comfirmed the delete
	{
		$page_title = htmlspecialchars($options['board_title']).' / Admin / Categories';
		require 'header.php';

		admin_menu('categories');

?>
<form method="post" action="admin_categories.php">
	<input type="hidden" name="cat_to_delete" value="<?php print $cat_to_delete ?>">
	<table class="punmain" cellspacing="1" cellpadding="4">
		<tr class="punhead">
			<td class="punhead">Confirm delete category</td>
		</tr>
		<tr>
			<td class="puncon2">
				<br>&nbsp;Are you sure that you want to delete this category?<br><br>
				&nbsp;WARNING! Deleting a category will delete all forums and posts (if any) in that category!<br><br>
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


else if (isset($_POST['update']))	// Change order, name and admmod_only of the categories
{
	confirm_referer('admin_categories.php');

	$cat_order = $_POST['cat_order'];
	$cat_name = $_POST['cat_name'];
	$admmod_only = $_POST['admmod_only'];

	$result = $db->query('SELECT id, position FROM '.$db->prefix.'categories ORDER BY position') or error('Unable to fetch category list', __FILE__, __LINE__, $db->error());
	$num_cats = $db->num_rows($result);

	for ($i = 0; $i < $num_cats; $i++)
	{
		if ($cat_name[$i] == '')
			message('You must enter a category name.');

		if ($cat_order[$i] == '' || preg_match('/[^0-9]/', $cat_order[$i]))
			message('Position must be an integer value.');

		if ($admmod_only[$i] != '1')
			$admmod_only[$i] = '0';

		list($cat_id, $position) = $db->fetch_row($result);

		$db->query('UPDATE '.$db->prefix.'categories SET cat_name=\''.escape($cat_name[$i]).'\', admmod_only=\''.$admmod_only[$i].'\', position='.$cat_order[$i].' WHERE id='.$cat_id) or error('Unable to update category', __FILE__, __LINE__, $db->error());
	}

	redirect('admin_categories.php', 'Category updated. Redirecting ...');
}


// Generate an array with all categories
$result = $db->query('SELECT id, cat_name, admmod_only, position FROM '.$db->prefix.'categories ORDER BY position') or error('Unable to fetch category list', __FILE__, __LINE__, $db->error());
$num_cats = $db->num_rows($result);

for ($i = 0; $i < $num_cats; $i++)
	$cat_list[] = $db->fetch_row($result);


$page_title = htmlspecialchars($options['board_title']).' / Admin / Categories';
require 'header.php';

admin_menu('categories');

?>
<form method="post" action="admin_categories.php?action=foo">
	<table class="punmain" cellspacing="1" cellpadding="4">
		<tr class="punhead">
			<td class="punhead" colspan="2">Add/remove/edit categories</td>
		</tr>
<?php if ($num_cats): ?>		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap">Edit categories&nbsp;&nbsp;</td>
			<td class="puncon2">
<?php

foreach ($cat_list as $cat_info) {
    list($cat_id, $cat_name, $admmod_only, $position) = $cat_info;
    // do something with the variables

?>
				<br>&nbsp;&nbsp;Position&nbsp;&nbsp;<input type="text" name="cat_order[<?php print $i ?>]" value="<?php print $position ?>" size="3" maxlength="3">&nbsp;&nbsp&nbsp&nbsp;&nbsp;Name&nbsp;&nbsp;<input type="text" name="cat_name[<?php print $i ?>]" value="<?php print htmlspecialchars($cat_name) ?>" size="30" maxlength="30">&nbsp;&nbsp&nbsp&nbsp;&nbsp;<input type="checkbox" name="admmod_only[<?php print $i ?>]" value="1"<?php if ($admmod_only == '1') print ' checked' ?>>&nbsp;Admins/moderators only
<?php

	}

?>
				<br><br>&nbsp;&nbsp;<input type="submit" name="update" value="Update"><br><br>
			</td>
		</tr>
<?php endif; ?>		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap">Add a new category&nbsp;&nbsp;</td>
			<td class="puncon2">
				<br>&nbsp;<input type="text" name="new_cat_name" size="30" maxlength="30"><br><br>
				&nbsp;&nbsp;<input type="submit" name="add_cat" value=" Add "><br><br>
			</td>
		</tr>
<?php if ($num_cats): ?>		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap">Delete a category&nbsp;&nbsp;</td>
			<td class="puncon2">
				<br>&nbsp;<select name="cat_to_delete">
<?php

	@reset($cat_list);
	foreach ($cat_list as [$cat_id, $cat_name])
		print "\t\t\t\t\t".'<option value="'.$cat_id.'">'.htmlspecialchars($cat_name).'</option>'."\n";

?>
				</select><br><br>
				&nbsp;&nbsp;<input type="submit" name="del_cat" value="Delete"><br><br>
			</td>
		</tr>
<?php endif; ?>	</table>
</form>

<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>
<?php

require 'footer.php';
