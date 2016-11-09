<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console;

use Cake\Console\Exception\ConsoleException;
use Cake\Utility\Inflector;

/**
 * Handles parsing the ARGV in the command line and provides support
 * for GetOpt compatible option definition. Provides a builder pattern implementation
 * for creating shell option parsers.
 *
 * ### Options
 *
 * Named arguments come in two forms, long and short. Long arguments are preceded
 * by two - and give a more verbose option name. i.e. `--version`. Short arguments are
 * preceded by one - and are only one character long. They usually match with a long option,
 * and provide a more terse alternative.
 *
 * ### Using Options
 *
 * Options can be defined with both long and short forms. By using `$parser->addOption()`
 * you can define new options. The name of the option is used as its long form, and you
 * can supply an additional short form, with the `short` option. Short options should
 * only be one letter long. Using more than one letter for a short option will raise an exception.
 *
 * Calling options can be done using syntax similar to most *nix command line tools. Long options
 * cane either include an `=` or leave it out.
 *
 * `cake myshell command --connection default --name=something`
 *
 * Short options can be defined singly or in groups.
 *
 * `cake myshell command -cn`
 *
 * Short options can be combined into groups as seen above. Each letter in a group
 * will be treated as a separate option. The previous example is equivalent to:
 *
 * `cake myshell command -c -n`
 *
 * Short options can also accept values:
 *
 * `cake myshell command -c default`
 *
 * ### Positional arguments
 *
 * If no positional arguments are defined, all of them will be parsed. If you define positional
 * arguments any arguments greater than those defined will cause exceptions. Additionally you can
 * declare arguments as optional, by setting the required param to false.
 *
 * ```
 * $parser->addArgument('model', ['required' => false]);
 * ```
 *
 * ### Providing Help text
 *
 * By providing help text for your positional arguments and named arguments, the ConsoleOptionParser
 * can generate a help display for you. You can view the help for shells by using the `--help` or `-h` switch.
 */
class ConsoleOptionParser
{

    /**
     * Description text - displays before options when help is generated
     *
     * @see \Cake\Console\ConsoleOptionParser::description()
     * @var string
     */
    protected $_description = null;

    /**
     * Epilog text - displays after options when help is generated
     *
     * @see \Cake\Console\ConsoleOptionParser::epilog()
     * @var string
     */
    protected $_epilog = null;

    /**
     * Option definitions.
     *
     * @see \Cake\Console\ConsoleOptionParser::addOption()
     * @var array
     */
    protected $_options = [];

    /**
     * Map of short -> long options, generated when using addOption()
     *
     * @var string
     */
    protected $_shortOptions = [];

    /**
     * Positional argument definitions.
     *
     * @see \Cake\Console\ConsoleOptionParser::addArgument()
     * @var array
     */
    protected $_args = [];

    /**
     * Subcommands for this Shell.
     *
     * @see \Cake\Console\ConsoleOptionParser::addSubcommand()
     * @var array
     */
    protected $_subcommands = [];

    /**
     * Command name.
     *
     * @var string
     */
    protected $_command = '';

    /**
     * Array of args (argv).
     *
     * @var array
     */
    protected $_tokens = [];

    /**
     * Construct an OptionParser so you can define its behavior
     *
     * @param string|null $command The command name this parser is for. The command name is used for generating help.
     * @param bool $defaultOptions Whether you want the verbose and quiet options set. Setting
     *  this to false will prevent the addition of `--verbose` & `--quiet` options.
     */
    public function __construct($command = null, $defaultOptions = true)
    {
        $this->command($command);

        $this->addOption('help', [
            'short' => 'h',
            'help' => 'Display this help.',
            'boolean' => true
        ]);

        if ($defaultOptions) {
            $this->addOption('verbose', [
                'short' => 'v',
                'help' => 'Enable verbose output.',
                'boolean' => true
            ])->addOption('quiet', [
                'short' => 'q',
                'help' => 'Enable quiet output.',
                'boolean' => true
            ]);
        }
    }

    /**
     * Static factory method for creating new OptionParsers so you can chain methods off of them.
     *
     * @param string|null $command The command name this parser is for. The command name is used for generating help.
     * @param bool $defaultOptions Whether you want the verbose and quiet options set.
     * @return $this
     */
    public static function create($command, $defaultOptions = true)
    {
        return new ConsoleOptionParser($command, $defaultOptions);
    }

