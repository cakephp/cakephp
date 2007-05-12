<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package			cake.tests
 * @subpackage		cake.tests.cases.libs
 * @since			CakePHP(tm) v 1.2.0.4206
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
uses('Validation');
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs
 */
class ValidationTestCase extends UnitTestCase {

	function testAlphaNumeric(){
		$validation = new Validation();

		$this->assertTrue($validation->alphaNumeric('frferrf'));
		$this->assertTrue($validation->alphaNumeric('12234'));
		$this->assertTrue($validation->alphaNumeric('1w2e2r3t4y'));
		$this->assertFalse($validation->alphaNumeric('12 234'));
		$this->assertFalse($validation->alphaNumeric('dfd 234'));
		$this->assertFalse($validation->alphaNumeric("\n"));
		$this->assertFalse($validation->alphaNumeric("\t"));
		$this->assertFalse($validation->alphaNumeric("\r"));
		$this->assertFalse($validation->alphaNumeric(' '));
		$this->assertFalse($validation->alphaNumeric(''));
	}

	function testAlphaNumericPassedAsArray(){
		$validation = new Validation();

		$this->assertTrue($validation->alphaNumeric(array('check' => 'frferrf')));
		$this->assertTrue($validation->alphaNumeric(array('check' => '12234')));
		$this->assertTrue($validation->alphaNumeric(array('check' => '1w2e2r3t4y')));
		$this->assertFalse($validation->alphaNumeric(array('check' => '12 234')));
		$this->assertFalse($validation->alphaNumeric(array('check' => 'dfd 234')));
		$this->assertFalse($validation->alphaNumeric(array('check' => "\n")));
		$this->assertFalse($validation->alphaNumeric(array('check' => "\t")));
		$this->assertFalse($validation->alphaNumeric(array('check' => "\r")));
		$this->assertFalse($validation->alphaNumeric(array('check' =>  ' ')));
		$this->assertFalse($validation->alphaNumeric(array('check' =>  '')));
	}

	function testBetween(){
		$validation = new Validation();
		$this->assertTrue($validation->between('abcdefg', 1, 7));
		$this->assertTrue($validation->between('', 0, 7));
		$this->assertFalse($validation->between('abcdefg', 1, 6));
	}

	function testBlank(){
		$validation = new Validation();
		$this->assertTrue($validation->blank(''));
		$this->assertTrue($validation->blank(' '));
		$this->assertTrue($validation->blank("\n"));
		$this->assertTrue($validation->blank("\t"));
		$this->assertTrue($validation->blank("\r"));
		$this->assertFalse($validation->blank('    Blank'));
		$this->assertFalse($validation->blank('Blank'));
	}

	function testBlankAsArray(){
		$validation = new Validation();
		$this->assertTrue($validation->blank(array('check' => '')));
		$this->assertTrue($validation->blank(array('check' => ' ')));
		$this->assertTrue($validation->blank(array('check' => "\n")));
		$this->assertTrue($validation->blank(array('check' => "\t")));
		$this->assertTrue($validation->blank(array('check' => "\r")));
		$this->assertFalse($validation->blank(array('check' => '    Blank')));
		$this->assertFalse($validation->blank(array('check' => 'Blank')));
	}

