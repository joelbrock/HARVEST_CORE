<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class UnfiExportForMas extends FannieReportPage 
{

    protected $report_headers = array('Vendor', 'Inv#', 'Date', 'Inv Ttl', 'Code Ttl', 'Code');
    protected $sortable = false;
    protected $no_sort_but_style = true;

    public $page_set = 'Purchasing';
    public $description = '[MAS Invoice Export] exports vendor invoices for MAS90.';
    public $themed = true;

    function preprocess(){
        /**
          Set the page header and title, enable caching
        */
        $this->report_cache = 'none';
        $this->title = "Fannie : Invoice Export";
        $this->header = "Invoice Export";

        if (isset($_REQUEST['date1'])) {
            /**
              Form submission occurred

              Change content function, turn off the menus,
              set up headers
            */
            $this->content_function = "report_content";
            $this->has_menus(False);
        
            /**
              Check if a non-html format has been requested
            */
            if (isset($_REQUEST['excel']) && $_REQUEST['excel'] == 'xls')
                $this->report_format = 'xls';
            elseif (isset($_REQUEST['excel']) && $_REQUEST['excel'] == 'csv')
                $this->report_format = 'csv';
        }

        return True;
    }

    /**
      Lots of options on this report.
    */
    function fetch_report_data(){
        global $FANNIE_OP_DB;
        $date1 = FormLib::get_form_value('date1',date('Y-m-d'));
        $date2 = FormLib::get_form_value('date2',date('Y-m-d'));

        $dbc = FannieDB::get($FANNIE_OP_DB);
        $departments = $dbc->tableDefinition('departments');
        $codingQ = 'SELECT o.orderID, d.salesCode, i.vendorInvoiceID, 
                    SUM(o.receivedTotalCost) as rtc,
                    MAX(o.receivedDate) AS rdate
                    FROM PurchaseOrderItems AS o
                    LEFT JOIN PurchaseOrder as i ON o.orderID=i.orderID
                    LEFT JOIN products AS p ON o.internalUPC=p.upc ';
        if (isset($departments['salesCode'])) {
            $codingQ .= ' LEFT JOIN departments AS d ON p.department=d.dept_no ';
        } else if ($dbc->tableExists('deptSalesCodes')) {
            $codingQ .= ' LEFT JOIN deptSalesCodes AS d ON p.department=d.dept_ID ';
        }
        $codingQ .= 'WHERE i.vendorID=? AND i.userID=0
                    AND o.receivedDate BETWEEN ? AND ?
                    GROUP BY o.orderID, d.salesCode, i.vendorInvoiceID
                    ORDER BY rdate, i.vendorInvoiceID, d.salesCode';
        $codingP = $dbc->prepare($codingQ);

        $report = array();
        $invoice_sums = array();
        $vendorID = FormLib::get('vendorID');
        $codingR = $dbc->execute($codingP, array($vendorID, $date1.' 00:00:00', $date2.' 23:59:59'));
        while($codingW = $dbc->fetch_row($codingR)) {
            if ($codingW['rtc'] == 0) {
                // skip zero lines (tote charges)
                continue;
            }
            $code = $codingW['salesCode'];
            if (substr($code,0,1) == "4") {
                $code = '5'.substr($code, 1);
            } else if (empty($code) && $this->report_format == 'html') {
                $code = 'n/a';
            }
            $record = array(
                'UNFI',
                $codingW['vendorInvoiceID'],
                $codingW['rdate'],
                0.00,
                sprintf('%.2f', $codingW['rtc']),
                $code,
            );
            if (!isset($invoice_sums[$codingW['vendorInvoiceID']])) {
                $invoice_sums[$codingW['vendorInvoiceID']] = 0;
            }
            $invoice_sums[$codingW['vendorInvoiceID']] += $codingW['rtc'];
            $report[] = $record;
        }
        for($i=0; $i<count($report); $i++) {
            $inv = $report[$i][1];
            $report[$i][3] = sprintf('%.2f', $invoice_sums[$inv]);
        }

        return $report;
    }
    
    function form_content()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        ob_start();
        ?>
<form method = "get" action="UnfiExportForMas.php">
<div class="col-sm-5">
    <div class="form-group">
        <label>Date Start</label>
            <input type=text id=date1 name=date1 
                class="form-control date-field" required />
    </div>
    <div class="form-group">
        <label>Date End</label>
            <input type=text id=date2 name=date2 
                class="form-control date-field" required />
    </div>
    <div class="form-group">
        <label>Vendor</label>
        <select name="vendorID" class="form-control">
        <?php
        $vendors = new VendorsModel($dbc);
        foreach ($vendors->find('vendorName') as $obj) {
            printf('<option %s value="%d">%s</option>',
                ($obj->vendorName() == 'UNFI' ? 'selected' : ''),
                $obj->vendorID(), $obj->vendorName());
        }
        ?>
        </select>
    </div>
    <p>
        <button type="submit" class="btn btn-default">Submit</button>
        <button type="reset" class="btn btn-default">Start Over</button>
    </p>
</div>
<div class="col-sm-5">
    <?php echo FormLib::date_range_picker(); ?>                         
</div>
</form>
<?php

        return ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>
            Transforms vendor invoice data into a format that
            can be imported into Sage MAS90. This probably cannot
            be used outside WFC other than as an example to
            base similar import/export reports on.
            </p>';
    }
}

FannieDispatch::conditionalExec();

