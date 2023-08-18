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


@include 'config.php';

// If config.php doesn't exist, PUN shouldn't be defined
if (!defined('PUN'))
	exit('config.php doesn\'t exist or is corrupt. Please run install.php to install PunBB first.');

require 'include/common.php';


if ($cookie['is_guest'] && $permissions['guests_read'] == '0')
	message($lang_common['Login required'].' <a href="login.php">'.$lang_common['Login'].'</a> '.$lang_common['or'].' <a href="register.php">'.$lang_common['register'].'</a>.');


// Load the index.php language file
require 'lang/'.$language.'/'.$language.'_index.php';

$page_title = htmlspecialchars($options['board_title']);
require 'header.php';

?>
<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>

<table class="punmain" cellspacing="1" cellpadding="4">
	<tr class="punhead">
		<td class="punhead" style="width: 24px">&nbsp;</td>
		<td class="punhead" style="white-space: nowrap"><?php print $lang_common['Forum'] ?></td>
		<td class="punheadcent" style="width: 6%; white-space: nowrap"><?php print $lang_index['Topics'] ?></td>
		<td class="punheadcent" style="width: 6%; white-space: nowrap"><?php print $lang_common['Posts'] ?></td>
		<td class="punheadcent" style="width: 18%; white-space: nowrap"><?php print $lang_common['Last post'] ?></td>
		<td class="punheadcent" style="width: 18%; white-space: nowrap"><?php print $lang_index['Moderators'] ?></td>
	</tr>
<?php


// Print the categories and forums
$cur_category = null; // Define $cur_category before using it in the if statement
$extra = '';
if (isset($cur_user['status']) < 1)
	$extra = ' WHERE c.admmod_only!=\'1\' AND f.admmod_only!=\'1\'';

$result = $db->query('SELECT c.id AS cid, c.cat_name, f.id AS fid, f.forum_name, f.forum_desc, f.moderators, f.num_topics, f.num_posts, f.last_post, f.last_post_id, f.last_poster, f.closed FROM '.$db->prefix.'categories AS c INNER JOIN '.$db->prefix.'forums AS f ON c.id=f.cat_id'.$extra.' ORDER BY c.position, cid, f.position') or error('Unable to fetch category/forum list', __FILE__, __LINE__, $db->error());

while ($cur_forum = $db->fetch_assoc($result))
{
	if ($cur_forum['cid'] != $cur_category)	// A new category since last iteration?
	{
		// ...
		$cur_category = $cur_forum['cid'];

	// ...


?>
	<tr>
		<td class="puncon3" colspan="6"><?php print htmlspecialchars($cur_forum['cat_name']) ?></td>
	</tr>
<?php

		$cur_category = $cur_forum['cid'];
	}

	if ($cur_forum['closed'] != '1')
		$forum_field = '<span class="punheadline"><a href="viewforum.php?id='.$cur_forum['fid'].'">'.htmlspecialchars($cur_forum['forum_name']).'</a></span>';
	else
		$forum_field = '<span class="punheadline"><a class="punclosed" href="viewforum.php?id='.$cur_forum['fid'].'">'.htmlspecialchars($cur_forum['forum_name']).'</a></span>';

	if ($cur_forum['forum_desc'] != '')
		$forum_field .= '<br>'."\n\t\t\t".$cur_forum['forum_desc'];

	// If there is a last_post/last_poster.
	if ($cur_forum['last_post'] != '')
		$last_post = '<a href="viewtopic.php?pid='.$cur_forum['last_post_id'].'#'.$cur_forum['last_post_id'].'">'.format_time($cur_forum['last_post']).'</a><br>'.$lang_common['by'].' '.htmlspecialchars($cur_forum['last_poster']);
	else
		$last_post = '&nbsp;';

	if (!$cookie['is_guest'] && $cur_forum['last_post'] > $cookie['last_timeout'])
	{
		if ($cur_user['show_img'] != '0')
			$icon = '<img src="img/'.$cur_user['style'].'_new.png" width="16" height="16" alt="">';
		else
			$icon = '<span class="puntext"><b>&#8226;</b></span>';
	}
	else
		$icon = '&nbsp;';

	if ($cur_forum['moderators'] != '')
	{
		$mods_array = unserialize($cur_forum['moderators']);
		$moderators = array();

		while (list($mod_username, $mod_id) = @each($mods_array))
		{
			$mod_username = htmlspecialchars($mod_username);
			$moderators[] = '<a href="profile.php?id='.$mod_id.'">'.$mod_username.'</a>';
		}

		$moderators = implode(', ', $moderators);
	}
	else
		$moderators = '&nbsp;';

?>
	<tr class="puncon1">
		<td class="puncent"><?php print $icon ?></td>
		<td>
			<?php print $forum_field."\n" ?>
		</td>
		<td class="puncent"><?php print $cur_forum['num_topics'] ?></td>
		<td class="puncent"><?php print $cur_forum['num_posts'] ?></td>
		<td class="puncent"><?php print $last_post ?></td>
		<td class="puncent"><?php print $moderators ?></td>
	</tr>
<?php

}

print "</table>\n\n";


