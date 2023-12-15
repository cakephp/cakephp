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
 * @since         5.0.3
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\Model\Enum;

use Cake\Database\Type\EnumLabelInterface;

enum ArticleStatusLabelInterface: string implements EnumLabelInterface
{
    case PUBLISHED = 'Y';
    case UNPUBLISHED = 'N';

    /**
     * @return string
     */
    public function label(): string
    {
        return 'Is ' . strtolower($this->name);
    }
}
