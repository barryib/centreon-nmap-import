/**
 * @author    Thierno IB. Barry
 * @license   GNU/LGPL v2.1
 */
 
//const HEART_BEAT_DELAY = 10;
const CONTROLLER_URL = "./modules/CentreonNmapImport/core/controller.json.php";

jQuery.noConflict();
jQuery(document).ready(function($){
    heartbeat();
    daemonStatus();
    
    $('input[name="submitScan"]').click(function(){
	var _target = $('input[name="target"]').val();
	var _netmask = $('input[name="netmask"]').val();
	var _pollerID = $('select[name="poller_select"]').val();
	
	if (_pollerID != '' && _target != '') {
	    removeMsg();
	    var _args = {
		'target': _target,
		'netmask': _netmask,
		'poller_id': _pollerID
	    };
	    var _options = {
		'os_detect': $('input[type=checkbox][name="is_os_detect"]').is(':checked'),
		'service_info': $('input[type=checkbox][name="is_service_detect"]').is(':checked'),
		'all_options': $('input[type=checkbox][name="is_all_detect"]').is(':checked')
	    };
	    //console.log(_args);
	    writeCmd(_args, _options);
	} else {
	    addMsg('error', 'You must select a poller and enter a IP or DNS, before doing a remote scan.');
	}
	
	return false;
    });
    
    $('#set-daemon-status').click(function(){
	var _status = $(this).attr('href');
	_status = _status.split('#');
	_status = _status[1];
	daemonStatus(_status);
	return false;
    });
    
    $('#get-daemon-status').click(function(){
	daemonStatus();
	return false;
    })
    //$('input[name="submitImport"]').click(function(){
	//var _tab = $(this).parents('.tab:first');
	//alert('ok');
	//return false;
    //});
});

function daemonStatus(status) {
    if (!status) {
	status = '';
    }
    
    jQuery.ajax({
        type: "POST",
        url: CONTROLLER_URL,
        data: {"action": "daemonStatus", "status": status},
        cache: false,
        //async: false,
        dataType: "json",
        success: function(result){
            if(result.diag.error){
                // ToDo: Print error
		addMsg('error', result.diag.msg);
            }
            else{
		var _action = result.result.status.code ? 'stop' : 'start';
		jQuery('#set-daemon-status').attr('href', '#'+_action)
		    .attr('title', 'click to '+_action+' cnicore')
		    .text(result.result.status.msg)
		    .parent()
			.attr('class', 'SubTableTactical '+result.result.status.css);
            }
        }
    });
}

function heartbeat() {
    jQuery.ajax({
        type: "POST",
        url: CONTROLLER_URL,
        data: {"action": "heartbeat"},
        cache: false,
        //async: false,
        dataType: "json",
        success: function(result){
            if(result.diag.error){
                // ToDo: Print error
		addMsg('error', result.diag.msg);
            }
            else{
		//removeMsg();
                updateAllCmds(result.result);
            }
        }
    });
    setTimeout('heartbeat()', HEART_BEAT_DELAY * 1000);
}

function getHostsFromCmd(pollerID, cmdID) {
    if (cmdID == null)
	return false;
    
    jQuery.ajax({
        type: "POST",
        url: CONTROLLER_URL,
        data: {"action": "getHosts", "cmd_id": cmdID},
        cache: false,
        //async: false,
        dataType: "json",
        success: function(result){
            if(result.diag.error){
                // ToDo: Print error
		addMsg('error', result.diag.msg);
            }
            else{
		removeMsg();
                addHosts(pollerID, cmdID, result.result.hosts, result.result.filters);
		updateStats(result.result.stats);
            }
        }
    });
}

function writeCmd(args, options) {
    jQuery.ajax({
        type: "POST",
        url: CONTROLLER_URL,
        data: {'action': 'write', 'args': args, 'options': options},
        cache: false,
        //async: false,
        dataType: "json", 
        success: function(result){
            if(result.diag.error){
                // ToDo: Print error
		addMsg('error', result.diag.msg);
            }
            else{
                addMsg('success', result.diag.msg);
            }
        }
    });
}

