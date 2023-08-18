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


if (isset($_POST['form_sent']))
{
	// Lazy referer check (in case base_url isn't correct)
	if (!preg_match('#/admin_options\.php#i', $_SERVER['HTTP_REFERER']))
		message($lang_common['Bad referer'].' <a href="mailto:'.$options['admin_email'].'">'.$options['admin_email'].'</a>.');

	$form = array_map('trim', $_POST['form']);

	if ($form['board_title'] == '')
		message('You must enter a board title.');

	require 'include/email.php';

	$form['admin_email'] = strtolower($form['admin_email']);
	if (!is_valid_email($form['admin_email']))
		message('The admin e-mail address you entered is invalid.');

	$form['webmaster_email'] = strtolower($form['webmaster_email']);
	if (!is_valid_email($form['webmaster_email']))
		message('The webmaster e-mail address you entered is invalid.');

	if ($form['mailing_list'] != '')
		$form['mailing_list'] = strtolower(preg_replace('/[\s]/', '', $form['mailing_list']));

	// Make sure all newlines are \n and not \r\n or \r
	if ($form['rules_message'] != '')
		$form['rules_message'] = str_replace("\r", "\n", str_replace("\r\n", "\n", $form['rules_message']));

	if ($form['rules'] == '1' && $form['rules_message'] == '')
		$form['rules'] = '0';

	// Make sure base_url doesn't end with a slash
	if (substr($form['base_url'], -1) == '/')
		$form['base_url'] = substr($form['base_url'], 0, -1);

	// Make sure avatars_dir doesn't end with a slash
	if (substr($form['avatars_dir'], -1) == '/')
		$form['avatars_dir'] = substr($form['avatars_dir'], 0, -1);

	if ($form['maintenance_message'] != '')
		$form['maintenance_message'] = str_replace("\r", "\n", str_replace("\r\n", "\n", $form['maintenance_message']));
	else
		$form['maintenance_message'] = 'The forums are temporarily down for maintenance. Please try again in a few minutes.\n\n/Administrator';

	foreach ($form as $key => $input)
	{
		$value = ($input != '') ? $value = '\''.escape($input).'\'' : 'NULL';
		$temp[] = $key.'='.$value;
	}

	$db->query('UPDATE '.$db->prefix.'options SET '.implode(',', $temp)) or error('Unable to update options', __FILE__, __LINE__, $db->error());

	redirect('admin_options.php', 'Options updated. Redirecting ...');
}


$page_title = htmlspecialchars($options['board_title']).' / Admin / Options';
$validate_form = true;
$form_name = 'update_options';
require 'header.php';

admin_menu('options');

