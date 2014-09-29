#!/usr/bin/php -q
<?php
/**
 * @author    Thierno IB. Barry
 * @license   GNU/LGPL v2.1
 */
function sendSSHCmd($cmd) {
    $pollerConf = getPollerConf($cmd['poller_id']);
    $sshCmd = "ssh -q nagios@" . $pollerConf['ip_address'] . " -p " . $pollerConf['ssh_port'];
    $sshCmd .= " '" . $cmd['cmd'] . "'";
    setCmdStatus($cmd['cmd_id'], CMD_IS_STARTED);
    exec(escapeshellcmd($sshCmd), $out, $ret_var);
    
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

require_once 'functions.php';

$c =& file_get_contents('/tmp/sendCmd');
file_put_contents('/tmp/sendCmd', $c.'from child '.posix_getpid()."\n");
sleep(30);
?>