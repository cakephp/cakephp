[![Total Downloads](https://img.shields.io/packagist/dt/cakephp/validation.svg?style=flat-square)](https://packagist.org/packages/cakephp/validation)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE.txt)

# CakePHP Validation Library

The validation library in CakePHP provides features to build validators that can validate arbitrary
arrays of data with ease.

## Usage

Validator objects define the rules that apply to a set of fields. Validator objects contain a mapping between
fields and validation sets. Creating a validator is simple:

```php
use Cake\Validation\Validator;

$validator = new Validator();
$validator
    ->requirePresence('email')
    ->add('email', 'validFormat', [
        'rule' => 'email',
        'message' => 'E-mail must be valid'
    ])
    ->requirePresence('name')
    ->notEmptyString('name', 'We need your name.')
    ->requirePresence('comment')
    ->notEmptyString('comment', 'You need to give a comment.');

$errors = $validator->validate($_POST);
if (!empty($errors)) {
    // display errors.
}
```

## Documentation

Please make sure you check the [official documentation](https://book.cakephp.org/4/en/core-libraries/validation.html)
