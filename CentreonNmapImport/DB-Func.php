<?php
/**
 * @author    Thierno IB. Barry
 * @license   GNU/LGPL v2.1
 */
require_once './include/common/common-Func.php';
require_once './include/configuration/configObject/host/DB-Func.php';
require_once './modules/CentreonNmapImport/common.php';

if(!isset($oreon)){
    exit();
}

function getGeneralOptions($optionFields=NULL){
    global $pearDB;
    $fields = '';
    
    if($optionFields === NULL)
	$fields = '*';
    else{
	array_walk($optionFields, 'sanitizeWalk');
	$fields = implode(', ', $optionFields);
    }
    
    $sql = "SELECT ".$fields." FROM centreon_nmap_import_opt LIMIT 1";
    $dbRes =& $pearDB->query($sql);
    if (PEAR::isError($dbRes)) {
	print "Mysql Error : ".$dbRes->getMessage() ." -- ". $sql;
	return false;
    }
    
    return (array_map("myDecode", $dbRes->fetchRow()));
}

function updateGeneralOptions($options = null){
    if(!$options)
	return;
    
    global $pearDB;
    
    array_walk($options, 'nullIfNotIssetWalk');

    $sql =  "UPDATE `centreon_nmap_import_opt` SET";
    $sql .= " `nmap_binary` = " . $options['nmap_binary'];
    $sql .= ", `output_file` = " . $options['output_file'];
    $sql .= ", `my_ip_address` = " . $options['my_ip_address'];
    $sql .= ", `ssh_port` = " . $options['ssh_port'];
    $sql .= ", `path_file_upload` = " . $options['path_file_upload'];
    $sql .= ", `use_sudo` = " . $options['use_sudo'];
    $sql .= ", `daemon_sleep_time` = " . $options['daemon_sleep_time'];
    $sql .= ", `heart_beat_delay` = " . $options['heart_beat_delay'];
    $sql .= ", `hosts_sort_type` = " . $options['hosts_sort_type'];
    $sql .= ", `hosts_sort_order` = " . $options['hosts_sort_order'];
    $sql .= ", `allowed_dup_names` = " . $options['allowed_dup_names'];
    $sql .= " WHERE `opt_id` =" . $options['opt_id'] . " LIMIT 1";
    
    $res =& $pearDB->query($sql);
    
    if (PEAR::isError($res))
	print $res->getDebugInfo()."<br>";
	    
    return getGeneralOptions();	
}

function getBookmarkedNetowrk($opt = NULL) {
    global $pearDB;
    
    if ($opt === NULL) {
	$opt['limit'] 	  = 5;
	$opt['order_by']  = 'scan_number';
	$opt['sort_type'] = 'ASC';
    }
    
    array_walk($opt, 'sanitizeWalk');
    $sql = "SELECT * FROM `centreon_nmap_import_bookmarked_networks`";
    $sql .= " ORDER BY " . $opt['order_by'] . " " . $opt['sort_type'];
    $sql .= " LIMIT " . $opt['limit'];
    $dbRes =& $pearDB->query($sql);
    if (PEAR::isError($dbRes)) {
	print "Mysql Error : ".$dbRes->getMessage();
	return false;
    }
    
    $bookmarks = array();
    while($dbRes->fetchInto($row)){
	$bookmarks[$row['network_id']] = $row;
    }
    return $bookmarks;
}

function getHistory($limit = NULL){
    global $pearDB;
    
    if($limit === NULL)
	return;
    
    $sql = "SELECT * FROM `centreon_nmap_import_history` ORDER BY scan_number LIMIT " . sanitize($limit);
    $dbRes =& $pearDB->query($sql);
    if (PEAR::isError($dbRes)) {
	print "Mysql Error : ".$dbRes->getMessage();
	return false;
    }
    
    $history = array();
    while($dbRes->fetchInto($row)){
	$history[$row['history_id']] = $row;
    }
    return $history;
}

function insertOrUpdateHistory($history = NULL){
    global $pearDB;
    
    if($history === NULL)
	return;
    
    $sql = "SELECT * FROM `centreon_nmap_import_history`";
    $sql .= " WHERE target = '" . sanitize($history['target']) . "' AND netmask = '" . sanitize($history['netmask']) ."'";
    $sql .= " LIMIT 1";
    
    $dbRes =& $pearDB->query($sql);
    
    if($dbRes->numRows() > 0)
	return updateHistory($history);
    
    return insertHistory($history);
}

