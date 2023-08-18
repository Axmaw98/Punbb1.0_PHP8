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


if (!$cookie['is_guest'])
{
	$disp_topics = $cur_user['disp_topics'];
	$disp_posts = $cur_user['disp_posts'];
}
else
{
	$disp_topics = $options['disp_topics_default'];
	$disp_posts = $options['disp_posts_default'];
}

$id = intval($_GET['id']);
if (empty($id) || $id < 0)
	message($lang_common['Bad request']);

// Load the viewforum.php language file
require 'lang/'.$language.'/'.$language.'_forum.php';

// Fetch some info from the forum
$result = $db->query('SELECT forum_name, moderators, num_topics, closed, admmod_only FROM '.$db->prefix.'forums WHERE id='.$id) or error('Unable to fetch forum info', __FILE__, __LINE__, $db->error());
if (!$db->num_rows($result))
	message($lang_common['Bad request'], true);

list($forum_name, $moderators, $num_topics, $closed, $admmod_only) = $db->fetch_row($result);

if ($admmod_only == '1' && $cur_user['status'] < 1)
	message($lang_common['Bad request']);

$mods_array = array();
if ($moderators != '')
{
	$mods_array = unserialize($moderators);

	while (list($mod_username, $mod_id) = @each($mods_array))
		$temp_array[] = '<a href="profile.php?id='.$mod_id.'">'.htmlspecialchars($mod_username).'</a>';

	$mods_string = implode(', ', $temp_array);
}

if ($closed != '1')
{
	if ($permissions['guests_post_topic'] == '0' && $cookie['is_guest'] || $permissions['users_post_topic'] == '0' && $cur_user['status'] < 1)
		$post_link = '&nbsp;';
	else
		$post_link = '<a href="post.php?fid='.$id.'">'.$lang_forum['Post topic'].'</a>';
}
else
{
	if ($cur_user['status'] > 1 || $cur_user['status'] == 1 && array_key_exists($cur_user['username'], $mods_array))
		$post_link = $lang_forum['Forum closed'].' / <a href="post.php?fid='.$id.'">'.$lang_forum['Post topic'].'</a>';
	else
		$post_link = $lang_forum['Forum closed'];
}


$page_title = htmlspecialchars($options['board_title']).' / '.htmlspecialchars($forum_name);
require 'header.php';

?>
<table class="punplain" cellspacing="1" cellpadding="4">
	<tr>
		<td style="width: 53%"><b><a href="index.php"><?php print htmlspecialchars($options['board_title']) ?></a> / <?php print htmlspecialchars($forum_name) ?></b></td>
		<td class="punright" style="width: 28%"><?php print (!empty($mods_array)) ? $lang_forum['Moderated by'].' '.$mods_string : '&nbsp;' ?></td>
		<td class="punright" style="width: 19%; white-space: nowrap"><b><?php print $post_link ?></b></td>
	</tr>
</table>

<table class="punmain" cellspacing="1" cellpadding="4">
	<tr class="punhead">
		<td class="punhead" style="width: 24px">&nbsp;</td>
		<td class="punhead" style="white-space: nowrap"><?php print $lang_common['Topic'] ?></td>
		<td class="punhead" style="width: 14%; white-space: nowrap"><?php print $lang_common['Author'] ?></td>
		<td class="punheadcent" style="width: 7%; white-space: nowrap"><?php print $lang_common['Replies'] ?></td>
		<td class="punheadcent" style="width: 7%; white-space: nowrap"><?php print $lang_forum['Views'] ?></td>
		<td class="punhead" style="width: 25%; white-space: nowrap"><?php print $lang_common['Last post'] ?></td>
	</tr>
<?php


// The number of pages required to display all topics (depending on $disp_topics setting)
$num_pages = ceil($num_topics / $disp_topics);

