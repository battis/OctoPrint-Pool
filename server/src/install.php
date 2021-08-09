<?php

use Battis\OctoPrintPool\POSIX;

require_once __DIR__ . '/../vendor/autoload.php';

// using ANSI colors for console readout
function info($message)
{
    echo "\033[39m$message" . PHP_EOL;
}

function alert($message)
{
    echo "\033[92m--> $message\033[39m" . PHP_EOL;
}

function error($message)
{
    echo "\033[91m--> $message\033[39m" . PHP_EOL;
}

function accessError($path, $permissions = ['write'])
{
    error('PHP user will need ' . implode(', ', $permissions) . " permissions for $path");
}

function shell_cmd_exists($cmd): bool
{
    return !!shell_exec("which $cmd 2> /dev/null");
}

function file_group_name($path): string
{
    return posix_getgrgid(filegroup($path))['name'];
}

$php_group = false;
/**
 * The inferred name of the user group for the web server's PHP process (www-data or apache, usually)
 * @return false|string `false` if cannot be inferred
 */
function php_group()
{
    global $php_group;
    if (!$php_group) {
        if (shell_cmd_exists('ps') && shell_cmd_exists('egrep')) {
            $processes = explode(PHP_EOL, `ps aux | egrep '(apache|httpd)'`);
            if (!empty($processes[1])) {
                $process = explode(' ', $processes[1]);
                if (!empty($process[0])) {
                    $php_group = $process[0];
                }
            }
        }
        if ($php_group) {
            info("PHP/web server user group inferred to be $php_group");
        } else {
            error('Cannot infer PHP/web server user group');
        }
    }
    return $php_group;
}

function make_dir_accessible($dir, $purpose)
{
    $ok = true;
    if (!file_exists($dir)) {
        if (mkdir($dir)) {
            $dir = realpath($dir);
            alert("$purpose: created $dir");
        } else {
            $ok = false;
            error("$purpose: could not create $dir");
        }
    }
    if ($ok && file_exists($dir)) {
        $dir = realpath($dir);
        info("$purpose: $dir exists");
        if ($group = php_group()) {
            if (file_group_name($dir) !== $group) {
                chgrp($dir, $group) ?
                    alert("$purpose: $group assigned as group for $dir") :
                    $ok = false;
            } else {
                info("$purpose: $group already assigned as group for $dir");
            }

            if ($ok) {
                if (fileperms($dir) & POSIX::GROUP_READ & POSIX::GROUP_WRITE & POSIX::GROUP_EXECUTE) {
                    POSIX::symbolic_chmod($dir, 'g+rwx') ?
                        alert("$purpose: g=rwX permissions assigned to $dir") :
                        $ok = false;
                } else {
                    info("$purpose: g=rwX permissions already assigned to $dir");
                }
            } else {
                error("$purpose: did not attempt to set g=rwX permissions for $dir");
            }
        } else {
            error("$purpose: PHP/web server user needs rwx access to $dir");
        }
    }
}

// prepare .env for editing, if necessary
$env = __DIR__ . '/../../env/.env';
if (file_exists($env)) {
    info('.env environment already exists at ' . realpath($env));
} else {
    copy("$env.example", $env);
    alert('.env environment ready to edit at ' . realpath($env));
}

if (preg_match('/windows/i', php_uname())) {
    error('OctoPrint-Pool is meant to be run on a LAMP stack, so further installation steps will not be performed:');
    error('    1. /logs/ directory created and permissions set');
    error('    2. /var/ directory created and permissions set');
    error('    3. crontab updated to run cleanup job using scheduler.php');
    exit(0);
}

php_group(); // just to get the console log in the right order!
make_dir_accessible(__DIR__ . '/../../logs', 'Server logs');
make_dir_accessible(__DIR__ . '/../../var', 'File storage');

// append to crontab, if possible
if (shell_cmd_exists('crontab') && shell_cmd_exists('grep')) {
    $scheduler = realpath(__DIR__ . '/scheduler.php');
    $log = realpath(__DIR__ . '/../../logs/cleanup.log');
    if (`crontab -l | grep '* * * * * php "$scheduler"'`) {
        info('Cleanup job already scheduled in your crontab');
    } else {
        `(crontab -l; echo '* * * * * php "$scheduler" &>> "$log"') | crontab -`;
        alert('Cleanup job scheduled in your crontab');
    }
} else {
    alert('Not able to schedule cleanup job automatically -- make sure that ' . realpath(__DIR__ . '/scheduler.php') .
        ' is scheduled to run regularly on your system!');
}

// add octoprint-pool.ini to apache2 php.ini (which should never work on a well-configured system)
$conf = dirname(str_replace('/cli/', '/apache2/', php_ini_loaded_file())) . '/conf.d';
$ini = realpath(__DIR__ . '/../../env/octoprint-pool.ini');
$ok = true;
if (file_exists($conf)) {
    $conf = realpath($conf);
    if (posix_access($conf, POSIX_W_OK)) {
        if (copy($ini, $conf . "/99-octoprint-pool.ini")) {
            alert("Copied octoprint-pool.ini to $conf, restart apache to use settings");
        } else {
            $ok = false;
        }
    } else {
        $ok = false;
    }
}
if (!$ok) {
    alert("(Edit and) copy $ini to php.ini apache2 conf directory (probably $conf) to adjust maximum upload size");
}
