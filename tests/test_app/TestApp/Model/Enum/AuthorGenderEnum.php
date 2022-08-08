<?php
declare(strict_types=1);

namespace TestApp\Model\Enum;

enum AuthorGenderEnum: string
{
    case MALE = 'male';
    case FEMALE = 'female';
    case OTHER = 'other';
}
