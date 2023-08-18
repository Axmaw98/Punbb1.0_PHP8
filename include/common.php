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


// Enable debugging by removing // from the following line
define('PUN_DEBUG', 1);

// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;

// Record the start time (will be used to calculate the generation time for the page)
$pun_start = get_microtime();


// Make sure no one sends user information though GPC (only if register_globals is on)
unset($cur_user, $cookie);

// Disable error reporting for uninitialized variables
error_reporting(E_ERROR | E_WARNING | E_PARSE);

// Turn off magic_quotes_runtime
ini_set('magic_quotes_gpc', 'Off');
ini_set('magic_quotes_runtime', 'Off');
ini_set('magic_quotes_sybase', 'Off');

// Load the common language file
require 'lang/'.$language.'/'.$language.'_common.php';

// Load DB abstraction layer and try to connect
require 'include/dblayer/commondb.php';


// Get the forum options and permissions
$result = $db->query('SELECT * FROM '.$db->prefix.'options, '.$db->prefix.'permissions') or error('Unable to fetch forum options and permissions', __FILE__, __LINE__, $db->error());
$optperm = $db->fetch_assoc($result);

// The first 48 elements should be options and the rest permissions
list($options, $permissions) = array_chunk($optperm, 48, true);


// Enable output buffering
if (!defined('PUN_DISABLE_BUFFERING'))
{
	// Should we use gzip output compression?
	if ($options['gzip'] == '1' && extension_loaded('zlib') && (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false || strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'deflate') !== false))
		ob_start('ob_gzhandler');
	else
		ob_start();
}


// Check/update/set cookie and fetch user info
$cookie = check_cookie($cur_user);

// Check if we are to display a maintenance message
if ($options['maintenance'] == '1' && $cur_user['status'] < 2 && !defined('PUN_TURN_OFF_MAINT'))
	maintenance_message();


// Check if current user is banned
check_bans();

// Update online list
update_users_online();


//
// Cookie stuff!
//
function check_cookie(&$cur_user)
{
	global $db, $cookie_path, $cookie_domain, $cookie_secure, $options;

	$now = time();
	$expire = $now + 31536000;	// The cookie expires after a year

	if (isset($_COOKIE['punbb_cookie']))
	{
		$cookie = array();
		$punbb_cookie = $_COOKIE['punbb_cookie'] ?? '';
		if ($punbb_cookie) {
		    $cookie_values = unserialize(urldecode($punbb_cookie));
		    if (is_array($cookie_values)) {
		        $cookie['username'] = $cookie_values[0] ?? '';
		        $cookie['password'] = $cookie_values[1] ?? '';
		        $cookie['last_action'] = $cookie_values[2] ?? '';
		        $cookie['last_timeout'] = $cookie_values[3] ?? '';
		    }
		}


	    if (strcasecmp($cookie['username'], 'Guest'))
	    {
	        $result = $db->query('SELECT * FROM '.$db->prefix.'users WHERE username=\''.addslashes($cookie['username']).'\'') or error('Unable to fetch user information', __FILE__, __LINE__, $db->error());
	        $cur_user = $db->fetch_assoc($result);

					if (!is_null($cur_user) && isset($cur_user['disp_topics']) && $cur_user['disp_topics'] == '') {
					    $cur_user['disp_topics'] = $options['disp_topics_default'];
					}

	        if (!isset($cur_user['disp_posts']) || $cur_user['disp_posts'] == '')
	            $cur_user['disp_posts'] = $options['disp_posts_default'];

	        // Determine what style to use
	        if (!@file_exists('style/'.$cur_user['style'].'.css'))
	            $cur_user['style'] = $options['default_style'];

	        // If the user couldn't be found or if the password was incorrect
					if (!$cur_user || !isset($cookie['password']) || !isset($cur_user['password']) || md5($cookie['password']) !== md5($cur_user['password']))
{
    setcookie('punbb_cookie', serialize(array('Guest', 'Guest', $now, $now)), $expire, $cookie_path, $cookie_domain, $cookie_secure);

    $cookie['username'] = 'Guest';
    $cookie['password'] = 'Guest';
    $cookie['last_action'] = $now;
    $cookie['last_timeout'] = $now;
    $cookie['is_guest'] = true;

    return $cookie;
}



			if ($cur_user['save_pass'] == '0')
				$expire = 0;

			// Define this if you don't want PunBB to update the current users cookie
			if (!defined('PUN_DONT_UPDATE_COOKIE'))
			{
				// Has the user been idle longer than timeout_cookie?
				if ($now > ($cookie['last_action'] + $options['timeout_cookie']))
				{
					$cookie['last_timeout'] = $cookie['last_action'];
					$cookie['last_action'] = $now;

					setcookie('punbb_cookie', serialize(array($cookie['username'], $cookie['password'], $now, $cookie['last_timeout'])), $expire, $cookie_path, $cookie_domain, $cookie_secure);
				}
				else
				{
					$cookie['last_action'] = $now;

					setcookie('punbb_cookie', serialize(array($cookie['username'], $cookie['password'], $now, $cookie['last_timeout'])), $expire, $cookie_path, $cookie_domain, $cookie_secure);
				}
			}

			$cookie['is_guest'] = false;
		}
		else
			$cookie['is_guest'] = true;
	}
	else
	{
		$cookie['username'] = 'Guest';
		$cookie['password'] = 'Guest';
		$cookie['last_action'] = $now;
		$cookie['last_timeout'] = $now;
		$cookie['is_guest'] = true;
	}

	return $cookie;
}