    /**
     * Build a parser from an array. Uses an array like
     *
     * ```
     * $spec = [
     *      'description' => 'text',
     *      'epilog' => 'text',
     *      'arguments' => [
     *          // list of arguments compatible with addArguments.
     *      ],
     *      'options' => [
     *          // list of options compatible with addOptions
     *      ],
     *      'subcommands' => [
     *          // list of subcommands to add.
     *      ]
     * ];
     * ```
     *
     * @param array $spec The spec to build the OptionParser with.
     * @param bool $defaultOptions Whether you want the verbose and quiet options set.
     * @return $this
     */
    public static function buildFromArray($spec, $defaultOptions = true)
    {
        $parser = new ConsoleOptionParser($spec['command'], $defaultOptions);
        if (!empty($spec['arguments'])) {
            $parser->addArguments($spec['arguments']);
        }
        if (!empty($spec['options'])) {
            $parser->addOptions($spec['options']);
        }
        if (!empty($spec['subcommands'])) {
            $parser->addSubcommands($spec['subcommands']);
        }
        if (!empty($spec['description'])) {
            $parser->description($spec['description']);
        }
        if (!empty($spec['epilog'])) {
            $parser->epilog($spec['epilog']);
        }

        return $parser;
    }

    /**
     * Returns an array representation of this parser.
     *
     * @return array
     */
    public function toArray()
    {
        $result = [
            'command' => $this->_command,
            'arguments' => $this->_args,
            'options' => $this->_options,
            'subcommands' => $this->_subcommands,
            'description' => $this->_description,
            'epilog' => $this->_epilog
        ];

        return $result;
    }

    /**
     * Get or set the command name for shell/task.
     *
     * @param array|\Cake\Console\ConsoleOptionParser $spec ConsoleOptionParser or spec to merge with.
     * @return $this
     */
    public function merge($spec)
    {
        if ($spec instanceof ConsoleOptionParser) {
            $spec = $spec->toArray();
        }
        if (!empty($spec['arguments'])) {
            $this->addArguments($spec['arguments']);
        }
        if (!empty($spec['options'])) {
            $this->addOptions($spec['options']);
        }
        if (!empty($spec['subcommands'])) {
            $this->addSubcommands($spec['subcommands']);
        }
        if (!empty($spec['description'])) {
            $this->description($spec['description']);
        }
        if (!empty($spec['epilog'])) {
            $this->epilog($spec['epilog']);
        }

        return $this;
    }

    /**
     * Gets or sets the command name for shell/task.
     *
     * @param string|null $text The text to set, or null if you want to read
     * @return string|$this If reading, the value of the command. If setting $this will be returned.
     */
    public function command($text = null)
    {
        if ($text !== null) {
            $this->_command = Inflector::underscore($text);

            return $this;
        }

        return $this->_command;
    }

    /**
     * Get or set the description text for shell/task.
     *
     * @param string|array|null $text The text to set, or null if you want to read. If an array the
     *   text will be imploded with "\n".
     * @return string|$this If reading, the value of the description. If setting $this will be returned.
     */
    public function description($text = null)
    {
        if ($text !== null) {
            if (is_array($text)) {
                $text = implode("\n", $text);
            }
            $this->_description = $text;

            return $this;
        }

        return $this->_description;
    }

    /**
     * Get or set an epilog to the parser. The epilog is added to the end of
     * the options and arguments listing when help is generated.
     *
     * @param string|array|null $text Text when setting or null when reading. If an array the text will
     *   be imploded with "\n".
     * @return string|$this If reading, the value of the epilog. If setting $this will be returned.
     */
    public function epilog($text = null)
    {
        if ($text !== null) {
            if (is_array($text)) {
                $text = implode("\n", $text);
            }
            $this->_epilog = $text;

            return $this;
        }

        return $this->_epilog;
    }

