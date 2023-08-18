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


// Add a censor word
if (isset($_POST['add_word']))
{
	confirm_referer('admin_censoring.php');

	$search_for = trim($_POST['new_search_for']);
	$replace_with = trim($_POST['new_replace_with']);

	if ($search_for == '' || $replace_with == '')
		message('You must enter both a word to censor and text to replace it with.');

	$db->query('INSERT INTO '.$db->prefix.'censoring (search_for, replace_with) VALUES (\''.escape($search_for).'\', \''.escape($replace_with).'\')') or error('Unable to add censor word', __FILE__, __LINE__, $db->error());

	redirect('admin_censoring.php', 'Censor word added. Redirecting ...');
}


// Update a censor word
else if (isset($_POST['update']))
{
	confirm_referer('admin_censoring.php');

	$id = key($_POST['update']);

	$search_for = trim($_POST['search_for'][$id]);
	$replace_with = trim($_POST['replace_with'][$id]);

	if ($search_for == '' || $replace_with == '')
		message('You must enter both text to search for and text to replace with.');

	$db->query('UPDATE '.$db->prefix.'censoring SET search_for=\''.escape($search_for).'\', replace_with=\''.escape($replace_with).'\' WHERE id='.$id) or error('Unable to update censor word', __FILE__, __LINE__, $db->error());

	redirect('admin_censoring.php', 'Censor word updated. Redirecting ...');
}


// Remove a censor word
else if (isset($_POST['remove']))
{
	confirm_referer('admin_censoring.php');

	$id = key($_POST['remove']);

	$db->query('DELETE FROM '.$db->prefix.'censoring WHERE id='.$id) or error('Unable to delete censor word', __FILE__, __LINE__, $db->error());

	redirect('admin_censoring.php', 'Censor word removed. Redirecting ...');
}


$page_title = htmlspecialchars($options['board_title']).' / Admin / Censoring';
$form_name = 'censoring';
$focus_element = 'new_search_for';
require 'header.php';

if ($cur_user['status'] > 1)
	admin_menu('censoring');
else
	moderator_menu('censoring');

?>
<form method="post" action="admin_censoring.php?action=foo" id="censoring">
	<table class="punmain" cellspacing="1" cellpadding="4">
		<tr class="punhead">
			<td class="punhead" colspan="2">Censoring</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap">Add word&nbsp;&nbsp;</td>
			<td class="puncon2">
				<table class="punplain" cellpadding="6">
					<tr>
						<td colspan="3">Enter a word that you want to censor and the replacement text for this word. Wildcards are accepted (i.e. *some* would match somewhere and lonesome). Censor words also affect usernames. New users will not be able to register with usernames containing any censored words. The search is case insensitive. <b>Censor words must be enabled in <a href="admin_options.php#censoring">Options</a> for this to have any effect.</b><br><br></td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Word</b><br>The word to censor.</td>
						<td style="width: 35%"><input type="text" name="new_search_for" size="35" maxlength="60" tabindex="1"></td>
						<td style="width: 30%" rowspan="2"><input type="submit" name="add_word" value=" Add " tabindex="3"></td>
					</tr>
					<tr>
						<td class="punright" style="width: 35%"><b>Replacement</b><br>The text to replace the matching censored word with.</td>
						<td style="width: 35%"><input type="text" name="new_replace_with" size="35" maxlength="60" tabindex="2"></td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap">Edit/remove words&nbsp;&nbsp;</td>
			<td class="puncon2">
				<table class="punplain" cellpadding="6">
					<tr>
						<td>
<?php

$result = $db->query('SELECT id, search_for, replace_with FROM '.$db->prefix.'censoring ORDER BY id') or error('Unable to fetch censor word list', __FILE__, __LINE__, $db->error());
if ($db->num_rows($result))
{
	while ($cur_word = $db->fetch_assoc($result))
		print "\t\t\t\t\t\t\t".'&nbsp;&nbsp;&nbsp;Word&nbsp;&nbsp;<input type="text" name="search_for['.$cur_word['id'].']" value="'.$cur_word['search_for'].'" size="25" maxlength="60">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Replacement&nbsp;&nbsp;<input type="text" name="replace_with['.$cur_word['id'].']" value="'.$cur_word['replace_with'].'" size="25" maxlength="60">&nbsp;&nbsp&nbsp&nbsp;&nbsp;<input type="submit" name="update['.$cur_word['id'].']" value="Update">&nbsp;<input type="submit" name="remove['.$cur_word['id'].']" value="Remove"><br>'."\n";
}
else
	print "\t\t\t\t\t\t\t".'No censor words in list.'."\n";

?>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</form>

<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>
<?php

require 'footer.php';