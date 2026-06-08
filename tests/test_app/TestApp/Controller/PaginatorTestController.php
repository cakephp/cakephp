<?php
declare(strict_types=1);

namespace TestApp\Controller;

use Cake\Controller\Controller;

class PaginatorTestController extends Controller
{
    /**
     * components property
     *
     * @var array<int|string, string|array<string, mixed>>
     */
    protected array $components = ['Paginator'];
}
