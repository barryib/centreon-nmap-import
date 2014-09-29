--
-- Add topology
--
INSERT INTO `topology` (`topology_id`, `topology_name`, `topology_icone`, `topology_parent`, `topology_page`, `topology_order`, `topology_group`, `topology_url`, `topology_url_opt`, `topology_popup`, `topology_modules`, `topology_show`) VALUES (
    '', 'Centreon Nmap Import', NULL, 6, 699, 100, 1, './modules/CentreonNmapImport/core/centreonNmapImport.php', NULL, '0', '1', '1'
);

INSERT INTO `topology` (`topology_id`, `topology_name`, `topology_icone`, `topology_parent`, `topology_page`, `topology_order`, `topology_group`, `topology_url`, `topology_url_opt`, `topology_popup`, `topology_modules`, `topology_show`) VALUES (
    '', 'Local Scan', './img/icones/16x16/house.gif', 699, 69901, 100, 1, './modules/CentreonNmapImport/core/centreonNmapImport.php', '&o=l', '0', '1', '1'
);

INSERT INTO `topology` (`topology_id`, `topology_name`, `topology_icone`, `topology_parent`, `topology_page`, `topology_order`, `topology_group`, `topology_url`, `topology_url_opt`, `topology_popup`, `topology_modules`, `topology_show`) VALUES (
    '', 'Poller Scan', './img/icones/16x16/client_network.gif', 699, 69902, 100, 1, './modules/CentreonNmapImport/core/centreonNmapImport.php', '&o=p', '0', '1', '1'
);

INSERT INTO `topology` (`topology_id`, `topology_name`, `topology_icone`, `topology_parent`, `topology_page`, `topology_order`, `topology_group`, `topology_url`, `topology_url_opt`, `topology_popup`, `topology_modules`, `topology_show`) VALUES (
    '', 'Import from XML', './img/icones/16x16/import2.gif', 699, 69903, 100, 1, './modules/CentreonNmapImport/core/centreonNmapImport.php', '&o=x', '0', '1', '1'
);

--
--
--
INSERT INTO `topology` (`topology_id`, `topology_name`, `topology_icone`, `topology_parent`, `topology_page`, `topology_order`, `topology_group`, `topology_url`, `topology_url_opt`, `topology_popup`, `topology_modules`, `topology_show`) VALUES (
    '', 'Centreon Nmap Import', './img/icones/16x16/data_find.gif', 507, 50799, 180, 1, './modules/CentreonNmapImport/core/options.php', '', '0', '1', '1'
);
INSERT INTO `topology` (`topology_id`, `topology_name`, `topology_icone`, `topology_parent`, `topology_page`, `topology_order`, `topology_group`, `topology_url`, `topology_url_opt`, `topology_popup`, `topology_modules`, `topology_show`) VALUES (
    '', 'General', './img/icones/16x16/tool.gif', 50799, 5079901, 180, 1, './modules/CentreonNmapImport/core/options.php', '&o=g', '0', '1', '1'
);
INSERT INTO `topology` (`topology_id`, `topology_name`, `topology_icone`, `topology_parent`, `topology_page`, `topology_order`, `topology_group`, `topology_url`, `topology_url_opt`, `topology_popup`, `topology_modules`, `topology_show`) VALUES (
    '', 'Pollers', './img/icones/16x16/server_network.gif', 50799, 5079902, 180, 1, './modules/CentreonNmapImport/core/options.php', '&o=pl', '0', '1', '1'
);
INSERT INTO `topology` (`topology_id`, `topology_name`, `topology_icone`, `topology_parent`, `topology_page`, `topology_order`, `topology_group`, `topology_url`, `topology_url_opt`, `topology_popup`, `topology_modules`, `topology_show`) VALUES (
    '', 'OS', './img/icones/16x16/component_green.gif', 50799, 5079903, 180, 1, './modules/CentreonNmapImport/core/options.php', '&o=ol', '0', '1', '1'
);