	function testcc(){
		$validation = new Validation();
		//American Express
		$this->assertTrue($validation->cc('370482756063980', array('amex')));
		$this->assertTrue($validation->cc('349106433773483', array('amex')));
		$this->assertTrue($validation->cc('344671486204764', array('amex')));
		$this->assertTrue($validation->cc('344042544509943', array('amex')));
		$this->assertTrue($validation->cc('377147515754475', array('amex')));
		$this->assertTrue($validation->cc('375239372816422', array('amex')));
		$this->assertTrue($validation->cc('376294341957707', array('amex')));
		$this->assertTrue($validation->cc('341779292230411', array('amex')));
		$this->assertTrue($validation->cc('341646919853372', array('amex')));
		$this->assertTrue($validation->cc('348498616319346', array('amex')));
		//BankCard
		$this->assertTrue($validation->cc('5610745867413420', array('bankcard')));
		$this->assertTrue($validation->cc('5610376649499352', array('bankcard')));
		$this->assertTrue($validation->cc('5610091936000694', array('bankcard')));
		$this->assertTrue($validation->cc('5602248780118788', array('bankcard')));
		$this->assertTrue($validation->cc('5610631567676765', array('bankcard')));
		$this->assertTrue($validation->cc('5602238211270795', array('bankcard')));
		$this->assertTrue($validation->cc('5610173951215470', array('bankcard')));
		$this->assertTrue($validation->cc('5610139705753702', array('bankcard')));
		$this->assertTrue($validation->cc('5602226032150551', array('bankcard')));
		$this->assertTrue($validation->cc('5602223993735777', array('bankcard')));
		//Diners Club 14
		$this->assertTrue($validation->cc('30155483651028', array('diners')));
		$this->assertTrue($validation->cc('36371312803821', array('diners')));
		$this->assertTrue($validation->cc('38801277489875', array('diners')));
		$this->assertTrue($validation->cc('30348560464296', array('diners')));
		$this->assertTrue($validation->cc('30349040317708', array('diners')));
		$this->assertTrue($validation->cc('36567413559978', array('diners')));
		$this->assertTrue($validation->cc('36051554732702', array('diners')));
		$this->assertTrue($validation->cc('30391842198191', array('diners')));
		$this->assertTrue($validation->cc('30172682197745', array('diners')));
		$this->assertTrue($validation->cc('30162056566641', array('diners')));
		$this->assertTrue($validation->cc('30085066927745', array('diners')));
		$this->assertTrue($validation->cc('36519025221976', array('diners')));
		$this->assertTrue($validation->cc('30372679371044', array('diners')));
		$this->assertTrue($validation->cc('38913939150124', array('diners')));
		$this->assertTrue($validation->cc('36852899094637', array('diners')));
		$this->assertTrue($validation->cc('30138041971120', array('diners')));
		$this->assertTrue($validation->cc('36184047836838', array('diners')));
		$this->assertTrue($validation->cc('30057460264462', array('diners')));
		$this->assertTrue($validation->cc('38980165212050', array('diners')));
		$this->assertTrue($validation->cc('30356516881240', array('diners')));
		$this->assertTrue($validation->cc('38744810033182', array('diners')));
		$this->assertTrue($validation->cc('30173638706621', array('diners')));
		$this->assertTrue($validation->cc('30158334709185', array('diners')));
		$this->assertTrue($validation->cc('30195413721186', array('diners')));
		$this->assertTrue($validation->cc('38863347694793', array('diners')));
		$this->assertTrue($validation->cc('30275627009113', array('diners')));
		$this->assertTrue($validation->cc('30242860404971', array('diners')));
		$this->assertTrue($validation->cc('30081877595151', array('diners')));
		$this->assertTrue($validation->cc('38053196067461', array('diners')));
		$this->assertTrue($validation->cc('36520379984870', array('diners')));
		//2004 MasterCard/Diners Club Alliance International 14
		$this->assertTrue($validation->cc('36747701998969', array('diners')));
		$this->assertTrue($validation->cc('36427861123159', array('diners')));
		$this->assertTrue($validation->cc('36150537602386', array('diners')));
		$this->assertTrue($validation->cc('36582388820610', array('diners')));
		$this->assertTrue($validation->cc('36729045250216', array('diners')));
		//2004 MasterCard/Diners Club Alliance US & Canada 16
		$this->assertTrue($validation->cc('5597511346169950', array('diners')));
		$this->assertTrue($validation->cc('5526443162217562', array('diners')));
		$this->assertTrue($validation->cc('5577265786122391', array('diners')));
		$this->assertTrue($validation->cc('5534061404676989', array('diners')));
		$this->assertTrue($validation->cc('5545313588374502', array('diners')));
		//Discover
		$this->assertTrue($validation->cc('6011802876467237', array('disc')));
		$this->assertTrue($validation->cc('6506432777720955', array('disc')));
		$this->assertTrue($validation->cc('6011126265283942', array('disc')));
		$this->assertTrue($validation->cc('6502187151579252', array('disc')));
		$this->assertTrue($validation->cc('6506600836002298', array('disc')));
		$this->assertTrue($validation->cc('6504376463615189', array('disc')));
		$this->assertTrue($validation->cc('6011440907005377', array('disc')));
		$this->assertTrue($validation->cc('6509735979634270', array('disc')));
		$this->assertTrue($validation->cc('6011422366775856', array('disc')));
		$this->assertTrue($validation->cc('6500976374623323', array('disc')));
		//enRoute
		$this->assertTrue($validation->cc('201496944158937', array('enroute')));
		$this->assertTrue($validation->cc('214945833739665', array('enroute')));
		$this->assertTrue($validation->cc('214982692491187', array('enroute')));
		$this->assertTrue($validation->cc('214901395949424', array('enroute')));
		$this->assertTrue($validation->cc('201480676269187', array('enroute')));
		$this->assertTrue($validation->cc('214911922887807', array('enroute')));
		$this->assertTrue($validation->cc('201485025457250', array('enroute')));
		$this->assertTrue($validation->cc('201402662758866', array('enroute')));
		$this->assertTrue($validation->cc('214981579370225', array('enroute')));
		$this->assertTrue($validation->cc('201447595859877', array('enroute')));
		//JCB 15 digit
		$this->assertTrue($validation->cc('210034762247893', array('jcb')));
		$this->assertTrue($validation->cc('180078671678892', array('jcb')));
		$this->assertTrue($validation->cc('180010559353736', array('jcb')));
		$this->assertTrue($validation->cc('210095474464258', array('jcb')));
		$this->assertTrue($validation->cc('210006675562188', array('jcb')));
		$this->assertTrue($validation->cc('210063299662662', array('jcb')));
		$this->assertTrue($validation->cc('180032506857825', array('jcb')));
		$this->assertTrue($validation->cc('210057919192738', array('jcb')));
		$this->assertTrue($validation->cc('180031358949367', array('jcb')));
		$this->assertTrue($validation->cc('180033802147846', array('jcb')));
		//JCB 16 digit
		$this->assertTrue($validation->cc('3096806857839939', array('jcb')));
		$this->assertTrue($validation->cc('3158699503187091', array('jcb')));
		$this->assertTrue($validation->cc('3112549607186579', array('jcb')));
		$this->assertTrue($validation->cc('3112332922425604', array('jcb')));
		$this->assertTrue($validation->cc('3112001541159239', array('jcb')));
		$this->assertTrue($validation->cc('3112162495317841', array('jcb')));
		$this->assertTrue($validation->cc('3337562627732768', array('jcb')));
		$this->assertTrue($validation->cc('3337107161330775', array('jcb')));
		$this->assertTrue($validation->cc('3528053736003621', array('jcb')));
		$this->assertTrue($validation->cc('3528915255020360', array('jcb')));
		$this->assertTrue($validation->cc('3096786059660921', array('jcb')));
		$this->assertTrue($validation->cc('3528264799292320', array('jcb')));
		$this->assertTrue($validation->cc('3096469164130136', array('jcb')));
		$this->assertTrue($validation->cc('3112127443822853', array('jcb')));
		$this->assertTrue($validation->cc('3096849995802328', array('jcb')));
		$this->assertTrue($validation->cc('3528090735127407', array('jcb')));
		$this->assertTrue($validation->cc('3112101006819234', array('jcb')));
		$this->assertTrue($validation->cc('3337444428040784', array('jcb')));
		$this->assertTrue($validation->cc('3088043154151061', array('jcb')));
		$this->assertTrue($validation->cc('3088295969414866', array('jcb')));
		$this->assertTrue($validation->cc('3158748843158575', array('jcb')));
		$this->assertTrue($validation->cc('3158709206148538', array('jcb')));
		$this->assertTrue($validation->cc('3158365159575324', array('jcb')));
		$this->assertTrue($validation->cc('3158671691305165', array('jcb')));
		$this->assertTrue($validation->cc('3528523028771093', array('jcb')));
		$this->assertTrue($validation->cc('3096057126267870', array('jcb')));
		$this->assertTrue($validation->cc('3158514047166834', array('jcb')));
		$this->assertTrue($validation->cc('3528274546125962', array('jcb')));
		$this->assertTrue($validation->cc('3528890967705733', array('jcb')));
		$this->assertTrue($validation->cc('3337198811307545', array('jcb')));
		//Maestro (debit card)
		$this->assertTrue($validation->cc('5020147409985219', array('maestro')));
		$this->assertTrue($validation->cc('5020931809905616', array('maestro')));
		$this->assertTrue($validation->cc('5020412965470224', array('maestro')));
		$this->assertTrue($validation->cc('5020129740944022', array('maestro')));
		$this->assertTrue($validation->cc('5020024696747943', array('maestro')));
		$this->assertTrue($validation->cc('5020581514636509', array('maestro')));
		$this->assertTrue($validation->cc('5020695008411987', array('maestro')));
		$this->assertTrue($validation->cc('5020565359718977', array('maestro')));
		$this->assertTrue($validation->cc('6339931536544062', array('maestro')));
		$this->assertTrue($validation->cc('6465028615704406', array('maestro')));
		//Mastercard
		$this->assertTrue($validation->cc('5580424361774366', array('mc')));
		$this->assertTrue($validation->cc('5589563059318282', array('mc')));
		$this->assertTrue($validation->cc('5387558333690047', array('mc')));
		$this->assertTrue($validation->cc('5163919215247175', array('mc')));
		$this->assertTrue($validation->cc('5386742685055055', array('mc')));
		$this->assertTrue($validation->cc('5102303335960674', array('mc')));
		$this->assertTrue($validation->cc('5526543403964565', array('mc')));
		$this->assertTrue($validation->cc('5538725892618432', array('mc')));
		$this->assertTrue($validation->cc('5119543573129778', array('mc')));
		$this->assertTrue($validation->cc('5391174753915767', array('mc')));
		$this->assertTrue($validation->cc('5510994113980714', array('mc')));
		$this->assertTrue($validation->cc('5183720260418091', array('mc')));
		$this->assertTrue($validation->cc('5488082196086704', array('mc')));
		$this->assertTrue($validation->cc('5484645164161834', array('mc')));
		$this->assertTrue($validation->cc('5171254350337031', array('mc')));
		$this->assertTrue($validation->cc('5526987528136452', array('mc')));
		$this->assertTrue($validation->cc('5504148941409358', array('mc')));
		$this->assertTrue($validation->cc('5240793507243615', array('mc')));
		$this->assertTrue($validation->cc('5162114693017107', array('mc')));
		$this->assertTrue($validation->cc('5163104807404753', array('mc')));
		$this->assertTrue($validation->cc('5590136167248365', array('mc')));
		$this->assertTrue($validation->cc('5565816281038948', array('mc')));
		$this->assertTrue($validation->cc('5467639122779531', array('mc')));
		$this->assertTrue($validation->cc('5297350261550024', array('mc')));
		$this->assertTrue($validation->cc('5162739131368058', array('mc')));
		//Solo 16
		$this->assertTrue($validation->cc('6767432107064987', array('solo')));
		$this->assertTrue($validation->cc('6334667758225411', array('solo')));
		$this->assertTrue($validation->cc('6767037421954068', array('solo')));
		$this->assertTrue($validation->cc('6767823306394854', array('solo')));
		$this->assertTrue($validation->cc('6334768185398134', array('solo')));
		$this->assertTrue($validation->cc('6767286729498589', array('solo')));
		$this->assertTrue($validation->cc('6334972104431261', array('solo')));
		$this->assertTrue($validation->cc('6334843427400616', array('solo')));
		$this->assertTrue($validation->cc('6767493947881311', array('solo')));
		$this->assertTrue($validation->cc('6767194235798817', array('solo')));
		//Solo 18
		$this->assertTrue($validation->cc('676714834398858593', array('solo')));
		$this->assertTrue($validation->cc('676751666435130857', array('solo')));
		$this->assertTrue($validation->cc('676781908573924236', array('solo')));
		$this->assertTrue($validation->cc('633488724644003240', array('solo')));
		$this->assertTrue($validation->cc('676732252338067316', array('solo')));
		$this->assertTrue($validation->cc('676747520084495821', array('solo')));
		$this->assertTrue($validation->cc('633465488901381957', array('solo')));
		$this->assertTrue($validation->cc('633487484858610484', array('solo')));
		$this->assertTrue($validation->cc('633453764680740694', array('solo')));
		$this->assertTrue($validation->cc('676768613295414451', array('solo')));
		//Solo 19
		$this->assertTrue($validation->cc('6767838565218340113', array('solo')));
		$this->assertTrue($validation->cc('6767760119829705181', array('solo')));
		$this->assertTrue($validation->cc('6767265917091593668', array('solo')));
		$this->assertTrue($validation->cc('6767938856947440111', array('solo')));
		$this->assertTrue($validation->cc('6767501945697390076', array('solo')));
		$this->assertTrue($validation->cc('6334902868716257379', array('solo')));
		$this->assertTrue($validation->cc('6334922127686425532', array('solo')));
		$this->assertTrue($validation->cc('6334933119080706440', array('solo')));
		$this->assertTrue($validation->cc('6334647959628261714', array('solo')));
		$this->assertTrue($validation->cc('6334527312384101382', array('solo')));
		//Switch 16
		$this->assertTrue($validation->cc('5641829171515733', array('switch')));
		$this->assertTrue($validation->cc('5641824852820809', array('switch')));
		$this->assertTrue($validation->cc('6759129648956909', array('switch')));
		$this->assertTrue($validation->cc('6759626072268156', array('switch')));
		$this->assertTrue($validation->cc('5641822698388957', array('switch')));
		$this->assertTrue($validation->cc('5641827123105470', array('switch')));
		$this->assertTrue($validation->cc('5641823755819553', array('switch')));
		$this->assertTrue($validation->cc('5641821939587682', array('switch')));
		$this->assertTrue($validation->cc('4936097148079186', array('switch')));
		$this->assertTrue($validation->cc('5641829739125009', array('switch')));
		$this->assertTrue($validation->cc('5641822860725507', array('switch')));
		$this->assertTrue($validation->cc('4936717688865831', array('switch')));
		$this->assertTrue($validation->cc('6759487613615441', array('switch')));
		$this->assertTrue($validation->cc('5641821346840617', array('switch')));
		$this->assertTrue($validation->cc('5641825793417126', array('switch')));
		$this->assertTrue($validation->cc('5641821302759595', array('switch')));
		$this->assertTrue($validation->cc('6759784969918837', array('switch')));
		$this->assertTrue($validation->cc('5641824910667036', array('switch')));
		$this->assertTrue($validation->cc('6759139909636173', array('switch')));
		$this->assertTrue($validation->cc('6333425070638022', array('switch')));
		$this->assertTrue($validation->cc('5641823910382067', array('switch')));
		$this->assertTrue($validation->cc('4936295218139423', array('switch')));
		$this->assertTrue($validation->cc('6333031811316199', array('switch')));
		$this->assertTrue($validation->cc('4936912044763198', array('switch')));
		$this->assertTrue($validation->cc('4936387053303824', array('switch')));
		$this->assertTrue($validation->cc('6759535838760523', array('switch')));
		$this->assertTrue($validation->cc('6333427174594051', array('switch')));
		$this->assertTrue($validation->cc('5641829037102700', array('switch')));
		$this->assertTrue($validation->cc('5641826495463046', array('switch')));
		$this->assertTrue($validation->cc('6333480852979946', array('switch')));
		$this->assertTrue($validation->cc('5641827761302876', array('switch')));
		$this->assertTrue($validation->cc('5641825083505317', array('switch')));
		$this->assertTrue($validation->cc('6759298096003991', array('switch')));
		$this->assertTrue($validation->cc('4936119165483420', array('switch')));
		$this->assertTrue($validation->cc('4936190990500993', array('switch')));
		$this->assertTrue($validation->cc('4903356467384927', array('switch')));
		$this->assertTrue($validation->cc('6333372765092554', array('switch')));
		$this->assertTrue($validation->cc('5641821330950570', array('switch')));
		$this->assertTrue($validation->cc('6759841558826118', array('switch')));
		$this->assertTrue($validation->cc('4936164540922452', array('switch')));
		//Switch 18
		$this->assertTrue($validation->cc('493622764224625174', array('switch')));
		$this->assertTrue($validation->cc('564182823396913535', array('switch')));
		$this->assertTrue($validation->cc('675917308304801234', array('switch')));
		$this->assertTrue($validation->cc('675919890024220298', array('switch')));
		$this->assertTrue($validation->cc('633308376862556751', array('switch')));
		$this->assertTrue($validation->cc('564182377633208779', array('switch')));
		$this->assertTrue($validation->cc('564182870014926787', array('switch')));
		$this->assertTrue($validation->cc('675979788553829819', array('switch')));
		$this->assertTrue($validation->cc('493668394358130935', array('switch')));
		$this->assertTrue($validation->cc('493637431790930965', array('switch')));
		$this->assertTrue($validation->cc('633321438601941513', array('switch')));
		$this->assertTrue($validation->cc('675913800898840986', array('switch')));
		$this->assertTrue($validation->cc('564182592016841547', array('switch')));
		$this->assertTrue($validation->cc('564182428380440899', array('switch')));
		$this->assertTrue($validation->cc('493696376827623463', array('switch')));
		$this->assertTrue($validation->cc('675977939286485757', array('switch')));
		$this->assertTrue($validation->cc('490302699502091579', array('switch')));
		$this->assertTrue($validation->cc('564182085013662230', array('switch')));
		$this->assertTrue($validation->cc('493693054263310167', array('switch')));
		$this->assertTrue($validation->cc('633321755966697525', array('switch')));
		$this->assertTrue($validation->cc('675996851719732811', array('switch')));
		$this->assertTrue($validation->cc('493699211208281028', array('switch')));
		$this->assertTrue($validation->cc('493697817378356614', array('switch')));
		$this->assertTrue($validation->cc('675968224161768150', array('switch')));
		$this->assertTrue($validation->cc('493669416873337627', array('switch')));
		$this->assertTrue($validation->cc('564182439172549714', array('switch')));
		$this->assertTrue($validation->cc('675926914467673598', array('switch')));
		$this->assertTrue($validation->cc('564182565231977809', array('switch')));
		$this->assertTrue($validation->cc('675966282607849002', array('switch')));
		$this->assertTrue($validation->cc('493691609704348548', array('switch')));
		$this->assertTrue($validation->cc('675933118546065120', array('switch')));
		$this->assertTrue($validation->cc('493631116677238592', array('switch')));
		$this->assertTrue($validation->cc('675921142812825938', array('switch')));
		$this->assertTrue($validation->cc('633338311815675113', array('switch')));
		$this->assertTrue($validation->cc('633323539867338621', array('switch')));
		$this->assertTrue($validation->cc('675964912740845663', array('switch')));
		$this->assertTrue($validation->cc('633334008833727504', array('switch')));
		$this->assertTrue($validation->cc('493631941273687169', array('switch')));
		$this->assertTrue($validation->cc('564182971729706785', array('switch')));
		$this->assertTrue($validation->cc('633303461188963496', array('switch')));
		//Switch 19
		$this->assertTrue($validation->cc('6759603460617628716', array('switch')));
		$this->assertTrue($validation->cc('4936705825268647681', array('switch')));
		$this->assertTrue($validation->cc('5641829846600479183', array('switch')));
		$this->assertTrue($validation->cc('6759389846573792530', array('switch')));
		$this->assertTrue($validation->cc('4936189558712637603', array('switch')));
		$this->assertTrue($validation->cc('5641822217393868189', array('switch')));
		$this->assertTrue($validation->cc('4903075563780057152', array('switch')));
		$this->assertTrue($validation->cc('4936510653566569547', array('switch')));
		$this->assertTrue($validation->cc('4936503083627303364', array('switch')));
		$this->assertTrue($validation->cc('4936777334398116272', array('switch')));
		$this->assertTrue($validation->cc('5641823876900554860', array('switch')));
		$this->assertTrue($validation->cc('6759619236903407276', array('switch')));
		$this->assertTrue($validation->cc('6759011470269978117', array('switch')));
		$this->assertTrue($validation->cc('6333175833997062502', array('switch')));
		$this->assertTrue($validation->cc('6759498728789080439', array('switch')));
		$this->assertTrue($validation->cc('4903020404168157841', array('switch')));
		$this->assertTrue($validation->cc('6759354334874804313', array('switch')));
		$this->assertTrue($validation->cc('6759900856420875115', array('switch')));
		$this->assertTrue($validation->cc('5641827269346868860', array('switch')));
		$this->assertTrue($validation->cc('5641828995047453870', array('switch')));
		$this->assertTrue($validation->cc('6333321884754806543', array('switch')));
		$this->assertTrue($validation->cc('6333108246283715901', array('switch')));
		$this->assertTrue($validation->cc('6759572372800700102', array('switch')));
		$this->assertTrue($validation->cc('4903095096797974933', array('switch')));
		$this->assertTrue($validation->cc('6333354315797920215', array('switch')));
		$this->assertTrue($validation->cc('6759163746089433755', array('switch')));
		$this->assertTrue($validation->cc('6759871666634807647', array('switch')));
		$this->assertTrue($validation->cc('5641827883728575248', array('switch')));
		$this->assertTrue($validation->cc('4936527975051407847', array('switch')));
		$this->assertTrue($validation->cc('5641823318396882141', array('switch')));
		$this->assertTrue($validation->cc('6759123772311123708', array('switch')));
		$this->assertTrue($validation->cc('4903054736148271088', array('switch')));
		$this->assertTrue($validation->cc('4936477526808883952', array('switch')));
		$this->assertTrue($validation->cc('4936433964890967966', array('switch')));
		$this->assertTrue($validation->cc('6333245128906049344', array('switch')));
		$this->assertTrue($validation->cc('4936321036970553134', array('switch')));
		$this->assertTrue($validation->cc('4936111816358702773', array('switch')));
		$this->assertTrue($validation->cc('4936196077254804290', array('switch')));
		$this->assertTrue($validation->cc('6759558831206830183', array('switch')));
		$this->assertTrue($validation->cc('5641827998830403137', array('switch')));
		//VISA 13 digit
		$this->assertTrue($validation->cc('4024007174754', array('visa')));
		$this->assertTrue($validation->cc('4104816460717', array('visa')));
		$this->assertTrue($validation->cc('4716229700437', array('visa')));
		$this->assertTrue($validation->cc('4539305400213', array('visa')));
		$this->assertTrue($validation->cc('4728260558665', array('visa')));
		$this->assertTrue($validation->cc('4929100131792', array('visa')));
		$this->assertTrue($validation->cc('4024007117308', array('visa')));
		$this->assertTrue($validation->cc('4539915491024', array('visa')));
		$this->assertTrue($validation->cc('4539790901139', array('visa')));
		$this->assertTrue($validation->cc('4485284914909', array('visa')));
		$this->assertTrue($validation->cc('4782793022350', array('visa')));
		$this->assertTrue($validation->cc('4556899290685', array('visa')));
		$this->assertTrue($validation->cc('4024007134774', array('visa')));
		$this->assertTrue($validation->cc('4333412341316', array('visa')));
		$this->assertTrue($validation->cc('4539534204543', array('visa')));
		$this->assertTrue($validation->cc('4485640373626', array('visa')));
		$this->assertTrue($validation->cc('4929911445746', array('visa')));
		$this->assertTrue($validation->cc('4539292550806', array('visa')));
		$this->assertTrue($validation->cc('4716523014030', array('visa')));
		$this->assertTrue($validation->cc('4024007125152', array('visa')));
		$this->assertTrue($validation->cc('4539758883311', array('visa')));
		$this->assertTrue($validation->cc('4024007103258', array('visa')));
		$this->assertTrue($validation->cc('4916933155767', array('visa')));
		$this->assertTrue($validation->cc('4024007159672', array('visa')));
		$this->assertTrue($validation->cc('4716935544871', array('visa')));
		$this->assertTrue($validation->cc('4929415177779', array('visa')));
		$this->assertTrue($validation->cc('4929748547896', array('visa')));
		$this->assertTrue($validation->cc('4929153468612', array('visa')));
		$this->assertTrue($validation->cc('4539397132104', array('visa')));
		$this->assertTrue($validation->cc('4485293435540', array('visa')));
		$this->assertTrue($validation->cc('4485799412720', array('visa')));
		$this->assertTrue($validation->cc('4916744757686', array('visa')));
		$this->assertTrue($validation->cc('4556475655426', array('visa')));
		$this->assertTrue($validation->cc('4539400441625', array('visa')));
		$this->assertTrue($validation->cc('4485437129173', array('visa')));
		$this->assertTrue($validation->cc('4716253605320', array('visa')));
		$this->assertTrue($validation->cc('4539366156589', array('visa')));
		$this->assertTrue($validation->cc('4916498061392', array('visa')));
		$this->assertTrue($validation->cc('4716127163779', array('visa')));
		$this->assertTrue($validation->cc('4024007183078', array('visa')));
		$this->assertTrue($validation->cc('4041553279654', array('visa')));
		$this->assertTrue($validation->cc('4532380121960', array('visa')));
		$this->assertTrue($validation->cc('4485906062491', array('visa')));
		$this->assertTrue($validation->cc('4539365115149', array('visa')));
		$this->assertTrue($validation->cc('4485146516702', array('visa')));
		//VISA 16 digit
		$this->assertTrue($validation->cc('4916375389940009', array('visa')));
		$this->assertTrue($validation->cc('4929167481032610', array('visa')));
		$this->assertTrue($validation->cc('4485029969061519', array('visa')));
		$this->assertTrue($validation->cc('4485573845281759', array('visa')));
		$this->assertTrue($validation->cc('4485669810383529', array('visa')));
		$this->assertTrue($validation->cc('4929615806560327', array('visa')));
		$this->assertTrue($validation->cc('4556807505609535', array('visa')));
		$this->assertTrue($validation->cc('4532611336232890', array('visa')));
		$this->assertTrue($validation->cc('4532201952422387', array('visa')));
		$this->assertTrue($validation->cc('4485073797976290', array('visa')));
		$this->assertTrue($validation->cc('4024007157580969', array('visa')));
		$this->assertTrue($validation->cc('4053740470212274', array('visa')));
		$this->assertTrue($validation->cc('4716265831525676', array('visa')));
		$this->assertTrue($validation->cc('4024007100222966', array('visa')));
		$this->assertTrue($validation->cc('4539556148303244', array('visa')));
		$this->assertTrue($validation->cc('4532449879689709', array('visa')));
		$this->assertTrue($validation->cc('4916805467840986', array('visa')));
		$this->assertTrue($validation->cc('4532155644440233', array('visa')));
		$this->assertTrue($validation->cc('4467977802223781', array('visa')));
		$this->assertTrue($validation->cc('4539224637000686', array('visa')));
		$this->assertTrue($validation->cc('4556629187064965', array('visa')));
		$this->assertTrue($validation->cc('4532970205932943', array('visa')));
		$this->assertTrue($validation->cc('4821470132041850', array('visa')));
		$this->assertTrue($validation->cc('4916214267894485', array('visa')));
		$this->assertTrue($validation->cc('4024007169073284', array('visa')));
		$this->assertTrue($validation->cc('4716783351296122', array('visa')));
		$this->assertTrue($validation->cc('4556480171913795', array('visa')));
		$this->assertTrue($validation->cc('4929678411034997', array('visa')));
		$this->assertTrue($validation->cc('4682061913519392', array('visa')));
		$this->assertTrue($validation->cc('4916495481746474', array('visa')));
		$this->assertTrue($validation->cc('4929007108460499', array('visa')));
		$this->assertTrue($validation->cc('4539951357838586', array('visa')));
		$this->assertTrue($validation->cc('4716482691051558', array('visa')));
		$this->assertTrue($validation->cc('4916385069917516', array('visa')));
		$this->assertTrue($validation->cc('4929020289494641', array('visa')));
		$this->assertTrue($validation->cc('4532176245263774', array('visa')));
		$this->assertTrue($validation->cc('4556242273553949', array('visa')));
		$this->assertTrue($validation->cc('4481007485188614', array('visa')));
		$this->assertTrue($validation->cc('4716533372139623', array('visa')));
		$this->assertTrue($validation->cc('4929152038152632', array('visa')));
		$this->assertTrue($validation->cc('4539404037310550', array('visa')));
		$this->assertTrue($validation->cc('4532800925229140', array('visa')));
		$this->assertTrue($validation->cc('4916845885268360', array('visa')));
		$this->assertTrue($validation->cc('4394514669078434', array('visa')));
		$this->assertTrue($validation->cc('4485611378115042', array('visa')));
		//Visa Electron
		$this->assertTrue($validation->cc('4175003346287100', array('electron')));
		$this->assertTrue($validation->cc('4913042516577228', array('electron')));
		$this->assertTrue($validation->cc('4917592325659381', array('electron')));
		$this->assertTrue($validation->cc('4917084924450511', array('electron')));
		$this->assertTrue($validation->cc('4917994610643999', array('electron')));
		$this->assertTrue($validation->cc('4175005933743585', array('electron')));
		$this->assertTrue($validation->cc('4175008373425044', array('electron')));
		$this->assertTrue($validation->cc('4913119763664154', array('electron')));
		$this->assertTrue($validation->cc('4913189017481812', array('electron')));
		$this->assertTrue($validation->cc('4913085104968622', array('electron')));
		$this->assertTrue($validation->cc('4175008803122021', array('electron')));
		$this->assertTrue($validation->cc('4913294453962489', array('electron')));
		$this->assertTrue($validation->cc('4175009797419290', array('electron')));
		$this->assertTrue($validation->cc('4175005028142917', array('electron')));
		$this->assertTrue($validation->cc('4913940802385364', array('electron')));
		//Voyager
		$this->assertTrue($validation->cc('869940697287073', array('voyager')));
		$this->assertTrue($validation->cc('869934523596112', array('voyager')));
		$this->assertTrue($validation->cc('869958670174621', array('voyager')));
		$this->assertTrue($validation->cc('869921250068209', array('voyager')));
		$this->assertTrue($validation->cc('869972521242198', array('voyager')));
	}

