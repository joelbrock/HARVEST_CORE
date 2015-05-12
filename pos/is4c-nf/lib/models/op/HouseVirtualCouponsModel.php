<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of Fannie.

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
  @class HouseVirtualCouponsModel
*/
class HouseVirtualCouponsModel extends BasicModel
{

    protected $name = "houseVirtualCoupons";
    protected $preferred_db = 'op';

    protected $columns = array(
    'card_no' => array('type'=>'INT', 'primary_key'=>true),
    'coupID' => array('type'=>'INT', 'primary_key'=>true),
    'description' => array('type'=>'VARCHAR(100)'),
    'start_date' => array('type'=>'DATETIME'),
    'end_date' => array('type'=>'DATETIME'),
    );

    /* START ACCESSOR FUNCTIONS */

    public function card_no()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["card_no"])) {
                return $this->instance["card_no"];
            } elseif(isset($this->columns["card_no"]["default"])) {
                return $this->columns["card_no"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["card_no"] = func_get_arg(0);
        }
    }

    public function coupID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["coupID"])) {
                return $this->instance["coupID"];
            } elseif(isset($this->columns["coupID"]["default"])) {
                return $this->columns["coupID"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["coupID"] = func_get_arg(0);
        }
    }

    public function description()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["description"])) {
                return $this->instance["description"];
            } elseif(isset($this->columns["description"]["default"])) {
                return $this->columns["description"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["description"] = func_get_arg(0);
        }
    }

    public function start_date()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["start_date"])) {
                return $this->instance["start_date"];
            } elseif(isset($this->columns["start_date"]["default"])) {
                return $this->columns["start_date"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["start_date"] = func_get_arg(0);
        }
    }

    public function end_date()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["end_date"])) {
                return $this->instance["end_date"];
            } elseif(isset($this->columns["end_date"]["default"])) {
                return $this->columns["end_date"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["end_date"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

