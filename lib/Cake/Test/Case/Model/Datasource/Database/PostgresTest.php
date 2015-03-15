<?php
/**
 * DboPostgresTest file
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.Case.Model.Datasource.Database
 * @since         CakePHP(tm) v 1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Model', 'Model');
App::uses('AppModel', 'Model');
App::uses('Postgres', 'Model/Datasource/Database');

require_once dirname(dirname(dirname(__FILE__))) . DS . 'models.php';

/**
 * DboPostgresTestDb class
 *
 * @package       Cake.Test.Case.Model.Datasource.Database
 */
class DboPostgresTestDb extends Postgres {

/**
 * simulated property
 *
 * @var array
 */
	public $simulated = array();

/**
 * execute method
 *
 * @param mixed $sql
 * @return void
 */
	protected function _execute($sql, $params = array(), $prepareOptions = array()) {
		$this->simulated[] = $sql;
		return null;
	}

/**
 * getLastQuery method
 *
 * @return void
 */
	public function getLastQuery() {
		return $this->simulated[count($this->simulated) - 1];
	}

}

/**
 * PostgresTestModel class
 *
 * @package       Cake.Test.Case.Model.Datasource.Database
 */
class PostgresTestModel extends Model {

/**
 * useTable property
 *
 * @var bool
 */
	public $useTable = false;

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array(
		'PostgresClientTestModel' => array(
			'foreignKey' => 'client_id'
		)
	);

/**
 * find method
 *
 * @param mixed $conditions
 * @param mixed $fields
 * @param mixed $order
 * @param mixed $recursive
 * @return void
 */
	public function find($conditions = null, $fields = null, $order = null, $recursive = null) {
		return $conditions;
	}

/**
 * findAll method
 *
 * @param mixed $conditions
 * @param mixed $fields
 * @param mixed $order
 * @param mixed $recursive
 * @return void
 */
	public function findAll($conditions = null, $fields = null, $order = null, $recursive = null) {
		return $conditions;
	}

/**
 * schema method
 *
 * @return void
 */
	public function schema($field = false) {
		return array(
			'id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
			'client_id' => array('type' => 'integer', 'null' => '', 'default' => '0', 'length' => '11'),
			'name' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
			'login' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
			'passwd' => array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '255'),
			'addr_1' => array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '255'),
			'addr_2' => array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '25'),
			'zip_code' => array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
			'city' => array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
			'country' => array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
			'phone' => array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
			'fax' => array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
			'url' => array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '255'),
			'email' => array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
			'comments' => array('type' => 'text', 'null' => '1', 'default' => '', 'length' => ''),
			'last_login' => array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => ''),
			'created' => array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
			'updated' => array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
		);
	}

}

/**
 * PostgresClientTestModel class
 *
 * @package       Cake.Test.Case.Model.Datasource.Database
 */
class PostgresClientTestModel extends Model {

/**
 * useTable property
 *
 * @var bool
 */
	public $useTable = false;

/**
 * schema method
 *
 * @return void
 */
	public function schema($field = false) {
		return array(
			'id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8', 'key' => 'primary'),
			'name' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
			'email' => array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
			'created' => array('type' => 'datetime', 'null' => true, 'default' => null, 'length' => ''),
			'updated' => array('type' => 'datetime', 'null' => true, 'default' => null, 'length' => null)
		);
	}

}

/**
 * PostgresTest class
 *
 * @package       Cake.Test.Case.Model.Datasource.Database
 */
