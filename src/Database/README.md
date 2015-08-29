# A flexible and lightweight Database Library for PHP

This library abstracts and provides help with most aspects of dealing with relational
databases such as keeping connections to the server, building queries,
preventing SQL injections, inspecting and altering schemas, and with debugging and
profiling queries sent to the database.

It adopts the API from the native PDO extension in PHP for familiarity, but solves many of the
inconsistencies PDO has, while also providing several features that extend PDO's capabilities.

A distinguishing factor of this library when compared to similar database connection packages,
is that it takes the concept of "data types" to its core. It lets you work with complex PHP objects
or structures that can be passed as query conditions or to be inserted in the database.

The typing system will intelligently convert the PHP structures when passing them to the database, and
convert them back when retrieving.


## Connecting to the database

This library is able to work with the following databases:

* MySQL
* Postgres
* SQLite
* Microsoft SQL Server (2008 and above)

The first thing you need to do when using this library is create a connection object.
Before performing any operations with the connection, you need to specify a driver
to use:

```php
use Cake\Database\Connection;
use Cake\Database\Driver\Mysql;

$driver = new Mysql([
	'database' => 'test',
	'username' => 'root',
	'password' => 'secret'
]);
$connection = new Connection([
	'driver' => $driver
]);
```

Drivers are classes responsible for actually executing the commands to the database and
correctly building the SQL according to the database specific dialect. Drivers can also
be specified by passing a class name. In that case, include all the connection details
directly in the options array:

```php
use Cake\Database\Connection;

$connection = new Connection([
	'driver' => 'Cake\Database\Driver\Sqlite'
	'database' => '/path/to/file.db'
]);
```

### Connection options

This is a list of possible options that can be passed when creating a connection:

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
Binding values to parametrized arguments is also possible with the execute function:

```php
$statement = $connection->execute('SELECT * FROM articles WHERE id = :id', ['id' => 1], ['id' => 'integer']);
$results = $statement->fetch('assoc');
```

The third parameter is the types the passed values should be converted to when passed to the database. If
no types are passed, all arguments will be interpreted as a string.

Alternatively you can construct a statement manually and then fetch rows from it:

```php
$statement = $connection->prepare('SELECT * from articles WHERE id != :id');
$statement->bind(['id' => 1], ['id' => 'integer']);
$results = $statement->fetchAll('assoc');
```

The default types that are understood by this library and can be passed to the `bind()` function or to `execute()`
are:

* biginteger
* binary
* date
* float
* decimal
* integer
* time
* datetime
* timestamp
* uuid

More types can be added dynamically in a bit.

Statements can be reused by binding new values to the parameters in the query:

```php
$statement = $connection->prepare('SELECT * from articles WHERE id = :id');
$statement->bind(['id' => 1], ['id' => 'integer']);
$results = $statement->fetchAll('assoc');

$statement->bind(['id' => 1], ['id' => 'integer']);
$results = $statement->fetchAll('assoc');
```

### Updating Rows

Updating can be done using the `update()` function in the connection object. In the following
example we will update the title of the article with id = 1:

```php
$connection->update('articles', ['title' => 'New title'], ['id' => 1]);
```

The concept of data types is central to this library, so you can use the last parameter of the function
to specify what types should be used:

```php
$connection->update(
	'articles',
	['title' => 'New title'],
	['created >=' => new DateTime('-3 day'), 'created <' => new DateTime('now')],
	['created' => 'datetime']
);
```

The example above will execute the following SQL:

```sql
UPDATE articles SET title = 'New Title' WHERE created >= '2014-10-10 00:00:00' AND created < '2014-10-13 00:00:00';
```

More on creating complex where conditions or more complex update queries later.

### Deleting Rows

Similarly, the `delete()` method is used to delete rows from the database:

```php
$connection->delete('articles', ['created <' => DateTime('now')], ['created' => 'date']);
```

Will generate the following SQL

```sql
DELETE FROM articles where created < '2014-10-10'
```

### Inserting Rows

Rows can be inserted using the `insert()` method:

```php
$connection->insert(
	'articles',
	['title' => 'My Title', 'body' => 'Some paragraph', 'created' => new DateTime()],
	['created' => 'datetime']
);
```

More complex updates, deletes and insert queries can be generated using the `Query` class.

## Query Builder

One of the goals of this library is to allow the generation of both simple and complex queries with
ease. The query builder can be accessed by getting a new instance of a query:

```php
$query = $connection->newQuery();
```