	function testLuhn(){
		$validation = new Validation();
		$validation->deep = true;

		//American Express
		$validation->check = '370482756063980';
		$this->assertTrue($validation->_luhn());
		//BankCard
		$validation->check = '5610745867413420';
		$this->assertTrue($validation->_luhn());
		//Diners Club 14
		$validation->check = '30155483651028';
		$this->assertTrue($validation->_luhn());
		//2004 MasterCard/Diners Club Alliance International 14
		$validation->check = '36747701998969';
		$this->assertTrue($validation->_luhn());
		//2004 MasterCard/Diners Club Alliance US & Canada 16
		$validation->check = '5597511346169950';
		$this->assertTrue($validation->_luhn());
		//Discover
		$validation->check = '6011802876467237';
		$this->assertTrue($validation->_luhn());
		//enRoute
		$validation->check = '201496944158937';
		$this->assertTrue($validation->_luhn());
		//JCB 15 digit
		$validation->check = '210034762247893';
		$this->assertTrue($validation->_luhn());
		//JCB 16 digit
		$validation->check = '3096806857839939';
		$this->assertTrue($validation->_luhn());
		//Maestro (debit card)
		$validation->check = '5020147409985219';
		$this->assertTrue($validation->_luhn());
		//Mastercard
		$validation->check = '5580424361774366';
		$this->assertTrue($validation->_luhn());
		//Solo 16
		$validation->check = '6767432107064987';
		$this->assertTrue($validation->_luhn());
		//Solo 18
		$validation->check = '676714834398858593';
		$this->assertTrue($validation->_luhn());
		//Solo 19
		$validation->check = '6767838565218340113';
		$this->assertTrue($validation->_luhn());
		//Switch 16
		$validation->check = '5641829171515733';
		$this->assertTrue($validation->_luhn());
		//Switch 18
		$validation->check = '493622764224625174';
		$this->assertTrue($validation->_luhn());
		//Switch 19
		$validation->check = '6759603460617628716';
		$this->assertTrue($validation->_luhn());
		//VISA 13 digit
		$validation->check = '4024007174754';
		$this->assertTrue($validation->_luhn());
		//VISA 16 digit
		$validation->check = '4916375389940009';
		$this->assertTrue($validation->_luhn());
		//Visa Electron
		$validation->check = '4175003346287100';
		$this->assertTrue($validation->_luhn());
		//Voyager
		$validation->check = '869940697287073';
		$this->assertTrue($validation->_luhn());

		$validation->check = '0000000000000000';
		$this->assertFalse($validation->_luhn());

		$validation->check = '869940697287173';
		$this->assertFalse($validation->_luhn());
	}

