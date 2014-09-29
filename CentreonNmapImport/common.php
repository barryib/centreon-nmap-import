<?php
/**
 * @author    Thierno IB. Barry
 * @license   GNU/LGPL v2.1
 */
require_once './include/configuration/configObject/host/DB-Func.php';
require_once 'Net/Traceroute.php';

/**
 * Sanitize a string with trim and htmlentities
 *
 * @param string $str String to sanitize
 * @return string Sanitized string
 * */
function sanitize($str){
    $str = trim($str);
    $str = htmlentities($str, ENT_QUOTES);
    
    return $str;
}

/**
 * Sanitize function to use with array_walk
 *
 * @param string &$item Array item to sanize
 * @param string $key Array key
 * */
function sanitizeWalk(&$item, $key){
    $item = trim($item);
    $item = htmlentities($item, ENT_QUOTES);
}

/**
 * Check if a value is null or not define.
 * It is used before any insertion into a database.
 * The function can also sanitize.
 *
 * @param string $value Field to check
 * @param boolean $sanitize True if you need to sanitize the chcked field
 * @return string Checked (and sanitized) field
 * */
function nullIfNotIsset($value, $sanitize){
    if($sanitize)
        $value = sanitize($value);
        
    return (isset($value) && $value != NULL) ? "'" .$value ."'" : "NULL";
}

function nullIfNotIssetWalk(&$item, $key){
    $item = nullIfNotIsset($item, true);
}

function stub($str){
    return str_replace(array(' ', '.'), array('-', '_'), sanitize($str));
}

function getStubsFromHost($host){
    $stub = '';
    $stub .= ' status-'.stub($host->getStatus());
    $stub .= ' os-'.stub($host->getOS());
    $stub .= ' db-'. (hostExists($host->getHostname()) ? 'yes':'no');
    $services = $host->getServices();
    foreach($services as $service)
        $stub .= ' service-'.stub($service->name);
        
    return $stub;
}

/**
 * Scan a target host
 *
 * @param string $target Ip address to scan
 * @param array $opt Nmap's Initial options
 * @param boolean $osDetect Try to detect OS type if it's true
 * @param boolean $serviceInfo Try to detect services which are running on target host
 * @param boolean $all
 * @return array A hosts object array and stats
 *  array(
 *      hosts => host array
 *      stats => stats about nmap scan
 *  )
 * */
function nmapScan($target, $opt, $osDetect = false, $serviceInfo = false, $all = false){
    try{
        $nmap = new CNI_Nmap($opt);
        // Enable nmap options                    
        $nmap->enableOptions(array('os_detection' => $osDetect,
                                   'service_info' => $serviceInfo,
                                   //'port_ranges' => 'U:53,111,137,T:21-25,80,139,8080',
                                   'all_options'  => $all
                                   ));
        // Scan target
        $userSudo = $opt['use_sudo'] == 1 ? true : false;
        $nmap->scan(array($target), $userSudo);
        // Get failed hosts 
        $failed_to_resolve = $nmap->getFailedToResolveHosts();
        if (count($failed_to_resolve) > 0) {
            $ret['failed_to_resolve'] = $failed_to_resolve;
        }
        // Parse XML Output to retrieve Hosts Object
        $ret['hosts'] = $nmap->parseXMLOutput();
        $ret['stats'] = $nmap->getNmapStats();
        
    }catch (Net_Nmap_Exception $ne) {
        $ret['exception'] = $ne->getMessage();
    }
    return $ret;
}

/**
 * Parse a xml file
 *
 * @param string $xml Path of xml file which have to be parsed
 * @return array An hosts object array
 * */
function nmapXmlParse($xml){
    try {
        $nmap = new CNI_Nmap();

        // Parse XML Output to retrieve Hosts Object
        $ret['hosts'] = $nmap->parseXMLOutput($xml);
        $ret['stats'] = $nmap->getNmapStats();
         
    }catch (Net_Nmap_Exception $ne) {
        $ret['exception'] = $ne->getMessage();
    }
    return $ret;
}

function traceroute($target) {
    $traceroute = Net_Traceroute::factory();
    if(PEAR::isError($traceroute)) {
        //echo $traceroute->getMessage();
        return array();
    } 
    $traceroute->setArgs(array('numeric' => NULL,
                               'timeout' => 5));
    $response = $traceroute->traceroute(escapeshellcmd($target));
    return $response->getHops();
}

