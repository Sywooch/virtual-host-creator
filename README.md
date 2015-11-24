# Virtual Host Creator Utility
Apache virtual host creation utility (Ubuntu 14.04 / Apache 2.4.7)

Simple script to automate the routine work

# Usage
**NOTE:** PHP Version >= 5.4.

**Example usage:**
```
# php virtual-host-creator.php <HOSTNAME> <APACHE_DOCUMENT_ROOT_DIRECTORY_ABSOLUTE_PATH>
```
**Example output:**
```
 Success: Virtual host config file "/etc/apache2/sites-available/vhc-test.conf" was created. 
 Success: Virtual host config file "/etc/apache2/sites-enabled/vhc-test.conf" was created. 
 Success: Config file "/etc/hosts" was updated. 
 Warning: Project root directory "/var/www/html/vhc-test" already exists. 
 Rewrite directory (this operation can harm your data)? (Y/N) 
y
 Success: Project root directory "/var/www/html/vhc-test" was created with 777 permissions, you can update this manually for security reasons. 
 Success: Entry script "/var/www/html/vhc-test/index.php" file was created. 
 Info: Restarting apache web server. 
 Info: Sending request to the host "http://vhc-test.local". 
 Success: Hostname "http://vhc-test.local" is available. 
 Your site URL's: 
 http://vhc-test.local 
 http://www.vhc-test.local 
 Success: That's all folks! 
```
