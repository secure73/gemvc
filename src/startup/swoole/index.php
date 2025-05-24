<?php

use Gemvc\Core\SwooleBootstrap;
use Gemvc\Http\SwooleRequest;
// Minimal OpenSwoole HTTP server

if (!extension_loaded('openswoole')) {
    die("OpenSwoole extension is not installed.\n");
}

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/app/.env');

$server = new OpenSwoole\HTTP\Server("0.0.0.0", 9501);

$server->on("request", function ($request, $response) {
    $sr = new SwooleRequest($request);

    $bs = new SwooleBootstrap($sr->request);
    $jsonResponse = $bs->processRequest($response);
    if ($jsonResponse instanceof \Gemvc\Http\JsonResponse) {
        $jsonResponse->showSwoole($response);
    }
});

//echo "Minimal OpenSwoole server running on http://localhost:9501\n";
$server->start();
