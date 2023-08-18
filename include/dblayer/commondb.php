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


define('PUN_TRANS_START', 1);
define('PUN_TRANS_END', 2);


// Load the appropriate DB layer class
switch ($db_type)
{
	case 'mysql':
		require 'include/dblayer/mysql.php';
		break;

	case 'pgsql':
		require 'include/dblayer/pgsql.php';
		break;

	default:
		error('\''.$db_type.'\' is not a valid database type. Please check settings in config.php', __FILE__, __LINE__);
		break;
}


// Create the database object (and connect to/select db)
$db = new DBLayer($db_host, $db_username, $db_password, $db_name, $db_prefix, $p_connect);
