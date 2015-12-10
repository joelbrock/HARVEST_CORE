<?php
include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT . 'classlib2.0/FannieAPI.php');
}

class ExcelUpload extends \COREPOS\Fannie\API\FannieUploadPage {

    function process_file($linedata)
    {
        $headers = $linedata[0]; 
        $headers = array_map(function($i){ return str_replace(' ', '', $i);}, $headers);
        $dbc = $this->connection;
        $dbc->query('DROP TABLE GenericUpload');
        $query = 'CREATE TABLE GenericUpload (';
        $query .= array_reduce($headers, function($carry, $i) use ($dbc) { return $carry . ($i === '' ? 'col'.rand(0,9999) : $dbc->identifierEscape($i)) . ' VARCHAR(255),'; });
        $query = substr($query, 0, strlen($query)-1) . ')';
        $dbc->query($query);
        $query = 'INSERT INTO GenericUpload VALUES (' . str_repeat('?,', count($headers));
        $query = substr($query, 0, strlen($query)-1) . ')';
        $prep = $dbc->prepare($query);
        for ($i=1; $i<count($linedata); $i++) {
            if (count($linedata[$i]) < count($headers)) {
                while (count($linedata[$i]) < count($headers)) {
                    $linedata[$i][] = '';
                }
            } elseif (count($linedata[$i]) > count($headers)) {
                $linedata[$i] = array_slice($linedata[$i], 0, count($headers));
            }
            $dbc->execute($prep, $linedata[$i]);
        }


        return true;
    }
}

FannieDispatch::conditionalExec();
