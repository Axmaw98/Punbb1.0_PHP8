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


if ($cookie['is_guest'] && $permissions['guests_read'] == '0') {
	message($lang_common['Login required'].' <a href="login.php">'.$lang_common['Login'].'</a> '.$lang_common['or'].' <a href="register.php">'.$lang_common['register'].'</a>.');
}

if ($cookie['is_guest']) {
	$disp_posts = $options['disp_posts_default'];
} else {
	$disp_posts = $cur_user['disp_posts'];
}

if (isset($_GET['id'])) {
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
} else {
    $id = 0; // or some other default value
}

if (isset($_GET['pid'])) {
    $pid = filter_var($_GET['pid'], FILTER_VALIDATE_INT);
} else {
    $pid = 0; // or some other default value
}

if ($id < 0 && $pid < 0) {
    message($lang_common['Bad request']);
}


// Load the viewtopic.php language file
require 'lang/'.$language.'/'.$language.'_topic.php';

// If a pid (post ID) is specified we find out the topic ID and page in that topic
// so we can redirect to the correct message
if (isset($_GET['pid']))
{
	$pid = $_GET['pid'];

	$result = $db->query('SELECT topic_id FROM '.$db->prefix.'posts WHERE id='.$pid) or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result))
		message($lang_common['Bad request']);

	$id = $db->result($result, 0);

	// Determine on what page the post is located (depending on $disp_posts)
	$result = $db->query('SELECT id FROM '.$db->prefix.'posts WHERE topic_id='.$id.' ORDER BY posted') or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
	$num_posts = $db->num_rows($result);

	for ($i = 0; $i < $num_posts; $i++)
	{
		$curid = $db->result($result, $i);
		if ($curid == $pid)
			break;
	}
	$i++;	// we started at 0

	$_GET['p'] = ceil($i / $disp_posts);
}


// Fetch some info from the topic
$result = $db->query('SELECT subject, closed, sticky, subscribers, num_replies, forum_id FROM '.$db->prefix.'topics WHERE id='.$id.' AND moved_to IS NULL') or error('Unable to fetch topic info', __FILE__, __LINE__, $db->error());
if (!$db->num_rows($result))
	message($lang_common['Bad request']);

list($subject, $closed, $sticky, $subscribers, $num_replies, $forum_id) = $db->fetch_row($result);


$result = $db->query('SELECT forum_name, moderators, closed, admmod_only FROM '.$db->prefix.'forums WHERE id='.$forum_id) or error('Unable to fetch forum info', __FILE__, __LINE__, $db->error());
list($forum_name, $moderators, $forum_closed, $admmod_only) = $db->fetch_row($result);

$mods_array = array();
if ($moderators != '')
{
	$mods_array = unserialize($moderators);

	while (list($mod_username, $mod_id) = @each($mods_array))
		$temp_array[] = '<a href="profile.php?id='.$mod_id.'">'.htmlspecialchars($mod_username).'</a>';

	$mods_string = implode(', ', $temp_array);
}


if (isset($cur_user['status']) == 2 || (isset($cur_user['status']) == 1 && array_key_exists($cur_user['username'], $mods_array)))
	$is_admmod = true;
else
	$is_admmod = false;

if ($admmod_only == '1' && $cur_user['status'] < 1)
	message($lang_common['Bad request']);

if ($closed != '1' && $forum_closed != '1')
{
	if ($permissions['guests_post'] == '0' && $cookie['is_guest'] || $permissions['users_post'] == '0' && $cur_user['status'] < 1)
		$post_link = '&nbsp;';
	else
		$post_link = '<a href="post.php?tid='.$id.'">'.$lang_topic['Post reply'].'</a>';
}
else
{
	if ($is_admmod)
		$post_link = $lang_topic['Topic closed'].' / <a href="post.php?tid='.$id.'">'.$lang_topic['Post reply'].'</a>';
	else
		$post_link = $lang_topic['Topic closed'];
}


$num_pages = ceil(($num_replies + 1) / $disp_posts);

