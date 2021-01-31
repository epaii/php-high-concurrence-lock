<?php

namespace epii\lock;

interface ILock{
    public function init($config);
    public function require_configs();
    public function doLock($key, $timeout = 5);
    public function unLock($key);
}