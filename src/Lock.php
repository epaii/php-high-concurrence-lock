<?php

namespace epii\lock;

class Lock
{

    private static $driver_class = null;
    private static $driver_config = null;
    private static $driver = null;
    private  static $locks = [];
    private static $is_register_shutdown_function = false;
    private static $is_init = false;
    private static $timeout =5;
    public static function init($driver_class_name, $config = array())
    {
        self::$driver_class = $driver_class_name;
        self::$driver_config = $config;
        self::$is_init = true;
        self::$driver = null;
    }
    public static function isInit()
    {
        return self::$is_init;
    }
    public static function getDriver()
    {
        if (self::$driver_class == null) {
            exit("Lock need set Driver");
        }
        if (self::$driver == null) {
            self::$driver = new self::$driver_class();
            if (self::$driver instanceof ILock) {
                $rconfig = self::$driver->require_configs();
                if ($rconfig) {
                    foreach ($rconfig as $value) {
                        if (!isset(self::$driver_config[$value])) {
                            exit(self::$driver_class . "need config key " . $value);
                        }
                    }
                }
                self::$driver->init(self::$driver_config);
            } else {
                exit("driver need instanceof ILock");
            }
        }
        return self::$driver;
    }
    public static function setTimeout($time){
        self::$timeout = $time;
    }
    public static function doLock($key, $timeout = null)
    {
        if (!self::$is_register_shutdown_function) {
            register_shutdown_function(function () {
                foreach (self::$locks as $key => $value) {

                    self::unLock($key);
                }
            });
        }
        if (!isset(self::$locks[$key])) {
            if (self::getDriver()->doLock($key, $timeout ? $timeout :self::$timeout)) {
                self::$locks[$key] = true;
                return true;
            }
        }
        return false;
    }
    public static function unLock($key)
    {
        if (isset(self::$locks[$key])) {
            if (self::getDriver()->unLock($key)) {
                unset(self::$locks[$key]);
                return true;
            }
        }
        return false;
    }
}
