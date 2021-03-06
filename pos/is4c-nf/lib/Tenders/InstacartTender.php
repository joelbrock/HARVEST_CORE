<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

/**
  @class Instacart
  Tender module for Instacart grocery service
  Requires Mgr. password
*/
class InstacartTender extends TenderModule 
{

    /**
      Check for errors
      @return True or an error message string
    */
    public function errorCheck()
    {
        global $CORE_LOCAL;

        if(MiscLib::truncate2($CORE_LOCAL->get("amtdue")) != MiscLib::truncate2($this->amount)) {
            return DisplayLib::xboxMsg(_("tender must equal purchase amount"));
        } else {
			return true;
		}
    }
    
    /**
      Set up state and redirect if needed
      @return True or a URL to redirect
    */
    public function preReqCheck()
    {
        global $CORE_LOCAL;
        $my_url = MiscLib::base_url();

        if ($CORE_LOCAL->get("instacarttender") != 1) {
            $CORE_LOCAL->set("instacarttender",1);
            return $my_url."gui-modules/adminlogin.php?class=InstacartTender";
        } else {
            $CORE_LOCAL->set("instacarttender",0);
            return true;
        }
    }

    /**
      adminlogin callback to approve instacart tenders
    */
    public static $adminLoginMsg = 'Login for Instacart Tender';

	// DHermann 31dec14: level reduced below standard 30, to allow non-supervisor
	//   to approve Instacart tender only
	// DHermann 6jan15: level reduced again below 20, which is the cutoff for returns
    public static $adminLoginLevel = 15;

    static public function adminLoginCallback($success)
    {
        global $CORE_LOCAL;
        if ($success) {
            $CORE_LOCAL->set('strRemembered', $CORE_LOCAL->get('strEntered'));    
            $CORE_LOCAL->set('msgrepeat', 1);
            return true;
        } else {
            $CORE_LOCAL->set('instacarttender', 0);
            return false;
        }
    }
}

