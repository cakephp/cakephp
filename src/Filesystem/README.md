[![Total Downloads](https://img.shields.io/packagist/dt/cakephp/filesystem.svg?style=flat-square)](https://packagist.org/packages/cakephp/filesystem)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE.txt)

# CakePHP Filesystem Library

The Folder and File utilities are convenience classes to help you read from and write/append to files; list files within a folder and other common directory related tasks.

## Basic Usage

Create a folder instance and search for all the `.ctp` files within it:

```php
use Cake\Filesystem\Folder;

$dir = new Folder('/path/to/folder');
$files = $dir->find('.*\.ctp');
```

Now you can loop through the files and read from or write/append to the contents or simply delete the file:

```php
foreach ($files as $file) {
    $file = new File($dir->pwd() . DIRECTORY_SEPARATOR . $file);
    $contents = $file->read();
    // $file->write('I am overwriting the contents of this file');
    // $file->append('I am adding to the bottom of this file.');
    // $file->delete(); // I am deleting this file
    $file->close(); // Be sure to close the file when you're done
}
```

## Documentation

Please make sure you check the [official
documentation](https://book.cakephp.org/3/en/core-libraries/file-folder.html)