    /**
     * Add an option to the option parser. Options allow you to define optional or required
     * parameters for your console application. Options are defined by the parameters they use.
     *
     * ### Options
     *
     * - `short` - The single letter variant for this option, leave undefined for none.
     * - `help` - Help text for this option. Used when generating help for the option.
     * - `default` - The default value for this option. Defaults are added into the parsed params when the
     *    attached option is not provided or has no value. Using default and boolean together will not work.
     *    are added into the parsed parameters when the option is undefined. Defaults to null.
     * - `boolean` - The option uses no value, it's just a boolean switch. Defaults to false.
     *    If an option is defined as boolean, it will always be added to the parsed params. If no present
     *    it will be false, if present it will be true.
     * - `choices` A list of valid choices for this option. If left empty all values are valid..
     *   An exception will be raised when parse() encounters an invalid value.
     *
     * @param \Cake\Console\ConsoleInputOption|string $name The long name you want to the value to be parsed out as when options are parsed.
     *   Will also accept an instance of ConsoleInputOption
     * @param array $options An array of parameters that define the behavior of the option
     * @return $this
     */
    public function addOption($name, array $options = [])
    {
        if ($name instanceof ConsoleInputOption) {
            $option = $name;
            $name = $option->name();
        } else {
            $defaults = [
                'name' => $name,
                'short' => null,
                'help' => '',
                'default' => null,
                'boolean' => false,
                'choices' => []
            ];
            $options += $defaults;
            $option = new ConsoleInputOption($options);
        }
        $this->_options[$name] = $option;
        asort($this->_options);
        if ($option->short() !== null) {
            $this->_shortOptions[$option->short()] = $name;
            asort($this->_shortOptions);
        }

        return $this;
    }

    /**
     * Remove an option from the option parser.
     *
     * @param string $name The option name to remove.
     * @return $this
     */
    public function removeOption($name)
    {
        unset($this->_options[$name]);

        return $this;
    }

    /**
     * Add a positional argument to the option parser.
     *
     * ### Params
     *
     * - `help` The help text to display for this argument.
     * - `required` Whether this parameter is required.
     * - `index` The index for the arg, if left undefined the argument will be put
     *   onto the end of the arguments. If you define the same index twice the first
     *   option will be overwritten.
     * - `choices` A list of valid choices for this argument. If left empty all values are valid..
     *   An exception will be raised when parse() encounters an invalid value.
     *
     * @param \Cake\Console\ConsoleInputArgument|string $name The name of the argument.
     *   Will also accept an instance of ConsoleInputArgument.
     * @param array $params Parameters for the argument, see above.
     * @return $this
     */
    public function addArgument($name, array $params = [])
    {
        if ($name instanceof ConsoleInputArgument) {
            $arg = $name;
            $index = count($this->_args);
        } else {
            $defaults = [
                'name' => $name,
                'help' => '',
                'index' => count($this->_args),
                'required' => false,
                'choices' => []
            ];
            $options = $params + $defaults;
            $index = $options['index'];
            unset($options['index']);
            $arg = new ConsoleInputArgument($options);
        }
        foreach ($this->_args as $k => $a) {
            if ($a->isEqualTo($arg)) {
                return $this;
            }
        }
        $this->_args[$index] = $arg;
        ksort($this->_args);

        return $this;
    }

    /**
     * Add multiple arguments at once. Take an array of argument definitions.
     * The keys are used as the argument names, and the values as params for the argument.
     *
     * @param array $args Array of arguments to add.
     * @see \Cake\Console\ConsoleOptionParser::addArgument()
     * @return $this
     */
    public function addArguments(array $args)
    {
        foreach ($args as $name => $params) {
            if ($params instanceof ConsoleInputArgument) {
                $name = $params;
                $params = [];
            }
            $this->addArgument($name, $params);
        }

        return $this;
    }

    /**
     * Add multiple options at once. Takes an array of option definitions.
     * The keys are used as option names, and the values as params for the option.
     *
     * @param array $options Array of options to add.
     * @see \Cake\Console\ConsoleOptionParser::addOption()
     * @return $this
     */
    public function addOptions(array $options)
    {
        foreach ($options as $name => $params) {
            if ($params instanceof ConsoleInputOption) {
                $name = $params;
                $params = [];
            }
            $this->addOption($name, $params);
        }

        return $this;
    }

