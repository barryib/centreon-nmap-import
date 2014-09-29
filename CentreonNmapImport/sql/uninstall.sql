--
-- Droping tables
--
DROP TABLE IF EXISTS `centreon_nmap_import_opt`;
DROP TABLE IF EXISTS `centreon_nmap_import_history`;
DROP TABLE IF EXISTS `centreon_nmap_import_pollers`;
DROP TABLE IF EXISTS `centreon_nmap_import_cmds`;
DROP TABLE IF EXISTS `centreon_nmap_import_os`;

--
-- Deleting Centreon Nmap Import's topologies
--
-- scan and import pages
DELETE FROM `topology` WHERE `topology_page` = '699';
DELETE FROM `topology` WHERE `topology_page` = '69901';
DELETE FROM `topology` WHERE `topology_page` = '69902';
DELETE FROM `topology` WHERE `topology_page` = '69903';
-- Option pages
DELETE FROM `topology` WHERE `topology_page` = '50799';
DELETE FROM `topology` WHERE `topology_page` = '5079901';
DELETE FROM `topology` WHERE `topology_page` = '5079902';
DELETE FROM `topology` WHERE `topology_page` = '5079903';