function importHosts(pollerID, cmdID, hosts) {
    jQuery.ajax({
        type: "POST",
        url: CONTROLLER_URL,
        data: {'action': 'import', 'cmd_id': cmdID, 'hosts': hosts},
        cache: false,
        //async: false,
        dataType: "json", 
        success: function(result){
            if(result.diag.error){
                // ToDo: Print error
		addMsg('error', result.diag.msg);
            }
            else{
                addMsg('success', result.diag.msg);
		clearImportedHosts(pollerID, cmdID);
            }
        }
    });
}


function updateAllCmds(cmds) {
    var _nbCmd = cmds.length;
    for (var _i = 0; _i < _nbCmd; _i++) {
	addPoller(cmds[_i].cmd.poller);
	addCmd(cmds[_i].cmd);
    }
    removeOrphanCmds(cmds);
    updateStatus();
}

function addPoller(poller) {
    if (poller == null)
	return false;
    
    var _pollerListDOMID = '#poller-ajax #poller-list';
    var _pollerRowDOMID = '#mainnav #'+poller.poller_id;
    if (!jQuery(_pollerListDOMID).length) {
	pollerDOMSkeleton();
    }
    if (!jQuery(_pollerListDOMID).find(_pollerRowDOMID).length) {
	jQuery(_pollerListDOMID).find('#mainnav').append(
	    '<li id="'+poller.poller_id+'" class="a">'+
		'<img src="./img/icones/16x16/server_network.gif">'+
		'<a href="#'+poller.poller_id+'-tab">&nbsp;'+poller.name+'&nbsp;</a>'+
	    '</li>'
	);
	// Bind events
	jQuery(_pollerListDOMID).find(_pollerRowDOMID+' a').click(function(){
	    removeMsg();
	    showTab(jQuery(this).attr('href'));
	    return false;
	});
    }
    // ToDo: Update poller style
}

function addCmd(cmd) {
    if (cmd == null)
	return false;

    var _tabDOMID = '#'+cmd.poller.poller_id+'-tab';
    var _cmdRowDOMID = '#'+cmd.poller.poller_id+'-'+cmd.cmd_id+'-cmd';
    var _pollerName = jQuery('#'+cmd.poller.poller_id+' a').text();
    var _html = '<a id="'+cmd.poller.poller_id+'-'+cmd.cmd_id+'-cmd" href="#'+cmd.poller.poller_id+'-'+cmd.cmd_id+'-cmd" class="'+cmd.status+'">nmap '+cmd.target+'</a>';
    
    if (!jQuery(_tabDOMID).length) {
	pollerTabDOMSkeleton(cmd.poller);
    }
    if (jQuery(_tabDOMID).find(_cmdRowDOMID).length == 0) {
	jQuery(_tabDOMID)
	    .find('.cmd-mainnav tr.nav')
		.append('<td>'+_html+'&nbsp;</td>');
    } else {
	jQuery(_tabDOMID).find(_cmdRowDOMID).attr('class', cmd.status);
    }
    
    jQuery(_tabDOMID)
	.find(_cmdRowDOMID)
	    .unbind('click')
	    .click(function(){
		removeMsg();
		if (jQuery(this).hasClass('ready')) {
		    var _href = jQuery(this).attr('id');
		    _href = _href.split('-');
		    var _pollerID = _href[0];
		    var _cmdID = _href[1];
		    
		    getHostsFromCmd(_pollerID, _cmdID);
		} else {
		    addMsg('error', 'This command has not ready to import.<br>'+cmd.error_reason+'.');
		}
		return false;
	    });
}

function removeOrphanCmds(cmds) {
    var _nbCmd = cmds.length;
    jQuery('.cmd-mainnav .nav td a').each(function(i, cmdDOM){
	var _cmdDOM = jQuery(cmdDOM);
	var _id = _cmdDOM.attr('id').split('-');
	var _exist = false;
	for(var _i=0; _i < _nbCmd && !_exist; _i++) {
	    if (_id[1] == cmds[_i].cmd.cmd_id) {
		_exist = true;
	    }
	}
	if (!_exist) {
	    _cmdDOM.parent().remove();
	    //console.log(_tab)
	}
    });    
}

