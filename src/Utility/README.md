# CakePHP Utility Classes

This library provides a range of utility classes that are used throughout the CakePHP framework

## What's in the toolbox?

### Hash

A ``Hash`` (as in PHP arrays) class, capable of extracting data using an intuitive DSL:

```php
$things = [
    ['name' => 'Mark', 'age' => 15],
    ['name' => 'Susan', 'age' => 30]
    ['name' => 'Lucy', 'age' => 25]
];

$bigPeople = Hash::extract($things, '{n}[age>21].name');

// $bigPeople will contain ['Susan', 'Lucy']
```

Check the [official Hash class documentation](http://book.cakephp.org/3.0/en/core-libraries/hash.html)

### Inflector

The Inflector class takes a string and can manipulate it to handle word variations
such as pluralizations or camelizing.

```php
echo Inflector::pluralize('Apple'); // echoes Apples

echo Inflector::singularize('People'); // echoes Person
```

Check the [official Inflector class documentation](http://book.cakephp.org/3.0/en/core-libraries/inflector.html)

### String

The String class includes convenience methods for creating and manipulating strings.

```php
String::insert(
    'My name is :name and I am :age years old.',
    array('name' => 'Bob', 'age' => '65')
);
// Returns: "My name is Bob and I am 65 years old."

$text = 'This is the song that never ends.';
$result = String::wrap($text, 22);

// Returns
This is the song
that never ends.
```

Check the [official String class documentation](http://book.cakephp.org/3.0/en/core-libraries/string.html)

### Security

The security library handles basic security measures such as providing methods for hashing and encrypting data.

```php
$key = 'wt1U5MACWJFTXGenFoZoiLwQGrLgdbHA';
$result = Security::encrypt($value, $key);

Security::decrypt($result, $key);
```

Check the [official Security class documentation](http://book.cakephp.org/3.0/en/core-libraries/security.html)

### Xml

The Xml class allows you to easily transform arrays into SimpleXMLElement or DOMDocument objects
and back into arrays again

```php
$data = array(
    'post' => array(
        'id' => 1,
        'title' => 'Best post',
        'body' => ' ... '
    )
);
$xml = Xml::build($data);
```

Check the [official Xml class documentation](http://book.cakephp.org/3.0/en/core-libraries/xml.html)
