<?php
/**
 * @author    Thierno IB. Barry
 * @license   GNU/LGPL v2.1
 */
 
if(!isset($oreon)){
    exit();
}

$os = array();
if($os_id){
    $os = getOs($os_id);
}

$tpl = new Smarty();
$tpl = initSmartyTpl($tplPath, $tpl);

$form = new HTML_QuickForm('formOs', 'POST', '?p='.$p.'&o='.$o);

$hostTpls = getHostTemplates();

/*
 * Headers
 */
$form->addElement('header', 'os_info', _("Os Information"));
$form->addElement('header', 'vendor_info', _("Vendor Information"));
$form->addElement('header', 'tpl_info', _("Template Information"));

$form->addElement('text', 'os_name', _("OS Name"), array('size' => 30, 'maxlength' => 30));
$form->addElement('text', 'vendor_logo', _("Vendor Logo"), array('size' => 100, 'maxlength' => 255));
$form->addElement('select', 'template_id', _("Template"), $hostTpls);
$form->addElement('checkbox', 'use_default_template', _("Use default template"), NULL);

$form->addElement('submit', 'submit', _("Save"));
$form->addElement('reset', 'reset', _("Reset"));

if (isset($os) && !empty($os)){
    $form->addElement('header', 'title', _("Modify a OS Configration"));
    $form->addElement('hidden', 'os_id', $os_id);
    $form->setDefaults($os);
}
else{
    $form->addElement('header', 'title', _("Add Os"));
    $form->setDefaults(array('os_name' => '',
                             'vendor_logo' => '',
                             'template_id' => 0,
                             'use_default_template' => 1));
}

if ($form->validate() && $form->getSubmitValue('submit')){
    $submitValues = $form->getSubmitValues();
    $submitValues['use_default_template'] = isset($submitValues['use_default_template']) ? '1' : '0';
    // TODO: Have to test the result of update or insertion to know if evrything is well done 
    if (isset($submitValues['os_id']) && !empty($submitValues['os_id'])){
        updateOsInDB($submitValues);
    }
    else{
        insertOsInDB($submitValues);
    }
    require_once $corePath . 'listOs.php';
}
else{
    /*
     * Apply a template definition
     */
   $renderer =& new HTML_QuickForm_Renderer_ArraySmarty($tpl);
   $renderer->setRequiredTemplate('{$label}&nbsp;<font color="red" size="1">*</font>');
   $renderer->setErrorTemplate('<font color="red">{$error}</font><br />{$html}');
   $form->accept($renderer);	
   $tpl->assign('form', $renderer->toArray());
   //$tpl->assign('o', $o);
   $tpl->display("formOs.ihtml");
}
?>