class PostgresTest extends CakeTestCase {

/**
 * Do not automatically load fixtures for each test, they will be loaded manually
 * using CakeTestCase::loadFixtures
 *
 * @var bool
 */
	public $autoFixtures = false;

/**
 * Fixtures
 *
 * @var object
 */
	public $fixtures = array('core.user', 'core.binary_test', 'core.comment', 'core.article',
		'core.tag', 'core.articles_tag', 'core.attachment', 'core.person', 'core.post', 'core.author',
		'core.datatype',
	);

/**
 * Actual DB connection used in testing
 *
 * @var DboSource
 */
	public $Dbo = null;

/**
 * Simulated DB connection used in testing
 *
 * @var DboSource
 */
	public $Dbo2 = null;

/**
 * Sets up a Dbo class instance for testing
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		Configure::write('Cache.disable', true);
		$this->Dbo = ConnectionManager::getDataSource('test');
		$this->skipIf(!($this->Dbo instanceof Postgres));
		$this->Dbo2 = new DboPostgresTestDb($this->Dbo->config, false);
		$this->model = new PostgresTestModel();
	}

/**
 * Sets up a Dbo class instance for testing
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		Configure::write('Cache.disable', false);
		unset($this->Dbo2);
	}

/**
 * Test field quoting method
 *
 * @return void
 */
	public function testFieldQuoting() {
		$fields = array(
			'"PostgresTestModel"."id" AS "PostgresTestModel__id"',
			'"PostgresTestModel"."client_id" AS "PostgresTestModel__client_id"',
			'"PostgresTestModel"."name" AS "PostgresTestModel__name"',
			'"PostgresTestModel"."login" AS "PostgresTestModel__login"',
			'"PostgresTestModel"."passwd" AS "PostgresTestModel__passwd"',
			'"PostgresTestModel"."addr_1" AS "PostgresTestModel__addr_1"',
			'"PostgresTestModel"."addr_2" AS "PostgresTestModel__addr_2"',
			'"PostgresTestModel"."zip_code" AS "PostgresTestModel__zip_code"',
			'"PostgresTestModel"."city" AS "PostgresTestModel__city"',
			'"PostgresTestModel"."country" AS "PostgresTestModel__country"',
			'"PostgresTestModel"."phone" AS "PostgresTestModel__phone"',
			'"PostgresTestModel"."fax" AS "PostgresTestModel__fax"',
			'"PostgresTestModel"."url" AS "PostgresTestModel__url"',
			'"PostgresTestModel"."email" AS "PostgresTestModel__email"',
			'"PostgresTestModel"."comments" AS "PostgresTestModel__comments"',
			'"PostgresTestModel"."last_login" AS "PostgresTestModel__last_login"',
			'"PostgresTestModel"."created" AS "PostgresTestModel__created"',
			'"PostgresTestModel"."updated" AS "PostgresTestModel__updated"'
		);

		$result = $this->Dbo->fields($this->model);
		$expected = $fields;
		$this->assertEquals($expected, $result);

		$result = $this->Dbo->fields($this->model, null, 'PostgresTestModel.*');
		$expected = $fields;
		$this->assertEquals($expected, $result);

		$result = $this->Dbo->fields($this->model, null, array('*', 'AnotherModel.id', 'AnotherModel.name'));
		$expected = array_merge($fields, array(
			'"AnotherModel"."id" AS "AnotherModel__id"',
			'"AnotherModel"."name" AS "AnotherModel__name"'));
		$this->assertEquals($expected, $result);

		$result = $this->Dbo->fields($this->model, null, array('*', 'PostgresClientTestModel.*'));
		$expected = array_merge($fields, array(
			'"PostgresClientTestModel"."id" AS "PostgresClientTestModel__id"',
			'"PostgresClientTestModel"."name" AS "PostgresClientTestModel__name"',
			'"PostgresClientTestModel"."email" AS "PostgresClientTestModel__email"',
			'"PostgresClientTestModel"."created" AS "PostgresClientTestModel__created"',
			'"PostgresClientTestModel"."updated" AS "PostgresClientTestModel__updated"'));
		$this->assertEquals($expected, $result);
	}

/**
 * testColumnParsing method
 *
 * @return void
 */
	public function testColumnParsing() {
		$this->assertEquals('text', $this->Dbo2->column('text'));
		$this->assertEquals('date', $this->Dbo2->column('date'));
		$this->assertEquals('boolean', $this->Dbo2->column('boolean'));
		$this->assertEquals('string', $this->Dbo2->column('character varying'));
		$this->assertEquals('time', $this->Dbo2->column('time without time zone'));
		$this->assertEquals('datetime', $this->Dbo2->column('timestamp without time zone'));
		$this->assertEquals('decimal', $this->Dbo2->column('decimal'));
		$this->assertEquals('decimal', $this->Dbo2->column('numeric'));
		$this->assertEquals('float', $this->Dbo2->column('float'));
		$this->assertEquals('float', $this->Dbo2->column('double precision'));

		$result = $this->Dbo2->column('bigint');
		$expected = 'biginteger';
		$this->assertEquals($expected, $result);
	}

/**
 * testValueQuoting method
 *
 * @return void
 */
	public function testValueQuoting() {
		$this->assertEquals("1.200000", $this->Dbo->value(1.2, 'float'));
		$this->assertEquals("'1,2'", $this->Dbo->value('1,2', 'float'));

		$this->assertEquals("0", $this->Dbo->value('0', 'integer'));
		$this->assertEquals('NULL', $this->Dbo->value('', 'integer'));
		$this->assertEquals('NULL', $this->Dbo->value('', 'float'));
		$this->assertEquals("NULL", $this->Dbo->value('', 'integer', false));
		$this->assertEquals("NULL", $this->Dbo->value('', 'float', false));
		$this->assertEquals("'0.0'", $this->Dbo->value('0.0', 'float'));

		$this->assertEquals("'TRUE'", $this->Dbo->value('t', 'boolean'));
		$this->assertEquals("'FALSE'", $this->Dbo->value('f', 'boolean'));
		$this->assertEquals("'TRUE'", $this->Dbo->value(true));
		$this->assertEquals("'FALSE'", $this->Dbo->value(false));
		$this->assertEquals("'t'", $this->Dbo->value('t'));
		$this->assertEquals("'f'", $this->Dbo->value('f'));
		$this->assertEquals("'TRUE'", $this->Dbo->value('true', 'boolean'));
		$this->assertEquals("'FALSE'", $this->Dbo->value('false', 'boolean'));
		$this->assertEquals("'FALSE'", $this->Dbo->value('', 'boolean'));
		$this->assertEquals("'FALSE'", $this->Dbo->value(0, 'boolean'));
		$this->assertEquals("'TRUE'", $this->Dbo->value(1, 'boolean'));
		$this->assertEquals("'TRUE'", $this->Dbo->value('1', 'boolean'));
		$this->assertEquals("NULL", $this->Dbo->value(null, 'boolean'));
		$this->assertEquals("NULL", $this->Dbo->value(array()));
	}

/**
 * test that localized floats don't cause trouble.
 *
 * @return void
 */
	public function testLocalizedFloats() {
		$restore = setlocale(LC_NUMERIC, 0);

		$this->skipIf(setlocale(LC_NUMERIC, 'de_DE') === false, "The German locale isn't available.");

		$result = $this->db->value(3.141593, 'float');
		$this->assertEquals("3.141593", $result);

		$result = $this->db->value(3.14);
		$this->assertEquals("3.140000", $result);

		setlocale(LC_NUMERIC, $restore);
	}

/**
 * test that date and time columns do not generate errors with null and nullish values.
 *
 * @return void
 */
	public function testDateAndTimeAsNull() {
		$this->assertEquals('NULL', $this->Dbo->value(null, 'date'));
		$this->assertEquals('NULL', $this->Dbo->value('', 'date'));

		$this->assertEquals('NULL', $this->Dbo->value('', 'datetime'));
		$this->assertEquals('NULL', $this->Dbo->value(null, 'datetime'));

		$this->assertEquals('NULL', $this->Dbo->value('', 'timestamp'));
		$this->assertEquals('NULL', $this->Dbo->value(null, 'timestamp'));

		$this->assertEquals('NULL', $this->Dbo->value('', 'time'));
		$this->assertEquals('NULL', $this->Dbo->value(null, 'time'));
	}

/**
 * Tests that different Postgres boolean 'flavors' are properly returned as native PHP booleans
 *
 * @return void
 */
	public function testBooleanNormalization() {
		$this->assertEquals(true, $this->Dbo2->boolean('t', false));
		$this->assertEquals(true, $this->Dbo2->boolean('true', false));
		$this->assertEquals(true, $this->Dbo2->boolean('TRUE', false));
		$this->assertEquals(true, $this->Dbo2->boolean(true, false));
		$this->assertEquals(true, $this->Dbo2->boolean(1, false));
		$this->assertEquals(true, $this->Dbo2->boolean(" ", false));

		$this->assertEquals(false, $this->Dbo2->boolean('f', false));
		$this->assertEquals(false, $this->Dbo2->boolean('false', false));
		$this->assertEquals(false, $this->Dbo2->boolean('FALSE', false));
		$this->assertEquals(false, $this->Dbo2->boolean(false, false));
		$this->assertEquals(false, $this->Dbo2->boolean(0, false));
		$this->assertEquals(false, $this->Dbo2->boolean('', false));
	}

/**
 * test that default -> false in schemas works correctly.
 *
 * @return void
 */
	public function testBooleanDefaultFalseInSchema() {
		$this->loadFixtures('Datatype');

		$model = new Model(array('name' => 'Datatype', 'table' => 'datatypes', 'ds' => 'test'));
		$model->create();
		$this->assertSame(false, $model->data['Datatype']['bool']);
	}

/**
 * testLastInsertIdMultipleInsert method
 *
 * @return void
 */
	public function testLastInsertIdMultipleInsert() {
		$this->loadFixtures('User');
		$db1 = ConnectionManager::getDataSource('test');

		$table = $db1->fullTableName('users', false);
		$password = '5f4dcc3b5aa765d61d8327deb882cf99';
		$db1->execute(
			"INSERT INTO {$table} (\"user\", password) VALUES ('mariano', '{$password}')"
		);

		$this->assertEquals(5, $db1->lastInsertId($table));

		$db1->execute("INSERT INTO {$table} (\"user\", password) VALUES ('hoge', '{$password}')");
		$this->assertEquals(6, $db1->lastInsertId($table));
	}

/**
 * Tests that column types without default lengths in $columns do not have length values
 * applied when generating schemas.
 *
 * @return void
 */
	public function testColumnUseLength() {
		$result = array('name' => 'foo', 'type' => 'string', 'length' => 100, 'default' => 'FOO');
		$expected = '"foo" varchar(100) DEFAULT \'FOO\'';
		$this->assertEquals($expected, $this->Dbo->buildColumn($result));

		$result = array('name' => 'foo', 'type' => 'text', 'length' => 100, 'default' => 'FOO');
		$expected = '"foo" text DEFAULT \'FOO\'';
		$this->assertEquals($expected, $this->Dbo->buildColumn($result));
	}

/**
 * Tests that binary data is escaped/unescaped properly on reads and writes
 *
 * @return void
 */
	public function testBinaryDataIntegrity() {
		$this->loadFixtures('BinaryTest');
		$data = '%PDF-1.3
		%ƒÂÚÂÎßÛ†–ƒ∆
		4 0 obj
		<< /Length 5 0 R /Filter /FlateDecode >>
		stream
		xµYMì€∆Ω„WÃ%)nï0¯îâ-«é]Q"πXµáÿ•Ip	-	P V,]Ú#c˚ˇ‰ut¥†∏Ti9 Ü=”›Ø_˜4>à∑‚Épcé¢Pxæ®2q\'
		1UªbUáˇ’+ö«√[ıµ⁄ão"R∑"HiGæä€(å≠≈^Ãøsm?YlƒÃõªﬁ‹âEÚB&‚Î◊7bÒ^¸m°÷˛?2±Øs“ﬁu#®U√ˇú÷g¥C;ä")n})JºIÔ3ËSnÑÎ¥≤ıD∆¢∂Msx1üèG˚±Œ™⁄>¶ySïufØ ˝¸?UπÃã√6ﬂÌÚC=øK?˝…s
		˛§¯ˇ:-˜ò7€ÓFæ∂∑Õ˛∆“V’>ılﬂëÅd«ÜQdI›ÎB%W¿ΩıÉn~hvêCS>«é˛(ØôK!€¡zB!√
		[œÜ"ûß ·iH¸[Ã€ºæ∑¯¡L,ÀÚAlS∫ˆ=∫Œ≤cÄr&ˆÈ:√ÿ£˚È«4ﬂ•À]vc›bÅôÿî=siXe4/¡p]ã]ôÆIœ™ Ωﬂà_ƒ‚G?«7	ùÿ ı¯K4ïIpV◊÷·\'éµóªÚæ>î
		;›sú!2ﬂ¬F•/f∑j£
		dw"IÊÜπ<ôÿˆ%IG1ytÛDﬂXg|Éòa§˜}C˛¿ÿe°G´Ú±jÍm~¿/∂hã<#-¥•ıùe87€t˜õ6w}´{æ
		m‹ê–	∆¡ 6⁄\
		rAÀBùZ3aË‚r$G·$ó0ÑüâUY4È™¡%C∑Ÿ2rc<Iõ-cï.
		[ŒöâFA†É‡+QglMÉîÉÄúÌ|¸»#x7¥«MgVÎ-GGÚ• I?Á‘”Lzw∞pHÅ¯◊nefqCî.nÕeè∆ÿÛy¡˙fb≤üŒHÜAëÕNq=´@	’cQdÖúAÉIqñŸ˘+2&∏  Àù.gÅ‚ƒœ3EPƒOi—‰:>ÍCäı
		=Õec=ëR˝”eñ=<V$ì˙+x+¢ïÒÕ<àeWå»–˚∫Õd§&£àf ]fPA´âtënöå∏◊ó„Ë@∆≠K´÷˘}a_CI˚©yòHg,ôSSVìBƒl4 L.ÈY…á,2∂íäÙ.$ó¸CäŸ*€óy
		π?G,_√·ÆÎç=^Vkvo±ó{§ƒ2»±¨Ïüo»ëD-ãé ﬁó¥cVÙ\'™G~\'p¢%* ã˚÷
		ªºnh˚ºO^∏…®[Ó“‚ÅfıÌ≥∫F!Eœ(π∑T6`¬tΩÆ0ì»rTÎ`»Ñ«
		]≈åp˝)=¿Ô0∆öVÂmˇˆ„ø~¯ÁÔ∏b*fc»‡Îı„Ú}∆tœs∂Y∫ÜaÆ˙X∏~<ÿ·Ùvé1‹p¿TD∆ÔîÄ“úhˆ*Ú€îe)K–p¨ÚJ3Ÿ∞ã>ÊuNê°“√Ü ‹Ê9iÙ0˙AAEÍ ˙`∂£\'ûce•åƒX›ŸÁ´1SK{qdá"tÏ[wQ#SµBe∞∑µó…ÌV`B"Ñ≥„!è_ÓÏ†-º*ºú¿Ë0ˆeê∂´ë+HFj…‡zvHÓN|ÔL÷ûñ3õÜ$z%sá…pÎóV38âs	Çoµ•ß3†<9B·¨û~¢3)ÂxóÿÁCÕòÆ∫Í=»ÿSπS;∆~±êÆTEp∑óÈ÷ÀuìDHÈ$ÉõæÜjÃ»§"≤ÃONM®RËíRr{õS	∏Ê™op±W;ÂUÔ P∫kÔˇﬂTæ∑óﬂË”ÆC©Ô[≥◊HÁ˚¨hê"ÆbF?ú%h˙ˇ4xèÕ(ó2ÙáíM])Ñd|=fë-cI0ñL¢kÖêk‰Rƒ«ıÄWñ8mO3∏&√æËX¯Hó—ì]yF2»–˜ádàà‡‹ÇÎ¿„≥7mªHAS∑¶.;Œx(1} _kd©.ﬁdç48M\'àáªCp^Krí<É‰XÓıïl!Ì$N<ı∞B»G]…∂Ó¯>˛ÔbõÒπÀ•:ôO<j∂™œ%âÏ—>@È$pÖu‹Ê´-QqV ?V≥JÆÍqÛX8(lπï@zgÖ}Fe<ˇ‡Sñ“ÿ˜ê?6‡L∫Oß~µ –?ËeäÚ®YîÕ=Ü=¢DÁu*GvBk;)L¬N«î:flö∂≠ÇΩq„Ñmí•˜Ë∂‚"û≥§:±≤i^ΩÑ!)WıyÅ§ô á„RÄ÷Òôc’≠—s™rı‚Pdêãh˘ßHVç5ﬁﬁÈF€çÌÛuçÖ/M=gëµ±ÿGû1coÔuñæ‘z®. õ∑7ÉÏÜÆ,°’H†ÍÉÌ∂7e	º® íˆ⁄◊øNWK”ÂYµ‚ñé;µ¶gV-ﬂ>µtË¥áßN2 ¯¶BaP-)eW.àôt^∏1›C∑Ö?L„&”5’4jvã–ªZ	÷+4% ´0l…»ú^°´© ûiπ∑é®óÜ±Òÿ‰ïˆÌ–dˆ◊Æ19rQ=Í|ı•rMæ¬;ò‰Y‰é9.”‹˝V«ã¯∏,+ë®j*¡·/';

		$model = new AppModel(array('name' => 'BinaryTest', 'ds' => 'test'));
		$model->save(compact('data'));

		$result = $model->find('first');
		$this->assertEquals($data, $result['BinaryTest']['data']);
	}

/**
 * Tests passing PostgreSQL regular expression operators when building queries
 *
 * @return void
 */
	public function testRegexpOperatorConditionsParsing() {
		$this->assertSame(' WHERE "name" ~ \'[a-z_]+\'', $this->Dbo->conditions(array('name ~' => '[a-z_]+')));
		$this->assertSame(' WHERE "name" ~* \'[a-z_]+\'', $this->Dbo->conditions(array('name ~*' => '[a-z_]+')));
		$this->assertSame(' WHERE "name" !~ \'[a-z_]+\'', $this->Dbo->conditions(array('name !~' => '[a-z_]+')));
		$this->assertSame(' WHERE "name" !~* \'[a-z_]+\'', $this->Dbo->conditions(array('name !~*' => '[a-z_]+')));
	}

/**
 * Tests the syntax of generated schema indexes
 *
 * @return void
 */
	public function testSchemaIndexSyntax() {
		$schema = new CakeSchema();
		$schema->tables = array('i18n' => array(
			'id' => array(
				'type' => 'integer', 'null' => false, 'default' => null,
				'length' => 10, 'key' => 'primary'
			),
			'locale' => array('type' => 'string', 'null' => false, 'length' => 6, 'key' => 'index'),
			'model' => array('type' => 'string', 'null' => false, 'key' => 'index'),
			'foreign_key' => array(
				'type' => 'integer', 'null' => false, 'length' => 10, 'key' => 'index'
			),
			'field' => array('type' => 'string', 'null' => false, 'key' => 'index'),
			'content' => array('type' => 'text', 'null' => true, 'default' => null),
			'indexes' => array(
				'PRIMARY' => array('column' => 'id', 'unique' => 1),
				'locale' => array('column' => 'locale', 'unique' => 0),
				'model' => array('column' => 'model', 'unique' => 0),
				'row_id' => array('column' => 'foreign_key', 'unique' => 0),
				'field' => array('column' => 'field', 'unique' => 0)
			)
		));

		$result = $this->Dbo->createSchema($schema);
		$this->assertNotRegExp('/^CREATE INDEX(.+);,$/', $result);
	}

/**
 * testCakeSchema method
 *
 * Test that schema generated postgresql queries are valid. ref #5696
 * Check that the create statement for a schema generated table is the same as the original sql
 *
 * @return void
 */
	public function testCakeSchema() {
		$db1 = ConnectionManager::getDataSource('test');
		$db1->cacheSources = false;

		$db1->rawQuery('CREATE TABLE ' . $db1->fullTableName('datatype_tests') . ' (
			id serial NOT NULL,
			"varchar" character varying(40) NOT NULL,
			"full_length" character varying NOT NULL,
			"huge_int" bigint NOT NULL,
			"timestamp" timestamp without time zone,
			"date" date,
			CONSTRAINT test_data_types_pkey PRIMARY KEY (id)
		)');

		$schema = new CakeSchema(array('connection' => 'test'));
		$result = $schema->read(array(
			'connection' => 'test',
			'models' => array('DatatypeTest')
		));

		$schema->tables = array(
			'datatype_tests' => $result['tables']['missing']['datatype_tests']
		);
		$result = $db1->createSchema($schema, 'datatype_tests');

		$this->assertNotRegExp('/timestamp DEFAULT/', $result);
		$this->assertRegExp('/\"full_length\"\s*text\s.*,/', $result);
		$this->assertContains('timestamp ,', $result);
		$this->assertContains('"huge_int" bigint NOT NULL,', $result);

		$db1->query('DROP TABLE ' . $db1->fullTableName('datatype_tests'));

		$db1->query($result);
		$result2 = $schema->read(array(
			'connection' => 'test',
			'models' => array('DatatypeTest')
		));
		$schema->tables = array('datatype_tests' => $result2['tables']['missing']['datatype_tests']);
		$result2 = $db1->createSchema($schema, 'datatype_tests');
		$this->assertEquals($result, $result2);

		$db1->query('DROP TABLE ' . $db1->fullTableName('datatype_tests'));
	}

/**
 * testCakeSchemaBegserial method
 *
 * Test that schema generated postgresql queries are valid.
 *
 * @return void
 */
	public function testCakeSchemaBigserial() {
		$db1 = ConnectionManager::getDataSource('test');
		$db1->cacheSources = false;

		$db1->rawQuery('CREATE TABLE ' . $db1->fullTableName('bigserial_tests') . ' (
			"id" bigserial NOT NULL,
			"varchar" character varying(40) NOT NULL,
			PRIMARY KEY ("id")
		)');

		$schema = new CakeSchema(array('connection' => 'test'));
		$result = $schema->read(array(
			'connection' => 'test',
			'models' => array('BigserialTest')
		));
		$schema->tables = array(
			'bigserial_tests' => $result['tables']['missing']['bigserial_tests']
		);
		$result = $db1->createSchema($schema, 'bigserial_tests');

		$this->assertContains('"id" bigserial NOT NULL,', $result);

		$db1->query('DROP TABLE ' . $db1->fullTableName('bigserial_tests'));
	}

/**
 * Test index generation from table info.
 *
 * @return void
 */
	public function testIndexGeneration() {
		$name = $this->Dbo->fullTableName('index_test', false, false);
		$this->Dbo->query('CREATE TABLE ' . $name . ' ("id" serial NOT NULL PRIMARY KEY, "bool" integer, "small_char" varchar(50), "description" varchar(40) )');
		$this->Dbo->query('CREATE INDEX pointless_bool ON ' . $name . '("bool")');
		$this->Dbo->query('CREATE UNIQUE INDEX char_index ON ' . $name . '("small_char")');
		$expected = array(
			'PRIMARY' => array('unique' => true, 'column' => 'id'),
			'pointless_bool' => array('unique' => false, 'column' => 'bool'),
			'char_index' => array('unique' => true, 'column' => 'small_char'),
		);
		$result = $this->Dbo->index($name);
		$this->Dbo->query('DROP TABLE ' . $name);
		$this->assertEquals($expected, $result);

		$name = $this->Dbo->fullTableName('index_test_2', false, false);
		$this->Dbo->query('CREATE TABLE ' . $name . ' ("id" serial NOT NULL PRIMARY KEY, "bool" integer, "small_char" varchar(50), "description" varchar(40) )');
		$this->Dbo->query('CREATE UNIQUE INDEX multi_col ON ' . $name . '("small_char", "bool")');
		$expected = array(
			'PRIMARY' => array('unique' => true, 'column' => 'id'),
			'multi_col' => array('unique' => true, 'column' => array('small_char', 'bool')),
		);
		$result = $this->Dbo->index($name);
		$this->Dbo->query('DROP TABLE ' . $name);
		$this->assertEquals($expected, $result);
	}

/**
 * Test the alterSchema capabilities of postgres
 *
 * @return void
 */
	public function testAlterSchema() {
		$Old = new CakeSchema(array(
			'connection' => 'test',
			'name' => 'AlterPosts',
			'alter_posts' => array(
				'id' => array('type' => 'integer', 'key' => 'primary'),
				'author_id' => array('type' => 'integer', 'null' => false),
				'title' => array('type' => 'string', 'null' => true),
				'body' => array('type' => 'text'),
				'published' => array('type' => 'string', 'length' => 1, 'default' => 'N'),
				'created' => array('type' => 'datetime'),
				'updated' => array('type' => 'datetime'),
			)
		));
		$this->Dbo->query($this->Dbo->createSchema($Old));

		$New = new CakeSchema(array(
			'connection' => 'test',
			'name' => 'AlterPosts',
			'alter_posts' => array(
				'id' => array('type' => 'integer', 'key' => 'primary'),
				'author_id' => array('type' => 'integer', 'null' => true),
				'title' => array('type' => 'string', 'null' => false, 'default' => 'my title'),
				'body' => array('type' => 'string', 'length' => 500),
				'status' => array('type' => 'integer', 'length' => 3, 'default' => 1),
				'created' => array('type' => 'datetime'),
				'updated' => array('type' => 'datetime'),
			)
		));
		$this->Dbo->query($this->Dbo->alterSchema($New->compare($Old), 'alter_posts'));

		$model = new CakeTestModel(array('table' => 'alter_posts', 'ds' => 'test'));
		$result = $model->schema();
		$this->assertTrue(isset($result['status']));
		$this->assertFalse(isset($result['published']));
		$this->assertEquals('string', $result['body']['type']);
		$this->assertEquals(1, $result['status']['default']);
		$this->assertEquals(true, $result['author_id']['null']);
		$this->assertEquals(false, $result['title']['null']);

		$this->Dbo->query($this->Dbo->dropSchema($New));

		$New = new CakeSchema(array(
			'connection' => 'test_suite',
			'name' => 'AlterPosts',
			'alter_posts' => array(
				'id' => array('type' => 'string', 'length' => 36, 'key' => 'primary'),
				'author_id' => array('type' => 'integer', 'null' => false),
				'title' => array('type' => 'string', 'null' => true),
				'body' => array('type' => 'text'),
				'published' => array('type' => 'string', 'length' => 1, 'default' => 'N'),
				'created' => array('type' => 'datetime'),
				'updated' => array('type' => 'datetime'),
			)
		));
		$result = $this->Dbo->alterSchema($New->compare($Old), 'alter_posts');
		$this->assertNotRegExp('/varchar\(36\) NOT NULL/i', $result);
	}

/**
 * Test the alterSchema changing boolean to integer
 *
 * @return void
 */
	public function testAlterSchemaBooleanToIntegerField() {
		$default = array(
			'connection' => 'test',
			'name' => 'BoolField',
			'bool_fields' => array(
				'id' => array('type' => 'integer', 'key' => 'primary'),
				'name' => array('type' => 'string', 'length' => 50),
				'active' => array('type' => 'boolean', 'null' => false),
			)
		);
		$Old = new CakeSchema($default);
		$result = $this->Dbo->query($this->Dbo->createSchema($Old));
		$this->assertTrue($result);

		$modified = $default;
		$modified['bool_fields']['active'] = array('type' => 'integer', 'null' => true);

		$New = new CakeSchema($modified);
		$query = $this->Dbo->alterSchema($New->compare($Old));
		$result = $this->Dbo->query($query);
		$this->Dbo->query($this->Dbo->dropSchema($Old));
	}

/**
 * Test the alterSchema changing text to integer
 *
 * @return void
 */
	public function testAlterSchemaTextToIntegerField() {
		$default = array(
			'connection' => 'test',
			'name' => 'TextField',
			'text_fields' => array(
				'id' => array('type' => 'integer', 'key' => 'primary'),
				'name' => array('type' => 'string', 'length' => 50),
				'active' => array('type' => 'text', 'null' => false),
			)
		);
		$Old = new CakeSchema($default);
		$result = $this->Dbo->query($this->Dbo->createSchema($Old));
		$this->assertTrue($result);

		$modified = $default;
		$modified['text_fields']['active'] = array('type' => 'integer', 'null' => true);

		$New = new CakeSchema($modified);
		$this->Dbo->query($this->Dbo->alterSchema($New->compare($Old)));
		$result = $this->Dbo->describe('text_fields');

		$this->Dbo->query($this->Dbo->dropSchema($Old));
		$expected = array(
			'type' => 'integer',
			'null' => true,
			'default' => null,
			'length' => null,
		);
		$this->assertEquals($expected, $result['active']);
	}

/**
 * Test the alter index capabilities of postgres
 *
 * @return void
 */
	public function testAlterIndexes() {
		$this->Dbo->cacheSources = false;

		$schema1 = new CakeSchema(array(
			'name' => 'AlterTest1',
			'connection' => 'test',
			'altertest' => array(
				'id' => array('type' => 'integer', 'null' => false, 'default' => 0),
				'name' => array('type' => 'string', 'null' => false, 'length' => 50),
				'group1' => array('type' => 'integer', 'null' => true),
				'group2' => array('type' => 'integer', 'null' => true)
			)
		));

		$this->Dbo->rawQuery($this->Dbo->createSchema($schema1));

		$schema2 = new CakeSchema(array(
			'name' => 'AlterTest2',
			'connection' => 'test',
			'altertest' => array(
				'id' => array('type' => 'integer', 'null' => false, 'default' => 0),
				'name' => array('type' => 'string', 'null' => false, 'length' => 50),
				'group1' => array('type' => 'integer', 'null' => true),
				'group2' => array('type' => 'integer', 'null' => true),
				'indexes' => array(
					'name_idx' => array('unique' => false, 'column' => 'name'),
					'group_idx' => array('unique' => false, 'column' => 'group1'),
					'compound_idx' => array('unique' => false, 'column' => array('group1', 'group2')),
					'PRIMARY' => array('unique' => true, 'column' => 'id')
				)
			)
		));
		$this->Dbo->query($this->Dbo->alterSchema($schema2->compare($schema1)));

		$indexes = $this->Dbo->index('altertest');
		$this->assertEquals($schema2->tables['altertest']['indexes'], $indexes);

		// Change three indexes, delete one and add another one
		$schema3 = new CakeSchema(array(
			'name' => 'AlterTest3',
			'connection' => 'test',
			'altertest' => array(
				'id' => array('type' => 'integer', 'null' => false, 'default' => 0),
				'name' => array('type' => 'string', 'null' => false, 'length' => 50),
				'group1' => array('type' => 'integer', 'null' => true),
				'group2' => array('type' => 'integer', 'null' => true),
				'indexes' => array(
					'name_idx' => array('unique' => true, 'column' => 'name'),
					'group_idx' => array('unique' => false, 'column' => 'group2'),
					'compound_idx' => array('unique' => false, 'column' => array('group2', 'group1')),
					'another_idx' => array('unique' => false, 'column' => array('group1', 'name')))
		)));

		$this->Dbo->query($this->Dbo->alterSchema($schema3->compare($schema2)));

		$indexes = $this->Dbo->index('altertest');
		$this->assertEquals($schema3->tables['altertest']['indexes'], $indexes);

		// Compare us to ourself.
		$this->assertEquals(array(), $schema3->compare($schema3));

		// Drop the indexes
		$this->Dbo->query($this->Dbo->alterSchema($schema1->compare($schema3)));

		$indexes = $this->Dbo->index('altertest');
		$this->assertEquals(array(), $indexes);

		$this->Dbo->query($this->Dbo->dropSchema($schema1));
	}

/**
 * Test the alterSchema RENAME statements
 *
 * @return void
 */
	public function testAlterSchemaRenameTo() {
		$query = $this->Dbo->alterSchema(array(
			'posts' => array(
				'change' => array(
					'title' => array('name' => 'subject', 'type' => 'string', 'null' => false)
				)
			)
		));
		$this->assertContains('RENAME "title" TO "subject";', $query);
		$this->assertContains('ALTER COLUMN "subject" TYPE', $query);
		$this->assertNotContains(";\n\tALTER COLUMN \"subject\" TYPE", $query);
		$this->assertNotContains('ALTER COLUMN "title" TYPE "subject"', $query);
	}

/**
 * Test it is possible to use virtual field with postgresql
 *
 * @return void
 */
	public function testVirtualFields() {
		$this->loadFixtures('Article', 'Comment', 'User', 'Attachment', 'Tag', 'ArticlesTag');
		$Article = new Article;
		$Article->virtualFields = array(
			'next_id' => 'Article.id + 1',
			'complex' => 'Article.title || Article.body',
			'functional' => 'COALESCE(User.user, Article.title)',
			'subquery' => 'SELECT count(*) FROM ' . $Article->Comment->table
		);
		$result = $Article->find('first');
		$this->assertEquals(2, $result['Article']['next_id']);
		$this->assertEquals($result['Article']['complex'], $result['Article']['title'] . $result['Article']['body']);
		$this->assertEquals($result['Article']['functional'], $result['User']['user']);
		$this->assertEquals(6, $result['Article']['subquery']);
	}

/**
 * Test that virtual fields work with SQL constants
 *
 * @return void
 */
	public function testVirtualFieldAsAConstant() {
		$this->loadFixtures('Article', 'Comment');
		$Article = ClassRegistry::init('Article');
		$Article->virtualFields = array(
			'empty' => "NULL",
			'number' => 43,
			'truth' => 'TRUE'
		);
		$result = $Article->find('first');
		$this->assertNull($result['Article']['empty']);
		$this->assertTrue($result['Article']['truth']);
		$this->assertEquals(43, $result['Article']['number']);
	}

/**
 * Tests additional order options for postgres
 *
 * @return void
 */
	public function testOrderAdditionalParams() {
		$result = $this->Dbo->order(array('title' => 'DESC NULLS FIRST', 'body' => 'DESC'));
		$expected = ' ORDER BY "title" DESC NULLS FIRST, "body" DESC';
		$this->assertEquals($expected, $result);
	}

/**
 * Test it is possible to do a SELECT COUNT(DISTINCT Model.field)
 * query in postgres and it gets correctly quoted
 *
 * @return void
 */
	public function testQuoteDistinctInFunction() {
		$this->loadFixtures('Article');
		$Article = new Article;
		$result = $this->Dbo->fields($Article, null, array('COUNT(DISTINCT Article.id)'));
		$expected = array('COUNT(DISTINCT "Article"."id")');
		$this->assertEquals($expected, $result);

		$result = $this->Dbo->fields($Article, null, array('COUNT(DISTINCT id)'));
		$expected = array('COUNT(DISTINCT "id")');
		$this->assertEquals($expected, $result);

		$result = $this->Dbo->fields($Article, null, array('COUNT(DISTINCT FUNC(id))'));
		$expected = array('COUNT(DISTINCT FUNC("id"))');
		$this->assertEquals($expected, $result);
	}

/**
 * test that saveAll works even with conditions that lack a model name.
 *
 * @return void
 */
	public function testUpdateAllWithNonQualifiedConditions() {
		$this->loadFixtures('Article');
		$Article = new Article();
		$result = $Article->updateAll(array('title' => "'Awesome'"), array('title' => 'Third Article'));
		$this->assertTrue($result);

		$result = $Article->find('count', array(
			'conditions' => array('Article.title' => 'Awesome')
		));
		$this->assertEquals(1, $result, 'Article count is wrong or fixture has changed.');
	}

/**
 * test alterSchema on two tables.
 *
 * @return void
 */
	public function testAlteringTwoTables() {
		$schema1 = new CakeSchema(array(
			'name' => 'AlterTest1',
			'connection' => 'test',
			'altertest' => array(
				'id' => array('type' => 'integer', 'null' => false, 'default' => 0),
				'name' => array('type' => 'string', 'null' => false, 'length' => 50),
			),
			'other_table' => array(
				'id' => array('type' => 'integer', 'null' => false, 'default' => 0),
				'name' => array('type' => 'string', 'null' => false, 'length' => 50),
			)
		));
		$schema2 = new CakeSchema(array(
			'name' => 'AlterTest1',
			'connection' => 'test',
			'altertest' => array(
				'id' => array('type' => 'integer', 'null' => false, 'default' => 0),
				'field_two' => array('type' => 'string', 'null' => false, 'length' => 50),
			),
			'other_table' => array(
				'id' => array('type' => 'integer', 'null' => false, 'default' => 0),
				'field_two' => array('type' => 'string', 'null' => false, 'length' => 50),
			)
		));
		$result = $this->db->alterSchema($schema2->compare($schema1));
		$this->assertEquals(2, substr_count($result, 'field_two'), 'Too many fields');
		$this->assertFalse(strpos(';ALTER', $result), 'Too many semi colons');
	}

/**
 * test encoding setting.
 *
 * @return void
 */
	public function testEncoding() {
		$result = $this->Dbo->setEncoding('UTF8');
		$this->assertTrue($result);

		$result = $this->Dbo->getEncoding();
		$this->assertEquals('UTF8', $result);

		$result = $this->Dbo->setEncoding('EUC_JP'); /* 'EUC_JP' is right character code name in PostgreSQL */
		$this->assertTrue($result);

		$result = $this->Dbo->getEncoding();
		$this->assertEquals('EUC_JP', $result);
	}

/**
 * Test truncate with a mock.
 *
 * @return void
 */
	public function testTruncateStatements() {
		$this->loadFixtures('Article', 'User');
		$db = ConnectionManager::getDatasource('test');
		$schema = $db->config['schema'];
		$Article = new Article();

		$this->Dbo = $this->getMock('Postgres', array('execute'), array($db->config));

		$this->Dbo->expects($this->at(0))->method('execute')
			->with("DELETE FROM \"$schema\".\"articles\"");
		$this->Dbo->truncate($Article);

		$this->Dbo->expects($this->at(0))->method('execute')
			->with("DELETE FROM \"$schema\".\"articles\"");
		$this->Dbo->truncate('articles');

		// #2355: prevent duplicate prefix
		$this->Dbo->config['prefix'] = 'tbl_';
		$Article->tablePrefix = 'tbl_';
		$this->Dbo->expects($this->at(0))->method('execute')
			->with("DELETE FROM \"$schema\".\"tbl_articles\"");
		$this->Dbo->truncate($Article);

		$this->Dbo->expects($this->at(0))->method('execute')
			->with("DELETE FROM \"$schema\".\"tbl_articles\"");
		$this->Dbo->truncate('articles');
	}

/**
 * Test nested transaction
 *
 * @return void
 */
	public function testNestedTransaction() {
		$this->Dbo->useNestedTransactions = true;
		$this->skipIf($this->Dbo->nestedTransactionSupported() === false, 'The Postgres server do not support nested transaction');

		$this->loadFixtures('Article');
		$model = new Article();
		$model->hasOne = $model->hasMany = $model->belongsTo = $model->hasAndBelongsToMany = array();
		$model->cacheQueries = false;
		$this->Dbo->cacheMethods = false;

		$this->assertTrue($this->Dbo->begin());
		$this->assertNotEmpty($model->read(null, 1));

		$this->assertTrue($this->Dbo->begin());
		$this->assertTrue($model->delete(1));
		$this->assertEmpty($model->read(null, 1));
		$this->assertTrue($this->Dbo->rollback());
		$this->assertNotEmpty($model->read(null, 1));

		$this->assertTrue($this->Dbo->begin());
		$this->assertTrue($model->delete(1));
		$this->assertEmpty($model->read(null, 1));
		$this->assertTrue($this->Dbo->commit());
		$this->assertEmpty($model->read(null, 1));

		$this->assertTrue($this->Dbo->rollback());
		$this->assertNotEmpty($model->read(null, 1));
	}

	public function testResetSequence() {
		$model = new Article();

		$table = $this->Dbo->fullTableName($model, false);
		$fields = array(
			'id', 'user_id', 'title', 'body', 'published',
		);
		$values = array(
			array(1, 1, 'test', 'first post', false),
			array(2, 1, 'test 2', 'second post post', false),
		);
		$this->Dbo->insertMulti($table, $fields, $values);
		$sequence = $this->Dbo->getSequence($table);
		$result = $this->Dbo->rawQuery("SELECT nextval('$sequence')");
		$original = $result->fetch(PDO::FETCH_ASSOC);

		$this->assertTrue($this->Dbo->resetSequence($table, 'id'));
		$result = $this->Dbo->rawQuery("SELECT currval('$sequence')");
		$new = $result->fetch(PDO::FETCH_ASSOC);
		$this->assertTrue($new['currval'] > $original['nextval'], 'Sequence did not update');
	}

	public function testSettings() {
		Configure::write('Cache.disable', true);
		$this->Dbo = ConnectionManager::getDataSource('test');
		$this->skipIf(!($this->Dbo instanceof Postgres));

		$config2 = $this->Dbo->config;
		$config2['settings']['datestyle'] = 'sql, dmy';
		ConnectionManager::create('test2', $config2);
		$dbo2 = new Postgres($config2, true);
		$expected = array(array('r' => date('d/m/Y')));
		$r = $dbo2->fetchRow('SELECT now()::date AS "r"');
		$this->assertEquals($expected, $r);
		$dbo2->execute('SET DATESTYLE TO ISO');
		$dbo2->disconnect();
	}

/**
 * Test the limit function.
 *
 * @return void
 */
	public function testLimit() {
		$db = $this->Dbo;

		$result = $db->limit('0');
		$this->assertNull($result);

		$result = $db->limit('10');
		$this->assertEquals(' LIMIT 10', $result);

		$result = $db->limit('FARTS', 'BOOGERS');
		$this->assertEquals(' LIMIT 0 OFFSET 0', $result);

		$result = $db->limit(20, 10);
		$this->assertEquals(' LIMIT 20 OFFSET 10', $result);

		$result = $db->limit(10, 300000000000000000000000000000);
		$scientificNotation = sprintf('%.1E', 300000000000000000000000000000);
		$this->assertNotContains($scientificNotation, $result);
	}

/**
 * Test that postgres describes UUID columns correctly.
 *
 * @return void
 */
	public function testDescribeUuid() {
		$db = $this->Dbo;
		$db->execute('CREATE TABLE test_uuid_describe (id UUID PRIMARY KEY, name VARCHAR(255))');
		$data = $db->describe('test_uuid_describe');

		$expected = array(
			'type' => 'string',
			'null' => false,
			'default' => null,
			'length' => 36,
		);
		$this->assertSame($expected, $data['id']);
		$db->execute('DROP TABLE test_uuid_describe');
	}

/**
 * Test describe() behavior for timestamp columns.
 *
 * @return void
 */
	public function testDescribeTimestamp() {
		$this->loadFixtures('User');
		$model = ClassRegistry::init('User');
		$result = $this->Dbo->describe($model);
		$expected = array(
			'id' => array(
				'type' => 'integer',
				'null' => false,
				'default' => null,
				'length' => 11,
				'key' => 'primary'
			),
			'user' => array(
				'type' => 'string',
				'null' => true,
				'default' => null,
				'length' => 255
			),
			'password' => array(
				'type' => 'string',
				'null' => true,
				'default' => null,
				'length' => 255
			),
			'created' => array(
				'type' => 'datetime',
				'null' => true,
				'default' => null,
				'length' => null
			),
			'updated' => array(
				'type' => 'datetime',
				'null' => true,
				'default' => null,
				'length' => null
			)
		);
		$this->assertEquals($expected, $result);
	}

}
