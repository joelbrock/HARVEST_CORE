<?php
/*******************************************************************************

    Copyright 2007 Whole Foods Co-op

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

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 *
 * 17Feb2013 Eric Lee Support argument to PV, either before or after.
 *           See also gui-modules/productlist.php
*/

/* 
 * This class is for any input designed to set processing
 * to an alternate gui module. That's how the particular
 * olio of seemingly unrelated inputs gets caught here
 */
class Steering extends Parser 
{
    private $dest_input_page;
    private $dest_main_page;
    private $dest_scale;
    private $ret;

    function check($str)
    {
        $my_url = MiscLib::base_url();
        
        $this->dest_input_page = "";
        $this->dest_main_page = "";
        $this->dest_scale = False;
        $this->ret = $this->default_json();

        // Argument to PV, either before or after.
        if (substr($str,-2,2) == "PV") {
            $pvsearch = substr($str,0,-2);
            $str = "PV";
        } elseif (substr($str,0,2) == "PV") {
            $pvsearch = substr($str,2);
            $str = "PV";
        }

        // common error message
        $repeat = CoreLocal::get('msgrepeat');
        $in_progress_msg = DisplayLib::boxMsg(
            _("transaction in progress"),
            '',
            true,
            DisplayLib::standardClearButton()
        );
        CoreLocal::set('msgrepeat', $repeat);

        switch($str) {
            
            case 'CAB':
                if (CoreLocal::get("LastID") != "0") {
                    $this->ret['output'] = $in_progress_msg;
                } else {
                    $this->ret['main_frame'] = $my_url."gui-modules/cablist.php";
                }
                return true;

            case "PV":
                CoreLocal::set("pvsearch","$pvsearch");
                $this->ret['main_frame'] = $my_url."gui-modules/productlist.php";
                return true;

            case "MSTG":
                if (CoreLocal::get('memType') == 1 || CoreLocal::get('memType') == 2) {
                    // could this be CoreLocal::get('isMember') == 1
                    // to avoid relying on specific memTypes?
                    $this->ret['output'] = DisplayLib::boxMsg(
                        _("Cannot UNset a member status"),
                        '',
                        true,
                        DisplayLib::standardClearButton()
                    );
                } elseif (CoreLocal::get("SecuritySR") > 20){
                    $this->ret['main_frame'] = $my_url."gui-modules/adminlogin.php?class=MemStatusAdminLogin";
                } else {
                    $this->ret['output'] = DisplayLib::boxMsg(
                        _("You must be an admin to do this."),
                        _('Access Denied'),
                        true,
                        DisplayLib::standardClearButton()
                    );
                }
                return true;

            case "UNDO":
                if (CoreLocal::get("LastID") != "0") {
                    $this->ret['output'] = $in_progress_msg;
                } else {
                    $this->ret['main_frame'] = $my_url."gui-modules/adminlogin.php?class=UndoAdminLogin";
                }
                return true;

            case 'SK':
            case "DDD":
                $this->ret['main_frame'] = $my_url."gui-modules/DDDReason.php";
                return true;
            case 'MG':
                if (CoreLocal::get("SecuritySR") > 20) {
                    $this->ret['main_frame'] = $my_url."gui-modules/adminlogin.php?class=SusResAdminLogin";
                } else {
                    $this->ret['main_frame'] = $my_url."gui-modules/adminlist.php";
                }
                return true;
            case 'RP':
                if (CoreLocal::get("LastID") != "0") {
                    $tr = CoreLocal::get("receiptToggle");
                    if ($tr == 1) {
                        CoreLocal::set("receiptToggle",0);
                    } else {
                        CoreLocal::set("receiptToggle",1);
                    }
                    $this->ret['main_frame'] = $my_url."gui-modules/pos2.php";
                } else {
                    $db = Database::tDataConnect();
                    $query = "select register_no, emp_no, trans_no, "
                        ."sum((case when trans_type = 'T' then -1 * total else 0 end)) as total "
                        ."from localtranstoday where register_no = " . CoreLocal::get("laneno")
                        ." and emp_no = " . CoreLocal::get("CashierNo")
                        ." AND datetime >= " . $db->curdate()
                        ." group by register_no, emp_no, trans_no order by 1000 - trans_no";
                    $result = $db->query($query);
                    $num_rows = $db->num_rows($result);

                    if ($num_rows == 0)  {
                        $this->ret['output'] = DisplayLib::boxMsg(
                            _("no receipt found"),
                            '',
                            true,
                            DisplayLib::standardClearButton()
                        );
                    } else {
                        $this->ret['main_frame'] = $my_url."gui-modules/rplist.php";
                    }
                }                
                return true;

            case 'ID':
                $this->ret['main_frame'] = $my_url."gui-modules/memlist.php";
                return true;

            case 'DDM':
                $this->ret['main_frame'] = $my_url.'gui-modules/drawerPage.php';
                return true;
            case 'SS':
            case 'SO':
                // sign off and suspend shift are identical except for
                // drawer behavior
                if (CoreLocal::get("LastID") != 0) {
                    $this->ret['output'] = $in_progress_msg;
                } else {
                    TransRecord::addLogRecord(array(
                        'upc' => 'SIGNOUT',
                        'description' => 'Sign Out Emp#' . CoreLocal::get('CashierNo'),
                    ));
                    Database::setglobalvalue("LoggedIn", 0);
                    CoreLocal::set("LoggedIn",0);
                    CoreLocal::set("training",0);
                    CoreLocal::set("gui-scale","no");
                    /**
                      An empty transaction may still contain
                      invisible, logging records. Rotate those
                      out of localtemptrans to ensure sequential
                      trans_id values
                    */
                    if (Database::rotateTempData()) {
                        Database::clearTempTables();
                    }
                    if ($str == 'SO') {
                        if (session_id() != '') {
                            session_write_close();
                        }
                        $kicker_class = (CoreLocal::get("kickerModule")=="") ? 'Kicker' : CoreLocal::get('kickerModule');
                        $kicker_object = new $kicker_class();
                        if ($kicker_object->kickOnSignOut()) {
                            ReceiptLib::drawerKick();
                        }
                        ReceiptLib::freeDrawer(ReceiptLib::currentDrawer());
                    }
                    $this->ret['main_frame'] = $my_url."login.php";
                }
                return true;

            case 'NS':
                if (CoreLocal::get("LastID") != 0) {
                    $this->ret['output'] = $in_progress_msg;
                } else {
                    $this->ret['main_frame'] = $my_url."gui-modules/nslogin.php";
                }
                return true;

            case 'GD':
                CoreLocal::set("msgrepeat",0);
                $this->ret['main_frame'] = $my_url."gui-modules/giftcardlist.php";
                return true;

            case 'IC':
                CoreLocal::set("msgrepeat",0);
                $this->ret['main_frame'] = $my_url."gui-modules/HouseCouponList.php";
                return true;

            case "CN":
                $this->ret['main_frame'] = $my_url."gui-modules/mgrlogin.php";
                return true;

            case "PO":
                $this->ret['main_frame'] = $my_url."gui-modules/adminlogin.php?class=PriceOverrideAdminLogin";
                return true;
        }

        return false;
    }

