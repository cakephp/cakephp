<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Database;

use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\TypeFactory;
use Cake\Database\TypeMap;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use TestApp\Database\Type\PointType;

class SelectExpressionTypeConversionTest extends TestCase
{
    /**
     * @var \Cake\Database\Connection
     */
    protected $connection;

    public function setUp(): void
    {
        parent::setUp();

        $this->connection = ConnectionManager::get('test');
        TypeFactory::map('point', PointType::class);
    }

    public function testConversion()
    {
        $typeMap = new TypeMap([
            'pt' => 'point',
            'points__pt' => 'point',
        ]);

        $query = $this->connection->newQuery()->setSelectTypeMap($typeMap);
        $sql = $query->select(['pt'])->sql();
        $this->assertEqualsSql('SELECT (ST_AsGeoJSON(pt)) AS pt', $sql);

        $query = $this->connection->newQuery()->setSelectTypeMap($typeMap);
        $sql = $query->select(['points.pt'])->sql();
        $this->assertEqualsSql('SELECT (ST_AsGeoJSON(points.pt)) AS pt', $sql);

        $query = $this->connection->newQuery()->setSelectTypeMap($typeMap);
        $sql = $query->select(['points__pt' => 'pt'])->sql();
        $this->assertEqualsSql('SELECT (ST_AsGeoJSON(pt)) AS points__pt', $sql);

        $query = $this->connection->newQuery()->setSelectTypeMap($typeMap);
        $sql = $query->select(['points__pt' => 'unmapped_field'])->sql();
        $this->assertEqualsSql('SELECT (ST_AsGeoJSON(unmapped_field)) AS points__pt', $sql);

        $query = $this->connection->newQuery()->setSelectTypeMap($typeMap);
        $sql = $query->select(['unmapped_alias' => 'pt'])->sql();
        $this->assertEqualsSql('SELECT pt AS unmapped_alias', $sql);
    }

    public function testConversionWithExpressions()
    {
        $typeMap = new TypeMap([
            'pt' => 'point',
            'points__pt' => 'point',
        ]);

        $query = $this->connection->newQuery()->setSelectTypeMap($typeMap);
        $sql = $query->select([new IdentifierExpression('pt')])->sql();
        $this->assertEqualsSql('SELECT (ST_AsGeoJSON(pt)) AS pt', $sql);

        $query = $this->connection->newQuery()->setSelectTypeMap($typeMap);
        $sql = $query->select([new IdentifierExpression('points.pt')])->sql();
        $this->assertEqualsSql('SELECT (ST_AsGeoJSON(points.pt)) AS pt', $sql);

        $query = $this->connection->newQuery()->setSelectTypeMap($typeMap);
        $sql = $query->select(['points__pt' => new IdentifierExpression('pt')])->sql();
        $this->assertEqualsSql('SELECT (ST_AsGeoJSON(pt)) AS points__pt', $sql);

        $query = $this->connection->newQuery()->setSelectTypeMap($typeMap);
        $sql = $query->select(['points__pt' => new IdentifierExpression('unmapped_field')])->sql();
        $this->assertEqualsSql('SELECT (ST_AsGeoJSON(unmapped_field)) AS points__pt', $sql);

        $query = $this->connection->newQuery()->setSelectTypeMap($typeMap);
        $sql = $query->select(['unmapped_alias' => new IdentifierExpression('pt')])->sql();
        $this->assertEqualsSql('SELECT (pt) AS unmapped_alias', $sql);
    }

    public function testConversionWithUnsupportedExpressions()
    {
        $typeMap = new TypeMap([
            'pt' => 'point',
        ]);

        $query = $this->connection->newQuery()->setSelectTypeMap($typeMap);
        $sql = $query->select([new QueryExpression('pt')])->sql();
        $this->assertEqualsSql('SELECT (pt)', $sql);
    }

    public function testConversionWithNonExistingType()
    {
        $typeMap = new TypeMap([
            'pt' => 'nonExistingType',
        ]);

        $query = $this->connection->newQuery()->setSelectTypeMap($typeMap);
        $sql = $query->select(['pt'])->sql();
        $this->assertEqualsSql('SELECT pt', $sql);
    }

    public function testConversionWithNoMappedTypes()
    {
        $typeMap = new TypeMap();

        $query = $this->connection->newQuery()->setSelectTypeMap($typeMap);
        $sql = $query->select(['pt'])->sql();
        $this->assertEqualsSql('SELECT pt', $sql);
    }
}