--
-- Structure de la table `nmap_scanner_opt`
--
CREATE TABLE IF NOT EXISTS `centreon_nmap_import_opt` (
    `opt_id` INT( 11 ) NOT NULL AUTO_INCREMENT,
    `nmap_binary` VARCHAR( 255 ) NULL,
    `output_file` VARCHAR( 255 ) NULL,
    `history_size` INT(2) NOT NULL default 5,
    `use_sudo` enum('0','1') default 1,
    `my_ip_address` VARCHAR( 255 ) NULL,
    `ssh_port` INT(6) default 22,
    `path_file_upload` VARCHAR( 255 ) NULL,
    `daemon_sleep_time` INT(2) default 5,
    `heart_beat_delay` INT(2) default 10,
    `hosts_sort_type` VARCHAR( 10 ) NOT NULL DEFAULT 'hostname',
    `hosts_sort_order` VARCHAR( 10 ) NOT NULL DEFAULT 'ASC',
    `allowed_dup_names` VARCHAR( 255 ) NOT NULL DEFAULT 'unknown',
    `www_user` VARCHAR( 20 ) NOT NULL Default 'www-data',
    PRIMARY KEY ( `opt_id` )
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

--
-- Contenu de la table `centreon_nmap_import_opt`
--
INSERT INTO `centreon_nmap_import_opt` (
    `history_size`,
    `ssh_port`
)
VALUES (
    5, 22
);

--
-- Structure de la table `centreon_nmap_import_history`
--
CREATE TABLE IF NOT EXISTS `centreon_nmap_import_history` (
    `history_id` INT( 11 ) NOT NULL AUTO_INCREMENT ,
    `target` VARCHAR( 255 ) NOT NULL ,
    `netmask` VARCHAR( 255 ) NOT NULL ,
    `scan_number` INT(5) default 0,
    PRIMARY KEY ( `history_id` )
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `centreon_nmap_import_pollers` (
    `poller_id` INT( 11 ) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR( 30 ) NULL,
    `ip_address` VARCHAR( 30 ) NULL,
    `ssh_port` INT( 6 ) NULL,
    `connection_timeout` INT( 3 ) default 3,
    `nmap_binary` VARCHAR( 255 ) NULL,
    `nmap_output_file` VARCHAR( 255 ) NULL,
    `use_sudo` enum('0','1') default 0,
    PRIMARY KEY (`poller_id`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

--
--
--
CREATE TABLE IF NOT EXISTS `centreon_nmap_import_cmds` (
    `cmd_id` INT( 11 ) NOT NULL AUTO_INCREMENT ,
    `target` VARCHAR( 255 ) NOT NULL ,
    `cmd` VARCHAR( 255 ) NOT NULL ,
    `poller_id` INT( 11 ) NOT NULL ,
    `status` INT( 1 ) NOT NULL default 2 ,
    `creation_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
    `start_timestamp` INT( 20 ) NULL ,
    `stop_timestamp` INT( 20 ) NULL ,
    `is_imported` INT( 1 ) NOT NULL default 0 ,
    `error_reason` VARCHAR( 255 ) NULL ,
    `scp_tmp_file` VARCHAR( 255 ) NULL ,
    PRIMARY KEY ( `cmd_id` )
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

--
-- Structure de la table `constructors`
--
CREATE  TABLE IF NOT EXISTS `centreon_nmap_import_os` (
    `os_id` INT(11) NOT NULL AUTO_INCREMENT ,
    `os_name` VARCHAR(50) NOT NULL ,
    `vendor_logo` VARCHAR(255) NOT NULL ,
    `template_id` INT(11) default 0 ,
    `use_default_template` enum('0','1') default 1,
    PRIMARY KEY (`os_id`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

--
-- Contenu de la table `centreon_nmap_import_opt`
--
INSERT INTO `centreon_nmap_import_os` VALUES ('', '3Com', 'vendors/3com.png', '0', '1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Aironet', 'vendors/aironet.png', '0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Alcatel', 'vendors/alcatel.png', '0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Allied Telesyn', 'vendors/allied.png', '0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'AmigaOS', 'vendors/amigaos.png', '0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'APC', 'vendors/apc.png', '0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Apple Color LaserWriter', 'vendors/apple.png', '0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Apple LaserWriter', 'vendors/apple.png', '0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Apple Airport', 'vendors/apple.png', '0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Apple Mac OS X', 'vendors/apple.png', '0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Apple Mac OS 7', 'vendors/apple.png', '0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Apple Mac OS 8', 'vendors/apple.png', '0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Apple Mac OS 9', 'vendors/apple.png', '0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Atari','vendors/atari.png', '0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'AtheOS','vendors/atheos.png', '0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Avaya','vendors/avaya.png', '0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Avocent','vendors/avocent.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Axent','vendors/axent.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'AXIS','vendors/axis.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'BeOS','vendors/beos.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Blue Coat','vendors/bluecoat.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'BlueCoat','vendors/bluecoat.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Borderware','vendors/borderware.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Brother','vendors/brother.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'BSDI BSD/OS','vendors/bsd.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Cabletron','vendors/cabletron.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'CacheFlow','vendors/cacheflow.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Canon','vendors/canon.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Netopia','vendors/netopia.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Nokia IPSO','vendors/nokia.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Checkpoint Firewall-1','vendors/checkpoint.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Check Point FireWall-1','vendors/checkpoint.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Cisco Catalyst','vendors/cisco.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Cisco IP phone','vendors/cisco.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Cisco IOS','vendors/cisco.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Cisco PIX','vendors/cisco.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Cisco Aironet','vendors/cisco.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Cisco','vendors/cisco.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Cobalt Linux','vendors/cobalt.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Commodore 64','vendors/comodore.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Compaq Inside Management Board','vendors/compaq.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Compaq Integrated Lights Out remote configuration Board','vendors/compaq.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Compaq ProLiant','vendors/compaq.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Compaq Tru64 UNIX','vendors/compaq.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'HP Tru64','vendors/hp.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Conexant ADSL Router','vendors/conexant.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'D-Link','vendors/dlink.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'DEC OpenVMS','vendors/dec.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Dell Remote Access Controller','vendors/dell.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Dell Powervault','vendors/dell.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Dell PowerConnect','vendors/dell.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'F5 Labs','vendors/f5.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Fortinet firewall','vendors/fortinet.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Foundry','vendors/foundry.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'FreeBSD','vendors/freebsd.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'FreeSCO','vendors/freesco.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'HP-BSD','vendors/hp.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'HP Procurve','vendors/hp.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'HP LaserJet','vendors/hp.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'HP printer','vendors/hp.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'HP-UX','vendors/hp.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'VxWorks','vendors/vxworks.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'IBM AIX','vendors/ibm.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'IBM MVS','vendors/ibm.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'IBM OS/2','vendors/ibm.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'IBM OS/390','vendors/ibm.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'IBM OS/400','vendors/ibm.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'IBM AS/400','vendors/ibm.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'IBM','vendors/ibm.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'OS/400','vendors/ibm.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Intel','vendors/intel.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'IPCop','vendors/ipcorp.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'IronPort','vendors/ironport.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Juniper Networks','vendors/juniper.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Kyocera','vendors/kyocera.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Lexmark','vendors/lexmark.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Linksys WET','vendors/linksys.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Linksys WGA','vendors/linksys.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Linksys BEF','vendors/linksys.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Linksys WAG','vendors/linksys.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Linksys WRT','vendors/linksys.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Linksys','vendors/linksys.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Linux','vendors/linux.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Microsoft Windows 2003','vendors/windows.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Microsoft Windows Server 2003','vendors/windows.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Microsoft Windows 95','vendors/windows.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Microsoft Windows 98SE','vendors/windows.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Microsoft Windows 98','vendors/windows.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Microsoft Windows Millennium','vendors/windows.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Microsoft Windows NT','vendors/windows.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Microsoft Windows 2000','vendors/windows.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Microsoft Widows XP SP2','vendors/windows.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Microsoft Windows XP','vendors/windows.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'NetApp','vendors/netapp.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'NetApp NetCache','vendors/netapp.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'NetBSD','vendors/netbsd.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Netgear','vendors/netgear.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Netopia','vendors/netopia.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Netscreen','vendors/netscreen.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'NeXT','vendors/next.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'OpenStep','vendors/openstep.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Nortel','vendors/nortel.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Novell NetWare','vendors/novell.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'NetWare','vendors/netware.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'OpenBSD','vendors/openbsd.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Polycom','vendors/polycom.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'QNX','vendors/qnx.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Quantum Snap','vendors/quantum.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Radware','vendors/radware.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Raptor','vendors/raptor.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Ricoh','vendors/ricoh.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'SCO','vendors/sco.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'SGI IRIX','vendors/sgi.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Siemens','vendors/siemens.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'SMC Barricade','vendors/smc.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Smoothwall','vendors/smoothwall.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'SonicWall','vendors/sonicwall.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Speedstream','vendors/speedstream.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'SunOS','vendors/sun.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Sun Solaris','vendors/sun.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Symantec Gateway','vendors/symantec.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Symantec Enterprise Firewall','vendors/symantec.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'WatchGuard Firebox','vendors/watchgard.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'Xerox','vendors/xerox.png','0','1');
INSERT INTO `centreon_nmap_import_os` VALUES ('', 'ZyXel','vendors/zyxel.png','0','1');
