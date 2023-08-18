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

// If there is already a config.php, we shouldn't be here
if (defined('PUN'))
	exit('config.php already exists which would mean that PunBB is already installed. You should go <a href="index.php">here</a> instead.');

// Make sure we are running at least PHP 4.2.0
if (intval(str_replace('.', '', phpversion())) < 420)
	exit('You are running PHP version '.phpversion().'. PunBB requires at least PHP 4.2.0 to run properly. You must upgrade your PHP installation before you can continue.');

// Disable error reporting for uninitialized variables
error_reporting(E_ERROR | E_WARNING | E_PARSE);

// Turn off PHP time limit
@set_time_limit(0);


$punbb_version = '1.0';


if (!isset($_POST['form_sent']))
{

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>PunBB Installation</title>
<link rel="stylesheet" type="text/css" href="style/Oxygen.css">
<script type="text/javascript">
<!--
function set_focus()
{
	if (document.getElementById('install'))
		document.getElementById('install').req_db_host.focus()
}
function process_form( theform )
{
	// Check for required elements
	if (document.images) {
		for (i = 0; i < theform.length; i++) {
			if (theform.elements[i].name.substring(0, 4) == "req_") {
				if ((theform.elements[i].type=="text" || theform.elements[i].type=="textarea" || theform.elements[i].type=="password" || theform.elements[i].type=="file") && theform.elements[i].value=='') {
					alert(theform.elements[i].name.substring(4, 30) + " is a required field in this form.")
					return false
				}
			}
		}
	}
<?php if (!strstr($_SERVER['HTTP_USER_AGENT'], 'Opera')): ?>
	// Disable any submit buttons we find
	if (document.all || document.getElementById) {
		for (i = 0; i < theform.length; i++) {
			var elem = theform.elements[i]
			if (elem.type.toLowerCase() == "submit")
				elem.disabled = true
		}
		return true
	}
<?php endif; ?>	return true
}
// -->
</script>
</head>
<body onLoad="set_focus()">

<table class="punmain" style="width: 70%" align="center" cellspacing="1" cellpadding="4">
	<tr class="punhead">
		<td class="punhead">Instructions</td>
	</tr>
	<tr>
		<td class="puncon2">Welcome to PunBB installation! You are about to install PunBB <?php print $punbb_version ?>. Please make sure that the database that PunBB will be installed into is already created. If you are uncertain about what to enter in the fields below consult your server administrator.</td>
	</tr>
</table>

<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>

<form method="post" action="install.php" id="install" onsubmit="return process_form(this)">
	<input type="hidden" name="form_sent" value="1">
	<table class="punmain" style="width: 70%" align="center" cellspacing="1" cellpadding="4">
		<tr class="punhead">
			<td class="punhead" colspan="2">Configuration</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 180px; white-space: nowrap"><b>Database type</b>&nbsp;&nbsp;</td>
			<td class="puncon2">
				The type of database this forum will be using.<br><br>
				&nbsp;<select name="req_db_type">
					<option value="mysql">MySQL</option>
					<option value="pgsql">PostgreSQL</option>
				</select>
			</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 180px; white-space: nowrap"><b>Database server hostname</b>&nbsp;&nbsp;</td>
			<td class="puncon2">
				The address of the database server (example: localhost, db.myhost.com or 192.168.0.15). You can specify a specific port number if your database doesn't run on the default port (example: localhost:3580).<br><br>
				&nbsp;<input type="text" name="req_db_host" size="50" maxlength="100">
			</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 180px; white-space: nowrap"><b>Database name</b>&nbsp;&nbsp;</td>
			<td class="puncon2">
				The name of the database that PunBB will be installed into.<br><br>
				&nbsp;<input type="text" name="req_db_name" size="30" maxlength="50">
			</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 180px; white-space: nowrap">Database username&nbsp;&nbsp;</td>
			<td class="puncon2">
				The username with which you connect to the database.<br><br>
				&nbsp;<input type="text" name="db_username" size="30" maxlength="50">
			</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 180px; white-space: nowrap">Database password&nbsp;&nbsp;</td>
			<td class="puncon2">
				The password with which you connect to the database.<br><br>
				&nbsp;<input type="password" name="db_password" size="30" maxlength="50">
			</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 180px; white-space: nowrap">Table prefix&nbsp;&nbsp;</td>
			<td class="puncon2">
				If you like you can specify a table prefix. This way you can run multiple copies of PunBB in the same database (example: foo_).<br><br>
				&nbsp;<input type="text" name="db_prefix" size="20" maxlength="30">
			</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 180px; white-space: nowrap"><b>Administrator username</b>&nbsp;&nbsp;</td>
			<td class="puncon2">
				The username of the forum administrator. You can later create more adminstrators and moderators. Usernames can be between 2 and 25 characters long.<br><br>
				&nbsp;<input type="text" name="req_username" size="25" maxlength="25">
			</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 180px; white-space: nowrap"><b>Administrator password</b>&nbsp;&nbsp;</td>
			<td class="puncon2">
				Passwords can be between 4 and 16 characters long. Passwords are case sensitive.<br><br>
				&nbsp;<input type="password" name="req_password1" size="16" maxlength="16"><br>
				&nbsp;<input type="password" name="req_password2" size="16" maxlength="16">&nbsp;&nbsp;Re-enter password to confirm.
			</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 180px; white-space: nowrap"><b>Administrator e-mail</b>&nbsp;&nbsp;</td>
			<td class="puncon2">
				The e-mail address of the forum administrator.<br><br>
				&nbsp;<input type="text" name="req_email" size="50" maxlength="50">
			</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 180px; white-space: nowrap"><b>Base URL</b>&nbsp;&nbsp;</td>
			<td class="puncon2">
				The URL (without trailing slash) of your PunBB forum (example: http://forum.myhost.com or http://myhost.com/~myuser). This <b>must</b> be correct or administrators and moderators will not be able to submit any forms.<br><br>
				&nbsp;<input type="text" name="req_base_url" size="60" maxlength="100">
			</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 180px; white-space: nowrap"><b>Language</b>&nbsp;&nbsp;</td>
			<td class="puncon2">
				The language you want the forum to be displayed in.<br><br>
				&nbsp;<select name="req_lang">
					<option value="en">English</option>
					<option value="se">Swedish</option>
				</select>
			</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 180px; white-space: nowrap">Actions&nbsp;&nbsp;</td>
			<td class="puncon2">
				<br>&nbsp;&nbsp;<input type="submit" name="start" value="Start install"><br><br>
			</td>
		</tr>
	</table>
</form>

</body>
</html>
<?php

}
else
{
	//
	// Add slashes only if magic_quotes_gpc is off.
	//
	function escape($str)
	{
		return (get_magic_quotes_gpc() == 1) ? $str : addslashes($str);
	}


	//
	// Strip slashes only if magic_quotes_gpc is on.
	//
	function un_escape($str)
	{
	    if (function_exists('get_magic_quotes_runtime') && get_magic_quotes_runtime()) {
	        return stripslashes($str);
	    } else {
	        return $str;
	    }
	}


	//
	// A temporary replacement for the full error handler found in common.php
	// It's here because a function called error() must be callable in the database abstraction layer
	//
	function error($message, $file, $line)
	{
		exit('Error: '.$message.'.');
	}


	$db_type = $_POST['req_db_type'];
	$db_host = trim($_POST['req_db_host']);
	$db_name = trim($_POST['req_db_name']);
	$db_username = un_escape(trim($_POST['db_username']));
	$db_password = un_escape(trim($_POST['db_password']));
	$db_prefix = trim($_POST['db_prefix']);
	$username = un_escape(trim($_POST['req_username']));
	$email = strtolower(trim($_POST['req_email']));
	$password1 = un_escape(trim($_POST['req_password1']));
	$password2 = un_escape(trim($_POST['req_password2']));
	$lang = $_POST['req_lang'];


	// Make sure base_url doesn't end with a slash
	if (substr($_POST['req_base_url'], -1) == '/')
		$base_url = substr($_POST['req_base_url'], 0, -1);
	else
		$base_url = $_POST['req_base_url'];


	// Validate username and passwords
	if (strlen($username) < 2)
		exit('Usernames must be at least 2 characters long. Please go back and correct. <a href="JavaScript: history.go(-1)">Go back</a>.');
	if (strlen($password1) < 4)
		exit('Passwords must be at least 4 characters long. Please go back and correct. <a href="JavaScript: history.go(-1)">Go back</a>.');
	if ($password1 != $password2)
		exit('Passwords do not match. Please go back and correct. <a href="JavaScript: history.go(-1)">Go back</a>.');
	if (!strcasecmp($username, 'Guest'))
		exit('The username guest is reserved. Please go back and correct. <a href="JavaScript: history.go(-1)">Go back</a>.');
	if (preg_match('/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/', $username))
		exit('Usernames may not be in the form of an IP address. Please go back and correct. <a href="JavaScript: history.go(-1)">Go back</a>.');
	if (preg_match('#\[b\]|\[/b\]|\[u\]|\[/u\]|\[i\]|\[/i\]|\[color|\[/color\]|\[quote\]|\[/quote\]|\[code\]|\[/code\]|\[img\]|\[/img\]|\[url|\[/url\]|\[email|\[/email\]#i', $username))
		exit('Usernames may not contain any of the text formatting tags (BBCode) that the forum uses. <a href="JavaScript: history.go(-1)">Go back</a>.');

	if (!preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/', $email))
		exit('The administrator e-mail address you entered is invalid. Please go back and correct. <a href="JavaScript: history.go(-1)">Go back</a>.');


	// Load the appropriate DB layer class
	switch ($db_type)
	{
		case 'mysql':
			require 'include/dblayer/mysql.php';
			break;

		case 'pgsql':
			require 'include/dblayer/pgsql.php';
			break;

		default:
			exit('\''.$db_type.'\' is not a valid database type. <a href="JavaScript: history.go(-1)">Go back</a>.');
			break;
	}

	// Create the database object (and connect/select db)
	$db = new DBLayer($db_host, $db_username, $db_password, $db_name, $db_prefix, false);


	// Create all tables
	switch ($db_type)
	{
		case 'mysql':
			$sql = 'CREATE TABLE '.$db_prefix."bans (
					id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					username VARCHAR(25) DEFAULT NULL,
					ip VARCHAR(15) DEFAULT NULL,
					email VARCHAR(50) DEFAULT NULL,
					expire INT(10) UNSIGNED DEFAULT NULL,
					PRIMARY KEY (id)
					) ENGINE=InnoDB;";
			break;

		case 'pgsql':
			// This is a good time to start a transaction
			$db->query('BEGIN') or exit('Unable to start transaction. Please check your settings and try again. <a href="JavaScript: history.go(-1)">Go back</a>.');

			$sql = 'CREATE TABLE '.$db_prefix."bans (
					id SERIAL,
					username VARCHAR(25) DEFAULT NULL,
					ip VARCHAR(15) DEFAULT NULL,
					email VARCHAR(50) DEFAULT NULL,
					expire INT DEFAULT NULL,
					PRIMARY KEY (id)
					)";
			break;
	}

	$db->query($sql) or exit('Unable to create table '.$db_prefix.'bans. Please check your settings and try again. <a href="JavaScript: history.go(-1)">Go back</a>.');



	switch ($db_type)
	{
		case 'mysql':
			$sql = 'CREATE TABLE '.$db_prefix."categories (
					id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					cat_name VARCHAR(80) NOT NULL DEFAULT 'New Category',
					admmod_only TINYINT(1) NOT NULL DEFAULT '0',
					position INT(10) NOT NULL DEFAULT '0',
					PRIMARY KEY (id)
					) ENGINE=InnoDB;";
			break;

		case 'pgsql':
			$sql = 'CREATE TABLE '.$db_prefix."categories (
					id SERIAL,
					cat_name VARCHAR(80) NOT NULL DEFAULT 'New Category',
					admmod_only SMALLINT NOT NULL DEFAULT '0',
					position INT NOT NULL DEFAULT '0',
					PRIMARY KEY (id)
					)";
			break;
	}

	$db->query($sql) or exit('Unable to create table '.$db_prefix.'categories. Please check your settings and try again. <a href="JavaScript: history.go(-1)">Go back</a>.');



	switch ($db_type)
	{
		case 'mysql':
			$sql = 'CREATE TABLE '.$db_prefix."censoring (
					id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					search_for VARCHAR(60) NOT NULL DEFAULT '',
					replace_with VARCHAR(60) NOT NULL DEFAULT '',
					PRIMARY KEY (id)
					) ENGINE=InnoDB;";
			break;

		case 'pgsql':
			$sql = 'CREATE TABLE '.$db_prefix."censoring (
					id SERIAL,
					search_for VARCHAR(60) NOT NULL DEFAULT '',
					replace_with VARCHAR(60) NOT NULL DEFAULT '',
					PRIMARY KEY (id)
					)";
			break;
	}

	$db->query($sql) or exit('Unable to create table '.$db_prefix.'censoring. Please check your settings and try again. <a href="JavaScript: history.go(-1)">Go back</a>.');



	switch ($db_type)
	{
		case 'mysql':
			$sql = 'CREATE TABLE '.$db_prefix."forums (
					id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					forum_name VARCHAR(80) NOT NULL DEFAULT 'New forum',
					forum_desc TEXT DEFAULT NULL,
					moderators TEXT DEFAULT NULL,
					num_topics MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',
					num_posts MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',
					last_post INT(10) UNSIGNED DEFAULT NULL,
					last_post_id INT(10) UNSIGNED DEFAULT NULL,
					last_poster VARCHAR(25) DEFAULT NULL,
					closed TINYINT(1) NOT NULL DEFAULT '0',
					admmod_only TINYINT(1) NOT NULL DEFAULT '0',
					position INT(10) NOT NULL DEFAULT '0',
					cat_id INT(10) UNSIGNED NOT NULL DEFAULT '0',
					PRIMARY KEY (id)
					) ENGINE=InnoDB;";
			break;

		case 'pgsql':
			$sql = 'CREATE TABLE '.$db_prefix."forums (
					id SERIAL,
					forum_name VARCHAR(80) NOT NULL DEFAULT 'New forum',
					forum_desc TEXT DEFAULT NULL,
					moderators TEXT DEFAULT NULL,
					num_topics INT NOT NULL DEFAULT '0',
					num_posts INT NOT NULL DEFAULT '0',
					last_post INT DEFAULT NULL,
					last_post_id INT DEFAULT NULL,
					last_poster VARCHAR(25) DEFAULT NULL,
					closed SMALLINT NOT NULL DEFAULT '0',
					admmod_only SMALLINT NOT NULL DEFAULT '0',
					position INT NOT NULL DEFAULT '0',
					cat_id INT NOT NULL DEFAULT '0',
					PRIMARY KEY (id)
					)";
			break;
	}

	$db->query($sql) or exit('Unable to create table '.$db_prefix.'forums. Please check your settings and try again. <a href="JavaScript: history.go(-1)">Go back</a>.');



	switch ($db_type)
	{
		case 'mysql':
			$sql = 'CREATE TABLE '.$db_prefix."online (
					user_id INT(10) UNSIGNED NOT NULL DEFAULT '0',
					ident VARCHAR(25) NOT NULL DEFAULT '',
					logged INT(10) UNSIGNED NOT NULL DEFAULT '0'
					) ENGINE=MEMORY;";
			break;

		case 'pgsql':
			$sql = 'CREATE TABLE '.$db_prefix."online (
					user_id INT NOT NULL DEFAULT '0',
					ident VARCHAR(25) NOT NULL DEFAULT '',
					logged INT NOT NULL DEFAULT '0'
					)";
			break;
	}

	$db->query($sql) or exit('Unable to create table '.$db_prefix.'online. Please check your settings and try again. <a href="JavaScript: history.go(-1)">Go back</a>.');



	switch ($db_type)
	{
		case 'mysql':
			$sql = 'CREATE TABLE '.$db_prefix."options (
					cur_version VARCHAR(30) NOT NULL DEFAULT '',
					board_title TEXT NOT NULL DEFAULT '',
					board_desc TEXT DEFAULT NULL,
					server_timezone TINYINT(2) NOT NULL DEFAULT '0',
					time_format VARCHAR(15) NOT NULL DEFAULT 'H:i:s',
					date_format VARCHAR(15) NOT NULL DEFAULT 'Y-m-d',
					timeout_cookie SMALLINT(6) NOT NULL DEFAULT '600',
					timeout_online SMALLINT(6) NOT NULL DEFAULT '300',
					redirect_delay TINYINT(3) NOT NULL DEFAULT '1',
					flood_interval SMALLINT(5) UNSIGNED NOT NULL DEFAULT '30',
					smilies TINYINT(1) NOT NULL DEFAULT '1',
					smilies_sig TINYINT(1) NOT NULL DEFAULT '1',
					make_links TINYINT(1) NOT NULL DEFAULT '1',
					show_post_count TINYINT(1) NOT NULL DEFAULT '1',
					default_style VARCHAR(25) NOT NULL DEFAULT 'Oxygen',
					topic_review TINYINT(3) NOT NULL DEFAULT '15',
					disp_topics_default TINYINT(3) NOT NULL DEFAULT '30',
					disp_posts_default TINYINT(3) NOT NULL DEFAULT '25',
					indent_num_spaces TINYINT(3) NOT NULL DEFAULT '4',
					quickpost TINYINT(1) NOT NULL DEFAULT '1',
					users_online TINYINT(1) NOT NULL DEFAULT '1',
					censoring TINYINT(1) NOT NULL DEFAULT '0',
					ranks TINYINT(1) NOT NULL DEFAULT '1',
					show_dot TINYINT(1) NOT NULL DEFAULT '0',
					quickjump TINYINT(1) NOT NULL DEFAULT '1',
					gzip TINYINT(1) NOT NULL DEFAULT '0',
					report_method TINYINT(1) NOT NULL DEFAULT '0',
					mailing_list TEXT DEFAULT NULL,
					avatars TINYINT(1) NOT NULL DEFAULT '1',
					avatars_dir VARCHAR(50) NOT NULL DEFAULT 'img/avatars',
					avatars_width SMALLINT(5) UNSIGNED NOT NULL DEFAULT '60',
					avatars_height SMALLINT(5) UNSIGNED NOT NULL DEFAULT '60',
					avatars_size SMALLINT(5) UNSIGNED NOT NULL DEFAULT '10240',
					search TINYINT(1) NOT NULL DEFAULT '1',
					search_all_forums TINYINT(1) NOT NULL DEFAULT '1',
					base_url VARCHAR(100) NOT NULL DEFAULT '',
					admin_email VARCHAR(50) NOT NULL DEFAULT '',
					webmaster_email VARCHAR(50) NOT NULL DEFAULT '',
					subscriptions TINYINT(1) NOT NULL DEFAULT '1',
					smtp_host VARCHAR(100) DEFAULT NULL,
					smtp_user VARCHAR(25) DEFAULT NULL,
					smtp_pass VARCHAR(25) DEFAULT NULL,
					regs_allow TINYINT(1) NOT NULL DEFAULT '1',
					regs_validate TINYINT(1) NOT NULL DEFAULT '0',
					rules TINYINT(1) NOT NULL DEFAULT '0',
					rules_message TEXT DEFAULT NULL,
					maintenance TINYINT(1) NOT NULL DEFAULT '0',
					maintenance_message TEXT DEFAULT NULL
				) ENGINE=InnoDB;";
			break;

		case 'pgsql':
			$sql = 'CREATE TABLE '.$db_prefix."options (
					cur_version VARCHAR(30) NOT NULL DEFAULT '',
					board_title VARCHAR(255) NOT NULL DEFAULT '',
					board_desc VARCHAR(255) DEFAULT NULL,
					server_timezone SMALLINT NOT NULL DEFAULT '0',
					time_format VARCHAR(15) NOT NULL DEFAULT 'H:i:s',
					date_format VARCHAR(15) NOT NULL DEFAULT 'Y-m-d',
					timeout_cookie SMALLINT NOT NULL DEFAULT '600',
					timeout_online SMALLINT NOT NULL DEFAULT '300',
					redirect_delay SMALLINT NOT NULL DEFAULT '1',
					flood_interval SMALLINT NOT NULL DEFAULT '30',
					smilies SMALLINT NOT NULL DEFAULT '1',
					smilies_sig SMALLINT NOT NULL DEFAULT '1',
					make_links SMALLINT NOT NULL DEFAULT '1',
					show_post_count SMALLINT NOT NULL DEFAULT '1',
					default_style VARCHAR(25) NOT NULL DEFAULT 'Oxygen',
					topic_review SMALLINT NOT NULL DEFAULT '15',
					disp_topics_default SMALLINT NOT NULL DEFAULT '30',
					disp_posts_default SMALLINT NOT NULL DEFAULT '25',
					indent_num_spaces SMALLINT NOT NULL DEFAULT '4',
					quickpost SMALLINT NOT NULL DEFAULT '1',
					users_online SMALLINT NOT NULL DEFAULT '1',
					censoring SMALLINT NOT NULL DEFAULT '0',
					ranks SMALLINT NOT NULL DEFAULT '1',
					show_dot SMALLINT NOT NULL DEFAULT '0',
					quickjump SMALLINT NOT NULL DEFAULT '1',
					gzip SMALLINT NOT NULL DEFAULT '0',
					report_method SMALLINT NOT NULL DEFAULT '0',
					mailing_list TEXT DEFAULT NULL,
					avatars SMALLINT NOT NULL DEFAULT '1',
					avatars_dir VARCHAR(50) NOT NULL DEFAULT 'img/avatars',
					avatars_width SMALLINT NOT NULL DEFAULT '60',
					avatars_height SMALLINT NOT NULL DEFAULT '60',
					avatars_size SMALLINT NOT NULL DEFAULT '10240',
					search SMALLINT NOT NULL DEFAULT '1',
					search_all_forums SMALLINT NOT NULL DEFAULT '1',
					base_url VARCHAR(100) NOT NULL DEFAULT '',
					admin_email VARCHAR(50) NOT NULL DEFAULT '',
					webmaster_email VARCHAR(50) NOT NULL DEFAULT '',
					subscriptions SMALLINT NOT NULL DEFAULT '1',
					smtp_host VARCHAR(100) DEFAULT NULL,
					smtp_user VARCHAR(25) DEFAULT NULL,
					smtp_pass VARCHAR(25) DEFAULT NULL,
					regs_allow SMALLINT NOT NULL DEFAULT '1',
					regs_validate SMALLINT NOT NULL DEFAULT '0',
					rules SMALLINT NOT NULL DEFAULT '0',
					rules_message TEXT DEFAULT NULL,
					maintenance SMALLINT NOT NULL DEFAULT '0',
					maintenance_message TEXT DEFAULT NULL
				)";
			break;
	}

	$db->query($sql) or exit('Unable to create table '.$db_prefix.'options. Please check your settings and try again. <a href="JavaScript: history.go(-1)">Go back</a>.');



	switch ($db_type)
	{
		case 'mysql':
			$sql = 'CREATE TABLE '.$db_prefix."permissions (
					guests_read TINYINT(1) NOT NULL DEFAULT '1',
					guests_post TINYINT(1) NOT NULL DEFAULT '1',
					guests_post_topic TINYINT(1) NOT NULL DEFAULT '1',
					guests_search TINYINT(1) NOT NULL DEFAULT '1',
					users_post TINYINT(1) NOT NULL DEFAULT '1',
					users_post_topic TINYINT(1) NOT NULL DEFAULT '1',
					users_edit_post TINYINT(1) NOT NULL DEFAULT '1',
					users_del_post TINYINT(1) NOT NULL DEFAULT '1',
					users_del_topic TINYINT(1) NOT NULL DEFAULT '1',
					users_set_title TINYINT(1) NOT NULL DEFAULT '0',
					message_html TINYINT(1) NOT NULL DEFAULT '0',
					message_bbcode TINYINT(1) NOT NULL DEFAULT '1',
					message_img_tag TINYINT(1) NOT NULL DEFAULT '1',
					message_all_caps TINYINT(1) NOT NULL DEFAULT '0',
					subject_all_caps TINYINT(1) NOT NULL DEFAULT '0',
					sig_all_caps TINYINT(1) NOT NULL DEFAULT '0',
					sig_html TINYINT(1) NOT NULL DEFAULT '0',
					sig_bbcode TINYINT(1) NOT NULL DEFAULT '1',
					sig_img_tag TINYINT(1) NOT NULL DEFAULT '0',
					sig_length SMALLINT(5) NOT NULL DEFAULT '400',
					sig_lines TINYINT(3) NOT NULL DEFAULT '4',
					allow_banned_email TINYINT(1) NOT NULL DEFAULT '1',
					allow_dupe_email TINYINT(1) NOT NULL DEFAULT '0'
					) ENGINE=InnoDB;";
			break;

		case 'pgsql':
			$sql = 'CREATE TABLE '.$db_prefix."permissions (
					guests_read SMALLINT NOT NULL DEFAULT '1',
					guests_post SMALLINT NOT NULL DEFAULT '1',
					guests_post_topic SMALLINT NOT NULL DEFAULT '1',
					guests_search SMALLINT NOT NULL DEFAULT '1',
					users_post SMALLINT NOT NULL DEFAULT '1',
					users_post_topic SMALLINT NOT NULL DEFAULT '1',
					users_edit_post SMALLINT NOT NULL DEFAULT '1',
					users_del_post SMALLINT NOT NULL DEFAULT '1',
					users_del_topic SMALLINT NOT NULL DEFAULT '1',
					users_set_title SMALLINT NOT NULL DEFAULT '0',
					message_html SMALLINT NOT NULL DEFAULT '0',
					message_bbcode SMALLINT NOT NULL DEFAULT '1',
					message_img_tag SMALLINT NOT NULL DEFAULT '1',
					message_all_caps SMALLINT NOT NULL DEFAULT '0',
					subject_all_caps SMALLINT NOT NULL DEFAULT '0',
					sig_all_caps SMALLINT NOT NULL DEFAULT '0',
					sig_html SMALLINT NOT NULL DEFAULT '0',
					sig_bbcode SMALLINT NOT NULL DEFAULT '1',
					sig_img_tag SMALLINT NOT NULL DEFAULT '0',
					sig_length SMALLINT NOT NULL DEFAULT '400',
					sig_lines SMALLINT NOT NULL DEFAULT '4',
					allow_banned_email SMALLINT NOT NULL DEFAULT '1',
					allow_dupe_email SMALLINT NOT NULL DEFAULT '0'
					)";
			break;
	}

	$db->query($sql) or exit('Unable to create table '.$db_prefix.'permissions. Please check your settings and try again. <a href="JavaScript: history.go(-1)">Go back</a>.');



	switch ($db_type)
	{
		case 'mysql':
			$sql = 'CREATE TABLE '.$db_prefix."posts (
					id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					poster VARCHAR(25) NOT NULL DEFAULT '',
					poster_id INT(10) UNSIGNED NOT NULL DEFAULT '1',
					poster_ip VARCHAR(15) DEFAULT NULL,
					poster_email VARCHAR(50) DEFAULT NULL,
					message TEXT NOT NULL DEFAULT '',
					smilies TINYINT(1) NOT NULL DEFAULT '1',
					posted INT(10) UNSIGNED NOT NULL DEFAULT '0',
					edited INT(10) UNSIGNED DEFAULT NULL,
					edited_by VARCHAR(25) DEFAULT NULL,
					topic_id INT(10) UNSIGNED NOT NULL DEFAULT '0',
					PRIMARY KEY (id)
					) ENGINE=InnoDB;";
			break;

		case 'pgsql':
			$sql = 'CREATE TABLE '.$db_prefix."posts (
					id SERIAL,
					poster VARCHAR(25) NOT NULL DEFAULT '',
					poster_id INT NOT NULL DEFAULT '1',
					poster_ip VARCHAR(15) DEFAULT NULL,
					poster_email VARCHAR(50) DEFAULT NULL,
					message TEXT NOT NULL DEFAULT '',
					smilies SMALLINT NOT NULL DEFAULT '1',
					posted INT NOT NULL DEFAULT '0',
					edited INT DEFAULT NULL,
					edited_by VARCHAR(25) DEFAULT NULL,
					topic_id INT NOT NULL DEFAULT '0',
					PRIMARY KEY (id)
					)";
			break;
	}

	$db->query($sql) or exit('Unable to create table '.$db_prefix.'posts. Please check your settings and try again. <a href="JavaScript: history.go(-1)">Go back</a>.');



	switch ($db_type)
	{
		case 'mysql':
			$sql = 'CREATE TABLE '.$db_prefix."ranks (
					id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					rank VARCHAR(50) NOT NULL DEFAULT '',
					min_posts MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',
					PRIMARY KEY (id)
					) ENGINE=InnoDB;";
			break;

		case 'pgsql':
			$sql = 'CREATE TABLE '.$db_prefix."ranks (
					id SERIAL,
					rank VARCHAR(50) NOT NULL DEFAULT '',
					min_posts INT NOT NULL DEFAULT '0',
					PRIMARY KEY (id)
					)";
			break;
	}

	$db->query($sql) or exit('Unable to create table '.$db_prefix.'titles. Please check your settings and try again. <a href="JavaScript: history.go(-1)">Go back</a>.');



	switch ($db_type)
	{
		case 'mysql':
			$sql = 'CREATE TABLE '.$db_prefix."reports (
					id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					post_id int(10) UNSIGNED NOT NULL DEFAULT '0',
					topic_id int(10) UNSIGNED NOT NULL DEFAULT '0',
					forum_id int(10) UNSIGNED NOT NULL DEFAULT '0',
					reported_by int(10) UNSIGNED NOT NULL DEFAULT '0',
					created int(10) UNSIGNED NOT NULL DEFAULT '0',
					message text NOT NULL DEFAULT '',
					zapped int(10) UNSIGNED DEFAULT NULL,
					zapped_by int(10) UNSIGNED DEFAULT NULL,
					PRIMARY KEY (id)
					) ENGINE=InnoDB;";
			break;

		case 'pgsql':
			$sql = 'CREATE TABLE '.$db_prefix."reports (
					id SERIAL,
					post_id INT NOT NULL DEFAULT '0',
					topic_id INT NOT NULL DEFAULT '0',
					forum_id INT NOT NULL DEFAULT '0',
					reported_by INT NOT NULL DEFAULT '0',
					created INT NOT NULL DEFAULT '0',
					message TEXT NOT NULL DEFAULT '',
					zapped INT DEFAULT NULL,
					zapped_by INT DEFAULT NULL,
					PRIMARY KEY (id)
					)";
			break;
	}

	$db->query($sql) or exit('Unable to create table '.$db_prefix.'reports. Please check your settings and try again. <a href="JavaScript: history.go(-1)">Go back</a>.');



	switch ($db_type)
	{
		case 'mysql':
			$sql = 'CREATE TABLE '.$db_prefix."search_matches (
					post_id INT(10) UNSIGNED NOT NULL DEFAULT '0',
					word_id INT(10) UNSIGNED NOT NULL DEFAULT '0',
					subject_match TINYINT(1) NOT NULL DEFAULT '0'
					) ENGINE=InnoDB;";
			break;

		case 'pgsql':
			$sql = 'CREATE TABLE '.$db_prefix."search_matches (
					post_id INT NOT NULL DEFAULT '0',
					word_id INT NOT NULL DEFAULT '0',
					subject_match SMALLINT NOT NULL DEFAULT '0'
					)";
			break;
	}

	$db->query($sql) or exit('Unable to create table '.$db_prefix.'search_matches. Please check your settings and try again. <a href="JavaScript: history.go(-1)">Go back</a>.');



	switch ($db_type)
	{
		case 'mysql':
			$sql = 'CREATE TABLE '.$db_prefix."search_results (
					id INT(10) UNSIGNED NOT NULL DEFAULT '0',
					ident VARCHAR(25) NOT NULL DEFAULT '',
					search_data TEXT NOT NULL,
					PRIMARY KEY (id)
					) ENGINE=InnoDB;";
			break;

		case 'pgsql':
			$sql = 'CREATE TABLE '.$db_prefix."search_results (
					id INT NOT NULL DEFAULT '0',
					ident VARCHAR(25) NOT NULL DEFAULT '',
					search_data TEXT NOT NULL,
					PRIMARY KEY (id)
					)";
			break;
	}

	$db->query($sql) or exit('Unable to create table '.$db_prefix.'search_results. Please check your settings and try again. <a href="JavaScript: history.go(-1)">Go back</a>.');



	switch ($db_type)
	{
		case 'mysql':
			$sql = 'CREATE TABLE '.$db_prefix."search_words (
					id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					word VARCHAR(20) BINARY NOT NULL DEFAULT '',
					PRIMARY KEY (word),
					KEY ".$db_prefix."search_words_id_idx (id)
					) ENGINE=InnoDB;";
			break;

		case 'pgsql':
			$sql = 'CREATE TABLE '.$db_prefix."search_words (
					id SERIAL,
					word VARCHAR(20) NOT NULL DEFAULT '',
					PRIMARY KEY (word)
					)";
			break;
	}

	$db->query($sql) or exit('Unable to create table '.$db_prefix.'search_words. Please check your settings and try again. <a href="JavaScript: history.go(-1)">Go back</a>.');



	switch ($db_type)
	{
		case 'mysql':
			$sql = 'CREATE TABLE '.$db_prefix."topics (
					id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					poster VARCHAR(25) NOT NULL DEFAULT '',
					subject VARCHAR(70) NOT NULL DEFAULT '',
					posted INT(10) UNSIGNED NOT NULL DEFAULT '0',
					last_post INT(10) UNSIGNED NOT NULL DEFAULT '0',
					last_post_id INT(10) UNSIGNED NOT NULL DEFAULT '0',
					last_poster VARCHAR(25) DEFAULT NULL,
					subscribers TEXT DEFAULT NULL,
					num_views MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',
					num_replies MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',
					closed TINYINT(1) NOT NULL DEFAULT '0',
					sticky TINYINT(1) NOT NULL DEFAULT '0',
					moved_to INT(10) UNSIGNED DEFAULT NULL,
					forum_id INT(10) UNSIGNED NOT NULL DEFAULT '0',
					PRIMARY KEY (id)
					) ENGINE=InnoDB;";
			break;

		case 'pgsql':
			$sql = 'CREATE TABLE '.$db_prefix."topics (
					id SERIAL,
					poster VARCHAR(25) NOT NULL DEFAULT '',
					subject VARCHAR(70) NOT NULL DEFAULT '',
					posted INT NOT NULL DEFAULT '0',
					last_post INT NOT NULL DEFAULT '0',
					last_post_id INT NOT NULL DEFAULT '0',
					last_poster VARCHAR(25) DEFAULT NULL,
					subscribers TEXT DEFAULT NULL,
					num_views INT NOT NULL DEFAULT '0',
					num_replies INT NOT NULL DEFAULT '0',
					closed SMALLINT NOT NULL DEFAULT '0',
					sticky SMALLINT NOT NULL DEFAULT '0',
					moved_to INT DEFAULT NULL,
					forum_id INT NOT NULL DEFAULT '0',
					PRIMARY KEY (id)
					)";
			break;
	}

	$db->query($sql) or exit('Unable to create table '.$db_prefix.'topics. Please check your settings and try again. <a href="JavaScript: history.go(-1)">Go back</a>.');



	switch ($db_type)
	{
		case 'mysql':
			$sql = 'CREATE TABLE '.$db_prefix."users (
					id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					username VARCHAR(25) NOT NULL DEFAULT '',
					password VARCHAR(32) NOT NULL DEFAULT '',
					email VARCHAR(50) NOT NULL DEFAULT '',
					title VARCHAR(50) DEFAULT NULL,
					realname VARCHAR(40) DEFAULT NULL,
					url VARCHAR(100) DEFAULT NULL,
					icq VARCHAR(12) DEFAULT NULL,
					aim VARCHAR(20) DEFAULT NULL,
					yahoo VARCHAR(20) DEFAULT NULL,
					location VARCHAR(30) DEFAULT NULL,
					use_avatar TINYINT(1) NOT NULL DEFAULT '0',
					signature TEXT DEFAULT NULL,
					disp_topics TINYINT(3) UNSIGNED DEFAULT NULL,
					disp_posts TINYINT(3) UNSIGNED DEFAULT NULL,
					hide_email TINYINT(1) NOT NULL DEFAULT '0',
					save_pass TINYINT(1) NOT NULL DEFAULT '1',
					smilies TINYINT(1) NOT NULL DEFAULT '1',
					show_img TINYINT(1) NOT NULL DEFAULT '1',
					show_sig TINYINT(1) NOT NULL DEFAULT '1',
					link_to_new_win TINYINT(1) NOT NULL DEFAULT '1',
					timezone TINYINT(2) NOT NULL DEFAULT '0',
					style VARCHAR(25) NOT NULL DEFAULT 'Oxygen',
					num_posts INT(10) UNSIGNED NOT NULL DEFAULT '0',
					status TINYINT(1) NOT NULL DEFAULT '-1',
					last_post INT(10) UNSIGNED DEFAULT NULL,
					registered INT(10) UNSIGNED NOT NULL DEFAULT '0',
					admin_note VARCHAR(30) DEFAULT NULL,
					activate_string VARCHAR(50) DEFAULT NULL,
					activate_key VARCHAR(8) DEFAULT NULL,
					PRIMARY KEY (id)
					) ENGINE=InnoDB;";
			break;

		case 'pgsql':
			$sql = 'CREATE TABLE '.$db_prefix."users (
					id SERIAL,
					username VARCHAR(25) NOT NULL DEFAULT '',
					password VARCHAR(32) NOT NULL DEFAULT '',
					email VARCHAR(50) NOT NULL DEFAULT '',
					title VARCHAR(50) DEFAULT NULL,
					realname VARCHAR(40) DEFAULT NULL,
					url VARCHAR(100) DEFAULT NULL,
					icq VARCHAR(12) DEFAULT NULL,
					aim VARCHAR(20) DEFAULT NULL,
					yahoo VARCHAR(20) DEFAULT NULL,
					location VARCHAR(30) DEFAULT NULL,
					use_avatar SMALLINT NOT NULL DEFAULT '0',
					signature TEXT DEFAULT NULL,
					disp_topics SMALLINT DEFAULT NULL,
					disp_posts SMALLINT DEFAULT NULL,
					hide_email SMALLINT NOT NULL DEFAULT '0',
					save_pass SMALLINT NOT NULL DEFAULT '1',
					smilies SMALLINT NOT NULL DEFAULT '1',
					show_img SMALLINT NOT NULL DEFAULT '1',
					show_sig SMALLINT NOT NULL DEFAULT '1',
					link_to_new_win SMALLINT NOT NULL DEFAULT '1',
					timezone SMALLINT NOT NULL DEFAULT '0',
					style VARCHAR(25) NOT NULL DEFAULT 'Oxygen',
					num_posts INT NOT NULL DEFAULT '0',
					status SMALLINT NOT NULL DEFAULT '-1',
					last_post INT DEFAULT NULL,
					registered INT NOT NULL DEFAULT '0',
					admin_note VARCHAR(30) DEFAULT NULL,
					activate_string VARCHAR(50) DEFAULT NULL,
					activate_key VARCHAR(8) DEFAULT NULL,
					PRIMARY KEY (id)
					)";
			break;
	}

	$db->query($sql) or exit('Unable to create table '.$db_prefix.'users. Please check your settings and try again. <a href="JavaScript: history.go(-1)">Go back</a>.');


	// Add a few indexes
	$queries[] = 'CREATE INDEX '.$db_prefix.'posts_topic_id_idx ON '.$db_prefix.'posts(topic_id)';
	$queries[] = 'CREATE INDEX '.$db_prefix.'posts_poster_id_idx ON '.$db_prefix.'posts(poster_id)';
	$queries[] = 'CREATE INDEX '.$db_prefix.'reports_zapped_idx ON '.$db_prefix.'reports(zapped)';
	$queries[] = 'CREATE INDEX '.$db_prefix.'search_matches_word_id_idx ON '.$db_prefix.'search_matches(word_id)';
	$queries[] = 'CREATE INDEX '.$db_prefix.'search_matches_post_id_idx ON '.$db_prefix.'search_matches(post_id)';
	$queries[] = 'CREATE INDEX '.$db_prefix.'search_results_ident_idx ON '.$db_prefix.'search_results(ident)';
	$queries[] = 'CREATE INDEX '.$db_prefix.'topics_forum_id_idx ON '.$db_prefix.'topics(forum_id)';
	$queries[] = 'CREATE INDEX '.$db_prefix.'users_registered_idx ON '.$db_prefix.'users(registered)';

	// Special cases
	switch ($db_type)
	{
		case 'mysql':
			$queries[] = 'CREATE INDEX '.$db_prefix.'users_username_idx ON '.$db_prefix.'users(username(3))';
			break;

		case 'pgsql':
			$queries[] = 'CREATE INDEX '.$db_prefix.'users_username_idx ON '.$db_prefix.'users(username)';
			$queries[] = 'CREATE INDEX '.$db_prefix.'search_words_id_idx ON '.$db_prefix.'search_words(id)';

			// PostgreSQL <7.3 note
			// Remove the next 10 rows if running PostgreSQL 7.2 or earlier
			$queries[] = 'CREATE INDEX '.$db_prefix.'bans_id_idx ON '.$db_prefix.'bans(id)';
			$queries[] = 'CREATE INDEX '.$db_prefix.'categories_id_idx ON '.$db_prefix.'categories(id)';
			$queries[] = 'CREATE INDEX '.$db_prefix.'censoring_id_idx ON '.$db_prefix.'censoring(id)';
			$queries[] = 'CREATE INDEX '.$db_prefix.'forums_id_idx ON '.$db_prefix.'forums(id)';
			$queries[] = 'CREATE INDEX '.$db_prefix.'posts_id_idx ON '.$db_prefix.'posts(id)';
			$queries[] = 'CREATE INDEX '.$db_prefix.'ranks_id_idx ON '.$db_prefix.'ranks(id)';
			$queries[] = 'CREATE INDEX '.$db_prefix.'reports_id_idx ON '.$db_prefix.'reports(id)';
			$queries[] = 'CREATE INDEX '.$db_prefix.'search_results_id_idx ON '.$db_prefix.'search_results(id)';
			$queries[] = 'CREATE INDEX '.$db_prefix.'topics_id_idx ON '.$db_prefix.'topics(id)';
			$queries[] = 'CREATE INDEX '.$db_prefix.'users_id_idx ON '.$db_prefix.'users(id)';
			break;
	}

	@reset($queries);
	foreach ($queries as $sql)
		$db->query($sql) or exit('Unable to create indexes. Please check your configuration and try again. <a href="JavaScript: history.go(-1)">Go back</a>.');



	$now = time();

	// Insert some default values (start transaction)
	$db->query('INSERT INTO '.$db_prefix."options (cur_version, board_title, board_desc, base_url, admin_email, webmaster_email, mailing_list, rules_message, maintenance_message) VALUES('$punbb_version', 'My PunBB forum', 'Unfortunately no one can be told what PunBB is - you have to see it for yourself.', '$base_url', '$email', '$email', '$email', 'Enter your rules here.', 'The forums are temporarily down for maintenance. Please try again in a few minutes.\n\n/Administrator')", 1)
		or exit('Unable to insert into table '.$db_prefix.'options. Please check your configuration and try again. <a href="JavaScript: history.go(-1)">Go back</a>.');

	$db->query('INSERT INTO '.$db_prefix."permissions (message_html) VALUES(0)")
		or exit('Unable to insert into table '.$db_prefix.'permissions. Please check your configuration and try again. <a href="JavaScript: history.go(-1)">Go back</a>.');

	$db->query('INSERT INTO '.$db_prefix."users (username, password, email) VALUES('Guest', 'Guest', 'Guest')")
		or exit('Unable to insert into table '.$db_prefix.'users. Please check your configuration and try again. <a href="JavaScript: history.go(-1)">Go back</a>.');

	$db->query('INSERT INTO '.$db_prefix."users (username, password, email, num_posts, status, registered) VALUES('".addslashes($username)."', '".md5($password1)."', '$email', 1, 2, ".$now.')')
		or exit('Unable to insert into table '.$db_prefix.'users. Please check your configuration and try again. <a href="JavaScript: history.go(-1)">Go back</a>.');

	$db->query('INSERT INTO '.$db_prefix."categories (cat_name, position) VALUES('Test category', 1)")
		or exit('Unable to insert into table '.$db_prefix.'categories. Please check your configuration and try again. <a href="JavaScript: history.go(-1)">Go back</a>.');

	$db->query('INSERT INTO '.$db_prefix."forums (forum_name, forum_desc, num_topics, num_posts, last_post, last_post_id, last_poster, position, cat_id) VALUES('Test forum', 'This is just a test forum', 1, 1, ".$now.", 1, '".addslashes($username)."', 1, 1)")
		or exit('Unable to insert into table '.$db_prefix.'forums. Please check your configuration and try again. <a href="JavaScript: history.go(-1)">Go back</a>.');

	$db->query('INSERT INTO '.$db_prefix."topics (poster, subject, posted, last_post, last_post_id, last_poster, forum_id) VALUES('".addslashes($username)."', 'Test post', ".$now.", ".$now.", 1, '".addslashes($username)."', 1)")
		or exit('Unable to insert into table '.$db_prefix.'topics. Please check your configuration and try again. <a href="JavaScript: history.go(-1)">Go back</a>.');

	$db->query('INSERT INTO '.$db_prefix."posts (poster, poster_id, poster_ip, message, posted, topic_id) VALUES('".addslashes($username)."', 2, '127.0.0.1', 'If you are looking at this (which I guess you are), the install of PunBB appears to have worked! Now log in and head over to the administration control panel to configure your forum.', ".$now.', 1)')
		or exit('Unable to insert into table '.$db_prefix.'posts. Please check your configuration and try again. <a href="JavaScript: history.go(-1)">Go back</a>.');

	$db->query('INSERT INTO '.$db_prefix."ranks (rank, min_posts) VALUES('New member', 0)")
		or exit('Unable to insert into table '.$db_prefix.'titles. Please check your configuration and try again. <a href="JavaScript: history.go(-1)">Go back</a>.');

	$db->query('INSERT INTO '.$db_prefix."ranks (rank, min_posts) VALUES('Member', 10)", 2)	// end transaction
		or exit('Unable to insert into table '.$db_prefix.'titles. Please check your configuration and try again. <a href="JavaScript: history.go(-1)">Go back</a>.');



	// Check if default avatar directory is writable
	if (!@is_writable('img/avatars/'))
		$avatar_alert = '<br><br><span style="color: #C03000"><b>The default directory for avatars (img/avatars) is currently not writable!</b></span> If you want users to be able to upload their own avatar images you must see to it that the avatar directory is writable by PHP. You can later choose to save avatar images in a different directory (see Admin/Options).';


	/// Display config.php and give further instructions
	$config = '<?php'."\n\n".'$db_type = \''.$db_type."';\n".'$db_host = \''.$db_host."';\n".'$db_name = \''.$db_name."';\n".'$db_username = \''.$db_username."';\n".'$db_password = \''.$db_password."';\n".'$db_prefix = \''.$db_prefix."';\n".'$p_connect = true;'."\n\n".'$cookie_domain = '."'';\n".'$cookie_path = '."'/';\n".'$cookie_secure = 0;'."\n\n".'$language = \''.$lang.'\';'."\n\ndefine('PUN', 1);";