function prepareHostsToPrint($hosts, $printForm, $isShowExit = false){
    $fStateOpts = $fOsOpts = $fServiceOpts = $fInDBOpt = array(NULL=>'&nbsp;&nbsp;');
    if(!empty($hosts)){
        if(get_class($hosts[0]) == 'Net_Nmap_Host') {
            $retArr = prepareHostsObjToPrint($hosts, $printForm, $isShowExit);
        }
        else {
            $retArr = prepareHostArrToPrint($hosts, $printForm, $isShowExit);
        }
        
        $hostArr        = $retArr['hostArr'];
        $fStateOpts     = array_merge($fStateOpts, $retArr['fStateOpts']);
        $fOsOpts        = array_merge($fOsOpts, $retArr['fOsOpts']);
        $fServiceOpts   = array_merge($fServiceOpts, $retArr['fServiceOpts']);
        $fInDBOpt       = array_merge($fInDBOpt, $retArr['fInDBOpt']);
    }
    $printForm->addElement('select', 'filter_state', _("State:"), $fStateOpts);
    $printForm->addElement('select', 'filter_os', _("Os:"), $fOsOpts);
    $printForm->addElement('select', 'filter_service', _("Service:"), $fServiceOpts);
    $printForm->addElement('select', 'filter_inDB', _("In DB:"), $fInDBOpt);

    return $hostArr;
}

function prepareHostsObjToPrint($hosts, $printForm, $isShowExit = false){
    if(get_class($hosts[0]) != 'Net_Nmap_Host')
        return false;
    
    $ret = array();
    $index = 0;
    $hTpls = getHostTemplates();
    $nagios_servers = getNagiosServer(NULL, array('id', 'name'), true);
    if (!empty($nagios_servers)) {
        foreach ($nagios_servers as $id => $ns) {
            $nagios_servers[$id] = $ns['name'];
        }
    }
    
    foreach ($hosts as $host){
        if ($isShowExit && testHostExistence($host['hostname']))
            continue;
        
        $hostTpl = getDefaultTemplateForOs($host->getOs());
        echo 'ok'.$hostTpl;
        $cssClass = getStubsFromHost($host);
        
        $chekbox    = &$printForm->addElement('checkbox', 'hosts['. $index .'][isImport]', NULL, NULL, array('class' => 'ckbox-import'));
        $chekbox2   = &$printForm->addElement('checkbox', 'hosts['. $index .'][dupSvTplAssoc][dupSvTplAssoc]');
        $select     = &$printForm->addElement('select',   'hosts['. $index .'][hTpl]' , '', $hTpls);
	$select->setValue($hostTpl);
        $ns_select  = &$printForm->addElement('select',   'hosts['. $index .'][nagios_server_id]' , '', $nagios_servers);
        // Hidden element
        $hHostname  = &$printForm->addElement('hidden', 'hosts['. $index .'][hostname]' , $host->getHostname());
        $hStatus    = &$printForm->addElement('hidden', 'hosts['. $index .'][status]'   , $host->getStatus());
        $hAddress   = &$printForm->addElement('hidden', 'hosts['. $index .'][address]'  , $host->getAddress());
        $hOs	    = &$printForm->addElement('hidden', 'hosts['. $index .'][os]'	, $host->getOs());
        $hServices  = &$printForm->addElement('hidden', 'hosts['. $index .'][services]'	, NULL);
        $hClass     = &$printForm->addElement('hidden', 'hosts['. $index .'][class]'    , $cssClass);
        
        $hostArr[] = array(
                           'index'	    => $index,
                           'status'	    => $host->getStatus(),
                           'hostname'	    => $host->getHostname(),
                           'address'	    => $host->getAddress(),
                           'os'             => $host->getOS(),
                           'hTpl'	    => $select->toHtml(),
                           'isImport'	    => $chekbox->toHtml(),
                           'ns_select'      =>$ns_select->toHtml(),
                           'dupSvTplAssoc'  => array('dupSvTplAssoc' => $chekbox2->toHtml()),
                           // Add hidden elements
                           'hStatus'	    => $hStatus->toHtml(),
                           'hHostname'      => $hHostname->toHtml(),
                           'hAddress'	    => $hAddress->toHtml(),
                           'hOs'	    => $hOs->toHtml(),
                           'hServices'      => $hServices->toHtml(),
                           // Add dom class to filter
                           'class'          => $cssClass,
                           'hClass'         => $hClass->toHtml()
                           );
        
        $tmp = hostExists($host->getHostname()) ? 'yes' : 'no';
        $ret['fStateOpts']['status-'.stub($host->getStatus())] = $host->getStatus();
        $ret['fOsOpts']['os-'.stub($host->getOS())] = $host->getOS();
        $ret['fInDBOpt']['db-'.$tmp] = $tmp;
        $services = $host->getServices();
        foreach($services as $service)
            $ret['fServiceOpts']['service-'.stub($service->name)] = $service->name;
        
        $index++;
    }
    $ret['hostArr'] = $hostArr;
    
    return $ret;
}

