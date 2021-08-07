<?php

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

function shellcmd($cmd) {
    return !!shell_exec("which $cmd 2> /dev/null");
}

function filegroupname($path): string {
    return posix_getgrgid(filegroup($path))['name'];
}

/**
 * @see https://www.php.net/manual/en/function.fileperms.php
 */
function filepermissions($path): string {
    $perms = fileperms($path);

    switch ($perms & 0xF000) {
        case 0xC000: // socket
            $info = 's';
            break;
        case 0xA000: // symbolic link
            $info = 'l';
            break;
        case 0x8000: // regular
            $info = 'r';
            break;
        case 0x6000: // block special
            $info = 'b';
            break;
        case 0x4000: // directory
            $info = 'd';
            break;
        case 0x2000: // character special
            $info = 'c';
            break;
        case 0x1000: // FIFO pipe
            $info = 'p';
            break;
        default: // unknown
            $info = 'u';
    }

// Owner
    $info .= (($perms & 0x0100) ? 'r' : '-');
    $info .= (($perms & 0x0080) ? 'w' : '-');
    $info .= (($perms & 0x0040) ?
        (($perms & 0x0800) ? 's' : 'x' ) :
        (($perms & 0x0800) ? 'S' : '-'));

// Group
    $info .= (($perms & 0x0020) ? 'r' : '-');
    $info .= (($perms & 0x0010) ? 'w' : '-');
    $info .= (($perms & 0x0008) ?
        (($perms & 0x0400) ? 's' : 'x' ) :
        (($perms & 0x0400) ? 'S' : '-'));

// World
    $info .= (($perms & 0x0004) ? 'r' : '-');
    $info .= (($perms & 0x0002) ? 'w' : '-');
    $info .= (($perms & 0x0001) ?
        (($perms & 0x0200) ? 't' : 'x' ) :
        (($perms & 0x0200) ? 'T' : '-'));

    return $info;
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

// detect apache user to set permissions
$apache = false;
if (shellcmd('ps') && shellcmd('egrep')) {
    $processes = explode(PHP_EOL, `ps aux | egrep '(apache|httpd)'`);
    if (!empty($processes[1])) {
        $process = explode(' ', $processes[1]);
        if (!empty($process[0])) {
            $apache = $process[0];
        }
    }
}
if ($apache) {
    info("Apache user inferred to be $apache, permissions will be set");
} else {
    error('Cannot infer Apache user, will not set permissions');
}

// prepare log files
$logDir = __DIR__ . "/../../logs";
if (!file_exists($logDir)) {
    mkdir($logDir);
    alert('Logs will be stored in ' . realpath($logDir));
}
if ($apache) {
    if (filegroupname($logDir) !== $apache) {
        chgrp($logDir, $apache);
        alert("$apache group set for " . realpath($logDir));
    }
    if (filepermissions($logDir) !== 'drwx-w----') {
        chmod($logDir, 0720);
        alert("$apache given write permissions to " . realpath($logDir));
    }
} else {
    accessError(realpath($logDir));
}
foreach (['server', 'cleanup', 'php'] as $name) {
    $log = "$logDir/$name.log";
    if (!file_exists($log)) {
        touch($log);
        info("Created $name.log");
    }
}

// prepare var directory for file storage
$var = __DIR__ . '/../../var';
if (file_exists($var)) {
    info('var directory is ready to store queued files at ' . realpath($var));
} else {
    mkdir($var);
    alert('var directory created to store queued files at ' . realpath($var));
}
if ($apache) {
    if (filegroupname($var) !== $apache) {
        chgrp($var, $apache);
        alert("$apache group set for " . realpath($var));
    }
    if (filegroupname($var) !== $apache && filepermissions($var) !== 'dwxrw----') {
        chmod($var, 0760);
        alert("$apache given read, write permissions to " . realpath($var));
    }
} else {
    accessError($var, ['read', 'write']);
}

// append to crontab, if possible
if (shellcmd('crontab') && shellcmd('grep')) {
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