//
// Try to determine the correct remote IP-address
//
function get_remote_address()
{
	// If HTTP_X_FORWARDED_FOR is set we grab the first address in the list
	if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
	{
		if (preg_match('/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/', $_SERVER['HTTP_X_FORWARDED_FOR'], $addresses))
			return $addresses[0];
	}

	// If no address was found in HTTP_X_FORWARDED_FOR, we try HTTP_CLIENT_IP and if that isn't set we return REMOTE_ADDR
	return (isset($_SERVER['HTTP_CLIENT_IP'])) ? $_SERVER['HTTP_CLIENT_IP'] : $_SERVER['REMOTE_ADDR'];
}


//
// Add slashes only if magic_quotes_gpc is off
//
function escape($str)
{
    $magic_quotes_active = (bool) ini_get('magic_quotes_gpc');
    if ($magic_quotes_active) {
        $str = stripslashes($str);
    }
    return addslashes($str);
}



//
// Strip slashes only if magic_quotes_gpc is on
//
function un_escape($str)
{
	return (ini_get('magic_quotes_gpc') == 1) ? stripslashes($str) : $str;
}


//
// Check whether the connecting user is banned (and delete any expired bans)
//
function check_bans()
{
	global $db, $cookie, $options, $lang_common;

	$ip = get_remote_address();

	$result = $db->query('SELECT id, username, ip, expire FROM '.$db->prefix.'bans WHERE username IS NOT NULL OR ip IS NOT NULL OR expire IS NOT NULL') or error('Unable to fetch ban list', __FILE__, __LINE__, $db->error());

	while ($row = $db->fetch_row($result))
	{
		if ($row[3] != '' && $row[3] <= time())
		{
			$db->query('DELETE FROM '.$db->prefix.'bans WHERE id='.$row[0]) or error('Unable to delete expired ban', __FILE__, __LINE__, $db->error());
			continue;
		}

		if (($row[1] != '' && !strcasecmp($cookie['username'], $row[1])) || ($row[2] != '' && !strcmp(substr($ip, 0, strlen($row[2])), $row[2])))
			message($lang_common['Banned message'].' <a href="mailto:'.$options['admin_email'].'">'.$options['admin_email'].'</a>.');
	}
}


//
// Update "Users online"
//
function update_users_online()
{
	global $db, $cookie, $cur_user, $options;

	if (!$cookie['is_guest'])
	{
		$user_id = $cur_user['id'];
		$ident = addslashes($cookie['username']);
	}
	else
	{
		$user_id = 0;
		$ident = get_remote_address();
	}

	$now = time();

	// Delete entries older than timeout_online seconds and any duplicates (start transaction)
	$db->query('DELETE FROM '.$db->prefix.'online WHERE logged<'.($now-$options['timeout_online']).' OR ident=\''.addslashes($cookie['username']).'\' OR ident=\''.get_remote_address().'\'', PUN_TRANS_START) or error('Unable to delete from online list', __FILE__, __LINE__, $db->error());

	// Add a new entry. username and user_id if logged in; ip and user_id=0 if not (end transaction)
	$db->query('INSERT INTO '.$db->prefix.'online (user_id, ident, logged) VALUES(\''.$user_id.'\', \''.$ident.'\', '.$now.')', PUN_TRANS_END) or error('Unable to insert into online list', __FILE__, __LINE__, $db->error());
}