    public function parse($str)
    {
        return $this->ret;
    }

    public function doc()
    {
        return "<table cellspacing=0 cellpadding=3 border=1>
            <tr>
                <td colspan=2>This module gets used
                for a lot of seemingly disparate things.
                What they have in common is they all involve
                going to a different display page</td>
            </tr>
            <tr>
                <th>Input</th><th>Result</th>
            </tr>
            <tr>
                <td>PV</td>
                <td>Search for a product</td>
            </tr>
            <tr>
                <td>PROD</td>
                <td>Dump status of a product</td>
            </tr>
            <tr>
                <td>UNDO</td>
                <td>Reverse an entire transaction</td>
            </tr>
            <tr>
                <td>MG</td>
                <td>Suspend/resume transactions,
                print tender reports</td>
            </tr>
            <tr>
                <td>RP</td>
                <td>Reprint a receipt</td>
            </tr>
            <tr>
                <td>ID</td>
                <td>Search for a member</td>
            </tr>
            <tr>
                <td>SO</td>
                <td>Sign out register</td>
            </tr>
            <tr>
                <td>NS</td>
                <td>No sale</td>
            </tr>
            <tr>
                <td>GD</td>
                <td>Integrated gift card menu</td>
            </tr>
            <tr>
                <td>CN</td>
                <td>Cancel transaction</td>
            </tr>
            <tr>
                <td>SK or DDD</td>
                <td>Similar to cancelling a transaction, but marks all
                the items as unsellable (shrink), for the
                user-provided reason.</td>
            </tr>
            </table>";
    }
}