// Show what the current user can and cannot do
if (isset($cur_user) && isset($cur_user['status']) && $cur_user['status'] > 0) {
    $perms = ($cur_user['status'] > 0)
        ? "{$lang_index['You']} <b>{$lang_index['can']}</b> {$lang_index['post replies']}<br>
             {$lang_index['You']} <b>{$lang_index['can']}</b> {$lang_index['post topics']}<br>
             {$lang_index['You']} <b>{$lang_index['can']}</b> {$lang_index['edit posts']}<br>
             {$lang_index['You']} <b>{$lang_index['can']}</b> {$lang_index['delete posts']}<br>
             {$lang_index['You']} <b>{$lang_index['can']}</b> {$lang_index['delete topics']}\n"
        : '';
} else if (!$cookie['is_guest']) {
    $perms = $lang_index['You'].' <b>'. (($permissions['users_post'] == '1') ? $lang_index['can'] : $lang_index['cannot']) .'</b> '.$lang_index['post replies'].'<br>';
    $perms .= "\n\t\t\t\t\t\t".$lang_index['You'].' <b>'. (($permissions['users_post_topic'] == '1') ? $lang_index['can'] : $lang_index['cannot']) .'</b> '.$lang_index['post topics'].'<br>';
    $perms .= "\n\t\t\t\t\t\t".$lang_index['You'].' <b>'. (($permissions['users_edit_post'] == '1') ? $lang_index['can'] : $lang_index['cannot']) .'</b> '.$lang_index['edit posts'].'<br>';
    $perms .= "\n\t\t\t\t\t\t".$lang_index['You'].' <b>'. (($permissions['users_del_post'] == '1') ? $lang_index['can'] : $lang_index['cannot']) .'</b> '.$lang_index['delete posts'].'<br>';
    $perms .= "\n\t\t\t\t\t\t".$lang_index['You'].' <b>'. (($permissions['users_del_topic'] == '1') ? $lang_index['can'] : $lang_index['cannot']) .'</b> '.$lang_index['delete topics'].'<br>'."\n";
} else {
    $perms = $lang_index['You'].' <b>'. (($permissions['guests_post'] == '1') ? $lang_index['can'] : $lang_index['cannot']) .'</b> '.$lang_index['post replies'].'<br>';
    $perms .= "\n\t\t\t\t\t\t".$lang_index['You'].' <b>'. (($permissions['guests_post_topic'] == '1') ? $lang_index['can'] : $lang_index['cannot']) .'</b> '.$lang_index['post topics'].'<br>';
    $perms .= "\n\t\t\t\t\t\t".$lang_index['You'].' <b>'.$lang_index['cannot'].'</b> '.$lang_index['edit posts'].'<br>'.$lang_index['You'].' <b>'.$lang_index['cannot'].'</b> '.$lang_index['delete posts'].'<br>'.$lang_index['You'].' <b>'.$lang_index['cannot'].'</b> '.$lang_index['delete topics']."\n";
}


// Collect some statistics from the database
$result = $db->query('SELECT COUNT(id) FROM '.$db->prefix.'users') or error('Unable to fetch total user count', __FILE__, __LINE__, $db->error());
$stats['totalusers'] = $db->result($result, 0) - 1;	// Minus the guest account

$result = $db->query('SELECT id, username FROM '.$db->prefix.'users ORDER BY registered DESC LIMIT 1') or error('Unable to fetch newest registered user', __FILE__, __LINE__, $db->error());
$stats['lastuser'] = $db->fetch_assoc($result);

$result = $db->query('SELECT SUM(num_topics), SUM(num_posts) FROM '.$db->prefix.'forums') or error('Unable to fetch topic/post count', __FILE__, __LINE__, $db->error());
list($stats['totaltopics'], $stats['totalposts']) = $db->fetch_row($result);

?>
<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>

<table class="punmain" cellspacing="1" cellpadding="4">
	<tr>
		<td class="puncon1">
			<table class="punplain" cellspacing="0" cellpadding="0">
				<tr>
					<td class="puntop" style="margin-right: 40px">
						<?php print $lang_index['This forum has'].' '.$stats['totalusers'].' '.(($stats['totalusers'] <> 1) ? $lang_index['registered users'] : $lang_index['registered users']).', '.$stats['totaltopics'].' '.(($stats['totaltopics'] <> 1) ? $lang_index['topics'] : $lang_index['topic']).' '.$lang_index['and'].' '.$stats['totalposts'].' '.(($stats['totalposts'] <> 1) ? $lang_index['posts'] : $lang_index['post']) ?>.<br>
						<?php print $lang_index['Newest user'] ?> <a href="profile.php?id=<?php print $stats['lastuser']['id'] ?>"><?php print htmlspecialchars($stats['lastuser']['username']) ?></a>.
<?php

if ($options['users_online'] == '1') {
    // Fetch users online info and generate strings for output.
    $num_guests = 0;
    $users = array();

    $result = $db->query('SELECT user_id, ident, logged FROM '.$db->prefix.'online ORDER BY ident');
    if (!$result) {
        throw new Exception('Unable to fetch online list: ' . $db->error());
    }

    while ($cur_user_online = $result->fetch_array(MYSQLI_ASSOC)) {
        if ($cur_user_online['user_id'] > 0) {
            $users[] = '<a href="profile.php?id='.$cur_user_online['user_id'].'">'.htmlspecialchars($cur_user_online['ident']).'</a>';
        } else {
            $num_guests++;
        }
    }

    $num_users = count($users);

    echo "\t\t\t\t\t\t".'<br>'.$lang_index['Currently serving'].' '.$num_users.' '.(($num_users != 1) ? $lang_index['registered users'] : $lang_index['registered user']).' '.$lang_index['and'].' '.$num_guests.' '.(($num_guests != 1) ? $lang_index['guests'] : $lang_index['guest']).'.';

    if ($num_users) {
        echo '<br><br>'."\n\t\t\t\t\t\t".implode(', ', $users)."\n";
    } else {
        echo "\n";
    }
}


?>
					</td>
					<td class="puntopright" style="white-space: nowrap">
						<?php print $perms ?>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>

<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>
<?php

$footer_style = 'index';
require 'footer.php';