//
// Format a time string according to $time_format and timezones
//
function format_time($timestamp, $date_only = false)
{
	global $cur_user, $options, $lang_common;

	if ($timestamp == '')
		return $lang_common['Never'];

		if (!isset($cur_user) || !isset($cur_user['timezone']) || $options['server_timezone'] == $cur_user['timezone'])
		    $diff = 0;
		else if ($options['server_timezone'] < $cur_user['timezone'])
		{
		    if ($options['server_timezone'] >= 0 && $cur_user['timezone'] >= 0)
		        $diff = $cur_user['timezone'] - $options['server_timezone'];
		    else if ($options['server_timezone'] < 0 && $cur_user['timezone'] >= 0)
		        $diff = (-1*$options['server_timezone']) + $cur_user['timezone'];
		    else if ($options['server_timezone'] < 0 && $cur_user['timezone'] < 0)
		        $diff = $cur_user['timezone'] - $options['server_timezone'];
		}
		else
		{
		    if ($options['server_timezone'] >= 0 && $cur_user['timezone'] >= 0)
		        $diff = $cur_user['timezone'] - $options['server_timezone'];
		    else if ($options['server_timezone'] >= 0 && $cur_user['timezone'] < 0)
		        $diff = (-1*$options['server_timezone']) + $cur_user['timezone'];
		    else if ($options['server_timezone'] < 0 && $cur_user['timezone'] < 0)
		        $diff = $cur_user['timezone'] - $options['server_timezone'];
		}


	$timestamp += $diff * 3600;

	$now = time();

	$date = date($options['date_format'], $timestamp);
	$today = date($options['date_format'], $now);
	$yesterday = date($options['date_format'], $now-86400);

	if ($date == $today)
		$date = $lang_common['Today'];
	else if ($date == $yesterday)
		$date = $lang_common['Yesterday'];

	if (!$date_only)
		return $date.' '.date($options['time_format'], $timestamp);
	else
		return $date;
}


//
// Generate the "navigator" that appears at the top of every page
//
function generate_navlinks()
{
	global $cur_user, $options, $permissions, $cookie, $lang_common;

	$links[] = '<a href="index.php">'.$lang_common['Home'].'</a> | <a href="userlist.php">'.$lang_common['User list'].'</a>';

	if ($options['rules'] == '1')
		$links[] = '<a href="misc.php?action=rules">'.$lang_common['Rules'].'</a>';

	if ($cookie['is_guest'])
	{
		if ($options['search'] == '1' && $permissions['guests_search'] == '1')
			$links[] = '<a href="search.php">'.$lang_common['Search'].'</a>';

		$links[] = '<a href="register.php">'.$lang_common['Register'].'</a> | <a href="login.php">'.$lang_common['Login'].'</a>';

		$info = $lang_common['Not logged in'];
	}
	else
	{
		if ($cur_user['status'] < 1)
		{
			if ($options['search'] == '1')
				$links[] = '<a href="search.php">'.$lang_common['Search'].'</a>';

			$links[] = '<a href="profile.php?id='.$cur_user['id'].'">'.$lang_common['Profile'].'</a>';
			$links[] = '<a href="login.php?action=out">'.$lang_common['Logout'].'</a>';
		}
		else
		{
			$links[] = '<a href="search.php">'.$lang_common['Search'].'</a>';
			$links[] = '<a href="profile.php?id='.$cur_user['id'].'">'.$lang_common['Profile'].'</a>';
			$links[] = '<a href="admin_index.php">'.$lang_common['Admin'].'</a>';
			$links[] = '<a href="login.php?action=out">'.$lang_common['Logout'].'</a>';
		}
	}

	return implode(' | ', $links);
}