function insertHistory($history = NULL){
    global $pearDB;
    
    if($history === NULL)
	return;
    
    array_walk($history, 'nullIfNotIssetWalk');
    
    $sql = "INSERT INTO `centreon_nmap_import_history` (`target`, `netmask`)";
    $sql .= " VALUES (";
    $sql .= $history['target'];
    $sql .= ", " . $history['netmask'];
    $sql .= ")";
    
    $dbRes =& $pearDB->query($sql);
    
    if (PEAR::isError($dbRes)){
	print "Mysql Error : ".$dbRes->getMessage();
	return false;
    }
    return true;    
}

function updateHistory($history = NULL){
    global $pearDB;
    
    if(!isset($history['history_id']) || empty($history['history_id']))
	return false;
    
    array_walk($history, 'nullIfNotIssetWalk');
    
    $sql = "UPDATE `centreon_nmap_import_history` SET";"(`target`, `netmask`)";
    $sql .= " ip_address = " . $history['target'];
    $sql .= ", netmask = " . $history['netmask'];
    $sql .= ")";
    
    $dbRes =& $pearDB->query($sql);
    
    if (PEAR::isError($dbRes)){
	print "Mysql Error : ".$dbRes->getMessage();
	return false;
    }
    return true;    
}

function getHostTemplates(){
    global $pearDB;
    
    $hostTemplates = array();
    
    $sql = "SELECT host_id, host_name, host_template_model_htm_id FROM host WHERE host_register = '0' ORDER BY host_name";
    $res =& $pearDB->query($sql);
    
    while($res->fetchInto($hostTemplate)){
	if (!$hostTemplate["host_name"]){
	    $hostTemplate["host_name"] = getMyHostName($hostTemplate["host_template_model_htm_id"]);
	}
	$hostTemplates[$hostTemplate["host_id"]] = $hostTemplate["host_name"];
    }
    return $hostTemplates;
}

function isHostExist($hostname, $hostAddress = NULL) {
    global $pearDB;
    
    $sql = "SELECT host_id FROM `host` WHERE host_name = '".sanitize($hostname)."'";
    if ($hostAddress !== NULL) {
	$sql .= " AND host_address = '".sanitize($hostAddress)."'";
    }
    $sql .= " LIMIT 1";
    $dbRes =& $pearDB->query($sql);
    if ($dbRes->numRows() > 0) {
	return true;
    }
    return false;
}

function isHostAddressExist($hostAddress) {
    global $pearDB;
    if (empty($hostAddress) || $hostAddress == '*') {
	return NULL;
    }

    $sql = "SELECT host_id FROM `host` WHERE host_address = '".sanitize($hostAddress)."' LIMIT 1";
    $dbRes =& $pearDB->query($sql);
    $host =& $dbRes->fetchRow();
    if ($dbRes->numRows() < 0) {
	return NULL;
    }
    return $host['host_id'];
}

function insertCNIHostInDB($host = NULL) {
    global $oreon;
    
    if ($host === NULL) {
	return NULL;
    }
    
    $host_id = insertHost($host, $host['macro']);
    updateHostHostParent($host_id, $host);
    updateHostHostChild($host_id, $host);
    updateHostContactGroup($host_id, $host);
    updateHostContact($host_id, $host);
    updateHostHostGroup($host_id, $host);
    updateHostTemplateService($host_id, $host);
    updateNagiosServerRelation($host_id, $host);
    $oreon->user->access->updateACL();
    
    createCNIHostTemplateService($host_id, $host);
    
    insertHostExtInfos($host_id, $host);
    
    return ($host_id);
}

