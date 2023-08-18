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


// The contents of this file are very much inspired by the file search.php
// from the phpBB Group forum software phpBB2 (http://www.phpbb.com).


require 'config.php';
require 'include/common.php';


// Load the search.php language file
require 'lang/'.$language.'/'.$language.'_search.php';


if (!$cookie['is_guest'])
{
	$disp_topics = $cur_user['disp_topics'];
	$disp_posts = $cur_user['disp_posts'];
}
else
{
	if ($permissions['guests_read'] == '0')
		message($lang_common['Login required'].' <a href="login.php">'.$lang_common['Login'].'</a> '.$lang_common['or'].' <a href="register.php">'.$lang_common['register'].'</a>.');
	else if ($permissions['guests_search'] == '0')
		message($lang_search['No guest search']);

	$disp_topics = $options['disp_topics_default'];
	$disp_posts = $options['disp_posts_default'];
}


// Figure out what to do :-)
if (isset($_POST['action']) || isset($_GET['action']) || isset($_GET['search_id']))
{
	$action = (isset($_POST['action'])) ? $_POST['action'] : ((isset($_GET['action'])) ? $_GET['action'] : null);
	$forum = (isset($_POST['forum'])) ? intval($_POST['forum']) : -1;
	$sort_dir = (isset($_POST['sort_dir'])) ? (($_POST['sort_dir'] == 'DESC') ? 'DESC' : 'ASC') : 'DESC';

	// If a search_id was supplied
	if (isset($_GET['search_id']))
	{
		$search_id = intval($_GET['search_id']);
		if (empty($search_id) || $search_id < 0)
			message($lang_common['Bad request']);
	}
	// If it's a regular search (keywords and/or author)
	else if ($action == 'search')
	{
		$keywords = (isset($_POST['keywords'])) ? trim($_POST['keywords']) : ((isset($_GET['keywords'])) ? trim($_GET['keywords']) : null);
		$author = (isset($_POST['author'])) ? trim($_POST['author']) : ((isset($_GET['author'])) ? trim($_GET['author']) : null);

		if ((!$keywords && !$author))
			message($lang_search['No terms']);

		if ($author)
			$author = str_replace('*', '%', $author);

		$show_as = (isset($_POST['show_as'])) ? $_POST['show_as'] : ((isset($_GET['show_as'])) ? $_GET['show_as'] : 'posts');
		$sort_by = (isset($_POST['sort_by'])) ? intval($_POST['sort_by']) : null;
		$search_in = (!isset($_POST['search_in']) || $_POST['search_in'] == 'all') ? 0 : (($_POST['search_in'] == 'message') ? 1 : -1);
	}
	// If it's a user search (by id)
	else if ($action == 'show_user')
	{
		$user_id = intval($_GET['user_id']);
		if ($user_id < 2)
			message($lang_common['Bad request']);
	}
	else
	{
		if ($action != 'show_new' && $action != 'show_unanswered')
			message($lang_common['Bad request']);
	}


	// Fetch the list of forums
	$result = $db->query('SELECT id, forum_name, admmod_only FROM '.$db->prefix.'forums') or error('Unable to fetch forum list', __FILE__, __LINE__, $db->error());
	$num_forums = $db->num_rows($result);

	// Build two arrays with foruminfo
	$admmod_forums = array();
	for ($i = 0; $i < $num_forums; $i++)
	{
		$forum_list[$i] = $db->fetch_row($result);
		if ($forum_list[$i][2] == '1')
			$admmod_forums[$i] = $forum_list[$i][0];	// $admmod_forums contains the ID's of admin/mod only forums
	}


	// If a valid search_id was supplied we attempt to fetch the search results from the db
	if (isset($search_id))
	{
		if ($cookie['is_guest'])
			$ident = get_remote_address();
		else
			$ident = addslashes($cookie['username']);

		$result = $db->query('SELECT search_data FROM '.$db->prefix.'search_results WHERE id='.$search_id.' AND ident=\''.$ident.'\'') or error('Unable to fetch search results', __FILE__, __LINE__, $db->error());
		if ($row = $db->fetch_assoc($result))
		{
			$temp = unserialize($row['search_data']);

			$search_results = $temp['search_results'];
			$num_hits = $temp['num_hits'];
			$sort_by = $temp['sort_by'];
			$sort_dir = $temp['sort_dir'];
			$show_as = $temp['show_as'];

			unset($temp);
		}
		else
			message($lang_search['No hits']);
	}
	else
	{
		$keyword_results = $author_results = array();

		// Search a specific forum?
		if ($forum != -1)
		{
			if (in_array($forum, $admmod_forums) && $cur_user['status'] < 1)
				message($lang_search['No hits']);

			$forum_sql = 't.forum_id = '.$forum;
		}
		else
		{
			if (empty($admmod_forums) || $cur_user['status'] > 0)
				$forum_sql = '';
			else
				$forum_sql = 't.forum_id NOT IN('.implode(',', $admmod_forums).')';
		}


		if (isset($author) || isset($keywords))
		{
			// If it's a search for keywords
			if ($keywords)
			{
				$stopwords = @file('lang/'.$language.'/'.$language.'_stopwords.txt');
				$keywords = ' '.strtolower($keywords).' ';

				// Locate some common search operators
				$operator_match = array('+', '-', '&&', '||');
				$operator_replace = array(' and ', ' not ', ' and ', ' or ');
				$keywords = str_replace($operator_match, $operator_replace, $keywords);

				// Filter out non-alphabetical chars
				$noise_match = array('^', '$', '&', '(', ')', '<', '>', '`', '\'', '"', '|', ',', '@', '_', '?', '%', '~', '.', '[', ']', '{', '}', ':', '\\', '/', '=', '#', '\'', ';', '!', '�');
				$noise_replace = array(' ', ' ', ' ', ' ', ' ', ' ', ' ', '',  '',   ' ', ' ', ' ', ' ', '',  ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', '' ,  ' ', ' ', ' ', ' ',  ' ', ' ', ' ');
				$keywords = str_replace($noise_match, $noise_replace, $keywords);

				// Filter out stopwords
				if (!empty($stopwords))
				{
					foreach ($stopwords as $word)
					{
						$word = trim($word);
						if ($word != 'and' || $word != 'or' || $word != 'not')
							$text = preg_replace('#\b'.preg_quote($word).'\b#', ' ', isset($text));
					}
				}

				// Split up keywords
				$keywords_array = preg_split('#[\s]+#', substr($keywords, 1, -1));


				// Should we search in message body or topic subject specifically?
				if ($search_in)
					$search_in_cond = ($search_in > 0) ? 'AND m.subject_match = 0' : 'AND m.subject_match = 1';

				$match_type = 'or';
				foreach ($keywords_array as $cur_word)
				{
					switch ($cur_word)
					{
						case 'and':
						case 'or':
						case 'not':
							$match_type = $cur_word;
							break;

						default:
						{
							$match_word = str_replace('*', '%', $cur_word);

							$sql = 'SELECT m.post_id FROM '.$db->prefix.'search_words AS w INNER JOIN '.$db->prefix.'search_matches AS m ON m.word_id = w.id WHERE w.word LIKE \''.$match_word.'\''.isset($search_in_cond);

							$result = $db->query($sql) or error('Unable to search for posts', __FILE__, __LINE__, $db->error());

							$row = array();
							$result_list = null;
							while ($temp = $db->fetch_row($result))
							{
								$row[$temp[0]] = 1;

								if (!isset($word_count))
									$result_list[$temp[0]] = 1;
								else if ( $match_type == 'or')
									$result_list[$temp[0]] = 1;
								else if ( $match_type == 'not')
									$result_list[$temp[0]] = 0;
							}

							if ($match_type === 'and' && $word_count > 0) {
    foreach ($result_list as $post_id => $value) {
        if (empty($row[$post_id])) {
            $result_list[$post_id] = 0;
        }
    }
}

							$word_count = 0;
							$word_count++;
							$db->free_result($result);

							break;
						}
					}
				}

				if ($result_list !== null) {
    @reset($result_list);
    foreach ($result_list as $post_id => $matches) {
        if ($matches) {
            $keyword_results[] = $post_id;
        }
    }
    unset($result_list);
}
}

			// If it's a search for author name (and that author name isn't Guest)
			if ($author && strcasecmp($author, 'Guest') && strcasecmp($author, $lang_common['Guest']))
			{
				switch ($db_type)
				{
					case 'mysql':
						$result = $db->query('SELECT id FROM '.$db->prefix.'users WHERE username LIKE \''.escape($author).'\'') or error('Unable to fetch users', __FILE__, __LINE__, $db->error());
						break;

					case 'pgsql':
						$result = $db->query('SELECT id FROM '.$db->prefix.'users WHERE username ILIKE \''.escape($author).'\'') or error('Unable to fetch users', __FILE__, __LINE__, $db->error());
						break;
				}

				if ($db->num_rows($result))
				{
					while ($row = $db->fetch_row($result))
						$user_ids .= ( ($user_ids != '') ? ',' : '').$row[0];

					$result = $db->query('SELECT id FROM '.$db->prefix.'posts WHERE poster_id IN('.$user_ids.')') or error('Unable to fetch matched posts list', __FILE__, __LINE__, $db->error());

					$search_ids = array();
					while ($row = $db->fetch_row($result))
						$author_results[] = $row[0];

					$db->free_result($result);
				}
			}


			if ($author && $keywords)
			{
				// If we searched for both keywords and author name we want the intersection between the results
				$search_ids = array_intersect($keyword_results, $author_results);
				unset($keyword_results, $author_results);
			}
			else if ($keywords)
				$search_ids = $keyword_results;
			else
				$search_ids = $author_results;

			$num_hits = count($search_ids);
			if (!$num_hits)
				message($lang_search['No hits']);


			if ($show_as == 'topics')
			{
				if ($forum_sql == '')
					$sql = 'SELECT topic_id FROM '.$db->prefix.'posts WHERE id IN('.implode(',', $search_ids).') GROUP BY topic_id';
				else
					$sql = 'SELECT p.topic_id FROM '.$db->prefix.'posts AS p INNER JOIN '.$db->prefix.'topics AS t ON p.topic_id=t.id WHERE p.id IN('.implode(',', $search_ids).') AND '.$forum_sql.' GROUP BY p.topic_id';

				$result = $db->query($sql) or error('Unable to fetch topic list', __FILE__, __LINE__, $db->error());

				$search_ids = array();
				while ($row = $db->fetch_row($result))
					$search_ids[] = $row[0];

				$db->free_result($result);

				$num_hits = count($search_ids);
			}
			else if ($forum_sql)
			{
				$sql = 'SELECT p.id FROM '.$db->prefix.'posts AS p INNER JOIN '.$db->prefix.'topics AS t ON p.topic_id=t.id WHERE p.id IN('.implode(',', $search_ids).') AND '.$forum_sql;

				$result = $db->query($sql) or error('Unable to fetch post list', __FILE__, __LINE__, $db->error());

				$search_ids = array();
				while ($row = $db->fetch_row($result))
					$search_ids[] = $row[0];

				$db->free_result($result);

				$num_hits = count($search_ids);
			}
		}
		else if ($action == 'show_new' || $action == 'show_user' || $action == 'show_unanswered')
		{
			// If it's a search for new posts
			if ($action == 'show_new')
			{
				if ($cookie['is_guest'])
					message($lang_common['No permission']);

				if ($forum_sql != '')
					$sql = 'SELECT t.id FROM '.$db->prefix.'topics AS t WHERE t.last_post>'.$cookie['last_timeout'].' AND '.$forum_sql;
				else
					$sql = 'SELECT id FROM '.$db->prefix.'topics WHERE last_post>'.$cookie['last_timeout'];

				$result = $db->query($sql) or error('Unable to fetch topic list', __FILE__, __LINE__, $db->error());
				$num_hits = $db->num_rows($result);

				if (!$num_hits)
					message($lang_search['No new posts']);
			}
			// If it's a search for posts by a specific user ID
			else if ($action == 'show_user')
			{
				if ($forum_sql != '')
					$sql = 'SELECT t.id FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'posts AS p ON p.topic_id=t.id WHERE p.poster_id='.$user_id.' AND '.$forum_sql.' GROUP BY t.id';
				else
					$sql = 'SELECT t.id FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'posts AS p ON p.topic_id=t.id WHERE p.poster_id='.$user_id.' GROUP BY t.id';

				$result = $db->query($sql) or error('Unable to fetch topic list', __FILE__, __LINE__, $db->error());
				$num_hits = $db->num_rows($result);

				if (!$num_hits)
					message($lang_search['User no posts']);
			}
			// If it's a search for unanswered posts
			else
			{
				if ($forum_sql != '')
					$sql = 'SELECT t.id FROM '.$db->prefix.'topics AS t WHERE t.num_replies=0 AND t.moved_to IS NULL AND '.$forum_sql;
				else
					$sql = 'SELECT id FROM '.$db->prefix.'topics WHERE num_replies=0 AND moved_to IS NULL';

				$result = $db->query($sql) or error('Unable to fetch topic list', __FILE__, __LINE__, $db->error());
				$num_hits = $db->num_rows($result);

				if (!$num_hits)
					message($lang_search['No unanswered']);
			}

			// We want to sort things after last post
			$sort_by = 4;

			$search_ids = array();
			while ($row = $db->fetch_row($result))
				$search_ids[] = $row[0];

			$db->free_result($result);

			$show_as = 'topics';
		}
		else
			message($lang_common['Bad request']);


		// Prune "old" search results
		$result = $db->query('SELECT ident FROM '.$db->prefix.'online') or error('Unable to fetch online list', __FILE__, __LINE__, $db->error());

		if ($db->num_rows($result) > 0)
		{
			while ($row = $db->fetch_row($result))
				$old_searches[] = '\''.$row[0].'\'';

			$db->query('DELETE FROM '.$db->prefix.'search_results WHERE ident NOT IN('.implode(',', $old_searches).')') or error('Unable to delete search results', __FILE__, __LINE__, $db->error());
		}

		// Final search results
		$search_results = implode(',', $search_ids);

		// Fill an array with our results and search properties
		$temp['search_results'] = $search_results;
		$temp['num_hits'] = $num_hits;
		$temp['sort_by'] = $sort_by;
		$temp['sort_dir'] = $sort_dir;
		$temp['show_as'] = $show_as;
		$temp = addslashes(serialize($temp));
		$search_id = mt_rand();

		if ($cookie['is_guest'])
			$ident = get_remote_address();
		else
			$ident = addslashes($cookie['username']);

		$db->query('UPDATE '.$db->prefix.'search_results SET id='.$search_id.', search_data=\''.$temp.'\' WHERE ident=\''.$ident.'\'') or error('Unable to update search results', __FILE__, __LINE__, $db->error());
		if (!$db->affected_rows())
			$db->query('INSERT INTO '.$db->prefix.'search_results (id, ident, search_data) VALUES('.$search_id.', \''.$ident.'\', \''.$temp.'\')') or error('Unable to insert search results', __FILE__, __LINE__, $db->error());
	}

	// Fetch results to display
	if ($search_results != '')
	{
		switch ($sort_by)
		{
			case 1:
				$sql = ($show_as == 'topics') ? 't.poster' : 'p.poster';
				break;

			case 2:
				$sql = 't.subject';
				break;

			case 3:
				$sql = 't.forum_id';
				break;

			case 4:
				$sql = 't.last_post';
				break;

			default:
			{
				$sql = ($show_as == 'topics') ? 't.posted' : 'p.posted';

				if ($show_as == 'topics')
					$group_by = ', t.posted';

				break;
			}
		}
		$group_by = '';
		if ($show_as == 'posts')
			$sql = 'SELECT p.id AS pid, p.poster AS pposter, p.poster_id, SUBSTRING(p.message, 1, 140) AS message, t.id AS tid, t.poster, t.subject, t.last_post, t.last_post_id, t.last_poster, t.num_replies, t.forum_id FROM '.$db->prefix.'posts AS p INNER JOIN '.$db->prefix.'topics AS t ON p.topic_id=t.id WHERE p.id IN('.$search_results.') ORDER BY '.$sql;
		else
			$sql = 'SELECT t.id AS tid, t.poster, t.subject, t.last_post, t.last_post_id, t.last_poster, t.num_replies, t.forum_id FROM '.$db->prefix.'posts AS p INNER JOIN '.$db->prefix.'topics AS t ON p.topic_id=t.id WHERE t.id IN('.$search_results.') GROUP BY t.id, t.poster, t.subject, t.last_post, t.last_post_id, t.last_poster, t.num_replies, t.forum_id'.$group_by.' ORDER BY '.$sql;

		$per_page = ($show_as == 'posts') ? $disp_posts : $disp_topics;

		// The number of pages required to display all results (depending on $disp_topics setting)
		$num_pages = ceil($num_hits / $per_page);

		if (!isset($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $num_pages)
		{
			$p = 1;
			$start_from = 0;
		}
		else
		{
			$p = $_GET['p'];
			$start_from = $per_page * ($p - 1);
		}


		$sql .= ' '.$sort_dir.' LIMIT '.$start_from.', '.$per_page;

		$result = $db->query($sql) or error('Unable to fetch search results', __FILE__, __LINE__, $db->error());

		$search_set = array();
		while ($row = $db->fetch_assoc($result))
			$search_set[] = $row;

		$db->free_result($result);


		$page_title = htmlspecialchars($options['board_title']).' / '.$lang_search['Search results'];
		require 'header.php';

?>
<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>

<table class="punmain" cellspacing="1" cellpadding="4">
	<tr class="punhead">
		<td class="punhead" style="width: 24px">&nbsp;</td>
		<td class="punhead" style="white-space: nowrap"><?php print ($show_as == 'posts') ? $lang_search['Topic/Message'] : $lang_common['Topic']; ?></td>
		<td class="punhead" style="width: 18%; white-space: nowrap"><?php print $lang_common['Forum'] ?></td>
		<td class="punhead" style="width: 11%; white-space: nowrap"><?php print $lang_common['Author'] ?></td>
		<td class="punheadcent" style="width: 7%; white-space: nowrap"><?php print $lang_common['Replies'] ?></td>
		<td class="punhead" style="width: 25%; white-space: nowrap"><?php print $lang_common['Last post'] ?></td>
	</tr>
<?php
//here is the problem
		for ($i = 0; $i < count($search_set); $i++)
		{
			@reset($forum_list);
			foreach ($forum_list as $temp)
			{
				if ($temp[0] == $search_set[$i]['forum_id'])
					$forum = '<a href="viewforum.php?id='.$temp[0].'">'.$temp[1].'</a>';
			}

			if ($options['censoring'] == '1')
				$search_set[$i]['subject'] = censor_words($search_set[$i]['subject']);

			$subject = '<a href="viewtopic.php?id='.$search_set[$i]['tid'].'">'.htmlspecialchars($search_set[$i]['subject']).'</a>';

			if (!$cookie['is_guest'] && $search_set[$i]['last_post'] > $cookie['last_timeout'])
			{
				if ($cur_user['show_img'] != '0')
					$icon = '<img src="img/'.$cur_user['style'].'_new.png" width="16" height="16" alt="">';
				else
					$icon = '<span class="puntext"><b>&#8226;</b></span>';

				$subject = '<b>'.$subject.'</b>';
			}
			else
				$icon = '&nbsp;';

			if ($show_as == 'posts')
			{
				if ($options['censoring'] == '1')
					$search_set[$i]['message'] = censor_words($search_set[$i]['message']);

				$message = str_replace("\n", '<br>', htmlspecialchars($search_set[$i]['message']));
				$pposter = htmlspecialchars($search_set[$i]['pposter']);

				if ($search_set[$i]['poster_id'] > 1)
					$pposter = '<a href="profile.php?id='.$search_set[$i]['poster_id'].'">'.$pposter.'</a>';

				if (strlen($message) == 140)
					$message .= ' ...';

?>
	<tr class="puntopic">
		<td class="puncon1cent"><?php print $icon ?></td>
		<td class="puncon2">
			<?php print $lang_common['Topic'] ?>: <?php print $subject ?><br>
			<?php print $lang_common['Author'] ?>: <?php print $pposter ?><br><br>
			<table class="punplain" style="table-layout: fixed" cellspacing="4" cellpadding="6">
				<tr>
					<td class="punquote">
						<?php print $message ?>
						<div style="text-align: right"><a href="viewtopic.php?pid=<?php print $search_set[$i]['pid'].'#'.$search_set[$i]['pid'] ?>"><?php print $lang_search['Go to post'] ?></a></div>
					</td>
				</tr>
			</table>
		</td>
		<td class="puncon1"><?php print $forum ?></td>
		<td class="puncon2"><?php print htmlspecialchars($search_set[$i]['poster']) ?></td>
		<td class="puncon1cent"><?php print $search_set[$i]['num_replies'] ?></td>
		<td class="puncon2" style="white-space: nowrap"><?php print '<a href="viewtopic.php?pid='.$search_set[$i]['last_post_id'].'#'.$search_set[$i]['last_post_id'].'">'.format_time($search_set[$i]['last_post']).'</a> '.$lang_common['by'].' '.htmlspecialchars($search_set[$i]['last_poster']) ?></td>
	</tr>
<?php

			}
			else
			{

?>
<tr class="puntopic">
	<td class="puncon1cent"><?php print $icon ?></td>
	<td class="puncon2"><?php print $subject ?></td>
	<td class="puncon1"><?php print $forum ?></td>
	<td class="puncon2"><?php print htmlspecialchars($search_set[$i]['poster']) ?></td>
	<td class="puncon1cent"><?php print $search_set[$i]['num_replies'] ?></td>
	<td class="puncon2" style="white-space: nowrap"><?php print '<a href="viewtopic.php?pid='.$search_set[$i]['last_post_id'].'#'.$search_set[$i]['last_post_id'].'">'.format_time( $search_set[$i]['last_post']).'</a> '.$lang_common['by'].' '.htmlspecialchars($search_set[$i]['last_poster']) ?></td>
</tr>
<?php

			}
		}

?>
</table>

<table class="punplain" cellspacing="1" cellpadding="4">
	<tr>
		<td><?php print $lang_common['Pages'].': '.paginate($num_pages, $p, 'search.php?search_id='.$search_id) ?></td>
	</tr>
</table>
<?php

		$footer_style = 'search';
		require 'footer.php';
	}
	else
		message($lang_search['No hits']);
}


if ($options['search'] == '0' && $cur_user['status'] < 1)
	message($lang_search['Search disabled']);

$page_title = htmlspecialchars($options['board_title']).' / '.$lang_search['Search'];
$validate_form = true;
$form_name = 'search';
$focus_element = 'keywords';
require 'header.php';

?>
<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>

<form method="post" action="search.php?action=search" id="search" onsubmit="return process_form(this)">
	<input type="hidden" name="action" value="search">
	<table class="punmain" cellspacing="1" cellpadding="4">
		<tr class="punhead">
			<td class="punhead" colspan="2"><?php print $lang_search['Search'] ?></td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><b><?php print $lang_search['Keyword search'] ?></b>&nbsp;&nbsp;</td>
			<td class="puncon2">
				<?php print $lang_search['Keyword search info'] ?><br><br>
				&nbsp;<input type="text" name="keywords" size="40" maxlength="100">
			</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><b><?php print $lang_search['Author search'] ?></b>&nbsp;&nbsp;</td>
			<td class="puncon2">
				<?php print $lang_search['Author search info'] ?><br><br>
				&nbsp;<input type="text" name="author" size="40" maxlength="100">
			</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><b><?php print $lang_search['Forum search'] ?></b>&nbsp;&nbsp;</td>
			<td class="puncon2">
				<br>&nbsp;
				<select name="forum">
<?php

if ($options['search_all_forums'] == '1' || $cur_user['status'] > 0)
	print "\t\t\t\t\t".'<option value="-1">'.$lang_search['All forums'].'</option>'."\n";


if ($cur_user['status'] < 1)
	$extra = ' WHERE c.admmod_only=\'0\' AND f.admmod_only=\'0\'';

$result = $db->query('SELECT c.id AS cid, c.cat_name, f.id AS fid, f.forum_name FROM '.$db->prefix.'categories AS c INNER JOIN '.$db->prefix.'forums AS f ON c.id=f.cat_id'.$extra.' ORDER BY c.position, cid, f.position') or error('Unable to fetch category/forum list', __FILE__, __LINE__, $db->error());
$num_forums = $db->num_rows($result);

while ($num_forums--)
{
	$forum = $db->fetch_assoc($result);

	if ($forum['cid'] != $cur_category)	// Are we still in the same category?
	{
		if (!empty($cur_category))
			print "\t\t\t\t\t".'</optgroup>'."\n";

		print "\t\t\t\t\t".'<optgroup label="'.htmlspecialchars($forum['cat_name']).'">'."\n";
		$cur_category = $forum['cid'];
	}

	print "\t\t\t\t\t\t".'<option value="'.$forum['fid'].'">'.htmlspecialchars($forum['forum_name']).'</option>'."\n";
}

?>
					</optgroup>
				</select><br><br>
			</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><b><?php print $lang_search['Search in'] ?></b>&nbsp;&nbsp;</td>
			<td class="puncon2">
				&nbsp;&nbsp;<input type="radio" name="search_in" value="all" checked>&nbsp;<?php print $lang_search['Message and subject'] ?><br>
				&nbsp;&nbsp;<input type="radio" name="search_in" value="message">&nbsp;<?php print $lang_search['Message only'] ?><br>
				&nbsp;&nbsp;<input type="radio" name="search_in" value="topic">&nbsp;<?php print $lang_search['Topic only'] ?>
			</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><b><?php print $lang_search['Sort by'] ?></b>&nbsp;&nbsp;</td>
			<td class="puncon2">
				<br>&nbsp;
				<select name="sort_by">
					<option value="0"><?php print $lang_search['Sort by post time'] ?></option>
					<option value="1"><?php print $lang_search['Sort by author'] ?></option>
					<option value="2"><?php print $lang_search['Sort by subject'] ?></option>
					<option value="3"><?php print $lang_search['Sort by forum'] ?></option>
				</select>
				&nbsp;&nbsp;<input type="radio" name="sort_dir" value="ASC">&nbsp;<?php print $lang_search['Ascending'] ?>
				&nbsp;&nbsp;<input type="radio" name="sort_dir" value="DESC" checked>&nbsp;<?php print $lang_search['Descending'] ?><br><br>
			</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><b><?php print $lang_search['Show as'] ?></b>&nbsp;&nbsp;</td>
			<td class="puncon2">
				&nbsp;&nbsp;<input type="radio" name="show_as" value="topics" checked>&nbsp;<?php print $lang_search['Show as topics'] ?><br>
				&nbsp;&nbsp;<input type="radio" name="show_as" value="posts">&nbsp;<?php print $lang_search['Show as posts'] ?>
			</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php print $lang_common['Actions'] ?>&nbsp;&nbsp;</td>
			<td class="puncon2"><br>&nbsp;&nbsp;<input type="submit" name="search" value="<?php print $lang_common['Submit'] ?>" accesskey="s"><br><br></td>
		</tr>
	</table>
</form>

<table class="punplain" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>
<?php

require 'footer.php';
