<?php
/**
 * @author    Thierno IB. Barry
 * @license   GNU/LGPL v2.1
 */
define ('CMD_PID_SUICIDE',  '-3');
define ('CMD_SSH_FAILED',   '-2');
define ('CMD_SCP_FAILED',   '-1');
define ('CMD_ALL_SUCCESS',  '0');
define ('CMD_SSH_SUCCESS',  '1');
define ('CMD_IS_STOPED',    '2');
define ('CMD_IS_STARTED',   '3');

function getCmdsByStatus($status) {
    global $pearDB;
    $sql = "SELECT * FROM `centreon_nmap_import_cmds` WHERE status = '" . $status . "'";
    $dbRes =& $pearDB->query($sql);
    $retCmds = array();
    if (PEAR::isError($dbRes)){
	//print "Mysql Error : ".$dbRes->getMessage() . " -> " . $sql;
	return false;
    }
    while ($dbRes->fetchInto($row)) {
        $row['cmd'] = html_entity_decode($row['cmd'], ENT_QUOTES);
        $retCmds[$row['cmd_id']] = $row;
    }
    return $retCmds;
}

function setCmdStatus($cmd_id, $status) {
    global $pearDB;
    
    $sql = "UPDATE `centreon_nmap_import_cmds` SET";
    $sql .= " status = '" . htmlentities($status, ENT_QUOTES) . "'";
    if ($status == CMD_ALL_SUCCESS) {
        $sql .= " ,stop_timestamp = '" . time() . "'";
        //$sql .= " ,error_reason = ''";
    }
    else if ($status == CMD_IS_STARTED) {
        $sql .= " ,start_timestamp = '" . time() ."'";
    }
    $sql .= " WHERE cmd_id = '".$cmd_id."' LIMIT 1";
    $dbRes =& $pearDB->query($sql);
    if (PEAR::isError($dbRes)){
	//print "Mysql Error : ".$dbRes->getMessage() . " -> " . $sql;
	return false;
    }
    return true; 
}

function updateScpPath($cmd_id, $path) {
    global $pearDB;
    $sql = "UPDATE `centreon_nmap_import_cmds` SET";
    $sql .= " scp_tmp_file = '" . htmlentities($path, ENT_QUOTES) . "'";
    $sql .= " WHERE cmd_id = '".$cmd_id."' LIMIT 1";
    $dbRes =& $pearDB->query($sql);
    if (PEAR::isError($dbRes)){
	//print "Mysql Error : ".$dbRes->getMessage() . " -> " . $sql;
	return false;
    }
    return true; 
}

function logCmdFailed($cmd_id, $status, $reason) {
    global $pearDB;
    $sql = "UPDATE `centreon_nmap_import_cmds` SET";
    $sql .= " status = '" . htmlentities($status, ENT_QUOTES) . "'";
    $sql .= ", error_reason = '" . htmlentities($reason, ENT_QUOTES) . "'";
    $sql .= " WHERE cmd_id = '".$cmd_id."' LIMIT 1";
    $dbRes =& $pearDB->query($sql);
    if (PEAR::isError($dbRes)){
	//print "Mysql Error : ".$dbRes->getMessage() . " -> " . $sql;
	return false;
    }
    return true; 
}

function stopAllRunningCmds() {
    global $pearDB;
    $sql = "UPDATE `centreon_nmap_import_cmds` SET";
    $sql .= " status = '" . htmlentities(CMD_IS_STOPED, ENT_QUOTES) . "'";
    $sql .= ", error_reason = 'urgence stop'";
    $sql .= " WHERE status = '".htmlentities(CMD_IS_STARTED, ENT_QUOTES)."' LIMIT 1";
    $dbRes =& $pearDB->query($sql);
    if (PEAR::isError($dbRes)){
	//print "Mysql Error : ".$dbRes->getMessage() . " -> " . $sql;
	return false;
    }
    return true; 
}

function getPollerConf($pollerID) {
    global $pearDB;
    $sql = "SELECT * FROM `centreon_nmap_import_pollers` WHERE poller_id = '".$pollerID."' LIMIT 1";
    $dbRes =& $pearDB->query($sql);
    if (PEAR::isError($dbRes)){
	//print "Mysql Error : ".$dbRes->getMessage() . " -> " . $sql;
	return false;
    }
    return $dbRes->fetchRow(); 
}

function getGenOptions() {
    global $pearDB;
    
    $sql = "SELECT * FROM centreon_nmap_import_opt LIMIT 1";
    $dbRes =& $pearDB->query($sql);
    if (PEAR::isError($dbRes)) {
	//print "Mysql Error : ".$dbRes->getMessage() . " -> " . $sql;
	return false;
    }
    
    return $dbRes->fetchRow();
}

function getNagiosUser() {
    $pearDB = new CentreonDB();
    
    $sql = "SELECT nagios_user FROM `cfg_nagios` WHERE nagios_id='1' LIMIT 1";
    $dbRes =& $pearDB->query($sql);
    if (PEAR::isError($dbRes)) {

    }
    $nagios_user = $dbRes->fetchRow();
    $dbRes->free();
    $pearDB->disconnect();
    unset($pearDB);
    $nagios_user = $nagios_user['nagios_user'];
    
    return empty($nagios_user) ? 'nagios':$nagios_user;
}
?>