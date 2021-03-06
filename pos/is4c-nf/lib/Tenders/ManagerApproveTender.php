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
  @class StoreTransfer
  Tender module for inter-departmental transfers
  Requires Mgr. password
*/
class ManagerApproveTender extends TenderModule 
{

    /**
      Check for errors
      @return True or an error message string
    */
    public function errorCheck()
    {
        if (MiscLib::truncate2(CoreLocal::get("amtdue")) < MiscLib::truncate2($this->amount)) {
            return DisplayLib::xboxMsg(
                _("tender cannot exceed purchase amount"),
                DisplayLib::standardClearButton()
            );
        }

        return true;
    }
    
    /**
      Set up state and redirect if needed
      @return True or a URL to redirect
    */
    public function preReqCheck()
    {
        $my_url = MiscLib::base_url();

        if (CoreLocal::get("approvetender") != 1) {
            CoreLocal::set("approvetender",1);
            return $my_url."gui-modules/adminlogin.php?class=ManagerApproveTender";
        } else {
            CoreLocal::set("approvetender",0);
            return true;
        }
    }

    /**
      adminlogin callback to approve store transfers
    */
    public static $adminLoginMsg = 'Login to approve tender';

    public static $adminLoginLevel = 30;

    static public function adminLoginCallback($success)
    {
        if ($success) {
            CoreLocal::set('strRemembered', CoreLocal::get('strEntered'));    
            CoreLocal::set('msgrepeat', 1);
            return true;
        } else {
            CoreLocal::set('approvetender', 0);
            return false;
        }
    }
}

