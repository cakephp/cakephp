<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Database;

use Cake\Database\Driver\Mysql;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\TypeFactory;
use Cake\Database\TypeMap;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use TestApp\Database\Point;
use TestApp\Database\Type\PointType;

class SelectExpressionTypeIntegrationTest extends TestCase
{
    protected $fixtures = [
        'core.Points',
    ];

    public $autoFixtures = false;

    /**
     * @var \Cake\Database\Connection
     */
    protected $connection;

    public function setUp(): void
    {
        parent::setUp();
        static::setAppNamespace();

        $this->connection = ConnectionManager::get('test');
        $this->skipIf(!($this->connection->getDriver() instanceof Mysql), 'MySQL only for now.');
        $this->skipIf(
            version_compare($this->connection->getDriver()->version(), '5.7.0', '<'),
            'MySQL 5.7+ only for now.'
        );

        TypeFactory::map('point', PointType::class);

        $this->loadFixtures('Points');
    }

    public function testRead()
    {
        $typeMap = new TypeMap([
            'pt' => 'point',
        ]);
        $results = $this->connection
            ->newQuery()
            ->select(['pt'])
            ->from('points')
            ->setSelectTypeMap($typeMap)
            ->execute()
            ->fetchAll('assoc');
        $this->assertEquals([['pt' => new Point(10, 20)]], $results);

        $typeMap = new TypeMap([
            'pt' => 'point',
        ]);
        $results = $this->connection
            ->newQuery()
            ->select(['points.pt'])
            ->from('points')
            ->setSelectTypeMap($typeMap)
            ->execute()
            ->fetchAll('assoc');
        $this->assertEquals([['pt' => new Point(10, 20)]], $results);

        $typeMap = new TypeMap([
            'points__pt' => 'point',
        ]);
        $results = $this->connection
            ->newQuery()
            ->select(['points__pt' => 'pt'])
            ->from('points')
            ->setSelectTypeMap($typeMap)
            ->execute()
            ->fetchAll('assoc');
        $this->assertEquals([['points__pt' => new Point(10, 20)]], $results);
    }

    public function testReadWithExpressions()
    {
        $typeMap = new TypeMap([
            'pt' => 'point',
        ]);
        $results = $this->connection
            ->newQuery()
            ->select([new IdentifierExpression('pt')])
            ->from('points')
            ->setSelectTypeMap($typeMap)
            ->execute()
            ->fetchAll('assoc');
        $this->assertEquals([['pt' => new Point(10, 20)]], $results);

        $typeMap = new TypeMap([
            'pt' => 'point',
        ]);
        $results = $this->connection
            ->newQuery()
            ->select([new IdentifierExpression('points.pt')])
            ->from('points')
            ->setSelectTypeMap($typeMap)
            ->execute()
            ->fetchAll('assoc');
        $this->assertEquals([['pt' => new Point(10, 20)]], $results);

        $typeMap = new TypeMap([
            'points__pt' => 'point',
        ]);
        $results = $this->connection
            ->newQuery()
            ->select(['points__pt' => new IdentifierExpression('pt')])
            ->from('points')
            ->setSelectTypeMap($typeMap)
            ->execute()
            ->fetchAll('assoc');
        $this->assertEquals([['points__pt' => new Point(10, 20)]], $results);
    }

    public function testReadWithUnsupportedExpressions()
    {
        $typeMap = new TypeMap([
            'pt' => 'point',
        ]);

        $results = $this->connection
            ->newQuery()
            ->select([new QueryExpression('pt')])
            ->from('points')
            ->setSelectTypeMap($typeMap)
            ->execute()
            ->fetchAll('assoc');
        $this->assertEquals([['pt' => null]], $results);
    }

    public function testReadWithNonExistingType()
    {
        $typeMap = new TypeMap([
            'pt' => 'nonExistingType',
        ]);

        $results = $this->connection
            ->newQuery()
            ->select(['pt'])
            ->from('points')
            ->setSelectTypeMap($typeMap)
            ->execute()
            ->fetchAll('assoc');
        $this->assertEquals([['pt' => hex2bin('00000000010100000000000000000024400000000000003440')]], $results);
    }

    public function testReadWithNoMappedTypes()
    {
        $typeMap = new TypeMap();

        $results = $this->connection
            ->newQuery()
            ->select(['pt'])
            ->from('points')
            ->setSelectTypeMap($typeMap)
            ->execute()
            ->fetchAll('assoc');
        $this->assertEquals([['pt' => hex2bin('00000000010100000000000000000024400000000000003440')]], $results);
    }
}
