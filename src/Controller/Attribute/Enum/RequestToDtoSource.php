<?php
declare(strict_types=1);

namespace Cake\Controller\Attribute\Enum;

/**
 * Source for DTO data mapping.
 */
enum RequestToDtoSource: string
{
    case Body = 'body';
    case Query = 'query';
    case Request = 'request';
    case Auto = 'auto';
}
