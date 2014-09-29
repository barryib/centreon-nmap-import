#Centreon Nmap Import

This package add the Nmap Scanner functionnality to Centreon https://community.centreon.com/projects/import-nmap

##Before installation

You need to install System_Daemon PEAR Packages

Run these commands under root user :
```shell
pear install -o -f --alldeps System_Daemon
```

Set HTTP rights for module.

Example : On Debian or Ubuntu
```shell
cd /usr/local/oreon/modules
chown -R www-data.www-data CentreonNmapImport
```

##After installation

IF YOU WANT TO USE THE OS DISCOVER FUNCTION :
Edit sudo file and add these few lines at the end:

```shell
CENTREON   ALL = NOPASSWD: /usr/bin/nmap *              # nmap os discovery
CENTREON   ALL = NOPASSWD: /etc/init.d/cnicore start    # start cnicore from the poller scan page
CENTREON   ALL = NOPASSWD: /etc/init.d/cnicore stop     # stop cnicore from the poller scan page
```

To be able to run cnicore from the module, you have to generate manualy the /etc/init.d/cnicore file.
To do that, type:
```shell
/path/to/centreon/modules/CentreonNmapImport/bin/cnicore --write-initd
```
Configure the nmap binary path and the nmap xml file output in Administration > Module > Centreon Nmap Import

And enjoy ! ;-)

##License
GNU/LGPL v2.1