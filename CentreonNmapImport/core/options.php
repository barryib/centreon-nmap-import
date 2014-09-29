<?php
/**
 * @author    Thierno IB. Barry
 * @license   GNU/LGPL v2.1
 */
 
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

if (!isset ($oreon))
    exit ();
  
// Pear library
require_once 'HTML/QuickForm.php';
require_once 'HTML/QuickForm/advmultiselect.php';
require_once 'HTML/QuickForm/Renderer/ArraySmarty.php';

// Path to the configuration dir
$modulePath = './modules/CentreonNmapImport/';
$classPath = $modulePath . 'class/';
$corePath = $modulePath . 'core/';
$tplPath = $modulePath . 'tpl/';
$imgPath = $modulePath . 'includes/img/';
$pathFilesUpload = realpath($modulePath) . '/filesUpload/';

// PHP functions
require_once $modulePath . 'DB-Func.php';
require_once $modulePath . 'common.php';

$pG = isset($_GET['poller_id']) ? $_GET['poller_id'] : NULL;
$pP = isset($_POST['poller_id']) ? $_POST['poller_id'] : NULL;
$poller_id = isset($pG) ? $pG : $pP;

$oG = isset($_GET['os_id']) ? $_GET['os_id'] : NULL;
$oP = isset($_POST['os_id']) ? $_POST['os_id'] : NULL;
$os_id = isset($oG) ? $oG : $oP;

$nG = isset($_GET['ns_id']) ? $_GET['ns_id'] : NULL;
$nP = isset($_POST['ns_id']) ? $_POST['ns_id'] : NULL;
$ns_id = isset($nG) ? $nG : $nP;

$sG = isset($_GET['select']) ? $_GET['select'] : NULL;
$sP = isset($_POST['select']) ? $_POST['select'] : NULL;
$select = isset($sG) ? $sG : $sP;

switch ($o){
    // Centreon Nmap Import general options 
    case 'g'  : require_once($corePath.'generalOptions.php'); break;
    // Centreon Nmap Import OS options
    case 'ol' : require_once($corePath.'listOs.php'); break;
    case 'of' : require_once($corePath.'formOs.php'); break;
    case 'od' : deleteOsInDB($select); require_once($corePath.'listOs.php'); break;
    case 'om' : multipleOsInDb($select); require_once($corePath.'listOs.php'); break;
    case 'oa' : useDefaultTemplateOsInDb($os_id, true); require_once($corePath.'listOs.php'); break;
    case 'ou' : useDefaultTemplateOsInDb($os_id, false); require_once($corePath.'listOs.php'); break;
    // Centreon Nmap Import pollers options
    case 'pl' : require_once($corePath.'listPollers.php'); break;
    case 'pf' : require_once($corePath.'formPollers.php'); break;
    case 'pd' : deletePollerInDB($select); require_once($corePath.'listPollers.php'); break;
    case 'pm' : multiplePollerInDb($select); require_once($corePath.'listPollers.php'); break;
    
    default : require_once($corePath.'generalOptions.php'); break;
}
?>