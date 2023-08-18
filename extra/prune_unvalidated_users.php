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


//	This script deletes any users that have registered but never logged in.
//	Copy this file to the forum root directory and run it. Then remove it
//  from the root directory or anyone will be able to run it (NOT good!).


@include 'config.php';

if (!defined('PUN'))
	exit('This file must be run from the forum root directory.');

require 'include/common.php';


print 'Pruning unvalidated users... ';
$result = $db->query('DELETE FROM '.$db->prefix.'users WHERE id>1 AND status=-1') or error('Unable to prune unvalidated users', __FILE__, __LINE__, $db->error());
print 'success<br><br>Now remove this file!';

exit;
