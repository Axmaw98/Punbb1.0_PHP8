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


//	This script updates the forum database from version 1.0 RC 1 to
//  1.0 RC 2. Copy this file to the forum root directory and run it. Then
//  remove it from the root directory or anyone will be able to run it (NOT
//  good!).

$update_from = '1.0 RC 2';
$update_to = '1.0';


@include 'config.php';

// If config.php doesn't exist, PUN won't be defined
if (!defined('PUN'))
	exit('This file must be run from the forum root directory.');


// Turn off PHP time limit
@set_time_limit(0);


function error($message, $file, $line, $db_error = false)
{
	print '<b>An error was encountered</b><br><br>'."\n".'<b>File:</b> '.$file.'<br>'."\n".'<b>Line:</b> '.$line.'<br><br>'."\n".'<b>PunBB reported</b>: '.$message."\n";

	if ($db_error != false)
		print '<br><b>Database reported:</b> '.htmlspecialchars($db_error['error']).' (Errno: '.$db_error['errno'].')'."\n";

	exit;
}


// Update posts, topics, lastpost, lastpostid and lastposter for a forum (orphaned topics are not included)
function update_forum($forum_id)
{
	global $db;

	$result = $db->query('SELECT COUNT(id), SUM(num_replies) FROM '.$db->prefix.'topics WHERE moved_to IS NULL AND forum_id='.$forum_id) or error('Unable to fetch forum topic count', __FILE__, __LINE__, $db->error());
	list($num_topics, $num_posts) = $db->fetch_row($result);

	$num_posts = $num_posts + $num_topics;		// $num_posts is only the sum of all replies (we have to add the topic posts)

	$result = $db->query('SELECT last_post, last_post_id, last_poster FROM '.$db->prefix.'topics WHERE forum_id='.$forum_id.' AND moved_to IS NULL ORDER BY last_post DESC LIMIT 1') or error('Unable to fetch last_post/last_post_id/last_poster', __FILE__, __LINE__, $db->error());
	if ($db->num_rows($result))		// There are topics in the forum
	{
		list($last_post, $last_post_id, $last_poster) = $db->fetch_row($result);

		$db->query('UPDATE '.$db->prefix.'forums SET num_topics='.$num_topics.', num_posts='.$num_posts.', last_post='.$last_post.', last_post_id='.$last_post_id.', last_poster=\''.addslashes($last_poster).'\' WHERE id='.$forum_id) or error('Unable to update last_post/last_post_id/last_poster', __FILE__, __LINE__, $db->error());
	}
	else	// There are no topics
		$db->query('UPDATE '.$db->prefix.'forums SET num_topics=0, num_posts=0, last_post=NULL, last_post_id=NULL, last_poster=NULL WHERE id='.$forum_id) or error('Unable to update last_post/last_post_id/last_poster', __FILE__, __LINE__, $db->error());
}


// Load DB abstraction layer and try to connect
require 'include/dblayer/commondb.php';


// Check current version
$result = $db->query('SELECT cur_version FROM '.$db->prefix.'options');
if (!$result || $db->result($result, 0) != $update_from)
	error('This script can only update version '.$update_from.'. The database "'.$db_name.'" doesn\'t seem to be running that version. Update process aborted.', __FILE__, __LINE__);



switch ($db_type)
{
	case 'mysql':
		$query = 'ALTER TABLE '.$db->prefix."posts MODIFY poster_id INT(10) UNSIGNED NOT NULL DEFAULT '1'";
		break;

	case 'pgsql':
		$query = 'ALTER TABLE '.$db->prefix."posts ALTER poster_id SET DEFAULT '1'";
		break;
}

$db->query($query) or exit('Error on line: '.__LINE__.'<br>'.$db_type.' reported: '.current($db->error()));


// Move the guest account to ID 1
$result = $db->query('SELECT MAX(id) FROM '.$db->prefix.'users');
$new_id = $db->result($result, 0) + 1;	// Next available ID

$db->query('UPDATE '.$db->prefix.'users SET id='.$new_id.' WHERE id=1') or exit('Error on line: '.__LINE__.'<br>'.$db_type.' reported: '.current($db->error()));
$db->query('UPDATE '.$db->prefix.'posts SET poster_id='.$new_id.' WHERE poster_id=1') or exit('Error on line: '.__LINE__.'<br>'.$db_type.' reported: '.current($db->error()));
$db->query('UPDATE '.$db->prefix.'reports SET reported_by='.$new_id.' WHERE reported_by=1') or exit('Error on line: '.__LINE__.'<br>'.$db_type.' reported: '.current($db->error()));
$db->query('UPDATE '.$db->prefix."users SET id=1 WHERE username='Guest'") or exit('Error on line: '.__LINE__.'<br>'.$db_type.' reported: '.current($db->error()));


// This feels like a good time to update lastpost/lastposter for all forums
$result = $db->query('SELECT id FROM '.$db->prefix.'forums') or exit('Error on line: '.__LINE__.'<br>'.$db_type.' reported: '.current($db->error()));

while ($row = $db->fetch_row($result))
	update_forum($row[0]);


// We'll empty the search results table as well
$db->query('TRUNCATE TABLE '.$db->prefix.'search_results') or exit('Error on line: '.__LINE__.'<br>'.$db_type.' reported: '.current($db->error()));


// Update version information in database
$db->query('UPDATE '.$db->prefix.'options SET cur_version=\''.$update_to.'\'') or exit('Error on line: '.__LINE__.'<br>'.$db_type.' reported: '.current($db->error()));


exit('Update successful! Your forum database has now been updated to version '.$update_to.'. You must now remove this script from the forum root directory!');