function clearImportedHosts(pollerID, cmdID) {
    // 1. remove command
    // 2. remove .cmd-mainnav if empty    
    // 3. empty .host-list-table .host-list
    // 4. remove .host-list-table #filter
    // 5. remove .host-list-table .validForm
    // 6. actualize notification count
    var _cmdRow = jQuery('#'+pollerID+'-'+cmdID+'-cmd').parent();
    _cmdRow.parents('.tab:first')
	.find('.host-list-table')
	.empty()
	.append('<table class="host-list ListTable" style="margin-top:0;"></table>');

    if (!_cmdRow.siblings().length) {
	_cmdRow.parents('.tab:first').remove();
	//console.log('ok')
    } else {
	_cmdRow.remove();
    }
    
    var _pollerLi = jQuery('#poller-list #mainnav #'+pollerID);
    if (!_pollerLi.siblings().length) {
	_pollerLi.parents('#poller-ajax').empty();
	//console.log('ok')
    } else {
	_pollerLi.remove();
    }
    updateStatus();
    //jQuery('#'+pollerID+'-'+cmdID+'-host-list').remove();
}

function addHost(pollerID, cmdID, host, rowStyle) {
    var _hostListDOM = jQuery('#'+pollerID+'-tab .host-list').attr('id', pollerID+'-'+cmdID+'-host-list');
    var _hostRowDOM = _hostListDOM.find('.host-row');
    var _statusImg = host.status == 'up' ? jQuery('#up-icon').val() : jQuery('#down-icon').val();
    if (_hostRowDOM.length == 0) {
	// Add table header
	_hostListDOM.append(
	    '<tr class="ListHeader">'+
		'<td class="ListColHeaderPicker"><input type="checkbox" name="checkall"></td>'+
		'<td class="ListColHeaderCenter">Hostname</td>'+
		'<td class="ListColHeaderCenter">Address</td>'+
		'<td class="ListColHeaderCenter">Status</td>'+
		'<td class="ListColHeaderCenter">OS</td>'+
		'<td class="ListColHeaderCenter">Poller</td>'+
		'<td class="ListColHeaderCenter">Template</td>'+
		'<td class="ListColHeaderCenter">Create Service</td>'+
	    '</tr>'
	)
    }
    // Add host
    _hostListDOM.append(
	//'<table id="host-list-'+pollerID+'" class="ListTable">'+
	    '<tr id="'+host.index+'-host" class="host-row  '+rowStyle+' '+host.cssClass+'">'+
		'<td class="isImport ListColPicker">'+host.isImport+'</td>'+
		'<td class="hostname ListColCenter">'+
		    '<span class="editable" id="edit-'+pollerID+'-'+host.index+'">'+host.hostname+'</span>'+
		    '<input type="hidden"/>'+
		'</td>'+
		'<td class="address ListColCenter">'+host.address+'</td>'+
		'<td class="status ListColCenter">'+
		    '<img src="'+_statusImg+'" title="status" alt="'+host.status+'"/>'+
		'</td>'+
		'<td class="os ListColCenter">'+host.os+'</td>'+
		'<td class="ns_select ListColCenter">'+host.ns_select+'</td>'+
		'<td class="hTpl ListColCenter">'+host.hTpl+'</td>'+
		'<td class="dupSvTplAssoc ListColCenter">'+host.dupSvTplAssoc.dupSvTplAssoc+'</td>'+
	    '</tr>'
	//'</table>'
    );
}

function addHosts(pollerID, cmdID, hosts, filters) {
    var _nbHost = hosts.length;
    var _rowStyle = '';
    jQuery('#'+pollerID+'-tab .host-list').empty();
    for (var _i = 0; _i < _nbHost; _i++) {
	_rowStyle = _i % 2 ? 'list_one' : 'list_two';
	addHost(pollerID, cmdID, hosts[_i], _rowStyle);
    }
    if (_nbHost > 0) {
	var _html = '<div class="validForm"><p class="oreonbutton"><input type="submit" value="Import" name="submitImport"></p></div>';
	var _hostListDOM = jQuery('#'+pollerID+'-tab #'+pollerID+'-'+cmdID+'-host-list');
	
	addSearchAndFilterBar(pollerID, cmdID, filters);
	
	_hostListDOM.siblings('div.validForm').remove();
	_hostListDOM.before(_html).after(_html);
	
	jQuery('#'+pollerID+'-tab .host-list-table input[name="submitImport"]').click(triggerImportHost);
    }
}

