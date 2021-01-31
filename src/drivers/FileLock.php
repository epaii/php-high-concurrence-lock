<?php

namespace epii\lock\drivers;

use epii\lock\ILock;

class FileLock implements ILock
{
    private $config = [];
    private $files = [];
    public function init($config)
    {
        $this->config = $config;
        if (!is_dir($this->config["lock_dir"])) {
            if (!mkdir($this->config["lock_dir"], 0777, true)) {
                exit("mkdir " . $this->config["lock_dir"] . " faid");
            }
        }
    }
    public function require_configs()
    {
        return ["lock_dir"];
    }
    public function doLock($key, $timeout = 5)
    {
        if (!isset($this->files[$key])) {

            $file = $this->config["lock_dir"] . DIRECTORY_SEPARATOR . $key . ".lock";

            $fp = null;
            $startTime = microtime(true);
            $locked = false;
            do {
                if (!$fp) {
                    $fp = @fopen($file, "w+");
                    if (!$fp) {
                        exit("error on fopen ".$file);
                    }
                }
                $locked = flock($fp, LOCK_EX | LOCK_NB);
                if (!$locked) {
                    usleep(mt_rand(1, 50) * 1000);
                }
            } while ((!$locked) && ((microtime(true) - $startTime) < $timeout));

            if ($locked) {
                $this->files[$key] = $fp;
                return true;
            } else {
                return false;
            }
        }
        return false;
    }
    public function unLock($key)
    {
        if (!isset($this->files[$key])) {
            flock($this->files[$key], LOCK_UN);
            fclose($this->files[$key]);
            unset($this->files[$key]);
            return true;
        }else{
            return false;
        }
    }
}
