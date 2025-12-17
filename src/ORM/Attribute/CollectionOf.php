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
 * @since         5.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM\Attribute;

use Attribute;

/**
 * Attribute to specify the DTO class for array collections.
 *
 * Since PHP doesn't have runtime generics, this attribute tells the DtoMapper
 * what type of DTO to create for each element in an array.
 *
 * ### Example
 *
 * ```php
 * readonly class ArticleDto {
 *     public function __construct(
 *         public int $id,
 *         public string $title,
 *         #[CollectionOf(CommentDto::class)]
 *         public array $comments = [],
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class CollectionOf
{
    /**
     * @param class-string $class The DTO class for collection elements
     */
    public function __construct(
        public string $class,
    ) {
    }
}
