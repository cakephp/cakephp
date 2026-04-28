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

use Cake\Database\Type\Attribute\Label;
use Cake\Database\Type\EnumLabelTrait;

enum ArticleDomainStatus
{
    use EnumLabelTrait;

    #[Label('Article is published in the news domain', domain: 'news', context: 'Article')]
    case Published;

    #[Label('Article is unpublished in the news domain', domain: 'news')]
    case Unpublished;
}
