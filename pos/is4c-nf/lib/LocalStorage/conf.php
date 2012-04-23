<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

    This file is part of IT CORE.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

$CORE_PATH = isset($CORE_PATH)?$CORE_PATH:"";
if (empty($CORE_PATH)){ while(!file_exists($CORE_PATH."pos.css")) $CORE_PATH .= "../"; }

/** 
  @file 
  @brief This file specifies the LocalStorage implemenation

  Include this file to get a LocalStorage instance
  in the variable $CORE_LOCAL.
*/


ini_set('display_errors',1);
ini_set('error_log',$CORE_PATH."log/php-errors.log");

$LOCAL_STORAGE_MECHANISM = 'SessionStorage';

if (!class_exists($LOCAL_STORAGE_MECHANISM)){
	include($CORE_PATH."lib/LocalStorage/"
		.$LOCAL_STORAGE_MECHANISM.".php");
}

$CORE_LOCAL = new $LOCAL_STORAGE_MECHANISM();
global $CORE_LOCAL;

?>
