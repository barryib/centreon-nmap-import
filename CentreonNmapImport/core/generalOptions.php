<?php
/**
 * @author    Thierno IB. Barry
 * @license   GNU/LGPL v2.1
 */
 
if (!isset($oreon)){
    exit();
}

require_once 'System.php';

$txtXS = array('size' => 5);
$txtS = array('size' => 10);
$txtM = array('size' => 20);
$txtL = array('size' => 50);
$txtXL = array('size' => 100);

$form = new HTML_QuickForm('generalOptions', 'POST', '?p='.$p);

$form->addElement('header', 'header', _("Modify General Options"));
$form->addElement('header', 'daemon', _("Daemon cnicore Options"));
$form->addElement('header', 'refresh', _("Refresh properties"));
$form->addElement('header', 'display', _("Display properties"));
$form->addElement('header', 'import', _("Import properties"));
$form->addElement('text', 'nmap_binary', _("Nmap Binary Path"), $txtL);
$form->addElement('text', 'output_file', _("XML Output File"), $txtXL);
$form->addElement('text', 'history_size', _("History Size"), $txtXS);
$form->addElement('text', 'daemon_sleep_time', _("Cnicore sleep time"), $txtXS);
$form->addElement('text', 'heart_beat_delay', _("Json controller heart beat delay"), $txtXS);
$form->addElement('text', 'my_ip_address', _("Ip address/DNS"), $txtM);
$form->addElement('checkbox', 'use_sudo', _("Use sudo during the local scan"), NULL);
$form->addElement('text', 'ssh_binary', _("SSH Binary Path"), $txtL);
$form->addElement('text', 'ssh_port', _("SSH port"), $txtXS);
$form->addElement('text', 'path_file_upload', _("Files upload path"), $txtXL);
$sort_order = array('ASC' => _("Ascending"), 'DESC' => _("Descending"));
$form->addElement('select', 'hosts_sort_order', _("Hosts sort order "), $sort_order);
$sort_type = array('hostname'=> _("Hostname"),
                   'address' => _("IP address"),
                   'status'  => _("Status"),
                   'os'      => _("OS"));
$form->addElement('select', 'hosts_sort_type', _("Sort hosts by  "), $sort_type);
$form->addElement('text', 'allowed_dup_names', _("Allowed duplicate names. Comma separated"), $txtL);
$form->addElement('hidden', 'opt_id');
$form->addElement('submit', 'optSubmit', _("SAVE"));
$form->addElement('reset' , 'optRst'   , _("RESET"));

$form->registerRule('test_delay', 'callback', 'isValidDelay');
$form->addRule('heart_beat_delay', _("The delay must be between 5 and 30s"), 'test_delay');
$form->addRule('heart_beat_delay', _("A heart beat delay in second is required"), 'required');

$form->addRule('daemon_sleep_time', _("The delay must be between 5 and 30s"), 'test_delay');
$form->addRule('daemon_sleep_time', _("A time in second is required"), 'required');

$form->registerRule('test_nmap_binary', 'callback', 'testFileExist');
$form->addRule('nmap_binary', _("Nmap binary doesn't exist."), 'test_nmap_binary');
$form->addRule('nmap_binary', _("Nmap binary path is required"), 'required');

$form->registerRule('test_writable_basename', 'callback', 'testDirnameWritable');
$form->addRule('output_file', _("The base name is not writable"), 'test_writable_basename');
$form->addRule('output_file', _("A XML output file path is required"), 'required');

$form->registerRule('test_writable_dir', 'callback', 'testDirnameWritable');
$form->addRule('path_file_upload', _("The directory is not writable"), 'test_writable_dir');
$form->addRule('path_file_upload', _("A path to upload file is required"), 'required');

//$form->registerRule('test_ssh_binary_exist', 'callback', 'testFileExist');
//$form->registerRule('test_ssh2_mod', 'callback', 'testSSH2Mod');
//$form->addRule('ssh_binary', _("SSH binary doesn't exist"), 'test_ssh_binary_exist');
//$form->addRule('ssh_binary', _("SSH2 apache's module doesn't exist"), 'test_ssh2_mod');
//$form->addRule('ssh_binary', _("SSH binary path is required"), 'required');

if(!isset($_POST['optSubmit'])){
    $options = getGeneralOptions();
    $form->setDefaults(array(
        // General
        'opt_id'            => $options['opt_id'],
        'my_ip_address'     => $options['my_ip_address'],
        'path_file_upload'  => empty($options['path_file_upload']) ? $pathFilesUpload : $options['path_file_upload'],
        'history_size'      => empty($options['history_size']) ? 5 : $options['history_size'],
        'use_sudo'          => $options['use_sudo'],
        'daemon_sleep_time' => empty($options['daemon_sleep_time']) ? 5 : $options['daemon_sleep_time'],
        'heart_beat_delay'  => empty($options['heart_beat_delay']) ? 10 : $options['heart_beat_delay'],
        'hosts_sort_type'   => $options['hosts_sort_type'],
        'hosts_sort_order'  => $options['hosts_sort_order'],
        'allowed_dup_names' => empty($options['allowed_dup_names']) ? 'unknown' : $options['allowed_dup_names'],
        // SSH
        'ssh_binary'        => empty($options['ssh_binary']) ? System::which('ssh') : $options['ssh_binary'],
        'ssh_port'          => empty($options['ssh_port']) ? 22 : $options['ssh_port'],
        // Nmap
        'nmap_binary'       => empty($options['nmap_binary']) ? System::which('nmap') : $options['nmap_binary'],
        'output_file'       => empty($options['output_file']) ? $pathFilesUpload . 'nmap.xml' : $options['output_file']
    ));
    //$opt_id->setValue($options['opt_id']);
}
else if($form->validate()){
    $submitValues = $form->getSubmitValues();
    $submitValues['use_sudo'] = isset($submitValues['use_sudo']) ? '1' : '0';
    $updateOptions = updateGeneralOptions($submitValues);
    $form->setDefaults($updateOptions);
    //$opt_id->setValue($updateOptions['opt_id']);
}

$tpl = new Smarty();
$tpl = initSmartyTpl($tplPath, $tpl);

// Renderrer for template
$renderer =& new HTML_QuickForm_Renderer_ArraySmarty($tpl);
$renderer->setRequiredTemplate('{$label}&nbsp;<font color="red" size="1">*</font>');
$renderer->setErrorTemplate('{$html}&nbsp;<font color="red">{$error}</font>');

$form->accept($renderer);
$tpl->assign('form', $renderer->toArray());
$tpl->assign("server_info_icone", "./img/icones/16x16/clipboard.gif");
$tpl->assign("server_info_header", _("Server Information"));
$tpl->assign("ssh_icone", "./img/icones/16x16/lock.gif");
$tpl->assign("ssh_header", _("SSH Information"));
$tpl->assign("nmap_info_icone", "./img/icones/16x16/hat_red.gif");
$tpl->assign("nmap_info_header", _("Nmap Information"));
$tpl->assign("allowed_dup_names_info", _("Ex: name1, name2, name3"));
$tpl->assign('use_sudo_warning', _("Activate the scan with sudo would cause some problems, if sudo is not configured on the remote host."));

$tpl->display("generalOptions.ihtml");
?>