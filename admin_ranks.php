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


// Add a rank
if (isset($_POST['add_rank']))
{
	confirm_referer('admin_ranks.php');

	$rank = trim($_POST['new_rank']);
	$min_posts = $_POST['new_min_posts'];

	if ($rank == '')
		message('You must enter a rank title.');

	if ($min_posts == '' || preg_match('/[^0-9]/', $min_posts))
		message('Minimum posts must be a positive integer value.');

	// Make sure there isn't already a rank with the same min_posts value
	$result = $db->query('SELECT NULL FROM '.$db->prefix.'ranks WHERE min_posts='.$min_posts) or error('Unable to fetch rank info', __FILE__, __LINE__, $db->error());
	if ($db->num_rows($result))
		message('There is already a rank with a minimun posts value of '.$min_posts.'.');

	$db->query('INSERT INTO '.$db->prefix.'ranks (rank, min_posts) VALUES(\''.escape($rank).'\', '.$min_posts.')') or error('Unable to add rank', __FILE__, __LINE__, $db->error());

	redirect('admin_ranks.php', 'Rank added. Redirecting ...');
}


// Update a rank
else if (isset($_POST['update']))
{
	confirm_referer('admin_ranks.php');

	$id = key($_POST['update']);

	$rank = trim($_POST['rank'][$id]);
	$min_posts = trim($_POST['min_posts'][$id]);

	if ($rank == '')
		message('You must enter a rank title.');

	if ($min_posts == '' || preg_match('/[^0-9]/', $min_posts))
		message('Minimum posts must be a positive integer value.');

	$db->query('UPDATE '.$db->prefix.'ranks SET rank=\''.escape($rank).'\', min_posts='.$min_posts.' WHERE id='.$id) or error('Unable to update rank', __FILE__, __LINE__, $db->error());

	redirect('admin_ranks.php', 'Rank updated. Redirecting ...');
}


// Remove a rank
else if (isset($_POST['remove']))
{
	confirm_referer('admin_ranks.php');

	$id = key($_POST['remove']);

	$db->query('DELETE FROM '.$db->prefix.'ranks WHERE id='.$id) or error('Unable to delete rank', __FILE__, __LINE__, $db->error());

	redirect('admin_ranks.php', 'Rank removed. Redirecting ...');
}


$page_title = htmlspecialchars($options['board_title']).' / Admin / Ranks';
$form_name = 'ranks';
$focus_element = 'new_rank';
require 'header.php';

admin_menu('ranks');

?>
<form method="post" action="admin_ranks.php?action=foo" id="ranks">
	<table class="punmain" cellspacing="1" cellpadding="4">
		<tr class="punhead">
			<td class="punhead" colspan="2">Ranks</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap">Add rank&nbsp;&nbsp;</td>
			<td class="puncon2">
				<table class="punplain" cellpadding="6">
					<tr>
						<td colspan="3">Enter a rank and the minimum number of posts that a user has to have to aquire the rank. Different ranks cannot have the same value for minimum posts. If a title is set for a user, the title will be displayed instead of any rank. <b>User ranks must be enabled in <a href="admin_options.php#ranks">Options</a> for this to have any effect.</b><br><br></td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Rank title</b><br>Text to be displayed under username.</td>
						<td style="width: 35%"><input type="text" name="new_rank" size="25" maxlength="50" tabindex="1"></td>
						<td style="width: 30%" rowspan="2"><input type="submit" name="add_rank" value=" Add " tabindex="3"></td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Minimum posts</b><br>The minimum number of posts a user must have to attain this rank.</td>
						<td style="width: 35%"><input type="text" name="new_min_posts" size="7" maxlength="7" tabindex="2"></td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap">Edit/remove ranks&nbsp;&nbsp;</td>
			<td class="puncon2">
				<table class="punplain" cellpadding="6">
					<tr>
						<td>
<?php

$result = $db->query('SELECT id, rank, min_posts FROM '.$db->prefix.'ranks ORDER BY min_posts') or error('Unable to fetch rank list', __FILE__, __LINE__, $db->error());
if ($db->num_rows($result))
{
	while ($cur_rank = $db->fetch_assoc($result))
		print "\t\t\t\t\t\t\t".'&nbsp;&nbsp;&nbsp;Rank title&nbsp;&nbsp;<input type="text" name="rank['.$cur_rank['id'].']" value="'.$cur_rank['rank'].'" size="25" maxlength="50">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Minimum posts&nbsp;&nbsp;<input type="text" name="min_posts['.$cur_rank['id'].']" value="'.$cur_rank['min_posts'].'" size="7" maxlength="7">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" name="update['.$cur_rank['id'].']" value="Update">&nbsp;<input type="submit" name="remove['.$cur_rank['id'].']" value="Remove"><br>'."\n";
}
else
	print "\t\t\t\t\t\t\t".'No ranks in list.'."\n";

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