if (!isset($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $num_pages)
{
	$p = 1;
	$start_from = 0;
}
else
{
	$p = $_GET['p'];
	$start_from = $disp_posts * ($p - 1);
}


$pages = paginate($num_pages, $p, 'viewtopic.php?id='.$id);


if ($options['censoring'] == '1')
	$subject = censor_words($subject);


$page_title = htmlspecialchars($options['board_title']).' / '.$subject;

$validate_form = ($options['quickpost'] == '1') ? true : false;
require 'header.php';

?>
<table class="punplain" cellspacing="1" cellpadding="4">
	<tr>
		<td style="width: 53%"><b><a href="index.php"><?php print htmlspecialchars($options['board_title']) ?></a> / <a href="viewforum.php?id=<?php print $forum_id ?>"><?php print htmlspecialchars($forum_name) ?></a> / <?php print htmlspecialchars($subject) ?></b></td>
		<td class="punright" style="width: 28%"><?php print (!empty($mods_array)) ? $lang_topic['Moderated by'].' '.$mods_string : '&nbsp;' ?></td>
		<td class="punright" style="width: 19%; white-space: nowrap"><b><?php print $post_link ?></b></td>
	</tr>
</table>

<table class="punmain" cellspacing="1" cellpadding="4">
	<tr class="punhead">
		<td class="punhead" style="width: 185px; white-space: nowrap"><?php print $lang_common['Author'] ?></td>
		<td style="white-space: nowrap">
			<table class="punplain" cellspacing="0" cellpadding="0">
				<tr>
					<td class="punhead" style="width: 20%"><?php print $lang_common['Message'] ?></td>
					<td><?php print $lang_common['Pages'].': '.$pages ?></td>
				</tr>
			</table>
		</td>
	</tr>
</table>

<?php


// Build an array of user_id's online
$result = $db->query('SELECT user_id FROM '.$db->prefix.'online WHERE user_id>0') or error('Unable to fetch online list', __FILE__, __LINE__, $db->error());
$num_online = $db->num_rows($result);

for ($i = 0; $i < $num_online; $i++)
	$online_list[] = $db->result($result, $i);


require 'include/parser.php';

// Used for switching background color in posts
$bg_switch = true;


// Retrieve the topic posts (and their respective poster)
$result = $db->query('SELECT u.email, u.title, u.url, u.location, u.use_avatar, u.signature, u.hide_email, u.num_posts, u.status, u.registered, u.admin_note, p.id, p.poster, p.poster_id, p.poster_ip, p.poster_email, p.message, p.smilies, p.posted, p.edited, p.edited_by FROM '.$db->prefix.'posts AS p INNER JOIN '.$db->prefix.'users AS u ON u.id=p.poster_id WHERE p.topic_id='.$id.' ORDER BY p.posted LIMIT '.$start_from.','.$disp_posts) or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());

