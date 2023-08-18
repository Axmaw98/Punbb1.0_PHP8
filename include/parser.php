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
// Split the message into tokens ($inside contains all text inside $start and $end, and $outside contains all text outside)
//
function split_text($text, $start, $end)
{
	global $options;

	$tokens = explode($start, $text);

	$outside[] = $tokens[0];

	$num_tokens = count($tokens);
	for ($i = 1; $i < $num_tokens; $i++)
	{
		$temp = explode($end, $tokens[$i]);
		$inside[] = $temp[0];
		$outside[] = $temp[1];
	}

	if ($options['indent_num_spaces'] != 8 && $start == '[code]')
	{
		$spaces = str_repeat(' ', $options['indent_num_spaces']);
		$inside = str_replace("\t", $spaces, $inside);
	}

	return array($inside, $outside);
}


//
// Parse text and make sure that [code] and [quote] syntax is correct
//
function check_tag_order($text)
{
	global $lang_common;

	// The maximum allowed quote depth
	$max_depth = 3;
	$q_depth = 0;
	while (true)
	{
		$c_start = strpos($text, '[code]');
		$c_end = strpos($text, '[/code]');
		$q_start = strpos($text, '[quote]');
		$q_end = strpos($text, '[/quote]');

		// Deal with strpos() returning false when the string is not found
		if ($c_start === false) $c_start = 65536;
		if ($c_end === false) $c_end = 65536;
		if ($q_start === false) $q_start = 65536;
		if ($q_end === false) $q_end = 65536;

		// If none of the strings were found
		if (min($c_start, $c_end, $q_start, $q_end) == 65536)
			break;

		// We found a [quote]
		if ($q_start < min($q_end, $c_start, $c_end))
		{
			$cur_index = 0;
			$cur_index += $q_start+7;

			// Did we reach $max_depth?
			if ($q_depth == $max_depth)
				$overflow_begin = ($cur_index-7);

			$q_depth++;
			$text = substr($text, $q_start+7);
		}

		// We found a [/quote]
		else if ($q_end < min($q_start, $c_start, $c_end))
		{
			if ($q_depth == 0)
				message($lang_common['BBCode error'].' '.$lang_common['BBCode error 1']);

			$q_depth--;
			$cur_index += $q_end+8;

			// Did we reach $max_depth?
			if ($q_depth == $max_depth)
				$overflow_end = $cur_index;

			$text = substr($text, $q_end+8);
		}

		// We found a [code]
		else if ($c_start < min($c_end, $q_start, $q_end))
		{
			$tmp = strpos($text, '[/code]');
			if ($tmp === false)
				message($lang_common['BBCode error'].' '.$lang_common['BBCode error 2']);
			else
				$text = substr($text, $tmp+7);

			$cur_index += $tmp+7;
		}

		// We found a [/code] (this shouldn't happen since we handle both start and end tag in the if clause above)
		else if ($c_end < min($c_start, $q_start, $q_end))
			message($lang_common['BBCode error'].' '.$lang_common['BBCode error 3']);
	}

	// If $q_depth <> 0 something is wrong with the quote syntax
	if ($q_depth > 0)
		message($lang_common['BBCode error'].' '.$lang_common['BBCode error 4']);
	else if ($q_depth < 0)
		message($lang_common['BBCode error'].' '.$lang_common['BBCode error 5']);

	// If the quote depth level was higher than $max_depth we return the index for the
	// beginning and end of the part we should strip out
	if (isset($overflow_begin))
		return array($overflow_begin, $overflow_end);
	else
		return null;
}


//
// Truncate URL if longer than 55 characters (add http:// or ftp:// if missing)
//
function truncate_url($url, $link = '')
{
	global $cur_user;

	$full_url = $url;
	if (strpos($url, 'www.') === 0)
		$full_url = 'http://'.$full_url;
	else if (strpos($url, 'ftp.') === 0)
		$full_url = 'ftp://'.$full_url;

	// Ok, not very pretty :-)
	$link = ($link == '' || $link == $url) ? ((strlen($url) > 55) ? substr($url, 0 , 39).' ... '.substr($url, -10) : $url) : stripslashes($link);

	if ($cur_user['link_to_new_win'] == '0')
		return '<a href="'.$full_url.'">'.$link.'</a>';
	else
		return '<a href="'.$full_url.'" target="_blank">'.$link.'</a>';
}


