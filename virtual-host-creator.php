<?php
/**
 * @author Oleksandr Tolochko "tooleks@gmail.com"
 * @date 24.11.2015
 */

define('CONSOLE_MESSAGE_ERROR', 'error');
define('CONSOLE_MESSAGE_WARNING', 'warning');
define('CONSOLE_MESSAGE_INFO', 'info');
define('CONSOLE_MESSAGE_SUCCESS', 'success');
define('CONSOLE_MESSAGE_DEFAULT', 'default');

if (posix_getuid() != 0) {
    exit(console_message("You should run this command as root user.",
        CONSOLE_MESSAGE_ERROR));
} elseif ($argc !== 3) {
    exit(console_message("Example usage: php {$argv[0]} <HOSTNAME> <APACHE_DOCUMENT_ROOT_DIRECTORY_ABSOLUTE_PATH>.",
        CONSOLE_MESSAGE_WARNING));
}

$hostname = $argv[1];
$dir_document_root_path = $argv[2];

$hostname = path_sanitize(strtolower($hostname));
$dir_document_root_path = '/' . path_sanitize($dir_document_root_path);
$dir_project_document_root_path = "$dir_document_root_path/$hostname";

config_apache_virtual_host_create($hostname, $dir_document_root_path);
config_hosts_create($hostname);
dir_document_root_create($dir_project_document_root_path);
script_entry_create($hostname, $dir_project_document_root_path);
service_apache('restart');

if (host_test("http://$hostname.local")) {
    print console_message("Your site URL's:");
    print console_message("http://$hostname.local");
    print console_message("http://www.$hostname.local");
    print console_message("That's all folks!", CONSOLE_MESSAGE_SUCCESS);
} else {
    exit(console_message("Something went wrong. Unable to access \"http://$hostname.local\".",
        CONSOLE_MESSAGE_ERROR));
}

exit(0);

/**
 * Sends command to the apache service
 *
 * @param string $command
 * @return string command output
 */
function service_apache($command)
{
    print console_message("Restarting apache web server.",
        CONSOLE_MESSAGE_INFO);
    return shell_exec("service apache2 $command");
}

/**
 * Generates apache virtual host config data for host
 *
 * @param string $hostname host name
 * @param string $dir_document_root_path document root path
 * @return string configuration data
 */
function config_apache_virtual_host_generate($hostname, $dir_document_root_path)
{
    $output = "<VirtualHost *:80>" . PHP_EOL;
    $output .= PHP_EOL;
    $output .= "ServerName $hostname.local" . PHP_EOL;
    $output .= "ServerAlias www.$hostname.local" . PHP_EOL;
    $output .= "ServerAdmin webmaster@localhost" . PHP_EOL;
    $output .= "DocumentRoot $dir_document_root_path/$hostname" . PHP_EOL;
    $output .= PHP_EOL;
    $output .= "ErrorLog \${APACHE_LOG_DIR}/error.log" . PHP_EOL;
    $output .= "CustomLog \${APACHE_LOG_DIR}/access.log combined" . PHP_EOL;
    $output .= PHP_EOL;
    $output .= "<Directory \"$dir_document_root_path/$hostname\">" . PHP_EOL;
    $output .= "Options Indexes FollowSymLinks" . PHP_EOL;
    $output .= "AllowOverride All" . PHP_EOL;
    $output .= "Require all granted" . PHP_EOL;
    $output .= "</Directory>" . PHP_EOL;
    $output .= PHP_EOL;
    $output .= "</VirtualHost>";
    $output .= PHP_EOL;
    return $output;
}

/**
 * Creates apache virtual host config files for host
 *
 * @param string $hostname host name
 * @param string $dir_document_root_path document root path
 */
function config_apache_virtual_host_create($hostname, $dir_document_root_path)
{
    $dir_path_sites_available = "/etc/apache2/sites-available";
    if (!is_writable($dir_path_sites_available)) {
        exit(console_message("Directory \"$dir_path_sites_available\" is not writable",
            CONSOLE_MESSAGE_ERROR));
    }

    $dir_path_sites_enabled = '/etc/apache2/sites-enabled';
    if (!is_writable($dir_path_sites_enabled)) {
        exit(console_message("Directory \"$dir_path_sites_enabled\" is not writable",
            CONSOLE_MESSAGE_ERROR));
    }

    $config_path_sites_available = "$dir_path_sites_available/$hostname.conf";
    if (file_put_contents($config_path_sites_available,
            config_apache_virtual_host_generate($hostname,
                $dir_document_root_path)) !== false
    ) {
        print console_message("Virtual host config file \"$config_path_sites_available\" was created.",
            CONSOLE_MESSAGE_SUCCESS);
    } else {
        exit(console_message("Virtual host config file \"$config_path_sites_available\" creation error. Check permissions.",
            CONSOLE_MESSAGE_ERROR));
    }

    $config_path_sites_enabled = "$dir_path_sites_enabled/$hostname.conf";
    if (copy($config_path_sites_available, $config_path_sites_enabled)) {
        print console_message("Virtual host config file \"$config_path_sites_enabled\" was created.",
            CONSOLE_MESSAGE_SUCCESS);
    } else {
        exit(console_message("Virtual host config file \"$config_path_sites_enabled\" creation error. Check permissions.",
            CONSOLE_MESSAGE_ERROR));
    }
}

/**
 * Generates system hosts config data for host
 *
 * @param string $hostname host name
 * @return string
 */
function config_hosts_generate($hostname)
{
    return "127.0.0.1 $hostname.local www.$hostname.local" . PHP_EOL;
}

/**
 * Creates system hosts configuration for host
 *
 * @param $name string host name
 */
