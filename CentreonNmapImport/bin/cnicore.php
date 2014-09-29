#!/usr/bin/php -q
<?php
/**
 * @author    Thierno IB. Barry
 * @license   GNU/LGPL v2.1
 */
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

function sendSSHCmd($cmd) {
    $pollerConf = getPollerConf($cmd['poller_id']);
    $sshCmd = "ssh -q nagios@" . $pollerConf['ip_address'] . " -p " . $pollerConf['ssh_port'];
    $sshCmd .= " '" . $cmd['cmd'] . "'";
    setCmdStatus($cmd['cmd_id'], CMD_IS_STARTED);
    exec($sshCmd, $out, $ret_var);
    
    if ($ret_var == 255) {
        $msg = "Can not connect to the remote server '".$pollerConf['ip_address']."' by ssh";
        logCmdFailed($cmd['cmd_id'], CMD_SSH_FAILED, $msg);
        System_Daemon::notice($msg);
        return false;
    }
    if ($ret_var > 0) {
        System_Daemon::notice("ERROR: " . implode(' ', $out));
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
        System_Daemon::notice($msg);
        return false;
    }
    setCmdStatus($cmd['cmd_id'], CMD_SSH_SUCCESS);
    System_Daemon::info('The SSH command has been correctly executed');
    return true;
}