### Selecting Fields

Adding fields to the `SELECT` clause:

```php
$query->select(['id', 'title', 'body']);

// Results in SELECT id AS pk, title AS aliased_title, body ...
$query->select(['pk' => 'id', 'aliased_title' => 'title', 'body']);

// Use a closure
$query->select(function ($query) {
	return ['id', 'title', 'body'];
});
```

### Where Conditions

Generating conditions:

```php
// WHERE id = 1
$query->where(['id' => 1]);

// WHERE id > 2
$query->where(['id >' => 1]);
```

As you can see you can use any operator by placing it with a space after the field name.
Adding multiple conditions is easy as well:

```php
$query->where(['id >' => 1])->andWhere(['title' => 'My Title']);

// Equivalent to
$query->where(['id >' => 1, 'title' => 'My title']);
```

It is possible to generate `OR` conditions as well

```php
$query->where(['id >' => 1])->orWhere(['title' => 'My Title']);

// Equivalent to
$query->where(['OR' => ['id >' => 1, 'title' => 'My title']]);
```

For even more complex conditions you can use closures and expression objects:

```php
$query->where(function ($exp) {
        return $exp
            ->eq('author_id', 2)
            ->eq('published', true)
            ->notEq('spam', true)
            ->gt('view_count', 10);
    });
```

Which results in:

```sql
SELECT * FROM articles
WHERE
	author_id = 2
	AND published = 1
	AND spam != 1
	AND view_count > 10
```

Combining expressions is also possible:

```php
$query->where(function ($exp) {
        $orConditions = $exp->or_(['author_id' => 2])
            ->eq('author_id', 5);
        return $exp
            ->not($orConditions)
            ->lte('view_count', 10);
    });
```

That generates:

```sql
SELECT *
FROM articles
WHERE
	NOT (author_id = 2 OR author_id = 5)
	AND view_count <= 10
```

When using the expression objects you can use the following methods to create conditions:

* `eq()` Creates an equality condition.
* `notEq()` Create an inequality condition
* `like()` Create a condition using the LIKE operator.
* `notLike()` Create a negated LIKE condition.
* `in()` Create a condition using IN.
* `notIn()` Create a negated condition using IN.
* `gt()` Create a > condition.
* `gte()` Create a >= condition.
* `lt()` Create a < condition.
* `lte()` Create a <= condition.
* `isNull()` Create an IS NULL condition.
* `isNotNull()` Create a negated IS NULL condition.

### Aggregates and SQL Functions

```php
// Results in SELECT COUNT(*) count FROM ...
$query->select(['count' => $query->func()->count('*')]);
```

A number of commonly used functions can be created with the func() method:

* `sum()` Calculate a sum. The arguments will be treated as literal values.
* `avg()` Calculate an average. The arguments will be treated as literal values.
* `min()` Calculate the min of a column. The arguments will be treated as literal values.
* `max()` Calculate the max of a column. The arguments will be treated as literal values.
* `count()` Calculate the count. The arguments will be treated as literal values.
* `concat()` Concatenate two values together. The arguments are treated as bound parameters unless marked as literal.
* `coalesce()` Coalesce values. The arguments are treated as bound parameters unless marked as literal.
* `dateDiff()` Get the difference between two dates/times. The arguments are treated as bound parameters unless marked as literal.
* `now()` Take either 'time' or 'date' as an argument allowing you to get either the current time, or current date.

When providing arguments for SQL functions, there are two kinds of parameters you can use, literal arguments and bound parameters. Literal
parameters allow you to reference columns or other SQL literals. Bound parameters can be used to safely add user data to SQL functions.
For example:

```php
$concat = $query->func()->concat([
    'title' => 'literal',
    ' NEW'
]);
$query->select(['title' => $concat]);
```

The above generates:

```sql
SELECT CONCAT(title, :c0) ...;
```

### Other SQL Clauses

Read of all other SQL clases that the builder is capable of generating in the [official API docs](http://api.cakephp.org/3.0/class-Cake.Database.Query.html)

### Getting Results out of a Query

Once you’ve made your query, you’ll want to retrieve rows from it. There are a few ways of doing this:

```php
// Iterate the query
foreach ($query as $row) {
    // Do stuff.
}

// Get the statement and fetch all results
$results = $query->execute()->fetchAll('assoc');
```

## Official API

You can read the official [official API docs](http://api.cakephp.org/3.0/namespace-Cake.Database.html) to learn more of what this library
has to offer.