	function testCustomRegexForCc() {
		$validation = new Validation();
		$this->assertTrue($validation->cc('12332105933743585', null, null, '/123321\\d{11}/'));
		$this->assertFalse($validation->cc('1233210593374358', null, null, '/123321\\d{11}/'));
		$this->assertFalse($validation->cc('12312305933743585', null, null, '/123321\\d{11}/'));
	}

	function testCustomRegexForCcWithLuhnCheck() {
		$validation = new Validation();
		$this->assertTrue($validation->cc('12332110426226941', null, true, '/123321\\d{11}/'));
		$this->assertFalse($validation->cc('12332105933743585', null, true, '/123321\\d{11}/'));
		$this->assertFalse($validation->cc('12332105933743587', null, true, '/123321\\d{11}/'));
		$this->assertFalse($validation->cc('12312305933743585', null, true, '/123321\\d{11}/'));
	}

	function testFastCc() {
		$validation = new Validation();
		//American Express
		$this->assertTrue($validation->cc('370482756063980'));
		//Diners Club 14
		$this->assertTrue($validation->cc('30155483651028'));
		//2004 MasterCard/Diners Club Alliance International 14
		$this->assertTrue($validation->cc('36747701998969'));
		//2004 MasterCard/Diners Club Alliance US & Canada 16
		$this->assertTrue($validation->cc('5597511346169950'));
		//Discover
		$this->assertTrue($validation->cc('6011802876467237'));
		//Mastercard
		$this->assertTrue($validation->cc('5580424361774366'));
		//VISA 13 digit
		$this->assertTrue($validation->cc('4024007174754'));
		//VISA 16 digit
		$this->assertTrue($validation->cc('4916375389940009'));
		//Visa Electron
		$this->assertTrue($validation->cc('4175003346287100'));
	}

