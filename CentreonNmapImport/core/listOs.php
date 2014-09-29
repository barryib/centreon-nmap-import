<?php
/**
 * @author    Thierno IB. Barry
 * @license   GNU/LGPL v2.1
 */
 
if (!isset($oreon)){
    exit();
}

require_once './include/common/autoNumLimit.php';
require_once './include/common/quickSearch.php';

//$num = isset($_GET['num']) ? $_GET['num'] : $_POST['num'];
//$limit = isset($_GET['limit']) ? $_GET['limit'] : $_POST['limit'];

$tpl = new Smarty();
$tpl = initSmartyTpl($tplPath, $tpl);

$dbRes =& $pearDB->query("SELECT COUNT(*) FROM `centreon_nmap_import_os`");
$tmp = &$dbRes->fetchRow();
$dbRes->free();

if (!$limit) {
    $limit = 30;
}

$osList = getOs(NULL, NULL, array('num' => $num, 'limit' => $limit, 'search' => $search));

$rows = empty($search) ? $tmp["COUNT(*)"] : count($osList);
require_once './include/common/checkPagination.php';

$tpl->assign('rows', $rows);
$tpl->assign('nbOs', $rows);
$tpl->assign('noOsMsg', _("No Centreon Nmap OS to print. Try to add some."));

$tpl->assign("thead_os_type", _("OS Type"));
//$tpl->assign("thead_logo", _("Logo"));
$tpl->assign("thead_template", _("Template"));
$tpl->assign("thead_use_default_template", _("Use Default Template"));

/*
 * Form begin
 */ 
$form = new HTML_QuickForm('form', 'POST', "?p=".$p."&o=".$o);

$hostTpls = getHostTemplates();
$osArr = array();
foreach($osList as $os){
    $selectedElements =& $form->addElement('checkbox', "select[".$os['os_id']."]");
    
    if ($os['use_default_template'] == 1)
        $udtImg = "<a href='main.php?p=".$p."&os_id=".$os['os_id']."&o=ou&limit=".$limit."&num=".$num."&search=".$search."'><img src='img/icones/16x16/element_previous.gif' border='0' alt='"._("Disabled")."' title='"._("Disabled")."'></a>&nbsp;&nbsp;";
    else
        $udtImg = "<a href='main.php?p=".$p."&os_id=".$os['os_id']."&o=oa&limit=".$limit."&num=".$num."&search=".$search."'><img src='img/icones/16x16/element_next.gif' border='0' alt='"._("Enable")."' title='"._("Enable")."'></a>&nbsp;&nbsp;";
    
    $vendorImg = '<img src="' . $imgPath . $os['vendor_logo'].'" border="0" title="' . $os['os_name'] . '"/>';
    $hostTpl = isset($hostTpls[$os['template_id']]) ? $hostTpls[$os['template_id']] : _("Default Template not defined");
    $osArr[] = array('row_select' => $selectedElements->toHtml(),
                     'row_os_name' => $os['os_name'],
                     'row_vendor_logo' => $vendorImg,
                     //'row_vendor_logo' => $os['vendor_logo'],
                     'row_link' => '?p=' . $p . '&o=of&os_id=' . $os['os_id'],
                     'row_template' => $hostTpl,
                     'row_use_default_template' => $udtImg);
}
$tpl->assign('osArr', $osArr);

/*
 * Different messages we put in the template
 */
$msg = array ("addLink" => "?p=" . $p . "&o=of",
              "addText" => _("Add"),
              "delConfirm" => _("Do you confirm the deletion ?"));

$tpl->assign('msg', $msg);

/*
* Toolbar select 
*/
?>

<script type="text/javascript">
    function setO(_i) {
        document.forms['form'].elements['o'].value = _i;
    }
</script>

<?php
/*
 * Add top select menu o1
 */ 
$attrs = array('onchange' => "javascript: " .
               "if (this.form.elements['o1'].selectedIndex == 1 && confirm('"._("Do you confirm the duplication ?")."')) {" .
               "    setO(this.form.elements['o1'].value); submit();} " .
               "else if (this.form.elements['o1'].selectedIndex == 2 && confirm('"._("Do you confirm the deletion ?")."')) {" .
               "    setO(this.form.elements['o1'].value); submit();} " .
               "else if (this.form.elements['o1'].selectedIndex == 3) {" .
               "    setO(this.form.elements['o1'].value); submit();}");

$o1 =& $form->addElement('select', 'o1', NULL,
                         array(NULL => _("More actions..."),
                               "om" => _("Duplicate"),
                               "od" => _("Delete")),
                         $attrs);

$form->setDefaults(array('o1' => NULL));
$o1->setValue(NULL);

/*
 * Add bottom select menu o2
 */ 
$attrs = array('onchange' => "javascript: " .
               "if (this.form.elements['o2'].selectedIndex == 1 && confirm('"._("Do you confirm the duplication ?")."')) {" .
               "    setO(this.form.elements['o2'].value); submit();} " .
               "else if (this.form.elements['o2'].selectedIndex == 2 && confirm('"._("Do you confirm the deletion ?")."')) {" .
               "    setO(this.form.elements['o2'].value); submit();} " .
               "else if (this.form.elements['o2'].selectedIndex == 3) {" .
               "    setO(this.form.elements['o2'].value); submit();}");

$o2 =& $form->addElement('select', 'o2', NULL,
                         array(NULL => _("More actions..."),
                               "om" => _("Duplicate"),
                               "od" => _("Delete")),
                         $attrs);

$form->setDefaults(array('o2' => NULL));
$o2->setValue(NULL);

$tpl->assign('limit', $limit);

/*
 * Apply a template definition
 */
$renderer =& new HTML_QuickForm_Renderer_ArraySmarty($tpl);
$renderer->setRequiredTemplate('{$label}&nbsp;<font color="red" size="1">*</font>');
$renderer->setErrorTemplate('<font color="red">{$error}</font>');

$form->accept($renderer);
//$tpl->assign('o', $o);
$tpl->assign('form', $renderer->toArray());
$tpl->display("listOs.ihtml");
?>