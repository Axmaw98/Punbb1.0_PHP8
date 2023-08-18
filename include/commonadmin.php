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


// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;


//
// Displays link to admin pages (for moderators)
//
function admin_menu($page = NULL)
{

?>
<table class="punplain" cellspacing="1" cellpadding="4">
	<tr>
		<td class="<?php print (!strcmp($page, 'categories')) ? 'puncon1cent' : 'puncent'; ?>" style="width: 9%; white-space: nowrap"><b><a href="admin_categories.php">Categories</a></b></td>
		<td class="<?php print (!strcmp($page, 'forums')) ? 'puncon1cent' : 'puncent'; ?>" style="width: 9%; white-space: nowrap"><b><a href="admin_forums.php">Forums</a></b></td>
		<td class="<?php print (!strcmp($page, 'users')) ? 'puncon1cent' : 'puncent'; ?>" style="width: 9%; white-space: nowrap"><b><a href="admin_users.php">Users</a></b></td>
		<td class="<?php print (!strcmp($page, 'options')) ? 'puncon1cent' : 'puncent'; ?>" style="width: 9%; white-space: nowrap"><b><a href="admin_options.php">Options</a></b></td>
		<td class="<?php print (!strcmp($page, 'permissions')) ? 'puncon1cent' : 'puncent'; ?>" style="width: 9%; white-space: nowrap"><b><a href="admin_permissions.php">Permissions</a></b></td>
		<td class="<?php print (!strcmp($page, 'censoring')) ? 'puncon1cent' : 'puncent'; ?>" style="width: 9%; white-space: nowrap"><b><a href="admin_censoring.php">Censoring</a></b></td>
		<td class="<?php print (!strcmp($page, 'ranks')) ? 'puncon1cent' : 'puncent'; ?>" style="width: 9%; white-space: nowrap"><b><a href="admin_ranks.php">Ranks</a></b></td>
		<td class="<?php print (!strcmp($page, 'bans')) ? 'puncon1cent' : 'puncent'; ?>" style="width: 9%; white-space: nowrap"><b><a href="admin_bans.php">Bans</a></b></td>
		<td class="<?php print (!strcmp($page, 'prune')) ? 'puncon1cent' : 'puncent'; ?>" style="width: 9%; white-space: nowrap"><b><a href="admin_prune.php">Prune</a></b></td>
		<td class="<?php print (!strcmp($page, 'maintenance')) ? 'puncon1cent' : 'puncent'; ?>" style="width: 10%; white-space: nowrap"><b><a href="admin_maintenance.php">Maintenance</a></b></td>
		<td class="<?php print (!strcmp($page, 'reports')) ? 'puncon1cent' : 'puncent'; ?>" style="width: 9%; white-space: nowrap"><b><a href="admin_reports.php">Reports</a></b></td>
	</tr>
</table>

<?php

}


//
// Displays link to admin pages (for moderators)
//
function moderator_menu($page = NULL)
{

?>
<table class="punplain" cellspacing="1" cellpadding="4">
	<tr>
		<td class="<?php print (!strcmp($page, 'users')) ? 'puncon1cent' : 'puncent'; ?>" style="width: 25%; white-space: nowrap"><b><a href="admin_users.php">Users</a></b></td>
		<td class="<?php print (!strcmp($page, 'censoring')) ? 'puncon1cent' : 'puncent'; ?>" style="width: 25%; white-space: nowrap"><b><a href="admin_censoring.php">Censoring</a></b></td>
		<td class="<?php print (!strcmp($page, 'bans')) ? 'puncon1cent' : 'puncent'; ?>" style="width: 25%; white-space: nowrap"><b><a href="admin_bans.php">Bans</a></b></td>
		<td class="<?php print (!strcmp($page, 'reports')) ? 'puncon1cent' : 'puncent'; ?>" style="width: 25%; white-space: nowrap"><b><a href="admin_reports.php">Reports</a></b></td>
	</tr>
</table>

<?php

}


//
// Delete topics from $forum_id that are "older than" $prune_date (if $prune_sticky is 1, sticky topics will also be deleted)
//
function prune($forum_id, $prune_sticky, $prune_date)
{
	global $db;

	if ($prune_date != -1)
		$extra = ' AND last_post<'.$prune_date;

	if (!$prune_sticky)
		$extra .= ' AND sticky=\'0\'';

	// Fetch topics to prune
	$result = $db->query('SELECT id FROM '.$db->prefix.'topics WHERE forum_id='.$forum_id.$extra) or error('Unable to fetch topics', __FILE__, __LINE__, $db->error());

	while ($row = $db->fetch_row($result))
		$topic_ids .= (($topic_ids != '') ? ',' : '').$row[0];

	if ($topic_ids != '')
	{
		// Fetch posts to prune
		$result = $db->query('SELECT id FROM '.$db->prefix.'posts WHERE topic_id IN('.$topic_ids.')') or error('Unable to fetch posts', __FILE__, __LINE__, $db->error());

		while ($row = $db->fetch_row($result))
			$post_ids .= (($post_ids != '') ? ',' : '').$row[0];

		if ($post_ids != '')
		{
			// Delete topics (start transaction)
			// End transaction must be done after prune() (i.e. in updateForum() or when deleting a forum)
			$db->query('DELETE FROM '.$db->prefix.'topics WHERE id IN('.$topic_ids.')', PUN_TRANS_START) or error('Unable to prune topics', __FILE__, __LINE__, $db->error());
			$db->query('DELETE FROM '.$db->prefix.'posts WHERE id IN('.$post_ids.')') or error('Unable to prune posts', __FILE__, __LINE__, $db->error());

			// We removed a bunch of posts, so now we have to update the search index
			require 'include/searchidx.php';
			strip_search_index($post_ids);
		}
	}
}
