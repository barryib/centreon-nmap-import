<?php
/**
 * execute Nmap with ssh remote command and copy the output file by scp local server.
 *
 * @author    Thierno IB. Barry
 * @license   GNU/LGPL v2.1
 */
require_once '/etc/centreon/centreon.conf.php';
require_once $centreon_path . 'www/class/centreonDB.class.php';
require_once $centreon_path . 'www/modules/CentreonNmapImport/class/CNI_Nmap.class.php';
require_once $centreon_path . 'www/modules/CentreonNmapImport/DB-Func.php';

define ('CMD_SSH_FAILED',   '-2');
define ('CMD_SCP_FAILED',   '-1');
define ('CMD_ALL_SUCCESS',  '0');
define ('CMD_SSH_SUCCESS',  '1');
define ('CMD_IS_STOPED',    '2');
define ('CMD_IS_STARTED',   '3');

class CNI_Cmd{
    /**
     * Database handler
     *
     * @var pear DB
     */
    private $_localPearDB;
    
    /**
     * Poller id in database
     *
     * @var unsigned int
     */
    private $_pollerID;
    
    private $_nmapBinary;
    
    private $_nmapOutpuFile;
    
    private $_useSudo;
    
    private $_errors;

    /**
     * Creates a new CNI_Nmap object
     * 
     * @param db $pearDB database handler.
     * @param int $pollerID poller id into the database
     */
    public function __construct($pearDB = NULL, $pollerID = NULL, $autoLoad = true){
        $this->_localPearDB = is_object($pearDB) ? $pearDB : new CentreonDB();
        $this->_errors = array();
        if ($pollerID !== NULL) {
            $this->_pollerID = $pollerID;
            if ($autoLoad) {
                $this->_loadPollerConf();
            }
        }
    }
    
    private function _loadPollerConf() {
        if ($this->_pollerID) {
            $pollerConf = getPollerConfig($this->_pollerID);
            $this->_nmapBinary = $pollerConf['nmap_binary'];
            $this->_nmapOutpuFile = $pollerConf['nmap_output_file'];
            $this->_useSudo = $pollerConf['use_sudo'] == 1 ? true : false;
        } else {
            $this->_nmapBinary = '';
            $this->_nmapOutpuFile = '';
            $this->_useSudo = false;
        }
    }
    
    /**
     * Prepare the command to execute
     *
     * @param array $targets contains hostnames, IP addresses, networks to scan.
     * @param array $nmapOptions contains options for nmap.
     * @return string
     */
    private function _createNmapCmd($targets, $nmapOptions){
        $tmpOptions = array('nmap_binary' => $this->_nmapBinary,
                            'output_file' => $this->_nmapOutpuFile);

        $nmap = new CNI_Nmap($tmpOptions);
        $nmap->enableOptions($nmapOptions);
        
        return $nmap->createCNICommandLine($targets, $this->_useSudo);
    }
    
    public function write($targets, $nmapOptions){
        $cmd = $this->_createNmapCmd($targets, $nmapOptions);
        
        if (!$this->isCmdExist($targets[0])) {
            $sql = "INSERT INTO `centreon_nmap_import_cmds` (`target`, `cmd`, `poller_id`, `status`)";
            $sql .= " VALUES ( '".sanitize($targets[0])."', '".sanitize($cmd)."', '".sanitize($this->_pollerID)."', '".sanitize(CMD_IS_STOPED)."')";
            
            $dbRes =& $this->_localPearDB->query($sql);
            if (PEAR::isError($dbRes)) {
                $this->_errors[] = "Mysql Error : ".$dbRes->getMessage()." -> ".$sql;
                return false;
            }
            return true;
        }
        $this->_errors[] = _("A nmap scan has already been started on the target: ".sanitize($targets[0])." for this poller");
        return false;
    }
    
    private function isCmdExist($target) {
        /**
         * @todo Test on command status (if stop)
         */ 
        $sql = "SELECT cmd_id FROM `centreon_nmap_import_cmds`";
        $sql .= " WHERE target = '".sanitize($target)."' AND poller_id = '".sanitize($this->_pollerID)."' LIMIT 1";
        $dbRes =& $this->_localPearDB->query($sql);
        if (PEAR::isError($dbRes)) {
            $this->_errors[] = "Mysql Error : ".$dbRes->getMessage();
            return false;
        }
        return $dbRes->numRows() > 0;
    }
    
