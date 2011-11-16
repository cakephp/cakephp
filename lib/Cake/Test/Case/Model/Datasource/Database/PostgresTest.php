<?php
/**
 * DboPostgresTest file
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.Case.Model.Datasource.Database
 * @since         CakePHP(tm) v 1.2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
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
	function _execute($sql, $params = array(), $prepareOptions = array()) {
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
 * name property
 *
 * @var string 'PostgresTestModel'
 */
	public $name = 'PostgresTestModel';

/**
 * useTable property
 *
 * @var bool false
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
			'id'		=> array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
			'client_id' => array('type' => 'integer', 'null' => '', 'default' => '0', 'length' => '11'),
			'name'		=> array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
			'login'		=> array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
			'passwd'	=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '255'),
			'addr_1'	=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '255'),
			'addr_2'	=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '25'),
			'zip_code'	=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
			'city'		=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
			'country'	=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
			'phone'		=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
			'fax'		=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
			'url'		=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '255'),
			'email'		=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
			'comments'	=> array('type' => 'text', 'null' => '1', 'default' => '', 'length' => ''),
			'last_login'=> array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => ''),
			'created'	=> array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
			'updated'	=> array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
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
 * name property
 *
 * @var string 'PostgresClientTestModel'
 */
	public $name = 'PostgresClientTestModel';

/**
 * useTable property
 *
 * @var bool false
 */
	public $useTable = false;