function triggerImportHost() {
    var _hostListDOM = jQuery(this).parents('div.validForm:first').siblings('table.host-list:first');
    var _hostListID = _hostListDOM.attr('id').split('-');
    
    var _pollerID = _hostListID[0];
    var _cmdID = _hostListID[1];
    var _hostArray = [];
    var _cpt = 0;
    _hostListDOM.find('.isImport input[type=checkbox]:checked').each(function(i, obj){
	var _hostDOM = jQuery(obj).parents('.host-row:first');
	var _host = {
	    'isImport': true,
	    'hostname': _hostDOM.find('.hostname span').text(),
	    'address': _hostDOM.find('.address').text(),
	    'status': _hostDOM.find('.status img').attr('alt'),
	    'os': _hostDOM.find('.os').text(),
	    'hTpl': _hostDOM.find('.hTpl select').val(),
	    'nagios_server_id': _hostDOM.find('.ns_select select').val(),
	    'poller_id': _pollerID,
	    'dupSvTplAssoc': {'dupSvTplAssoc': _hostDOM.find('.dupSvTplAssoc input[type=checkbox]').is(':checked')}
	}
	_hostArray[_cpt++] = _host;
    });
    if (!_hostArray.length) {
	addMsg('error', 'There is no host to import.');
    } else {
	removeMsg();
	importHosts(_pollerID, _cmdID, _hostArray);
    }
    
    return false;
}

function updateStats(stats) {
    var _rightPannel = statsDOMSkeleton();
    _rightPannel.find('#scan').html(stats.scanner);
    _rightPannel.find('#version').html(stats.version);
    _rightPannel.find('#args').html(stats.args);
    _rightPannel.find('#start').html(stats.start);
    _rightPannel.find('#finish').html(stats.finished);
    _rightPannel.find('#scantime').html(stats.scantime);
    _rightPannel.find('#hosts-up').html(stats.hosts_up);
    _rightPannel.find('#hosts-down').html(stats.hosts_down);
    _rightPannel.find('#hosts-total').html(stats.hosts_total);
}

function updateStatus() {
    jQuery('#poller-list #mainnav li a').each(function(i, obj){
	var _pollerLink = jQuery(obj);
	var _id = _pollerLink.attr('href');
	var _nbReady = jQuery(_id).find('.cmd-mainnav .nav a.ready').length;
	var _span = _pollerLink.find('span');
	
	if (_nbReady) {
	    if (_span.length) {
		_span.text(_nbReady);
	    } else {
		_pollerLink.append('<span>'+_nbReady+'</span>');
	    }
	} else {
	    _span.remove();
	}
    });
}

function addMsg(cssClass, msg) {
    var _cniMsg = jQuery('#left-pannel #cni-msg');
    if (_cniMsg.length) {
	_cniMsg.attr('class', cssClass).text(msg);
    }
    else {
	jQuery('#left-pannel #validForm').after('<div class="'+cssClass+'" id="cni-msg">'+msg+'</div>');
    }
}

function removeMsg() {
    jQuery('#left-pannel #cni-msg').remove();
}

