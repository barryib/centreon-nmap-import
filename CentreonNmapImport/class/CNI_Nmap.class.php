<?php
/**
 * @author    Thierno IB. Barry
 * @license   GNU/LGPL v2.1
 */
require_once 'Nmap.php';

class CNI_Nmap extends Net_Nmap{
    public function createCNICommandLine($targets, $with_sudo = false) {
        return $this->createCommandLine($targets, $with_sudo);
    }
}
?>