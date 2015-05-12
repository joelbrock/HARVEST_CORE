<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Community Co-op

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
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class ItemStatusPage extends FannieRESTfulPage
{
    protected $header = '';
    protected $title = 'Status Check';
    protected $enable_linea = true;
    public $themed = true;
    public $description = '[Item Status] is a quick status check tool';

    public function preprocess()
    {
        $this->__routes[] = 'get<tagID><upc>';

        return parent::preprocess();
    }

    /**
      Queue or Re-Queue shelf tag
    */
    public function get_tagID_upc_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $product = new ProductsModel($dbc);
        $product->upc($upc);
        $product->load();
        $info = $product->getTagData();

        $tag = new ShelftagsModel($dbc);
        $tag->upc($this->upc);
        foreach ($tag as $obj) {
            if ($obj->id() != $this->tagID) {
                $obj->delete();
            }
        }

        $tag->id($this->ID);
        $tag->description($info['description']);
        $tag->brand($info['brand']);
        $tag->normal_price($info['normal_price']);
        $tag->sku($info['sku']);
        $tag->size($info['size']);
        $tag->units($info['units']);
        $tag->vendor($info['vendor']);
        $tag->pricePerUnit($info['pricePerUnit']);
        $tag->save();

        header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $this->upc);

        return false;
    }

    public function get_id_view()
    {
        global $FANNIE_OP_DB;
        $ret = $this->get_view();
        $upc = BarcodeLib::padUPC($this->id);

        $ret .= '<hr />';
        $ret .= '<div class="panel panel-default">
            <div class="panel-heading">' . $upc . '</div>
            <div class="panel-body">';

        $dbc = FannieDB::get($FANNIE_OP_DB);
        $product = new ProductsModel($dbc);
        $vendor = new VendorsModel($dbc);
        $product->upc($upc);
        if (!$product->load()) {
            $ret .= '<div class="alert alert-danger">Item not found</div>';
            return $ret;
        }
        $vendor->vendorID($product->default_vendor_id());
        $vendor->load();

        $ret .= '<p><strong>Brand</strong>: ' . $product->brand();
        $ret .= ', <strong>Desc.</strong>: ' . $product->description();
        $ret .= ', <strong>Vendor</strong>: ' . $vendor->vendorName() . '</p>';

        $ret .= '<p><strong>Price</strong>: $' . sprintf('%.2f', $product->normal_price());
        if ($product->discounttype() > 0) {
            $ret .= ', <span class="alert-success"><strong>On Sale</strong> $' . sprintf('%.2f', $product->special_price());
            $batchP = $dbc->prepare('
                SELECT b.batchName,
                    b.startDate,
                    b.endDate
                FROM batchList as l
                    INNER JOIN batches AS b ON l.batchID=b.batchID
                WHERE l.upc=?
                    AND ' . $dbc->curdate() . ' >= b.startDate 
                    AND ' . $dbc->curdate() . ' <= b.endDate');
            $batchR = $dbc->execute($batchP, array($upc));
            if ($batchR && $dbc->num_rows($batchR)) {
                $batchW = $dbc->fetch_row($batchR);
                $batchW['startDate'] = date('Y-m-d', strtotime($batchW['startDate']));
                $batchW['endDate'] = date('Y-m-d', strtotime($batchW['endDate']));
                $ret .= ' (' . $batchW['batchName'] . ' ' 
                    . $batchW['startDate'] . ' - ' . $batchW['endDate'] 
                    . ')';
            } else {
                $ret .= ' (Unknown batch)';
            }
            $ret .= '</span>';
        }
        $ret .= '</p>';

        $supersP = $dbc->prepare('
            SELECT s.superID,
                n.super_name
            FROM superdepts AS s
                INNER JOIN superDeptNames AS n ON s.superID=n.superID
            WHERE s.dept_ID=?
            ORDER BY s.superID');
        $supersR = $dbc->execute($supersP, array($product->department()));
        $master = false;
        $ret .= '<p><strong>Super(s)</strong>: ';
        while ($supersW = $dbc->fetch_row($supersR)) {
            if ($master === false) {
                $master = $supersW['superID'];
                $ret .= '<em>' . $supersW['super_name'] . '</em>; ';
            } else {
                $ret .= $supersW['super_name'] . '; ';
            }
        }
        $ret .= '</p>';

        $dept = new DepartmentsModel($dbc);
        $dept->dept_no($product->department());
        $dept->load();
        $sub = new SubDeptsModel($dbc);
        $sub->subdept_no($product->subdept());
        $sub->load();
        $ret .= sprintf('<p><strong>Dept</strong>: %d %s, <strong>SubDept</strong>: %d %s</p>',
            $dept->dept_no(), $dept->dept_name(),
            $sub->subdept_no(), $sub->subdept_name());

        $ret .= '<p><form class="form-inline" method="get">';
        $tags = new ShelftagsModel($dbc);
        $tags->upc($upc);
        $queued = $tags->find('id');
        $queues = new ShelfTagQueuesModel($dbc);
        $verb = 'Queue';
        if (count($queued) > 0) {
            if ($tags->id() == 0) {
                $ret .= 'Tags queued for Default';
            } else {
                $queues->shelfTagQueueID($tags->id());
                $queues->load();
                $ret .= 'Tags queued for ' . $queues->description();
            }
            $verb = 'Requeue';
        } else {
            $ret .= 'No tags queued';
        }
        $ret .= '<input type="hidden" name="upc" value="' . $upc . '" />
            <button class="btn btn-default" type="submit">' . $verb . ' Tags</button>
            for <select name="tagID" class="form-control">';
        $ret .= $queues->toOptions($master);
        $ret .= '</select></form></p>';

        if (FannieAuth::validateUserQuiet('pricechange') || FannieAuth::validateUserQuiet('audited_pricechange')) {
            $ret .= '<p><a href="../ItemEditorPage.php?searchupc=' . $this->id . '"
                class="btn btn-default">Edit This Item</a></p>';
        }

        $ret .= '</div></div>';

        return $ret;
    }

    public function get_view()
    {
        $this->add_script('../autocomplete.js');
        $this->add_onload_command("bindAutoComplete('#upc', '../../ws/', 'item');\n");
        $this->add_onload_command("\$('#upc').focus();\n");
        return '<form action="' . $_SERVER['PHP_SELF'] . '" method="get">
            <div class="form-group form-inline">
                <label>UPC</label>
                <input type="text" name="id" id="upc" class="form-control" />
                <button type="submit" class="btn btn-default">Check Item</button>
            </div>
            </form>';
    }

    public function helpContent()
    {
        return '<p>
            The status check shows a brief summary of
            a product\'s information in POS. It can be used
            to verify pricing and queue up new shelf tags.
            This particular page should scale to a mobile
            device where as the full item editor often
            does not fit well on small screens.
            </p>';
    }
}

FannieDispatch::conditionalExec();