while ($cur_post = $db->fetch_assoc($result))
{
	// If the poster is a registered user.
	if ($cur_post['poster_id'] > 1)
	{
		$registered = date($options['date_format'], $cur_post['registered']);

		if (isset($online_list) && in_array($cur_post['poster_id'], $online_list))
			$info = '<span class="punheadline"><a href="profile.php?id='.$cur_post['poster_id'].'"><u>'.htmlspecialchars($cur_post['poster']).'</u></a></span>';
		else
			$info = '<span class="punheadline"><a href="profile.php?id='.$cur_post['poster_id'].'">'.htmlspecialchars($cur_post['poster']).'</a></span>';

		// getTitle() requires that an element 'username' be present in the array
		$cur_post['username'] = $cur_post['poster'];
		$user_title = get_title($cur_post);

		if ($options['censoring'] == '1')
			$user_title = censor_words($user_title);

		$info .= '<br>'."\n\t\t\t\t\t\t".$user_title.'<br>';

		if ($options['avatars'] == '1' && $cur_post['use_avatar'] == '1')
		{
			if ($img_size = @getimagesize($options['avatars_dir'].'/'.$cur_post['poster_id'].'.gif'))
				$info .= "\n\t\t\t\t\t\t".'<img class="punavatar" src="'.$options['avatars_dir'].'/'.$cur_post['poster_id'].'.gif" '.$img_size[3].' alt=""><br>';
			else if ($img_size = @getimagesize($options['avatars_dir'].'/'.$cur_post['poster_id'].'.jpg'))
				$info .= "\n\t\t\t\t\t\t".'<img class="punavatar" src="'.$options['avatars_dir'].'/'.$cur_post['poster_id'].'.jpg" '.$img_size[3].' alt=""><br>';
			else if ($img_size = @getimagesize($options['avatars_dir'].'/'.$cur_post['poster_id'].'.png'))
				$info .= "\n\t\t\t\t\t\t".'<img class="punavatar" src="'.$options['avatars_dir'].'/'.$cur_post['poster_id'].'.png" '.$img_size[3].' alt=""><br>';
			else
				$info .= '<br>'."\n\t\t\t\t\t\t";
		}
		else
			$info .= '<br>'."\n\t\t\t\t\t\t";

		if ($cur_post['location'] != '')
		{
			if ($options['censoring'] == '1')
				$cur_post['location'] = censor_words($cur_post['location']);

			$info .= $lang_topic['From'].': '.htmlspecialchars($cur_post['location']).'<br>'."\n\t\t\t\t\t\t";
		}

		$info .= $lang_common['Registered'].': '.$registered.'<br>';

		if ($options['show_post_count'] == '1')
			$info .= "\n\t\t\t\t\t\t".$lang_common['Posts'].': '.$cur_post['num_posts'];

		if (isset($cur_user['status']) > 0)
		{
			$info .= '<br>'."\n\t\t\t\t\t\t".'IP: <a href="moderate.php?get_host='.$cur_post['id'].'">'.$cur_post['poster_ip'].'</a>';

			if ($cur_post['admin_note'] != '')
				$info .= '<br><br>'."\n\t\t\t\t\t\t".$lang_topic['Note'].': <b>'.$cur_post['admin_note'].'</b>';
		}

		// Generate the string for the links that appear at the bottom of every message.
		$links = array();

		if ($cur_post['hide_email'] == '0')
			$links[] = '<a href="mailto:'.$cur_post['email'].'">'.$lang_common['E-mail'].'</a>';

		if ($cur_post['url'] != '')
		{
			if ($cur_user['link_to_new_win'] == '0')
				$links[] = '<a href="'.htmlspecialchars($cur_post['url']).'">'.$lang_topic['Website'].'</a>';
			else
				$links[] = '<a href="'.htmlspecialchars($cur_post['url']).'" target="_blank">'.$lang_topic['Website'].'</a>';
		}
	}
	// If the poster is a guest (or a user that has been deleted)
	else
	{
		$info = '<span class="punheadline">'.htmlspecialchars($cur_post['poster']).'</span><br>'."\n\t\t\t\t\t\t".$lang_topic['Guest'];

		if (isset($cur_user['status']) > 0)
			$info .= '<br><br>'."\n\t\t\t\t\t\t".'IP: <a href="moderate.php?get_host='.$cur_post['id'].'">'.$cur_post['poster_ip'].'</a><br><br>';
		else
			$info .= '<br><br><br><br>';

		if ($cur_post['poster_email'] != '')
			$links = array('<a href="mailto:'.$cur_post['poster_email'].'">'.$lang_common['E-mail'].'</a>');
		else
			$links = array();
	}


	if ($cur_post['edited'])
		$edited = $lang_topic['Last edit'].' '.htmlspecialchars($cur_post['edited_by']).' ('.format_time($cur_post['edited']).')';
	else
		$edited = '&nbsp;';


	$actions = array();

	if (!$is_admmod)
	{
		if (!$cookie['is_guest'])
		{
			$actions[] = '<a class="punclosed" href="misc.php?report='.$cur_post['id'].'">'.$lang_topic['Report'].'</a>';

			if ($closed != '1' && $forum_closed != '1')
			{
				if ($permissions['users_edit_post'] == '1' && $cur_post['poster_id'] == $cur_user['id'])
				{
					if ($permissions['users_del_post'] == '1')
						$actions[] = '<a href="delete.php?id='.$cur_post['id'].'">'.$lang_topic['Delete'].'</a>';

					$actions[] = '<a href="edit.php?id='.$cur_post['id'].'">'.$lang_topic['Edit'].'</a>';
				}

				$actions[] = '<a href="post.php?tid='.$id.'&amp;qid='.$cur_post['id'].'">'.$lang_topic['Quote'].'</a>';
			}
		}
		else
		{
			if ($permissions['guests_post'] == '1' && $closed != '1' && $forum_closed != '1')
				$actions[] = '<a href="post.php?tid='.$id.'&amp;qid='.$cur_post['id'].'">'.$lang_topic['Quote'].'</a>';
		}
	}
	else
		$actions[] = '<a class="punclosed" href="misc.php?report='.$cur_post['id'].'">'.$lang_topic['Report'].'</a> | <a href="delete.php?id='.$cur_post['id'].'">'.$lang_topic['Delete'].'</a> | <a href="edit.php?id='.$cur_post['id'].'">'.$lang_topic['Edit'].'</a> | <a href="post.php?tid='.$id.'&amp;qid='.$cur_post['id'].'">'.$lang_topic['Quote'].'</a>';


	// Switch the background color for every message.
	$bg_switch = ($bg_switch) ? $bg_switch = false : $bg_switch = true;

	// Perform the main parsing of the message (BBCode, smilies, censor words etc)
	$cur_post['message'] = parse_message($cur_post['message'], $cur_post['smilies']);


	if ($cur_post['signature'] != '' && $cur_user['show_sig'] != '0')
		$signature = '<br><br>_______________________________________<br>'.parse_signature($cur_post['signature']).'<br><br>';
	else
		$signature = NULL;

?>
<div><a name="<?php print $cur_post['id'] ?>"></a></div>

<table class="punmain" cellspacing="1" cellpadding="4">
	<tr class="<?php print ($bg_switch) ? 'puncon1' : 'puncon2'; ?>">
		<td class="puntop" style="width: 185px">
			<table class="punplain" cellspacing="0" cellpadding="0">
				<tr>
					<td>
						<div style="width: 185px">
							<?php print $info."\n" ?>
						</div>
					</td>
				</tr>
			</table>
		</td>
		<td class="puntop">
			<table class="punplain" cellspacing="0" cellpadding="0">
				<tr>
					<td>
						<span class="puntext"><?php print $cur_post['message'] ?></span><?php print ($signature != NULL) ? '<span class="punsignature">'.$signature.'</span>'."\n" : '<br><br>'."\n"; ?>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr class="<?php print ($bg_switch) ? 'puncon1' : 'puncon2'; ?>">
		<td style="width: 185px; white-space: nowrap"><?php print format_time($cur_post['posted']) ?></td>
		<td>
			<table class="punplain" cellspacing="0" cellpadding="0">
				<tr>
					<td style="width: 47%"><?php print $edited ?></td>
					<td style="width: 20%"><?php print (count($links) > 0) ? implode(' | ', $links) : '&nbsp;'; ?></td>
					<td class="punright" style="width: 33%"><?php print (count($actions) > 0) ? implode(' | ', $actions) : '&nbsp;'; ?></td>
				</tr>
			</table>
		</td>
	</tr>
</table>

<?php

}


