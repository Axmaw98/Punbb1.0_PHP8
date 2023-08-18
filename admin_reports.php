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


// Zap a report
if (isset($_POST['zap_id']))
{
	confirm_referer('admin_reports.php');

	$zap_id = key($_POST['zap_id']);

	$result = $db->query('SELECT zapped FROM '.$db->prefix.'reports WHERE id='.$zap_id) or error('Unable to fetch report info', __FILE__, __LINE__, $db->error());
	$zapped = $db->result($result, 0);

	if ($zapped == '')
		$db->query('UPDATE '.$db->prefix.'reports SET zapped='.time().', zapped_by='.$cur_user['id'].' WHERE id='.$zap_id) or error('Unable to zap report', __FILE__, __LINE__, $db->error());

	redirect('admin_reports.php', 'Report zapped. Redirecting ...');
}


$page_title = htmlspecialchars($options['board_title']).' / Admin / Reports';
require 'header.php';

if ($cur_user['status'] > 1)
	admin_menu('reports');
else
	moderator_menu('reports');


?>
<form method="post" action="admin_reports.php?action=zap">
	<table class="punmain" cellspacing="1" cellpadding="4">
		<tr class="punhead"><td colspan="6">New reports</td></tr>
		<tr class="puncon3">
			<td style="width: 15%">Forum</td>
			<td style="width: 20%">Topic</td>
			<td>Message</td>
			<td style="width: 10%">Reporter</td>
			<td style="width: 12%">Created</td>
			<td class="puncent" width="6%">Actions</td>
		</tr>
<?php

$result = $db->query('SELECT r.id, r.post_id, r.topic_id, r.forum_id, r.reported_by, r.created, r.message, t.subject, f.forum_name, u.username AS reporter FROM '.$db->prefix.'reports AS r INNER JOIN '.$db->prefix.'topics AS t ON r.topic_id=t.id INNER JOIN '.$db->prefix.'forums AS f ON r.forum_id=f.id LEFT JOIN '.$db->prefix.'users AS u ON r.reported_by=u.id WHERE r.zapped IS NULL ORDER BY created DESC') or error('Unable to fetch report list', __FILE__, __LINE__, $db->error());

if ($db->num_rows($result))
{
	while ($cur_report = $db->fetch_assoc($result))
	{
		$reporter = ($cur_report['reporter'] != '') ? '<a href="profile.php?id='.$cur_report['reported_by'].'">'.htmlspecialchars($cur_report['reporter']).'</a>' : 'N/A';

?>
		<tr style="height: 24">
			<td class="puncon1"><a href="viewforum.php?id=<?php print $cur_report['forum_id'] ?>"><?php print htmlspecialchars($cur_report['forum_name']) ?></a></td>
			<td class="puncon2"><a href="viewtopic.php?id=<?php print $cur_report['topic_id'] ?>"><?php print htmlspecialchars($cur_report['subject']) ?></a></td>
			<td class="puncon1"><a href="viewtopic.php?pid=<?php print $cur_report['post_id'].'#'.$cur_report['post_id'] ?>"><?php print str_replace("\n", '<br>', htmlspecialchars($cur_report['message'])) ?></a></td>
			<td class="puncon2"><?php print $reporter ?></td>
			<td class="puncon1"><?php print format_time($cur_report['created']) ?></td>
			<td class="puncon2cent"><input type="submit" name="zap_id[<?php print $cur_report['id'] ?>]" value=" Zap "></td>
		</tr>
<?php

	}
}
else
	print "\t\t".'<tr><td class="puncon1" colspan="6">There are no new reports.</td></tr>'."\n";

?>
	</table>
</form>

<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>

<table class="punmain" cellspacing="1" cellpadding="4">
	<tr class="punhead"><td colspan="6">10 last zapped reports</td></tr>
	<tr class="puncon3">
		<td style="width: 15%">Forum</td>
		<td style="width: 20%">Topic</td>
		<td>Message</td>
		<td style="width: 10%">Reporter</td>
		<td style="width: 18%">Zapped</td>
	</tr>
<?php

$result = $db->query('SELECT r.id, r.post_id, r.topic_id, r.forum_id, r.reported_by, r.message, r.zapped, r.zapped_by AS zapped_by_id, t.subject, f.forum_name, u.username AS reporter, u2.username AS zapped_by FROM '.$db->prefix.'reports AS r INNER JOIN '.$db->prefix.'topics AS t ON r.topic_id=t.id INNER JOIN '.$db->prefix.'forums AS f ON r.forum_id=f.id LEFT JOIN '.$db->prefix.'users AS u ON r.reported_by=u.id LEFT JOIN '.$db->prefix.'users AS u2 ON r.zapped_by=u2.id WHERE r.zapped IS NOT NULL ORDER BY zapped DESC LIMIT 10') or error('Unable to fetch report list', __FILE__, __LINE__, $db->error());

if ($db->num_rows($result))
{
	while ($cur_report = $db->fetch_assoc($result))
	{
		$reporter = ($cur_report['reporter'] != '') ? '<a href="profile.php?id='.$cur_report['reported_by'].'">'.htmlspecialchars($cur_report['reporter']).'</a>' : 'N/A';
		$zapped_by = ($cur_report['zapped_by'] != '') ? '<a href="profile.php?id='.$cur_report['zapped_by_id'].'">'.htmlspecialchars($cur_report['zapped_by']).'</a>' : 'N/A';

?>
	<tr style="height: 24">
		<td class="puncon1"><a href="viewforum.php?id=<?php print $cur_report['forum_id'] ?>"><?php print htmlspecialchars($cur_report['forum_name']) ?></a></td>
		<td class="puncon2"><a href="viewtopic.php?id=<?php print $cur_report['topic_id'] ?>"><?php print htmlspecialchars($cur_report['subject']) ?></a></td>
		<td class="puncon1"><a href="viewtopic.php?pid=<?php print $cur_report['post_id'].'#'.$cur_report['post_id'] ?>"><?php print str_replace("\n", '<br>', htmlspecialchars($cur_report['message'])) ?></a></td>
		<td class="puncon2"><?php print $reporter ?></td>
		<td class="puncon1"><?php print format_time($cur_report['zapped']).' by '.$zapped_by ?></td>
	</tr>
<?php

	}
}
else
	print "\t".'<tr><td class="puncon1" colspan="6">There are no zapped reports.</td></tr>'."\n";

?>
</table>

<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>
<?php

require 'footer.php';
