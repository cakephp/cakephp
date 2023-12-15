[![Total Downloads](https://img.shields.io/packagist/dt/cakephp/http.svg?style=flat-square)](https://packagist.org/packages/cakephp/console)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE.txt)

# CakePHP Console Library

This library provides a framework for building command line applications from a
set of commands. It provides abstractions for defining option and argument
parsers, and dispatching commands.

# installation

You can install it from Composer. In your project:

```
composer require cakephp/console
```

# Getting Started

To start, define an entry point script and Application class which defines
bootstrap logic, and binds your commands. Lets put our entrypoint script in
`bin/tool.php`:

```php
#!/usr/bin/php -q
<?php
// Check platform requirements
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Application;
use Cake\Console\CommandRunner;

// Build the runner with an application and root executable name.
$runner = new CommandRunner(new Application(), 'tool');
exit($runner->run($argv));
````

For our `Application` class we can start with:

```php
<?php
namespace App;

use App\Command\HelloCommand;
use Cake\Core\ConsoleApplicationInterface;
use Cake\Console\CommandCollection;

class Application implements ConsoleApplicationInterface
{
    /**
     * Load all the application configuration and bootstrap logic.
     *
     * @return void
     */
    public function bootstrap(): void
    {
        // Load configuration here. This is the first
        // method Cake\Console\CommandRunner will call on your application.
    }


    /**
     * Define the console commands for an application.
     *
     * @param \Cake\Console\CommandCollection $commands The CommandCollection to add commands into.
     * @return \Cake\Console\CommandCollection The updated collection.
     */
    public function console(CommandCollection $commands): CommandCollection
    {
        $commands->add('hello', HelloCommand::class);

        return $commands;
    }
}
```

Next we'll build a very simple `HelloCommand`:

```php
<?php
namespace App\Command;

use Cake\Console\Arguments;
use Cake\Console\BaseCommand;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

class HelloCommand extends BaseCommand
{
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser
            ->addArgument('name', [
                'required' => true,
                'help' => 'The name to say hello to',
            ])
            ->addOption('color', [
                'choices' => ['none', 'green'],
                'default' => 'none',
                'help' => 'The color to use.'
            ]);

        return $parser;
    }

    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $color = $args->getOption('color');
        if ($color === 'none') {
            $io->out("Hello {$args->getArgument('name')}");
        } elseif ($color == 'green') {
            $io->out("<success>Hello {$args->getArgument('name')}</success>");
        }

        return static::CODE_SUCCESS;
    }
}
```

Next we can run our command with `php bin/tool.php hello Syd`. To learn more
about the various features we've used in this example read the docs:

* [Option Parsing](https://book.cakephp.org/5/en/console-commands/option-parsers.html)
* [Input & Output](https://book.cakephp.org/5/en/console-commands/input-output.html)

