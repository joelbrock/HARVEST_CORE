<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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
  @class MemberCardsViewModel
*/
class MemberCardsViewModel extends ViewModel
{

    protected $name = "memberCardsView";

    protected $columns = array(
    'upc' => array('type'=>'VARCHAR(13)'),
    'card_no' => array('type'=>'INT'),
    );

    public $preferred_db = 'op';

    public function definition()
    {
        $cardsViewQ = "
            SELECT "
                . $this->connection->concat("'".CoreLocal::get('memberUpcPrefix')."'",'c.CardNo','') . " AS upc, 
                c.CardNo as card_no 
            FROM custdata c";

        return $cardsViewQ;
    }

    /* START ACCESSOR FUNCTIONS */

    public function upc()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["upc"])) {
                return $this->instance["upc"];
            } elseif(isset($this->columns["upc"]["default"])) {
                return $this->columns["upc"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["upc"] = func_get_arg(0);
        }
    }

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
    /* END ACCESSOR FUNCTIONS */
}

