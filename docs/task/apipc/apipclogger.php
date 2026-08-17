<?php
/**
 * User: developer
 * Date: 28.05.15 10:50
 */
declare(ticks = 1);
$deamonName     = "HTTP API PC LOGGER DEAMON";
$DeamonFolder   = "/home/demons/apipclogger";
$DeamonpidFile  = $DeamonFolder . "/apipclogger.pid";

define('MSG_RECEIVE_MAX_SIZE', 65536);
$MSG_KEY = ftok($DeamonFolder, "L"); // Log Queue
if ( $MSG_KEY < 0 )
{
    echo "{$deamonName} error on create System V IPC keys\n";
    exit(0);
}

//echo dechex($MSG_KEY);
//exit(0);

$interval       = 100000; // v microsekundah (1 million microsekund = 1 sekund)
$yymmdd         = date("ymd");

$logFolder      = "/var/log/apipclog";
$logPrefix      = "apipc";
$logDefaultLevel= "3";

include("log.php");

$deamon = pcntl_fork();
if ($deamon == -1)
{
    echo "PCNTL functions not available on this PHP installation\n";
    exit(0);
}
elseif ($deamon)
{
    echo "{$deamonName} started. PID is {$deamon}\n";
    exit(0);
}

function sig_handler($signo)
{
    global $deamonName;
    switch($signo)
     {
         case SIGTERM:
            //echo "\n{$deamonName} Terminating...\n";
            exit;
         break;
         case SIGUSR1:
             echo "\n{$deamonName} signal: SIGUSR1\n";
         break;
         case SIGCHLD:
            //echo "\n{$deamonName} signal: SIGCHLD\n";
            while( pcntl_waitpid(-1, $status, WNOHANG) > 0 ) { /*echo "chpid of status = {$status}";*/ };
         break;
     }
}

pcntl_signal(SIGCHLD, "sig_handler");
pcntl_signal(SIGTERM, "sig_handler");
pcntl_signal(SIGUSR1, "sig_handler");

$sid = posix_setsid();
echo "Make session leader is {$sid}\n";

$deamonPID = getmypid();
function shutdown() {
    global $log, $deamonPID, $deamonName, $DeamonpidFile, $msgq;

    $pid = getmypid();
    if ($pid != $deamonPID) return;

    if ($msgq)
    {
        msg_remove_queue($msgq);
    }

    if (file_exists($DeamonpidFile)) unlink($DeamonpidFile);

    $log->write(LOG_INFO, "{$deamonName} Terminated!");
    //echo "\n{$deamonName} Terminated!\n";
}

register_shutdown_function('shutdown');

$log = new Log($logFolder, $logPrefix, $logDefaultLevel);
$log->write(LOG_INFO, $deamonName . ' started');

$msgq = msg_get_queue($MSG_KEY);
$log->write(LOG_INFO, 'Message queue created. Key is 0x' . dechex($MSG_KEY));
// Nachinayem prinimat zaprosi
file_put_contents($DeamonpidFile, $deamonPID);

// Beskonechniy tsikl
$exitTime = mktime();
while (true)
{
    $msg_type = NULL;
    $msg = NULL;
    while (msg_receive($msgq, 0, $msg_type, MSG_RECEIVE_MAX_SIZE, $msg, FALSE, MSG_IPC_NOWAIT))
    {
        $log->write($msg_type, $msg);
    }

    usleep($interval);
    $currTime = mktime();

    if ($exitTime + 60 < $currTime)
    {
        // kajduju minute proverim fayl, dlya vihoda iz tsikla
        $start = file_get_contents($DeamonFolder . "/apipclogger");
        if (trim($start) != "1")
            break;

        $exitTime = $currTime;
    }
    //break;
}

msg_remove_queue($msgq);
$msgq = 0;
?>
