Insectum-php
============

Example:

```php
$dbh = new PDO('mysql:dbname=database;host=localhost', 'root', 'root');
// In this example a PDO instance is created, but this approach is intended to utilize already existing PDO instance
// Something like DB::getPdo() in Laravel for example
$insectumConfig = array(
    'server' => 'local',
    'storage' => array(
        'type' => 'pdo',
        'options' => array(
            $dbh,
            'database' // database name
        )
    )
);

// Or like this
$insectumConfig = array(
    'server' => 'local',
    'stage' => 'dev',
    'storage' => array(
        'type' => 'pdo',
        'options' => array(
            'mysql:dbname=database;host=localhost',
            'root', // username
            'root' // password
        )
    )
);

$insect = new Insectum\InsectumClient\Client($insectumConfig);
$insect->register();
```