?>
<form method="post" action="admin_options.php?action=foo" id="update_options" onsubmit="return process_form(this)">
	<input type="hidden" name="form_sent" value="1">
	<table class="punmain" cellspacing="1" cellpadding="4">
		<tr class="punhead">
			<td class="punhead" colspan="2">Options</td>
		</tr>
		<tr>
			<td class="puncon2cent" colspan="2"><br><input type="submit" name="submit" value="Submit"><br><br></td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap">Title and description&nbsp;&nbsp;</td>
			<td class="puncon2">
				<table class="punplain" cellpadding="6">
					<tr>
						<td class="punright" style="width: 35%"><b>Board title</b><br>The title of this bulletin board (shown at the top of every page). This field may <b>not</b> contain HTML.</td>
						<td style="width: 65%"><input type="text" name="form[board_title]" size="40" maxlength="255" value="<?php print htmlspecialchars($options['board_title']) ?>"></td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Board description</b><br>A short description of this bulletin board (shown at the top of every page). This field may contain HTML.</td>
						<td style="width: 65%"><input type="text" name="form[board_desc]" size="60" maxlength="255" value="<?php print htmlspecialchars($options['board_desc']) ?>"></td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap">Time and timeouts&nbsp;&nbsp;</td>
			<td class="puncon2">
				<table class="punplain" cellpadding="6">
					<tr>
						<td class="punright" style="width: 35%"><b>Server timezone</b><br>The timezone for the server.</td>
						<td style="width: 65%">
							<select name="form[server_timezone]">
								<option value="-12"<?php if ($options['server_timezone'] == -12 ) print ' selected' ?>>-12</option>
								<option value="-11"<?php if ($options['server_timezone'] == -11) print ' selected' ?>>-11</option>
								<option value="-10"<?php if ($options['server_timezone'] == -10) print ' selected' ?>>-10</option>
								<option value="-9"<?php if ($options['server_timezone'] == -9 ) print ' selected' ?>>-09</option>
								<option value="-8"<?php if ($options['server_timezone'] == -8 ) print ' selected' ?>>-08 PST</option>
								<option value="-7"<?php if ($options['server_timezone'] == -7 ) print ' selected' ?>>-07 MST</option>
								<option value="-6"<?php if ($options['server_timezone'] == -6 ) print ' selected' ?>>-06 CST</option>
								<option value="-5"<?php if ($options['server_timezone'] == -5 ) print ' selected' ?>>-05 EST</option>
								<option value="-4"<?php if ($options['server_timezone'] == -4 ) print ' selected' ?>>-04 AST</option>
								<option value="-3"<?php if ($options['server_timezone'] == -3 ) print ' selected' ?>>-03 ADT</option>
								<option value="-2"<?php if ($options['server_timezone'] == -2 ) print ' selected' ?>>-02</option>
								<option value="-1"<?php if ($options['server_timezone'] == -1) print ' selected' ?>>-01</option>
								<option value="0"<?php if ($options['server_timezone'] == 0) print ' selected' ?>>00 GMT</option>
								<option value="1"<?php if ($options['server_timezone'] == 1) print ' selected' ?>>+01 CET</option>
								<option value="2"<?php if ($options['server_timezone'] == 2 ) print ' selected' ?>>+02</option>
								<option value="3"<?php if ($options['server_timezone'] == 3 ) print ' selected' ?>>+03</option>
								<option value="4"<?php if ($options['server_timezone'] == 4 ) print ' selected' ?>>+04</option>
								<option value="5"<?php if ($options['server_timezone'] == 5 ) print ' selected' ?>>+05</option>
								<option value="6"<?php if ($options['server_timezone'] == 6 ) print ' selected' ?>>+06</option>
								<option value="7"<?php if ($options['server_timezone'] == 7 ) print ' selected' ?>>+07</option>
								<option value="8"<?php if ($options['server_timezone'] == 8 ) print ' selected' ?>>+08</option>
								<option value="9"<?php if ($options['server_timezone'] == 9 ) print ' selected' ?>>+09</option>
								<option value="10"<?php if ($options['server_timezone'] == 10) print ' selected' ?>>+10</option>
								<option value="11"<?php if ($options['server_timezone'] == 11) print ' selected' ?>>+11</option>
								<option value="12"<?php if ($options['server_timezone'] == 12 ) print ' selected' ?>>+12</option>
								<option value="13"<?php if ($options['server_timezone'] == 13 ) print ' selected' ?>>+13</option>
							</select>
						</td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Time format</b><br>The format string for representing time. See <a href="http://www.php.net/manual/en/function.date.php" target="_blank">here</a> for formatting options.</td>
						<td style="width: 65%"><input type="text" name="form[time_format]" size="25" maxlength="25" value="<?php print $options['time_format'] ?>">&nbsp;&nbsp;Current format: <?php print date($options['time_format']) ?></td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Date format</b><br>The format string for representing date. See <a href="http://www.php.net/manual/en/function.date.php" target="_blank">here</a> for formatting options.</td>
						<td style="width: 65%"><input type="text" name="form[date_format]" size="25" maxlength="25" value="<?php print $options['date_format'] ?>">&nbsp;&nbsp;Current format: <?php print date($options['date_format']) ?></td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Cookie timeout</b><br>Number of seconds to wait before writing a new cookie (primarily affects new message indicators).</td>
						<td style="width: 65%"><input type="text" name="form[timeout_cookie]" size="5" maxlength="5" value="<?php print $options['timeout_cookie'] ?>"></td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Online timeout</b><br>Number of seconds a user can be idle before being removed from the online users list.</td>
						<td style="width: 65%"><input type="text" name="form[timeout_online]" size="5" maxlength="5" value="<?php print $options['timeout_online'] ?>"></td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Redirect time</b><br>Number of seconds to wait when redirecting.</td>
						<td style="width: 65%"><input type="text" name="form[redirect_delay]" size="3" maxlength="3" value="<?php print $options['redirect_delay'] ?>"></td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Flood interval</b><br>Number of seconds that users have to wait between posts. Set to 0 to disable.</td>
						<td style="width: 65%"><input type="text" name="form[flood_interval]" size="4" maxlength="4" value="<?php print $options['flood_interval'] ?>"></td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap">Display&nbsp;&nbsp;</td>
			<td class="puncon2">
				<table class="punplain" cellpadding="6">
					<tr>
						<td class="punright" style="width: 35%"><b>Smilies</b><br>Convert a series of smilies to small icons.</td>
						<td style="width: 65%"><input type="radio" name="form[smilies]" value="1"<?php if ($options['smilies'] == '1') print ' checked' ?>>&nbsp;Yes&nbsp;&nbsp;&nbsp;<input type="radio" name="form[smilies]" value="0"<?php if ($options['smilies'] == '0') print ' checked' ?>>&nbsp;No</td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Smilies in signatures</b><br>Convert a series of smilies to small icons in user signatures.</td>
						<td style="width: 65%"><input type="radio" name="form[smilies_sig]" value="1"<?php if ($options['smilies_sig'] == '1') print ' checked' ?>>&nbsp;Yes&nbsp;&nbsp;&nbsp;<input type="radio" name="form[smilies_sig]" value="0"<?php if ($options['smilies_sig'] == '0') print ' checked' ?>>&nbsp;No</td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Make clickable links</b><br>When enabled, PunBB will automatically detect any URL's in posts and make them clickable hyperlinks.</td>
						<td style="width: 65%"><input type="radio" name="form[make_links]" value="1"<?php if ($options['make_links'] == '1') print ' checked' ?>>&nbsp;Yes&nbsp;&nbsp;&nbsp;<input type="radio" name="form[make_links]" value="0"<?php if ($options['make_links'] == '0') print ' checked' ?>>&nbsp;No</td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Show user post count</b><br>Show the number of posts a user has made (affects topic view, profile and userlist).</td>
						<td style="width: 65%"><input type="radio" name="form[show_post_count]" value="1"<?php if ($options['show_post_count'] == '1') print ' checked' ?>>&nbsp;Yes&nbsp;&nbsp;&nbsp;<input type="radio" name="form[show_post_count]" value="0"<?php if ($options['show_post_count'] == '0') print ' checked' ?>>&nbsp;No</td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Default style</b><br>This is the default style used if the visitor is a guest or a user that hasn't changed from the default in his/her profile.</td>
						<td style="width: 65%">
							<select name="form[default_style]">
