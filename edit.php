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

// Load the edit.php language file
require 'lang/'.$language.'/'.$language.'_edit.php';

// Fetch some info from the post we are editing
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
		$permissions['users_edit_post'] == '0' && $cur_user['status'] < 1 ||
		$cur_post['poster_id'] != $cur_user['id'])
		message($lang_common['No permission']);
}


if (isset($_POST['form_sent']))
{
	if ($is_admmod)
		confirm_referer('edit.php');

	$smilies = $_POST['smilies'];

	// If it is a topic it must contain a subject
	if ($is_topicpost && $is_admmod)
	{
		$subject = trim(un_escape($_POST['req_subject']));

		if ($subject == '')
			message($lang_edit['No subject']);
		else if (strlen($subject) > 70)
			message($lang_edit['Too long subject']);
		else if ($permissions['subject_all_caps'] == '0' && !preg_match('/[[:lower:]]/', $subject) && $cur_user['status'] < 1)
			message($lang_edit['No caps subject']);
	}

	// Make sure all newlines are \n and not \r\n or \r
	$message = str_replace("\r", "\n", str_replace("\r\n", "\n", trim(un_escape($_POST['req_message']))));

	if ($message == '')
		message($lang_edit['No message']);
	else if (strlen($message) > 65535)
		message($lang_edit['Too long message']);
	else if ($permissions['message_all_caps'] === '0' && !preg_match("/[[:lower:]]/", $message) && $cur_user['status'] < 1)
		message($lang_edit['No caps message']);


	// Validate BBCode syntax
	if ($permissions['message_bbcode'] == '1' && strpos($message, '[') !== false && strpos($message, ']') !== false)
	{
		// Change all BBCodes to lower case (this way a lot of regex searches can be case sensitive)
		$a = array('[B]', '[I]', '[U]', '[/B]', '[/I]', '[/U]');
		$b = array('[b]', '[i]', '[u]', '[/b]', '[/i]', '[/u]');
		$message = str_replace($a, $b, $message);

		$a = array("#\[quote\]#i", "#\[/quote\]#i", "#\[code\]#i", "#\[/code\]#i", "#\[colou?r=([a-zA-Z]*|\#?[0-9a-fA-F]{6})\]#i", "#\[/colou?r\]#i", "#\[img\]#i", "#\[/img\]#i", "#\[email\]#i", "#\[email=#i", "#\[/email\]#i", "#\[url\]#i", "#\[url=#i", "#\[/url\]#i");
		$b = array('[quote]', '[/quote]', '[code]', '[/code]', "[color=\\1]", '[/color]', '[img]', '[/img]', '[email]', '[email=', '[/email]', '[url]', '[url=', '[/url]');
		$message = preg_replace($a, $b, $message);

		require 'include/parser.php';
		if ($overflow = check_tag_order($message))
			// The quote depth level was too high, so we strip out the inner most quote(s)
			$message = substr($message, 0, $overflow[0]).substr($message, $overflow[1], (strlen($message) - $overflow[0]));
	}


	require 'include/searchidx.php';

	if ($smilies != '1') $smilies = '0';

	if (!isset($_POST['silent']) || !$is_admmod)
		$edited_sql = ', edited='.time().', edited_by=\''.addslashes($cur_user['username']).'\'';

	if ($is_topicpost && $is_admmod)
	{
		// Update the topic
		$db->query('UPDATE '.$db->prefix.'topics SET subject=\''.addslashes($subject).'\' WHERE id='.$cur_post['topic_id']) or error('Unable to update topic', __FILE__, __LINE__, $db->error());

		// Update any redirect topics as well
		$db->query('UPDATE '.$db->prefix.'topics SET subject=\''.addslashes($subject).'\' WHERE moved_to='.$cur_post['topic_id']) or error('Unable to update redirect topic', __FILE__, __LINE__, $db->error());

		// We changed the subject, so we need to take that into account when we update the search words
		update_search_index('edit', $id, $message, $subject);
	}
	else
		update_search_index('edit', $id, $message);

	// Update the post
	$db->query('UPDATE '.$db->prefix.'posts SET message=\''.addslashes($message).'\', smilies=\''.$smilies.'\''.isset($edited_sql).' WHERE id='.$id) or error('Unable to update post', __FILE__, __LINE__, $db->error());

	redirect('viewtopic.php?pid='.$id.'#'.$id, $lang_edit['Edit redirect']);
}


