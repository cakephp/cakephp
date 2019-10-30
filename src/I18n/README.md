[![Total Downloads](https://img.shields.io/packagist/dt/cakephp/i18n.svg?style=flat-square)](https://packagist.org/packages/cakephp/i18n)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE.txt)

# CakePHP Internationalization Library

The I18n library provides a `I18n` service locator that can be used for setting
the current locale, building translation bundles and translating messages.

Additionally, it provides the `Time` and `Number` classes which can be used to
output dates, currencies and any numbers in the right format for the specified locale.

## Usage

Internally, the `I18n` class uses [Aura.Intl](https://github.com/auraphp/Aura.Intl).
Getting familiar with it will help you understand how to build and manipulate translation bundles,
should you wish to create them manually instead of using the conventions this library uses.

### Setting the Current Locale

```php
use Cake\I18n\I18n;

I18n::setLocale('en_US');
```

### Setting path to folder containing po files.

```php
use Cake\Core\Configure;

Configure::write('App.paths.locales', ['/path/with/trailing/slash/']);
```

Please refer to the [CakePHP Manual](https://book.cakephp.org/3/en/core-libraries/internationalization-and-localization.html#language-files) for details
about expected folder structure and file naming.

### Translating a Message

```php
echo __(
    'Hi {0,string}, your balance on the {1,date} is {2,number,currency}',
    ['Charles', '2014-01-13 11:12:00', 1354.37]
);

// Returns
Hi Charles, your balance on the Jan 13, 2014, 11:12 AM is $ 1,354.37
```

### Creating Your Own Translators

```php
use Cake\I18n\I18n;
use Aura\Intl\Package;

I18n::translator('animals', 'fr_FR', function () {
    $package = new Package(
        'default', // The formatting strategy (ICU)
        'default', // The fallback domain
    );
    $package->setMessages([
        'Dog' => 'Chien',
        'Cat' => 'Chat',
        'Bird' => 'Oiseau'
        ...
    ]);

    return $package;
});

I18n::getLocale('fr_FR');
__d('animals', 'Dog'); // Returns "Chien"
```

### Formatting Time

```php
$time = Time::now();
echo $time; // shows '4/20/14, 10:10 PM' for the en-US locale
```

### Formatting Numbers

```php
echo Number::format(100100100);
```

```php
echo Number::currency(123456.7890, 'EUR');
// outputs €123,456.79
```

## Documentation

Please make sure you check the [official I18n
documentation](https://book.cakephp.org/3/en/core-libraries/internationalization-and-localization.html).

The [documentation for the Time
class](https://book.cakephp.org/3/en/core-libraries/time.html) contains
instructions on how to configure and output time strings for selected locales.

The [documentation for the Number
class](https://book.cakephp.org/3/en/core-libraries/number.html) shows how to
use the `Number` class for displaying numbers in specific locales.
