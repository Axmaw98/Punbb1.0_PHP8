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

if (isset($_GET['action']) && $_GET['action'] == 'markread')
	define('PUN_DONT_UPDATE_COOKIE', 1);

require 'include/common.php';


$action = isset($_GET['action']);


// Load the misc.php language file
require 'lang/'.$language.'/'.$language.'_misc.php';

if ($action == 'rules')
	message($options['rules_message']);


else if ($action == 'markread')
{
	if ($cookie['is_guest'])
		message($lang_common['No permission']);

	$now = time();
	$expire = ($cur_user['save_pass'] == '1') ? $now + 31536000 : 0;

	setcookie('punbb_cookie', serialize(array($cookie['username'], $cookie['password'], $now, $now)), $expire, $cookie_path, $cookie_domain, $cookie_secure);

	redirect('index.php', $lang_misc['Mark read redirect']);
}


if (isset($_GET['report']))
{
	$report = intval($_GET['report']);
	if (empty($report))
		message($lang_common['Bad request']);

	if ($cookie['is_guest'])
		message($lang_common['No permission']);

	if (isset($_POST['form_sent']))
	{
		$reason = str_replace("\r", "\n", str_replace("\r\n", "\n", trim($_POST['req_reason'])));
		if ($reason == '')
			message($lang_misc['No reason']);

		// Get the topic ID
		$result = $db->query('SELECT topic_id FROM '.$db->prefix.'posts WHERE id='.$report) or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
		if (!$db->num_rows($result))
			message($lang_common['Bad request']);

		$topic_id = $db->result($result, 0);

		// Get the subject and forum ID
		$result = $db->query('SELECT subject, forum_id FROM '.$db->prefix.'topics WHERE id='.$topic_id) or error('Unable to fetch topic info', __FILE__, __LINE__, $db->error());
		if (!$db->num_rows($result))
			message($lang_common['Bad request']);

		list($subject, $forum_id) = $db->fetch_row($result);

		// Should we use the internal report handling?
		if ($options['report_method'] == 0 || $options['report_method'] == 2)
			$db->query('INSERT INTO '.$db->prefix.'reports (post_id, topic_id, forum_id, reported_by, created, message) VALUES('.$report.', '.$topic_id.', '.$forum_id.', '.$cur_user['id'].', '.time().', \''.escape($reason).'\')' ) or error('Unable to create report', __FILE__, __LINE__, $db->error());

		// Should we e-mail the report?
		if ($options['report_method'] == 1 || $options['report_method'] == 2)
		{
			// We send it to the complete mailing-list in one swoop
			if ($options['mailing_list'] != '')
			{
				$mail_subject = 'Report('.$forum_id.') - '.$subject;
				$mail_message = $cur_user['username'].' has reported the following message:'."\r\n".$options['base_url'].'/viewtopic.php?pid='.$report.'#'.$report."\r\n\r\n".'Reason:'."\r\n".$reason;
				$mail_extra = 'From: '.$options['board_title'].' Mailer <'.$options['webmaster_email'].'>';

				require 'include/email.php';
				pun_mail($options['mailing_list'], $mail_subject, $mail_message, $mail_extra);
			}
		}

		if ($_POST['redirect_url'] != '')
			redirect($_POST['redirect_url'], $lang_misc['Report redirect']);
		else
			redirect('viewtopic.php?id='.$topic_id, $lang_misc['Report redirect']);
	}


	$page_title = htmlspecialchars($options['board_title']).' / '.$lang_misc['Report post'];
	$validate_form = true;
	$form_name = 'report';
	$focus_element = 'req_reason';
	$dimsubmit = true;
	require 'header.php';

?>
<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>

<form method="post" action="misc.php?report=<?php print $report ?>" id="report" onsubmit="return process_form(this)">
	<input type="hidden" name="form_sent" value="1">
	<input type="hidden" name="redirect_url" value="<?php print $_SERVER["HTTP_REFERER"] ?>">
	<table class="punmain" cellspacing="1" cellpadding="4">
		<tr class="punhead">
			<td class="punhead" colspan="2"><?php print $lang_misc['Report post'] ?></td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><b><?php print $lang_misc['Reason'] ?></b>&nbsp;&nbsp;</td>
			<td class="puncon2">
				<?php print $lang_misc['Reason desc'] ?><br><br>
				&nbsp;<textarea name="req_reason" rows="5" cols="60"></textarea>
			</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_common['Actions'] ?>&nbsp;&nbsp;</td>
			<td class="puncon2"><br>&nbsp;<input type="submit" name="comply" value="<?php print $lang_common['Submit'] ?>" accesskey="s">&nbsp;&nbsp;&nbsp;<a href="javascript:history.go(-1)"><?php print $lang_common['Go back'] ?></a><br><br></td>
		</tr>
	</table>
</form>

<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>
<?php

	require 'footer.php';
}


else if (isset($_GET['subscribe']))
{
	$subscribe = intval($_GET['subscribe']);
	if (empty($subscribe))
		message($lang_common['Bad request']);

	if ($cookie['is_guest'])
		message($lang_common['No permission']);

	$result = $db->query('SELECT subscribers FROM '.$db->prefix.'topics WHERE id='.$subscribe) or error('Unable to fetch topic subscribers', __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result))
		message($lang_common['Bad request']);

	$subscribers = $db->result($result, 0);

	if ($subscribers == '')
		$subscribers = escape($cur_user['email']);
	else
	{
		if (!strstr($subscribers, $cur_user['email']))
			$subscribers .= ','.$cur_user['email'];
		else
			message($lang_misc['Already subscribed']);
	}

	$db->query('UPDATE '.$db->prefix.'topics SET subscribers=\''.$subscribers.'\' WHERE id='.$subscribe) or error('Unable to update topic subscribers', __FILE__, __LINE__, $db->error());

	redirect('viewtopic.php?id='.$subscribe, $lang_misc['Subscribe redirect']);
}


else if (isset($_GET['unsubscribe']))
{
	$unsubscribe = intval($_GET['unsubscribe']);
	if (empty($unsubscribe))
		message($lang_common['Bad request']);

	if ($cookie['is_guest'])
		message($lang_common['No permission']);

	$result = $db->query('SELECT subscribers FROM '.$db->prefix.'topics WHERE id='.$unsubscribe) or error('Unable to fetch topic subscribers', __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result))
		message($lang_common['Bad request']);

	$subscribers = $db->result($result, 0);

	if (strstr($subscribers, $cur_user['email']))
	{
		$addresses = explode(',', $subscribers);
		while (list($key, $value) = @each($addresses))
		{
			if ($value == $cur_user['email'])
				unset($addresses[$key]);
		}

		if (count($addresses))
		{
			$subscribers = implode(',', $addresses);
			$db->query('UPDATE '.$db->prefix.'topics SET subscribers=\''.$subscribers.'\' WHERE id='.$unsubscribe) or error('Unable to update topic subscribers', __FILE__, __LINE__, $db->error());
		}
		else
			$db->query('UPDATE '.$db->prefix.'topics SET subscribers=NULL WHERE id='.$unsubscribe) or error('Unable to update topic subscribers', __FILE__, __LINE__, $db->error());

		redirect('viewtopic.php?id='.$unsubscribe, $lang_misc['Unsubscribe redirect']);
	}
	else
		message($lang_misc['Not subscribed']);
}
else
	message($lang_common['Bad request']);
