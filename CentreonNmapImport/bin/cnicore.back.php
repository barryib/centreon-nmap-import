#!/usr/bin/php
<?php
/*
 * Error Level
 */ 
error_reporting(E_ERROR | E_PARSE); 

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
	print "Mysql Error : ".$dbRes->getMessage() . " -> " . $sql;
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
	print "Mysql Error : ".$dbRes->getMessage() . " -> " . $sql;
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
	print "Mysql Error : ".$dbRes->getMessage() . " -> " . $sql;
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
	print "Mysql Error : ".$dbRes->getMessage() . " -> " . $sql;
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
	print "Mysql Error : ".$dbRes->getMessage() . " -> " . $sql;
	return false;
    }
    return true; 
}

function getPollerConf($pollerID) {
    global $pearDB;
    $sql = "SELECT * FROM `centreon_nmap_import_pollers` WHERE poller_id = '".$pollerID."' LIMIT 1";
    $dbRes =& $pearDB->query($sql);
    if (PEAR::isError($dbRes)){
	print "Mysql Error : ".$dbRes->getMessage() . " -> " . $sql;
	return false;
    }
    return $dbRes->fetchRow(); 
}

function getGenOptions() {
    global $pearDB;
    
    $sql = "SELECT * FROM centreon_nmap_import_opt LIMIT 1";
    $dbRes =& $pearDB->query($sql);
    if (PEAR::isError($dbRes)) {
	print "Mysql Error : ".$dbRes->getMessage() . " -> " . $sql;
	return false;
    }
    
    return $dbRes->fetchRow();
}

function sendSSHCmd($cmd) {
    $pollerConf = getPollerConf($cmd['poller_id']);
    $sshCmd = "ssh -q nagios@" . $pollerConf['ip_address'] . " -p " . $pollerConf['ssh_port'];
    $sshCmd .= " '" . $cmd['cmd'] . "'";
    setCmdStatus($cmd['cmd_id'], CMD_IS_STARTED);
    exec(escapeshellcmd($sshCmd), $out, $ret_var);
    
    if ($ret_var == 255) {
        $msg = 'Can not connect to the remote server by ssh';
        logCmdFailed($cmd['cmd_id'], CMD_SSH_FAILED, $msg);
        print $msg . "\n";
        return false;
    }
    if ($ret_var > 0) {
        print "ERROR: " . implode(' ', $out) . "\n";
        return false;
    }
    foreach ($out as $row) {
        preg_match('@^Failed to resolve given hostname/IP:\s+(.+)\.\s+Note@',
                   $row,
                   $matches);
        if (count($matches) > 0) {
            $failed_to_resolve[] = $matches[1];
        }
    }
    if (count($failed_to_resolve) > 0) {
        $msg = 'Failed to resolve given hostname/IP: ' . implode(', ', $failed_to_resolve);
        logCmdFailed($cmd['cmd_id'], CMD_SSH_FAILED, $msg);
        print $msg . "\n";
        return false;
    }
    setCmdStatus($cmd['cmd_id'], CMD_SSH_SUCCESS);
    return true;
}

function remoteCopy($cmd, $localPath = NULL) {
    global $cniFileUploadPath;
    $pollerConf = getPollerConf($cmd['poller_id']);
    if ($localPath === NULL) {
        $localPath = $cniFileUploadPath;
    }
    $scpCmd = "scp -q -P " . $pollerConf['ssh_port'];
    $scpCmd .= " nagios@" . $pollerConf['ip_address'] . ":" . $pollerConf['nmap_output_file'];
    $scpCmd .= " " . $localPath . $pollerConf['ip_address'] . ".xml";
    
    exec(escapeshellcmd($scpCmd), $out, $ret_var);
    if (!empty($ret_var)) {
        $msg = strlen($ret_var) < 1
            ? $ret_var
            : "An error has occured during the transfert " . $pollerConf['nmap_output_file'] . " -> " . $localPath . $pollerConf['ip_address'] . ".xml";
        logCmdFailed($cmd['cmd_id'], CMD_SCP_FAILED, $msg);
        return false;
    }
    updateScpPath($cmd['cmd_id'], $localPath . $pollerConf['ip_address'] . ".xml");
    setCmdStatus($cmd['cmd_id'], CMD_ALL_SUCCESS);
    return true;
}

function signalCnicoreStart() {
    global $run;
    print "Starting cnicore engine...\n";
    print "PID: " . posix_getpid() . "\n";
    $run = true;
}

function signalCnicoreStop() {
    global $run;
    print "Stopping cnicore engine...\n";
    print "PID: " . posix_getpid() . "\n";
    $run = false;
}

function signalHandler($signo) {
    switch ($signo) {
        case SIGTERM:
        case SIGKILL:
        case SIGQUIT:
        case SIGINT:
            // handle shutdown tasks
            stopAllRunningCmds();
            signalCnicoreStop();
            break;
        case SIGHUP:
            // handle restart tasks
            break;
    }
}

/*
 * Start of cnicore
 */ 
if (posix_getuid() == 0) { 
    print  "You must not execute centcore with root user \n";
    exit(1);
}

require_once "/etc/centreon/centreon.conf.php";
require_once "$centreon_path/www/class/centreonDB.class.php";

// tick use required
declare(ticks = 1);
// setup signal handlers
pcntl_signal(SIGTERM, "signalHandler");
pcntl_signal(SIGKILL, "signalHandler");
pcntl_signal(SIGQUIT, "signalHandler");
pcntl_signal(SIGHUP,  "signalHandler");

global $pearDB, $run, $cniFileUploadPath;

$pearDB = new CentreonDB();
$cniOptions = getGenOptions();
$cniFileUploadPath = $cniOptions['path_file_upload'];
$sleepTime = 5;

signalCnicoreStart();
stopAllRunningCmds();
while ($run) {
    //$stopedSSHCmds = getCmdsByStatus(CMD_IS_STOPED);
    //$failedSSHCmds = getCmdsByStatus(CMD_SSH_FAILED);
    $readySSHCmds = array_merge(getCmdsByStatus(CMD_IS_STOPED), getCmdsByStatus(CMD_SSH_FAILED));
    if (!empty($readySSHCmds)) {
        foreach ($readySSHCmds as $key => $cmd) {
            print "= Starting cmd: ".$cmd['cmd'] . "\n";
            $command = '/usr/bin/nohup /usr/local/centreon/www/modules/CentreonNmapImport/bin/sendCmd.php > /dev/null 2>&1 & echo $!';
	    exec($command ,$op);
	    print 'From parent "'.(int)$op[0].'"';
	    unset($op);
        }
    }
    //unset($stopedSSHCmds);
    //unset($failedSSHCmds);
    unset($readySSHCmds);
    
    //$waitedScp = getCmdsByStatus(CMD_SSH_SUCCESS);
    //$failedScp = getCmdsByStatus(CMD_SCP_FAILED);
    $readyToScp = array_merge(getCmdsByStatus(CMD_SSH_SUCCESS), getCmdsByStatus(CMD_SCP_FAILED));
    if (!empty($readyToScp)) {
        foreach ($readyToScp as $key => $cmd) {
            print "= Starting copy: ".$cmd['cmd_id'] ."\n";
            remoteCopy($cmd, '/tmp/');
        }
    }
    //unset($waitedScp);
    //unset($failedScp);
    unset($readyToScp);
    
    sleep($sleepTime);
}
exit(0);
?>