    public function getCmd($cmd_id) {
        $sql = "SELECT * FROM `centreon_nmap_import_cmds` WHERE cmd_id = '".sanitize($cmd_id)."'";
        $dbRes =& $this->_localPearDB->query($sql);
        if (PEAR::isError($dbRes)){
            $this->_errors[] = "Mysql Error : ".$dbRes->getMessage() . " -> " . $sql;
            return false;
        }
        return $dbRes->fetchRow();
    }
    
    public function getAllCmds() {
        $sql = "SELECT * FROM `centreon_nmap_import_cmds`";
        $dbRes =& $this->_localPearDB->query($sql);
        $retCmds = array();
        if (PEAR::isError($dbRes)){
            $this->_errors[] = "Mysql Error : ".$dbRes->getMessage() . " -> " . $sql;
            return false;
        }
        while ($dbRes->fetchInto($row)) {
            $row['cmd'] = html_entity_decode($row['cmd'], ENT_QUOTES);
            $retCmds[$row['cmd_id']] = $row;
        }
        return $retCmds;
    }
    
    public function getCmdByStatus($status) {
        if (!is_array($status)) {
            $status = array($status);
        }
        
        //$sqlWhere = '';
        foreach ($status as $key => $val) {
            $status[$key] = "status = '".sanitize($val)."'";
        }
        $sqlWhere = implode(' OR ', $status);
        $sql = "SELECT * FROM `centreon_nmap_import_cmds` WHERE " . $sqlWhere;
        //return $sql;
        $dbRes =& $this->_localPearDB->query($sql);
        $retCmds = array();
        if (PEAR::isError($dbRes)){
            $this->_errors[] = "Mysql Error : ".$dbRes->getMessage() . " -> " . $sql;
            return false;
        }
        while ($dbRes->fetchInto($row)) {
            $row['cmd'] = html_entity_decode($row['cmd'], ENT_QUOTES);
            $retCmds[$row['cmd_id']] = $row;
        }
        return $retCmds;
    }
    
    public function getNonImportedCmds() {
        $sql = "SELECT * FROM `centreon_nmap_import_cmds` WHERE is_imported = '0'";
        $dbRes =& $this->_localPearDB->query($sql);
        if (PEAR::isError($dbRes)) {
            $this->_errors[] = "Mysql Error : ".$dbRes->getMessage();
            return false;
        }
        $retCmds = array();
        while ($dbRes->fetchInto($row)) {
            $row['cmd'] = html_entity_decode($row['cmd'], ENT_QUOTES);
            $retCmds[$row['cmd_id']] = $row;
        }
        return $retCmds;
    }
    
    public function getNbCmd(){
        $sql = "SELECT cmd_id FROM `centreon_nmap_import_cmds`";
        $dbRes =& $this->_localPearDB->query($sql);
        if (PEAR::isError($dbRes)) {
            $this->_errors[] = "Mysql Error : ".$dbRes->getMessage();
            return false;
        }
        return $dbRes->numRows();
    }
    
    public function delete($cmd_id) {
        $sql = "DELETE FROM `centreon_nmap_import_cmds` WHERE cmd_id = '".sanitize($cmd_id)."'";
        $dbRes =& $this->_localPearDB->query($sql);
        if (PEAR::isError($dbRes)) {
            $this->_errors[] = "Mysql Error : ".$dbRes->getMessage();
            return false;
        }
        return true;
    }
    
    public function getStrErrors(){
        return implode(', ', $this->_errors);
    }
    
    public function getError(){
        return $this->_errors;
    }
    
    public function hasImported($cmd_id) {
        $sql = "UPDATE `centreon_nmap_import_cmds` SET is_imported = '1' WHERE cmd_id = '".sanitize($cmd_id)."'";
        $dbRes =& $this->_localPearDB->query($sql);
        if (PEAR::isError($dbRes)) {
            $this->_errors[] = "Mysql Error : ".$dbRes->getMessage();
            return false;
        }
        return true;
    }
}
?>