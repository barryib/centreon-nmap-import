<?php
/**
 * @author    Thierno IB. Barry
 * @license   GNU/LGPL v2.1
 */
 
require_once $classPath . 'CNI_Nmap.class.php';
require_once $classPath . 'CNI_Cmd.class.php';

if (!isset($oreon)){
    exit();
}
global $pearDB;

$cniOptions = getGeneralOptions();

$pollers = getPollerConfig();
$pollerSelect = array(NULL => _("Select a poller..."));
foreach($pollers as $row){
    $pollerSelect[$row['poller_id']] = $row['name'];
}

$history = getHistory($cniOptions['history_size']);
$historySelectOpt = array(NULL => _("History..."));
foreach($history as $row){
    $historySelectOpt[$row['history_id']] = $row['target'];
    $historySelectOpt[$row['history_id']] .= empty($row['netmask']) ? '' : '/'.$row['netmask'];
}

$tpl = new Smarty();
$tpl = initSmartyTpl($tplPath, $tpl);

$form = new HTML_QuickForm('formPScan', 'POST', '?p='.$p.'&o='.$o);

$form->addElement('header', 'header', _("Distributed nmap scan"));
$form->addElement('header', 'scan_form', _("Scan Host or Network from selected poller"));
$form->addElement('header', 'scan_stats', _("Scan statistics"));
$form->addElement('select', 'poller_select', _("Select poller"), $pollerSelect);
$form->addElement('text', 'target', _("IP or DNS"), array('size' => 60));
$form->addElement('text', 'netmask', _("Mask"), array('size' => 10, 'maxlength' => 2));
$form->addElement('select', 'history', NULL, $historySelectOpt);
$form->addElement('checkbox', 'is_os_detect', _("OS detection"), NULL);
$form->addElement('checkbox', 'is_service_detect', _("Service detection"), NULL);
$form->addElement('checkbox', 'is_all_detect', _("Detect all"), NULL);
$form->addElement('submit', 'submitScan', _("Scan"));
$form->addElement('submit', 'submitImport', _("Import"));
$form->addElement('reset', 'reset', _("Reset"));

$form->addRule('poller_select', _("You must select a poller from which it will scan"), 'required');
$form->addRule('target', _("IP, DNS or Subnet is required"), 'required');
//$form->addRule('target', _("Ip or Subnet address not valid"), 'regex', '/^((25[0-5]|2[0-4]\d|1?\d?\d).){3}(25[0-5]|2[0-4]\d|1?\d?\d)$/');

$isPrintHostArr = false;
$hostArr = array();
$cniMsg = array();

// Prepare to Scan a target
if($form->getSubmitValue('submitScan') && $form->validate()){
    $submitValues = $form->getSubmitValues();
    // Construct the target
    $target = $submitValues['target'];
    if(isset($submitValues['netmask']) && !empty($submitValues['netmask']))
        $target .=  '/' . $submitValues['netmask'];
    
    $options = array(
                     'os_detect' => isset($submitValues['is_os_detect']),
                     'service_info' => isset($submitValues['is_service_detect']),
                     'all_options' => isset($submitValues['is_all_detect'])
                    );
    $cmd = new CNI_Cmd($pearDB, $submitValues['poller_select']);
    if ($cmd->write(array($target), $options)) {
        $cniMsg['class'] = 'success';
        $cniMsg['msg'] = _("Your remote commande has been write correctly");
    }
    else {
        $cniMsg['class'] = 'error';
        $cniMsg['msg'] = _("Failed to write your remote command"); 
    }
}

// Prepare to import hosts
if($form->getSubmitValue('submitImport')){
    $submitValues = $form->getSubmitValues();
    $hostArr = insertCNIHostArrayInDB($submitValues['hosts'], $cniOptions['allowed_dup_names']);
    if(count($hostArr) == 0){
        $cniMsg['class'] = 'success';
        $cniMsg['msg'] = _("All selected hosts have been correctly imported.");
    }
    else{
        $cniMsg['class'] = 'error';
        $cniMsg['msg'] = _("These following hosts have not been imported. Retry.");
        $hostArr = prepareHostsToPrint($hostArr, $form);
        $hostArr = array_sort($hostArr, $cniOptions['hosts_sort_type'], $cniOptions['hosts_sort_order']);
    }
}

?>

<script type="text/javascript">
    // Heart beat delay to check the controller.json.php
    const HEART_BEAT_DELAY = <?php echo $cniOptions['heart_beat_delay']; ?>;
</script>

<?php
/*
 * Apply a template definition
 */
$renderer =& new HTML_QuickForm_Renderer_ArraySmarty($tpl);
$renderer->setRequiredTemplate('{$label}&nbsp;<font color="red" size="1">*</font>');
$renderer->setErrorTemplate('{$html}&nbsp;<font color="red">{$error}</font>');
$form->accept($renderer);

$tpl->assign('cniMsg', $cniMsg);
$tpl->assign('isPrintHostArr', $isPrintHostArr);
$tpl->assign('hostArr', $hostArr);
$tpl->assign('nbHost', count($hostArr));
$tpl->assign('noHostToPrintMsg', _("No hosts to print."));
$tpl->assign('up_icon', $imgPath . 'arrow_up.gif');
$tpl->assign('down_icon', $imgPath . 'arrow_down.gif');
$tpl->assign('form', $renderer->toArray());

$tpl->display("pollerScan.ihtml");
?>