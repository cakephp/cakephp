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

Create an entry point that loads your application and runs its command registry:

```php
#!/usr/bin/php -q
<?php
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Application;
use Cake\Console\CommandRunner;

exit(new CommandRunner(new Application(), 'tool')->run($argv));
```

Use the shared attribute resolver to discover commands, as with attribute routes
and event listeners. Configure a collection during bootstrap, then explicitly
register its commands in `Application::console()`:

```php
<?php
namespace App;

use Cake\AttributeResolver\AttributeResolver;
use Cake\Console\CommandCollection;
use Cake\Core\ConsoleApplicationInterface;

class Application implements ConsoleApplicationInterface
{
    /**
     * Configure discovery for application and loaded plugin commands.
     */
    public function bootstrap(): void
    {
        AttributeResolver::setConfig('commands', [
            'paths' => ['src/Command/*.php', 'src/Command/**/*.php'],
            'cache' => false,
        ]);
    }

    /**
     * Register commands from the configured attribute collection.
     *
     * @param \Cake\Console\CommandCollection $commands Command registry.
     * @return \Cake\Console\CommandCollection
     */
    public function console(CommandCollection $commands): CommandCollection
    {
        return $commands->addFromAttributes('commands');
    }
}
```

Paths are relative to the application root and loaded plugin roots. They can
cover any directory; command class names do not need a `Command` suffix.
Use an existing resolver configuration shared with routes and events if desired.
For production, replace `cache => false` with a configured cache name, such as
`_cake_attributes_`, and warm that collection with
`bin/cake attributes warm --config commands`.

Define a command using class-level attributes:

```php
<?php
namespace App\Command;

use Cake\Console\Attribute\Argument;
use Cake\Console\Attribute\AsCommand;
use Cake\Console\Attribute\Option;
use Cake\Console\BaseCommand;

/**
 * Greet a named person.
 */
#[AsCommand('hello', description: 'Greet someone.', epilog: 'Example: tool hello Syd --color green')]
#[Argument('name', required: true, help: 'The name to say hello to.')]
#[Option('color', choices: ['none', 'green'], default: 'none', help: 'The color to use.')]
class HelloCommand extends BaseCommand
{
    /**
     * Print a greeting using the parsed argument and option.
     *
     * @return int
     */
    public function execute(): int
    {
        $message = 'Hello ' . $this->args->getArgument('name');
        if ($this->args->getOption('color') === 'green') {
            $message = '<success>' . $message . '</success>';
        }
        $this->io->out($message);

        return static::CODE_SUCCESS;
    }
}
```

Run `php bin/tool.php hello Syd`, or add `--help` to see the generated help.
`description` supplies the command-listing summary. `help` adds lines to that
summary in command help, and `epilog` appears after arguments and options.

## Parser customization and inheritance

`Argument` and `Option` expose the existing console parser settings, including
choices, defaults, separators, required values, repeated option values, and
interactive prompts. Positional arguments follow declaration order unless an
explicit `index` is supplied.

Parent argument and option declarations are inherited. A child declaration with
the same name replaces the parent definition; arguments retain their position
unless an explicit index changes it. Duplicate declarations within one class are
errors. `AsCommand` identity and help metadata belong to the concrete class.

Attributes populate the parser before `buildOptionParser()`. The hook can add
runtime configuration or explicitly replace options with `removeOption()` and
`addOption()`. Existing lifecycle hooks, `$this->args`, `$this->io`, constructor
injection, and command execution remain available. Explicit overrides of
`defaultName()` and `getDescription()` retain normal PHP method precedence;
attribute discovery registers the name declared by `AsCommand`.

## Migrating to CakePHP 6

Convention-based directory discovery and `CommandScanner` have been removed,
along with `autoDiscover()`, `discoverPlugin()`, and `discoverDirectory()`.

Add `AsCommand` to discoverable command classes, include their paths in a resolver
configuration, and call `addFromAttributes()` from your application console hook.
For full-stack applications, call `parent::console($commands)` to retain the
built-in framework commands. These remain available without resolver configuration,
including the attribute inspection and cache-warming commands.

Unannotated commands can still be registered using
`$commands->add('hello', HelloCommand::class)`. Parser attributes also work on
explicitly registered commands and do not require a discovery configuration.

Discovery registers class names without constructing commands. Loaded plugins
with console support enabled receive qualified names such as `my_plugin.hello`,
plus short names when available. Application commands take preference over plugin
short names. Default plugin console hooks no longer scan command directories;
custom plugin hooks can still explicitly register commands.

* [Option Parsing](https://book.cakephp.org/5/en/console-commands/option-parsers.html)
* [Input & Output](https://book.cakephp.org/5/en/console-commands/input-output.html)
