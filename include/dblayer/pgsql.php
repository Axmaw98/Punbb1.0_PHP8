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


// Make sure we have built in support for MySQL
if (!function_exists('pg_connect'))
	exit('This PHP environment doesn\'t have PostgreSQL support built in. PostgreSQL support is required if you want to use a PostgreSQL database to run this forum. Consult the PHP documentation for further assistance.');


class DBLayer
{
	var $prefix;
	var $link_id;
	var $query_result;
	var $row = array();
	var $row_num = array();
	var $in_transaction = 0;
	var $num_queries = 0;


	function DBLayer($db_host, $db_username, $db_password, $db_name, $db_prefix, $p_connect)
	{
		$this->prefix = $db_prefix;

		if ($db_host != '')
		{
			if (strpos($db_host, ':') !== false)
			{
				list($db_host, $dbport) = explode(':', $db_host);
				$connect_str[] = 'host='.$db_host.' port='.$dbport;
			}
			else
			{
				if ($db_host != 'localhost')
					$connect_str[] = 'host='.$db_host;
			}
		}

		if ($db_name)
			$connect_str[] = 'dbname='.$db_name;

		if ($db_username != '')
			$connect_str[] = 'user='.$db_username;

		if ($db_password != '')
			$connect_str[] = 'password='.$db_password;

		if ($p_connect)
			$this->link_id = @pg_pconnect(implode(' ', $connect_str));
		else
			$this->link_id = @pg_connect(implode(' ', $connect_str));

		if (!$this->link_id)
			error('Unable to connect to PostgreSQL server and select database', __LINE__, __FILE__);
		else
			return $this->link_id;
	}


	function query($sql, $transaction = 0)
	{
		unset($this->query_result);

		if ($sql != '')
		{
			$this->num_queries++;

			$sql = preg_replace("/LIMIT ([0-9]+),([ 0-9]+)/", "LIMIT \\2 OFFSET \\1", $sql);

			if ($transaction == PUN_TRANS_START && !$this->in_transaction)
			{
				$this->in_transaction = true;

				if (!@pg_query($this->link_id, 'BEGIN'))
					return false;
			}

			$this->query_result = @pg_query($this->link_id, $sql);
			if ($this->query_result)
			{
				if ($transaction == PUN_TRANS_END)
				{
					$this->in_transaction = false;

					if (!@pg_query($this->link_id, 'COMMIT'))
					{
						@pg_query($this->link_id, 'ROLLBACK');
						return false;
					}
				}

				$this->last_query_text[$this->query_result] = $sql;
				$this->row_num[$this->query_result] = 0;

				unset($this->row[$this->query_result]);

				return $this->query_result;
			}
			else
			{
				if ($this->in_transaction)
					@pg_query($this->link_id, 'ROLLBACK');

				$this->in_transaction = false;

				return false;
			}
		}
		else
		{
			if ($transaction == PUN_TRANS_END && $this->in_transaction)
			{
				$this->in_transaction = false;

				if (!@pg_query($this->link_id, 'COMMIT'))
				{
					@pg_query($this->link_id, 'ROLLBACK');
					return false;
				}
			}

			return true;
		}
	}


	function result($query_id = 0, $row = 0)
	{
		if (!$query_id)
			$query_id = $this->query_result;

		if ($query_id)
			return @pg_fetch_result($query_id, $row, 0);
		else
			return false;
	}


	function fetch_array($query_id = 0)
	{
		if (!$query_id)
			$query_id = $this->query_result;

		if ($query_id)
		{
			$this->row = @pg_fetch_array($query_id, $this->row_num[$query_id]);

			if ($this->row)
			{
				$this->row_num[$query_id]++;
				return $this->row;
			}
		}
		else
			return false;
	}


	function fetch_assoc($query_id = 0)
	{
		if (!$query_id)
			$query_id = $this->query_result;

		if ($query_id)
		{
			$this->row = @pg_fetch_array($query_id, $this->row_num[$query_id], PGSQL_ASSOC);

			if ($this->row)
			{
				$this->row_num[$query_id]++;
				return $this->row;
			}
		}
		else
			return false;
	}


	function fetch_row($query_id = 0)
	{
		if (!$query_id)
			$query_id = $this->query_result;

		if ($query_id)
		{
			$this->row = @pg_fetch_row($query_id, $this->row_num[$query_id]);

			if ($this->row)
			{
				$this->row_num[$query_id]++;
				return $this->row;
			}
		}
		else
			return false;
	}


	function num_rows($query_id = 0)
	{
		if (!$query_id)
		{
			$query_id = $this->query_result;
		}

		return ($query_id) ? @pg_num_rows($query_id) : false;
	}


	function affected_rows($query_id = 0)
	{
		if (!$query_id)
			$query_id = $this->query_result;

		return ($query_id) ? @pg_affected_rows($query_id) : false;
	}


	function insert_id()
	{
		$query_id = $this->query_result;

		if ($query_id && $this->last_query_text[$query_id] != '')
		{
			if (preg_match('/^INSERT[\t\n ]+INTO[\t\n ]+([a-z0-9\_\-]+)/is', $this->last_query_text[$query_id], $tablename))
			{
				$sql = 'SELECT currval(\''.$tablename[1].'_id_seq\') AS lastval';
				$temp_q_id = @pg_query($this->link_id, $sql);

				if (!$temp_q_id)
					return false;

				$temp_result = @pg_fetch_array($temp_q_id, 0, PGSQL_ASSOC);

				return ($temp_result) ? $temp_result['lastval'] : false;
			}
		}

		return false;
	}


	function get_num_queries()
	{
		return $this->num_queries;
	}


	function free_result($query_id = false)
	{
		if (!$query_id)
			$query_id = $this->query_result;

		return ($query_id) ? @pg_freeresult($query_id) : false;
	}


	function error($query_id = 0)
	{
		if (!$query_id)
			$query_id = $this->query_result;

		$result['error'] = trim(@pg_last_error($this->link_id));
		$result['errno'] = -1;

		return $result;
	}


	function close()
	{
		if ($this->link_id)
		{
			if ($this->in_transaction)
				@pg_query($this->link_id, 'COMMIT');

			if ($this->query_result)
				@pg_freeresult($this->query_result);

			return @pg_close($this->link_id);
		}
		else
			return false;
	}
}
