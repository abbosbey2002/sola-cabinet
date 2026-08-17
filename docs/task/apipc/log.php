<?php
/**
 * User: Ismoil
 * Date: 26.03.13
 * Time: 15:07
 */

class Log
{
    private $logFolder;
    private $logPrefix;
    private $logUniqVar;
    private $logDefaultLevel;
    private $log;

    public function __construct($logFolder, $logPrefix, $logDefaultLevel)
    {
        $this->logFolder = $logFolder;
        $this->logPrefix = $logPrefix;
        $this->logDefaultLevel = $logDefaultLevel;

        $this->logUniqVar = date("ymd");

        $log_file  = $this->logFolder . "/" . $this->logPrefix . "_" . $this->logUniqVar . ".log";
        $this->log = fopen($log_file,'a');
    }

    public function write($logType, $msg, $level=-1)
    {
        if ($level == -1) $level = $this->logDefaultLevel;

        $isWrite = false;
        switch ($logType)
        {
            case LOG_INFO:
            case LOG_CRIT:
                $isWrite = true; // Every time
            break;

            case LOG_ERR:
            case LOG_DAEMON:
            case LOG_SYSLOG:
                $isWrite = ($level >= 1);
            break;

            case LOG_WARNING:
                $isWrite = ($level >= 2);
            break;

            case LOG_ALERT:
            case LOG_AUTH:
            case LOG_NOTICE:
            case LOG_DEBUG:
                $isWrite = ($level >= 3);
            break;

            default:
                $isWrite = false;
            break;
        }

        if ($isWrite)
        {
            $uniqVar = date("ymd");
            if ($this->logUniqVar != $uniqVar)
            {
                fflush($this->log);
                fclose($this->log);

                $this->logUniqVar = $uniqVar;

                $log_file  = $this->logFolder . "/" . $this->logPrefix . "_" . $this->logUniqVar . ".log";
                $this->log = fopen($log_file,'a');
            }

            $logTypeName = "";
            switch ($logType)
            {
                case LOG_INFO:      $logTypeName = "LOG_INFO     "; break;
                case LOG_CRIT:      $logTypeName = "LOG_CRIT     "; break;
                case LOG_ERR:       $logTypeName = "LOG_ERR      "; break;
                case LOG_DAEMON:    $logTypeName = "LOG_DAEMON   "; break;
                case LOG_SYSLOG:    $logTypeName = "LOG_SYSLOG   "; break;
                case LOG_WARNING:   $logTypeName = "LOG_WARNING  "; break;
                case LOG_ALERT:     $logTypeName = "LOG_ALERT    "; break;
                case LOG_AUTH:      $logTypeName = "LOG_AUTH     "; break;
                case LOG_NOTICE:    $logTypeName = "LOG_NOTICE   "; break;
                case LOG_DEBUG:     $logTypeName = "LOG_DEBUG    "; break;
                default:            $logTypeName = "LOG_DEBUG    "; break;
            }

            $log_line = join(' ', array( date("H:i:s"), $logTypeName, $msg ));
            fwrite($this->log, $log_line."\n");
        }
    }

    function __destruct()
    {
        if ($this->log)
        {
            fflush($this->log);
            fclose($this->log);
        }
    }
}