	function testAllCc() {
		$validation = new Validation();
		//American Express
		$this->assertTrue($validation->cc('370482756063980', 'all'));
		//BankCard
		$this->assertTrue($validation->cc('5610745867413420', 'all'));
		//Diners Club 14
		$this->assertTrue($validation->cc('30155483651028', 'all'));
		//2004 MasterCard/Diners Club Alliance International 14
		$this->assertTrue($validation->cc('36747701998969', 'all'));
		//2004 MasterCard/Diners Club Alliance US & Canada 16
		$this->assertTrue($validation->cc('5597511346169950', 'all'));
		//Discover
		$this->assertTrue($validation->cc('6011802876467237', 'all'));
		//enRoute
		$this->assertTrue($validation->cc('201496944158937', 'all'));
		//JCB 15 digit
		$this->assertTrue($validation->cc('210034762247893', 'all'));
		//JCB 16 digit
		$this->assertTrue($validation->cc('3096806857839939', 'all'));
		//Maestro (debit card)
		$this->assertTrue($validation->cc('5020147409985219', 'all'));
		//Mastercard
		$this->assertTrue($validation->cc('5580424361774366', 'all'));
		//Solo 16
		$this->assertTrue($validation->cc('6767432107064987', 'all'));
		//Solo 18
		$this->assertTrue($validation->cc('676714834398858593', 'all'));
		//Solo 19
		$this->assertTrue($validation->cc('6767838565218340113', 'all'));
		//Switch 16
		$this->assertTrue($validation->cc('5641829171515733', 'all'));
		//Switch 18
		$this->assertTrue($validation->cc('493622764224625174', 'all'));
		//Switch 19
		$this->assertTrue($validation->cc('6759603460617628716', 'all'));
		//VISA 13 digit
		$this->assertTrue($validation->cc('4024007174754', 'all'));
		//VISA 16 digit
		$this->assertTrue($validation->cc('4916375389940009', 'all'));
		//Visa Electron
		$this->assertTrue($validation->cc('4175003346287100', 'all'));
		//Voyager
		$this->assertTrue($validation->cc('869940697287073', 'all'));
	}

	function testAllCcDeep() {
		$validation = new Validation();
		//American Express
		$this->assertTrue($validation->cc('370482756063980', 'all', true));
		//BankCard
		$this->assertTrue($validation->cc('5610745867413420', 'all', true));
		//Diners Club 14
		$this->assertTrue($validation->cc('30155483651028', 'all', true));
		//2004 MasterCard/Diners Club Alliance International 14
		$this->assertTrue($validation->cc('36747701998969', 'all', true));
		//2004 MasterCard/Diners Club Alliance US & Canada 16
		$this->assertTrue($validation->cc('5597511346169950', 'all', true));
		//Discover
		$this->assertTrue($validation->cc('6011802876467237', 'all', true));
		//enRoute
		$this->assertTrue($validation->cc('201496944158937', 'all', true));
		//JCB 15 digit
		$this->assertTrue($validation->cc('210034762247893', 'all', true));
		//JCB 16 digit
		$this->assertTrue($validation->cc('3096806857839939', 'all', true));
		//Maestro (debit card)
		$this->assertTrue($validation->cc('5020147409985219', 'all', true));
		//Mastercard
		$this->assertTrue($validation->cc('5580424361774366', 'all', true));
		//Solo 16
		$this->assertTrue($validation->cc('6767432107064987', 'all', true));
		//Solo 18
		$this->assertTrue($validation->cc('676714834398858593', 'all', true));
		//Solo 19
		$this->assertTrue($validation->cc('6767838565218340113', 'all', true));
		//Switch 16
		$this->assertTrue($validation->cc('5641829171515733', 'all', true));
		//Switch 18
		$this->assertTrue($validation->cc('493622764224625174', 'all', true));
		//Switch 19
		$this->assertTrue($validation->cc('6759603460617628716', 'all', true));
		//VISA 13 digit
		$this->assertTrue($validation->cc('4024007174754', 'all', true));
		//VISA 16 digit
		$this->assertTrue($validation->cc('4916375389940009', 'all', true));
		//Visa Electron
		$this->assertTrue($validation->cc('4175003346287100', 'all', true));
		//Voyager
		$this->assertTrue($validation->cc('869940697287073', 'all', true));
	}

	function testComparison() {
		$validation = new Validation();
		$this->assertTrue($validation->comparison(7, 'is greater', 6));
		$this->assertTrue($validation->comparison(7, '>', 6));
		$this->assertTrue($validation->comparison(6, 'is less', 7));
		$this->assertTrue($validation->comparison(6, '<', 7));
		$this->assertTrue($validation->comparison(7, 'greater or equal', 7));
		$this->assertTrue($validation->comparison(7, '>=', 7));
		$this->assertTrue($validation->comparison(7, 'greater or equal', 6));
		$this->assertTrue($validation->comparison(7, '>=', 6));
		$this->assertTrue($validation->comparison(6, 'less or equal', 7));
		$this->assertTrue($validation->comparison(6, '<=', 7));
		$this->assertTrue($validation->comparison(7, 'equal to', 7));
		$this->assertTrue($validation->comparison(7, '==', 7));
		$this->assertTrue($validation->comparison(7, 'not equal', 6));
		$this->assertTrue($validation->comparison(7, '!=', 6));
		$this->assertFalse($validation->comparison(6, 'is greater', 7));
		$this->assertFalse($validation->comparison(6, '>', 7));
		$this->assertFalse($validation->comparison(7, 'is less', 6));
		$this->assertFalse($validation->comparison(7, '<', 6));
		$this->assertFalse($validation->comparison(6, 'greater or equal', 7));
		$this->assertFalse($validation->comparison(6, '>=', 7));
		$this->assertFalse($validation->comparison(6, 'greater or equal', 7));
		$this->assertFalse($validation->comparison(6, '>=', 7));
		$this->assertFalse($validation->comparison(7, 'less or equal', 6));
		$this->assertFalse($validation->comparison(7, '<=', 6));
		$this->assertFalse($validation->comparison(7, 'equal to', 6));
		$this->assertFalse($validation->comparison(7, '==', 6));
		$this->assertFalse($validation->comparison(7, 'not equal', 7));
		$this->assertFalse($validation->comparison(7, '!=', 7));
	}

