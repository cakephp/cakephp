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

use BackedEnum;
use Cake\Attribute\Resolver;
use Cake\Attribute\Resolver\ValueObject\AttributeInfo;
use Cake\Console\ConsoleOptionParser;
use JsonException;
use UnitEnum;

/**
 * Command to inspect detailed information about attributes.
 */
class AttributesInspectCommand extends Command
{
    /**
     * @inheritDoc
     */
    public static function defaultName(): string
    {
        return 'attributes inspect';
    }

    /**
     * @inheritDoc
     */
    public static function getDescription(): string
    {
        return 'Inspect detailed information about specific attributes.';
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
            ->addArgument('attribute', [
                'help' => 'Attribute class name to inspect (partial match supported).',
                'required' => false,
            ])
            ->addOption('class', [
                'short' => 'c',
                'help' => 'Show all attributes on a specific class (partial match supported).',
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

        $config = Resolver::getConfig($configName);
        if ($config === null) {
            $this->io->error(sprintf('Configuration "%s" does not exist.', $configName));

            return static::CODE_ERROR;
        }

        $attributeName = $this->args->getArgumentAt(0);
        $className = $this->args->getOption('class');

        if (!$attributeName && !$className) {
            $this->io->error('Please provide an attribute name or use --class option.');

            return static::CODE_ERROR;
        }

        $collection = Resolver::collection($configName);

        if ($attributeName) {
            $collection = $collection->withAttributeContains((string)$attributeName);
        } elseif ($className) {
            $collection = $collection->withClassNameContains((string)$className);
        }

        $attributes = $collection->toList();

        if ($attributes === []) {
            $this->io->error('No attributes found matching the criteria.');

            return static::CODE_ERROR;
        }

        $this->io->out(sprintf('<info>Found %d attribute(s):</info>', count($attributes)));
        $this->io->out('');

        foreach ($attributes as $index => $attr) {
            $this->displayAttributeInfo($attr, $index + 1);
            $this->io->out('');
        }

        return static::CODE_SUCCESS;
    }

    /**
     * Display detailed information for a single attribute.
     *
     * @param \Cake\Attribute\Resolver\ValueObject\AttributeInfo $attr Attribute info
     * @param int $number Result number
     * @return void
     */
    protected function displayAttributeInfo(AttributeInfo $attr, int $number): void
    {
        $shortName = $this->getShortClassName($attr->attributeName);
        $this->io->out(sprintf('<comment>%d. %s</comment>', $number, $shortName));

        $this->io->out(sprintf('   Attribute Class: <info>%s</info>', $attr->attributeName));
        $this->io->out(sprintf('   Target Class: <info>%s</info>', $attr->className));
        $this->io->out(sprintf('   Plugin: <info>%s</info>', $attr->pluginName ?? '-'));
        $this->io->out(sprintf('   Target: <info>%s (%s)</info>', $attr->target->name, $attr->target->type->value));
        $this->io->out(sprintf('   File: <info>%s:%d</info>', $attr->filePath, $attr->lineNumber));

        if ($attr->fileTime > 0) {
            $this->io->out(sprintf('   File Time: <info>%s</info>', date('Y-m-d H:i:s', $attr->fileTime)));
        }

        if ($attr->arguments !== []) {
            $this->io->out('   Arguments:');
            foreach ($attr->arguments as $key => $value) {
                $this->io->out(sprintf('     - %s: %s', $key, $this->formatValue($value)));
            }
        }
    }

    /**
     * Get short class name (without namespace).
     *
     * @param string $className Full class name
     * @return string Short class name
     */
    protected function getShortClassName(string $className): string
    {
        $parts = explode('\\', $className);

        return end($parts);
    }

    /**
     * Format a value for display.
     *
     * @param mixed $value Value to format
     * @return string Formatted value
     */
    protected function formatValue(mixed $value): string
    {
        return match (true) {
            is_array($value) => $this->formatArray($value),
            is_bool($value) => $value ? 'true' : 'false',
            is_null($value) => 'null',
            $value instanceof BackedEnum => (string)$value->value,
            $value instanceof UnitEnum => $value->name,
            is_object($value) => $value::class,
            default => (string)$value,
        };
    }

    /**
     * Format array value with safe JSON encoding.
     *
     * @param array<mixed> $value Array to format
     * @return string Formatted array string
     */
    protected function formatArray(array $value): string
    {
        try {
            return json_encode($value, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            // Fallback for arrays with unserializable values
            return '[' . implode(', ', array_map($this->formatValue(...), $value)) . ']';
        }
    }
}
