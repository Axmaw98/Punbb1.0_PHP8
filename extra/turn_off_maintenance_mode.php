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


//	This script turns off the maintenance mode. Use it you happened to log
//  out while the forum was in maintenance mode. Copy this file to the forum
//  root directory and run it. Then remove it from the root directory or
//  anyone will be able to run it (NOT good!).


@include 'config.php';

if (!defined('PUN'))
	exit('This file must be run from the forum root directory.');

// Tell common.php that we are running this script (prevent it from showing us the maintenance message)
define('PUN_TURN_OFF_MAINT', 1);
require 'include/common.php';


print 'Turning off maintenance mode... ';
$db->query('UPDATE '.$db->prefix.'options SET maintenance=0') or error('Unable to turn off maintenance mode', __FILE__, __LINE__, $db->error());
print 'success<br><br>Now remove this file!';

exit;
