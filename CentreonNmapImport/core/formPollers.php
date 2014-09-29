<?php
/**
 * @author    Thierno IB. Barry
 * @license   GNU/LGPL v2.1
 */
 
if(!isset($oreon)){
    exit();
}

$cniPoller = array();
if($poller_id){
    $cniPoller = getPollerConfig($poller_id);
}

$nagios_servers = getNagiosServer();
$ns_select_kv = array(NULL => _("Nagios Servers..."));
foreach($nagios_servers as $ns){
    $ns_select_kv[$ns['id']] = $ns['name'];
}

$tpl = new Smarty();
$tpl = initSmartyTpl($tplPath, $tpl);

$form = new HTML_QuickForm('formPollers', 'POST', '?p='.$p.'&o='.$o);

$auth_type_array = array('password' => 'Password', 'pubkey' => 'Priv & Pub key');

/*
 * Headers
 */
$form->addElement('header', 'poller_info', _("Poller Information"));
$form->addElement('header', 'ssh_info', _("SSH Information"));
$form->addElement('header', 'nmap_info', _("Nmap Information"));

$attrs = array('onchange' => "javascript: submit();");
$nsID =& $form->addElement('select', 'ns_id', _("Get Poller Information from Nagios Server"), $ns_select_kv, $attrs);
$nsName =& $form->addElement('text', 'name', _("Sattelite Name"), array('size' => 30, 'maxlength' => 30));
$nsIpAdd =& $form->addElement('text', 'ip_address', _("IP Address"), array('size' => 30, 'maxlength' => 30));
$form->addElement('select', 'auth_type', _("Authentification Type"), $auth_type_array);
$nsSshPort =& $form->addElement('text', 'ssh_port', _("SSH port"),  array('size' => 6, 'maxlength' => 6));
$form->addElement('text', 'connection_timeout', _("SSH Connection timeout"), array('size' => 6, 'maxlength' => 3));
$form->addElement('text', 'nmap_binary', _("Nmap Binary"), array('size' => 100, 'maxlength' => 255));
$form->addElement('text', 'nmap_output_file', _("Nmap Output File"), array('size' => 100, 'maxlength' => 255));
$form->addElement('checkbox', 'use_sudo', _("Use sudo during the scan"), NULL);

$form->addElement('submit', 'submitBtn', _("Save"));
$form->addElement('reset', 'reset', _("Reset"));

//$form->registerRule('is_poller_name_exist', 'callback', 'isPollerExist');
//$form->addRule('name', _("This poller name exists already"), 'is_poller_name_exist');
$form->addRule('name', _("A name is required"), 'required');
$form->addRule('ip_address', _("A IP address or DNS is required"), 'required');
$form->addRule('nmap_binary', _("Nmap binary path is required"), 'required');
$form->addRule('nmap_output_file', _("A XML output file path is required"), 'required');

if (isset($cniPoller) && !empty($cniPoller)){
    $form->addElement('header', 'title', _("Modify a poller Configuration"));
    $form->addElement('hidden', 'poller_id', $poller_id);
    $form->setDefaults($cniPoller);
}
else{
    $form->addElement('header', 'title', _("Add poller"));
    $form->setDefaults(array('name' => '',
                     'ip_address' => '',
                     'ssh_port' => 22,
                     'connection_timeout' => 5,
                     'nmap_binary' => '',
                     'nmap_output_file' => '/tmp/nmap.xml',
                     'use_sudo', '0'));
}

if(isset($ns_id) && !empty($ns_id)){
    $ns = getNagiosServer($ns_id);
    
    $nsName->setValue($ns['name']);
    $nsIpAdd->setValue($ns['ns_ip_address']);
    $nsSshPort->setValue(empty($ns['ssh_port']) ? 22 : $ns['ssh_port']);
    #$nsKey->setValue(empty($ns['ssh_private_key']) ? '/home/nagios/.ssh/id_rsa' : $ns['ssh_private_key']);
    $nsID->setValue($ns_id);
}

if ($form->validate() && $form->getSubmitValue('submitBtn')){
    $submitValues = $form->getSubmitValues();
    $submitValues['use_sudo'] = isset($submitValues['use_sudo']) ? '1' : '0';
    $submitValues['ssh_port'] = empty($submitValues['ssh_port']) ? 22 : $submitValues['ssh_port'];
    // TODO: Test the result of update or insertion to know if evrything is well done 
    if (isset($submitValues['poller_id']) && !empty($submitValues['poller_id'])){
        updatePollerInDB($submitValues);
    }
    else{
        insertPollerInDB($submitValues);
    }
    require_once $corePath . 'listPollers.php';
}
else{
    /*
     * Apply a template definition
     */
   $renderer =& new HTML_QuickForm_Renderer_ArraySmarty($tpl);
   $renderer->setRequiredTemplate('{$label}&nbsp;<font color="red" size="1">*</font>');
   $renderer->setErrorTemplate('{$html}&nbsp;<font color="red">{$error}</font>');
   $form->accept($renderer);
   $tpl->assign('use_sudo_warning', _("Activate the scan with sudo would cause some problems, if sudo is not configured on the remote host."));
   $tpl->assign('form', $renderer->toArray());
   $tpl->assign('o', $o);
   $tpl->display("formPollers.ihtml");
}
?>