function prepareHostArrToPrint($hosts, $printForm, $isShowExit = false){
    if(get_class($hosts[0]))
        return false;
    
    $ret = array();
    $index = 0;
    $hTpls = getHostTemplates();
    $nagios_servers = getNagiosServer(NULL, array('id', 'name'), true);
    if (!empty($nagios_servers)) {
        foreach ($nagios_servers as $id => $ns) {
            $nagios_servers[$id] = $ns['name'];
        }
    }
    foreach ($hosts as $host){
        if ($isShowExit && testHostExistence($host['hostname']))
            continue;

        $chekbox    = &$printForm->addElement('checkbox', 'hosts['. $index .'][isImport]' );
        $chekbox2   = &$printForm->addElement('checkbox', 'hosts['. $index .'][dupSvTplAssoc][dupSvTplAssoc]');
        $ns_select  = &$printForm->addElement('select',   'hosts['. $index .'][nagios_server_id]' , '', $nagios_servers);
        $ns_select->setValue($host['nagios_server_id']);
        $select     = &$printForm->addElement('select',   'hosts['. $index .'][hTpl]' , '', $hTpls);
        $select->setValue($host['hTpl']);
        // Hidden element
        $hHostname  = &$printForm->addElement('hidden', 'hosts['. $index .'][hostname]' , $host['hostname']);
        $hStatus    = &$printForm->addElement('hidden', 'hosts['. $index .'][status]'   , $host['status']);
        $hAddress   = &$printForm->addElement('hidden', 'hosts['. $index .'][address]'  , $host['address']);
        $hOs	    = &$printForm->addElement('hidden', 'hosts['. $index .'][os]'	, $host['os']);
        $hServices  = &$printForm->addElement('hidden', 'hosts['. $index .'][services]'	, NULL);
        $hClass     = &$printForm->addElement('hidden', 'hosts['. $index .'][class]'    , $host['class']);
            
        $hostArr[] = array(
                           'index'	=> $index,
                           'status'	=> $host['status'],
                           'hostname'	=> $host['hostname'],
                           'address'	=> $host['address'],
                           'os'	        => $host['os'],
                           'hTpl'	=> $select->toHtml(),
                           'isImport'	=> $chekbox->toHtml(),
                           'ns_select'      =>$ns_select->toHtml(),
                           'dupSvTplAssoc' => array('dupSvTplAssoc' => $chekbox2->toHtml()),
                           // Add hidden elements
                           'hStatus'	=> $hStatus->toHtml(),
                           'hHostname'  => $hHostname->toHtml(),
                           'hAddress'	=> $hAddress->toHtml(),
                           'hOs'	=> $hOs->toHtml(),
                           'hServices'  => $hServices->toHtml(),
                           // Add dom class to filter
                           'class'      => $host['class'],
                           'hClass'     => $hClass->toHtml(),
                           // Add error
                           'isError'    => isset($host['isError']) ? $host['isError'] : NULL,
                           'reason'     => isset($host['reason']) ? $host['reason'] : NULL
                           );
        
        $tmp = hostExists($host['hostname']) ? 'yes' : 'no';
        $ret['fStateOpts']['status-'.stub($host['status'])] = $host['status'];
        $ret['fOsOpts']['os-'.stub($host['os'])] = $host['os'];
        $ret['fInDBOpt']['db-'.$tmp] = $tmp;
        $ret['fServiceOpts'] = array();
        /*$services = explode(',', $host['services']);
        foreach($services as $service)
            $ret['fServiceOpts']['service-'.stub($service['name'])] = $service['name'];
        */
        $index++;
    }
    $ret['hostArr'] = $hostArr;
    
    return $ret;
}