//
// Update posts, topics, last_post, last_post_id and last_poster for a forum (redirect topics are not included)
// If $transaction == PUN_TRANS_END, this function will end the current transaction
//
function update_forum($forum_id, $transaction = 0)
{
	global $db;

	$result = $db->query('SELECT COUNT(id), SUM(num_replies) FROM '.$db->prefix.'topics WHERE moved_to IS NULL AND forum_id='.$forum_id) or error('Unable to fetch forum topic count', __FILE__, __LINE__, $db->error());
	list($num_topics, $num_posts) = $db->fetch_row($result);

	$num_posts = $num_posts + $num_topics;		// $posts is only the sum of all replies (we have to add the topic posts)

	$result = $db->query('SELECT last_post, last_post_id, last_poster FROM '.$db->prefix.'topics WHERE forum_id='.$forum_id.' AND moved_to IS NULL ORDER BY last_post DESC LIMIT 1') or error('Unable to fetch last_post/last_post_id/last_poster', __FILE__, __LINE__, $db->error());
	if ($db->num_rows($result))		// There are topics in the forum
	{
		list($last_post, $last_post_id, $last_poster) = $db->fetch_row($result);

		$db->query('UPDATE '.$db->prefix.'forums SET num_topics='.$num_topics.', num_posts='.$num_posts.', last_post='.$last_post.', last_post_id='.$last_post_id.', last_poster=\''.addslashes($last_poster).'\' WHERE id='.$forum_id, $transaction) or error('Unable to update last_post/last_post_id/last_poster', __FILE__, __LINE__, $db->error());
	}
	else	// There are no topics
		$db->query('UPDATE '.$db->prefix.'forums SET num_topics=0, num_posts=0, last_post=NULL, last_post_id=NULL, last_poster=NULL WHERE id='.$forum_id, $transaction) or error('Unable to update last_post/last_post_id/last_poster', __FILE__, __LINE__, $db->error());
}


//
// Check whether the current user is an administrator or a moderator in $forum_id (also check if forum is closed and/or admmod_only)
//
function is_admmod($forum_id, &$forum_closed, &$admmod_only)
{
	global $db, $cur_user;

	$result = $db->query('SELECT moderators, admmod_only, closed FROM '.$db->prefix.'forums WHERE id='.$forum_id) or error('Unable to fetch forum info', __FILE__, __LINE__, $db->error());

	if ($db->num_rows($result))
	{
		list($moderators, $admmod_only, $forum_closed) = $db->fetch_row($result);
		$mods_array = ($moderators != '') ? unserialize($moderators) : array();

		return (isset($cur_user['status']) == 2 || (isset($cur_user['status']) == 1 && array_key_exists($cur_user['username'], $mods_array))) ? true : false;
	}
	else
		return false;
}


//
// Replace censored words in $text
//
function censor_words($text)
{
	global $db;
	static $search_for, $replace_with;

	// If not already built, build an array of censor words and their replacement text
	if (empty($search_for))
	{
		$result = $db->query('SELECT search_for, replace_with FROM '.$db->prefix.'censoring') or error('Unable to fetch censor word list', __FILE__, __LINE__, $db->error());
		$num_words = $db->num_rows($result);

		if ($num_words)
		{
			for ($i = 0; $i < $num_words; $i++)
			{
				list($search_for[$i], $replace_with[$i]) = $db->fetch_row($result);
				$search_for[$i] = '/\b('.str_replace('\*', '\w*?', preg_quote($search_for[$i], '/')).')\b/i';
			}
		}
		else
			$search_for[] = 1;	// Dummy entry
	}

	if (!empty($search_for) && $search_for[0] != 1)
		$text = substr(preg_replace($search_for, $replace_with, ' '.$text.' '), 1, -1);

	return $text;
}


//
// Determines the correct title for $user
// $user must contain the elements 'username', 'title', 'status' and 'posts'
//
function get_title($user)
{
	global $db, $lang_common;
	static $ban_list, $ranklist;

	// If not already built, build an array of banned usernames
	if (empty($ban_list))
	{
		$ban_list[] = 1;		// Dummy entry

		$result = $db->query('SELECT LOWER(username) FROM '.$db->prefix.'bans WHERE username IS NOT NULL') or error('Unable to fetch banned username list', __FILE__, __LINE__, $db->error());

		while ($row = $db->fetch_row($result))
			$ban_list[] = $row[0];
	}

	// If not already built, build an array of ranks and their respective minimun number of posts
	if (empty($ranklist))
	{
		$ranklist[] = 1;		// Dummy entry

		$result = $db->query('SELECT rank, min_posts FROM '.$db->prefix.'ranks ORDER BY min_posts') or error('Unable to fetch rank list', __FILE__, __LINE__, $db->error());

		while ($row = $db->fetch_row($result))
			$ranklist[] = $row;
	}

	// If the user has a title
	if ($user['title'] != '')
		$user_title = htmlspecialchars($user['title']);
	// If the user is banned
	else if (in_array(strtolower($user['username']), $ban_list))
		$user_title = $lang_common['Banned'];
	else if ($user['status'] <= 0)
	{
		// Are there any ranks? (> 1 because there is a dummy entry)
		if (count($ranklist) > 1)
		{
			@reset($ranklist);
			next($ranklist);
			foreach ($ranklist as $value)
			{
				if (intval($user['num_posts']) >= isset($value[1]))
					$user_title = htmlspecialchars(isset($value[0]));
			}
		}

		// If the user didn't "reach" any rank
		if ($user_title == '')
			$user_title = $lang_common['Member'];
	}
	else if ($user['status'] == 1)
		$user_title = $lang_common['Moderator'];
	else
		$user_title = $lang_common['Administrator'];

	return $user_title;
}


