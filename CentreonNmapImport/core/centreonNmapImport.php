<?php
/**
 * @author    Thierno IB. Barry
 * @license   GNU/LGPL v2.1
 */
 
//ini_set('display_errors', 1);
//ini_set('log_errors', 1);
//error_reporting(E_ALL);

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

switch ($o){
    case 'l'  : require_once($corePath.'localScan.php'); break;
    case 'p' : require_once($corePath.'pollerScan.php'); break;
    case 'x' : require_once($corePath.'importXml.php'); break;
    case 'i' : require_once($corePath.'importInDB.php'); break;
    default : require_once($corePath.'localScan.php'); break;
}
?>