<?php
/**
 * @author    Thierno IB. Barry
 * @license   GNU/LGPL v2.1
 */
 
//session_start();

//ini_set('display_errors', 1);
//ini_set('log_errors', 1);
//error_reporting(E_ALL);

$aG = isset($_GET['action']) ? $_GET['action'] : NULL;
$aP = isset($_POST['action']) ? $_POST['action'] : NULL;
$action = isset($aG) ? $aG : $aP;

if (isset($action) && !empty($action)) {
    global $centreon, $oreon, $pearDB;
    
    require_once '/etc/centreon/centreon.conf.php';
    chdir($centreon_path . 'www');

    require_once './class/centreonSession.class.php';
    require_once './class/centreon.class.php';
    require_once './class/centreonDB.class.php';
    require_once './class/centreonLog.class.php';

    $pearDB     = new CentreonDB();
    $pearDBO    = new CentreonDB("centstorage");

    CentreonSession::start();
    /*
    * Define Oreon var alias
    */
    $centreon =& $_SESSION["centreon"];
    $oreon =& $centreon;
    if (!is_object($centreon))
         exit();
    
    /*
     * Init differents elements we need in a lot of pages
     */

    unset($centreon->Nagioscfg);
    $centreon->initNagiosCFG($pearDB);
    unset($centreon->optGen);
    $centreon->initOptGen($pearDB);
   
    require_once './modules/CentreonNmapImport/class/CNI_Cmd.class.php';
    require_once './modules/CentreonNmapImport/DB-Func.php';
    require_once './modules/CentreonNmapImport/common.php';
 
    require_once 'HTML/QuickForm.php';

    switch($action){
        case 'exist':
            echo json_encode(isExist($_GET['poller_id'], $_GET['target']));
            break;
        case 'get':
            echo json_encode(getCmd($_GET['cmd_id']));
            break;
        case 'getHosts':
            echo json_encode(getHostsFromCmd($_POST['cmd_id']));
            break;
        case 'heartbeat':
            echo json_encode(heartBeat());
            break;
        case 'write':
            echo json_encode(writeCmd($_POST['args'], $_POST['options']));
            break;
        case 'import':
            echo json_encode(importHosts($_POST['cmd_id'], $_POST['hosts']));
            break;
        case 'hasImported':
            //echo json_encode(hasImported($GET['cmd_id']));
            break;
        case 'daemonStatus':
            echo json_encode(daemonStatus($_POST['status']));
            break;
    }
    $pearDB->disconnect();
}

function isExist($poller_id, $target) {
    global $pearDB;
    
    $ret = array();
    $cniCmd = new CNI_Cmd($pearDB, $poller_id, false);
    if ($cniCmd->isCmdExist($target)) {
        $ret['result'] = true;
        $ret['diag']['error'] = false;
        $ret['diag']['msg'] = _("The command: ".$cmd." exist already for this poller.");
    }
    else {
        $ret['result'] = false;
        $ret['diag']['error'] = false;
        $ret['diag']['msg'] = _("The command: ".$cmd." does not exist for this poller.");
    }
    return $ret;
}

function getCmd($cmd_id = NULL) {
    global $pearDB;
    
    $ret = array();
    $cniCmd = new CNI_Cmd($pearDB);
    if (empty($cmd_id)) {
        // return all commands
        $cmds = $cniCmd->getAllCmds();
        foreach ($cmds as $cmd) {
            $ret['result'][] = $cmd;
        }
        $ret['diag']['error'] = false;
        $ret['diag']['msg'] = '';
    }
    else {
        // return the asked command
        $ret['result'] = $cniCmd->getCmd($cmd_id);
        $ret['diag']['error'] = false;
        $ret['diag']['msg'] = '';
    }
    return $ret;
}

function heartBeat() {
    global $pearDB;
    
    $cniCmd = new CNI_Cmd($pearDB);
    $ret = array();
    $cmds = $cniCmd->getNonImportedCmds();
    $ret['result'] = array();
    if (!empty($cmds)) {
        foreach ($cmds as $cmd) {
            $tmpRet = array();
            $pollerConf = getPollerConfig($cmd['poller_id'], array('poller_id', 'name', 'ip_address'));
            
            $tmpRet['cmd']['cmd_id']        = $cmd['cmd_id'];
            $tmpRet['cmd']['target']        = $cmd['target'];
            $tmpRet['cmd']['cmd']           = $cmd['cmd'];
            $tmpRet['cmd']['status']        = $cmd['status'] == 0 ? 'ready' : 'not-ready';
            $tmpRet['cmd']['is_imported']   = $cmd['is_imported'];
            $tmpRet['cmd']['error_reason']  = $cmd['error_reason'];
            $tmpRet['cmd']['poller']        = $pollerConf;
            
            $ret['result'][] = $tmpRet;
        }
    }
    $ret['diag']['error'] = false;
    if (count($ret['result']) <= 0) {
        $ret['diag']['msg'] = _("There are no command to execute");
    }
    else {
        $ret['diag']['msg'] = 'ok';
    }
    
    return $ret;
}