function createCNIHostTemplateService($host_id = null, $host) {
    global $oreon, $path;
    $path = "./include/configuration/configObject/host/";

    if (!$host_id)
	return;
    
    if (isset($host["dupSvTplAssoc"]["dupSvTplAssoc"]) && $host["dupSvTplAssoc"]["dupSvTplAssoc"]
	&& $host["host_template_model_htm_id"]
	&& $oreon->user->get_version() < 3) {
	
	createHostTemplateService($host_id, $host["host_template_model_htm_id"]);
    }
    else if (isset($host["dupSvTplAssoc"]["dupSvTplAssoc"]) && $host["dupSvTplAssoc"]["dupSvTplAssoc"]
	     && $oreon->user->get_version() >= 3) {
	
	generateHostServiceMultiTemplate($host_id, $host_id);
    }
}
function insertCNIHostArrayInDB($hosts = NULL, $allowedDupNames = 'unknown', $returnImpoted = false){
    if($hosts === NULL or empty($hosts))
	return false;

    $imported = array();
    $notImported = array();
    $allowedDupNames = getAllowedDupNamesArray($allowedDupNames);
    foreach($hosts as $index=>$host){
        if (isset($host['isImport'])){
            $hostToImport['host_name'] = $host['hostname'];
	    $hostToImport['host_alias'] = $hostToImport['host_name'];
            $hostToImport['host_address'] = $host['address'];
            $hostToImport['host_template_model_htm_id'] = $host['hTpl'];
	    $hostToImport['use'] = getMyHostName($hostToImport["host_template_model_htm_id"]);
	    $hostToImport['nagios_server_id'] = $host['nagios_server_id'];
	    $hostToImport['dupSvTplAssoc']['dupSvTplAssoc'] = isset($host['dupSvTplAssoc']['dupSvTplAssoc']) ? '1' : '0';
            $hostToImport['host_register']['host_register'] = '1';
            $hostToImport['host_activate']['host_activate'] = '1';
	    $hostToImport['host_parents'] = array();
	    $hostToImport['macro'] = array('nbOfMacro'=>0);
	    if (isset($host['create_host_parents'])) {
		$hops = traceroute($host['address']);
		foreach ($hops as $hop) {
		    $host_id = isHostAddressExist($hop['ip']);
		    if ($host_id) {
			$hostToImport['host_parents'][] = $host_id;
		    }
		}
	    }
            # Add host into the data base
	    # isError possible values are:
	    #	0 => No error
	    #	1 => Error durring the insertion into the database
	    #	2 => Host already exist into the database
	    $hostNotExist = testHostExistence($hostToImport['host_name']);
	    $hostCanDuplicate = !$hostNotExist && in_array($hostToImport['host_name'], $allowedDupNames);
            if ($hostNotExist || $hostCanDuplicate){
		if ($hostCanDuplicate) {
		    $hostToImport['host_name'] .= '_' . $hostToImport['host_address'];
		}
		$id = insertCNIHostInDB($hostToImport);
		if (isset($id)){
		    $hosts[$index]['isError'] = 0;
		    $hosts[$index]['reason'] = _("Success");
		    $imported[] = $hosts[$index];
		}
		else{				
		    $hosts[$index]['isError'] = 1;
		    $hosts[$index]['reason'] = _("Failed");
		    $notImported[] = $hosts[$index];
		}	
		$id = null;
            }
            else{
                $hosts[$index]['isError'] = 2;
		$hosts[$index]['reason'] = _("Existe in DB");
		$notImported[] = $hosts[$index];
            }
        }
	else {
	    $notImported[] = $hosts[$index];
	}
    }

    if($returnImpoted)
	return $imported;
    
    return $notImported;
}

function isPollerExist($name = NULL, $pollerID = NULL){
    global $pearDB;
    
    $sql = "SELECT name FROM `centreon_nmap_import_pollers` WHERE name = '" . sanitize($name) . "'";
    if($pollerID !== NULL){
	$sql .= " AND poller_id = '" . sanitize($pollerID) . "'";
    }
    $sql .= " LIMIT 1";
    $dbRes =& $pearDB->query($sql);
    
    //print_r($sql);
    if ($dbRes->numRows() > 0)
	return true;
    
    return false;
}

function getPollerConfig($pollerID = NULL, $fieldsArr = NULL, $opt = NULL){
    global $pearDB;
    $fields = '';
    if($fieldsArr === NULL)
	$fields = '*';
    else{
	array_walk($fieldsArr, 'sanitizeWalk');
	$fields = implode(', ', $fieldsArr);
    }
    $sql = "SELECT ".$fields." FROM `centreon_nmap_import_pollers`";
    if($pollerID !== NULL){
	 $sql .= " WHERE poller_id = '".sanitize($pollerID)."' LIMIT 1";
    }
    else{
	if(isset($opt['search']) && !empty($opt['search'])){
	    $sql .= " WHERE name LIKE '%" . sanitize($opt['search']) . "%'";
	}
	if(isset($opt['num']) && isset($opt['limit'])){
	    $sql .= " ORDER BY name LIMIT ".sanitize($opt['num'] * $opt['limit']).", ".sanitize($opt['limit']);
	}
    }
    $dbRes =& $pearDB->query($sql);
    
    if($pollerID !== NULL)
	return $dbRes->fetchRow();
    
    $pollerList = array();
    while($dbRes->fetchInto($poller)){
	$pollerList[$poller['poller_id']] = $poller;
    }
    return $pollerList;
}

