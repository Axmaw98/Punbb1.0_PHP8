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


if ($cookie['is_guest'] && $permissions['guests_read'] == '0')
	message($lang_common['Login required'].' <a href="login.php">'.$lang_common['Login'].'</a> '.$lang_common['or'].' <a href="register.php">'.$lang_common['register'].'</a>.');


// Load the post.php language file
require 'lang/'.$language.'/'.$language.'_post.php';

if (isset($_POST['form_sent']))
{
	// Flood protection
	if (isset($cur_user['status']) < 1 && isset($cur_user['last_post']) != '' && (time() - $cur_user['last_post']) < $options['flood_interval'])
		message($lang_post['Flood start'].' '.$options['flood_interval'].' '.$lang_post['flood end']);

	// Make sure form_user is correct
	if (($cookie['is_guest'] && $_POST['form_user'] != 'Guest') || (!$cookie['is_guest'] && $_POST['form_user'] != $cur_user['username']))
		message($lang_common['Bad request']);

	$smilies = $_POST['smilies'];

	// If it's a reply
	if (isset($_GET['tid']))
	{
		$tid = intval($_GET['tid']);
		if (empty($tid))
			message($lang_common['Bad request']);

		if ($permissions['users_post'] == '0' && $cur_user['status'] < 1 || $permissions['guests_post'] == '0' && $cookie['is_guest'])
			message($lang_common['No permission']);

		$result = $db->query('SELECT closed, forum_id FROM '.$db->prefix.'topics WHERE id='.$tid) or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
		if (!$db->num_rows($result))
			message($lang_common['Bad request']);

		list($closed, $forum_id) = $db->fetch_row($result);

		$forum_closed = '0';
		if (!is_admmod($forum_id, $forum_closed, $admmod_only))
		{
			if ($admmod_only == '1' && $cur_user['status'] < 1 || $closed == '1' || $forum_closed == '1')
				message($lang_common['No permission']);
		}
	}
	// If it's a new topic
	else if (isset($_GET['fid']))
	{
		$fid = intval($_GET['fid']);
		if (empty($fid))
			message($lang_common['Bad request']);

		if ($permissions['users_post_topic'] == '0' && $cur_user['status'] < 1 || $permissions['guests_post_topic'] == '0' && $cookie['is_guest'])
			message($lang_common['No permission']);

		$result = $db->query('SELECT moderators, admmod_only, closed FROM '.$db->prefix.'forums WHERE id='.$fid) or error('Unable to fetch forum info', __FILE__, __LINE__, $db->error());
		if (!$db->num_rows($result))
			message($lang_common['Bad request']);

		list($moderators, $admmod_only, $forum_closed) = $db->fetch_row($result);
		$mods_array = ($moderators != '') ? unserialize($moderators) : array();

		if ($admmod_only == '1' && $cur_user['status'] < 1 || $forum_closed == '1' && $cur_user['status'] < 2 && !array_key_exists($cur_user['username'], $mods_array))
			message($lang_common['No permission']);

		$subject = trim(un_escape($_POST['req_subject']));
		if ($subject == '')
			message($lang_post['No subject']);
		else if (strlen($subject) > 70)
			message($lang_post['Too long subject']);
			else if ($permissions['subject_all_caps'] == '0' && !preg_match("/[[:lower:]]/", $subject) && $cur_user['status'] < 1)
			message($lang_post['No caps subject']);
	}
	else
		message($lang_common['Bad request']);


	// If the user is logged in we get the username and e-mail from $cur_user
	if (!$cookie['is_guest'])
	{
		$username = $cur_user['username'];
		$email = $cur_user['email'];
	}
	// Otherwise it should be in $_POST
	else
	{
		$username = trim(un_escape($_POST['req_username']));
		$email = trim($_POST['req_email']);

		// Load the register.php/profile.php language files
		require 'lang/'.$language.'/'.$language.'_prof_reg.php';
		require 'lang/'.$language.'/'.$language.'_register.php';

		// It's a guest, so we have to check the username
		if (strlen($username) < 2)
			message($lang_prof_reg['Username too short']);
		else if (!strcasecmp($username, 'Guest') || !strcasecmp($username, $lang_common['Guest']))
			message($lang_prof_reg['Username guest']);
		else if (preg_match('/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/', $username))
			message($lang_prof_reg['Username IP']);
		else if (preg_match('#\[b\]|\[/b\]|\[u\]|\[/u\]|\[i\]|\[/i\]|\[color|\[/color\]|\[quote\]|\[/quote\]|\[code\]|\[/code\]|\[img\]|\[/img\]|\[url|\[/url\]|\[email|\[/email\]#i', $username))
			message($lang_prof_reg['Username BBCode']);

		// Check username for any censored words
		$temp = censor_words($username);
		if (strcmp($temp, $username))
			message($lang_register['Username censor']);


		// Check that the username (or a too similar username) is not already registered
		$result = $db->query('SELECT username FROM '.$db->prefix.'users WHERE username=\''.addslashes($username).'\' OR username=\''.addslashes(preg_replace("/[^\w]/", '', $username)).'\'') or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());

		if ($db->num_rows($result))
		{
			$busy = $db->result($result, 0);
			message($lang_register['Username dupe 1'].' '.htmlspecialchars($busy).'. '.$lang_register['Username dupe 2']);
		}


		require 'include/email.php';

		if (!is_valid_email($email))
			message($lang_common['Invalid e-mail']);
	}

	$message = trim(un_escape($_POST['req_message']));

	// Make sure all newlines are \n and not \r\n or \r
	$message = str_replace("\r", "\n", str_replace("\r\n", "\n", $message));

	if ($message == '')
		message($lang_post['No message']);
	else if (strlen($message) > 65535)
		message($lang_post['Too long message']);
	else if ($permissions['message_all_caps'] == '0' && !preg_match("/[[:lower:]]/", $message) && $cur_user['status'] < 1)
		message($lang_post['No caps message']);


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


	if ($smilies != '1') $smilies = '0';

	$now = time();
	require 'include/searchidx.php';

	// If it's a reply
	if (isset($_GET['tid']))
	{
		// Get the topic and any subscribed users
		$result = $db->query('SELECT subject, subscribers FROM '.$db->prefix.'topics WHERE id='.$tid) or error('Unable to fetch topic info', __FILE__, __LINE__, $db->error());
		list($subject, $subscribers_save) = $db->fetch_row($result);

		if (!$cookie['is_guest'])
		{
			// Insert the new post (start transaction)
			$db->query('INSERT INTO '.$db->prefix.'posts (poster, poster_id, poster_ip, message, smilies, posted, topic_id) VALUES(\''.addslashes($username).'\', '.$cur_user['id'].', \''.get_remote_address().'\', \''.addslashes($message).'\', \''.$smilies.'\', '.$now.', '.$tid.')', PUN_TRANS_START) or error('Unable to create post', __FILE__, __LINE__, $db->error());
			$new_pid = $db->insert_id();

			if ($options['subscriptions'] == '1' && isset($_POST['subscribe']) == '1')
			{
				if ($subscribers_save == '')
					$subscribers = $cur_user['email'];
				else
				{
					if (!strstr($subscribers_save, $cur_user['email']))
						$subscribers = $subscribers_save.','.$cur_user['email'];
					else
						$subscribers = $subscribers_save;
				}

				// Update topic
				$db->query('UPDATE '.$db->prefix.'topics SET num_replies=num_replies+1, subscribers=\''.$subscribers.'\', last_post='.$now.', last_post_id='.$new_pid.', last_poster=\''.addslashes($username).'\' WHERE id='.$tid) or error('Unable to update topic', __FILE__, __LINE__, $db->error());
			}
			else
				// Update topic
				$db->query('UPDATE '.$db->prefix.'topics SET num_replies=num_replies+1, last_post='.$now.', last_post_id='.$new_pid.', last_poster=\''.addslashes($username).'\' WHERE id='.$tid) or error('Unable to update topic', __FILE__, __LINE__, $db->error());
		}
		else
		{
			// It's a guest. Insert the new post (start transaction)
			$db->query('INSERT INTO '.$db->prefix.'posts (poster, poster_ip, poster_email, message, smilies, posted, topic_id) VALUES(\''.addslashes($username).'\', \''.get_remote_address().'\', \''.$email.'\', \''.addslashes($message).'\', \''.$smilies.'\', '.$now.', '.$tid.')', PUN_TRANS_START) or error('Unable to create post', __FILE__, __LINE__, $db->error());
			$new_pid = $db->insert_id();

			// Update topic
			$db->query('UPDATE '.$db->prefix.'topics SET num_replies=num_replies+1, last_post='.$now.', last_post_id='.$new_pid.', last_poster=\''.addslashes($username).'\' WHERE id='.$tid) or error('Unable to update topic', __FILE__, __LINE__, $db->error());
		}

		update_search_index('post', $new_pid, $message);

		update_forum($forum_id, PUN_TRANS_END);	// end transaction

		// If there are any subscribed users and it's not the posting user him/herself
		if ($subscribers_save != '' && $subscribers_save != isset($cur_user['email']))
		{
			$addresses = explode(',', $subscribers_save);
			$addresses = array_map('trim', $addresses);

			foreach ($addresses as $key => $value)
			{
				if ($value == isset($cur_user['email']))
					unset($addresses[$key]);	// Remove the user who is posting (no need to e-mail him/her)
			}
			$subscribers_save = implode(',', $addresses);

			$mail_subject = $lang_post['Reply mail 1'].': '.$subject;
			$mail_message = $username.' '.$lang_post['Reply mail 2'].' \''.$subject.'\' '.$lang_post['Reply mail 3']."\r\n\r\n".$lang_post['Reply mail 4'].' '.$options['base_url'].'/viewtopic.php?pid='.$new_pid.'#'.$new_pid."\r\n\r\n".$lang_post['Reply mail 5'].' '.$options['base_url'].'/misc.php?unsubscribe='.$tid."\r\n\r\n".'/Forum Mailer'."\r\n".'('.$lang_post['Reply mail 6'].')';
			$mail_extra = 'From: '.$options['board_title'].' Mailer <'.$options['webmaster_email'].'>';

			require_once 'include/email.php';	// It could've been included once already
			pun_mail($subscribers_save, $mail_subject, $mail_message, $mail_extra);
		}
	}
	// If it's a new topic
	else if (isset($_GET['fid']))
	{
		if (!$cookie['is_guest'])
		{
			// Create the topic (start transaction)
			if ($options['subscriptions'] == '1' && isset($_POST['subscribe']) == '1')
				$db->query('INSERT INTO '.$db->prefix.'topics (poster, subject, posted, last_post, last_poster, subscribers, forum_id) VALUES(\''.addslashes($username).'\', \''.addslashes($subject).'\', '.$now.', '.$now.', \''.addslashes($username).'\', \''.$email.'\', '.$fid.')', PUN_TRANS_START) or error('Unable to create topic', __FILE__, __LINE__, $db->error());
			else
				$db->query('INSERT INTO '.$db->prefix.'topics (poster, subject, posted, last_post, last_poster, forum_id) VALUES(\''.addslashes($username).'\', \''.addslashes($subject).'\', '.$now.', '.$now.', \''.addslashes($username).'\', '.$fid.')', PUN_TRANS_START) or error('Unable to create topic', __FILE__, __LINE__, $db->error());
			$new_tid = $db->insert_id();

			// Create the post ("topic post")
			$db->query('INSERT INTO '.$db->prefix.'posts (poster, poster_id, poster_ip, message, smilies, posted, topic_id) VALUES(\''.addslashes($username).'\', '.$cur_user['id'].', \''.get_remote_address().'\', \''.addslashes($message).'\', \''.$smilies.'\', '.$now.', '.$new_tid.')') or error('Unable to create post', __FILE__, __LINE__, $db->error());
		}
		else
		{
			// Create the topic (start transaction)
			$db->query('INSERT INTO '.$db->prefix.'topics (poster, subject, posted, last_post, last_poster, forum_id) VALUES(\''.addslashes($username).'\', \''.addslashes($subject).'\', '.$now.', '.$now.', \''.addslashes($username).'\', '.$fid.')', PUN_TRANS_START) or error('Unable to create topic', __FILE__, __LINE__, $db->error());
			$new_tid = $db->insert_id();

			// Create the post ("topic post")
			$db->query('INSERT INTO '.$db->prefix.'posts (poster, poster_ip, poster_email, message, smilies, posted, topic_id) VALUES(\''.addslashes($username).'\', \''.get_remote_address().'\', \''.$email.'\', \''.addslashes($message).'\', \''.$smilies.'\', '.$now.', '.$new_tid.')') or error('Unable to create post', __FILE__, __LINE__, $db->error());
		}
		$new_pid = $db->insert_id();

		// Update the topic with last_post_id
		$db->query('UPDATE '.$db->prefix.'topics SET last_post_id='.$new_pid.' WHERE id='.$new_tid) or error('Unable to update topic', __FILE__, __LINE__, $db->error());

		update_search_index('post', $new_pid, $message, $subject);

		update_forum($fid, PUN_TRANS_END);	// end transaction
	}

	if (!$cookie['is_guest'])
		$db->query('UPDATE '.$db->prefix.'users SET num_posts=num_posts+1, last_post='.$now.' WHERE id='.$cur_user['id']) or error('Unable to update user', __FILE__, __LINE__, $db->error());

	redirect('viewtopic.php?pid='.$new_pid.'#'.$new_pid, $lang_post['Post redirect']);
}


