<?php
/**
 * @author    Thierno IB. Barry
 * @license   GNU/LGPL v2.1
 */
class CNI_History{
    private $_table;
    private $_localPearDB;
    
    private $_history;
    private $_size;
    
    public function __construct($pearDB, $size = NULL, $history = NULL){
        $this->table = 'centreon_nmap_import_history';
        $this->_localPearDB = $pearDB;
        
        if($size === NULL){
            $options = getOptions(array('history_size'));
            $this->_size = is_numeric($options['history_size']) ? $options['history_size'] : 5;
        }
        if($history === NULL){
            $sql = "SELECT * FROM ".$this->_table." ORDER BY scan_number LIMIT ".$this->_size;
            $dbRes =& $this->_localPearDB->query($sql);
            if (!$dbRes->numRows())
                $this->_history = array();
            
            $this->_history = array();
            while($dbRes->fetchInto($row)){
                $this->_history[$row['history_id']] = $row;
            }
        }
        else{
            $this->_history = $history;
        }
    }
    
    public function getHistory(){
        return $this->_history;
    }
    
    public function setHistory($history){
        $this->_history = $history;
    }
    
    public function addHistoryRow($cmd){
        //array_map("sanitize", $cmd);
        
        $sql = "INSERT INTO `".$this->_table."` (`ip_address`, `netmask`) VALUES ('".$cmd['ip_address']."', '".$cmd['netmask']."')";
        $dbRes =& $this->_localPearDB->query($sql);
        if (PEAR::isError($this->_localPearDB)) {
            print "Mysql Error : ".$this->_localPearDB->getMessage();
            return false;
        }
        return true;
    }
    
    public function updateRowScanNumber($row){
        if(!array_key_exists('target', $row))   return false;
        if(!array_key_exists('netmask', $row))  return false;
        
        //array_map("sanitize", $row);
        
        $row['scan_number']++;
        
        $sql = "UPDATE ".$this->_table." SET scan_number = '".$row['scan_number'].
                "' WHERE target = '".$row['target']."' AND netmask = '".$row['netmask']."'";
                
        $dbRes =& $this->_localPearDB->query($sql);
        if (PEAR::isError($this->_localPearDB)) {
            print "Mysql Error : ".$this->_localPearDB->getMessage();
            return false;
        }
        return true;
    }
    
    public function deleteHistoryRow($row){
        if(!array_key_exists('ip_address', $row))   return false;
        if(!array_key_exists('netmask', $row))      return false;
        
        $sql = "DELETE FROM ".$this->_table." WHERE ip_address = '".$row['ip_address']."' AND netmask = '".$row['netmask']."'";
                
        $dbRes =& $this->_localPearDB->query($sql);
        if (PEAR::isError($this->_localPearDB)) {
            print "Mysql Error : ".$this->_localPearDB->getMessage();
            return false;
        }
        return true;
    }
    
    public function getSize(){
        return $this->_size;
    }
    
    public function setSize($size){
        $this->_size = $size;
    }
    
    public function clear(){
        $sql = "DELETE FROM ".$this->_table;
                
        $dbRes =& $this->_localPearDB->query($sql);
        if (PEAR::isError($this->_localPearDB)) {
            print "Mysql Error : ".$this->_localPearDB->getMessage();
            return false;
        }
        $this->_history = array();
        return true;
    }
}
?>