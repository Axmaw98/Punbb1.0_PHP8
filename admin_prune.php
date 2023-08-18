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


if (isset($_GET['action']) || isset($_POST['prune']) || isset($_POST['comply']))
{
	if (isset($_POST['comply']))
	{
		confirm_referer('admin_prune.php');

		$prune_from = $_POST['prune_from'];
		$prune_days = intval($_POST['prune_days']);
		$prune_date = ($prune_days > 0) ? time() - ($prune_days*86400) : -1;

		@set_time_limit(0);

		if ($prune_from == 'all')
		{
			$result = $db->query('SELECT id FROM '.$db->prefix.'forums') or error('Unable to fetch forum list', __FILE__, __LINE__, $db->error());
			$num_forums = $db->num_rows($result);

			for ($i = 0; $i < $num_forums; $i++)
			{
				$fid = $db->result($result, $i);

				prune($fid, $_POST['prune_sticky'], $prune_date);	// start transaction
				update_forum($fid, PUN_TRANS_END);	// end transaction
			}
		}
		else
		{
			prune($prune_from, $_POST['prune_sticky'], $prune_date);	// start transaction
			update_forum($prune_from, PUN_TRANS_END);	// end transaction
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

		redirect('admin_prune.php', 'Posts pruned. Redirecting ...');
	}
	else
	{
		$prune_days = $_POST['req_prune_days'];
		if (preg_match('/[^0-9]/', $prune_days))
			message('Days to prune must be a positive integer.');

		$prune_date = time() - ($prune_days*86400);
		$prune_from = $_POST['prune_from'];

		// Concatenate together the query for counting number or topics to prune
		$sql = 'SELECT COUNT(id) FROM '.$db->prefix.'topics WHERE last_post<'.$prune_date;

		if ($_POST['prune_sticky'] == '0')
			$sql .= ' AND sticky=\'0\'';

		if ($prune_from != 'all')
		{
			$sql .= ' AND forum_id='.$prune_from;

			// Fetch the forum name (just for cosmetic reasons)
			$result = $db->query('SELECT forum_name FROM '.$db->prefix.'forums WHERE id='.$prune_from) or error('Unable to fetch forum name', __FILE__, __LINE__, $db->error());
			$forum = '"'.$db->result($result, 0).'"';
		}
		else
			$forum = 'all forums';

		$result = $db->query($sql) or error('Unable to fetch topic prune count', __FILE__, __LINE__, $db->error());
		$num_topics = $db->result($result, 0);

		if (!$num_topics)
			message('There are no topics that are '.$prune_days.' days old. Please decrease the value of "Days old" and try again.');


		$page_title = htmlspecialchars($options['board_title']).' / Admin / Prune';
		require 'header.php';

		admin_menu('prune');

?>
<form method="post" action="admin_prune.php?action=foo">
	<input type="hidden" name="prune_days" value="<?php print $prune_days ?>">
	<input type="hidden" name="prune_sticky" value="<?php print $_POST['prune_sticky'] ?>">
	<input type="hidden" name="prune_from" value="<?php print $prune_from ?>">
	<table class="punmain" cellspacing="1" cellpadding="4">
		<tr class="punhead">
			<td class="punhead">Confirm prune posts</td>
		</tr>
		<tr>
			<td class="puncon2">
				<br>&nbsp;Are you sure that you want to prune all topics older than <?php print $prune_days ?> days from <?php print $forum ?>? (<?php print $num_topics ?> topics)<br><br>
				&nbsp;WARNING! Pruning posts deletes them permanently.<br><br>
				&nbsp;<input type="submit" name="comply" value=" OK ">&nbsp;&nbsp;&nbsp;<a href="javascript:history.go(-1)">Go back</a><br><br>
			</td>
		</tr>
	</table>
</form>

<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>
<?php

	}

	require 'footer.php';
}


else
{
	$page_title = htmlspecialchars($options['board_title']).' / Admin / Prune';
	$validate_form = true;
	$form_name = 'prune';
	$focus_element = 'req_prune_days';
	require 'header.php';

	admin_menu('prune');

?>
<form method="post" action="admin_prune.php?action=foo" id="prune" onsubmit="return process_form(this)">
	<input type="hidden" name="form_sent" value="1">
	<table class="punmain" cellspacing="1" cellpadding="4">
		<tr class="punhead">
			<td class="punhead" colspan="2">Prune old posts</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap">Prune&nbsp;&nbsp;</td>
			<td class="puncon2">
				<table class="punplain" cellpadding="6">
					<tr>
						<td class="punright" style="width: 35%"><b>Days old</b><br>The number of days "old" a topic must be to be pruned. E.g. if you were to enter 30, every topic that didn't contain a post from up til 30 days ago would be deleted.</td>
						<td style="width: 35%"><input type="text" name="req_prune_days" size="3" maxlength="3" tabindex="1"></td>
						<td style="width: 30%" rowspan="3"><input type="submit" name="prune" value="Prune" tabindex="3"></td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Prune sticky topics</b><br>When enabled sticky topics will also be pruned.</td>
						<td style="width: 35%"><input type="radio" name="prune_sticky" value="1" checked>&nbsp;Yes&nbsp;&nbsp;&nbsp;<input type="radio" name="prune_sticky" value="0">&nbsp;No</td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Prune from forum</b><br>The forum from which you want to prune posts.</td>
						<td style="width: 35%">
							<select name="prune_from" tabindex="2">
								<option value="all">All forums</option>
<?php

	$result = $db->query('SELECT c.id AS cid, c.cat_name, f.id AS fid, f.forum_name FROM '.$db->prefix.'categories AS c INNER JOIN '.$db->prefix.'forums AS f ON c.id=f.cat_id'.$extra.' ORDER BY c.position, cid, f.position') or error('Unable to fetch category/forum list', __FILE__, __LINE__, $db->error());
	$num_forums = $db->num_rows($result);

	while ($num_forums--)
	{
		$forum = $db->fetch_assoc($result);

		if ($forum['cid'] != $cur_category)	// Are we still in the same category?
		{
			if (!empty($cur_category))
				print "\t\t\t\t\t\t\t\t".'</optgroup>'."\n";

			print "\t\t\t\t\t\t\t\t".'<optgroup label="'.htmlspecialchars($forum['cat_name']).'">'."\n";
			$cur_category = $forum['cid'];
		}

		print "\t\t\t\t\t\t\t\t\t".'<option value="'.$forum['fid'].'">'.htmlspecialchars($forum['forum_name']).'</option>'."\n";
	}

?>
								</optgroup>
							</select>
						</td>
					</tr>
					<tr>
						<td colspan="3">Use this feature with caution. Pruned posts can <b>never</b> be recovered. For best performance you should put the forum in maintenance mode during pruning.</td>
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
