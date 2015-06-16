<?php
/**
 * @author Oleksandr Tolochko <tooleks@gmail.com>
 * And all you touch and all you see. Is all your life will ever be... Breathe by Pink Floyd
 */

if ($argc !== 3) {
	echo 'Usage: ' . $argv[0] . ' <NAME> <DOCUMENT_ROOT_DIRECTORY_ABSOLUTE_PATH>.' . PHP_EOL;
	exit(1);
}

$name = trim(strtolower($argv[1]), "'\"./\t\n\r\0\x0B");
$documentRootDirPath = $argv[2];

createApacheConfig($name, $documentRootDirPath);
createHostsConfig($name);
createDocumentRootFolder($documentRootDirPath);
createEntryScript($name, $documentRootDirPath);

echo 'Success!' . PHP_EOL;
exit(0);

/*--------------------------------------------------------------------------------------------------------------------*/


/**
 * Generates apache virtual host config data for project
 * @param $name string project name
 * @param $documentRootPath string project document root path
 * @return string config data
 */
function generateApacheConfig($name, $documentRootPath)
{
	ob_start();
	echo '<VirtualHost *:80>' . PHP_EOL;
	echo PHP_EOL;
	echo 'ServerName ' . $name . '.local' . PHP_EOL;
	echo 'ServerAlias ' . $name . '.local' . PHP_EOL;
	echo 'ServerAdmin webmaster@localhost' . PHP_EOL;
	echo 'DocumentRoot ' . $documentRootPath . PHP_EOL;
	echo PHP_EOL;
	echo 'ErrorLog ${APACHE_LOG_DIR}/error.log' . PHP_EOL;
	echo 'CustomLog ${APACHE_LOG_DIR}/access.log combined' . PHP_EOL;
	echo PHP_EOL;
	echo '<Directory "' . $documentRootPath . '">' . PHP_EOL;
	echo 'Options Indexes FollowSymLinks' . PHP_EOL;
	echo 'AllowOverride All' . PHP_EOL;
	echo 'Require all granted' . PHP_EOL;
	echo '</Directory>' . PHP_EOL;
	echo PHP_EOL;
	echo '</VirtualHost>';
	echo PHP_EOL;
	return ob_get_clean();
}

/**
 * Creates apache virtual host config files for project
 * @param $name string project name
 * @param $documentRootPath string project document root path
 */
function createApacheConfig($name, $documentRootPath)
{
	$sitesAvailableDirPath = '/etc/apache2/sites-available';
	if (!is_writable($sitesAvailableDirPath)) {
		echo 'Directory <' . $sitesAvailableDirPath . '> is not writable';
		exit(1);
	}

	$sitesEnabledDirPath = '/etc/apache2/sites-enabled';
	if (!is_writable($sitesEnabledDirPath)) {
		echo 'Directory <' . $sitesEnabledDirPath . '> is not writable';
		exit(1);
	}

	$sitesAvailableConfigPath = $sitesAvailableDirPath . '/' . $name . '.conf';
	if (!file_put_contents($sitesAvailableConfigPath, generateApacheConfig($name, $documentRootPath))) {
		echo 'Config file <' . $sitesAvailableConfigPath . '> creation error. Check permissions.';
		exit(1);
	}

	$sitesEnabledConfigPath = $sitesEnabledDirPath . '/' . $name . '.conf';
	if (!copy($sitesAvailableConfigPath, $sitesEnabledConfigPath)) {
		echo 'Config file <' . $sitesEnabledConfigPath . '> creation error. Check permissions.';
		exit(1);
	}
}

/**
 * Generates system hosts config data for project
 * @param $name string project name
 * @return string
 */
function generateHostsConfig($name)
{
	ob_start();
	echo '127.0.0.1		' . $name . '.local' . ' www.' . $name . '.local' . PHP_EOL;
	return ob_get_clean();
}

/**
 * Creates system hosts configuration for project
 * @param $name string project name
 */
function createHostsConfig($name)
{
	$hostsConfigPath = '/etc/hosts';
	if (!is_writable($hostsConfigPath)) {
		echo 'File <' . $hostsConfigPath . '> is not writable';
		exit(1);
	}

	if (!file_put_contents($hostsConfigPath, generateHostsConfig($name), FILE_APPEND)) {
		echo 'Config file <' . $hostsConfigPath . '> updating error. Check permissions.';
		exit(1);
	}
}

/**
 * Creates project document root folder
 * @param $documentRootPath string project document root path
 */
function createDocumentRootFolder($documentRootPath)
{

	if (file_exists($documentRootPath)) {
		echo 'Project root directory with name <' . $documentRootPath . '> already exists.' . PHP_EOL;
		exit(1);
	}

	if (!mkdir($documentRootPath, 0777, true)) {
		echo 'Project root directory <' . $documentRootPath . '> creation error. Check permissions.';
		exit(1);
	}
}

/**
 * Creates simple entry script [index.php] in the project document root folder
 * @param $name string project name
 * @param $documentRootPath string project document root path
 */
function createEntryScript($name, $documentRootPath)
{
	if (!file_put_contents($documentRootPath . '/index.php', '<?php echo ' . $name . ' is working!;')) {
		echo 'File <' . $documentRootPath . '/index.php' . '> creation error. Check permissions.';
		exit(1);
	}
}
