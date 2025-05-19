<?php
require_once 'vendor/autoload.php';

use Gemvc\Core\Bootstrap;
use Gemvc\Http\ApacheRequest;
use Gemvc\Http\NoCors;
use Symfony\Component\Dotenv\Dotenv;

// Apply CORS headers using the new apache method
NoCors::apache();

$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/app/.env');

$webserver = new ApacheRequest();
$bootstrap = new Bootstrap($webserver->request);