function config_hosts_create($name)
{
    $config_path_hosts = '/etc/hosts';
    if (!is_writable($config_path_hosts)) {
        exit(console_message("Config file \"$config_path_hosts\" is not writable.",
            CONSOLE_MESSAGE_ERROR));
    }

    $config_path_file_handler = fopen($config_path_hosts,
        'r') or exit(console_message("Can't open file \"$config_path_hosts\"."));
    while (($file_line = fgets($config_path_file_handler)) !== false) {
        $file_line = trim($file_line);
        $config_line = trim(config_hosts_generate($name));
        if ($file_line == $config_line) {
            $config_exists = true;
            break;
        }
    }
    fclose($config_path_file_handler);

    if (isset($config_exists)) {
        print console_message("Config file \"$config_path_hosts\" was updated.",
            CONSOLE_MESSAGE_SUCCESS);
    } else {
        if (file_put_contents($config_path_hosts, config_hosts_generate($name),
                FILE_APPEND) !== false
        ) {
            print console_message("Config file \"$config_path_hosts\" was updated.",
                CONSOLE_MESSAGE_SUCCESS);
        } else {
            exit(console_message("Config file \"$config_path_hosts\" updating error. Check permissions.",
                CONSOLE_MESSAGE_ERROR));
        }
    }
}

/**
 * Creates host document root folder
 * @param string $dir_project_document_root_path host document root path
 */
function dir_document_root_create($dir_project_document_root_path)
{
    if (file_exists($dir_project_document_root_path)) {
        print console_message("Project root directory \"$dir_project_document_root_path\" already exists.",
            CONSOLE_MESSAGE_WARNING);
        print console_message("Rewrite directory (this operation can harm your data)? (Y/N)");
        $answer = strtolower(readline());
        while ($answer !== 'y' && $answer !== 'n') {
            print console_message("Please select one of the answers.",
                CONSOLE_MESSAGE_WARNING);
            print console_message("Rewrite directory (this operation can harm your data)? (Y/N)");
            $answer = strtolower(trim(fgets(STDIN)));
        }
        if ($answer === 'y') {
            dir_remove_recursive($dir_project_document_root_path);
        } elseif ($answer === 'n') {
            exit(console_message("Process was interrupted by the user.",
                CONSOLE_MESSAGE_ERROR));
        }
    }

    if (mkdir($dir_project_document_root_path, 0777, true)) {
        print console_message("Project root directory \"$dir_project_document_root_path\" was created with 777 permissions, you can update this manually for security reasons.",
            CONSOLE_MESSAGE_SUCCESS);
    } else {
        exit(console_message("Project root directory \"$dir_project_document_root_path\" creation error. Check permissions.",
            CONSOLE_MESSAGE_ERROR));
    }
}

/**
 * Creates simple entry script "index.php" in the host document root folder
 *
 * @param string $hostname host name
 * @param string $dir_project_document_root_path host document root path
 */
function script_entry_create($hostname, $dir_project_document_root_path)
{
    $script_path = "$dir_project_document_root_path/index.php";
    $script_code = "<?php print '$hostname.local is working!';" . PHP_EOL;
    if (file_put_contents($script_path, $script_code) !== false) {
        print console_message("Entry script \"$script_path\" file was created.",
            CONSOLE_MESSAGE_SUCCESS);
    } else {
        exit(console_message("File \"$script_path\" creation error. Check permissions.",
            CONSOLE_MESSAGE_ERROR));
    }
}

/**
 * Sends requests to the hosts
 *
 * @param string $host
 * @return string bool if host is available, otherwise false
 */
function host_test($host)
{
    print console_message("Sending request to the host \"$host\".",
        CONSOLE_MESSAGE_INFO);

    $success = file_get_contents($host) !== false;
    if ($success) {
        print console_message("Hostname \"$host\" is available.",
            CONSOLE_MESSAGE_SUCCESS);
    } else {
        print console_message("Can't get access to the \"$host\".",
            CONSOLE_MESSAGE_ERROR);
    }

    return $success;
}

/**
 * Removes directory recursively
 *
 * @param string $dir_name directory name to remove
 */
function dir_remove_recursive($dir_name)
{
    if (is_dir($dir_name)) {
        $items = scandir($dir_name);
        foreach ($items as $item) {
            if ($item != "." && $item != "..") {
                if (is_dir($dir_name . "/" . $item)) {
                    dir_remove_recursive("$dir_name/$item");
                } else {
                    unlink("$dir_name/$item");
                }
            }
        }
        rmdir($dir_name);
    }
}

/**
 * Sanitize path
 *
 * @param string $path path to sanitize
 * @return string sanitized path
 */
function path_sanitize($path)
{
    return trim($path, "/'\"./\t\n\r\0\x0B");
}

/**
 * Colourises messages for console output
 *
 * @param string $message message to colourise
 * @param string $type type of the message: error, warning, success, info
 * @return string colourised message
 */
function console_message($message, $type = CONSOLE_MESSAGE_DEFAULT)
{
    $colour_code_generate = function ($colour_code) {
        return "\033[{$colour_code}m";
    };
    $colourise = function ($string, $color) use ($colour_code_generate) {
        return "{$colour_code_generate($color)} $string {$colour_code_generate('1;37')}" . PHP_EOL;
    };
    switch (strtolower($type)) {
        case CONSOLE_MESSAGE_ERROR: { // red
            return $colourise("Error: $message", '0;31');
        }
        case CONSOLE_MESSAGE_WARNING: { // yellow
            return $colourise("Warning: $message", '1;33');
        }
        case 'success': { // green
            return $colourise("Success: $message", '0;32');
        }
        case CONSOLE_MESSAGE_INFO: { // blue
            return $colourise("Info: $message", '0;34');
        }
        default: { // white
            return $colourise($message, '1;37');
        }
    }
}
