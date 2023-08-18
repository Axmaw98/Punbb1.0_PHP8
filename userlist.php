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


// Load the userlist.php language file
require 'lang/'.$language.'/'.$language.'_userlist.php';

$page_title = htmlspecialchars($options['board_title']).' / '.$lang_ul['User list'];
require 'header.php';


$id = isset($_GET['id']);
if ($id != 'other' && $id != 'all' && !preg_match('/^[a-zA-Z]$/', $id))
	$id = 'A';

?>
<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>

<table class="punmain" cellspacing="1" cellpadding="4">
	<tr class="punhead">
		<td class="punhead"><?php print $lang_ul['User list'] ?></td>
	</tr>
	<tr>
		<td class="puncon2cent">
<?php

// Print out the alphabet
print "\t\t\t";
for ($i = 65; $i < 91; $i++)
{
	if (ord($id ) != $i)
		print '<b><a href="userlist.php?id='.chr($i).'">'.chr($i).'</a></b>&nbsp;&nbsp;';
	else
		print '<b>'.chr($i).'</b>&nbsp;&nbsp;';
}
print "\n";

?>
			<?php print (strcasecmp($id, 'other')) ? '<a href="userlist.php?id=other">'.$lang_ul['Other'].'</a>'."\n" : $lang_ul['Other']."\n"; ?>&nbsp;&nbsp;<?php print (strcasecmp($id, 'all')) ? '<a href="userlist.php?id=all">'.$lang_ul['All users'].'</a>'."\n" : $lang_ul['All users']."\n"; ?>
		</td>
	</tr>
</table>

<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>

<?php


if ($options['show_post_count'] == '0' && $cur_user['status'] < 1)
{

?>
<table class="punmain" cellspacing="1" cellpadding="4">
	<tr class="punhead">
		<td class="punhead" style="width: 23%"><?php print $lang_common['Username'] ?></td>
		<td class="punhead" style="width: 35%"><?php print $lang_common['E-mail'] ?></td>
		<td class="punhead" style="width: 21%"><?php print $lang_common['Title'] ?></td>
		<td class="punhead" style="width: 21%"><?php print $lang_common['Registered'] ?></td>
	</tr>
<?php

}
else
{

?>
<table class="punmain" cellspacing="1" cellpadding="4">
	<tr class="punhead">
		<td class="punhead" style="width: 18%"><?php print $lang_common['Username'] ?></td>
		<td class="punhead" style="width: 30%"><?php print $lang_common['E-mail'] ?></td>
		<td class="punhead" style="width: 20%"><?php print $lang_common['Title'] ?></td>
		<td class="punhead" style="width: 17%"><?php print $lang_common['Registered'] ?></td>
		<td class="punhead" style="width: 15%"><?php print $lang_common['Posts'] ?></td>
	</tr>
<?php

}


if ($id == 'all')
	$result = $db->query('SELECT COUNT(id)-1 FROM '.$db->prefix.'users') or error('Unable to fetch user list count', __FILE__, __LINE__, $db->error());
else if ($id == 'other')
{
	switch ($db_type)
	{
		case 'mysql':
			$result = $db->query('SELECT COUNT(id) FROM '.$db->prefix.'users WHERE id>1 AND username NOT REGEXP \'^[a-zA-Z]\'') or error('Unable to fetch user list count', __FILE__, __LINE__, $db->error());
			break;

		case 'pgsql';
			$result = $db->query('SELECT COUNT(id) FROM '.$db->prefix.'users WHERE id>1 AND username !~ \'^[a-zA-Z]\'') or error('Unable to fetch user list count', __FILE__, __LINE__, $db->error());
			break;
	}
}
else
	$result = $db->query('SELECT COUNT(id) FROM '.$db->prefix.'users WHERE id>1 AND username LIKE \''.$id.'%\'') or error('Unable to fetch user list count', __FILE__, __LINE__, $db->error());
$num_users = $db->result($result, 0);


// The number of pages required to display all users
$num_pages = ceil($num_users / 50);

if (!isset($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $num_pages)
{
	$p = 1;
	$start_from = 0;
}
else
{
	$p = $_GET['p'];
	$start_from = 50 * ($p - 1);
}


if ($id == 'all')
	$result = $db->query('SELECT id, username, email, title, hide_email, num_posts, status, registered FROM '.$db->prefix.'users WHERE id>1 ORDER BY username LIMIT '.$start_from.', 50') or error('Unable to fetch user list', __FILE__, __LINE__, $db->error());
else if ($id == 'other')
{
	switch ($db_type)
	{
		case 'mysql':
			$result = $db->query('SELECT id, username, email, title, hide_email, num_posts, status, registered FROM '.$db->prefix.'users WHERE id>1 AND username NOT REGEXP \'^[a-zA-Z]\' ORDER BY username LIMIT '.$start_from.', 50') or error('Unable to fetch user list', __FILE__, __LINE__, $db->error());
			break;

		case 'pgsql';
			$result = $db->query('SELECT id, username, email, title, hide_email, num_posts, status, registered FROM '.$db->prefix.'users WHERE id>1 AND username !~ \'^[a-zA-Z]\' ORDER BY username LIMIT '.$start_from.', 50') or error('Unable to fetch user list', __FILE__, __LINE__, $db->error());
			break;
	}
}
else
	$result = $db->query('SELECT id, username, email, title, hide_email, num_posts, status, registered FROM '.$db->prefix.'users WHERE id>1 AND username LIKE \''.$id.'%\' ORDER BY username LIMIT '.$start_from.', 50') or error('Unable to fetch user list', __FILE__, __LINE__, $db->error());
$num_users_page = $db->num_rows($result);


if ($num_users_page)
{
	while ($num_users_page--)
	{
		$user_data = $db->fetch_assoc($result);

		$user_title = get_title($user_data);

?>
	<tr class="puncon2">
		<td><?php print '<a href="profile.php?id='.$user_data['id'].'">'.htmlspecialchars($user_data['username']).'</a>' ?></td>
		<td><?php print ($user_data['hide_email'] == '0' || isset($cur_user['status']) > 0) ? '<a href="mailto:'.$user_data['email'].'">'.$user_data['email'].'</a>' : $lang_ul['Not displayed']; ?></td>
		<td><?php print $user_title ?></td>
		<td><?php print format_time($user_data['registered'], true) ?></td>
<?php if ($options['show_post_count'] == '1' || $cur_user['status'] > 0): ?>		<td><?php print $user_data['num_posts'] ?></td>
<?php endif; ?>	</tr>
<?php

	}
}
else
	print "\t".'<tr class="puncon2"><td colspan="5">'.$lang_ul['No users'].' "'.$id.'".</td></tr>'."\n";

?>
</table>

<table class="punplain" cellspacing="1" cellpadding="4">
	<tr>
		<td><?php print $lang_common['Pages'].': '.paginate($num_pages, $p, 'userlist.php?id='.$id) ?></td>
	</tr>
</table>
<?php

require 'footer.php';