function prepareStatsToPrint($stats, $printTpl){
    if(!empty($stats)){
        $dateFormat = 'D M j G:i:s Y';
        // Stat array header
        $printTpl->assign('thead_stats', _("Scan Statistics"));
        $printTpl->assign('header_gen_info', _("General informations"));
        $printTpl->assign('header_time_info', _("Time stats"));
        $printTpl->assign('header_hosts_info', _("Hosts stats"));
        // Assignig text label
        $printTpl->assign('row_scanT', _("Scan"));
        $printTpl->assign('row_versionT', _("Version"));
        $printTpl->assign('row_argsT', _("Args"));
        $printTpl->assign('row_startT', _("Start"));
        $printTpl->assign('row_finishT', _("Finish"));
        $printTpl->assign('row_scan_timeT', _("Scan have taken"));
        $printTpl->assign('row_upT', _("Up"));
        $printTpl->assign('row_downT', _("Down"));
        $printTpl->assign('row_totalT', _("Total"));
        // Assigning stats values
        $printTpl->assign('row_scanV', $stats->scanner);
        $printTpl->assign('row_versionV', $stats->version);
        $printTpl->assign('row_argsV', $stats->args);
        $printTpl->assign('row_startV', date($dateFormat, $stats->start));
        $printTpl->assign('row_finishV', date($dateFormat, $stats->finished));
        $printTpl->assign('row_scan_timeV', date('i:s', $stats->finished - $stats->start) . ' seconds');
        $printTpl->assign('row_upV', $stats->hosts_up);
        $printTpl->assign('row_downV', $stats->hosts_down);
        $printTpl->assign('row_totalV', $stats->hosts_total);
    }
}

function testFileExist($file = NULL) {
    return file_exists($file);    
}

function testWritable($name = NULL) {
    return is_file($name) ? is_writable(dirname($name)) : is_writable($name);
}

function testDirnameWritable($name = NULL) {
    return is_writable(dirname($name));
}

function setDirPerms($dir, $nagios_user) {
    //$fileStats = stat();    
}

function testSSH2Mod() {
    return function_exists('ssh2_connect');
}

function isImportedInto($hosts = null){
    if($hosts === null)
        return false;
    
    foreach($hosts as $host){
        if (isset($host['isImport']))
            return true;
    }
    return false;
}

function isValidDelay($time) {
    if ($time < 5 || $time > 30)
        return false;
    
    return true;
}

function test_poller_name($name) {
    return !isPollerExist($name);
}

function getAllowedDupNamesArray($allowedDupNamesStr) {
    return explode(',', str_replace(' ', '', $allowedDupNamesStr));
}

/*
function array_sort($array, $sort_type = 'hostname', $sort_order = 'ASC') {
    global $sort_type, $sort_order;
    
    usort($array, 'array_cmp_by_type');
    
    return $array;
}

function array_cmp_by_type($a, $b) {
    //global $sort_type, $sort_order;
    $sort_type = 'address';
    $sort_order = 'DESC';
    if ($a[$sort_type] == $b[$sort_type]) {
        return 0;
    }
    
    switch ($sort_order) {
        case 'ASC':
            if ($a[$sort_type] > $b[$sort_type]) {
                return +1;
            }
            return -1;
            break;
        case 'DESC':
            if ($a[$sort_type] > $b[$sort_type]) {
                return -1;
            }
            return +1;
            break;
    }
}*/

function array_sort($array, $on, $order='ASC') {

    $new_array = array();
    $sortable_array = array();

    if (count($array) > 0) {
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $k2 => $v2) {
                    if ($k2 == $on) {
                        $sortable_array[$k] = $v2;
                    }
                }
            } else {
                $sortable_array[$k] = $v;
            }
        }

        switch ($order) {
            case 'ASC': asort($sortable_array); break;
            case 'DESC': arsort($sortable_array); break;
        }

        foreach ($sortable_array as $k => $v) {
            $new_array[$k] = $array[$k];
        }
    }
    $index = 0;
    $temp = array();
    foreach ($new_array as $key => $host) {
        $temp[$index++] = $new_array[$key];
    }
    $new_array = $temp;
    
    return $new_array;
}
?>
