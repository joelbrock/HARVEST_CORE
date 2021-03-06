<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of CORE-POS.

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
  @class DisableCouponModel
*/
class DisableCouponModel extends BasicModel 
{

    protected $name = "disableCoupon";

    protected $columns = array(
    'upc' => array('type'=>'VARCHAR(13)','primary_key'=>True),
    'threshold' => array('type'=>'SMALLINT','default'=>0),
    'reason' => array('type'=>'text')
    );

    public function doc()
    {
        return '
Table: disableCoupon

Columns:
    upc varchar
    reason text

Depends on:
    none

Use:
Maintain a list of manufacturer coupons
the store does not accept. Most common
usage is coupons where a store does carry
products from that manufacturer but does
not carry any products the meet coupon
requirements. In theory family codes
address this situation better, but
obtaining and maintaing those codes isn\'t
feasible.
        ';
    }

    /* START ACCESSOR FUNCTIONS */

    public function upc()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["upc"])) {
                return $this->instance["upc"];
            } else if (isset($this->columns["upc"]["default"])) {
                return $this->columns["upc"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'upc',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["upc"]) || $this->instance["upc"] != func_get_args(0)) {
                if (!isset($this->columns["upc"]["ignore_updates"]) || $this->columns["upc"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["upc"] = func_get_arg(0);
        }
        return $this;
    }

    public function threshold()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["threshold"])) {
                return $this->instance["threshold"];
            } else if (isset($this->columns["threshold"]["default"])) {
                return $this->columns["threshold"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'threshold',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["threshold"]) || $this->instance["threshold"] != func_get_args(0)) {
                if (!isset($this->columns["threshold"]["ignore_updates"]) || $this->columns["threshold"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["threshold"] = func_get_arg(0);
        }
        return $this;
    }

    public function reason()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["reason"])) {
                return $this->instance["reason"];
            } else if (isset($this->columns["reason"]["default"])) {
                return $this->columns["reason"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'reason',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["reason"]) || $this->instance["reason"] != func_get_args(0)) {
                if (!isset($this->columns["reason"]["ignore_updates"]) || $this->columns["reason"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["reason"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

