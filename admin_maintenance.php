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

// Tell common.php that we don't want output buffering
define('PUN_DISABLE_BUFFERING', 1);

require 'include/common.php';
require 'include/commonadmin.php';


if ($cur_user['status'] < 2)
	message($lang_common['No permission']);


if (isset($_GET['req_per_page']) && isset($_GET['req_start_at']))
{
	confirm_referer('admin_maintenance.php');

	$per_page = intval($_GET['req_per_page']);
	$start_at = intval($_GET['req_start_at']);
	if (empty($per_page) || empty($start_at))
		message($lang_common['Bad request']);

	// If this is the first cycle of posts we empty the search index before we proceed
	if (isset($_GET['empty_index']))
	{
		$db->query('TRUNCATE TABLE '.$db->prefix.'search_matches') or error('Unable to empty search index match table', __FILE__, __LINE__, $db->error());
		$db->query('TRUNCATE TABLE '.$db->prefix.'search_words') or error('Unable to empty search index words table', __FILE__, __LINE__, $db->error());
	}

	$end_at = $start_at + $per_page;

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title><?php print htmlspecialchars($options['board_title']) ?> / Rebuilding search index...</title>
<style type="text/css">
body {
	font: 10px Verdana, Arial, Helvetica, sans-serif;
	color: #333333;
	background-color: #FFFFFF
}
</style>
</head>
<body>

Rebuilding index... This might be a good time to put on some coffee :-)<br><br>

<?php

	require 'include/searchidx.php';

	// Fetch posts to process
	$result = $db->query('SELECT DISTINCT t.id, p.id, p.message FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'posts AS p ON t.id=p.topic_id WHERE t.id>='.$start_at.' AND t.id<'.$end_at.' ORDER BY t.id') or error('Unable to fetch topic/post info', __FILE__, __LINE__, $db->error());

	while ($cur_post = $db->fetch_row($result))
	{
		if ($cur_post[0] <> $cur_topic)
		{
			// Fetch subject and ID of first post in topic
			$result2 = $db->query('SELECT p.id, t.subject, MIN(p.posted) AS first FROM '.$db->prefix.'posts AS p INNER JOIN '.$db->prefix.'topics AS t ON t.id=p.topic_id WHERE t.id='.$cur_post[0].' GROUP BY p.id, t.subject ORDER BY first LIMIT 1') or error('Unable to fetch topic info', __FILE__, __LINE__, $db->error());
			list($first_post, $subject) = $db->fetch_row($result2);

			$cur_topic = $cur_post[0];
		}

		print 'Processing post <b>'.$cur_post[1].'</b> in topic <b>'.$cur_post[0].'</b><br>'."\n";
	    flush();

		if ($cur_post[1] == $first_post)	// This is the "topic post" so we have to index the subject as well
			update_search_index('post', $cur_post[1], $cur_post[2], $subject);
		else
			update_search_index('post', $cur_post[1], $cur_post[2]);
	}

	// Check if there is more work to do
	$result = $db->query('SELECT id FROM '.$db->prefix.'topics WHERE id>'.$end_at) or error('Unable to fetch topic info', __FILE__, __LINE__, $db->error());
	if ($db->num_rows($result))
		print '<script type="text/javascript">window.location="admin_maintenance.php?req_per_page='.$per_page.'&req_start_at='.$end_at.'"</script>';
	else
		print '<script type="text/javascript">window.location="admin_maintenance.php"</script>';

	$db->close();
	exit;
}


else
{
	// Get the first post ID from the db
	$result = $db->query('SELECT id FROM '.$db->prefix.'topics ORDER BY id LIMIT 1') or error('Unable to create category', __FILE__, __LINE__, $db->error());
	if ($db->num_rows($result))
		$first_id = $db->result($result, 0);

	$page_title = htmlspecialchars($options['board_title']).' / Admin / Maintenance';
	$validate_form = true;
	$form_name = 'rebuild';
	$focus_element = 'req_per_page';
	require 'header.php';

	admin_menu('maintenance');

?>
<form method="get" action="admin_maintenance.php" id="rebuild" onsubmit="return process_form(this)">
	<table class="punmain" cellspacing="1" cellpadding="4">
		<tr class="punhead">
			<td class="punhead" colspan="2">Search index</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap">Rebuild search index&nbsp;&nbsp;</td>
			<td class="puncon2">
				<table class="punplain" cellpadding="6">
					<tr>
						<td colspan="3">If you switched language while there were posts in the database, you should rebuild the search index (to remove stopwords). For best performance you should put the forum in maintenance mode during rebuilding. <b>Rebuilding the search index can take a long time and will increase server load during the rebuild process!</b></td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Topics per cycle</b><br>The number of topics to process per pageview. E.g. if you were to enter 100, one hundred topics would be processed and then the page would refresh. This is to prevent the script from timing out during the rebuild process.</td>
						<td style="width: 35%"><input type="text" name="req_per_page" size="7" maxlength="7" value="100" tabindex="1"></td>
						<td style="width: 30%" rowspan="3"><input type="submit" name="rebuild_index" value="Rebuild index" tabindex="3"></td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Topic ID to start at</b><br>The topic ID to start rebuilding at. It's default value is the first available ID in the database. Normally you wouldn't want to change this.</td>
						<td style="width: 35%"><input type="text" name="req_start_at" size="7" maxlength="7" value="<?php print (isset($first_id)) ? $first_id : 0 ?>" tabindex="2"></td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Empty index</b><br>Select this if you want the search index to be emptied before rebuilding (see below).</td>
						<td style="width: 35%"><input type="checkbox" name="empty_index" value="1" checked></td>
					</tr>
					<tr>
						<td colspan="3">Once the process has completed you will be redirected back to this page. It is highly recommended that you have JavaScript enabled in your browser during rebuilding (for automatic redirect after a cycle has completed). If you are forced to abort the rebuild process, make a note of the last processed topic ID and enter that ID+1 in "Topic ID to start at" when/if you want to continue ("Empty index" must not be selected).</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</form>

<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>
<?php

	require 'footer.php';
}
