<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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

    DHermann test
*********************************************************************************/
use COREPOS\pos\lib\FormLib;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\install\conf\Conf;
use COREPOS\pos\install\conf\FormFactory;
use COREPOS\pos\install\db\Creator;
use COREPOS\pos\install\InstallUtilities;

ini_set('display_errors','1');

include(realpath(dirname(__FILE__).'/../lib/AutoLoader.php'));
AutoLoader::loadMap();
$form = new FormFactory(null);
?>
<html>
<head>
<title>IT CORE Lane Installation: Necessities</title>
<style type="text/css">
body {
    line-height: 1.5em;
}
</style>
<script type="text/javascript" src="../js/<?php echo MiscLib::jqueryFile(); ?>"></script>
</head>
<body>
<?php include('tabs.php'); ?>
<div id="wrapper">
<h2>IT CORE Lane Installation: Necessities</h2>

<form action=index.php method=post>

<div class="alert"><?php Conf::checkWritable('../ini.json', false, 'JSON'); ?></div>
<div class="alert"><?php Conf::checkWritable('../ini.php', true, 'PHP'); ?></div>

<?php echo _('PHP is running as: ') . Conf::whoami(); ?><br />
<?php
if (!function_exists("socket_create")){
    echo _('<b>Warning</b>: PHP socket extension is not enabled. NewMagellan will not work quite right');
}
?>
<br />
<table id="install" border=0 cellspacing=0 cellpadding=4>
<?php 
$lane_id_is_mapped = false;
$store_id_is_mapped = false;
if (is_array(CoreLocal::get('LaneMap'))) {
    $my_ips = MiscLib::getAllIPs();
    $map = CoreLocal::get('LaneMap');
    foreach ($my_ips as $ip) {
        if (!isset($map[$ip])) {
            continue;
        }
        if (!is_array($map[$ip])) {
            echo '<tr><td colspan="3">' . _('Error: invalid entry for ') . $ip . '</td></tr>';
        } elseif (!isset($map[$ip]['register_id'])) {
            echo '<tr><td colspan="3">' . _('Error: missing register_id for ') . $ip . '</td></tr>';
        } elseif (!isset($map[$ip]['store_id'])) {
            echo '<tr><td colspan="3">' . _('Error: missing store_id for ') . $ip . '</td></tr>';
        } else {
            if (CoreLocal::get('store_id') === '') {
                // no store_id set. assign based on IP
                CoreLocal::set('store_id', $map[$ip]['store_id']);
                $store_id_is_mapped = true;
            } else if (CoreLocal::get('store_id') != $map[$ip]['store_id']) {
                echo '<tr><td colspan="3">' . _('Warning: store_id is set to ')
                    . CoreLocal::get('store_id') . _('. Based on IP ') . $ip
                    . _(' it should be set to ') . $map[$ip]['store_id'] . '</td></tr>';
            } else {
                $store_id_is_mapped = true;
            }
            if (CoreLocal::get('laneno') === '') {
                // no store_id set. assign based on IP
                CoreLocal::set('laneno', $map[$ip]['register_id']);
                $lane_id_is_mapped = true;
            } else if (CoreLocal::get('laneno') != $map[$ip]['register_id']) {
                echo '<tr><td colspan="3">' . _('Warning: register_id is set to ')
                    . CoreLocal::get('laneno') . _('. Based on IP ') . $ip
                    . _(' it should be set to ') . $map[$ip]['register_id'] . '</td></tr>';
            } else {
                // map entry matches
                // should maybe delete ini entry if it exists?
                $lane_id_is_mapped = true;
            }

            // use first matching IP
            break;
        }
    }
}
?>
<tr>
    <td style="width:30%;"><?php echo _('Lane number*:'); ?></td>
    <?php if (CoreLocal::get('laneno') !== '' && CoreLocal::get('laneno') == 0) { ?>
    <td>0 (Zero)</td>
    <?php } elseif ($lane_id_is_mapped) { ?>
    <td><?php echo CoreLocal::get('laneno') . _(' (assigned by IP; cannot be edited)'); ?></td>
    <?php } else { ?>
    <td><?php echo $form->textField('laneno', 99, Conf::INI_SETTING, false); ?></td>
    <?php } ?>
    <td colspan=2>
        <div class="noteTxt">
        <?php if (CoreLocal::get('laneno') == 99) { 
        echo _('Lane #99 is used strictly for testing. Transaction data generated by lane #99 is
            automatically excluded from sales reports.');
        } ?>
        </div>
    </td>
</tr>
<tr>
    <td><?php echo _('Store number*:'); ?></td>
    <?php if (CoreLocal::get('store_id') !== '' && CoreLocal::get('store_id') == 0) { ?>
    <td>0 (Zero)</td>
    <?php } elseif ($store_id_is_mapped) { ?>
    <td><?php echo CoreLocal::get('store_id') . _(' (assigned by IP; cannot be edited)'); ?></td>
    <?php } else { ?>
    <td><?php echo $form->textField('store_id', 1, Conf::INI_SETTING, false); ?></td>
    <?php } ?>
</tr>
<?php if (CoreLocal::get('laneno') === '' || CoreLocal::get('laneno') != 0) { ?>
<tr>
    <td colspan=2 class="tblheader">
    <h3><?php echo _('Database set up'); ?></h3>
    </td>
</tr>
<tr>
    <td><?php echo _('Lane database host*: '); ?></td>
    <td><?php echo $form->textField('localhost', '127.0.0.1', Conf::INI_SETTING); ?></td>
</tr>
<tr>
    <td><?php echo _('Lane database type*: '); ?></td>
    <td>
    <?php
    $db_opts = \COREPOS\common\sql\Lib::getDrivers();
    $db_keys = array_keys($db_opts);
    $default = $db_opts[$db_keys[0]];
    echo $form->selectField('DBMS', $db_opts, $default, Conf::INI_SETTING);
    ?>
    </td>
</tr>
<tr>
    <td><?php echo _('Lane user name*: '); ?></td>
    <td><?php echo $form->textField('localUser', 'root', Conf::INI_SETTING); ?></td>
</tr>
<tr>
    <td><?php echo _('Lane password*: '); ?></td>
    <td>
    <?php
    echo $form->textField('localPass', '', Conf::INI_SETTING, true, array('type'=>'password'));
    ?>
    </td>
</tr>
<tr>
    <td><?php echo _('Lane operational DB*: '); ?></td>
    <td><?php echo $form->textField('pDatabase', 'opdata', Conf::INI_SETTING); ?></td>
</tr>
<tr>
    <td colspan=2>
<div class="noteTxt">
<?php echo _('Testing operational DB Connection:'); ?>
<?php
$gotDBs = 0;
if (CoreLocal::get("DBMS") == "mysql")
    $val = ini_set('mysql.connect_timeout',5);

$sql = InstallUtilities::dbTestConnect(CoreLocal::get('localhost'),
        CoreLocal::get('DBMS'),
        CoreLocal::get('pDatabase'),
        CoreLocal::get('localUser'),
        CoreLocal::get('localPass'));
if ($sql === False) {
    echo "<span class='fail'>" . _('Failed') . "</span>";
    echo '<div class="db_hints" style="margin-left:25px;">';
    if (!function_exists('socket_create')){
        echo _('<i>Try enabling PHP\'s socket extension in php.ini for better diagnostics</i>');
    }
    elseif (@MiscLib::pingport(CoreLocal::get('localhost'),CoreLocal::get('DBMS'))){
        printf(_('<i>Database found at %s. Verify username and password
            and/or database account permissions.</i>'), CoreLocal::get('localhost'));
    }
    else {
        printf(_('<i>Database does not appear to be listening for connections on 
            %s. Verify host is correct, database is running and
            firewall is allowing connections.</i>'), CoreLocal::get('localhost'));
    }
    echo '</div>';
} else {
    echo "<span class='success'>" . _('Succeeded') . "</span><br />";
    //echo "<textarea rows=3 cols=80>";
    $opErrors = Creator::createOpDBs($sql, CoreLocal::get('pDatabase'));
    $opErrors = array_filter($opErrors, function($x){ return $x['error'] != 0; });
    $gotDBs++;
    if (!empty($opErrors)){
        sqlErrorsToList($opErrors);
    }
}

$form->setDB($sql);

?>
</div> <!-- noteTxt -->
</td></tr>
<tr>
    <td><?php echo _('Lane transaction DB*: '); ?></td>
    <td><?php echo $form->textField('tDatabase', 'translog', Conf::INI_SETTING); ?></td>
</tr>
<tr>
    <td colspan=2>
<div class="noteTxt">
<?php 
echo _('Testing transactional DB connection:');
$sql = InstallUtilities::dbTestConnect(CoreLocal::get('localhost'),
        CoreLocal::get('DBMS'),
        CoreLocal::get('tDatabase'),
        CoreLocal::get('localUser'),
        CoreLocal::get('localPass'));
if ($sql === False ) {
    echo "<span class='fail'>" . _('Failed') . "</span>";
    echo '<div class="db_hints" style="margin-left:25px;">';
    echo _('<i>If both connections failed, see above. If just this one
        is failing, it\'s probably an issue of database user 
        permissions.</i>');
    echo '</div>';
} else {
    echo "<span class='success'>" . _('Succeeded') . "</span><br />";
    //echo "<textarea rows=3 cols=80>";
    

    /* Re-do tax rates here so changes affect the subsequent
     * ltt* view builds. 
     */
    if (is_array(FormLib::get('TAX_RATE')) && $sql->table_exists('taxrates')){
        $queries = array();
        $TAX_RATE = FormLib::get('TAX_RATE');
        $TAX_DESC = FormLib::get('TAX_DESC');
        for($i=0; $i<count($TAX_RATE); $i++){
            $rate = $TAX_RATE[$i];
            $desc = $TAX_DESC[$i];
            if(is_numeric($rate)){
                $desc = str_replace(" ","",$desc);
                $queries[] = sprintf("INSERT INTO taxrates VALUES 
                    (%d,%f,'%s')",$i+1,$rate,$desc);
            }
            else if ($rate != ""){
                printf(_("<br /><b>Error</b>: the given
                    tax rate, %s, doesn't seem to
                    be a number."), $rate);
            }
            $sql->query("TRUNCATE TABLE taxrates");
            foreach($queries as $q)
                $sql->query($q);
        }
    }

    $transErrors = Creator::createTransDBs($sql, CoreLocal::get('tDatabase'));
    $transErrors = array_filter($transErrors, function($x){ return $x['error'] != 0; });
    $gotDBs++;
    if (!empty($transErrors)){
        sqlErrorsToList($transErrors);
    }
    //echo "</textarea>";
}
?>
</div> <!-- noteTxt -->
</td>
</tr>
<?php } else { $gotDBs=2; } // end local lane db config that does not apply on lane#0 / server ?> 
<tr><td colspan="3">
<?php 
if ($gotDBs == 2 && CoreLocal::get('laneno') != 0) {
    InstallUtilities::validateConfiguration();
}
$form->setDB(InstallUtilities::dbOrFail(CoreLocal::get('pDatabase')));
?>
</td></tr>
<tr>
    <td><?php echo _('Server database host:'); ?></td>
    <td><?php echo $form->textField('mServer', '127.0.0.1'); ?></td>
</tr>
<tr>
    <td><?php echo _('Server database type:'); ?></td>
    <td>
    <?php
    $db_opts = \COREPOS\common\sql\Lib::getDrivers();
    $db_keys = array_keys($db_opts);
    $default = $db_opts[$db_keys[0]];
    echo $form->selectField('mDBMS', $db_opts, $default);
    ?>
    </td>
</tr>
<tr>
    <td><?php echo _('Server user name:'); ?></td>
    <td><?php echo $form->textField('mUser', 'root'); ?></td>
</tr>
<tr>
    <td><?php echo _('Server password:'); ?></td>
    <td>
    <?php
    echo $form->textField('mPass', '', Conf::EITHER_SETTING, true, array('type'=>'password'));
    ?>
    </td>
</tr>
<tr>
    <td><?php echo _('Server database name:'); ?></td>
    <td><?php echo $form->textField('mDatabase', 'core_trans'); ?></td>
</tr>
<tr>
    <td colspan=2>
<div class="noteTxt">
<?php echo _('Testing server connection:'); ?>
<?php
$sql = InstallUtilities::dbTestConnect(CoreLocal::get('mServer'),
        CoreLocal::get('mDBMS'),
        CoreLocal::get('mDatabase'),
        CoreLocal::get('mUser'),
        CoreLocal::get('mPass'));
if ($sql === False){
    echo "<span class='fail'>" . _('Failed') . "</span>";
    echo '<div class="db_hints" style="margin-left:25px;width:350px;">';
    if (!function_exists('socket_create')){
        echo _('<i>Try enabling PHP\'s socket extension in php.ini for better diagnostics</i>');
    }
    elseif (@MiscLib::pingport(CoreLocal::get('mServer'),CoreLocal::get('mDBMS'))){
        printf(_('<i>Database found at %s. Verify username and password
            and/or database account permissions.</i>'), CoreLocal::get('mServer'));
    }
    else {
        printf(_('<i>Database does not appear to be listening for connections on 
            %s. Verify host is correct, database is running and
            firewall is allowing connections.</i>'), CoreLocal::get('mServer'));
    }
    echo '</div>';
}
else {
    echo "<span class='success'>" . _('Succeeded') . "</span><br />";
    //echo "<textarea rows=3 cols=80>";
    $sErrors = Creator::createMinServer($sql, CoreLocal::get('mDatabase'));
    $sErrors = array_filter($sErrors, function($x){ return $x['error'] != 0; });
    if (!empty($sErrors)){
        sqlErrorsToList($sErrors);
    }
    //echo "</textarea>";
}
?>
</div>  <!-- noteTxt -->
</td></tr>
<tr>
    <td><?php echo _('Locale:'); ?></td>
    <td><?php echo $form->selectField('locale', Conf::getLocales(), 'en_US'); ?></td>
</tr>
<tr><td colspan=2 class="tblHeader">
<h3><?php echo _('Tax'); ?></h3></td></tr>
<tr><td colspan=2>
<p><i><?php echo _('Provided tax rates are used to create database views. As such,
descriptions should be DB-legal syntax (e.g., no spaces). A rate of
0% with ID 0 is automatically included. Enter exact values - e.g.,
0.05 to represent 5%.'); ?></i></p></td></tr>
<tr><td colspan=2>
<?php
$rates = array();
if ($gotDBs == 2) {
    $sql = new \COREPOS\pos\lib\SQLManager(CoreLocal::get('localhost'),
            CoreLocal::get('DBMS'),
            CoreLocal::get('tDatabase'),
            CoreLocal::get('localUser'),
            CoreLocal::get('localPass'));
    if (CoreLocal::get('laneno') == 0 && CoreLocal::get('laneno') !== '') {
        // server-side rate table is in op database
        $sql = new \COREPOS\pos\lib\SQLManager(CoreLocal::get('localhost'),
                CoreLocal::get('DBMS'),
                CoreLocal::get('pDatabase'),
                CoreLocal::get('localUser'),
                CoreLocal::get('localPass'));
    }
    if ($sql->table_exists('taxrates')) {
        $ratesR = $sql->query("SELECT id,rate,description FROM taxrates ORDER BY id");
        while($row=$sql->fetch_row($ratesR))
            $rates[] = array($row[0],$row[1],$row[2]);
    }
}
echo "<table><tr><th>" . _('ID') . '</th><th>' . _('Rate') . '</th><th>' . _('Description') . "</th></tr>";
foreach($rates as $rate){
    printf("<tr><td>%d</td><td><input type=text name=TAX_RATE[] value=\"%f\" /></td>
        <td><input type=text name=TAX_DESC[] value=\"%s\" /></td></tr>",
        $rate[0],$rate[1],$rate[2]);
}
printf("<tr><td>(Add)</td><td><input type=text name=TAX_RATE[] value=\"\" /></td>
    <td><input type=text name=TAX_DESC[] value=\"\" /></td></tr></table>");
?>
</td></tr><tr><td colspan=2 class="submitBtn">
<input type=submit value="<?php echo _('Save & Re-run installation checks'); ?>" />
</form>
</td></tr>
</table>
</div> <!--    wrapper -->
</body>
</html>
<?php
function sqlErrorsToList($errors)
{
    echo '<div class="db_create_errors" style="border: solid 1px red;padding:5px;">';
    echo _('There were some errors creating transactional DB structure');
    echo '<ul style="margin-top:2px;">';
    foreach ($errors as $error){
        if ($error['error'] == 0) {
            continue; // no error occurred
        }
        echo '<li>';    
        echo _('Error on structure') . ' <b>'.$error['struct'].'</b>. ';
        printf('<a href="" onclick="$(\'#eDetails%s\').toggle();return false;">' . _('Details') . '</a>',
            $error['struct']);
        printf('<ul style="display:none;" id="eDetails%s">',$error['struct']);
        echo '<li>' . _('Query:') . ' <pre>'.$error['query'].'</pre></li>';
        echo '<li>' . _('Error Message: ').$error['error_msg'].'</li>';
        echo '</ul>';
        echo '</li>';
    }
    echo '</div>';
}