	function testComparisonAsArray() {
		$validation = new Validation();
		$this->assertTrue($validation->comparison(array('check1' => 7, 'operator' => 'is greater', 'check2' => 6)));
		$this->assertTrue($validation->comparison(array('check1' => 7, 'operator' => '>', 'check2' => 6)));
		$this->assertTrue($validation->comparison(array('check1' => 6, 'operator' => 'is less', 'check2' => 7)));
		$this->assertTrue($validation->comparison(array('check1' => 6, 'operator' => '<', 'check2' => 7)));
		$this->assertTrue($validation->comparison(array('check1' => 7, 'operator' => 'greater or equal', 'check2' => 7)));
		$this->assertTrue($validation->comparison(array('check1' => 7, 'operator' => '>=', 'check2' => 7)));
		$this->assertTrue($validation->comparison(array('check1' => 7, 'operator' => 'greater or equal','check2' =>  6)));
		$this->assertTrue($validation->comparison(array('check1' => 7, 'operator' => '>=', 'check2' => 6)));
		$this->assertTrue($validation->comparison(array('check1' => 6, 'operator' => 'less or equal', 'check2' => 7)));
		$this->assertTrue($validation->comparison(array('check1' => 6, 'operator' => '<=', 'check2' => 7)));
		$this->assertTrue($validation->comparison(array('check1' => 7, 'operator' => 'equal to', 'check2' => 7)));
		$this->assertTrue($validation->comparison(array('check1' => 7, 'operator' => '==', 'check2' => 7)));
		$this->assertTrue($validation->comparison(array('check1' => 7, 'operator' => 'not equal', 'check2' => 6)));
		$this->assertTrue($validation->comparison(array('check1' => 7, 'operator' => '!=', 'check2' => 6)));
		$this->assertFalse($validation->comparison(array('check1' => 6, 'operator' => 'is greater', 'check2' => 7)));
		$this->assertFalse($validation->comparison(array('check1' => 6, 'operator' => '>', 'check2' => 7)));
		$this->assertFalse($validation->comparison(array('check1' => 7, 'operator' => 'is less', 'check2' => 6)));
		$this->assertFalse($validation->comparison(array('check1' => 7, 'operator' => '<', 'check2' => 6)));
		$this->assertFalse($validation->comparison(array('check1' => 6, 'operator' => 'greater or equal', 'check2' => 7)));
		$this->assertFalse($validation->comparison(array('check1' => 6, 'operator' => '>=', 'check2' => 7)));
		$this->assertFalse($validation->comparison(array('check1' => 6, 'operator' => 'greater or equal', 'check2' => 7)));
		$this->assertFalse($validation->comparison(array('check1' => 6, 'operator' => '>=', 'check2' => 7)));
		$this->assertFalse($validation->comparison(array('check1' => 7, 'operator' => 'less or equal', 'check2' => 6)));
		$this->assertFalse($validation->comparison(array('check1' => 7, 'operator' => '<=', 'check2' => 6)));
		$this->assertFalse($validation->comparison(array('check1' => 7, 'operator' => 'equal to', 'check2' => 6)));
		$this->assertFalse($validation->comparison(array('check1' => 7, 'operator' => '==','check2' =>  6)));
		$this->assertFalse($validation->comparison(array('check1' => 7, 'operator' => 'not equal', 'check2' => 7)));
		$this->assertFalse($validation->comparison(array('check1' => 7, 'operator' => '!=', 'check2' => 7)));
	}

	function testCustom() {
		$validation = new Validation();
		$this->assertTrue($validation->custom('12345', '/(?<!\\S)\\d++(?!\\S)/'));
		$this->assertFalse($validation->custom('Text', '/(?<!\\S)\\d++(?!\\S)/'));
		$this->assertFalse($validation->custom('123.45', '/(?<!\\S)\\d++(?!\\S)/'));
	}

	function testCustomAsArray() {
		$validation = new Validation();
		$this->assertTrue($validation->custom(array('check' => '12345', 'regex' => '/(?<!\\S)\\d++(?!\\S)/')));
		$this->assertFalse($validation->custom(array('check' => 'Text', 'regex' => '/(?<!\\S)\\d++(?!\\S)/')));
		$this->assertFalse($validation->custom(array('check' => '123.45', 'regex' => '/(?<!\\S)\\d++(?!\\S)/')));
	}

	function testDateDdmmyyyy() {
		$validation = new Validation();
		$this->assertTrue($validation->date('27-12-2006', array('dmy')));
		$this->assertTrue($validation->date('27.12.2006', array('dmy')));
		$this->assertTrue($validation->date('27/12/2006', array('dmy')));
		$this->assertTrue($validation->date('27 12 2006', array('dmy')));
		$this->assertFalse($validation->date('00-00-0000', array('dmy')));
		$this->assertFalse($validation->date('00.00.0000', array('dmy')));
		$this->assertFalse($validation->date('00/00/0000', array('dmy')));
		$this->assertFalse($validation->date('00 00 0000', array('dmy')));
		$this->assertFalse($validation->date('31-11-2006', array('dmy')));
		$this->assertFalse($validation->date('31.11.2006', array('dmy')));
		$this->assertFalse($validation->date('31/11/2006', array('dmy')));
		$this->assertFalse($validation->date('31 11 2006', array('dmy')));
	}

	function testDateDdmmyyyyLeapYear() {
		$validation = new Validation();
		$this->assertTrue($validation->date('29-02-2004', array('dmy')));
		$this->assertTrue($validation->date('29.02.2004', array('dmy')));
		$this->assertTrue($validation->date('29/02/2004', array('dmy')));
		$this->assertTrue($validation->date('29 02 2004', array('dmy')));
		$this->assertFalse($validation->date('29-02-2006', array('dmy')));
		$this->assertFalse($validation->date('29.02.2006', array('dmy')));
		$this->assertFalse($validation->date('29/02/2006', array('dmy')));
		$this->assertFalse($validation->date('29 02 2006', array('dmy')));
	}

	function testDateDdmmyy() {
		$validation = new Validation();
		$this->assertTrue($validation->date('27-12-06', array('dmy')));
		$this->assertTrue($validation->date('27.12.06', array('dmy')));
		$this->assertTrue($validation->date('27/12/06', array('dmy')));
		$this->assertTrue($validation->date('27 12 06', array('dmy')));
		$this->assertFalse($validation->date('00-00-00', array('dmy')));
		$this->assertFalse($validation->date('00.00.00', array('dmy')));
		$this->assertFalse($validation->date('00/00/00', array('dmy')));
		$this->assertFalse($validation->date('00 00 00', array('dmy')));
		$this->assertFalse($validation->date('31-11-06', array('dmy')));
		$this->assertFalse($validation->date('31.11.06', array('dmy')));
		$this->assertFalse($validation->date('31/11/06', array('dmy')));
		$this->assertFalse($validation->date('31 11 06', array('dmy')));
	}

	function testDateDdmmyyLeapYear() {
		$validation = new Validation();
		$this->assertTrue($validation->date('29-02-04', array('dmy')));
		$this->assertTrue($validation->date('29.02.04', array('dmy')));
		$this->assertTrue($validation->date('29/02/04', array('dmy')));
		$this->assertTrue($validation->date('29 02 04', array('dmy')));
		$this->assertFalse($validation->date('29-02-06', array('dmy')));
		$this->assertFalse($validation->date('29.02.06', array('dmy')));
		$this->assertFalse($validation->date('29/02/06', array('dmy')));
		$this->assertFalse($validation->date('29 02 06', array('dmy')));
	}

	function testDateDmyy() {
		$validation = new Validation();
		$this->assertTrue($validation->date('7-2-06', array('dmy')));
		$this->assertTrue($validation->date('7.2.06', array('dmy')));
		$this->assertTrue($validation->date('7/2/06', array('dmy')));
		$this->assertTrue($validation->date('7 2 06', array('dmy')));
		$this->assertFalse($validation->date('0-0-00', array('dmy')));
		$this->assertFalse($validation->date('0.0.00', array('dmy')));
		$this->assertFalse($validation->date('0/0/00', array('dmy')));
		$this->assertFalse($validation->date('0 0 00', array('dmy')));
		$this->assertFalse($validation->date('32-2-06', array('dmy')));
		$this->assertFalse($validation->date('32.2.06', array('dmy')));
		$this->assertFalse($validation->date('32/2/06', array('dmy')));
		$this->assertFalse($validation->date('32 2 06', array('dmy')));
	}

	function testDateDmyyLeapYear() {
		$validation = new Validation();
		$this->assertTrue($validation->date('29-2-04', array('dmy')));
		$this->assertTrue($validation->date('29.2.04', array('dmy')));
		$this->assertTrue($validation->date('29/2/04', array('dmy')));
		$this->assertTrue($validation->date('29 2 04', array('dmy')));
		$this->assertFalse($validation->date('29-2-06', array('dmy')));
		$this->assertFalse($validation->date('29.2.06', array('dmy')));
		$this->assertFalse($validation->date('29/2/06', array('dmy')));
		$this->assertFalse($validation->date('29 2 06', array('dmy')));
	}

	function testDateDmyyyy() {
		$validation = new Validation();
		$this->assertTrue($validation->date('7-2-2006', array('dmy')));
		$this->assertTrue($validation->date('7.2.2006', array('dmy')));
		$this->assertTrue($validation->date('7/2/2006', array('dmy')));
		$this->assertTrue($validation->date('7 2 2006', array('dmy')));
		$this->assertFalse($validation->date('0-0-0000', array('dmy')));
		$this->assertFalse($validation->date('0.0.0000', array('dmy')));
		$this->assertFalse($validation->date('0/0/0000', array('dmy')));
		$this->assertFalse($validation->date('0 0 0000', array('dmy')));
		$this->assertFalse($validation->date('32-2-2006', array('dmy')));
		$this->assertFalse($validation->date('32.2.2006', array('dmy')));
		$this->assertFalse($validation->date('32/2/2006', array('dmy')));
		$this->assertFalse($validation->date('32 2 2006', array('dmy')));
	}

	function testDateDmyyyyLeapYear() {
		$validation = new Validation();
		$this->assertTrue($validation->date('29-2-2004', array('dmy')));
		$this->assertTrue($validation->date('29.2.2004', array('dmy')));
		$this->assertTrue($validation->date('29/2/2004', array('dmy')));
		$this->assertTrue($validation->date('29 2 2004', array('dmy')));
		$this->assertFalse($validation->date('29-2-2006', array('dmy')));
		$this->assertFalse($validation->date('29.2.2006', array('dmy')));
		$this->assertFalse($validation->date('29/2/2006', array('dmy')));
		$this->assertFalse($validation->date('29 2 2006', array('dmy')));
	}

