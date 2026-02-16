<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         6.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Command;

use AttributeResolver\AttributeResolver;
use AttributeResolver\AttributeCollection;
use AttributeResolver\Enum\AttributeTargetType;
use AttributeResolver\ValueObject\AttributeInfo;
use Cake\Console\ConsoleOptionParser;

/**
 * Command to list discovered attributes.
 */
class AttributesListCommand extends Command
{
    /**
     * @inheritDoc
     */
    public static function defaultName(): string
    {
        return 'attributes list';
    }

    /**
     * @inheritDoc
     */
    public static function getDescription(): string
    {
        return 'List discovered PHP attributes in a table format.';
    }

    /**
     * Hook method for defining this command's option parser.
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);
        $parser
            ->setDescription(static::getDescription())
            ->addOption('attribute', [
                'short' => 'a',
                'help' => 'Filter by attribute class name (partial match supported).',
            ])
            ->addOption('class', [
                'short' => 'c',
                'help' => 'Filter by target class name (partial match supported).',
            ])
            ->addOption('namespace', [
                'short' => 'n',
                'help' => 'Filter by namespace pattern (supports wildcards).',
            ])
            ->addOption('type', [
                'short' => 't',
                'help' => 'Filter by target type (class, method, property, parameter, constant).',
            ])
            ->addOption('plugin', [
                'short' => 'p',
                'help' => 'Filter by plugin name.',
            ])
            ->addOption('verbose', [
                'help' => 'Display full class names without truncation.',
                'boolean' => true,
            ])
            ->addOption('format', [
                'short' => 'f',
                'help' => 'Output format (text, json).',
                'default' => 'text',
                'choices' => ['text', 'json'],
            ])
            ->addOption('config', [
                'default' => 'default',
                'help' => 'The Resolver configuration to use.',
            ]);

        return $parser;
    }

    /**
     * Implement this method with your command's logic.
     *
     * @return int|null The exit code or null for success
     */
    public function execute(): ?int
    {
        $configName = (string)$this->args->getOption('config');

        if (!AttributeResolver::getConfig($configName)) {
            $this->io->error(sprintf('Configuration "%s" does not exist.', $configName));

            return static::CODE_ERROR;
        }

        $collection = $this->getFilteredCollection($configName);
        $attributes = $collection->toList();

        $format = (string)$this->args->getOption('format');

        if ($attributes === []) {
            return match ($format) {
                'json' => $this->outputJson($attributes),
                default => $this->outputTextEmpty(),
            };
        }

        return match ($format) {
            'json' => $this->outputJson($attributes),
            default => $this->outputText($attributes),
        };
    }

    /**
     * Get filtered collection based on command arguments
     *
     * @param string $configName Configuration name
     * @return \AttributeResolver\AttributeCollection
     */
    protected function getFilteredCollection(string $configName): AttributeCollection
    {
        $collection = AttributeResolver::collection($configName);

        $attr = $this->args->getOption('attribute');
        if ($attr) {
            $collection = $collection->withAttributeContains((string)$attr);
        }

        $class = $this->args->getOption('class');
        if ($class) {
            $collection = $collection->withClassNameContains((string)$class);
        }

        $namespace = $this->args->getOption('namespace');
        if ($namespace) {
            $collection = $collection->withNamespace((string)$namespace);
        }

        $type = $this->args->getOption('type');
        if ($type) {
            $targetType = AttributeTargetType::tryFrom((string)$type);
            if ($targetType) {
                $collection = $collection->withTargetType($targetType);
            } else {
                // Invalid type should return empty collection
                return new AttributeCollection([]);
            }
        }

        $plugin = $this->args->getOption('plugin');
        if ($plugin) {
            return $collection->withPlugin((string)$plugin);
        }

        return $collection;
    }

    /**
     * Get display value for target column
     *
     * For class targets, returns FQDN. For other targets (methods, properties, etc.),
     * returns just the target name.
     *
     * @param \AttributeResolver\ValueObject\AttributeInfo $attr Attribute info
     * @return string Target display value
     */
    protected function getTargetDisplay(AttributeInfo $attr): string
    {
        // For class targets, show FQDN. For other targets, show just the name.
        if ($attr->target->type->value === 'class') {
            return $attr->className;
        }

        return $attr->target->name;
    }

    /**
     * Output attributes in text format.
     *
     * @param array<\AttributeResolver\ValueObject\AttributeInfo> $attributes List of attributes
     * @return int Exit code
     */
    protected function outputText(array $attributes): int
    {
        $this->io->out(sprintf('<info>Found %d attributes:</info>', count($attributes)));
        $this->io->out('');

        $verbose = (bool)$this->args->getOption('verbose');
        $maxLength = $verbose ? PHP_INT_MAX : 40;

        $tableData = [['Attribute', 'Class', 'Plugin', 'Type', 'Target']];
        foreach ($attributes as $attr) {
            $tableData[] = [
                $this->truncateLeft($attr->attributeName, $maxLength),
                $this->truncateLeft($attr->className, $maxLength),
                $attr->pluginName ?? '-',
                $attr->target->type->value,
                $this->truncateLeft($this->getTargetDisplay($attr), $maxLength),
            ];
        }

        $this->io->helper('Table')->output($tableData);

        return static::CODE_SUCCESS;
    }

    /**
     * Output empty result message for text format.
     *
     * @return int Exit code
     */
    protected function outputTextEmpty(): int
    {
        $this->io->warning('No attributes found matching the criteria.');

        return static::CODE_SUCCESS;
    }

    /**
     * Output attributes in JSON format.
     *
     * @param array<\AttributeResolver\ValueObject\AttributeInfo> $attributes List of attributes
     * @return int Exit code
     * @throws \JsonException
     */
    protected function outputJson(array $attributes): int
    {
        $this->io->out(json_encode($attributes, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

        return static::CODE_SUCCESS;
    }

    /**
     * Truncate string from the left if it exceeds max length
     *
     * Keeps the rightmost portion of the string and prepends '...' if truncated.
     * This is useful for long class names, especially anonymous classes, where
     * the end contains the most relevant information.
     *
     * @param string $value String to truncate
     * @param int $maxLength Maximum length before truncation
     * @return string Truncated string
     */
    protected function truncateLeft(string $value, int $maxLength = 40): string
    {
        if (strlen($value) <= $maxLength) {
            return $value;
        }

        return '...' . substr($value, -(int)($maxLength - 3));
    }
}