function addSearchAndFilterBar(pollerID, cmdID, filters) {
    var _hostListDOM = jQuery('#'+pollerID+'-tab #'+pollerID+'-'+cmdID+'-host-list');
    var _html = '<table class="ajaxOption" id="filter">'+
		'<tr>'+
		    '<td>Host&nbsp;</td>'+
		    '<td><input type="text" size="20" id="search-cni-host">&nbsp;</td>'+
		    '<td>State:</td>'+
		    '<td>'+filters.filter_state+'</td>'+
		    '<td>Os:</td>'+
		    '<td>'+filters.filter_os+'</td>'+
		    '<td>Service:</td>'+
		    '<td>'+filters.filter_service+'</td>'+
		    '<td>In DB:</td>'+
		    '<td>'+filters.filter_inDB+'</td>'+
		'</tr>'+
	    '</table>';
    
    jQuery('#'+pollerID+'-tab #filter').remove();
    _hostListDOM.before(_html);
    
    //jQuery('#'+pollerID+'-tab #search-cni-host').keyup(triggerSearch);
    //jQuery('#'+pollerID+'-tab #filter select').change(triggerFilter);
    //return _hostListDOM;
}

function statsDOMSkeleton() {
    var _rightPan = jQuery('#right-pannel');
    if (_rightPan.length)
	return _rightPan;
    
    jQuery('#left-pannel').after(
	'<div id="right-pannel">'+
	    '<table id="stats-pannel" class="ListTable">'+
		'<tr class="ListHeader"><td class="FormHeader" colspan="2"><img src="./img/icones/16x16/chart.gif">&nbsp;&nbsp;Scan Statistics</td></tr>'+
		'<tr class="list_lvl_1"><td class="ListColLvl1_name" colspan="2"><img src="./img/icones/16x16/about.gif">&nbsp;&nbsp;General informations</td></tr>'+
		'<tr class="list_two"><td>Scan</td><td id="scan"></td></tr>'+
		'<tr class="list_one"><td>Version</td><td id="version"></td></tr>'+
		'<tr class="list_two"><td>Args</td><td id="args"></td></tr>'+
		'<tr class="list_lvl_1"><td class="ListColLvl1_name" colspan="2"><img src="./img/icones/16x16/clock.gif">&nbsp;&nbsp;Time stats</td></tr>'+
		'<tr class="list_one"><td>Start</td><td id="start"></td></tr>'+
		'<tr class="list_two"><td>Finish</td><td id="finish"></td></tr>'+
		'<tr class="list_one"><td>Scan have taken</td><td id="scantime"></td></tr>'+
		'<tr class="list_lvl_1"><td class="ListColLvl1_name" colspan="2"><img src="./img/icones/16x16/clients.gif">&nbsp;&nbsp;Hosts stats</td></tr>'+
		'<tr class="list_two"><td>Up</td><td id="hosts-up"></td></tr>'+
		'<tr class="list_one"><td>Down</td><td id="hosts-down"></td></tr>'+
		'<tr class="list_two"><td>Total</td><td id="hosts-total"></td></tr>'+
	    '</table>'+   
	'</div>'
    );
    
    return jQuery('#right-pannel');
}

function pollerDOMSkeleton() {
    jQuery('#poller-ajax').append(
	'<div id="poller-list">'+
	    '<ul id="mainnav"></ul>'+
	'</div>'
    );
}

function pollerTabDOMSkeleton(poller) {
    jQuery('#poller-ajax').append(
	'<div class="tab" id="'+poller.poller_id+'-tab">'+
	    '<table class="ListTable" style="margin-top: 0;">'+
		'<tr class="ListHeader">'+
		    '<td valign="top" class="FormHeader">'+
			'<img src="./img/icones/16x16/server_network.gif">&nbsp;'+poller.name+'&nbsp;'+
		    '</td>'+
		'</tr>'+
		'<tr>'+
		    '<td>'+
			'<table id="'+poller.poller_id+'-cmd-nav" class="ajaxOption cmd-mainnav">'+
			    '<tr class="nav"></tr>'+
			'</table>'+
		    '</td>'+
		'</tr>'+
		'<tr>'+
		    '<td align="center" colspan="" class="host-list-table" style="padding-top: 20px;">'+
			'<table class="host-list ListTable" style="margin-top:0;"></table>'+
		    '</td>'+
		'</tr>'+	 		
	    '</table>'+
	'</div>'
    );
}

function showTab(id) {
    jQuery('#poller-ajax .tab.visible').addClass('hidden').removeClass('visible');
    jQuery(id).addClass('visible');
}