    /**
     * Append a subcommand to the subcommand list.
     * Subcommands are usually methods on your Shell, but can also be used to document Tasks.
     *
     * ### Options
     *
     * - `help` - Help text for the subcommand.
     * - `parser` - A ConsoleOptionParser for the subcommand. This allows you to create method
     *    specific option parsers. When help is generated for a subcommand, if a parser is present
     *    it will be used.
     *
     * @param \Cake\Console\ConsoleInputSubcommand|string $name Name of the subcommand. Will also accept an instance of ConsoleInputSubcommand
     * @param array $options Array of params, see above.
     * @return $this
     */
    public function addSubcommand($name, array $options = [])
    {
        if ($name instanceof ConsoleInputSubcommand) {
            $command = $name;
            $name = $command->name();
        } else {
            $defaults = [
                'name' => $name,
                'help' => '',
                'parser' => null
            ];
            $options += $defaults;
            $command = new ConsoleInputSubcommand($options);
        }
        $this->_subcommands[$name] = $command;
        asort($this->_subcommands);

        return $this;
    }

    /**
     * Remove a subcommand from the option parser.
     *
     * @param string $name The subcommand name to remove.
     * @return $this
     */
    public function removeSubcommand($name)
    {
        unset($this->_subcommands[$name]);

        return $this;
    }

    /**
     * Add multiple subcommands at once.
     *
     * @param array $commands Array of subcommands.
     * @return $this
     */
    public function addSubcommands(array $commands)
    {
        foreach ($commands as $name => $params) {
            if ($params instanceof ConsoleInputSubcommand) {
                $name = $params;
                $params = [];
            }
            $this->addSubcommand($name, $params);
        }

        return $this;
    }

    /**
     * Gets the arguments defined in the parser.
     *
     * @return array Array of argument descriptions
     */
    public function arguments()
    {
        return $this->_args;
    }

    /**
     * Get the defined options in the parser.
     *
     * @return array
     */
    public function options()
    {
        return $this->_options;
    }

    /**
     * Get the array of defined subcommands
     *
     * @return array
     */
    public function subcommands()
    {
        return $this->_subcommands;
    }

    /**
     * Parse the argv array into a set of params and args. If $command is not null
     * and $command is equal to a subcommand that has a parser, that parser will be used
     * to parse the $argv
     *
     * @param array $argv Array of args (argv) to parse.
     * @return array [$params, $args]
     * @throws \Cake\Console\Exception\ConsoleException When an invalid parameter is encountered.
     */
    public function parse($argv)
    {
        $command = isset($argv[0]) ? $argv[0] : null;
        if (isset($this->_subcommands[$command])) {
            array_shift($argv);
        }
        if (isset($this->_subcommands[$command]) && $this->_subcommands[$command]->parser()) {
            return $this->_subcommands[$command]->parser()->parse($argv);
        }
        $params = $args = [];
        $this->_tokens = $argv;
        while (($token = array_shift($this->_tokens)) !== null) {
            if (isset($this->_subcommands[$token])) {
                continue;
            }
            if (substr($token, 0, 2) === '--') {
                $params = $this->_parseLongOption($token, $params);
            } elseif (substr($token, 0, 1) === '-') {
                $params = $this->_parseShortOption($token, $params);
            } else {
                $args = $this->_parseArg($token, $args);
            }
        }
        foreach ($this->_args as $i => $arg) {
            if ($arg->isRequired() && !isset($args[$i]) && empty($params['help'])) {
                throw new ConsoleException(
                    sprintf('Missing required arguments. %s is required.', $arg->name())
                );
            }
        }
        foreach ($this->_options as $option) {
            $name = $option->name();
            $isBoolean = $option->isBoolean();
            $default = $option->defaultValue();

            if ($default !== null && !isset($params[$name]) && !$isBoolean) {
                $params[$name] = $default;
            }
            if ($isBoolean && !isset($params[$name])) {
                $params[$name] = false;
            }
        }

        return [$params, $args];
    }

    /**
     * Gets formatted help for this parser object.
     * Generates help text based on the description, options, arguments, subcommands and epilog
     * in the parser.
     *
     * @param string|null $subcommand If present and a valid subcommand that has a linked parser.
     *    That subcommands help will be shown instead.
     * @param string $format Define the output format, can be text or xml
     * @param int $width The width to format user content to. Defaults to 72
     * @return string Generated help.
     */
    public function help($subcommand = null, $format = 'text', $width = 72)
    {
        if (isset($this->_subcommands[$subcommand]) &&
            $this->_subcommands[$subcommand]->parser() instanceof self
        ) {
            $subparser = $this->_subcommands[$subcommand]->parser();
            $subparser->command($this->command() . ' ' . $subparser->command());

            return $subparser->help(null, $format, $width);
        }

        $formatter = new HelpFormatter($this);
        if ($format === 'text') {
            return $formatter->text($width);
        }
        if ($format === 'xml') {
            return $formatter->xml();
        }
    }