if (!$cookie['is_guest'] && $options['subscriptions'] == '1')
{
	if (strstr($subscribers, $cur_user['email']))
		// I apologize for the choice of variable name here. It's a mix of subscription and action I guess :-)
		$subscraction = $lang_topic['Is subscribed'].' - <a href="misc.php?unsubscribe='.$id.'">'.$lang_topic['Unsubscribe'].'</a>';
	else
		$subscraction = '<a href="misc.php?subscribe='.$id.'">'.$lang_topic['Subscribe'].'</a>';
}
else
	$subscraction = '&nbsp;';


?>
<table class="punplain" cellspacing="1" cellpadding="4">
	<tr>
		<td style="width: 46%"><?php print $lang_common['Pages'].': '.$pages ?></td>
		<td class="punright" style="width: 35%"><?php print $subscraction ?></td>
		<td class="punright" style="width: 19%"><b><?php print $post_link ?></b></td>
	</tr>
</table>
<?php

// Display quick post if enabled
if (!$cookie['is_guest'] && $options['quickpost'] == '1' && $permissions['users_post'] == '1')
{
	if (($closed == '0' && $forum_closed == '0') || $is_admmod)
	{

?>

<form method="post" action="post.php?tid=<?php print $id ?>" onsubmit="return process_form(this)">
	<input type="hidden" name="form_sent" value="1">
	<input type="hidden" name="form_user" value="<?php print (!$cookie['is_guest']) ? htmlspecialchars($cur_user['username']) : 'Guest'; ?>">
	<input type="hidden" name="smilies" value="<?php print $cur_user['smilies'] ?>">
	<input type="hidden" name="subscribe" value="0">
	<table class="punmain" cellspacing="1" cellpadding="4">
		<tr class="punhead">
			<td class="punhead" colspan="2"><?php print $lang_topic['Quick post'] ?></td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap">
				<b><?php print $lang_common['Message'] ?></b>&nbsp;&nbsp;<br><br>
				HTML: <?php print ($permissions['message_html'] == '1') ? $lang_common['on'] : $lang_common['off']; ?>&nbsp;&nbsp;<br>
				<a href="help.php" target="_blank">BBCode</a>: <?php print ($permissions['message_bbcode'] == '1') ? $lang_common['on'] : $lang_common['off']; ?>&nbsp;&nbsp;<br>
				<a href="help.php" target="_blank">[img] tag</a>: <?php print ($permissions['message_img_tag'] == '1') ? $lang_common['on'] : $lang_common['off']; ?>&nbsp;&nbsp;<br>
				<a href="help.php" target="_blank">Smilies</a>: <?php print ($options['smilies'] == '1') ? $lang_common['on'] : $lang_common['off']; ?>&nbsp;&nbsp;
			</td>
			<td class="puncon2">&nbsp;<textarea name="req_message" rows="7" cols="80"></textarea></td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_common['Actions'] ?>&nbsp;&nbsp;</td>
			<td class="puncon2"><br>&nbsp;&nbsp;<input type="submit" name="submit" value="<?php print $lang_common['Submit'] ?>" accesskey="s"><br><br></td>
		</tr>
	</table>
</form>

<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>
<?php

	}
}

// Increment "num_views" for topic
$db->query('UPDATE '.$db->prefix.'topics SET num_views=num_views+1 WHERE id='.$id) or error('Unable to update topic', __FILE__, __LINE__, $db->error());

$footer_style = 'topic';
require 'footer.php';