else
{
	if ($options['smilies'] == '1')
	{
		if ($cur_post['smilies'] == '1')
			$checkboxes[] = '<input type="checkbox" name="smilies" value="1" checked>&nbsp;'.$lang_edit['Show smilies'];
		else
			$checkboxes[] = '<input type="checkbox" name="smilies" value="1">&nbsp;'.$lang_edit['Show smilies'];
	}

	if ($is_admmod)
		$checkboxes[] = '<input type="checkbox" name="silent" value="1" checked>&nbsp;'.$lang_edit['Silent edit'];

	if (isset($checkboxes))
		$checkboxes = implode('<br>'."\n\t\t\t\t", $checkboxes);


	$page_title = htmlspecialchars($options['board_title']).' / '.$lang_edit['Edit message'];
	$validate_form = true;
	$form_name = 'edit';
	$focus_element = 'req_message';
	require 'header.php';

?>
<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>

<form method="post" action="edit.php?id=<?php print $id ?>&amp;action=edit" id="edit" onsubmit="return process_form(this)">
	<input type="hidden" name="form_sent" value="1">
	<table class="punmain" cellspacing="1" cellpadding="4">
		<tr class="punhead">
			<td class="punhead" colspan="2"><?php print $lang_edit['Edit message'] ?></td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><b><?php print $lang_common['Author'] ?></b>&nbsp;&nbsp;</td>
			<td class="puncon2">&nbsp;<?php print htmlspecialchars($cur_post['poster']) ?></td>
		</tr>
<?php if ($is_topicpost && $is_admmod): ?>		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><b><?php print $lang_edit['Subject'] ?></b>&nbsp;&nbsp;</td>
			<td class="puncon2">&nbsp;<input type="text" name="req_subject" size="80" maxlength="70" value="<?php print htmlspecialchars($subject) ?>"></td>
		</tr>
<?php endif; ?>		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap">
				<b><?php print $lang_common['Message'] ?></b>&nbsp;&nbsp;<br><br>
				HTML: <?php print ($permissions['message_html'] == '1') ? $lang_common['on'] : $lang_common['off']; ?>&nbsp;&nbsp;<br>
				<a href="help.php" target="_blank">BBCode</a>: <?php print ($permissions['message_bbcode'] == '1') ? $lang_common['on'] : $lang_common['off']; ?>&nbsp;&nbsp;<br>
				<a href="help.php" target="_blank">[img] tag</a>: <?php print ($permissions['message_img_tag'] == '1') ? $lang_common['on'] : $lang_common['off']; ?>&nbsp;&nbsp;<br>
				<a href="help.php" target="_blank">Smilies</a>: <?php print ($options['smilies'] == '1') ? $lang_common['on'] : $lang_common['off']; ?>&nbsp;&nbsp;
			</td>
			<td class="puncon2">&nbsp;<textarea name="req_message" rows="20" cols="95"><?php print htmlspecialchars($cur_post['message']) ?></textarea></td>
		</tr>
<?php if (isset($checkboxes)): ?>		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_common['Options'] ?>&nbsp;&nbsp;</td>
			<td class="puncon2">
				<?php print $checkboxes."\n" ?>
			</td>
		</tr>
<?php endif; ?>		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_common['Actions'] ?>&nbsp;&nbsp;</td>
			<td class="puncon2"><br>&nbsp;&nbsp;<input type="submit" name="submit" value="<?php print $lang_common['Submit'] ?>" accesskey="s">&nbsp;&nbsp;&nbsp;<a href="javascript:history.go(-1)"><?php print $lang_common['Go back'] ?></a><br><br></td>
		</tr>
	</table>
</form>

<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>
<?php

	require 'footer.php';
}
