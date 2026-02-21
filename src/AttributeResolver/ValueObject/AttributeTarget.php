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

use Cake\AttributeResolver\Enum\AttributeTargetType;
use Cake\AttributeResolver\Enum\DeclaringClassType;
use JsonSerializable;

/**
 * Represents information about where an attribute is attached.
 *
 * This value object encapsulates the target of an attribute, including:
 * - The type of target (class, method, property, etc.)
 * - The name of the target
 * - The declaring class (if applicable)
 * - The declaring class kind (class, interface, trait, enum)
 * - Whether the declaring class is abstract
 *
 * This class is readonly and immutable for safe serialization.
 */
readonly class AttributeTarget implements JsonSerializable
{
    /**
     * Constructor for AttributeTarget.
     *
     * @param \Cake\AttributeResolver\Enum\AttributeTargetType $type Target type
     * @param string $name Target name (e.g., method name, property name)
     * @param string|null $declaringClass Class name that declares this target
     * @param bool $isDeclaringClassAbstract Whether the declaring class is abstract
     * @param \Cake\AttributeResolver\Enum\DeclaringClassType $declaringClassType Declaring class kind
     */
    public function __construct(
        public AttributeTargetType $type,
        public string $name,
        public ?string $declaringClass = null,
        public bool $isDeclaringClassAbstract = false,
        public DeclaringClassType $declaringClassType = DeclaringClassType::CLASS_TYPE,
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
            'type' => $this->type->value,
            'name' => $this->name,
            'declaringClass' => $this->declaringClass,
            'isDeclaringClassAbstract' => $this->isDeclaringClassAbstract,
            'declaringClassType' => $this->declaringClassType->value,
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
        $type = $data['type'];
        if (!$type instanceof AttributeTargetType) {
            $type = AttributeTargetType::from((string)$type);
        }
        $declaringClassType = $data['declaringClassType'] ?? DeclaringClassType::CLASS_TYPE->value;
        if (!$declaringClassType instanceof DeclaringClassType) {
            $declaringClassType = DeclaringClassType::from((string)$declaringClassType);
        }

        return new self(
            type: $type,
            name: (string)$data['name'],
            declaringClass: isset($data['declaringClass']) ? (string)$data['declaringClass'] : null,
            isDeclaringClassAbstract: (bool)($data['isDeclaringClassAbstract'] ?? false),
            declaringClassType: $declaringClassType,
        );
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

    /**
     * Check whether the declaring type can be instantiated as a concrete class.
     *
     * @return bool
     */
    public function isInstantiableDeclaringType(): bool
    {
        return $this->declaringClassType === DeclaringClassType::CLASS_TYPE
            && $this->isDeclaringClassAbstract === false;
    }
}
