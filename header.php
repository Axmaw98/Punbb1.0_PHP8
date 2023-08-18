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

  You should have received a copy of the GNU G>eneral Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place, Suite 330, Boston,
  MA  02111-1307  USA

************************************************************************/


// Workaround for "current" Apache 2 + PHP module which seems to not
// cope with private cache control setting (from phpBB2)
if (strpos($_SERVER['SERVER_SOFTWARE'], 'Apache/2') !== 0)
	header('Cache-Control: no-cache, pre-check=0, post-check=0, max-age=0');
else
	header('Cache-Control: private, pre-check=0, post-check=0, max-age=0');

header('Expires: '.gmdate('D, d M Y H:i:s').' GMT');
header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');


// Load the main template
$fp = fopen('include/template/main.tpl', 'r');
$tpl_main = trim(fread($fp, filesize('include/template/main.tpl')));
fclose($fp);


// START SUBST - {pun_content_direction}
$tpl_main = str_replace('{pun_content_direction}', $lang_common['lang_direction'], $tpl_main);
// END SUBST - {pun_content_direction}


// START SUBST - {pun_char_encoding}
$tpl_main = str_replace('{pun_char_encoding}', $lang_common['lang_encoding'], $tpl_main);
// END SUBST - {pun_char_encoding}


// START SUBST - {pun_head}
ob_start();

if (isset($destination))
	print '<meta http-equiv="refresh" content="'.$delay.';URL='.$destination.'">'."\n";
else
{
	if ((isset($form_name) && isset($focus_element)) || isset($validate_form))
	{
		// Output javascript(s)
		// With a quick and dirty hack to not disable submit buttons if user agent is Opera (since Opera
		// refused to re-enable the button if we submit and then go back to this page)

?>
<script type="text/javascript">
<!--
<?php if ($validate_form): ?>function process_form(theform)
{
	// Check for required elements
	if (document.images) {
		for (i = 0; i < theform.length; i++) {
			if (theform.elements[i].name.substring(0, 4) == "req_") {
				if ((theform.elements[i].type=="text" || theform.elements[i].type=="textarea" || theform.elements[i].type=="password" || theform.elements[i].type=="file") && theform.elements[i].value=='') {
					alert(theform.elements[i].name.substring(4, 30) + " <?php print $lang_common['required field'] ?>")
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
<?php endif; ?>// -->
</script>
<?php

	}
}

$style = (isset($cur_user)) ? $cur_user['style'] : $options['default_style'];

?>
<title><?php print $page_title ?></title>
<link rel="stylesheet" type="text/css" href="style/<?php print $style.'.css' ?>">
<?php

$tpl_temp = trim(ob_get_contents());
$tpl_main = str_replace('{pun_head}', $tpl_temp, $tpl_main);
ob_end_clean();
// END SUBST - {pun_head}


// START SUBST - {pun_body}
ob_start();

if (isset($form_name) && isset($focus_element))
	print ' onLoad="document.getElementById(\''.$form_name.'\').'.$focus_element.'.focus()"';

$tpl_temp = ob_get_contents();
$tpl_main = str_replace('{pun_body}', $tpl_temp, $tpl_main);
ob_end_clean();
// END SUBST - {pun_body}


// START SUBST - {pun_title}
$tpl_main = str_replace('{pun_title}', htmlspecialchars($options['board_title']), $tpl_main);
// END SUBST - {pun_title}


// START SUBST - {pun_desc}
$tpl_main = str_replace('{pun_desc}', $options['board_desc'], $tpl_main);
// END SUBST - {pun_desc}


// START SUBST - {pun_navlinks}
$tpl_main = str_replace('{pun_navlinks}', generate_navlinks(), $tpl_main);
// END SUBST - {pun_navlinks}


// START SUBST - {pun_status}
if ($cookie['is_guest'])
	$tpl_temp = $lang_common['Not logged in'];
else
	$tpl_temp = $lang_common['Logged in as'].' <b>'.htmlspecialchars($cur_user['username']).'</b>.<br>'.$lang_common['Last visit'].': '.format_time($cookie['last_timeout']);

if (isset($cur_user['status']) > 0)
{
	$result_header = $db->query('SELECT COUNT(id) FROM '.$db->prefix.'reports WHERE zapped IS NULL') or error('Unable to fetch reports info', __FILE__, __LINE__, $db->error());

	if ($db->result($result_header, 0))
		$tpl_temp .= '<br><a class="punhot" href="admin_reports.php">There are new reports</a>';

	if ($options['maintenance'] == '1')
		$tpl_temp .= '<br><a class="punhot" href="admin_options.php#maintenance"><b>Maintenance mode is enabled!</b></a>';
}

$tpl_main = str_replace('{pun_status}', $tpl_temp, $tpl_main);
// END SUBST - {pun_status}


// START SUBST - {pun_main}
ob_start();


define('PUN_HEADER', 1);