//
// Generate a string with numbered links (appears at the bottom of multipage scripts)
//
function paginate($num_pages, $p, $base_url)
{
	global $lang_common;

	if ($num_pages <= 1)
		$string = '<u>1</u>';
	else
	{
		if ($p > 4)
			$string = '<a href="'.$base_url.'&amp;p=1">'.$lang_common['First page'].'</a>&nbsp;-';

		// Don't ask me how the following works. It just does, OK? :-)
		for ($current=$p-3, $stop=$p+4; $current < $stop; $current++)
		{
			if ($current < 1 || $current > $num_pages)
				continue;
			else if ($current != $p)
				$string .= '&nbsp;<a href="'.$base_url.'&amp;p='.$current.'">'.$current.'</a>';
			else
				$string .= '&nbsp;<b>'.$current.'</b>';
		}

		if ($p < ($num_pages-3))
			$string .= '&nbsp;-&nbsp;<a href="'.$base_url.'&amp;p='.$num_pages.'">'.$lang_common['Last page'].'</a>';
	}

	return $string;
}


//
// Make sure that HTTP_REFERER matches $options['base_url']/$script
//
function confirm_referer($script)
{
	global $lang_common, $options;

	if (!preg_match('#^'.preg_quote($options['base_url'].'/'.$script, '#').'#i', $_SERVER['HTTP_REFERER']))
		message($lang_common['Bad referer']);
}


//
// Generate a random password of length $len
//
function random_pass($len)
{
	$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

	while (strlen($password) < $len)
		$password .= substr($chars, (mt_rand() % strlen($chars)), 1);

	return $password;
}


//
// Display a message.
//
function message($message, $no_back_link = false)
{
	global $db, $lang_common, $options, $pun_start;

	if (!defined('PUN_HEADER'))
	{
		global $cur_user, $cookie;

		$page_title = htmlspecialchars($options['board_title']).' / '.$lang_common['Info'];
		require 'header.php';
	}

?>
<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>

<table class="punmain" cellspacing="1" cellpadding="4">
	<tr class="punhead">
		<td class="punhead"><?php print $lang_common['Info'] ?></td>
	</tr>
	<tr>
		<td class="puncon1">
			<?php print $message ?><br><br>
<?php if (!$no_back_link): ?>			<a href="JavaScript: history.go(-1)"><?php print $lang_common['Go back'] ?></a>.
<?php endif; ?>		</td>
	</tr>
</table>

<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>
<?php

	require 'footer.php';
}


//
// Display a message when board is in maintenance mode.
//
function maintenance_message()
{
	global $lang_common, $options, $cur_user;

	$message = str_replace("\n", '<br>', $options['maintenance_message']);
	$style = (!empty($cur_user)) ? $cur_user['style'] : $options['default_style'];


	// Load the maintenance template
	$fp = fopen('include/template/maintenance.tpl', 'r');
	$tpl_maint = trim(fread($fp, filesize('include/template/maintenance.tpl')));
	fclose($fp);


	// START SUBST - {pun_content_direction}
	$tpl_maint = str_replace('{pun_content_direction}', $lang_common['lang_direction'], $tpl_maint);
	// END SUBST - {pun_content_direction}


	// START SUBST - {pun_char_encoding}
	$tpl_maint = str_replace('{pun_char_encoding}', $lang_common['lang_encoding'], $tpl_maint);
	// END SUBST - {pun_char_encoding}


	// START SUBST - {pun_head}
	ob_start();

?>
<title><?php print htmlspecialchars($options['board_title']).' / '.$lang_common['Maintenance'] ?></title>
<link rel="stylesheet" type="text/css" href="style/<?php print $style.'.css' ?>">
<?php

	$tpl_temp = trim(ob_get_contents());
	$tpl_maint = str_replace('{pun_head}', $tpl_temp, $tpl_maint);
	ob_end_clean();
	// END SUBST - {pun_head}


	// START SUBST - {pun_maint_heading}
	$tpl_maint = str_replace('{pun_maint_heading}', $lang_common['Maintenance'], $tpl_maint);
	// END SUBST - {pun_maint_heading}


	// START SUBST - {pun_maint_message}
	$tpl_maint = str_replace('{pun_maint_message}', $message, $tpl_maint);
	// END SUBST - {pun_maint_message}


	exit($tpl_maint);
}


