<?php
date_default_timezone_set("Europe/Paris");
define("MEMORY_REAL_USAGE", true);
define('INIT_TIME', microtime(true));
define('INIT_MEMORY', memory_get_usage(MEMORY_REAL_USAGE));

require_once(__DIR__."/includes/libs/core/application/class.Singleton.php");
require_once(__DIR__."/includes/libs/core/application/class.Autoload.php");

use core\application\Autoload;
use core\application\Core;

Autoload::$folder = __DIR__;
spl_autoload_register(array(Autoload::getInstance(), "load"));

Core::checkEnvironment();
Core::init();
Core::parseURL();
Core::execute(Core::getController(), Core::getAction(), Core::getTemplate());
Core::endApplication();