function insertPollerInDB($cniPoller = NULL){
    global $pearDB;
    
    if($cniPoller === NULL)
	return;
    
    array_walk($cniPoller, 'nullIfNotIssetWalk');
    
    $sql = "INSERT INTO `centreon_nmap_import_pollers` (`name`, `ip_address`, `ssh_port`, `connection_timeout`, `nmap_binary`, `nmap_output_file`, `use_sudo`)";
    $sql .= " VALUES (";
    $sql .= $cniPoller['name'];
    $sql .= ", " . $cniPoller['ip_address'];
    $sql .= ", " . $cniPoller['ssh_port'];
    $sql .= ", " . $cniPoller['connection_timeout'];
    $sql .= ", " . $cniPoller['nmap_binary'];
    $sql .= ", " . $cniPoller['nmap_output_file'];
    $sql .= ", " . $cniPoller['use_sudo'];
    $sql .= ")";
    
    $dbRes =& $pearDB->query($sql);
    
    if (PEAR::isError($dbRes)){
	print "Mysql Error : ".$dbRes->getMessage();
	return false;
    }
    return true;
}

function updatePollerInDB($cniPoller = NULL){
    global $pearDB;
    
    if(!isset($cniPoller['poller_id']) || empty($cniPoller['poller_id']))
	return false;
    
    array_walk($cniPoller, 'nullIfNotIssetWalk');

    $sql = "UPDATE `centreon_nmap_import_pollers` SET";
    $sql .= " name = " . $cniPoller['name'];
    $sql .= ", ip_address = " . $cniPoller['ip_address'];
    $sql .= ", ssh_port = " . $cniPoller['ssh_port'];
    $sql .= ", connection_timeout = " . $cniPoller['connection_timeout'];
    $sql .= ", nmap_binary = " . $cniPoller['nmap_binary'];
    $sql .= ", nmap_output_file = " . $cniPoller['nmap_output_file'];
    $sql .= ", use_sudo = " . $cniPoller['use_sudo'];
    $sql .= " WHERE poller_id = " . $cniPoller['poller_id'];
    $dbRes =& $pearDB->query($sql);
    
    if (PEAR::isError($dbRes)){
	print "Mysql Error : ".$dbRes->getMessage()." -- ". $sql;
	return false;
    }
    return true;
}

function multiplePollerInDb($pollerIDs = array()){
    global $pearDB;
    
    if(count($pollerIDs) == 0)
	return false;
    
    foreach($pollerIDs as $key => $value){
	$poller = NULL;
	$poller = getPollerConfig($key);
	$poller['name'] = $poller['name'] . '_Copie';
	insertPollerInDB($poller);
    }
}

function deletePollerInDB($pollerIDs = array()){
    global $pearDB;
    
    if(count($pollerIDs) == 0)
	return false;
    
    foreach($pollerIDs as $key => $value){
	$dbRes =& $pearDB->query("DELETE FROM `centreon_nmap_import_pollers` WHERE poller_id = '".sanitize($key)."'");
    }
    
    return true;
}

function getNagiosServer($nsID = NULL, $fieldsArr = NULL, $isLocalhost = false){
    global $pearDB;
    
    $fields = '';
    $localhost = $isLocalhost ? '1' : '0';
    if($fieldsArr === NULL)
	$fields = 'id, name, ns_ip_address, ssh_port, ssh_private_key';
    else{
	array_walk($fieldsArr, 'sanitizeWalk');
	$fields = implode(', ', $fieldsArr);
    }
    $sql = "SELECT " . $fields . " FROM `nagios_server`";
    if($nsID !== NULL)
	 $sql .= " WHERE id = '".sanitize($nsID)."' AND localhost = '".sanitize($localhost)."' ORDER BY name LIMIT 1";
	    else
	$sql .= " WHERE localhost = '".sanitize($localhost)."' ORDER BY name";

    $dbRes =& $pearDB->query($sql);
    if($nsID !== NULL)
	return (array_map("myDecode", $dbRes->fetchRow()));
    
    $nagiosServers = array();
    while($dbRes->fetchInto($ns)){
	$nagiosServers[$ns['id']] = $ns;
    }
    return $nagiosServers;
}