//
// Display $message and redirect user to $destination.
//
function redirect($destination, $message)
{
	global $lang_common, $options, $cur_user;

	if ($destination == '')
		$destination = 'index.php';

	$style = (!empty($cur_user)) ? $cur_user['style'] : $options['default_style'];


	// Load the redirect template
	$fp = fopen('include/template/redirect.tpl', 'r');
	$tpl_redir = trim(fread($fp, filesize('include/template/redirect.tpl')));
	fclose($fp);


	// START SUBST - {pun_content_direction}
	$tpl_redir = str_replace('{pun_content_direction}', $lang_common['lang_direction'], $tpl_redir);
	// END SUBST - {pun_content_direction}


	// START SUBST - {pun_char_encoding}
	$tpl_redir = str_replace('{pun_char_encoding}', $lang_common['lang_encoding'], $tpl_redir);
	// END SUBST - {pun_char_encoding}


	// START SUBST - {pun_head}
	ob_start();

?>
<meta http-equiv="refresh" content="<?php print $options['redirect_delay'] ?>;URL=<?php print $destination ?>">
<title><?php print htmlspecialchars($options['board_title']).' / '.$lang_common['Redirecting'] ?></title>
<link rel="stylesheet" type="text/css" href="style/<?php print $style.'.css' ?>">
<?php

	$tpl_temp = trim(ob_get_contents());
	$tpl_redir = str_replace('{pun_head}', $tpl_temp, $tpl_redir);
	ob_end_clean();
	// END SUBST - {pun_head}


	// START SUBST - {pun_redir_heading}
	$tpl_redir = str_replace('{pun_redir_heading}', $lang_common['Redirecting'], $tpl_redir);
	// END SUBST - {pun_redir_heading}


	// START SUBST - {pun_redir_text}
	$tpl_temp = $message.'<br><br>'.'<a href="'.$destination.'">'.$lang_common['Click redirect'].'</a>';
	$tpl_redir = str_replace('{pun_redir_text}', $tpl_temp, $tpl_redir);
	// END SUBST - {pun_redir_text}


	exit($tpl_redir);
}


//
// Display a simple error message
//
function error($message, $file, $line, $db_error = false)
{
	global $options, $db_type;

	// Empty output buffer and stop buffering
	ob_end_clean();

	// "Restart" output buffering if we are using ob_gzhandler (since the gzip header is already sent)
	if ($options['gzip'] == '1' && extension_loaded('zlib') && (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false || strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'deflate') !== false))
		ob_start('ob_gzhandler');

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title><?php print htmlspecialchars($options['board_title']) ?> / Error</title>
</head>
<body>

<table style="width: 60%; border: none; background-color: #666666" align="center" cellspacing="1" cellpadding="4">
	<tr>
		<td style="font: bold 10px Verdana, Arial, Helvetica, sans-serif; color: #593909; background-color: #EFAF50">An error was encountered</td>
	</tr>
	<tr>
		<td style="font: 10px Verdana, Arial, Helvetica, sans-serif; background-color: #DEDFDF">
<?php

	if (defined('PUN_DEBUG'))
	{
		print "\t\t\t".'<b>File:</b> '.$file.'<br>'."\n\t\t\t".'<b>Line:</b> '.$line.'<br><br>'."\n\t\t\t".'<b>PunBB reported</b>: '.$message."\n";

		if ($db_error)
			print "\t\t\t".'<br><b>Database reported:</b> '.htmlspecialchars($db_error['error']).' (Errno: '.$db_error['errno'].')'."\n";
	}
	else
		print "\t\t\t".'Error: <b>'.$message.'.</b>'."\n";

?>
		</td>
	</tr>
</table>

</body>
</html>
<?php

	// If a database connection was established (before this error) we close it
	if ($db_error)
		$GLOBALS[db]->close();

	exit;
}


// DEBUG FUNCTIONS BELOW

//
// Return current timestamp (with microseconds) as a float
//
function get_microtime()
{
	list($usec, $sec) = explode(' ', microtime());
	return ((float)$usec + (float)$sec);
}


//
// Dump contents of variable(s)
//
function dump($var1, $var2 = null)
{
	print '<pre>';
	print_r($var1);

	if ($var2 != null)
	{
		print "\n\n";
		print_r($var2);
	}

	print '</pre>';
	exit;
}