function remoteCopy($cmd, $localPath = NULL) {
    global $cniFileUploadPath;
    
    $pollerConf = getPollerConf($cmd['poller_id']);
    if ($localPath === NULL) {
        $localPath = $cniFileUploadPath;
    }
    $filePath = $localPath . str_replace("/", "_", $cmd['target']) . '@' . $pollerConf['ip_address'] . ".xml";
    
    $scpCmd = "scp -q -P " . $pollerConf['ssh_port'];
    $scpCmd .= " nagios@" . $pollerConf['ip_address'] . ":" . $pollerConf['nmap_output_file'];
    $scpCmd .= " " . $filePath;
    
    exec(escapeshellcmd($scpCmd), $out, $ret_var);
    if (!empty($ret_var)) {
        $msg = strlen($ret_var) < 1
            ? $ret_var
            : "An error has occured during the transfert " . $pollerConf['nmap_output_file'] . " -> " . $filePath;
        logCmdFailed($cmd['cmd_id'], CMD_SCP_FAILED, $msg);
        System_Daemon::notice($msg);
        return false;
    }
    if (!file_exists($filePath)) {
        System_Daemon::notice('The file %s has been dowloaded, but a problem has occured', $filePath);
        return false;
    }
    
    updateScpPath($cmd['cmd_id'], $filePath);
    System_Daemon::info('The remote file %s has been correctly downloaded in local at %s',
                        $pollerConf['nmap_output_file'],
                        $filePath);
    
    if (chmod($filePath, 0666)) {
        System_Daemon::info('The mode of the file %s has been correctly changed into 0666', $filePath);
        //if (chgrp($filePath, $www_user)) {
        //    System_Daemon::info('The group of the file %s has correctly turned into %s', $filePath, $www_user);
        //}
    }
    
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
//if (posix_getuid() == 0) { 
    //print  "You must not execute centcore with root user \n";
    //exit(1);
//}

$runmode = array(
    'no-daemon' => false,
    'help' => false,
    'write-initd' => false,
);

// Scan command line attributes for allowed arguments
foreach ($argv as $k=>$arg) {
    if (substr($arg, 0, 2) == '--' && isset($runmode[substr($arg, 2)])) {
        $runmode[substr($arg, 2)] = true;
    }
}

// Help mode. Shows allowed argumentents and quit directly
if ($runmode['help'] == true) {
    echo 'Usage: '.$argv[0].' [runmode]' . "\n";
    echo 'Available runmodes:' . "\n";
    foreach ($runmode as $runmod=>$val) {
        echo ' --'.$runmod . "\n";
    }
    die();
}

// Make it possible to test in source directory
// This is for PEAR developers only
ini_set('include_path', ini_get('include_path').':..');

error_reporting(E_ALL);

require_once 'System/Daemon.php';
require_once '/etc/centreon/centreon.conf.php';
require_once $centreon_path . '/www/class/centreonDB.class.php';

$userinfo = posix_getpwnam(getNagiosUser());
// Setup
$options = array(
    'appName' => 'cnicore',
    'appDir' => dirname(__FILE__),
    'appDescription' => 'Execute Nmap command on a remote host via SSH',
    'authorName' => 'Thierno IB. Barry',
    'authorEmail' => 'toto@exemple.com',
    'sysMaxExecutionTime' => '0',
    'sysMaxInputTime' => '0',
    'sysMemoryLimit' => '10M',
    'appRunAsGID' => $userinfo['gid'],
    'appRunAsUID' => $userinfo['uid'],
);
unset($userinfo);
System_Daemon::setOptions($options);

// With the runmode --write-initd, this program can automatically write a
// system startup file called: 'init.d'
// This will make sure your daemon will be started on reboot
if (!$runmode['write-initd']) {
    System_Daemon::info('not writing an init.d script this time');
} else {
    if (($initd_location = System_Daemon::writeAutoRun()) === false) {
        System_Daemon::notice('unable to write init.d script');
    } else {
        System_Daemon::info('sucessfully written startup script: %s', $initd_location);
    }
    System_Daemon::stop();
}

// This program can also be run in the forground with runmode --no-daemon
if (!$runmode['no-daemon']) {
    // Spawn Daemon
    System_Daemon::start();
}

// Declare of global variables
global $pearDB, $run, $cniFileUploadPath;

$pearDB = new CentreonDB();
$cniOptions = getGenOptions();
$cniFileUploadPath = $cniOptions['path_file_upload'];

// Loop options
$sleepTime = $cniOptions['daemon_sleep_time'];
$runningOkay = true;

unset($cniOptions);

// What mode are we in?
$mode = '"'.(System_Daemon::isInBackground() ? '' : 'non-' ).'daemon" mode';
System_Daemon::info('{appName} start running in %s with %s s sleeping time ', $mode, $sleepTime);
stopAllRunningCmds();
while (!System_Daemon::isDying() && $runningOkay) {
    

    // In the actuall logparser program, You could replace 'true'
    // With e.g. a  parseLog('vsftpd') function, and have it return
    // either true on success, or false on failure.
    //$runningOkay = true;
    //$runningOkay = parseLog('vsftpd');

    // Should your parseLog('vsftpd') return false, then
    // the daemon is automatically shut down.
    // An extra log entry would be nice, we're using level 3,
    // which is critical.
    // Level 4 would be fatal and shuts down the daemon immediately,
    // which in this case is handled by the while condition.
    if (!$runningOkay) {
        System_Daemon::err('{appName} produced an error, so this will be my last run');
    }
    
    $readySSHCmds = array_merge(getCmdsByStatus(CMD_IS_STOPED), getCmdsByStatus(CMD_SSH_FAILED));
    if (!empty($readySSHCmds)) {
        foreach ($readySSHCmds as $key => $cmd) {
            System_Daemon::info('{appName} Starting cmd:  %s', $cmd['cmd']);
            sendSSHCmd($cmd);
        }
    }
    unset($readySSHCmds);
    
    $readyToScp = array_merge(getCmdsByStatus(CMD_SSH_SUCCESS), getCmdsByStatus(CMD_SCP_FAILED));
    if (!empty($readyToScp)) {
        foreach ($readyToScp as $key => $cmd) {
            System_Daemon::info('{appName} Starting remote copy of cmd_i:  %s', $cmd['cmd_id']);
            remoteCopy($cmd);
        }
    }
    unset($readyToScp);
    
    // Relax the system by sleeping for a little bit
    // iterate also clears statcache
    System_Daemon::iterate($sleepTime);
}

// Shut down the daemon nicely
// This is ignored if the class is actually running in the foreground
System_Daemon::info('{appName} is going to stop running in %s', $mode);
System_Daemon::stop();
?>