/**
 * schema method
 *
 * @return void
 */
	public function schema($field = false) {
		return array(
			'id'		=> array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8', 'key' => 'primary'),
			'name'		=> array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
			'email'		=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
			'created'	=> array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => ''),
			'updated'	=> array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
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
 * @var boolean
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
 */
	public function setUp() {
		Configure::write('Cache.disable', true);
		$this->Dbo = ConnectionManager::getDataSource('test');
		$this->skipIf(!($this->Dbo instanceof Postgres));
		$this->Dbo2 = new DboPostgresTestDb($this->Dbo->config, false);
		$this->model = new PostgresTestModel();
	}

/**
 * Sets up a Dbo class instance for testing
 *
 */
	public function tearDown() {
		Configure::write('Cache.disable', false);
		unset($this->Dbo2);
	}

/**
 * Test field quoting method
 *
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
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->fields($this->model, null, 'PostgresTestModel.*');
		$expected = $fields;
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->fields($this->model, null, array('*', 'AnotherModel.id', 'AnotherModel.name'));
		$expected = array_merge($fields, array(
			'"AnotherModel"."id" AS "AnotherModel__id"',
			'"AnotherModel"."name" AS "AnotherModel__name"'));
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->fields($this->model, null, array('*', 'PostgresClientTestModel.*'));
		$expected = array_merge($fields, array(
			'"PostgresClientTestModel"."id" AS "PostgresClientTestModel__id"',
    		'"PostgresClientTestModel"."name" AS "PostgresClientTestModel__name"',
    		'"PostgresClientTestModel"."email" AS "PostgresClientTestModel__email"',
    		'"PostgresClientTestModel"."created" AS "PostgresClientTestModel__created"',
    		'"PostgresClientTestModel"."updated" AS "PostgresClientTestModel__updated"'));
		$this->assertEqual($expected, $result);
	}

/**
 * testColumnParsing method
 *
 * @return void
 */
	public function testColumnParsing() {
		$this->assertEqual($this->Dbo2->column('text'), 'text');
		$this->assertEqual($this->Dbo2->column('date'), 'date');
		$this->assertEqual($this->Dbo2->column('boolean'), 'boolean');
		$this->assertEqual($this->Dbo2->column('character varying'), 'string');
		$this->assertEqual($this->Dbo2->column('time without time zone'), 'time');
		$this->assertEqual($this->Dbo2->column('timestamp without time zone'), 'datetime');
	}

/**
 * testValueQuoting method
 *
 * @return void
 */
	public function testValueQuoting() {
		$this->assertEqual($this->Dbo->value(1.2, 'float'), "1.200000");
		$this->assertEqual($this->Dbo->value('1,2', 'float'), "'1,2'");

		$this->assertEqual($this->Dbo->value('0', 'integer'), "0");
		$this->assertEqual($this->Dbo->value('', 'integer'), 'NULL');
		$this->assertEqual($this->Dbo->value('', 'float'), 'NULL');
		$this->assertEqual($this->Dbo->value('', 'integer', false), "NULL");
		$this->assertEqual($this->Dbo->value('', 'float', false), "NULL");
		$this->assertEqual($this->Dbo->value('0.0', 'float'), "'0.0'");

		$this->assertEqual($this->Dbo->value('t', 'boolean'), "'TRUE'");
		$this->assertEqual($this->Dbo->value('f', 'boolean'), "'FALSE'");
		$this->assertEqual($this->Dbo->value(true), "'TRUE'");
		$this->assertEqual($this->Dbo->value(false), "'FALSE'");
		$this->assertEqual($this->Dbo->value('t'), "'t'");
		$this->assertEqual($this->Dbo->value('f'), "'f'");
		$this->assertEqual($this->Dbo->value('true', 'boolean'), "'TRUE'");
		$this->assertEqual($this->Dbo->value('false', 'boolean'), "'FALSE'");
		$this->assertEqual($this->Dbo->value('', 'boolean'), "'FALSE'");
		$this->assertEqual($this->Dbo->value(0, 'boolean'), "'FALSE'");
		$this->assertEqual($this->Dbo->value(1, 'boolean'), "'TRUE'");
		$this->assertEqual($this->Dbo->value('1', 'boolean'), "'TRUE'");
		$this->assertEqual($this->Dbo->value(null, 'boolean'), "NULL");
		$this->assertEqual($this->Dbo->value(array()), "NULL");
	}

/**
 * test that localized floats don't cause trouble.
 *
 * @return void
 */
	public function testLocalizedFloats() {
		$restore = setlocale(LC_ALL, null);
		setlocale(LC_ALL, 'de_DE');

		$result = $this->db->value(3.141593, 'float');
		$this->assertEquals($result, "3.141593");

		$result = $this->db->value(3.14);
		$this->assertEquals($result, "3.140000");

		setlocale(LC_ALL, $restore);
	}

/**
 * test that date and time columns do not generate errors with null and nullish values.
 *
 * @return void
 */
	public function testDateAndTimeAsNull() {
		$this->assertEqual($this->Dbo->value(null, 'date'), 'NULL');
		$this->assertEqual($this->Dbo->value('', 'date'), 'NULL');

		$this->assertEqual($this->Dbo->value('', 'datetime'), 'NULL');
		$this->assertEqual($this->Dbo->value(null, 'datetime'), 'NULL');

		$this->assertEqual($this->Dbo->value('', 'timestamp'), 'NULL');
		$this->assertEqual($this->Dbo->value(null, 'timestamp'), 'NULL');

		$this->assertEqual($this->Dbo->value('', 'time'), 'NULL');
		$this->assertEqual($this->Dbo->value(null, 'time'), 'NULL');
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
		$this->assertIdentical(false, $model->data['Datatype']['bool']);
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

		$this->assertEqual($db1->lastInsertId($table), 5);

		$db1->execute("INSERT INTO {$table} (\"user\", password) VALUES ('hoge', '{$password}')");
		$this->assertEqual($db1->lastInsertId($table), 6);
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
		$this->assertEqual($this->Dbo->buildColumn($result), $expected);

		$result = array('name' => 'foo', 'type' => 'text', 'length' => 100, 'default' => 'FOO');
		$expected = '"foo" text DEFAULT \'FOO\'';
		$this->assertEqual($this->Dbo->buildColumn($result), $expected);
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
		$this->assertEqual($result['BinaryTest']['data'], $data);
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
			'locale' => array('type'=>'string', 'null' => false, 'length' => 6, 'key' => 'index'),
			'model' => array('type'=>'string', 'null' => false, 'key' => 'index'),
			'foreign_key' => array(
			    'type'=>'integer', 'null' => false, 'length' => 10, 'key' => 'index'
			),
			'field' => array('type'=>'string', 'null' => false, 'key' => 'index'),
			'content' => array('type'=>'text', 'null' => true, 'default' => null),
			'indexes' => array(
			    'PRIMARY' => array('column' => 'id', 'unique' => 1),
			    'locale' => array('column' => 'locale', 'unique' => 0),
			    'model' => array('column' => 'model', 'unique' => 0),
			    'row_id' => array('column' => 'foreign_key', 'unique' => 0),
			    'field' => array('column' => 'field', 'unique' => 0)
			)
		));

		$result = $this->Dbo->createSchema($schema);
		$this->assertNoPattern('/^CREATE INDEX(.+);,$/', $result);
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

		$db1->rawQuery('CREATE TABLE ' .  $db1->fullTableName('datatype_tests') . ' (
			id serial NOT NULL,
			"varchar" character varying(40) NOT NULL,
			"full_length" character varying NOT NULL,
			"timestamp" timestamp without time zone,
			"date" date,
			CONSTRAINT test_data_types_pkey PRIMARY KEY (id)
		)');

		$model = new Model(array('name' => 'DatatypeTest', 'ds' => 'test'));
		$schema = new CakeSchema(array('connection' => 'test'));
		$result = $schema->read(array(
			'connection' => 'test',
			'models' => array('DatatypeTest')
		));
		$schema->tables = array('datatype_tests' => $result['tables']['missing']['datatype_tests']);
		$result = $db1->createSchema($schema, 'datatype_tests');


		$this->assertNoPattern('/timestamp DEFAULT/', $result);
		$this->assertPattern('/\"full_length\"\s*text\s.*,/', $result);
		$this->assertPattern('/timestamp\s*,/', $result);


		$db1->query('DROP TABLE ' . $db1->fullTableName('datatype_tests'));

		$db1->query($result);
		$result2 = $schema->read(array(
			'connection' => 'test',
			'models' => array('DatatypeTest')
		));
		$schema->tables = array('datatype_tests' => $result2['tables']['missing']['datatype_tests']);
		$result2 = $db1->createSchema($schema, 'datatype_tests');
		$this->assertEqual($result, $result2);

		$db1->query('DROP TABLE ' . $db1->fullTableName('datatype_tests'));
	}