function writeCmd($args, $options) {
    global $pearDB;
    
    $ret = array();
    $targets = $args['target'];
    if(isset($args['netmask']) && !empty($args['netmask']))
        $targets .=  '/' . $args['netmask'];
    
    foreach($options as $key=>$val) {
        if ($val == 'false') {
            unset($options[$key]);
        }
    }
    //if (isset($options['os_detect']) || isset($options['service_info']) || isset($options['all_options'])) {
    //    $options['with_sudo'];
    //}
    
    $cniCmd = new CNI_Cmd($pearDB, $args['poller_id']);
    if ($cniCmd->write(array($targets), $options)) {
        $ret['diag']['error'] = false;
        $ret['diag']['msg'] = _("Your remote commande has been wrote correctly");
    }
    else {
        $ret['diag']['error'] = true;
        $ret['diag']['msg'] = $cniCmd->getStrErrors();
    }
    
    return $ret;
}

function getHostsFromCmd($cmd_id) {
    global $pearDB;
    
    $cniOptions = getGeneralOptions();
    
    $ret = array();
    $cniCmd = new CNI_Cmd($pearDB);
    $cmd = $cniCmd->getCmd($cmd_id);
    $nmap = nmapXmlParse($cmd['scp_tmp_file']);
    if (isset($nmap['exception'])) {
        $ret['diag']['error'] = true;
        $ret['diag']['msg'] = $nmap['exception'];
        
        return $ret;
    }
    $form = new HTML_QuickForm();
    $hosts = prepareHostsToPrint($nmap['hosts'], $form);//formatHostArray($nmap['hosts']);
    $stats = $nmap['stats'];
    $stats->scantime = date('i:s', $stats->finished - $stats->start) . ' seconds';
    $stats->start = date('D M j G:i:s Y', $stats->start);
    $stats->finished = date('D M j G:i:s Y', $stats->finished);
    
    $ret['result']['hosts'] = array_sort($hosts,
                                         $cniOptions['hosts_sort_type'],
                                         $cniOptions['hosts_sort_order']);
    $ret['result']['stats'] = $stats;
    $ret['result']['filters']['filter_state']   = &$form->getElement('filter_state')->toHtml();//$hosts['fStateOpts'];
    $ret['result']['filters']['filter_os']      = &$form->getElement('filter_os')->toHtml();
    $ret['result']['filters']['filter_service'] = &$form->getElement('filter_service')->toHtml();
    $ret['result']['filters']['filter_inDB']     = &$form->getElement('filter_inDB')->toHtml();
    
    $ret['diag']['error'] = false;
      
    return $ret;
}

function importHosts($cmd_id, $hosts) {
    global $centreon, $pearDB, $form;
    
    $ret = array();
    $form = new HTML_QuickForm();
    $cniOptions = getGeneralOptions();
    foreach ($hosts as $index=>$host) {
        if ($host['dupSvTplAssoc']['dupSvTplAssoc'] == 'false') {
            unset($hosts[$index]['dupSvTplAssoc']);
        }
    }
    $hostArr = insertCNIHostArrayInDB($hosts, $cniOptions['allowed_dup_names']);
    if(count($hostArr) == 0){
        $cniCmd = new CNI_Cmd();
        $cniCmd->delete($cmd_id);
        
        $ret['diag']['error'] = false;
        $ret['diag']['msg'] = _("All selected hosts have been correctly imported.");
    }
    else{
        $ret['diag']['error'] = true;
        $ret['diag']['msg'] = _("These following hosts have not been imported. Retry.");
        $ret['result']['hosts'] = $hostArr;
    }
    return $ret;
}

function hasImported($cmd_id) {
    $ret = array();
    $cniCmd = new CNI_Cmd();
    if ($cniCmd->hasImported($cmd_id)) {
        $ret['diag']['error'] = false;
        $ret['diag']['msg'] = _("The command has been correctly change to impoted");
    } else {
        $ret['diag']['error'] = true;
        $ret['diag']['msg'] = $cniCmd->getStrErrors();
    }
    return $ret;
}

function daemonStatus($status) {
    if (empty($status)) {
        return getDaemonStatus();
    }
    
    return setDaemonStatus($status);
}

function getDaemonStatus() {
    $ret = array();
    if (exec('ps ax | grep [c]nicore.php')) {
        $ret['result']['status']['code'] = 1;
        $ret['result']['status']['css'] = 'started';
        $ret['result']['status']['msg'] = _("Running");
    } else {
        $ret['result']['status']['code'] = 0;
        $ret['result']['status']['css'] = 'stoped';
        $ret['result']['status']['msg'] = _("Not running");
    }
    $ret['diag']['error'] = false;
    $ret['diag']['msg'] = '';
    
    return $ret;
}

function setDaemonStatus($status) {
    if (!array_key_exists($status, array('start'=>null, 'stop'=>null))) {
        return getDaemonStatus();
    }
    //echo escapeshellarg('sudo /etc/init.d/cnicore '.$status);
    $out = shell_exec('sudo /etc/init.d/cnicore '.$status.' > /dev/null');
    //return $out;
    return getDaemonStatus();
}
?>
