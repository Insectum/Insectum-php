Insectum-php
============

Example:

```php
$dbh = new PDO('mysql:dbname=database;host=localhost', 'root', 'root');

$insectumConfig = array(
    'server' => 'local',
    'storage' => array(
        'type' => 'pdo',
        $dbh,
        'database' // database name
    )
);
$insect = new Insectum\InsectumClient\Client($insectumConfig);
$insect->register();
```
