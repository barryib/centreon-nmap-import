<?php
/**
 * @author    Thierno IB. Barry
 * @license   GNU/LGPL v2.1
 */
 
require_once $classPath . 'CNI_Nmap.class.php';

if (!isset($oreon)){
    exit();
}

$cniOptions = getGeneralOptions();

$tpl = new Smarty();
$tpl = initSmartyTpl($tplPath, $tpl);

$form = new HTML_QuickForm('formXmlImport', 'POST', '?p='.$p.'&o='.$o);

// Add elements and rules on formUpload
$form->addElement('header', 'header', _("Upload a XML file to parse"));
$form->addElement('header', 'import_xml', _("XML file to parse"));
$xmlFile =& $form->addElement('file', 'xmlFile', _("XML File"));
$form->addElement('submit', 'submitUpload', _("Upload"));
$form->addElement('submit', 'submitImport', _("Import"));
$form->addElement('reset', 'reset', _("Reset"));

$form->addRule('xmlFile', _("A xml file is required"), 'uploadedfile');
$form->addRule('xmlFile', _("The file have to be a xml file"), 'mimetype', array('text/xml') );

$isPrintHostArr = false;
$hostArr = array();
$cniMsg = array();
// Prepare to upload xml file
if ($form->getSubmitValue('submitUpload') && $form->validate() && $xmlFile->isUploadedFile()){
    $submitValues = $form->getSubmitValues();
    $xml = $xmlFile->getValue();
    if($xmlFile->moveUploadedFile($pathFilesUpload)){
        $nmap = nmapXmlParse($pathFilesUpload . $xml['name']);
    
        if(isset($nmap['exception'])){
            $cniMsg['class'] = 'error';
            $cniMsg['msg'] = _($nmap['exception']);
        }
        else{
            $isPrintHostArr = true;
            $hostArr = prepareHostsToPrint($nmap['hosts'], $form);
            $hostArr = array_sort($hostArr, $cniOptions['hosts_sort_type'], $cniOptions['hosts_sort_order']);
            prepareStatsToPrint($nmap['stats'], $tpl);
            
            $cniMsg['class'] = 'info';
            $cniMsg['msg'] = _("The XML file has been done correctly parsed and " . count($hostArr) . " host(s) ha(s)(ve) been found.");
        }
    }
    else{
        $cniMsg['class'] = 'error';
        $cniMsg['msg'] = _("There were some problems during the file upload.");
    }
}
// Prepare to import hosts
if($form->getSubmitValue('submitImport')){
    $submitValues = $form->getSubmitValues();
    if (isImportedInto($submitValues['hosts'])) {
        $hostArr = insertCNIHostArrayInDB($submitValues['hosts'], $cniOptions['allowed_dup_names']);
        if(count($hostArr) == 0){
            $cniMsg['class'] = 'success';
            $cniMsg['msg'] = _("All selected hosts have been correctly imported.");
        }
        else{
            $hostArr = prepareHostsToPrint($hostArr, $form);
            $hostArr = array_sort($hostArr, $cniOptions['hosts_sort_type'], $cniOptions['hosts_sort_order']);
            $cniMsg['class'] = 'error';
            $cniMsg['msg'] = _("These following hosts have not been imported. Retry.");
        }
    }
    else{
        $hostArr = prepareHostsToPrint($submitValues['hosts'], $form);
        $hostArr = array_sort($hostArr, $cniOptions['hosts_sort_type'], $cniOptions['hosts_sort_order']);
        $cniMsg['class'] = 'error';
        $cniMsg['msg'] = _("No hosts have been selected to import.");
    }
    
}

/*
 * Apply a template definition
 */
$renderer =& new HTML_QuickForm_Renderer_ArraySmarty($tpl);
$renderer->setRequiredTemplate('{$label}&nbsp;<font color="red" size="1">*</font>');
$renderer->setErrorTemplate('{$html}&nbsp;<font color="red">{$error}</font>');
$form->accept($renderer);

$tpl->assign('cniMsg', $cniMsg);
$tpl->assign('isPrintHostArr', $isPrintHostArr);
//$tpl->assign('isNotImportedExist', $isNotImportedExist);
$tpl->assign('hostArr', $hostArr);
$tpl->assign('nbHost', count($hostArr));
$tpl->assign('noHostToPrintMsg', _("No hosts to print."));
$tpl->assign('up_icon', $imgPath . 'arrow_up.gif');
$tpl->assign('down_icon', $imgPath . 'arrow_down.gif');
$tpl->assign('form', $renderer->toArray());

$tpl->display("importXml.ihtml");
?>