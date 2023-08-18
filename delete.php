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


if ($cookie['is_guest'])
	message($lang_common['No permission']);


$id = intval($_GET['id']);
if (empty($id))
	message($lang_common['Bad request']);

// Load the delete.php language file
require 'lang/'.$language.'/'.$language.'_delete.php';

// Fetch some info from the post we are deleting
$result = $db->query('SELECT poster, poster_id, message, smilies, topic_id FROM '.$db->prefix.'posts WHERE id='.$id) or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
if (!$db->num_rows($result))
	message($lang_common['Bad request']);

$cur_post = $db->fetch_assoc($result);

// Determine whether this post is the "topic post" or not
$result = $db->query('SELECT id FROM '.$db->prefix.'posts WHERE topic_id='.$cur_post['topic_id'].' ORDER BY posted LIMIT 1') or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
$topicpost_id = $db->result($result, 0);

$is_topicpost = ($id == $topicpost_id) ? true : false;

// Fetch some info from the topic in which the post is located
$result = $db->query('SELECT subject, closed, forum_id FROM '.$db->prefix.'topics WHERE id='.$cur_post['topic_id']) or error('Unable to fetch topic info', __FILE__, __LINE__, $db->error());
list($subject, $topic_closed, $forum_id) = $db->fetch_row($result);

$forum_closed = '0';
$is_admmod = is_admmod($forum_id, $forum_closed, $admmod_only);

// If the current user isn't an administrator or a moderator of this forum
if (!$is_admmod)
{
	if ($admmod_only == '1' && $cur_user['status'] < 1 ||
	    $topic_closed == '1' ||
	    $forum_closed == '1' ||
	    $permissions['users_del_post'] == '0' && $cur_user['status'] < 1 ||
	    $is_topicpost && $permissions['users_del_topic'] == '0' && $cur_user['status'] < 1 ||
	    $cur_post['poster_id'] != $cur_user['id'])
		message($lang_common['No permission']);
}


if (isset($_POST['delete']))
{
	if ($is_admmod)
		confirm_referer('delete.php');

	require 'include/searchidx.php';

	// If it isn't the topic post
	if (!$is_topicpost)
	{
		$result = $db->query('SELECT id, poster, posted FROM '.$db->prefix.'posts WHERE topic_id='.$cur_post['topic_id'].' ORDER BY posted DESC LIMIT 2') or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
		list($last_id, ,) = $db->fetch_row($result);
		list($second_last_id, $second_poster, $second_posted) = $db->fetch_row($result);

		// Delete the post (start transaction)
		$db->query('DELETE FROM '.$db->prefix.'posts WHERE id='.$id, PUN_TRANS_START) or error('Unable to delete post', __FILE__, __LINE__, $db->error());

		strip_search_index($id);

		// If the message we deleted is the most recent in the topic (at the end of the topic)
		if ($last_id == $id)
		{
			// If there is a $second_last_id there is more than 1 reply to the topic
			if ($second_last_id != NULL)
				$db->query('UPDATE '.$db->prefix.'topics SET last_post='.$second_posted.', last_post_id='.$second_last_id.', last_poster=\''.addslashes($second_poster).'\', num_replies=num_replies-1 WHERE id='.$cur_post['topic_id']) or error('Unable to update topic', __FILE__, __LINE__, $db->error());
			else
				// We deleted the only reply, so now last_post/last_post_id/last_poster is posted/id/poster from the topic itself
				$db->query('UPDATE '.$db->prefix.'topics SET last_post=posted, last_post_id=id, last_poster=poster, num_replies=num_replies-1 WHERE id='.$cur_post['topic_id']) or error('Unable to update topic', __FILE__, __LINE__, $db->error());
		}
		else
			// Otherwise we just decrement the reply counter
			$db->query('UPDATE '.$db->prefix.'topics SET num_replies=num_replies-1 WHERE id='.$cur_post['topic_id']) or error('Unable to update topic', __FILE__, __LINE__, $db->error());

		update_forum($forum_id, PUN_TRANS_END);	// end transaction

		redirect('viewtopic.php?id='.$cur_post['topic_id'], $lang_delete['Post del redirect']);
	}
	else	// It's the topic post
	{
		// Delete the topic and any redirect topics (start transaction)
		$db->query('DELETE FROM '.$db->prefix.'topics WHERE id='.$cur_post['topic_id'].' OR moved_to='.$cur_post['topic_id'], PUN_TRANS_START) or error('Unable to delete topic', __FILE__, __LINE__, $db->error());

		// Create a list of the post ID's in this topic and then strip the search index
		$result = $db->query('SELECT id FROM '.$db->prefix.'posts WHERE topic_id='.$cur_post['topic_id']) or error('Unable to fetch posts', __FILE__, __LINE__, $db->error());
		while ($row = $db->fetch_row($result))
			$post_ids .= ($post_ids != '') ? ','.$row[0] : $row[0];

		strip_search_index($post_ids);

		// Delete posts in topic
		$db->query('DELETE FROM '.$db->prefix.'posts WHERE topic_id='.$cur_post['topic_id']) or error('Unable to delete posts', __FILE__, __LINE__, $db->error());

		update_forum($forum_id, PUN_TRANS_END);	// end transaction

		redirect('viewforum.php?id='.$forum_id, $lang_delete['Topic del redirect']);
	}
}


else
{
	$page_title = htmlspecialchars($options['board_title']).' / '.$lang_delete['Delete post'];
	require 'header.php';

	require 'include/parser.php';

	$cur_post['message'] = parse_message($cur_post['message'], $cur_post['smilies']);

?>
<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>

<form method="post" action="delete.php?id=<?php print $id ?>">
	<table class="punmain" cellspacing="1" cellpadding="4">
		<tr class="punhead">
			<td class="punhead" colspan="2"><?php print $lang_delete['Delete post'] ?></td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_common['Author'] ?>&nbsp;&nbsp;</td>
			<td class="puncon2">&nbsp;<?php print htmlspecialchars($cur_post['poster']) ?></td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_common['Message'] ?>&nbsp;&nbsp;</td>
			<td class="puncon2">
				<table class="punplain" cellspacing="0" cellpadding="4">
					<tr><td><span class="puntext"><?php print $cur_post['message'] ?></span></td></tr>
				</table>
			</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_common['Actions'] ?>&nbsp;&nbsp;</td>
			<td class="puncon2">
				<br>&nbsp;<?php print $lang_delete['Warning'] ?><br><br>
				&nbsp;&nbsp;<input type="submit" name="delete" value="<?php print $lang_delete['Delete'] ?>">&nbsp;&nbsp;&nbsp;<a href="javascript:history.go(-1)"><?php print $lang_common['Go back'] ?></a><br><br>
			</td>
		</tr>
	</table>
</form>

<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>
<?php

	require 'footer.php';
}
