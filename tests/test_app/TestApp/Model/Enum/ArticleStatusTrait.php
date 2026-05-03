<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @since         5.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\Model\Enum;

use Cake\Database\Type\EnumLabelInterface;
use Cake\Database\Type\EnumLabelTrait;

enum ArticleStatusTrait: string implements EnumLabelInterface
{
    use EnumLabelTrait;

    case Published = 'Y';
    case Unpublished = 'N';
    case PendingReview = 'P';
}
