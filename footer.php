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


$tpl_temp = trim(ob_get_contents());
$tpl_main = str_replace('{pun_main}', $tpl_temp, $tpl_main);
ob_end_clean();
// END SUBST - {pun_main}


// START SUBST - {pun_footer}
ob_start();

?>
<table class="punmain" cellspacing="1" cellpadding="4">
	<tr>
		<td class="puncon2">
			<table class="punplain" cellspacing="0" cellpadding="0">
				<tr>
					<td class="puntop">
<?php

if (isset($footer_style) == 'index' || isset($footer_style) == 'search')
{
	if (!$cookie['is_guest'])
	{
		if ($footer_style != 'search')
			print "\t\t\t\t\t\t".'<a href="search.php?action=show_new">'.$lang_common['Show new posts'].'</a><br>'."\n";

		print "\t\t\t\t\t\t".'<a href="search.php?action=show_unanswered">'.$lang_common['Show unanswered posts'].'</a><br>'."\n";
		print "\t\t\t\t\t\t".'<a href="search.php?action=show_user&user_id='.$cur_user['id'].'">'.$lang_common['Show your posts'].'</a><br>'."\n";
		print "\t\t\t\t\t\t".'<a href="misc.php?action=markread">'.$lang_common['Mark all as read'].'</a><br>'."\n";
	}
	else
	{
		if ($permissions['guests_search'] == '1')
			print "\t\t\t\t\t\t".'<a href="search.php?action=show_unanswered">'.$lang_common['Show unanswered posts'].'</a><br>'."\n";
		else
			print "\t\t\t\t\t\t".'&nbsp;'."\n";
	}
}
else if (isset($footer_style) == 'forum' || isset($footer_style) == 'topic')
{
	// Display the "Jump to" drop list
	if ($options['quickjump'] == '1')
	{

?>
						<b><?php print $lang_common['Jump to'] ?></b><br>
						<form method="get" action="viewforum.php">
							<select name="id" onchange="window.location=('viewforum.php?id='+this.options[this.selectedIndex].value)">
<?php

	if ($cur_user['status'] < 1)
		$extra = ' WHERE c.admmod_only=\'0\' AND f.admmod_only=\'0\'';

	$result = $db->query('SELECT c.id AS cid, c.cat_name, f.id AS fid, f.forum_name FROM '.$db->prefix.'categories AS c INNER JOIN '.$db->prefix.'forums AS f ON c.id=f.cat_id'.$extra.' ORDER BY c.position, cid, f.position') or error('Unable to fetch category/forum list', __FILE__, __LINE__, $db->error());

	while ($cur_forum = $db->fetch_assoc($result))
	{
		if ($cur_forum['cid'] != $cur_category)	// A new category since last iteration?
		{
			if (!empty($cur_category))
				print "\t\t\t\t\t\t\t\t".'</optgroup>'."\n";

			print "\t\t\t\t\t\t\t\t".'<optgroup label="'.htmlspecialchars($cur_forum['cat_name']).'">'."\n";
			$cur_category = $cur_forum['cid'];
		}

		if ($cur_forum['fid'] != $forum_id)
			print "\t\t\t\t\t\t\t\t\t".'<option value="'.$cur_forum['fid'].'">'.htmlspecialchars($cur_forum['forum_name']).'</option>'."\n";
		else
			print "\t\t\t\t\t\t\t\t\t".'<option value="'.$cur_forum['fid'].'" selected>'.htmlspecialchars($cur_forum['forum_name']).'</option>'."\n";
	}

?>
								</optgroup>
							</select>
							<input type="submit" value="<?php print $lang_common['Go'] ?>">
						</form>
<?php

	}

	if ($footer_style == 'topic' && $is_admmod)
	{
		print "\t\t\t\t\t\t".'<br><a href="moderate.php?fid='.$forum_id.'&amp;move='.$id.'">'.$lang_common['Move topic'].'</a><br>'."\n";

		if ($closed == '1')
			print "\t\t\t\t\t\t".'<a href="moderate.php?fid='.$forum_id.'&amp;open='.$id.'">'.$lang_common['Open topic'].'</a><br>'."\n";
		else
			print "\t\t\t\t\t\t".'<a href="moderate.php?fid='.$forum_id.'&amp;close='.$id.'">'.$lang_common['Close topic'].'</a><br>'."\n";

		if ($sticky == '1')
			print "\t\t\t\t\t\t".'<a href="moderate.php?fid='.$forum_id.'&amp;unstick='.$id.'">'.$lang_common['Unstick topic'].'</a><br>'."\n";
		else
			print "\t\t\t\t\t\t".'<a href="moderate.php?fid='.$forum_id.'&amp;stick='.$id.'">'.$lang_common['Stick topic'].'</a><br>'."\n";

		print "\t\t\t\t\t\t".'<a href="moderate.php?fid='.$forum_id.'&amp;edit_subscribers='.$id.'">'.$lang_common['Edit subscribers'].'</a>'."\n";
	}
	else if ($options['quickjump'] == '0')	// Only print out the nbsp if we didn't display the quickjump
		print "\t\t\t\t\t\t".'&nbsp;'."\n";
}
else if (isset($footer_style) == 'show_new')
	print "\t\t\t\t\t\t".'<a href="misc?action=markread">'.$lang_common['Mark all as read'].'</a><br>'."\n";
else
	print "\t\t\t\t\t\t".'&nbsp;'."\n";

?>
					</td>
					<td class="puntopright">
						Powered by <a target="_blank" href="http://www.punbb.org/">PunBB</a><br>
						Modified and migrated by <a target="_blank" href="https://github.com/Axmaw98">Ahmed Kawa</a><br>
						Version: <?php print $options['cur_version'] ?><br>
						&copy; Copyright 2002, 2003 Rickard Andersson
<?php

// Display debug info (if enabled/defined)
if (defined('PUN_DEBUG'))
{
	// Display PHP Accelerator info if enabled
	if (isset($_PHPA) && $_PHPA['ENABLED'] == 1)
		print "\t\t\t\t\t\t".'<br>Accelerated by <a href="http://www.php-accelerator.co.uk/">PHP Accelerator '.$_PHPA['VERSION'].'</a>'."\n";

	// Calculate script generation time
	$time_diff = sprintf('%.3f', get_microtime() - $pun_start);

	print "\t\t\t\t\t\t".'<br>[ <span class="punclosed">Generated in '.$time_diff.' seconds, '.$db->get_num_queries().' queries executed</span> ]'."\n";
}



?>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<?php

$tpl_temp = trim(ob_get_contents());
$tpl_main = str_replace('{pun_footer}', $tpl_temp, $tpl_main);
ob_end_clean();
// END SUBST - {pun_footer}

exit($tpl_main);
// Close the db connection (and free up any result data)
$db->close();
