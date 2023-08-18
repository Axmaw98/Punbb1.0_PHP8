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


// Load the help.php language file
require 'lang/'.$language.'/'.$language.'_help.php';

// Determine what style to use (for the [img] example)
if ($cur_user['style'] != '' && @file_exists('style/'.$cur_user['style'].'.css'))
	$img_url = $options['base_url'].'/img/'.$cur_user['style'].'_new.png';
else
	$img_url = $options['base_url'].'/img/'.$options['default_style'].'_new.png';


$page_title = htmlspecialchars($options['board_title']).' / '.$lang_help['Help'];
require 'header.php';

?>
<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>

<table class="punmain" cellspacing="1" cellpadding="4">
	<tr class="punhead">
		<td class="punhead" colspan="2"><?php print $lang_help['Help'] ?></td>
	</tr>
	<tr>
		<td class="puncon1right" style="width: 140px; white-space: nowrap"><b><?php print $lang_help['BBCode'] ?></b>&nbsp;&nbsp;</td>
		<td class="puncon2">
			<div style="padding-left: 4px">
				<?php print $lang_help['BBCode info 1'] ?><br><br>
				<?php print $lang_help['BBCode info 2'] ?><br><br>
			</div>
		</td>
	</tr>
	<tr>
		<td class="puncon1right" style="width: 140px; white-space: nowrap"><b><?php print $lang_help['Text style'] ?></b>&nbsp;&nbsp;</td>
		<td class="puncon2">
			<div style="padding-left: 4px">
				<?php print $lang_help['Text style info'] ?><br><br>
				&nbsp;&nbsp;&nbsp;&nbsp;[b]<?php print $lang_help['Bold text'] ?>[/b] <?php print $lang_help['produces'] ?> <b><?php print $lang_help['Bold text'] ?></b><br>
				&nbsp;&nbsp;&nbsp;&nbsp;[u]<?php print $lang_help['Underlined text'] ?>[/u] <?php print $lang_help['produces'] ?> <u><?php print $lang_help['Underlined text'] ?></u><br>
				&nbsp;&nbsp;&nbsp;&nbsp;[i]<?php print $lang_help['Italic text'] ?>[/i] <?php print $lang_help['produces'] ?> <i><?php print $lang_help['Italic text'] ?></i><br>
				&nbsp;&nbsp;&nbsp;&nbsp;[color=#FF0000]<?php print $lang_help['Red text'] ?>[/color] <?php print $lang_help['produces'] ?> <span style="color: #ff0000"><?php print $lang_help['Red text'] ?></span><br><br>
			</div>
		</td>
	</tr>
	<tr>
		<td class="puncon1right" style="width: 140px; white-space: nowrap"><b><?php print $lang_help['Links and images'] ?></b>&nbsp;&nbsp;</td>
		<td class="puncon2">
			<div style="padding-left: 4px">
				<?php print $lang_help['Links info'] ?><br><br>
				&nbsp;&nbsp;&nbsp;&nbsp;[url=<?php print $options['base_url'] ?>]<?php print htmlspecialchars($options['board_title']) ?>[/url] <?php print $lang_help['produces'] ?> <a href="<?php print $options['base_url'] ?>"><?php print htmlspecialchars($options['board_title']) ?></a><br>
				&nbsp;&nbsp;&nbsp;&nbsp;[url]<?php print $options['base_url'] ?>[/url] <?php print $lang_help['produces'] ?> <a href="<?php print $options['base_url'] ?>"><?php print $options['base_url'] ?></a><br>
				&nbsp;&nbsp;&nbsp;&nbsp;[email]myname@mydomain.com[/email] <?php print $lang_help['produces'] ?> <a href="mailto:myname@mydomain.com">myname@mydomain.com</a><br>
				&nbsp;&nbsp;&nbsp;&nbsp;[email=myname@mydomain.com]<?php print $lang_help['My e-mail address'] ?>[/email] <?php print $lang_help['produces'] ?> <a href="mailto:myname@mydomain.com"><?php print $lang_help['My e-mail address'] ?></a><br><br>
				<?php print $lang_help['Images info'] ?><br><br>
				&nbsp;&nbsp;&nbsp;&nbsp;[img]<?php print $img_url ?>[/img] <?php print $lang_help['produces'] ?> <img src="<?php print $img_url ?>" border="0" align="top" alt=""><br><br>
			</div>
		</td>
	</tr>
	<tr>
		<td class="puncon1right" style="width: 140px; white-space: nowrap"><b><?php print $lang_help['Quotes and code'] ?></b>&nbsp;&nbsp;</td>
		<td class="puncon2">
			<div style="padding-left: 4px">
				<?php print $lang_help['Quotes info'] ?><br><br>
				&nbsp;&nbsp;&nbsp;&nbsp;[quote]<?php print $lang_help['Quote text'] ?>[/quote]<br><br>
				<?php print $lang_help['produces quote box'] ?><br><br>
				<table style="width: 95%" align="center" cellspacing="4" cellpadding="6"><tr><td class="punquote"><span class="puntext"><?php print $lang_help['Quote text'] ?></span></td></tr></table><br>
				<?php print $lang_help['Code info'] ?><br><br>
				&nbsp;&nbsp;&nbsp;&nbsp;[code]<?php print $lang_help['Code text'] ?>[/code]<br><br>
				<?php print $lang_help['produces code box'] ?><br><br>
				<table style="width: 95%" align="center" cellspacing="4" cellpadding="6"><tr><td class="punquote"><span class="puntext"><b>code:</b></span><br><br><pre><?php print $lang_help['Code text'] ?></pre></td></tr></table><br>
			</div>
		</td>
	</tr>
	<tr>
		<td class="puncon1right" style="width: 140px; white-space: nowrap"><b><?php print $lang_help['Nested tags'] ?></b>&nbsp;&nbsp;</td>
		<td class="puncon2">
			<div style="padding-left: 4px">
				<?php print $lang_help['Nested tags info'] ?><br><br>
				&nbsp;&nbsp;&nbsp;&nbsp;[b][u]<?php print $lang_help['Bold, underlined text'] ?>[/u][/b] <?php print $lang_help['produces'] ?> <u><b><?php print $lang_help['Bold, underlined text'] ?></b></u><br><br>
			</div>
		</td>
	</tr>
	<tr>
		<td class="puncon1right" style="width: 140px; white-space: nowrap"><b><?php print $lang_help['Smilies'] ?></b>&nbsp;&nbsp;</td>
		<td class="puncon2">
			<div style="padding-left: 4px">
				<?php print $lang_help['Smilies info'] ?><br><br>
				&nbsp;&nbsp;&nbsp;&nbsp;:) <?php print $lang_common['and'] ?> =) <?php print $lang_help['produces'] ?> <img src="img/smilies/smile.png" width="15" height="15" alt=""><br>
				&nbsp;&nbsp;&nbsp;&nbsp;:( <?php print $lang_common['and'] ?> =( <?php print $lang_help['produces'] ?> <img src="img/smilies/sad.png" width="15" height="15" alt=""><br>
				&nbsp;&nbsp;&nbsp;&nbsp;:D <?php print $lang_common['and'] ?> =D <?php print $lang_help['produces'] ?> <img src="img/smilies/big_smile.png" width="15" height="15" alt=""><br>
				&nbsp;&nbsp;&nbsp;&nbsp;;) <?php print $lang_help['produces'] ?> <img src="img/smilies/wink.png" width="15" height="15" alt=""><br>
				&nbsp;&nbsp;&nbsp;&nbsp;:x <?php print $lang_help['produces'] ?> <img src="img/smilies/mad.png" width="15" height="15" alt=""><br>
				&nbsp;&nbsp;&nbsp;&nbsp;:rolleyes: <?php print $lang_help['produces'] ?> <img src="img/smilies/roll.png" width="15" height="15" alt=""><br><br>
			</div>
		</td>
	</tr>
</table>

<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>
<?php

require 'footer.php';