function getOs($osID = NULL, $fieldsArr = NULL, $opt = NULL){
    global $pearDB;
    
    $fields = '';
    if($fieldsArr === NULL)
	$fields = '*';
    else{
	array_walk($fieldsArr, 'sanitizeWalk');
	$fields = implode(', ', $fieldsArr);
    }
    $sql = "SELECT ".$fields." FROM `centreon_nmap_import_os`";
    if($osID !== NULL){
	 $sql .= " WHERE os_id = '".sanitize($osID)."' LIMIT 1";
    }
    else{
	if(isset($opt['search']) && !empty($opt['search'])){
	    $sql .= " WHERE os_name LIKE '%" . sanitize($opt['search']) . "%'";
	}
	if(isset($opt['num']) && isset($opt['limit'])){
	    $sql .= " ORDER BY os_name LIMIT ".sanitize($opt['num'] * $opt['limit']).", ".sanitize($opt['limit']);
	}
    }
    $dbRes =& $pearDB->query($sql);
    if($osID !== NULL)
	return (array_map("myDecode", $dbRes->fetchRow()));
    
    $osList = array();
    while($dbRes->fetchInto($os)){
	$osList[$os['os_id']] = $os;
    }
    return $osList;
}

function getDefaultTemplateForOs($os_name = '') {
    global $pearDB;

    $hTplId = 0;
    $sql = "SELECT template_id FROM `centreon_nmap_import_os` WHERE os_name = '".sanitize($os_name)."' LIMIT 1";
    $dbRes =& $pearDB->query($sql);
    $template =& $dbRes->fetchRow();
    if ($dbRes->numRows() <= 0) {
        $sql = "SELECT template_id FROM `centreon_nmap_import_os` WHERE os_name LIKE '%".sanitize($os_name)."%' LIMIT 1";
        $dbRes =& $pearDB->query($sql);
        $template =& $dbRes->fetchRow();
	if ($dbRes->numRows() <= 0) {
	    return 0;
	}
    }
    //echo($sql);
    return $template['template_id'];
}

function insertOsInDB($os = NULL){
    global $pearDB;
    
    if($os === NULL)
	return;
    
    array_walk($os, 'nullIfNotIssetWalk');

    $sql = "INSERT INTO `centreon_nmap_import_os` (`os_name`, `vendor_logo`, `template_id`, `use_default_template`)";
    $sql .= " VALUES (";
    $sql .= $os['os_name'];
    $sql .= ", " . $os['vendor_logo'];
    $sql .= ", " . $os['template_id'];
    $sql .= ", " . $os['use_default_template'];
    $sql .= ")";
    
    $dbRes =& $pearDB->query($sql);
    
    if (PEAR::isError($dbRes)){
	print "Mysql Error : ".$dbRes->getMessage();
	return false;
    }
    return true;
}

function updateOsInDB($os = NULL){
    global $pearDB;
    
    if(!isset($os['os_id']) || empty($os['os_id']))
	return false;
    
    array_walk($os, 'nullIfNotIssetWalk');

    $sql = "UPDATE `centreon_nmap_import_os` SET";
    $sql .= " os_name = " . $os['os_name'];
    $sql .= ", vendor_logo = " . $os['vendor_logo'];
    $sql .= ", template_id = " . $os['template_id'];
    $sql .= ", use_default_template = " . $os['use_default_template'];
    $sql .= " WHERE os_id = " . $os['os_id'];
    $dbRes =& $pearDB->query($sql);
    
    if (PEAR::isError($dbRes)){
	print "Mysql Error : ".$dbRes->getMessage();
	return false;
    }
    return true;
}

function multipleOsInDb($osIDs = array()){
    global $pearDB;
    
    if(count($osID) == 0)
	return false;
    
    foreach($osIDs as $key => $value){
	$os = NULL;
	$os = getOs($key);
	$os['os_name'] = $os['os_name'] . '_Copie';
	insertOsInDB($os);
    }
}

function deleteOsInDB($osIDs = array()){
    global $pearDB;
    
    if(count($osIDs) == 0)
	return false;
    
    foreach($osIDs as $key => $value){
	$dbRes =& $pearDB->query("DELETE FROM `centreon_nmap_import_os` WHERE os_id = '" . sanitize($key) . "'");
    }
    
    return true;
}

function useDefaultTemplateOsInDb($osID = NULL, $bool){
    global $pearDB;
    
    if($osID === NULL)
	return false;
    
    $bool = $bool ? 1 : 0;
    $sql = "UPDATE `centreon_nmap_import_os` SET";
    $sql .= " `use_default_template` = '" . $bool . "'";
    $sql .= " WHERE `os_id` = " . sanitize($osID) . " LIMIT 1";
    
    $dbRes =& $pearDB->query($sql);
    if (PEAR::isError($dbRes)){
	print "Mysql Error : ".$dbRes->getMessage();
	return false;
    }
    return true;
}
?>