else
{
	// If a topic id was specified in the url (it's a reply).
	if (isset($_GET['tid']))
	{
		$tid = intval($_GET['tid']);
		if (empty($tid))
			message($lang_common['Bad request']);

		if ($permissions['users_post'] == '0' && $cur_user['status'] < 1 || $permissions['guests_post'] == '0' && $cookie['is_guest'])
			message($lang_common['No permission']);

		$result = $db->query('SELECT subject, closed, forum_id FROM '.$db->prefix.'topics WHERE id='.$tid) or error('Unable to fetch topic info', __FILE__, __LINE__, $db->error());
		if (!$db->num_rows($result))
			message($lang_common['Bad request']);

		list($subject, $closed, $forum_id) = $db->fetch_row($result);

		$forum_closed = '0';
		if (!is_admmod($forum_id, $forum_closed, $admmod_only))
		{
			if ($admmod_only == '1' && $cur_user['status'] < 1 || $closed == '1' || $forum_closed == '1')
				message($lang_common['No permission']);
		}

		$action = $lang_post['Post a reply'];
		$form = '<form method="post" action="post.php?action=post&amp;tid='.$tid.'" id="post" onsubmit="return process_form(this)">';

		// If a quoteid was specified in the url.
		if (isset($_GET['qid']))
		{
			$qid = intval($_GET['qid']);
			if (empty($qid))
				message($lang_common['Bad request']);

			$result = $db->query('SELECT poster, message FROM '.$db->prefix.'posts WHERE id='.$qid) or error('Unable to fetch quote info', __FILE__, __LINE__, $db->error());
			if (!$db->num_rows($result))
				message($lang_common['Bad request']);

			list($qposter, $qmessage) = $db->fetch_row($result);

			if ($permissions['message_bbcode'] == '1')
				$quote = '[quote][b][i]'.$qposter.' '.$lang_post['wrote'].':[/i][/b]'."\n\n".$qmessage."\n".'[/quote]'."\n";
			else
				$quote = '> '.$qposter.' '.$lang_post['wrote'].':'."\n\n".'> '.$qmessage."\n";
		}

		// We have to fetch the forum name in order to display Title / Forum / Topic
		$result = $db->query('SELECT forum_name FROM '.$db->prefix.'forums WHERE id='.$forum_id) or error('Unable to fetch forum info', __FILE__, __LINE__, $db->error());
		$forum = '<a href="viewforum.php?id='.$forum_id.'">'.htmlspecialchars($db->result($result, 0)).'</a>';
	}
	// If a forum_id was specified in the url (new topic).
	else if (isset($_GET['fid']))
	{
		$fid = intval($_GET['fid']);
		if (empty($fid))
			message($lang_common['Bad request']);

		if ($permissions['users_post_topic'] == '0' && $cur_user['status'] < 1 || $permissions['guests_post_topic'] == '0' && $cookie['is_guest'])
			message($lang_common['No permission']);

		$result = $db->query('SELECT forum_name, moderators, admmod_only, closed FROM '.$db->prefix.'forums WHERE id='.$fid) or error('Unable to fetch forum info', __FILE__, __LINE__, $db->error());
		if (!$db->num_rows($result))
			message($lang_common['Bad request']);

		list($forum_name, $moderators, $admmod_only, $forum_closed) = $db->fetch_row($result);
		$mods_array = ($moderators != '') ? unserialize($moderators) : array();

		if ($admmod_only == '1' && $cur_user['status'] < 1 || $forum_closed == '1' && $cur_user['status'] < 2 && !array_key_exists($cur_user['username'], $mods_array))
			message($lang_common['No permission']);

		$action = $lang_post['Post new topic'];
		$form = '<form method="post" action="post.php?action=post&amp;fid='.$fid.'" id="post" onsubmit="return process_form(this)">';

		$forum = htmlspecialchars($forum_name);
	}
	else
		message($lang_common['Bad request']);


	$page_title = htmlspecialchars($options['board_title']).' / '.$action;
	$validate_form = true;
	$form_name = 'post';
	$dimsubmit = true;

	if (!$cookie['is_guest'])
	{
		if (isset($_GET['fid']))
			$focus_element = 'req_subject';
		else
			$focus_element = 'req_message';
	}
	else
		$focus_element = 'req_username';

	require 'header.php';

	$cur_index = 1;

?>
<table class="punplain" cellspacing="1" cellpadding="4">
	<tr>
		<td><b><a href="index.php"><?php print htmlspecialchars($options['board_title']) ?></a> / <?php print $forum ?><?php if (isset($subject)) print ' / '.htmlspecialchars($subject) ?></b></td>
	</tr>
</table>

<?php print $form."\n" ?>
	<input type="hidden" name="form_sent" value="1">
	<input type="hidden" name="form_user" value="<?php print (!$cookie['is_guest']) ? htmlspecialchars($cur_user['username']) : 'Guest'; ?>">
	<table class="punmain" cellspacing="1" cellpadding="4">
		<tr class="punhead">
			<td class="punhead" colspan="2"><?php print $action ?></td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><b><?php print $lang_common['Username'] ?></b>&nbsp;&nbsp;</td>
			<td class="puncon2">&nbsp;<?php print (!$cookie['is_guest']) ? htmlspecialchars($cur_user['username']) : '<input type="text" name="req_username" size="25" maxlength="25" tabindex="'.($cur_index++).'">'; ?></td>
		</tr>
<?php if ($cookie['is_guest']): ?>		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><b><?php print $lang_common['E-mail'] ?></b>&nbsp;&nbsp;</td>
			<td class="puncon2">&nbsp;<input type="text" name="req_email" size="50" maxlength="50" tabindex="<?php print $cur_index++ ?>"></td>
		</tr>
<?php endif; ?><?php if (isset($fid)): ?>		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><b><?php print $lang_post['Subject'] ?></b>&nbsp;&nbsp;</td>
			<td class="puncon2">&nbsp;<input type="text" name="req_subject" size="80" maxlength="70" tabindex="<?php print $cur_index++ ?>"></td>
		</tr>
<?php endif; ?>		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap">
				<b><?php print $lang_common['Message'] ?></b>&nbsp;&nbsp;<br><br>
				HTML: <?php print ($permissions['message_html'] == '1') ? $lang_common['on'] : $lang_common['off']; ?>&nbsp;&nbsp;<br>
				<a href="help.php" target="_blank">BBCode</a>: <?php print ($permissions['message_bbcode'] == '1') ? $lang_common['on'] : $lang_common['off']; ?>&nbsp;&nbsp;<br>
				<a href="help.php" target="_blank">[img] tag</a>: <?php print ($permissions['message_img_tag'] == '1') ? $lang_common['on'] : $lang_common['off']; ?>&nbsp;&nbsp;<br>
				<a href="help.php" target="_blank">Smilies</a>: <?php print ($options['smilies'] == '1') ? $lang_common['on'] : $lang_common['off']; ?>&nbsp;&nbsp;
			</td>
			<td class="puncon2">&nbsp;<textarea name="req_message" rows="20" cols="95" tabindex="<?php print $cur_index++ ?>"><?php if (isset($quote)) { print $quote; } ?></textarea></td>
		</tr>
<?php

	if (!$cookie['is_guest'])
	{
		if ($options['smilies'] == '1')
		{
			if ($cur_user['smilies'] == '1')
				$checkboxes[] = '<input type="checkbox" name="smilies" value="1" tabindex="'.($cur_index++).'" checked>&nbsp;'.$lang_post['Show smilies'];
			else
				$checkboxes[] = '<input type="checkbox" name="smilies" value="1" tabindex="'.($cur_index++).'">&nbsp;'.$lang_post['Show smilies'];
		}

		if ($options['subscriptions'] == '1')
			$checkboxes[] = '<input type="checkbox" name="subscribe" value="1" tabindex="'.($cur_index++).'">&nbsp;'.$lang_post['Subscribe'];

		if (isset($checkboxes))
			$checkboxes = implode('<br>'."\n\t\t\t\t", $checkboxes)."\n";
	}
	else if ($options['smilies'] == '1')
		$checkboxes = '<input type="checkbox" name="smilies" value="1" tabindex="'.($cur_index++).'" checked>&nbsp;'.$lang_post['Show smilies']."\n";

	if (isset($checkboxes))
	{

?>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_common['Options'] ?>&nbsp;&nbsp;</td>
			<td class="puncon2">
				<?php print $checkboxes ?>
			</td>
		</tr>
<?php

	}

?>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_common['Actions'] ?>&nbsp;&nbsp;</td>
			<td class="puncon2"><br>&nbsp;&nbsp;<input type="submit" name="submit" value="<?php print $lang_common['Submit'] ?>" tabindex="<?php print $cur_index++ ?>" accesskey="s">&nbsp;&nbsp;&nbsp;<a href="javascript:history.go(-1)"><?php print $lang_common['Go back'] ?></a><br><br></td>
		</tr>
	</table>
</form>
<?php

	// Check to see if the topic review is to be displayed.
	if (isset($_GET['tid']) && $options['topic_review'] > 0)
	{
		require 'include/parser.php';

		$result = $db->query('SELECT poster, message, smilies, posted FROM '.$db->prefix.'posts WHERE topic_id='.$tid.' ORDER BY posted DESC LIMIT '.$options['topic_review']) or error('Unable to fetch topic review', __FILE__, __LINE__, $db->error());

?>

<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>

<table class="punmain" cellspacing="1" cellpadding="4">
	<tr class="punhead">
		<td class="punhead" colspan="2"><?php print $lang_post['Topic review'] ?></td>
	</tr>
<?php

		while ($cur_post = $db->fetch_assoc($result))
		{
			$cur_post['message'] = parse_message($cur_post['message'], $cur_post['smilies']);

?>
	<tr>
		<td class="puncon1" style="width: 140px; vertical-align: top"><?php print htmlspecialchars($cur_post['poster']) ?></td>
		<td class="puncon2"><?php print $cur_post['message'] ?></td>
	</tr>
<?php

		}
		print "</table>\n";
	}

?>

<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>
<?php

	require 'footer.php';
}
