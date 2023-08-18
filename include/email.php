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


// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;


//
// Validate an e-mail address
//
function is_valid_email($email)
{
	return preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/', $email);
}


//
// Check if $email is banned
//
function is_banned_email($email)
{
	global $db, $options;

	$result = $db->query('SELECT email FROM '.$db->prefix.'bans WHERE email IS NOT NULL') or error('Unable to fetch e-mail from ban list', __FILE__, __LINE__, $db->error());
	$num_bans = $db->num_rows($result);

	for ($i = 0; $i < $num_bans; $i++)
	{
		$cur_ban = $db->result($result, $i);

		if (!strcmp($email, $cur_ban) || strpos($cur_ban, '@') === false && stristr($email, "@$cur_ban"))
			return true;
	}

	return false;
}


//
// Wrapper for PHP's mail()
//
/*ini_set('SMTP', 'localhost');
ini_set('smtp_port', '80');
*/
function pun_mail($to, $subject, $message, $headers = '')
{
	global $options;

	if ($options['smtp_host'] != '')
		smtp_mail($to, $subject, $message, $headers);
	else
		mail($to, $subject, $message, $headers);
}


//
// This function was originally a part of the phpBB Group forum software phpBB2 (http://www.phpbb.com).
// They deserve all the credit for writing it. I made small modifications for it to suit PunBB and it's coding standards.
//
function server_parse($socket, $response)
{
	while (substr($server_response, 3, 1) != ' ')
	{
		if (!($server_response = fgets($socket, 256)))
			error('Couldn\'t get mail server response codes. Please contact the forum administrator.', __FILE__, __LINE__);
	}

	if (!(substr($server_response, 0, 3) == $response))
		error('Unable to send e-mail. Please contact the forum administrator with the following error message: "'.$server_response.'"', __FILE__, __LINE__);
}


//
// This function was originally a part of the phpBB Group forum software phpBB2 (http://www.phpbb.com).
// They deserve all the credit for writing it. I made small modifications for it to suit PunBB and it's coding standards.
//
function smtp_mail($to, $subject, $message, $headers = '')
{
	global $options;

	$recipients = explode(',', $to);

	if (!($socket = fsockopen($options['smtp_host'], 25, $errno, $errstr, 15)))
		error('Could not connect to smtp host "'.$options['smtp_host'].'" ('.$errno.') ('.$errstr.')', __FILE__, __LINE__);

	server_parse($socket, '220');

	if ($options['smtp_user'] != '' && $options['smtp_pass'] != '')
	{
		fwrite($socket, 'EHLO ' . $options['smtp_host']."\r\n");
		server_parse($socket, '250');

		fwrite($socket, 'AUTH LOGIN'."\r\n");
		server_parse($socket, '334');

		fwrite($socket, base64_encode($options['smtp_user'])."\r\n");
		server_parse($socket, '334');

		fwrite($socket, base64_encode($options['smtp_pass'])."\r\n");
		server_parse($socket, '235');
	}
	else
	{
		fwrite($socket, 'HELO '.$options['smtp_host']."\r\n");
		server_parse($socket, '250');
	}

	fwrite($socket, 'MAIL FROM: <'.$options['webmaster_email'].'>'."\r\n");
	server_parse($socket, '250');

	$to_header = 'To: ';

	@reset($recipients);
	while (list(, $email) = @each($recipients))
	{
		fwrite($socket, 'RCPT TO: <'.$email.'>'."\r\n");
		server_parse($socket, '250');

		$to_header .= '<'.$email.'>, ';
	}

	fwrite($socket, 'DATA'."\r\n");
	server_parse($socket, '354');

	fwrite($socket, 'Subject: '.$subject."\r\n".$to_header."\r\n".$headers."\r\n\r\n".$message."\r\n");

	fwrite($socket, '.'."\r\n");
	server_parse($socket, '250');

	fwrite($socket, 'QUIT'."\r\n");
	fclose($socket);

	return true;
}
