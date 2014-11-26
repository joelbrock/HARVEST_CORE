<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op, Duluth, MN

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

include_once(dirname(__FILE__).'/../../config.php');
include_once(dirname(__FILE__).'/../../classlib2.0/FannieAPI.php');

class BaseItemModule extends ItemModule {

    public function showEditForm($upc, $display_mode=1, $expand_mode=1)
    {
        global $FANNIE_URL, $FANNIE_PRODUCT_MODULES;
        $upc = BarcodeLib::padUPC($upc);

        $ret = '<div id="BaseItemFieldset" class="panel panel-default">';

        $dbc = $this->db();
        $p = $dbc->prepare_statement('SELECT
                                        p.description,
                                        p.pricemethod,
                                        p.normal_price,
                                        p.size,
                                        p.unitofmeasure,
                                        p.modified,
                                        p.special_price,
                                        p.end_date,
                                        p.subdept,
                                        p.department,
                                        p.tax,
                                        p.foodstamp,
                                        p.scale,
                                        p.qttyEnforced,
                                        p.discount,
                                        p.brand AS manufacturer,
                                        x.distributor,
                                        u.description as ldesc,
                                        p.default_vendor_id
                                      FROM products AS p 
                                        LEFT JOIN prodExtra AS x ON p.upc=x.upc 
                                        LEFT JOIN productUser AS u ON p.upc=u.upc 
                                      WHERE p.upc=?');
        $r = $dbc->exec_statement($p,array($upc));
        $rowItem = array();
        $prevUPC = False;
        $nextUPC = False;
        $likeCode = False;
        if ($dbc->num_rows($r) > 0) {
            //existing item
            $rowItem = $dbc->fetch_row($r);

            /**
              Lookup default vendor & normalize
            */
            $product = new ProductsModel($dbc);
            $product->upc($upc);
            $product->load();
            $vendor = new VendorsModel($dbc);
            $vendor->vendorID($product->default_vendor_id());
            if ($vendor->load()) {
                $rowItem['distributor'] = $vendor->vendorName();
            }

            /* find previous and next items in department */
            $pnP = $dbc->prepare_statement('SELECT upc FROM products WHERE department=? ORDER BY upc');
            $pnR = $dbc->exec_statement($pnP,array($product->department()));
            $passed_it = False;
            while($pnW = $dbc->fetch_row($pnR)){
                if (!$passed_it && $upc != $pnW[0])
                    $prevUPC = $pnW[0];
                else if (!$passed_it && $upc == $pnW[0])
                    $passed_it = True;
                else if ($passed_it){
                    $nextUPC = $pnW[0];
                    break;      
                }
            }

            $lcP = $dbc->prepare_statement('SELECT likeCode FROM upcLike WHERE upc=?');
            $lcR = $dbc->exec_statement($lcP,array($upc));
            if ($dbc->num_rows($lcR) > 0) {
                $lcW = $dbc->fetch_row($lcR);
                $likeCode = $lcW['likeCode'];
            }
        } else {
            // default values for form fields
            $rowItem = array(
                'description' => '',
                'normal_price' => 0,
                'pricemethod' => 0,
                'size' => '',
                'unitofmeasure' => '',
                'modified' => '',
                'ledesc' => '',
                'manufacturer' => '',
                'distributor' => '',
                'default_vendor_id' => 0,
                'department' => 0,
                'subdept' => 0,
                'tax' => 0,
                'foodstamp' => 0,
                'scale' => 0,
                'qttyEnforced' => 0,
                'discount' => 1,
            );

            /**
              Check for entries in the vendorItems table to prepopulate
              fields for the new item
            */
            $vendorP = "SELECT description,brand as manufacturer,cost,
                vendorName as distributor,margin,i.vendorID,srp,
                i.vendorID as default_vendor_id
                FROM vendorItems AS i LEFT JOIN vendors AS v ON i.vendorID=v.vendorID
                LEFT JOIN vendorDepartments AS d ON i.vendorDept=d.deptID
                LEFT JOIN vendorSRPs AS s ON s.upc=i.upc AND s.vendorID=i.vendorID
                WHERE i.upc=?";
            $args = array($upc);
            $vID = FormLib::get_form_value('vid','');
            if ($vID !== ''){
                $vendorP .= ' AND i.vendorID=?';
                $args[] = $vID;
            }
            $vendorP = $dbc->prepare_statement($vendorP);
            $vendorR = $dbc->exec_statement($vendorP,$args);
            
            if ($dbc->num_rows($vendorR) > 0){
                $v = $dbc->fetch_row($vendorR);
                $ret .= "<br /><i>This product is in the ".$v['distributor']." catalog. Values have
                    been filled in where possible</i><br />";
                $rowItem['description'] = $v['description'];
                $rowItem['manufacturer'] = $v['manufacturer'];
                $rowItem['cost'] = $v['cost'];
                $rowItem['distributor'] = $v['distributor'];
                $rowItem['normal_price'] = $v['srp'];

                while($v = $dbc->fetch_row($vendorR)){
                    printf('This product is also in <a href="?searchupc=%s&vid=%d">%s</a><br />',
                        $upc,$v['vendorID'],$v['distributor']);
                }
            }

            /**
              Look for items with a similar UPC to guess what
              department this item goes in. If found, use 
              department settings to fill in some defaults
            */
            $rowItem['department'] = 0;
            $search = substr($upc,0,12);
            $searchP = $dbc->prepare_statement('SELECT department FROM products WHERE upc LIKE ?');
            while(strlen($search) >= 8){
                $searchR = $dbc->exec_statement($searchP,array($search.'%'));
                if ($dbc->num_rows($searchR) > 0){
                    $rowItem['department'] = array_pop($dbc->fetch_row($searchR));
                    $settingP = $dbc->prepare_statement('SELECT dept_tax,dept_fs,dept_discount
                                FROM departments WHERE dept_no=?');
                    $settingR = $dbc->exec_statement($settingP,array($rowItem['department']));
                    if ($dbc->num_rows($settingR) > 0){
                        $d = $dbc->fetch_row($settingR);
                        $rowItem['tax'] = $d['dept_tax'];
                        $rowItem['foodstamp'] = $d['dept_fs'];
                        $rowItem['discount'] = $d['dept_discount'];
                    }
                    break;
                }
                $search = substr($search,0,strlen($search)-1);
            }
        }

        $ret .= '
            <div class="panel-heading">
                <strong>UPC</strong>
                <span class="alert-danger">' . $upc . '</span>
                <input type="hidden" id="upc" name="upc" value="' . $upc . '" />';
        if ($prevUPC) {
            $ret .= ' <a class="small" href="ItemEditor.php?searchupc=' . $prevUPC . '">Previous</a>';
        }
        if ($nextUPC) {
            $ret .= ' <a class="small" href="ItemEditor.php?searchupc=' . $nextUPC . '">Next</a>';
        }
        $ret .= '</div>'; // end panel-heading

        $ret .= '<div class="panel-body">';

        if ($dbc->num_rows($r) == 0) {
            // new item
            $ret .= "<div class=\"alert alert-warning\">Item not found.  You are creating a new one.</div>";
        }

        // system for store-level records not refined yet; might go here
        $ret .= '<input type="hidden" name="store_id" value="0" />';

        $limit = 35 - strlen(isset($rowItem['description'])?$rowItem['description']:'');
        $ret .= 
            '<div class="form-group form-inline">
                <label>Description</label>
                <div class="input-group">
                    <input type="text" maxlength="30" class="form-control"
                        name="descript" id="descript" value="' . $rowItem['description'] . '"
                        onkeyup="$(\'#dcounter\').html(35-(this.value.length));" />
                    <span id="dcounter" class="input-group-addon">' . $limit . '</span>
                </div>
                <label>Price</label>
                <div class="input-group">
                    <span class="input-group-addon">$</span>
                    <input type="text" id="price" name="price" class="form-control"
                        value="' . sprintf('%.2f', $rowItem['normal_price']) . '" />
                </div>
            </div>';

        // no need to display this field twice
        if (!isset($FANNIE_PRODUCT_MODULES['ProdUserModule'])) {
            $ret .= '
                <div class="form-group form-inline">
                    <label>Long Desc.</label>
                    <input type="text" size="60" name="puser_description"
                        value="' . $rowItem['ldesc'] . '" class="form-control" />
                </div>';
        }

        $ret .= '
            <div class="form-group form-inline">
                <label>Brand</label>
                <input type="text" name="manufacturer" class="form-control"
                    value="' . $rowItem['manufacturer'] . '" id="brand-field" />';
        /**
          Check products.default_vendor_id to see if it is a 
          valid reference to the vendors table
        */
        $normalizedVendorID = false;
        if (isset($rowItem['default_vendor_id']) && $rowItem['default_vendor_id'] > 0) {
            $normalizedVendor = new VendorsModel($dbc);
            $normalizedVendor->vendorID($rowItem['default_vendor_id']);
            if ($normalizedVendor->load()) {
                $normalizedVendorID = $normalizedVendor->vendorID();
            }
        }
        /**
          Use a <select> box if the current vendor corresponds to a valid
          entry OR if no vendor entry exists. Only allow free text
          if it's already in place
        */
        $ret .= ' <label>Vendor</label> ';
        if ($normalizedVendorID || empty($rowItem['distributor'])) {
            $ret .= '<select name="distributor" class="chosen-select form-control"
                        id="vendor_field">';
            $ret .= '<option value="0"></option>';
            $vendors = new VendorsModel($dbc);
            foreach ($vendors->find('vendorName') as $v) {
                $ret .= sprintf('<option %s>%s</option>',
                            ($v->vendorID() == $normalizedVendorID ? 'selected' : ''),
                            $v->vendorName());
            }
            $ret .= '</select>';
        } else {
            $ret .= "<input type=text name=distributor size=8 value=\""
                .(isset($rowItem['distributor'])?$rowItem['distributor']:"")
                ."\" id=\"vendor_field\" class=\"form-control\" />";
        }
        $ret .= ' <button type="button" id="newVendorButton"
                    class="btn btn-default"><span class="glyphicon glyphicon-plus"></span></button>';
        $ret .= '</div>'; // end row

        $ret .= '<div id="newVendorDialog" title="Create new Vendor" class="collapse">';
        $ret .= '<fieldset>';
        $ret .= '<label for="newVendorName">Vendor Name</label>';
        $ret .= '<input type="text" name="newVendorName" id="newVendorName" class="form-control" />';
        $ret .= '</fieldset>';
        $ret .= '</div>';

        if (isset($rowItem['special_price']) && $rowItem['special_price'] <> 0){
            /* show sale info */
            $batchP = $dbc->prepare_statement("
                SELECT b.batchName, 
                    b.batchID 
                FROM batches AS b 
                    LEFT JOIN batchList as l on b.batchID=l.batchID 
                WHERE '" . date('Y-m-d') . "' BETWEEN b.startDate AND b.endDate 
                    AND (l.upc=? OR l.upc=?)"
            );
            $batchR = $dbc->exec_statement($batchP,array($upc,'LC'.$likeCode));
            $batch = array('batchID'=>0, 'batchName'=>"Unknown");
            if ($dbc->num_rows($batchR) > 0) {
                $batch = $dbc->fetch_row($batchR);
            }

            $ret .= '<div class="alert-success">';
            $ret .= sprintf("<strong>Sale Price:</strong>
                %.2f (<em>Batch: <a href=\"%sbatches/newbatch/BatchManagementTool.php?startAt=%d\">%s</a></em>)",
                $rowItem['special_price'], $FANNIE_URL, $batch['batchID'], $batch['batchName']);
            list($date,$time) = explode(' ',$rowItem['end_date']);
            $ret .= "<strong>End Date:</strong>
                    $date 
                    (<a href=\"EndItemSale.php?id=$upc\">Unsale Now</a>)";
            $ret .= '</div>';
        }

        /*
        $ret .= '<tr><th>Dept</th><th>Tax</th><th><label for="FS">FS</label></th>
            <th><label for="scale-checkbox">Scale</label>'.\COREPOS\Fannie\API\lib\FannieHelp::ToolTip('Item sold by weight').'</th>
            <th><label for="qty-checkbox">QtyFrc</label>'.\COREPOS\Fannie\API\lib\FannieHelp::ToolTip('Cashier must enter quantity').'</th>
            <th><label for="no-disc-checkbox">NoDisc</label>'.\COREPOS\Fannie\API\lib\FannieHelp::ToolTip('Item not subject to % discount').'</th></tr>';
        */

        $depts = array();
        $subs = array();
        $p = $dbc->prepare_statement('SELECT dept_no,dept_name,subdept_no,subdept_name,dept_ID 
                FROM departments AS d
                LEFT JOIN subdepts AS s ON d.dept_no=s.dept_ID
                ORDER BY d.dept_no, s.subdept_name');
        $r = $dbc->exec_statement($p);
        while ($w = $dbc->fetch_row($r)) {
            if (!isset($depts[$w['dept_no']])) $depts[$w['dept_no']] = $w['dept_name'];
            if ($w['subdept_no'] == '') continue;
            if (!isset($subs[$w['dept_ID']]))
                $subs[$w['dept_ID']] = '';
            $subs[$w['dept_ID']] .= sprintf('<option %s value="%d">%d %s</option>',
                    ($w['subdept_no'] == $rowItem['subdept'] ? 'selected':''),
                    $w['subdept_no'],$w['subdept_no'],$w['subdept_name']);
        }

        $ret .= '
            <div class="form-group form-inline">
                <label>Dept</label>
                <select name="department" id="department" 
                    class="form-control" onchange="chainSelects(this.value);">';
        foreach ($depts as $id => $name){
            $ret .= sprintf('<option %s value="%d">%d %s</option>',
                    ($id == $rowItem['department'] ? 'selected':''),
                    $id,$id,$name);
        }
        $ret .= '</select>';
        $ret .= '<select name="subdept" id="subdept" class="form-control">';
        $ret .= isset($subs[$rowItem['department']]) ? $subs[$rowItem['department']] : '<option value="0">None</option>';
        $ret .= '</select>';

        $taxQ = $dbc->prepare_statement('SELECT id,description FROM taxrates ORDER BY id');
        $taxR = $dbc->exec_statement($taxQ);
        $rates = array();
        while ($taxW = $dbc->fetch_row($taxR)) {
            array_push($rates,array($taxW[0],$taxW[1]));
        }
        array_push($rates,array("0","NoTax"));
        $ret .= ' <label>Tax</label>
            <select name="tax" id="tax" class="form-control">';
        foreach($rates as $r){
            $ret .= sprintf('<option %s value="%d">%s</option>',
                (isset($rowItem['tax'])&&$rowItem['tax']==$r[0]?'selected':''),
                $r[0],$r[1]);
        }
        $ret .= '</select></div>';

        $ret .= '
            <div class="form-group form-inline">
                <label>FS
                <input type="checkbox" value="1" name="FS" id="FS"
                    ' . ($rowItem['foodstamp'] == 1 ? 'checked' : '') . ' />
                </label>
                |
                <label>Scale
                <input type="checkbox" value="1" name="Scale" id="scale-checkbox"
                    ' . ($rowItem['scale'] == 1 ? 'checked' : '') . ' />
                </label>
                |
                <label>QtyFrc
                <input type="checkbox" value="1" name="QtyFrc" id="qty-checkbox"
                    ' . ($rowItem['qttyEnforced'] == 1 ? 'checked' : '') . ' />
                </label>
                |
                <label>NoDisc
                <input type="checkbox" value="1" name="NoDisc" id="no-disc-checkbox"
                    ' . ($rowItem['discount'] == 0 ? 'checked' : '') . ' />
                </label>
                |
                <label style="color:darkmagenta;">Last modified</label>
                <span style="color:darkmagenta;">'. $rowItem['modified'] . '</span>
            </div>';

        $ret .= '
            <div class="form-group form-inline">
                <label>Package Size</label>
                <input type="text" name="size" class="form-control"
                    value="' . $rowItem['size'] . '" />
                <label>Unit of measure</label>
                <input type="text" name="unitm" class="form-control"
                    value="' . $rowItem['unitofmeasure'] . '" />
            </div>';

        $ret .= '</div>'; // end panel-body
        $ret .= '</div>'; // end panel

        return $ret;
    }

    public function getFormJavascript($upc)
    {
        global $FANNIE_URL;
        $dbc = $this->db();

        $p = $dbc->prepare_statement('SELECT dept_no,dept_name,subdept_no,subdept_name,dept_ID 
                FROM departments AS d
                LEFT JOIN subdepts AS s ON d.dept_no=s.dept_ID
                ORDER BY d.dept_no, s.subdept_name');
        $r = $dbc->exec_statement($p);
        $subs = array();
        while($w = $dbc->fetch_row($r)){
            if ($w['subdept_no'] == '') continue;
            if (!isset($subs[$w['dept_ID']]))
                $subs[$w['dept_ID']] = '';
            $subs[$w['dept_ID']] .= sprintf('<option %s value="%d">%d %s</option>',
                    ($w['subdept_no'] == $rowItem['subdept'] ? 'selected':''),
                    $w['subdept_no'],$w['subdept_no'],$w['subdept_name']);
        }

        $json = count($subs) == 0 ? '{}' : json_encode($subs);
        ob_start();
        ?>
        function chainSelects(val){
            var lookupTable = <?php echo $json; ?>;
            if (val in lookupTable)
                $('#subdept').html(lookupTable[val]);
            else
                $('#subdept').html('<option value=0>None</option>');
            $.ajax({
                url: '<?php echo $FANNIE_URL; ?>item/modules/BaseItemModule.php',
                data: 'dept_defaults='+val,
                dataType: 'json',
                cache: false,
                success: function(data){
                    if (data.tax)
                        $('#tax').val(data.tax);
                    if (data.fs)
                        $('#FS').attr('checked','checked');
                    else{
                        $('#FS').removeAttr('checked');
                    }
                    if (data.nodisc)
                        $('#NoDisc').attr('checked','checked');
                    else
                        $('#NoDisc').removeAttr('checked');
                }

            });
        }
        function addVendorDialog()
        {
            var v_dialog = $('#newVendorDialog').dialog({
                autoOpen: false,
                height: 300,
                width: 300,
                modal: true,
                buttons: {
                    "Create Vendor" : addVendorCallback,
                    "Cancel" : function() {
                        v_dialog.dialog("close");
                    }
                },
                close: function() {
                    $('#newVendorDialog :input').each(function(){
                        $(this).val('');
                    });
                    $('#newVendorAlert').html('');
                }
            });

            $('#newVendorDialog :input').keyup(function(e) {
                if (e.which == 13) {
                    addVendorCallback();
                }
            });

            $('#newVendorButton').click(function(e){
                e.preventDefault();
                v_dialog.dialog("open"); 
            });

            function addVendorCallback()
            {
                var data = 'action=addVendor';
                data += '&' + $('#newVendorDialog :input').serialize();
                $.ajax({
                    url: '<?php echo $FANNIE_URL; ?>item/modules/BaseItemModule.php',
                    data: data,
                    dataType: 'json',
                    error: function() {
                        $('#newVendorAlert').html('Communication error');
                    },
                    success: function(resp){
                        if (resp.vendorID) {
                            v_dialog.dialog("close");
                            var v_field = $('#vendor_field');
                            if (v_field.hasClass('chosen-select')) {
                                var newopt = $('<option/>').attr('id', resp.vendorID).html(resp.vendorName);
                                v_field.append(newopt);
                            }
                            $('#vendor_field').val(resp.vendorName);
                            if (v_field.hasClass('chosen-select')) {
                                v_field.trigger('chosen:updated');
                            }
                        } else if (resp.error) {
                            $('#newVendorAlert').html(resp.error);
                        } else {
                            $('#newVendorAlert').html('Invalid response');
                        }
                    }
                });
            }

        }
        <?php

        return ob_get_clean();
    }

    function SaveFormData($upc){
        global $FANNIE_PRODUCT_MODULES;
        $upc = BarcodeLib::padUPC($upc);
        $dbc = $this->db();

        $model = new ProductsModel($dbc);
        $model->upc($upc);
        if (!$model->load()) {
            // fully init new record
            $model->special_price(0);
            $model->specialpricemethod(0);
            $model->specialquantity(0);
            $model->specialgroupprice(0);
            $model->advertised(0);
            $model->tareweight(0);
            $model->start_date('');
            $model->end_date('');
            $model->discounttype(0);
            $model->wicable(0);
            $model->scaleprice(0);
            $model->inUse(1);
        }
        $model->tax(FormLib::get_form_value('tax',0));
        $model->foodstamp(FormLib::get_form_value('FS',0));
        $model->scale(FormLib::get_form_value('Scale',0));
        $model->qttyEnforced(FormLib::get_form_value('QtyFrc',0));
        $model->discount(FormLib::get_form_value('NoDisc',1));
        $model->normal_price(FormLib::get_form_value('price',0.00));
        $model->description(str_replace("'", '', FormLib::get_form_value('descript','')));
        $model->brand(str_replace("'", '', FormLib::get('manufacturer', '')));
        $model->pricemethod(0);
        $model->groupprice(0.00);
        $model->quantity(0);
        $model->department(FormLib::get_form_value('department',0));
        $model->size(FormLib::get_form_value('size',''));
        $model->modified(date('Y-m-d H:i:s'));
        $model->unitofmeasure(FormLib::get_form_value('unitm',''));
        $model->subdept(FormLib::get_form_value('subdept',0));

        /* turn on volume pricing if specified, but don't
           alter pricemethod if it's already non-zero */
        $doVol = FormLib::get_form_value('doVolume',False);
        $vprice = FormLib::get_form_value('vol_price','');
        $vqty = FormLib::get_form_value('vol_qtty','');
        if ($doVol !== false && is_numeric($vprice) && is_numeric($vqty)) {
            $model->pricemethod(FormLib::get_form_value('pricemethod',0));
            if ($model->pricemethod() == 0) {
                $model->pricemethod(2);
            }
            $model->groupprice($vprice);
            $model->quantity($vqty);
        }

        // lookup vendorID by name
        $vendorID = 0;
        $vendor = new VendorsModel($dbc);
        $vendor->vendorName(FormLib::get('distributor'));
        foreach($vendor->find('vendorID') as $obj) {
            $vendorID = $obj->vendorID();
            break;
        }
        $model->default_vendor_id($vendorID);

        $model->save();

        if ($dbc->table_exists('prodExtra')){
            $arr = array();
            $arr['manufacturer'] = $dbc->escape(str_replace("'",'',FormLib::get_form_value('manufacturer')));
            $arr['distributor'] = $dbc->escape(str_replace("'",'',FormLib::get_form_value('distributor')));
            $arr['location'] = 0;

            $checkP = $dbc->prepare_statement('SELECT upc FROM prodExtra WHERE upc=?');
            $checkR = $dbc->exec_statement($checkP,array($upc));
            if ($dbc->num_rows($checkR) == 0){
                // if prodExtra record doesn't exist, needs more values
                $arr['upc'] = $dbc->escape($upc);
                $arr['variable_pricing'] = 0;
                $arr['margin'] = 0;
                $arr['case_quantity'] = "''";
                $arr['case_cost'] = 0.00;
                $arr['case_info'] = "''";
                $dbc->smart_insert('prodExtra',$arr);
            }
            else {
                $dbc->smart_update('prodExtra',$arr,"upc='$upc'");
            }
        }

        if (!isset($FANNIE_PRODUCT_MODULES['ProdUserModule'])) {
            if ($dbc->table_exists('productUser')){
                $ldesc = FormLib::get_form_value('puser_description');
                $model = new ProductUserModel($dbc);
                $model->upc($upc);
                $model->description($ldesc);
                $model->save();
            }
        }
    }

    function AjaxCallback()
    {
        $db = $this->db();
        $json = array();
        if (FormLib::get('action') == 'addVendor') {
            $name = FormLib::get('newVendorName');
            if (empty($name)) {
                $json['error'] = 'Name is required';
            } else {
                $vendor = new VendorsModel($db);
                $vendor->vendorName($name);
                if (count($vendor->find()) > 0) {
                    $json['error'] = 'Vendor "' . $name . '" already exists';
                } else {
                    $max = $db->query('SELECT MAX(vendorID) AS max
                                       FROM vendors');
                    $newID = 1;
                    if ($max && $maxW = $db->fetch_row($max)) {
                        $newID = ((int)$maxW['max']) + 1;
                    }
                    $vendor->vendorID($newID);
                    $vendor->save();
                    $json['vendorID'] = $newID;
                    $json['vendorName'] = $name;
                }
            }
        } else {
            $json = array('tax'=>0,'fs'=>False,'nodisc'=>False);
            $dept = FormLib::get_form_value('dept_defaults','');
            $p = $db->prepare_statement('SELECT dept_tax,dept_fs,dept_discount
                    FROM departments WHERE dept_no=?');
            $r = $db->exec_statement($p,array($dept));
            if ($db->num_rows($r)) {
                $w = $db->fetch_row($r);
                $json['tax'] = $w['dept_tax'];
                if ($w['dept_fs'] == 1) $json['fs'] = True;
                if ($w['dept_discount'] == 0) $json['nodisc'] = True;
            }
        }

        echo json_encode($json);
    }

    function summaryRows($upc)
    {
        global $FANNIE_OP_DB;
        $dbc = $this->db();

        $model = new ProductsModel($dbc);
        $model->upc($upc);
        if ($model->load()) {
            $row1 = '<th>UPC</th>
                <td><a href="ItemEditorPage.php?searchupc=' . $upc . '">' . $upc . '</td>
                <td>
                    <a class="iframe fancyboxLink" href="addShelfTag.php?upc='.$upc.'" title="Create Shelf Tag">Shelf Tag</a>
                </td>';
            $row2 = '<th>Description</th><td>' . $model->description() . '</td>
                     <th>Price</th><td>$' . $model->normal_price() . '</td>';

            return array($row1, $row2);
        } else {
            return array('<td colspan="4">Error saving. <a href="ItemEditorPage.php?searchupc=' . $upc . '">Try Again</a>?</td>');
        }
    }
}

/**
  This form does some fancy tricks via AJAX calls. This block
  ensures the AJAX functionality only runs when the script
  is accessed via the browser and not when it's included in
  another PHP script.
*/
if (basename($_SERVER['SCRIPT_NAME']) == basename(__FILE__)){
    $obj = new BaseItemModule();
    $obj->AjaxCallback();   
}

?>