if (!isset($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $num_pages)
{
	$p = 1;
	$start_from = 0;
}
else
{
	$p = $_GET['p'];
	$start_from = $disp_topics * ($p - 1);
}


// Fetch topics (with or without "the dot")
if ($cookie['is_guest'] || $options['show_dot'] == '0')
{
	// Without "the dot"
	$result = $db->query('SELECT id, poster, subject, posted, last_post, last_post_id, last_poster, num_views, num_replies, closed, sticky, moved_to FROM '.$db->prefix.'topics WHERE forum_id='.$id.' ORDER BY sticky DESC, last_post DESC LIMIT '.$start_from.', '.$disp_topics) or error('Unable to fetch topic list for forum', __FILE__, __LINE__, $db->error());
}
else
{
	// Fetch topic ID's
	$result = $db->query('SELECT id FROM '.$db->prefix.'topics WHERE forum_id='.$id.' ORDER BY sticky DESC, last_post DESC LIMIT '.$start_from.', '.$disp_topics) or error('Unable to fetch topic list for forum', __FILE__, __LINE__, $db->error());

	$threadids = '0';
	while ($row = $db->fetch_row($result))
		$threadids .= ','.$row[0];

	// Fetch topics
	$result = $db->query('SELECT DISTINCT p.poster_id AS has_posted, t.id, t.poster, t.subject, t.posted, t.last_post, t.last_post_id, t.last_poster, t.num_views, t.num_replies, t.closed, t.sticky, t.moved_to FROM '.$db->prefix.'topics AS t LEFT JOIN '.$db->prefix.'posts AS p ON t.id=p.topic_id AND p.poster_id='.$cur_user['id'].' WHERE t.id IN('.$threadids.') ORDER BY sticky DESC, last_post DESC') or error('Unable to fetch topic list for forum', __FILE__, __LINE__, $db->error());
}


// If there are topics in this forum.
if ($db->num_rows($result))
{
	while ($cur_topic = $db->fetch_assoc($result))
	{
		if ($cur_topic['moved_to'] == null)
			$last_post = '<a href="viewtopic.php?pid='.$cur_topic['last_post_id'].'#'.$cur_topic['last_post_id'].'">'.format_time($cur_topic['last_post']).'</a> '.$lang_common['by'].' '.htmlspecialchars($cur_topic['last_poster']);
		else
			$last_post = '&nbsp;';

		if ($options['censoring'] == '1')
			$cur_topic['subject'] = censor_words($cur_topic['subject']);

		if ($cur_topic['moved_to'] != 0)
			$subject = $lang_forum['Moved'].': <a href="viewtopic.php?id='.$cur_topic['moved_to'].'">'.htmlspecialchars($cur_topic['subject']).'</a>';
		else if ($cur_topic['closed'] != '1' && $closed != '1')
			$subject = '<a href="viewtopic.php?id='.$cur_topic['id'].'">'.htmlspecialchars($cur_topic['subject']).'</a>';
		else
			$subject = '<a class="punclosed" href="viewtopic.php?id='.$cur_topic['id'].'">'.htmlspecialchars($cur_topic['subject']).'</a>';

		if (!$cookie['is_guest'] && $cur_topic['last_post'] > $cookie['last_timeout'] && $cur_topic['moved_to'] == null)
		{
			if ($cur_user['show_img'] != '0')
				$icon = '<img src="img/'.$cur_user['style'].'_new.png" width="16" height="16" alt="">';
			else
				$icon = '<span class="puntext"><b>&#8226;</b></span>';

			$subject = '<b>'.$subject.'</b>';
		}
		else
			$icon = '&nbsp;';

		// Should we display the dot or not? :)
		if (!$cookie['is_guest'] && $options['show_dot'] == '1')
		{
			if ($cur_topic['has_posted'] == $cur_user['id'])
				$subject = '<b>&middot;</b>&nbsp;'.$subject;
			else
				$subject = '&nbsp;&nbsp;'.$subject;
		}

		if ($cur_topic['sticky'] == '1')
			$subject = $lang_forum['Sticky'].': '.$subject;

		$num_pages_topic = ceil(($cur_topic['num_replies'] + 1) / $disp_posts);

		if ($num_pages_topic > 1)
		{
			$stop = ($num_pages_topic < 3) ? ($num_pages_topic + 1) : 4;

			$subject .= '&nbsp;&nbsp;[';
			for ($current=1; $current < $stop; $current++)
				$subject .= '&nbsp;<a href="viewtopic.php?id='.$cur_topic['id'].'&amp;p='.$current.'">'.$current.'</a>';

			if ($num_pages_topic > 3)
				$subject .= '&nbsp;-&nbsp;<a href="viewtopic.php?id='.$cur_topic['id'].'&amp;p='.$num_pages_topic.'">'.$lang_common['Last page'].'</a>&nbsp;]';
			else
				$subject .= '&nbsp;]';
		}

?>
	<tr class="puntopic">
		<td class="puncon1cent"><?php print $icon ?></td>
		<td class="puncon2"><?php print $subject ?></td>
		<td class="puncon1"><?php print htmlspecialchars($cur_topic['poster']) ?></td>
		<td class="puncon2cent"><?php print ($cur_topic['moved_to'] == null) ? $cur_topic['num_replies'] : '&nbsp;' ?></td>
		<td class="puncon1cent"><?php print ($cur_topic['moved_to'] == null) ? $cur_topic['num_views'] : '&nbsp;' ?></td>
		<td class="puncon2" style="white-space: nowrap"><?php print $last_post ?></td>
	</tr>
<?php

	}
}
else
	print "\t".'<tr><td class="puncon1" colspan="6">'.$lang_forum['Empty forum'].'</td></tr>'."\n";

?>
</table>

<table class="punplain" cellspacing="1" cellpadding="4">
	<tr>
		<td><?php print $lang_common['Pages'].': '.paginate($num_pages, $p, 'viewforum.php?id='.$id) ?></td>
		<td class="punright"><b><?php print $post_link ?></b></td>
	</tr>
</table>
<?php

$forum_id = $id;
$footer_style = 'forum';
require 'footer.php';