	function testDateMmddyyyy() {
		$validation = new Validation();
		$this->assertTrue($validation->date('12-27-2006', array('mdy')));
		$this->assertTrue($validation->date('12.27.2006', array('mdy')));
		$this->assertTrue($validation->date('12/27/2006', array('mdy')));
		$this->assertTrue($validation->date('12 27 2006', array('mdy')));
		$this->assertFalse($validation->date('00-00-0000', array('mdy')));
		$this->assertFalse($validation->date('00.00.0000', array('mdy')));
		$this->assertFalse($validation->date('00/00/0000', array('mdy')));
		$this->assertFalse($validation->date('00 00 0000', array('mdy')));
		$this->assertFalse($validation->date('11-31-2006', array('mdy')));
		$this->assertFalse($validation->date('11.31.2006', array('mdy')));
		$this->assertFalse($validation->date('11/31/2006', array('mdy')));
		$this->assertFalse($validation->date('11 31 2006', array('mdy')));
	}

	function testDateMmddyyyyLeapYear() {
		$validation = new Validation();
		$this->assertTrue($validation->date('02-29-2004', array('mdy')));
		$this->assertTrue($validation->date('02.29.2004', array('mdy')));
		$this->assertTrue($validation->date('02/29/2004', array('mdy')));
		$this->assertTrue($validation->date('02 29 2004', array('mdy')));
		$this->assertFalse($validation->date('02-29-2006', array('mdy')));
		$this->assertFalse($validation->date('02.29.2006', array('mdy')));
		$this->assertFalse($validation->date('02/29/2006', array('mdy')));
		$this->assertFalse($validation->date('02 29 2006', array('mdy')));
	}

	function testDateMmddyy() {
		$validation = new Validation();
		$this->assertTrue($validation->date('12-27-06', array('mdy')));
		$this->assertTrue($validation->date('12.27.06', array('mdy')));
		$this->assertTrue($validation->date('12/27/06', array('mdy')));
		$this->assertTrue($validation->date('12 27 06', array('mdy')));
		$this->assertFalse($validation->date('00-00-00', array('mdy')));
		$this->assertFalse($validation->date('00.00.00', array('mdy')));
		$this->assertFalse($validation->date('00/00/00', array('mdy')));
		$this->assertFalse($validation->date('00 00 00', array('mdy')));
		$this->assertFalse($validation->date('11-31-06', array('mdy')));
		$this->assertFalse($validation->date('11.31.06', array('mdy')));
		$this->assertFalse($validation->date('11/31/06', array('mdy')));
		$this->assertFalse($validation->date('11 31 06', array('mdy')));
	}

	function testDateMmddyyLeapYear() {
		$validation = new Validation();
		$this->assertTrue($validation->date('02-29-04', array('mdy')));
		$this->assertTrue($validation->date('02.29.04', array('mdy')));
		$this->assertTrue($validation->date('02/29/04', array('mdy')));
		$this->assertTrue($validation->date('02 29 04', array('mdy')));
		$this->assertFalse($validation->date('02-29-06', array('mdy')));
		$this->assertFalse($validation->date('02.29.06', array('mdy')));
		$this->assertFalse($validation->date('02/29/06', array('mdy')));
		$this->assertFalse($validation->date('02 29 06', array('mdy')));
	}

	function testDateMdyy() {
		$validation = new Validation();
		$this->assertTrue($validation->date('2-7-06', array('mdy')));
		$this->assertTrue($validation->date('2.7.06', array('mdy')));
		$this->assertTrue($validation->date('2/7/06', array('mdy')));
		$this->assertTrue($validation->date('2 7 06', array('mdy')));
		$this->assertFalse($validation->date('0-0-00', array('mdy')));
		$this->assertFalse($validation->date('0.0.00', array('mdy')));
		$this->assertFalse($validation->date('0/0/00', array('mdy')));
		$this->assertFalse($validation->date('0 0 00', array('mdy')));
		$this->assertFalse($validation->date('2-32-06', array('mdy')));
		$this->assertFalse($validation->date('2.32.06', array('mdy')));
		$this->assertFalse($validation->date('2/32/06', array('mdy')));
		$this->assertFalse($validation->date('2 32 06', array('mdy')));
	}

	function testDateMdyyLeapYear() {
		$validation = new Validation();
		$this->assertTrue($validation->date('2-29-04', array('mdy')));
		$this->assertTrue($validation->date('2.29.04', array('mdy')));
		$this->assertTrue($validation->date('2/29/04', array('mdy')));
		$this->assertTrue($validation->date('2 29 04', array('mdy')));
		$this->assertFalse($validation->date('2-29-06', array('mdy')));
		$this->assertFalse($validation->date('2.29.06', array('mdy')));
		$this->assertFalse($validation->date('2/29/06', array('mdy')));
		$this->assertFalse($validation->date('2 29 06', array('mdy')));
	}

	function testDateMdyyyy() {
		$validation = new Validation();
		$this->assertTrue($validation->date('2-7-2006', array('mdy')));
		$this->assertTrue($validation->date('2.7.2006', array('mdy')));
		$this->assertTrue($validation->date('2/7/2006', array('mdy')));
		$this->assertTrue($validation->date('2 7 2006', array('mdy')));
		$this->assertFalse($validation->date('0-0-0000', array('mdy')));
		$this->assertFalse($validation->date('0.0.0000', array('mdy')));
		$this->assertFalse($validation->date('0/0/0000', array('mdy')));
		$this->assertFalse($validation->date('0 0 0000', array('mdy')));
		$this->assertFalse($validation->date('2-32-2006', array('mdy')));
		$this->assertFalse($validation->date('2.32.2006', array('mdy')));
		$this->assertFalse($validation->date('2/32/2006', array('mdy')));
		$this->assertFalse($validation->date('2 32 2006', array('mdy')));
	}

	function testDateMdyyyyLeapYear() {
		$validation = new Validation();
		$this->assertTrue($validation->date('2-29-2004', array('mdy')));
		$this->assertTrue($validation->date('2.29.2004', array('mdy')));
		$this->assertTrue($validation->date('2/29/2004', array('mdy')));
		$this->assertTrue($validation->date('2 29 2004', array('mdy')));
		$this->assertFalse($validation->date('2-29-2006', array('mdy')));
		$this->assertFalse($validation->date('2.29.2006', array('mdy')));
		$this->assertFalse($validation->date('2/29/2006', array('mdy')));
		$this->assertFalse($validation->date('2 29 2006', array('mdy')));
	}

	function testDateYyyymmdd() {
		$validation = new Validation();
		$this->assertTrue($validation->date('2006-12-27', array('ymd')));
		$this->assertTrue($validation->date('2006.12.27', array('ymd')));
		$this->assertTrue($validation->date('2006/12/27', array('ymd')));
		$this->assertTrue($validation->date('2006 12 27', array('ymd')));
		$this->assertFalse($validation->date('2006-11-31', array('ymd')));
		$this->assertFalse($validation->date('2006.11.31', array('ymd')));
		$this->assertFalse($validation->date('2006/11/31', array('ymd')));
		$this->assertFalse($validation->date('2006 11 31', array('ymd')));
	}

	function testDateYyyymmddLeapYear() {
		$validation = new Validation();
		$this->assertTrue($validation->date('2004-02-29', array('ymd')));
		$this->assertTrue($validation->date('2004.02.29', array('ymd')));
		$this->assertTrue($validation->date('2004/02/29', array('ymd')));
		$this->assertTrue($validation->date('2004 02 29', array('ymd')));
		$this->assertFalse($validation->date('2006-02-29', array('ymd')));
		$this->assertFalse($validation->date('2006.02.29', array('ymd')));
		$this->assertFalse($validation->date('2006/02/29', array('ymd')));
		$this->assertFalse($validation->date('2006 02 29', array('ymd')));
	}

	function testDateYymmdd() {
		$validation = new Validation();
		$this->assertTrue($validation->date('06-12-27', array('ymd')));
		$this->assertTrue($validation->date('06.12.27', array('ymd')));
		$this->assertTrue($validation->date('06/12/27', array('ymd')));
		$this->assertTrue($validation->date('06 12 27', array('ymd')));
		$this->assertFalse($validation->date('12/27/2600', array('ymd')));
		$this->assertFalse($validation->date('12.27.2600', array('ymd')));
		$this->assertFalse($validation->date('12/27/2600', array('ymd')));
		$this->assertFalse($validation->date('12 27 2600', array('ymd')));
		$this->assertFalse($validation->date('06-11-31', array('ymd')));
		$this->assertFalse($validation->date('06.11.31', array('ymd')));
		$this->assertFalse($validation->date('06/11/31', array('ymd')));
		$this->assertFalse($validation->date('06 11 31', array('ymd')));
	}

	function testDateYymmddLeapYear() {
		$validation = new Validation();
		$this->assertTrue($validation->date('2004-02-29', array('ymd')));
		$this->assertTrue($validation->date('2004.02.29', array('ymd')));
		$this->assertTrue($validation->date('2004/02/29', array('ymd')));
		$this->assertTrue($validation->date('2004 02 29', array('ymd')));
		$this->assertFalse($validation->date('2006-02-29', array('ymd')));
		$this->assertFalse($validation->date('2006.02.29', array('ymd')));
		$this->assertFalse($validation->date('2006/02/29', array('ymd')));
		$this->assertFalse($validation->date('2006 02 29', array('ymd')));
	}

