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

global $pearDB;

$tpl = new Smarty();
$tpl = initSmartyTpl($tplPath, $tpl);

$dbRes =& $pearDB->query("SELECT COUNT(*) FROM `centreon_nmap_import_pollers`");
$tmp = &$dbRes->fetchRow();
$dbRes->free();

if (!$limit) {
    $limit = 30;
}

$cniPollers = getPollerConfig(NULL, NULL, array('num' => $num, 'limit' => $limit, 'search' => $search));

$rows = empty($search) ? $tmp["COUNT(*)"] : count($cniPollers);
require_once './include/common/checkPagination.php';

$tpl->assign('rows', $rows);
$tpl->assign('nbPoller', $rows);
$tpl->assign('noPollerMsg', _("No Centreon Nmap Poller to print. Try to add some."));

$tpl->assign("thead_name", _("Name"));
$tpl->assign("thead_ip_address", _("Ip Address"));
$tpl->assign("thead_status", _("Status"));
$tpl->assign("thead_auth_type", _("Auth. Type"));

$form = new HTML_QuickForm('form', 'POST', "?p=".$p."&o=".$o);

$pollersArr = array();
$style = 'one';
foreach($cniPollers as $cniPoller){
    $selectedElements =& $form->addElement('checkbox', "select[".$cniPoller['poller_id']."]");	
    $pollersArr[] = array('row_class' => 'list_' . $style,
                          'row_select' => $selectedElements->toHtml(),
                          'row_name' => $cniPoller['name'],
                          'row_ip_address' => $cniPoller['ip_address'],
                          'row_link' => '?p=' . $p . '&o=pf&poller_id=' . $cniPoller['poller_id'],
                          'row_auth_username' => _("Enabled"),
                          'row_auth_type' => _("Exchanged SSH Keys"));
    
    $style = $style != 'two' ? 'two' : 'one';	
}
$tpl->assign('pollersArr', $pollersArr);

/*
 * Different messages we put in the template
 */
$msg = array ("addLink" => "?p=" . $p . "&o=pf",
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
                               "pm" => _("Duplicate"),
                               "pd" => _("Delete")),
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
                               "pm" => _("Duplicate"),
                               "pd" => _("Delete")),
                         $attrs);

$form->setDefaults(array('o2' => NULL));
$o2->setValue(NULL);
	
//$tpl->assign('limit', $limit);

/*
 * Apply a template definition
 */
$renderer =& new HTML_QuickForm_Renderer_ArraySmarty($tpl);
$form->accept($renderer);	
$tpl->assign('form', $renderer->toArray());
$tpl->display("listPollers.ihtml");
?>