//
// Convert BBCodes to their HTML equivalent
//
function do_bbcode($message)
{
	global $cur_user;

	if (strpos($message, '[') !== false && strpos($message, ']') !== false)
	{
		$pattern = array("#\[b\](.*?)\[/b\]#s",
						 "#\[i\](.*?)\[/i\]#s",
						 "#\[u\](.*?)\[/u\]#s",
						 "#\[url\](.*?)\[/url\]#ie",
						 "#\[url=(.*?)\](.*?)\[/url\]#ie",
						 "#\[email\](.*?)\[/email\]#i",
						 "#\[email=(.*?)\](.*?)\[/email\]#i",
						 "#\[color=([a-zA-Z]*|\#?[0-9a-fA-F]{6})](.*?)\[/color\]#s");

		$replace = array('<b>$1</b>',
						 '<i>$1</i>',
						 '<u>$1</u>',
						 'truncate_url("$1")',
						 'truncate_url("$1", "$2")',
						 '<a href="mailto:$1">$1</a>',
						 '<a href="mailto:$1">$2</a>',
						 '<span style="color: $1">$2</span>');

		// Run this big regex replacement
		/*$message = preg_replace($pattern, $replace, $message);*/

		if (strpos($message, 'quote]') !== false)
		{
			$message = str_replace('[quote]', '<br></span><table style="width: 95%" align="center" cellspacing="4" cellpadding="6"><tr><td class="punquote"><span class="puntext">', $message);
			$message = str_replace('[/quote]', '</span></td></tr></table><span class="puntext"><br>', $message);
		}
	}

	return $message;
}


//
// Make hyperlinks clickable
//
function do_clickable($message)
{
	global $cur_user;

	$message = ' '.$message;


	$message = preg_replace_callback(
	    '#([\t\n ])([a-z0-9]+?){1}://([\w\-]+\.([\w\-]+\.)*[\w]+(:[0-9]+)?(/[^ \"\n\r\t<]*)?)#i',
	    function($matches) {
	        return $matches[1] . truncate_url($matches[2] . '://' . $matches[3]);
	    },
	    $message
	);

	$message = preg_replace_callback(
	    '#([\t\n ])(www|ftp)\.(([\w\-]+\.)*[\w]+(:[0-9]+)?(/[^ \"\n\r\t<]*)?)#i',
	    function($matches) {
	        return $matches[1] . truncate_url($matches[2] . '.' . $matches[3], $matches[2] . '.' . $matches[3]);
	    },
	    $message
	);
	return substr($message, 1);
}


//
// Convert a series of smilies to images
//
function do_smilies($message)
{
	// Here you can add additional smilies if you like (please note that you must escape singlequote and backslash)
	$text = array(':)', '=)', ':(', '=(', ':D', '=D', ';)', ':x', ':rolleyes:');
	$img = array('smile.png', 'smile.png', 'sad.png', 'sad.png', 'big_smile.png', 'big_smile.png', 'wink.png', 'mad.png', 'roll.png');

	// Uncomment the next row if you add smilies that contain any of the characters &"'<>
//	$text = array_map('htmlspecialchars', $text);

	$message = ' '.$message.' ';

	$num_smilies = count($text);
	for ($i = 0; $i < $num_smilies; $i++)
		$message = preg_replace("#(?<=.\W|\W.|^\W)".preg_quote($text[$i], '#')."(?=.\W|\W.|\W$)#m", '$1<img src="img/smilies/'.$img[$i].'" width="15" height="15" alt="'.$text[$i].'">$2', $message);

	return substr($message, 1, -1);
}


