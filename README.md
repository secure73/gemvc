# gemvc
gemvc is super light library that is suitable to create microservice API 

Require .env with following data

DB_HOST=localhost
DB_PORT=3306
DB_NAME=database_name
DB_CHARSET = charsset
DB_USER=root
DB_PASSWORD=secret

please rememmber to load .env as follows :

after loading autoloader
require __DIR__ . '/vendor/autoload.php';

use following code

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