<?php

		$d = dir('style');
		while (($entry = $d->read()) !== false)
		{
			if (substr($entry, strlen($entry)-4) == '.css')
				$styles[] = substr($entry, 0, strlen($entry)-4);
		}
		$d->close();

		foreach ($styles as $temp)
		{
			if ($options['default_style'] == $temp)
				print "\t\t\t\t\t\t\t\t<option value=\"$temp\" selected>".str_replace('_', ' ', $temp)."</option>\n";
			else
				print "\t\t\t\t\t\t\t\t<option value=\"$temp\">".str_replace('_', ' ', $temp)."</option>\n";
		}

?>
							</select>
						</td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Topic review</b><br>Maximum number of posts to display when posting (newest first). 0 to disable.</td>
						<td style="width: 65%"><input type="text" name="form[topic_review]" size="3" maxlength="3" value="<?php print $options['topic_review'] ?>"></td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Topics per page default</b><br>The default number of topics to display per page in a forum. Users can personalize this setting.</td>
						<td style="width: 65%"><input type="text" name="form[disp_topics_default]" size="3" maxlength="3" value="<?php print $options['disp_topics_default'] ?>"></td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Posts per page default</b><br>The default number of posts to display per page in a topic. Users can personalize this setting.</td>
						<td style="width: 65%"><input type="text" name="form[disp_posts_default]" size="3" maxlength="3" value="<?php print $options['disp_posts_default'] ?>"></td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Indent size</b><br>If set to 8, a regular tab will be used when displaying text within the [code][/code] tag. Otherwise this many spaces will be used to indent the text.</td>
						<td><input type="text" name="form[indent_num_spaces]" size="3" maxlength="3" value="<?php print $options['indent_num_spaces'] ?>"></td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap">Features&nbsp;&nbsp;</td>
			<td class="puncon2">
				<table class="punplain" cellpadding="6">
					<tr>
						<td class="punright" style="width: 35%"><b>Quick post</b><br>When enabled, PunBB will add a quick post form at the bottom of topics. This way users can post directly from the topic view.</td>
						<td style="width: 65%"><input type="radio" name="form[quickpost]" value="1"<?php if ($options['quickpost'] == '1') print ' checked' ?>>&nbsp;Yes&nbsp;&nbsp;&nbsp;<input type="radio" name="form[quickpost]" value="0"<?php if ($options['quickpost'] == '0') print ' checked' ?>>&nbsp;No</td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Users online</b><br>Display a list of users current online on the index page.</td>
						<td style="width: 65%"><input type="radio" name="form[users_online]" value="1"<?php if ($options['users_online'] == '1') print ' checked' ?>>&nbsp;Yes&nbsp;&nbsp;&nbsp;<input type="radio" name="form[users_online]" value="0"<?php if ($options['users_online'] == '0') print ' checked' ?>>&nbsp;No</td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><a name="censoring"><b>Censor words</b></a><br>Enable this to censor specific words in the forum. See <a href="admin_censoring.php">Censoring</a> for more info.</td>
						<td style="width: 65%"><input type="radio" name="form[censoring]" value="1"<?php if ($options['censoring'] == '1') print ' checked' ?>>&nbsp;Yes&nbsp;&nbsp;&nbsp;<input type="radio" name="form[censoring]" value="0"<?php if ($options['censoring'] == '0') print ' checked' ?>>&nbsp;No</td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><a name="ranks"><b>User ranks</b></a><br>Enable this to use user ranks. See <a href="admin_ranks.php">Ranks</a> for more info.</td>
						<td style="width: 65%"><input type="radio" name="form[ranks]" value="1"<?php if ($options['ranks'] == '1') print ' checked' ?>>&nbsp;Yes&nbsp;&nbsp;&nbsp;<input type="radio" name="form[ranks]" value="0"<?php if ($options['ranks'] == '0') print ' checked' ?>>&nbsp;No</td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>User has posted earlier</b><br>This feature displays a dot in front of topics in viewforum.php in case the currently logged in user has posted in that topic earlier. Disable if you are experiencing high server load.</td>
						<td style="width: 65%"><input type="radio" name="form[show_dot]" value="1"<?php if ($options['show_dot'] == '1') print ' checked' ?>>&nbsp;Yes&nbsp;&nbsp;&nbsp;<input type="radio" name="form[show_dot]" value="0"<?php if ($options['show_dot'] == '0') print ' checked' ?>>&nbsp;No</td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Quick jump</b><br>Enable the quick jump (jump to forum) drop list.</td>
						<td style="width: 65%"><input type="radio" name="form[quickjump]" value="1"<?php if ($options['quickjump'] == '1') print ' checked' ?>>&nbsp;Yes&nbsp;&nbsp;&nbsp;<input type="radio" name="form[quickjump]" value="0"<?php if ($options['quickjump'] == '0') print ' checked' ?>>&nbsp;No</td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>GZip output</b><br>If enabled, PunBB will gzip the output sent to browsers. This will reduce bandwidth usage, but use a little more CPU. This feature requires that PHP is configured with zlib (--with-zlib). Note: If you already have the Apache module mod_gzip set up to compress PHP scripts, you should disable this feature.</td>
						<td style="width: 65%"><input type="radio" name="form[gzip]" value="1"<?php if ($options['gzip'] == '1') print ' checked' ?>>&nbsp;Yes&nbsp;&nbsp;&nbsp;<input type="radio" name="form[gzip]" value="0"<?php if ($options['gzip'] == '0') print ' checked' ?>>&nbsp;No</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap">Reports&nbsp;&nbsp;</td>
			<td class="puncon2">
				<table class="punplain" cellpadding="6">
					<tr>
						<td class="punright" style="width: 35%"><b>Report method</b><br>Select the method for handling topic/post reports. You can choose whether topic/post reports should be handled by the internal report system,  e-mailed to the addresses on the mailing list (see below) or both.</td>
						<td style="width: 65%"><input type="radio" name="form[report_method]" value="0"<?php if ($options['report_method'] == '0') print ' checked' ?>>&nbsp;Internal&nbsp;&nbsp;&nbsp;<input type="radio" name="form[report_method]" value="1"<?php if ($options['report_method'] == '1') print ' checked' ?>>&nbsp;E-mail&nbsp;&nbsp;&nbsp;<input type="radio" name="form[report_method]" value="2"<?php if ($options['report_method'] == '2') print ' checked' ?>>&nbsp;Both</td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Mailing list</b><br>A comma separatad list of subscribers. The people on this list are the recipients of topic/post reports (see above).</td>
						<td style="width: 65%"><textarea name="form[mailing_list]" rows="5" cols="55"><?php print htmlspecialchars($options['mailing_list']) ?></textarea></td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap">Avatars&nbsp;&nbsp;</td>
			<td class="puncon2">
				<table class="punplain" cellpadding="6">
					<tr>
						<td class="punright" style="width: 35%"><b>Use avatars</b><br>When enabled, users will be able to upload an avatar which will be displayed under their title/rank.</td>
						<td style="width: 65%"><input type="radio" name="form[avatars]" value="1"<?php if ($options['avatars'] == '1') print ' checked' ?>>&nbsp;Yes&nbsp;&nbsp;&nbsp;<input type="radio" name="form[avatars]" value="0"<?php if ($options['avatars'] == '0') print ' checked' ?>>&nbsp;No</td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Upload directory</b><br>The upload directory for avatars (relative to the PunBB root directory). PHP must have write permissions to this directory.</td>
						<td style="width: 65%"><input type="text" name="form[avatars_dir]" size="35" maxlength="50" value="<?php print $options['avatars_dir'] ?>"></td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Max width</b><br>The maximum allowed width of avatars in pixels (60 is recommended).</td>
						<td style="width: 65%"><input type="text" name="form[avatars_width]" size="5" maxlength="5" value="<?php print $options['avatars_width'] ?>"></td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Max height</b><br>The maximum allowed height of avatars in pixels (60 is recommended).</td>
						<td style="width: 65%"><input type="text" name="form[avatars_height]" size="5" maxlength="5" value="<?php print $options['avatars_height'] ?>"></td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Max size</b><br>The maximum allowed size of avatars in bytes (10240 is recommended).</td>
						<td style="width: 65%"><input type="text" name="form[avatars_size]" size="6" maxlength="6" value="<?php print $options['avatars_size'] ?>"></td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap">Search&nbsp;&nbsp;</td>
			<td class="puncon2">
				<table class="punplain" cellpadding="6">
					<tr>
						<td class="punright" style="width: 35%"><b>Search enabled</b><br>When disabled, regular users will not be able to use the search feature. "Show new posts since last visit" and "Show posts by this user" will still work though.</td>
						<td style="width: 65%"><input type="radio" name="form[search]" value="1"<?php if ($options['search'] == '1') print ' checked' ?>>&nbsp;Yes&nbsp;&nbsp;&nbsp;<input type="radio" name="form[search]" value="0"<?php if ($options['search'] == '0') print ' checked' ?>>&nbsp;No</td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Search all forums</b><br>When disabled, searches will only be allowed in one forum at a time. Disable if server load is high due to excessive searching.</td>
						<td style="width: 65%"><input type="radio" name="form[search_all_forums]" value="1"<?php if ($options['search_all_forums'] == '1') print ' checked' ?>>&nbsp;Yes&nbsp;&nbsp;&nbsp;<input type="radio" name="form[search_all_forums]" value="0"<?php if ($options['search_all_forums'] == '0') print ' checked' ?>>&nbsp;No</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap">E-mail&nbsp;&nbsp;</td>
			<td class="puncon2">
				<table class="punplain" cellpadding="6">
					<tr>
						<td class="punright" style="width: 35%"><b>Base URL</b><br>The complete URL of the forum without trailing slash (i.e. http://www.mydomain.com/forums). This <b>must</b> be correct in order for all admin and moderator features to work. If you get "Bad referer" errors, it's probably incorrect.</td>
						<td style="width: 65%"><input type="text" name="form[base_url]" size="60" maxlength="100" value="<?php print $options['base_url'] ?>"></td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Admin e-mail</b><br>The e-mail address of the forum administrator.</td>
						<td style="width: 65%"><input type="text" name="form[admin_email]" size="50" maxlength="50" value="<?php print $options['admin_email'] ?>"></td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Webmaster e-mail</b><br>This is the address that all e-mails sent by the forum will be addressed from.</td>
						<td style="width: 65%"><input type="text" name="form[webmaster_email]" size="50" maxlength="50" value="<?php print $options['webmaster_email'] ?>"></td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Subscriptions</b><br>Enable users to subscribe to topics (recieve e-mail when someone replies).</td>
						<td style="width: 65%"><input type="radio" name="form[subscriptions]" value="1"<?php if ($options['subscriptions'] == '1') print ' checked' ?>>&nbsp;Yes&nbsp;&nbsp;&nbsp;<input type="radio" name="form[subscriptions]" value="0"<?php if ($options['subscriptions'] == '0') print ' checked' ?>>&nbsp;No</td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>SMTP server address</b><br>The address of an external SMTP server to send e-mails with. Leave blank to use the local mail program.</td>
						<td style="width: 65%"><input type="text" name="form[smtp_host]" size="30" maxlength="100" value="<?php print $options['smtp_host'] ?>"></td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>SMTP username</b><br>Username for SMTP server. Only enter a username if it is required by the SMTP server (most servers <b>don't</b> require authentication).</td>
						<td style="width: 65%"><input type="text" name="form[smtp_user]" size="25" maxlength="25" value="<?php print $options['smtp_user'] ?>"></td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>SMTP password</b><br>Password for SMTP server. Only enter a password if it is required by the SMTP server (most servers <b>don't</b> require authentication).</td>
						<td style="width: 65%"><input type="text" name="form[smtp_pass]" size="25" maxlength="25" value="<?php print $options['smtp_pass'] ?>"></td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap">Registration&nbsp;&nbsp;</td>
			<td class="puncon2">
				<table class="punplain" cellpadding="6">
					<tr>
						<td class="punright" style="width: 35%"><b>Allow new registrations</b><br>Controls whether this forum accepts new registrations. Disable only under special circumstances.</td>
						<td style="width: 65%"><input type="radio" name="form[regs_allow]" value="1"<?php if ($options['regs_allow'] == '1') print ' checked' ?>>&nbsp;Yes&nbsp;&nbsp;&nbsp;<input type="radio" name="form[regs_allow]" value="0"<?php if ($options['regs_allow'] == '0') print ' checked' ?>>&nbsp;No</td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Validate registrations</b><br>When enabled, users are e-mailed a random password when they register. They can then log in and change the password in their profile if they see fit. This feature also requires users to validate new e-mail addresses if they choose to change from the one they registered with. This is an effective way of avoiding registration abuse and making sure that all users have "correct" e-mail addresses in their profiles.</td>
						<td style="width: 65%"><input type="radio" name="form[regs_validate]" value="1"<?php if ($options['regs_validate'] == '1') print ' checked' ?>>&nbsp;Yes&nbsp;&nbsp;&nbsp;<input type="radio" name="form[regs_validate]" value="0"<?php if ($options['regs_validate'] == '0') print ' checked' ?>>&nbsp;No</td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Use forum rules</b><br>When enabled, users must agree to a set of rules when registering (enter text below). The rules will always be available through a link in the navigation table at the top of every page.</td>
						<td style="width: 65%"><input type="radio" name="form[rules]" value="1"<?php if ($options['rules'] == '1') print ' checked' ?>>&nbsp;Yes&nbsp;&nbsp;&nbsp;<input type="radio" name="form[rules]" value="0"<?php if ($options['rules'] == '0') print ' checked' ?>>&nbsp;No</td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Rules</b><br>Here you can enter any rules or other information that the user must review and accept when registering. If you enabled rules above you have to enter something here, otherwise it will be disabled. This text will not be parsed like regular posts and thus may contain HTML.</td>
						<td style="width: 65%"><textarea name="form[rules_message]" rows="10" cols="55"><?php print htmlspecialchars($options['rules_message']) ?></textarea></td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap">Maintenance&nbsp;&nbsp;</td>
			<td class="puncon2">
				<table class="punplain" cellpadding="6">
					<tr>
						<td class="punright" style="width: 35%"><a name="maintenance"><b>Maintenance mode</b></a><br>When enabled, the board will only be available to administrators. This should be used if the board needs to taken down temporarily for maintenance. WARNING! Do not log out when the board is in maintenance mode. You will not be able to login again.</td>
						<td style="width: 65%"><input type="radio" name="form[maintenance]" value="1"<?php if ($options['maintenance'] == '1') print ' checked' ?>>&nbsp;Yes&nbsp;&nbsp;&nbsp;<input type="radio" name="form[maintenance]" value="0"<?php if ($options['maintenance'] == '0') print ' checked' ?>>&nbsp;No</td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Message</b><br>The message that will be displayed to users when the board is in maintenance mode. If left blank a default message will be used. This text will not be parsed like regular posts and thus may contain HTML.</td>
						<td style="width: 65%"><textarea name="form[maintenance_message]" rows="5" cols="55"><?php print htmlspecialchars($options['maintenance_message']) ?></textarea></td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td class="puncon2cent" colspan="2"><br><input type="submit" name="submit" value="Submit"><br><br></td>
		</tr>
	</table>
</form>

<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>
<?php

require 'footer.php';
