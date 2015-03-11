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
    ->notEmpty('name', 'We need your name.')
    ->requirePresence('comment')
    ->notEmpty('comment', 'You need to give a comment.');

$errors = $validator->errors($_POST);
if (!empty($errors)) {
    // display errors.
}
```

## Documentation

Please make sure you check the [official documentation](http://book.cakephp.org/3.0/en/core-libraries/validation.html)
