# A flexible and lightweight Database Library for PHP

This library abstracts and provides help with most aspects of dealing with relational
databases such as keeping connections to the server, building queries,
filtering possible SQL injections, inspecting and altering schemas and with debugging and
profiling queries sent to the database.

It adopts the API from the native PDO extension in PHP for familiarity, but solves many of the
inconsistencies PDO has, while also provides several features that extends PDO capabilities.


## Connecting to the database

This library is able to work with the following databases:

* MySQL
* Postges
* SQLite
* Microsoft SQL Server (2008 and above)

The first thing you need to do for using this library is creating a connection object,
and before performing any operations with the connection, you need to specify a driver
to use:

```php
use Cake\Database\Connection;
use Cake\Database\Driver\Mysql;

$driver = new Mysql([
	'database' => 'test',
	'login' => 'root',
	'password' => 'secret'
]);
$connection = new Connection([
	'driver' => $driver
]);
```

Drivers are classes responsible for actually executing the commands to the database and
correctly building the SQL according to the database specific dialect. Drivers can also
be specified by passing a class name, in that case, include all the connection details
directly in the options array:

```php
use Cake\Database\Connection;

$connection = new Connection([
	'driver' => 'Cake\Driver\Sqlite'
	'database' => '/path/to/file.db'
]);
```

### Connection options

This is a list of possible options that can be passed for creating a connection:

* `persistent`: Creates a persistent connection
* `host`: The server host
* `database`: The database name
* `username`: Login credential
* `password`: Connection secret
* `encoding`: The connection encoding (or charset)
* `timezone`: The connection timezone or time offset

## Using connections

After creating a connection, you can immediately interact with the database. You can choose
either to use the shorthand methods `execute()`, `insert()`, `update()`, `delete()` or use the
`newQuery()` for using a query builder.

The easiest way of executing queries is by using the `execute()` method, it will return a
`Cake\Database\StatementInterface` that you can use to get the data back:

```php
$statement = $connection->execute('SELECT * FROM articles');

while($row = $statement->fetch('assoc')) {
	echo $row['title'] . PHP_EOL;
}

```