//
// Parse message text
//
function parse_message($message, $smilies)
{
	global $cur_user, $permissions, $options;

	// Deal with some possible "exploits"
	$message = preg_replace("#javascript:#i", '_javascript_:', $message);
	$message = preg_replace("#about:#i", '_about_:', $message);

	if ($options['censoring'] == '1')
		$message = censor_words($message);

	if ($permissions['message_html'] == '0')
		$message = htmlspecialchars($message);

	// If the message contains a code tag we have to split it up (text within [code][/code] shouldn't be touched)
	if (strpos($message, '[code]') !== false && strpos($message, '[/code]') !== false)
	{
		list($inside, $outside) = split_text($message, '[code]', '[/code]');
		$outside = array_map('trim', $outside);
		$message = implode('<">', $outside);
	}

	if ($options['make_links'] == '1')
		$message = do_clickable($message);

	if ($smilies == '1' && $options['smilies'] == '1' && isset($cur_user['show_img']) != '0')
		$message = do_smilies($message);

	if ($permissions['message_bbcode'] == '1')
	{
		$message = do_bbcode($message);

		if ($permissions['message_img_tag'] == '1')
		{
			if (isset($cur_user['show_img']) != '0')
				$message = preg_replace('#\[img\](.*?)\[/img\]#s', '<img src="$1" border="0" align="top" alt="">', $message);
			else
			{
				if (isset($cur_user['link_to_new_win']) == '0')
					$message = preg_replace('#\[img\](.*?)\[/img\]#s', '<a href="$1">&lt;image&gt;</a>', $message);
				else
					$message = preg_replace('#\[img\](.*?)\[/img\]#s', '<a href="$1" target="_blank">&lt;image&gt;</a>', $message);
			}
		}
	}

	// Deal with newlines, tabs and multiple spaces
	$pattern = array("\n", "\t", '  ', '  ');
	$replace = array('<br>', '&nbsp; &nbsp; ', '&nbsp; ', ' &nbsp;');
	$message = str_replace($pattern, $replace, $message);

	// If we split up the message before we have to concatenate it together again (code tags)
	if (isset($inside))
	{
		$outside = explode('<">', $message);
		$message = '';

		$num_tokens = count($outside);

		for ($i = 0; $i < $num_tokens; $i++)
		{
			$message .= $outside[$i];
			if ($inside[$i])
				$message .= '<br><br></span><table style="width: 95%" align="center" cellspacing="4" cellpadding="6"><tr><td class="punquote"><span class="puntext"><b>code:</b></span><br><br><pre>'.trim($inside[$i]).'</pre></td></tr></table><span class="puntext"><br>';
		}
	}

	return $message;
}


//
// Parse signature text
//
function parse_signature($message)
{
	global $cur_user, $permissions, $options;

	// Deal with some possible "exploits"
	$message = preg_replace('/javascript:/i', '_javascript_:', $message);
	$message = preg_replace('/about:/i', '_about_:', $message);

	if ($options['censoring'] == '1')
		$message = censor_words($message);

	if ($permissions['sig_html'] == '0')
		$message = htmlspecialchars($message);

	if ($options['make_links'] == '1')
		$message = do_clickable($message);

	if ($options['smilies_sig'] == '1' && $cur_user['show_img'] != '0')
		$message = do_smilies($message);

	if ($permissions['sig_bbcode'] == '1')
	{
		$message = do_bbcode($message);

		if ($permissions['sig_img_tag'] == '1')
		{
			if ($cur_user['show_img'] != '0')
				$message = preg_replace('#\[img\](.*?)\[/img\]#s', '<img src="$1" border="0" align="top" alt="">', $message);
			else
			{
				if ($cur_user['link_to_new_win'] == '0')
					$message = preg_replace('#\[img\](.*?)\[/img\]#s', '<a href="$1">&lt;image&gt;</a>', $message);
				else
					$message = preg_replace('#\[img\](.*?)\[/img\]#s', '<a href="$1" target="_blank">&lt;image&gt;</a>', $message);
			}
		}
	}

	// Deal with newlines, tabs and multiple spaces
	$pattern = array("\n", "\t", '  ', '  ');
	$replace = array('<br>', '&nbsp; &nbsp; ', '&nbsp; ', ' &nbsp;');
	$message = str_replace($pattern, $replace, $message);

	return $message;
}