/**
 * Test index generation from table info.
 *
 * @return void
 */
	public function testIndexGeneration() {
		$name = $this->Dbo->fullTableName('index_test', false);
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
		$this->assertEqual($expected, $result);

		$name = $this->Dbo->fullTableName('index_test_2', false);
		$this->Dbo->query('CREATE TABLE ' . $name . ' ("id" serial NOT NULL PRIMARY KEY, "bool" integer, "small_char" varchar(50), "description" varchar(40) )');
		$this->Dbo->query('CREATE UNIQUE INDEX multi_col ON ' . $name . '("small_char", "bool")');
		$expected = array(
			'PRIMARY' => array('unique' => true, 'column' => 'id'),
			'multi_col' => array('unique' => true, 'column' => array('small_char', 'bool')),
		);
		$result = $this->Dbo->index($name);
		$this->Dbo->query('DROP TABLE ' . $name);
		$this->assertEqual($expected, $result);
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
		$this->assertEqual($result['body']['type'], 'string');
		$this->assertEqual($result['status']['default'], 1);
		$this->assertEqual($result['author_id']['null'], true);
		$this->assertEqual($result['title']['null'], false);

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
		$this->assertNoPattern('/varchar\(36\) NOT NULL/i', $result);
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
		$this->assertEqual($schema2->tables['altertest']['indexes'], $indexes);

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
		$this->assertEqual($schema3->tables['altertest']['indexes'], $indexes);

		// Compare us to ourself.
		$this->assertEqual($schema3->compare($schema3), array());

		// Drop the indexes
		$this->Dbo->query($this->Dbo->alterSchema($schema1->compare($schema3)));

		$indexes = $this->Dbo->index('altertest');
		$this->assertEqual(array(), $indexes);

		$this->Dbo->query($this->Dbo->dropSchema($schema1));
	}

/*
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
		$this->assertEqual($result['Article']['next_id'], 2);
		$this->assertEqual($result['Article']['complex'], $result['Article']['title'] . $result['Article']['body']);
		$this->assertEqual($result['Article']['functional'], $result['User']['user']);
		$this->assertEqual($result['Article']['subquery'], 6);
	}

/**
 * Test that virtual fields work with SQL constants
 *
 * @return void
 */
	function testVirtualFieldAsAConstant() {
		$this->loadFixtures('Article', 'Comment');
		$Article =& ClassRegistry::init('Article');
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
		$this->assertEqual($expected, $result);
	}

/**
* Test it is possible to do a SELECT COUNT(DISTINCT Model.field) query in postgres and it gets correctly quoted
*/
	public function testQuoteDistinctInFunction() {
		$this->loadFixtures('Article');
		$Article = new Article;
		$result = $this->Dbo->fields($Article, null, array('COUNT(DISTINCT Article.id)'));
		$expected = array('COUNT(DISTINCT "Article"."id")');
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->fields($Article, null, array('COUNT(DISTINCT id)'));
		$expected = array('COUNT(DISTINCT "id")');
		$this->assertEqual($expected, $result);

		$result = $this->Dbo->fields($Article, null, array('COUNT(DISTINCT FUNC(id))'));
		$expected = array('COUNT(DISTINCT FUNC("id"))');
		$this->assertEqual($expected, $result);
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
		$this->assertEqual($result, 1, 'Article count is wrong or fixture has changed.');
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
		$this->assertEqual(2, substr_count($result, 'field_two'), 'Too many fields');
		$this->assertFalse(strpos(';ALTER', $result), 'Too many semi colons');
	}
	
/**
 * test encoding setting.
 *
 * @return void
 */
	public function testEncoding() {
		$result = $this->Dbo->setEncoding('utf8');
		$this->assertTrue($result) ;
		
		$result = $this->Dbo->getEncoding();
		$this->assertEqual('utf8', $result) ;
		
		$result = $this->Dbo->setEncoding('EUC-JP');
		$this->assertTrue($result) ;
		
		$result = $this->Dbo->getEncoding();
		$this->assertEqual('EUC-JP', $result) ;
	}
}
