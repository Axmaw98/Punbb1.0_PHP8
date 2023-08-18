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


if ($cur_user['status'] < 1)
	message($lang_common['No permission']);


// Get the server load averages
$output = @exec('uptime');
if (preg_match('/averages?: ([0-9\.]+),[\s]+([0-9\.]+),[\s]+([0-9\.]+)/i', $output, $server_load))
	$server_load = $server_load[1].' '.$server_load[2].' '.$server_load[3];
else
	$server_load = 'Not available.';


// Get number of current visitors
$result = $db->query('SELECT COUNT(user_id) FROM '.$db->prefix.'online') or error('Unable to fetch online count', __FILE__, __LINE__, $db->error());
$num_online = $db->result($result, 0);


// Get the database system version
$result = $db->query('SELECT version()') or error('Unable to fetch version info', __FILE__, __LINE__, $db->error());
$db_version = $db->result($result, 0);


if ($db_type == 'mysql')
{
	$db_version = 'MySQL '.$db_version;

	// Calculate total db size/row count (MySQL only)
	$result = $db->query('SHOW TABLE STATUS FROM '.$db_name) or error('Unable to fetch table status', __FILE__, __LINE__, $db->error());
	$num_tables = $db->num_rows($result);

  $total_records = 0;
	$total_size = 0;
	while ($num_tables--)
	{
	    $status = $db->fetch_row($result);
	    $total_records += (int)$status[3];
	    $total_size += (int)$status[5] + (int)$status[7];
	}


	$total_size = $total_size/1024;

	if ($total_size > 1024)
		$total_size = round($total_size/1024, 2).' MB';
	else
		$total_size = round($total_size, 2).' KB';
}


$page_title = htmlspecialchars($options['board_title']).' / Admin';
require 'header.php';

if ($cur_user['status'] > 1)
	admin_menu();
else
	moderator_menu();

?>
<table class="punmain" cellspacing="1" cellpadding="4">
	<tr class="punhead">
		<td class="punhead">Forum administation</td>
	</tr>
	<tr>
		<td class="puncon2">
			Welcome to the PunBB administration control panel. From here you can control vital aspects of the forum. Depending on whether you are an administrator or a moderator you can<br><br>
			&nbsp;- organize categories and forums.<br>
			&nbsp;- set forum-wide options and preferences.<br>
			&nbsp;- control permissions for users and guests.<br>
			&nbsp;- view IP statistics for users.<br>
			&nbsp;- ban users.<br>
			&nbsp;- censor words.<br>
			&nbsp;- set up user ranks.<br>
			&nbsp;- prune old posts.<br>
			&nbsp;- handle post reports.<br><br>
		</td>
	</tr>
</table>

<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>

<table class="punmain" cellspacing="1" cellpadding="4">
	<tr class="punhead">
		<td class="punhead" colspan="2">Statistics</td>
	</tr>
	<tr>
		<td class="puncon1right" style="width: 140px; white-space: nowrap">Current version&nbsp;&nbsp;</td>
		<td class="puncon2">
			&nbsp;PunBB <?php print $options['cur_version'] ?><br><br>
			&nbsp;Developed by Rickard Andersson<br>
			&nbsp;&copy Copyright 2002, 2003 Rickard Andersson
		</td>
	</tr>
	<tr>
		<td class="puncon1right" style="width: 140px; white-space: nowrap">Unix load averages&nbsp;&nbsp;</td>
		<td class="puncon2">&nbsp;<?php print $server_load ?> - <?php print $num_online ?> users online</td>
	</tr>
	<tr>
		<td class="puncon1right" style="width: 140px; white-space: nowrap">Environment&nbsp;&nbsp;</td>
		<td class="puncon2">
			&nbsp;PHP <?php print phpversion() ?><br>
			&nbsp;<?php print $db_version."\n" ?>
<?php if (isset($total_records) && isset($total_size)): ?>			<br><br>&nbsp;Rows: <?php print $total_records ?><br>
			&nbsp;Size: <?php print $total_size."\n" ?>
<?php endif; ?>
		</td>
	</tr>
</table>

<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>
<?php

require 'footer.php';
