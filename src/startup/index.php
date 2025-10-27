<?php
require_once __DIR__ . '/vendor/autoload.php';
use Gemvc\Core\OpenSwooleServer;

/**
 * GEMVC OpenSwoole Server Entry Point
 * 
 * This is the main entry point for the GEMVC OpenSwoole server.
 * All HTTP requests are processed through this file.
 */

// Create and start the OpenSwoole server
$server = new OpenSwooleServer();
$server->start();