    /**
     * Parse the value for a long option out of $this->_tokens. Will handle
     * options with an `=` in them.
     *
     * @param string $option The option to parse.
     * @param array $params The params to append the parsed value into
     * @return array Params with $option added in.
     */
    protected function _parseLongOption($option, $params)
    {
        $name = substr($option, 2);
        if (strpos($name, '=') !== false) {
            list($name, $value) = explode('=', $name, 2);
            array_unshift($this->_tokens, $value);
        }

        return $this->_parseOption($name, $params);
    }

    /**
     * Parse the value for a short option out of $this->_tokens
     * If the $option is a combination of multiple shortcuts like -otf
     * they will be shifted onto the token stack and parsed individually.
     *
     * @param string $option The option to parse.
     * @param array $params The params to append the parsed value into
     * @return array Params with $option added in.
     * @throws \Cake\Console\Exception\ConsoleException When unknown short options are encountered.
     */
    protected function _parseShortOption($option, $params)
    {
        $key = substr($option, 1);
        if (strlen($key) > 1) {
            $flags = str_split($key);
            $key = $flags[0];
            for ($i = 1, $len = count($flags); $i < $len; $i++) {
                array_unshift($this->_tokens, '-' . $flags[$i]);
            }
        }
        if (!isset($this->_shortOptions[$key])) {
            throw new ConsoleException(sprintf('Unknown short option `%s`', $key));
        }
        $name = $this->_shortOptions[$key];

        return $this->_parseOption($name, $params);
    }

    /**
     * Parse an option by its name index.
     *
     * @param string $name The name to parse.
     * @param array $params The params to append the parsed value into
     * @return array Params with $option added in.
     * @throws \Cake\Console\Exception\ConsoleException
     */
    protected function _parseOption($name, $params)
    {
        if (!isset($this->_options[$name])) {
            throw new ConsoleException(sprintf('Unknown option `%s`', $name));
        }
        $option = $this->_options[$name];
        $isBoolean = $option->isBoolean();
        $nextValue = $this->_nextToken();
        $emptyNextValue = (empty($nextValue) && $nextValue !== '0');
        if (!$isBoolean && !$emptyNextValue && !$this->_optionExists($nextValue)) {
            array_shift($this->_tokens);
            $value = $nextValue;
        } elseif ($isBoolean) {
            $value = true;
        } else {
            $value = $option->defaultValue();
        }
        if ($option->validChoice($value)) {
            $params[$name] = $value;

            return $params;
        }

        return [];
    }

    /**
     * Check to see if $name has an option (short/long) defined for it.
     *
     * @param string $name The name of the option.
     * @return bool
     */
    protected function _optionExists($name)
    {
        if (substr($name, 0, 2) === '--') {
            return isset($this->_options[substr($name, 2)]);
        }
        if ($name{0} === '-' && $name{1} !== '-') {
            return isset($this->_shortOptions[$name{1}]);
        }

        return false;
    }

    /**
     * Parse an argument, and ensure that the argument doesn't exceed the number of arguments
     * and that the argument is a valid choice.
     *
     * @param string $argument The argument to append
     * @param array $args The array of parsed args to append to.
     * @return array Args
     * @throws \Cake\Console\Exception\ConsoleException
     */
    protected function _parseArg($argument, $args)
    {
        if (empty($this->_args)) {
            $args[] = $argument;

            return $args;
        }
        $next = count($args);
        if (!isset($this->_args[$next])) {
            throw new ConsoleException('Too many arguments.');
        }

        if ($this->_args[$next]->validChoice($argument)) {
            $args[] = $argument;

            return $args;
        }
    }

    /**
     * Find the next token in the argv set.
     *
     * @return string next token or ''
     */
    protected function _nextToken()
    {
        return isset($this->_tokens[0]) ? $this->_tokens[0] : '';
    }
}
