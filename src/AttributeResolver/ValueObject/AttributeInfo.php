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
namespace Cake\AttributeResolver\ValueObject;

use InvalidArgumentException;
use JsonSerializable;
use RuntimeException;

/**
 * Represents complete information about a discovered attribute.
 *
 * This value object contains all metadata about an attribute found during
 * scanning, including:
 * - The class and file where it was found
 * - The attribute class name and arguments
 * - Where it's attached (via AttributeTarget)
 * - Plugin context and file modification time for cache validation
 *
 * This class is readonly and immutable for safe serialization and caching.
 */
readonly class AttributeInfo implements JsonSerializable
{
    /**
     * Constructor for AttributeInfo.
     *
     * @param string $className Fully qualified class name containing the attribute
     * @param string $attributeName Fully qualified attribute class name
     * @param array<string, mixed> $arguments Attribute constructor arguments
     * @param string $filePath Absolute path to file where attribute was found
     * @param int $lineNumber Line number where attribute appears in file
     * @param \Cake\AttributeResolver\ValueObject\AttributeTarget $target Target information (what the attribute is attached to)
     * @param int $fileTime File modification time (Unix timestamp) for cache validation
     * @param string|null $pluginName Plugin name or null for App namespace
     */
    public function __construct(
        public string $className,
        public string $attributeName,
        public array $arguments,
        public string $filePath,
        public int $lineNumber,
        public AttributeTarget $target,
        public int $fileTime = 0,
        public ?string $pluginName = null,
    ) {
    }

    /**
     * Convert to array for serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'className' => $this->className,
            'attributeName' => $this->attributeName,
            'arguments' => $this->arguments,
            'filePath' => $this->filePath,
            'lineNumber' => $this->lineNumber,
            'target' => $this->target->toArray(),
            'fileTime' => $this->fileTime,
            'pluginName' => $this->pluginName,
        ];
    }

    /**
     * Create from array data.
     *
     * @param array<string, mixed> $data Data array
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            className: (string)$data['className'],
            attributeName: (string)$data['attributeName'],
            arguments: (array)$data['arguments'],
            filePath: (string)$data['filePath'],
            lineNumber: (int)$data['lineNumber'],
            target: AttributeTarget::fromArray((array)$data['target']),
            fileTime: (int)($data['fileTime'] ?? 0),
            pluginName: $data['pluginName'] ?? null,
        );
    }

    /**
     * Instantiate the actual attribute object.
     *
     * Returns the attribute instance with its arguments applied,
     * allowing access to attribute properties and methods.
     *
     * @template T of object
     * @param class-string<T>|null $expectedClass Optional expected class for type safety
     * @return T|object The instantiated attribute (T if expectedClass provided, otherwise object)
     * @phpstan-return ($expectedClass is null ? object : T)
     * @throws \RuntimeException If the attribute class does not exist
     * @throws \InvalidArgumentException If attribute doesn't match expected class
     */
    public function getInstance(?string $expectedClass = null): object
    {
        if (!class_exists($this->attributeName)) {
            throw new RuntimeException(sprintf(
                'Attribute class "%s" does not exist',
                $this->attributeName,
            ));
        }

        $instance = new ($this->attributeName)(...$this->arguments);

        if ($expectedClass !== null && !$instance instanceof $expectedClass) {
            throw new InvalidArgumentException(sprintf(
                'Attribute "%s" is not an instance of "%s"',
                $this->attributeName,
                $expectedClass,
            ));
        }

        return $instance;
    }

    /**
     * Check if the attribute is an instance of a given class.
     *
     * Uses is_a() with allow_string to check inheritance without instantiation.
     *
     * @param class-string $className Class name to check against
     * @return bool True if the attribute extends or implements the given class
     */
    public function isInstanceOf(string $className): bool
    {
        return is_a($this->attributeName, $className, true);
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
