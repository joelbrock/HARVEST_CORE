<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Community Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

require(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class OpenRingReceipts extends FannieRESTfulPage
{
    protected $header = 'Open Ring Receipts';
    protected $title = 'Open Ring Receipts';

    public $description = '[Open Ring Receipts] finds receipts on a given day that
    contain an open ring. Optionally filter the list to receipts containing
    both an open ring and a UPC that did not scan. These are likely items that need
    to be entered into POS.';
    public $themed = true;

    public function preprocess()
    {
        $this->__routes[] = 'get<date1><date2>';

        return parent::preprocess();
    }
    
    public function get_date1_date2_handler()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $dlog = DTransactionsModel::selectDlog($this->date1, $this->date2);
        $dtrans = DTransactionsModel::selectDtrans($this->date1, $this->date2);

        $openQ = '
            SELECT YEAR(tdate) AS year,
                MONTH(tdate) AS month,
                DAY(tdate) AS day,
                emp_no,
                register_no,
                trans_no
            FROM ' . $dlog . ' AS d
                INNER JOIN MasterSuperDepts AS m ON m.dept_ID=d.department
            WHERE tdate BETWEEN ? AND ?
                AND trans_type=\'D\'
                AND m.superID <> 0
            GROUP BY YEAR(tdate),
                MONTH(tdate),
                DAY(tdate),
                emp_no,
                register_no,
                trans_no
            HAVING SUM(total) <> 0';

        $badQ = '
            SELECT upc
            FROM ' . $dtrans . ' AS d
            WHERE datetime BETWEEN ? AND ?
                AND emp_no=?
                AND register_no=?
                AND trans_no=?
                AND trans_type=\'L\'
                AND description=\'BADSCAN\'
                AND d.upc LIKE \'0%\'
                AND d.upc NOT LIKE \'00000000000%\'';

        $openP = $dbc->prepare($openQ);
        $badP = $dbc->prepare($badQ);
        $filter = FormLib::get('badscans', false);

        $this->receipts = array();
        $openR = $dbc->execute($openP, array($this->date1 . ' 00:00:00', $this->date2 . ' 23:59:59'));
        while ($openW = $dbc->fetchRow($openR)) {
            $ts = mktime(0, 0, 0, $openW['month'], $openW['day'], $openW['year']);
            if ($filter) {
                $args = array(
                    date('Y-m-d 00:00:00', $ts),
                    date('Y-m-d 23:59:59', $ts),
                    $openW['emp_no'],
                    $openW['register_no'],
                    $openW['trans_no'],
                );
                $badR = $dbc->execute($badP, $args);
                if (!$badR || $dbc->num_rows($badR) == 0) {
                    continue;
                }
            }
            $this->receipts[] = array(
                'date' => date('Y-m-d', $ts),
                'trans_num' => $openW['emp_no'] . '-' . $openW['register_no'] . '-' . $openW['trans_no'],
            );
        }

        return true;
    }

    public function get_date1_date2_view()
    {
        $ret = '';
        if (!is_array($this->receipts) || count($this->receipts) == 0) {
            $ret .= '<div class="alert alert-danger">No matches found</div>';
        } else {
            $url_stem = $this->config->get('URL');
            $ret .= '<ul>';
            foreach ($this->receipts as $receipt) {
                $ret .= sprintf('<li><a href="%sadmin/LookupReceipt/RenderReceiptPage.php?date=%s&receipt=%s"
                                    target="_rp_%s_%d">%s</a></li>',
                            $url_stem, $receipt['date'], $receipt['trans_num'],
                            $receipt['date'], $receipt['trans_num'], $receipt['trans_num']);
            }
            $ret .= '</ul>';
        }

        $ret .= '<p>
            <a href="' . $_SERVER['PHP_SELF'] . '" class="btn btn-default">New Search</a>
            </p>';

        return $ret;
    }

    public function get_view()
    {
        return '
            <form action="' . $_SERVER['PHP_SELF'] . '" type="get">
            <div class="col-sm-5">
                <div class="form-group">
                    <label>Start Date</label>
                    <input type="text" name="date1" id="date1" class="form-control date-field" required />
                </div>
                <div class="form-group">
                    <label>End Date</label>
                    <input type="text" name="date2" id="date2" class="form-control date-field" required />
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="badscans" value="1" checked />
                        Only receipts with unknown UPCs
                    </label>
                </div>
                <p>
                    <button type="submit" class="btn btn-default">Lookup Receipts</button>
                </p>
            </div>
            <div class="col-sm-5">
                ' . FormLib::dateRangePicker() . '
            </div>
            </form>';
    }
}

FannieDispatch::conditionalExec();

