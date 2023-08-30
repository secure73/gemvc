<?php
const ROOT = '';
const DEFAULT_LANGUAGE = 'de';
const LIBRARY = 'gemvc/lib/';
const URL = 'https://your_domain.com';
const TOKEN_IP_RESTRICT = false;
const TOKEN_USER_MACHINE_RESTRICT = true;
const API_TOKEN_SECRET_KEY = 'your token secret key';
const ENCRYPT_SECRET = 'your encrypt secret key';
const REFRESH_TOKEN_VALIDATE_SECOND = 7200;
const ACCESS_TOKEN_VALIDATION_SECOND = 1200;
const LOGIN_TOKEN_VALIDITY_SECOND = 5184000;
const DEFAULT_CONNECTION_NAME = 'default';
const ENCRYPTION_ALGORYTHEM = 'AES-256-CBC';
const SHA_ALGORYTHEM = 'sha256';

const EMAIL_TEMPLATE_DIRECTORY = './app/email/themplates/';
const EMAIL_CONTENT_STYLE_WRAPPER = 'app/email/style/default.html';
const DEFAULT_EMAIL = 'info';

const FILE_ROOT = ' the path for saving your files ex: /var/www/vhosts/gemvc.com/files.your_domain.com/';
const USER_DIRECTORY = 'the path for user relevant files: it will be under FILE_ROOT  user/';

$projects_ips = [];
define('PROJECT_SERVER_IPS', $projects_ips);
/**
 * this is list of public services, user need no tokens to access this services
 */
$publicServices=[
    'User'=>['help'],
    'Index'=>['help'],
    'Address'=>['help'],
    'Auth'=>['help','byEmail','register','renew','token/update'],
    'Service'=>['help','id','index','create','update','delete']
];


// #Database##
$database_connections = [
    'default' => [
        'type' => 'mysql',
        'database_name' => '',
        'host' => '127.0.0.1',
        'username' => '',
        'password' => '',
        'port' => '',
    ],
    'files' => [
        'type' => 'mysql',
        'database_name' => '',
        'host' => '127.0.0.1',
        'username' => '',
        'password' => '',
        'port' => '',
    ]
];
$emails = [
    'info@your_domain.com' => [
        'connection_type' => 'ssl',
        'host' => 'mail.your_domain.com',
        'port_number' => 465,
        'username' => 'info@your_domain.com',
        'password' => '',
        'from' => 'info@your_domain.com',
        'from_name' => 'your_domain Admin',
    ],
    'noreply@your_domain.com' => [
        'connection_type' => 'ssl',
        'host' => 'mail.your_domain.com',
        'port_number' => 465,
        'username' => 'noreply@your_domain.com',
        'password' => '',
        'from' => 'noreply@your_domain.com',
        'from_name' => 'your_domain Information',
    ],
];
define('DB_CONNECTIONS', $database_connections);
define('EMAILS', $emails);
define('PUBLIC_SERVICES', $publicServices);