?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>PunBB Installation</title>
<link rel="stylesheet" type="text/css" href="style/Oxygen.css">
</head>
<body>

<table class="punmain" style="width: 70%" align="center" cellspacing="1" cellpadding="4">
	<tr class="punhead">
		<td class="punhead">Final instructions</td>
	</tr>
	<tr>
		<td class="puncon2">To finalize the installation all you need to do is to <b>copy and paste the text in the text box below into a file called config.php and then upload this file to the root directory of your PunBB installation</b>. You can later edit config.php if you reconfigure your setup (i.e. install a new language pack or change the database password).<?php print isset($avatar_alert) ?></td>
	</tr>
</table>

<table style="width: 70%" align="center" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>

<table class="punmain" style="width: 70%" align="center" cellspacing="1" cellpadding="4">
	<tr class="punhead">
		<td class="punhead" colspan="2">config.php</td>
	</tr>
	<tr>
		<td class="puncon1right" style="width: 180px; white-space: nowrap"><b>File contents</b>&nbsp;&nbsp;</td>
		<td class="puncon2">&nbsp;<textarea cols="80" rows="20"><?php print htmlspecialchars($config) ?></textarea></td>
	</tr>
	<tr>
		<td class="puncon1right" style="width: 180px; white-space: nowrap"><b>Info</b>&nbsp;&nbsp;</td>
		<td class="puncon2">
			<br>Once you have created config.php with the contents above, PunBB is installed!<br><br>
			<a href="index.php">Go to forum index</a>.
			<br><br>
		</td>
	</tr>
</table>

</body>
</html>
<?php

}
