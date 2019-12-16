[![Total Downloads](https://img.shields.io/packagist/dt/cakephp/form.svg?style=flat-square)](https://packagist.org/packages/cakephp/form)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE.txt)

# CakePHP Form Library

Form abstraction used to create forms not tied to ORM backed models,
or to other permanent datastores. Ideal for implementing forms on top of
API services, or contact forms.

## Usage


```php
use Cake\Form\Form;
use Cake\Form\Schema;
use Cake\Validation\Validator;

class ContactForm extends Form
{

    protected function _buildSchema(Schema $schema)
    {
        return $schema->addField('name', 'string')
            ->addField('email', ['type' => 'string'])
            ->addField('body', ['type' => 'text']);
    }

    public function validationDefault(Validator $validator)
    {
        return $validator->add('name', 'length', [
                'rule' => ['minLength', 10],
                'message' => 'A name is required'
            ])->add('email', 'format', [
                'rule' => 'email',
                'message' => 'A valid email address is required',
            ]);
    }

    protected function _execute(array $data)
    {
        // Send an email.
        return true;
    }
}
```

In the above example we see the 3 hook methods that forms provide:

- `_buildSchema()` is used to define the schema data. You can define field type, length, and precision.
- `validationDefault()` Gets a `Cake\Validation\Validator` instance that you can attach validators to.
- `_execute()` lets you define the behavior you want to happen when `execute()` is called and the data is valid.

You can always define additional public methods as you need as well.

```php
$contact = new ContactForm();
$success = $contact->execute($data);
$errors = $contact->getErrors();
```

## Documentation

Please make sure you check the [official documentation](https://book.cakephp.org/3/en/core-libraries/form.html)