	function testDateDdMMMMyyyy() {
		$validation = new Validation();
		$this->assertTrue($validation->date('27 December 2006', array('dMy')));
		$this->assertTrue($validation->date('27 Dec 2006', array('dMy')));
		$this->assertFalse($validation->date('2006 Dec 27', array('dMy')));
		$this->assertFalse($validation->date('2006 December 27', array('dMy')));
	}

	function testDateDdMMMMyyyyLeapYear() {
		$validation = new Validation();
		$this->assertTrue($validation->date('29 February 2004', array('dMy')));
		$this->assertFalse($validation->date('29 February 2006', array('dMy')));
	}

	function testDateMmmmDdyyyy() {
		$validation = new Validation();
		$this->assertTrue($validation->date('December 27, 2006', array('Mdy')));
		$this->assertTrue($validation->date('Dec 27, 2006', array('Mdy')));
		$this->assertTrue($validation->date('December 27 2006', array('Mdy')));
		$this->assertTrue($validation->date('Dec 27 2006', array('Mdy')));
		$this->assertFalse($validation->date('27 Dec 2006', array('Mdy')));
		$this->assertFalse($validation->date('2006 December 27', array('Mdy')));
	}

	function testDateMmmmDdyyyyLeapYear() {
		$validation = new Validation();
		$this->assertTrue($validation->date('February 29, 2004', array('Mdy')));
		$this->assertTrue($validation->date('Feb 29, 2004', array('Mdy')));
		$this->assertTrue($validation->date('February 29 2004', array('Mdy')));
		$this->assertTrue($validation->date('Feb 29 2004', array('Mdy')));
		$this->assertFalse($validation->date('February 29, 2006', array('Mdy')));
	}

	function testDateMy() {
		$validation = new Validation();
		$this->assertTrue($validation->date('December 2006', array('My')));
		$this->assertTrue($validation->date('Dec 2006', array('My')));
		$this->assertTrue($validation->date('December/2006', array('My')));
		$this->assertTrue($validation->date('Dec/2006', array('My')));
	}

	function testDateMyNumeric() {
		$validation = new Validation();
		$this->assertTrue($validation->date('12/2006', array('my')));
		$this->assertTrue($validation->date('12-2006', array('my')));
		$this->assertTrue($validation->date('12.2006', array('my')));
		$this->assertTrue($validation->date('12 2006', array('my')));
		$this->assertFalse($validation->date('12/06', array('my')));
		$this->assertFalse($validation->date('12-06', array('my')));
		$this->assertFalse($validation->date('12.06', array('my')));
		$this->assertFalse($validation->date('12 06', array('my')));
	}

	function testDateCustomRegx() {
		$validation = new Validation();
		$this->assertTrue($validation->date('2006-12-27', null, '%^(19|20)[0-9]{2}[- /.](0[1-9]|1[012])[- /.](0[1-9]|[12][0-9]|3[01])$%'));
		$this->assertFalse($validation->date('12-27-2006', null, '%^(19|20)[0-9]{2}[- /.](0[1-9]|1[012])[- /.](0[1-9]|[12][0-9]|3[01])$%'));
	}

	function testDecimal() {
		$validation = new Validation();
		$this->assertTrue($validation->decimal('+1234.54321'));
		$this->assertTrue($validation->decimal('-1234.54321'));
		$this->assertTrue($validation->decimal('1234.54321'));
		$this->assertTrue($validation->decimal('+0123.45e6'));
		$this->assertTrue($validation->decimal('-0123.45e6'));
		$this->assertTrue($validation->decimal('0123.45e6'));
		$this->assertFalse($validation->decimal('string'));
		$this->assertFalse($validation->decimal('1234'));
		$this->assertFalse($validation->decimal('-1234'));
		$this->assertFalse($validation->decimal('+1234'));
	}

	function testDecimalWithPlaces() {
		$validation = new Validation();
		$this->assertTrue($validation->decimal('.27', '2'));
		$this->assertTrue($validation->decimal(.27, 2));
		$this->assertTrue($validation->decimal(-.27, 2));
		$this->assertTrue($validation->decimal(+.27, 2));
		$this->assertTrue($validation->decimal('.277', '3'));
		$this->assertTrue($validation->decimal(.277, 3));
		$this->assertTrue($validation->decimal(-.277, 3));
		$this->assertTrue($validation->decimal(+.277, 3));
		$this->assertTrue($validation->decimal('1234.5678', '4'));
		$this->assertTrue($validation->decimal(1234.5678, 4));
		$this->assertTrue($validation->decimal(-1234.5678, 4));
		$this->assertTrue($validation->decimal(+1234.5678, 4));
		$this->assertFalse($validation->decimal('1234.5678', '3'));
		$this->assertFalse($validation->decimal(1234.5678, 3));
		$this->assertFalse($validation->decimal(-1234.5678, 3));
		$this->assertFalse($validation->decimal(+1234.5678, 3));
	}

	function testDecimalCustomRegex() {
		$validation = new Validation();
		$this->assertTrue($validation->decimal('1.54321', null, '/^[-+]?[0-9]+(\\.[0-9]+)?$/s'));
		$this->assertFalse($validation->decimal('.54321', null, '/^[-+]?[0-9]+(\\.[0-9]+)?$/s'));
	}

	function testEmail() {
		$validation = new Validation();
		$this->assertTrue($validation->email('abc.efg@domain.com'));
		$this->assertTrue($validation->email('efg@domain.com'));
		$this->assertTrue($validation->email('abc-efg@domain.com'));
		$this->assertTrue($validation->email('abc_efg@domain.com'));
		$this->assertFalse($validation->email('abc@efg@domain.com'));
	}
/*
	//Commented out because test is slow, but it does work
	function testEmailDeep() {
		$validation = new Validation();
		$this->assertTrue($validation->email('abc.efg@cakephp.org', true), 'Expected valid email');
		$this->assertFalse($validation->email('abc.efg@caphpkeinvalid.com', true), 'Expected valid email');
	}
*/
	function testEmailCustomRegex() {
		$validation = new Validation();
		$this->assertTrue($validation->email('abc.efg@cakephp.org', null, '/^[A-Z0-9._%-]+@[A-Z0-9.-]+\\.[A-Z]{2,4}$/i'));
		$this->assertFalse($validation->email('abc.efg@com.caphpkeinvalid', null, '/^[A-Z0-9._%-]+@[A-Z0-9.-]+\\.[A-Z]{2,4}$/i'));
	}

	function testIp() {
		$validation = new Validation();
		$this->assertTrue($validation->ip('0.0.0.0'));
		$this->assertTrue($validation->ip('192.168.1.156'));
		$this->assertTrue($validation->ip('255.255.255.255'));
		$this->assertFalse($validation->ip('127.0.0'));
		$this->assertFalse($validation->ip('127.0.0.a'));
		$this->assertFalse($validation->ip('127.0.0.256'));
	}

	function testMaxLength() {
		$validation = new Validation();
		$this->assertTrue($validation->maxLength('ab', 3));
		$this->assertTrue($validation->maxLength('abc', 3));
		$this->assertFalse($validation->maxLength('abcd', 3));
	}

	function testMinLength() {
		$validation = new Validation();
		$this->assertFalse($validation->minLength('ab', 3));
		$this->assertTrue($validation->minLength('abc', 3));
		$this->assertTrue($validation->minLength('abcd', 3));
	}

	function testUrl() {
		$validation = new Validation();
		$this->assertTrue($validation->url('https://my.gizmoproject.com/gizmo/app?class=MySip;proc=start'));
	}
		function testValidNumber() {
		$validation = new Validation();
		$this->assertTrue($validation->custom('12345', VALID_NUMBER));
		$this->assertTrue($validation->custom('-12345', VALID_NUMBER));
		$this->assertTrue($validation->custom('+12345', VALID_NUMBER));
		$this->assertFalse($validation->custom('--12345', VALID_NUMBER));
		$this->assertFalse($validation->custom('++12345', VALID_NUMBER));
		$this->assertFalse($validation->custom('a12345', VALID_NUMBER));
		$this->assertFalse($validation->custom('12345z', VALID_NUMBER));
		$this->assertFalse($validation->custom('-a12345z', VALID_NUMBER));
		$this->assertFalse($validation->custom('-', VALID_NUMBER));
		$this->assertFalse($validation->custom('123-12345', VALID_NUMBER));
		$this->assertTrue($validation->custom('1.2345', VALID_NUMBER));
		$this->assertTrue($validation->custom('-1.2345', VALID_NUMBER));
		$this->assertTrue($validation->custom('+1.2345', VALID_NUMBER));
		$this->assertFalse($validation->custom('1..2345', VALID_NUMBER));
		$this->assertFalse($validation->custom('-1..2345', VALID_NUMBER));
		$this->assertFalse($validation->custom('+1..2345', VALID_NUMBER));
		$this->assertFalse($validation->custom('.2345', VALID_NUMBER));
		$this->assertFalse($validation->custom('12345.', VALID_NUMBER));
	}
}
?>