<?php
require_once('vendor/autoload.php');
require_once('gemvc/helper/NoCors.php');
use Gemvc\Helper\NoCors;
use Gemvc\Core\Bootstrap;
/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/
NoCors::NoCors();
$bootstrap = new Bootstrap();
