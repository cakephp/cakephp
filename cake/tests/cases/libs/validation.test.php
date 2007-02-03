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

		$this->assertTrue($validation->alphaNumeric('frferrf'), 'Expected only letters');
		$this->assertTrue($validation->alphaNumeric('12234'), 'Expected only integer');
		$this->assertTrue($validation->alphaNumeric('1w2e2r3t4y'), 'Expected letters and integers');
		$this->assertFalse($validation->alphaNumeric('12 234'), 'Expected integers mixed with spaces');
		$this->assertFalse($validation->alphaNumeric('dfd 234'), 'Expected letters and integers with space');
		$this->assertFalse($validation->alphaNumeric("\n"), 'Expected new line');
		$this->assertFalse($validation->alphaNumeric("\t"), 'Expected tab');
		$this->assertFalse($validation->alphaNumeric("\r"), 'Expected new line');
		$this->assertFalse($validation->alphaNumeric(' '), 'Expected new space');
	}

	function testAlphaNumericPassedAsArray(){
		$validation = new Validation();

		$this->assertTrue($validation->alphaNumeric(array('check' => 'frferrf'), 'Expected only letters'));
		$this->assertTrue($validation->alphaNumeric(array('check' => '12234'), 'Expected only integer'));
		$this->assertTrue($validation->alphaNumeric(array('check' => '1w2e2r3t4y'), 'Expected letters and integers'));
		$this->assertFalse($validation->alphaNumeric(array('check' => '12 234'), 'Expected integers mixed with spaces'));
		$this->assertFalse($validation->alphaNumeric(array('check' => 'dfd 234'), 'Expected letters and integers with space'));
		$this->assertFalse($validation->alphaNumeric(array('check' => "\n"), 'Expected new line'));
		$this->assertFalse($validation->alphaNumeric(array('check' => "\t"), 'Expected tab'));
		$this->assertFalse($validation->alphaNumeric(array('check' => "\r"), 'Expected new line'));
		$this->assertFalse($validation->alphaNumeric(array('check' =>  ' '), 'Expected space'));
	}

	function testBetween(){
		$validation = new Validation();
		$this->assertTrue($validation->between('abcdefg', 1, 7), 'Expected string to be between 1 and 7 chars, 7 sent');
		$this->assertTrue($validation->between('', 0, 7), 'Expected either empty string or up to 7 chars, empty string sent');
		$this->assertFalse($validation->between('abcdefg', 1, 6), 'Expected 1 and 6 chars, 7 sent');
	}

	function testBlank(){
		$validation = new Validation();
		$this->assertTrue($validation->blank(''), 'Expected empty string');
		$this->assertTrue($validation->blank(' '), 'Expected space');
		$this->assertTrue($validation->blank("\n"), 'Expected new line');
		$this->assertTrue($validation->blank("\t"), 'Expected tab');
		$this->assertTrue($validation->blank("\r"), 'Expected new line');
		$this->assertFalse($validation->blank('    Blank'), 'Expected empty string, Blank was sent');
		$this->assertFalse($validation->blank('Blank'), 'Expected empty string, Blank was sent');
	}

	function testBlankAsArray(){
		$validation = new Validation();
		$this->assertTrue($validation->blank(array('check' => ''), 'Expected empty string'));
		$this->assertTrue($validation->blank(array('check' => ' '), 'Expected space'));
		$this->assertTrue($validation->blank(array('check' => "\n"), 'Expected new line'));
		$this->assertTrue($validation->blank(array('check' => "\t"), 'Expected tab'));
		$this->assertTrue($validation->blank(array('check' => "\r"), 'Expected new line'));
		$this->assertFalse($validation->blank(array('check' => '    Blank'), 'Expected empty string, Blank was sent'));
		$this->assertFalse($validation->blank(array('check' => 'Blank'), 'Expected empty string, Blank was sent'));
	}

	function testcc(){
		$validation = new Validation();
		//American Express
		$this->assertTrue($validation->cc('370482756063980', array('amex')), 'Expected American Express');
		$this->assertTrue($validation->cc('349106433773483', array('amex')), 'Expected American Express');
		$this->assertTrue($validation->cc('344671486204764', array('amex')), 'Expected American Express');
		$this->assertTrue($validation->cc('344042544509943', array('amex')), 'Expected American Express');
		$this->assertTrue($validation->cc('377147515754475', array('amex')), 'Expected American Express');
		$this->assertTrue($validation->cc('375239372816422', array('amex')), 'Expected American Express');
		$this->assertTrue($validation->cc('376294341957707', array('amex')), 'Expected American Express');
		$this->assertTrue($validation->cc('341779292230411', array('amex')), 'Expected American Express');
		$this->assertTrue($validation->cc('341646919853372', array('amex')), 'Expected American Express');
		$this->assertTrue($validation->cc('348498616319346', array('amex')), 'Expected American Express');
		//BankCard
		$this->assertTrue($validation->cc('5610745867413420', array('bankcard')), 'Expected BankCard');
		$this->assertTrue($validation->cc('5610376649499352', array('bankcard')), 'Expected BankCard');
		$this->assertTrue($validation->cc('5610091936000694', array('bankcard')), 'Expected BankCard');
		$this->assertTrue($validation->cc('5602248780118788', array('bankcard')), 'Expected BankCard');
		$this->assertTrue($validation->cc('5610631567676765', array('bankcard')), 'Expected BankCard');
		$this->assertTrue($validation->cc('5602238211270795', array('bankcard')), 'Expected BankCard');
		$this->assertTrue($validation->cc('5610173951215470', array('bankcard')), 'Expected BankCard');
		$this->assertTrue($validation->cc('5610139705753702', array('bankcard')), 'Expected BankCard');
		$this->assertTrue($validation->cc('5602226032150551', array('bankcard')), 'Expected BankCard');
		$this->assertTrue($validation->cc('5602223993735777', array('bankcard')), 'Expected BankCard');
		//Diners Club 14
		$this->assertTrue($validation->cc('30155483651028', array('diners')), 'Expected Diners Club 14 digit');
		$this->assertTrue($validation->cc('36371312803821', array('diners')), 'Expected Diners Club 14 digit');
		$this->assertTrue($validation->cc('38801277489875', array('diners')), 'Expected Diners Club 14 digit');
		$this->assertTrue($validation->cc('30348560464296', array('diners')), 'Expected Diners Club 14 digit');
		$this->assertTrue($validation->cc('30349040317708', array('diners')), 'Expected Diners Club 14 digit');
		$this->assertTrue($validation->cc('36567413559978', array('diners')), 'Expected Diners Club 14 digit');
		$this->assertTrue($validation->cc('36051554732702', array('diners')), 'Expected Diners Club 14 digit');
		$this->assertTrue($validation->cc('30391842198191', array('diners')), 'Expected Diners Club 14 digit');
		$this->assertTrue($validation->cc('30172682197745', array('diners')), 'Expected Diners Club 14 digit');
		$this->assertTrue($validation->cc('30162056566641', array('diners')), 'Expected Diners Club 14 digit');
		$this->assertTrue($validation->cc('30085066927745', array('diners')), 'Expected Diners Club 14 digit');
		$this->assertTrue($validation->cc('36519025221976', array('diners')), 'Expected Diners Club 14 digit');
		$this->assertTrue($validation->cc('30372679371044', array('diners')), 'Expected Diners Club 14 digit');
		$this->assertTrue($validation->cc('38913939150124', array('diners')), 'Expected Diners Club 14 digit');
		$this->assertTrue($validation->cc('36852899094637', array('diners')), 'Expected Diners Club 14 digit');
		$this->assertTrue($validation->cc('30138041971120', array('diners')), 'Expected Diners Club 14 digit');
		$this->assertTrue($validation->cc('36184047836838', array('diners')), 'Expected Diners Club 14 digit');
		$this->assertTrue($validation->cc('30057460264462', array('diners')), 'Expected Diners Club 14 digit');
		$this->assertTrue($validation->cc('38980165212050', array('diners')), 'Expected Diners Club 14 digit');
		$this->assertTrue($validation->cc('30356516881240', array('diners')), 'Expected Diners Club 14 digit');
		$this->assertTrue($validation->cc('38744810033182', array('diners')), 'Expected Diners Club 14 digit');
		$this->assertTrue($validation->cc('30173638706621', array('diners')), 'Expected Diners Club 14 digit');
		$this->assertTrue($validation->cc('30158334709185', array('diners')), 'Expected Diners Club 14 digit');
		$this->assertTrue($validation->cc('30195413721186', array('diners')), 'Expected Diners Club 14 digit');
		$this->assertTrue($validation->cc('38863347694793', array('diners')), 'Expected Diners Club 14 digit');
		$this->assertTrue($validation->cc('30275627009113', array('diners')), 'Expected Diners Club 14 digit');
		$this->assertTrue($validation->cc('30242860404971', array('diners')), 'Expected Diners Club 14 digit');
		$this->assertTrue($validation->cc('30081877595151', array('diners')), 'Expected Diners Club 14 digit');
		$this->assertTrue($validation->cc('38053196067461', array('diners')), 'Expected Diners Club 14 digit');
		$this->assertTrue($validation->cc('36520379984870', array('diners')), 'Expected Diners Club 14 digit');
		//2004 MasterCard/Diners Club Alliance International 14
		$this->assertTrue($validation->cc('36747701998969', array('diners')), 'Expected MasterCard/Diners Club International 14 digit');
		$this->assertTrue($validation->cc('36427861123159', array('diners')), 'Expected MasterCard/Diners Club International 14 digit');
		$this->assertTrue($validation->cc('36150537602386', array('diners')), 'Expected MasterCard/Diners Club International 14 digit');
		$this->assertTrue($validation->cc('36582388820610', array('diners')), 'Expected MasterCard/Diners Club International 14 digit');
		$this->assertTrue($validation->cc('36729045250216', array('diners')), 'Expected MasterCard/Diners Club International 14 digit');
		//2004 MasterCard/Diners Club Alliance US & Canada 16
		$this->assertTrue($validation->cc('5597511346169950', array('diners')), 'Expected MasterCard/Diners Club US & Canada 16 digit');
		$this->assertTrue($validation->cc('5526443162217562', array('diners')), 'Expected MasterCard/Diners Club US & Canada 16 digit');
		$this->assertTrue($validation->cc('5577265786122391', array('diners')), 'Expected MasterCard/Diners Club US & Canada 16 digit');
		$this->assertTrue($validation->cc('5534061404676989', array('diners')), 'Expected MasterCard/Diners Club US & Canada 16 digit');
		$this->assertTrue($validation->cc('5545313588374502', array('diners')), 'Expected MasterCard/Diners Club US & Canada 16 digit');
		//Discover
		$this->assertTrue($validation->cc('6011802876467237', array('disc')), 'Expected Discover');
		$this->assertTrue($validation->cc('6506432777720955', array('disc')), 'Expected Discover');
		$this->assertTrue($validation->cc('6011126265283942', array('disc')), 'Expected Discover');
		$this->assertTrue($validation->cc('6502187151579252', array('disc')), 'Expected Discover');
		$this->assertTrue($validation->cc('6506600836002298', array('disc')), 'Expected Discover');
		$this->assertTrue($validation->cc('6504376463615189', array('disc')), 'Expected Discover');
		$this->assertTrue($validation->cc('6011440907005377', array('disc')), 'Expected Discover');
		$this->assertTrue($validation->cc('6509735979634270', array('disc')), 'Expected Discover');
		$this->assertTrue($validation->cc('6011422366775856', array('disc')), 'Expected Discover');
		$this->assertTrue($validation->cc('6500976374623323', array('disc')), 'Expected Discover');
		//enRoute
		$this->assertTrue($validation->cc('201496944158937', array('enroute')), 'Expected enRoute');
		$this->assertTrue($validation->cc('214945833739665', array('enroute')), 'Expected enRoute');
		$this->assertTrue($validation->cc('214982692491187', array('enroute')), 'Expected enRoute');
		$this->assertTrue($validation->cc('214901395949424', array('enroute')), 'Expected enRoute');
		$this->assertTrue($validation->cc('201480676269187', array('enroute')), 'Expected enRoute');
		$this->assertTrue($validation->cc('214911922887807', array('enroute')), 'Expected enRoute');
		$this->assertTrue($validation->cc('201485025457250', array('enroute')), 'Expected enRoute');
		$this->assertTrue($validation->cc('201402662758866', array('enroute')), 'Expected enRoute');
		$this->assertTrue($validation->cc('214981579370225', array('enroute')), 'Expected enRoute');
		$this->assertTrue($validation->cc('201447595859877', array('enroute')), 'Expected enRoute');
		//JCB 15 digit
		$this->assertTrue($validation->cc('210034762247893', array('jcb')), 'Expected JCB 15 digit');
		$this->assertTrue($validation->cc('180078671678892', array('jcb')), 'Expected JCB 15 digit');
		$this->assertTrue($validation->cc('180010559353736', array('jcb')), 'Expected JCB 15 digit');
		$this->assertTrue($validation->cc('210095474464258', array('jcb')), 'Expected JCB 15 digit');
		$this->assertTrue($validation->cc('210006675562188', array('jcb')), 'Expected JCB 15 digit');
		$this->assertTrue($validation->cc('210063299662662', array('jcb')), 'Expected JCB 15 digit');
		$this->assertTrue($validation->cc('180032506857825', array('jcb')), 'Expected JCB 15 digit');
		$this->assertTrue($validation->cc('210057919192738', array('jcb')), 'Expected JCB 15 digit');
		$this->assertTrue($validation->cc('180031358949367', array('jcb')), 'Expected JCB 15 digit');
		$this->assertTrue($validation->cc('180033802147846', array('jcb')), 'Expected JCB 15 digit');
		//JCB 16 digit
		$this->assertTrue($validation->cc('3096806857839939', array('jcb')), 'Expected JCB 16 digit');
		$this->assertTrue($validation->cc('3158699503187091', array('jcb')), 'Expected JCB 16 digit');
		$this->assertTrue($validation->cc('3112549607186579', array('jcb')), 'Expected JCB 16 digit');
		$this->assertTrue($validation->cc('3112332922425604', array('jcb')), 'Expected JCB 16 digit');
		$this->assertTrue($validation->cc('3112001541159239', array('jcb')), 'Expected JCB 16 digit');
		$this->assertTrue($validation->cc('3112162495317841', array('jcb')), 'Expected JCB 16 digit');
		$this->assertTrue($validation->cc('3337562627732768', array('jcb')), 'Expected JCB 16 digit');
		$this->assertTrue($validation->cc('3337107161330775', array('jcb')), 'Expected JCB 16 digit');
		$this->assertTrue($validation->cc('3528053736003621', array('jcb')), 'Expected JCB 16 digit');
		$this->assertTrue($validation->cc('3528915255020360', array('jcb')), 'Expected JCB 16 digit');
		$this->assertTrue($validation->cc('3096786059660921', array('jcb')), 'Expected JCB 16 digit');
		$this->assertTrue($validation->cc('3528264799292320', array('jcb')), 'Expected JCB 16 digit');
		$this->assertTrue($validation->cc('3096469164130136', array('jcb')), 'Expected JCB 16 digit');
		$this->assertTrue($validation->cc('3112127443822853', array('jcb')), 'Expected JCB 16 digit');
		$this->assertTrue($validation->cc('3096849995802328', array('jcb')), 'Expected JCB 16 digit');
		$this->assertTrue($validation->cc('3528090735127407', array('jcb')), 'Expected JCB 16 digit');
		$this->assertTrue($validation->cc('3112101006819234', array('jcb')), 'Expected JCB 16 digit');
		$this->assertTrue($validation->cc('3337444428040784', array('jcb')), 'Expected JCB 16 digit');
		$this->assertTrue($validation->cc('3088043154151061', array('jcb')), 'Expected JCB 16 digit');
		$this->assertTrue($validation->cc('3088295969414866', array('jcb')), 'Expected JCB 16 digit');
		$this->assertTrue($validation->cc('3158748843158575', array('jcb')), 'Expected JCB 16 digit');
		$this->assertTrue($validation->cc('3158709206148538', array('jcb')), 'Expected JCB 16 digit');
		$this->assertTrue($validation->cc('3158365159575324', array('jcb')), 'Expected JCB 16 digit');
		$this->assertTrue($validation->cc('3158671691305165', array('jcb')), 'Expected JCB 16 digit');
		$this->assertTrue($validation->cc('3528523028771093', array('jcb')), 'Expected JCB 16 digit');
		$this->assertTrue($validation->cc('3096057126267870', array('jcb')), 'Expected JCB 16 digit');
		$this->assertTrue($validation->cc('3158514047166834', array('jcb')), 'Expected JCB 16 digit');
		$this->assertTrue($validation->cc('3528274546125962', array('jcb')), 'Expected JCB 16 digit');
		$this->assertTrue($validation->cc('3528890967705733', array('jcb')), 'Expected JCB 16 digit');
		$this->assertTrue($validation->cc('3337198811307545', array('jcb')), 'Expected JCB 16 digit');
		//Maestro (debit card)
		$this->assertTrue($validation->cc('5020147409985219', array('maestro')), 'Expected Maestro (debit card)');
		$this->assertTrue($validation->cc('5020931809905616', array('maestro')), 'Expected Maestro (debit card)');
		$this->assertTrue($validation->cc('5020412965470224', array('maestro')), 'Expected Maestro (debit card)');
		$this->assertTrue($validation->cc('5020129740944022', array('maestro')), 'Expected Maestro (debit card)');
		$this->assertTrue($validation->cc('5020024696747943', array('maestro')), 'Expected Maestro (debit card)');
		$this->assertTrue($validation->cc('5020581514636509', array('maestro')), 'Expected Maestro (debit card)');
		$this->assertTrue($validation->cc('5020695008411987', array('maestro')), 'Expected Maestro (debit card)');
		$this->assertTrue($validation->cc('5020565359718977', array('maestro')), 'Expected Maestro (debit card)');
		$this->assertTrue($validation->cc('6339931536544062', array('maestro')), 'Expected Maestro (debit card)');
		$this->assertTrue($validation->cc('6465028615704406', array('maestro')), 'Expected Maestro (debit card)');
		//Mastercard
		$this->assertTrue($validation->cc('5580424361774366', array('mc')), 'Expected Mastercard)');
		$this->assertTrue($validation->cc('5589563059318282', array('mc')), 'Expected Mastercard)');
		$this->assertTrue($validation->cc('5387558333690047', array('mc')), 'Expected Mastercard)');
		$this->assertTrue($validation->cc('5163919215247175', array('mc')), 'Expected Mastercard)');
		$this->assertTrue($validation->cc('5386742685055055', array('mc')), 'Expected Mastercard)');
		$this->assertTrue($validation->cc('5102303335960674', array('mc')), 'Expected Mastercard)');
		$this->assertTrue($validation->cc('5526543403964565', array('mc')), 'Expected Mastercard)');
		$this->assertTrue($validation->cc('5538725892618432', array('mc')), 'Expected Mastercard)');
		$this->assertTrue($validation->cc('5119543573129778', array('mc')), 'Expected Mastercard)');
		$this->assertTrue($validation->cc('5391174753915767', array('mc')), 'Expected Mastercard)');
		$this->assertTrue($validation->cc('5510994113980714', array('mc')), 'Expected Mastercard)');
		$this->assertTrue($validation->cc('5183720260418091', array('mc')), 'Expected Mastercard)');
		$this->assertTrue($validation->cc('5488082196086704', array('mc')), 'Expected Mastercard)');
		$this->assertTrue($validation->cc('5484645164161834', array('mc')), 'Expected Mastercard)');
		$this->assertTrue($validation->cc('5171254350337031', array('mc')), 'Expected Mastercard)');
		$this->assertTrue($validation->cc('5526987528136452', array('mc')), 'Expected Mastercard)');
		$this->assertTrue($validation->cc('5504148941409358', array('mc')), 'Expected Mastercard)');
		$this->assertTrue($validation->cc('5240793507243615', array('mc')), 'Expected Mastercard)');
		$this->assertTrue($validation->cc('5162114693017107', array('mc')), 'Expected Mastercard)');
		$this->assertTrue($validation->cc('5163104807404753', array('mc')), 'Expected Mastercard)');
		$this->assertTrue($validation->cc('5590136167248365', array('mc')), 'Expected Mastercard)');
		$this->assertTrue($validation->cc('5565816281038948', array('mc')), 'Expected Mastercard)');
		$this->assertTrue($validation->cc('5467639122779531', array('mc')), 'Expected Mastercard)');
		$this->assertTrue($validation->cc('5297350261550024', array('mc')), 'Expected Mastercard)');
		$this->assertTrue($validation->cc('5162739131368058', array('mc')), 'Expected Mastercard)');
		//Solo 16
		$this->assertTrue($validation->cc('6767432107064987', array('solo')), 'Expected Solo 16 digit');
		$this->assertTrue($validation->cc('6334667758225411', array('solo')), 'Expected Solo 16 digit');
		$this->assertTrue($validation->cc('6767037421954068', array('solo')), 'Expected Solo 16 digit');
		$this->assertTrue($validation->cc('6767823306394854', array('solo')), 'Expected Solo 16 digit');
		$this->assertTrue($validation->cc('6334768185398134', array('solo')), 'Expected Solo 16 digit');
		$this->assertTrue($validation->cc('6767286729498589', array('solo')), 'Expected Solo 16 digit');
		$this->assertTrue($validation->cc('6334972104431261', array('solo')), 'Expected Solo 16 digit');
		$this->assertTrue($validation->cc('6334843427400616', array('solo')), 'Expected Solo 16 digit');
		$this->assertTrue($validation->cc('6767493947881311', array('solo')), 'Expected Solo 16 digit');
		$this->assertTrue($validation->cc('6767194235798817', array('solo')), 'Expected Solo 16 digit');
		//Solo 18
		$this->assertTrue($validation->cc('676714834398858593', array('solo')), 'Expected Solo 18 digit');
		$this->assertTrue($validation->cc('676751666435130857', array('solo')), 'Expected Solo 18 digit');
		$this->assertTrue($validation->cc('676781908573924236', array('solo')), 'Expected Solo 18 digit');
		$this->assertTrue($validation->cc('633488724644003240', array('solo')), 'Expected Solo 18 digit');
		$this->assertTrue($validation->cc('676732252338067316', array('solo')), 'Expected Solo 18 digit');
		$this->assertTrue($validation->cc('676747520084495821', array('solo')), 'Expected Solo 18 digit');
		$this->assertTrue($validation->cc('633465488901381957', array('solo')), 'Expected Solo 18 digit');
		$this->assertTrue($validation->cc('633487484858610484', array('solo')), 'Expected Solo 18 digit');
		$this->assertTrue($validation->cc('633453764680740694', array('solo')), 'Expected Solo 18 digit');
		$this->assertTrue($validation->cc('676768613295414451', array('solo')), 'Expected Solo 18 digit');
		//Solo 19
		$this->assertTrue($validation->cc('6767838565218340113', array('solo')), 'Expected Solo 19 digit');
		$this->assertTrue($validation->cc('6767760119829705181', array('solo')), 'Expected Solo 19 digit');
		$this->assertTrue($validation->cc('6767265917091593668', array('solo')), 'Expected Solo 19 digit');
		$this->assertTrue($validation->cc('6767938856947440111', array('solo')), 'Expected Solo 19 digit');
		$this->assertTrue($validation->cc('6767501945697390076', array('solo')), 'Expected Solo 19 digit');
		$this->assertTrue($validation->cc('6334902868716257379', array('solo')), 'Expected Solo 19 digit');
		$this->assertTrue($validation->cc('6334922127686425532', array('solo')), 'Expected Solo 19 digit');
		$this->assertTrue($validation->cc('6334933119080706440', array('solo')), 'Expected Solo 19 digit');
		$this->assertTrue($validation->cc('6334647959628261714', array('solo')), 'Expected Solo 19 digit');
		$this->assertTrue($validation->cc('6334527312384101382', array('solo')), 'Expected Solo 19 digit');
		//Switch 16
		$this->assertTrue($validation->cc('5641829171515733', array('switch')), 'Expected Switch 16 digit');
		$this->assertTrue($validation->cc('5641824852820809', array('switch')), 'Expected Switch 16 digit');
		$this->assertTrue($validation->cc('6759129648956909', array('switch')), 'Expected Switch 16 digit');
		$this->assertTrue($validation->cc('6759626072268156', array('switch')), 'Expected Switch 16 digit');
		$this->assertTrue($validation->cc('5641822698388957', array('switch')), 'Expected Switch 16 digit');
		$this->assertTrue($validation->cc('5641827123105470', array('switch')), 'Expected Switch 16 digit');
		$this->assertTrue($validation->cc('5641823755819553', array('switch')), 'Expected Switch 16 digit');
		$this->assertTrue($validation->cc('5641821939587682', array('switch')), 'Expected Switch 16 digit');
		$this->assertTrue($validation->cc('4936097148079186', array('switch')), 'Expected Switch 16 digit');
		$this->assertTrue($validation->cc('5641829739125009', array('switch')), 'Expected Switch 16 digit');
		$this->assertTrue($validation->cc('5641822860725507', array('switch')), 'Expected Switch 16 digit');
		$this->assertTrue($validation->cc('4936717688865831', array('switch')), 'Expected Switch 16 digit');
		$this->assertTrue($validation->cc('6759487613615441', array('switch')), 'Expected Switch 16 digit');
		$this->assertTrue($validation->cc('5641821346840617', array('switch')), 'Expected Switch 16 digit');
		$this->assertTrue($validation->cc('5641825793417126', array('switch')), 'Expected Switch 16 digit');
		$this->assertTrue($validation->cc('5641821302759595', array('switch')), 'Expected Switch 16 digit');
		$this->assertTrue($validation->cc('6759784969918837', array('switch')), 'Expected Switch 16 digit');
		$this->assertTrue($validation->cc('5641824910667036', array('switch')), 'Expected Switch 16 digit');
		$this->assertTrue($validation->cc('6759139909636173', array('switch')), 'Expected Switch 16 digit');
		$this->assertTrue($validation->cc('6333425070638022', array('switch')), 'Expected Switch 16 digit');
		$this->assertTrue($validation->cc('5641823910382067', array('switch')), 'Expected Switch 16 digit');
		$this->assertTrue($validation->cc('4936295218139423', array('switch')), 'Expected Switch 16 digit');
		$this->assertTrue($validation->cc('6333031811316199', array('switch')), 'Expected Switch 16 digit');
		$this->assertTrue($validation->cc('4936912044763198', array('switch')), 'Expected Switch 16 digit');
		$this->assertTrue($validation->cc('4936387053303824', array('switch')), 'Expected Switch 16 digit');
		$this->assertTrue($validation->cc('6759535838760523', array('switch')), 'Expected Switch 16 digit');
		$this->assertTrue($validation->cc('6333427174594051', array('switch')), 'Expected Switch 16 digit');
		$this->assertTrue($validation->cc('5641829037102700', array('switch')), 'Expected Switch 16 digit');
		$this->assertTrue($validation->cc('5641826495463046', array('switch')), 'Expected Switch 16 digit');
		$this->assertTrue($validation->cc('6333480852979946', array('switch')), 'Expected Switch 16 digit');
		$this->assertTrue($validation->cc('5641827761302876', array('switch')), 'Expected Switch 16 digit');
		$this->assertTrue($validation->cc('5641825083505317', array('switch')), 'Expected Switch 16 digit');
		$this->assertTrue($validation->cc('6759298096003991', array('switch')), 'Expected Switch 16 digit');
		$this->assertTrue($validation->cc('4936119165483420', array('switch')), 'Expected Switch 16 digit');
		$this->assertTrue($validation->cc('4936190990500993', array('switch')), 'Expected Switch 16 digit');
		$this->assertTrue($validation->cc('4903356467384927', array('switch')), 'Expected Switch 16 digit');
		$this->assertTrue($validation->cc('6333372765092554', array('switch')), 'Expected Switch 16 digit');
		$this->assertTrue($validation->cc('5641821330950570', array('switch')), 'Expected Switch 16 digit');
		$this->assertTrue($validation->cc('6759841558826118', array('switch')), 'Expected Switch 16 digit');
		$this->assertTrue($validation->cc('4936164540922452', array('switch')), 'Expected Switch 16 digit');
		//Switch 18
		$this->assertTrue($validation->cc('493622764224625174', array('switch')), 'Expected Switch 18 digit');
		$this->assertTrue($validation->cc('564182823396913535', array('switch')), 'Expected Switch 18 digit');
		$this->assertTrue($validation->cc('675917308304801234', array('switch')), 'Expected Switch 18 digit');
		$this->assertTrue($validation->cc('675919890024220298', array('switch')), 'Expected Switch 18 digit');
		$this->assertTrue($validation->cc('633308376862556751', array('switch')), 'Expected Switch 18 digit');
		$this->assertTrue($validation->cc('564182377633208779', array('switch')), 'Expected Switch 18 digit');
		$this->assertTrue($validation->cc('564182870014926787', array('switch')), 'Expected Switch 18 digit');
		$this->assertTrue($validation->cc('675979788553829819', array('switch')), 'Expected Switch 18 digit');
		$this->assertTrue($validation->cc('493668394358130935', array('switch')), 'Expected Switch 18 digit');
		$this->assertTrue($validation->cc('493637431790930965', array('switch')), 'Expected Switch 18 digit');
		$this->assertTrue($validation->cc('633321438601941513', array('switch')), 'Expected Switch 18 digit');
		$this->assertTrue($validation->cc('675913800898840986', array('switch')), 'Expected Switch 18 digit');
		$this->assertTrue($validation->cc('564182592016841547', array('switch')), 'Expected Switch 18 digit');
		$this->assertTrue($validation->cc('564182428380440899', array('switch')), 'Expected Switch 18 digit');
		$this->assertTrue($validation->cc('493696376827623463', array('switch')), 'Expected Switch 18 digit');
		$this->assertTrue($validation->cc('675977939286485757', array('switch')), 'Expected Switch 18 digit');
		$this->assertTrue($validation->cc('490302699502091579', array('switch')), 'Expected Switch 18 digit');
		$this->assertTrue($validation->cc('564182085013662230', array('switch')), 'Expected Switch 18 digit');
		$this->assertTrue($validation->cc('493693054263310167', array('switch')), 'Expected Switch 18 digit');
		$this->assertTrue($validation->cc('633321755966697525', array('switch')), 'Expected Switch 18 digit');
		$this->assertTrue($validation->cc('675996851719732811', array('switch')), 'Expected Switch 18 digit');
		$this->assertTrue($validation->cc('493699211208281028', array('switch')), 'Expected Switch 18 digit');
		$this->assertTrue($validation->cc('493697817378356614', array('switch')), 'Expected Switch 18 digit');
		$this->assertTrue($validation->cc('675968224161768150', array('switch')), 'Expected Switch 18 digit');
		$this->assertTrue($validation->cc('493669416873337627', array('switch')), 'Expected Switch 18 digit');
		$this->assertTrue($validation->cc('564182439172549714', array('switch')), 'Expected Switch 18 digit');
		$this->assertTrue($validation->cc('675926914467673598', array('switch')), 'Expected Switch 18 digit');
		$this->assertTrue($validation->cc('564182565231977809', array('switch')), 'Expected Switch 18 digit');
		$this->assertTrue($validation->cc('675966282607849002', array('switch')), 'Expected Switch 18 digit');
		$this->assertTrue($validation->cc('493691609704348548', array('switch')), 'Expected Switch 18 digit');
		$this->assertTrue($validation->cc('675933118546065120', array('switch')), 'Expected Switch 18 digit');
		$this->assertTrue($validation->cc('493631116677238592', array('switch')), 'Expected Switch 18 digit');
		$this->assertTrue($validation->cc('675921142812825938', array('switch')), 'Expected Switch 18 digit');
		$this->assertTrue($validation->cc('633338311815675113', array('switch')), 'Expected Switch 18 digit');
		$this->assertTrue($validation->cc('633323539867338621', array('switch')), 'Expected Switch 18 digit');
		$this->assertTrue($validation->cc('675964912740845663', array('switch')), 'Expected Switch 18 digit');
		$this->assertTrue($validation->cc('633334008833727504', array('switch')), 'Expected Switch 18 digit');
		$this->assertTrue($validation->cc('493631941273687169', array('switch')), 'Expected Switch 18 digit');
		$this->assertTrue($validation->cc('564182971729706785', array('switch')), 'Expected Switch 18 digit');
		$this->assertTrue($validation->cc('633303461188963496', array('switch')), 'Expected Switch 18 digit');
		//Switch 19
		$this->assertTrue($validation->cc('6759603460617628716', array('switch')), 'Expected Switch 19 digit');
		$this->assertTrue($validation->cc('4936705825268647681', array('switch')), 'Expected Switch 19 digit');
		$this->assertTrue($validation->cc('5641829846600479183', array('switch')), 'Expected Switch 19 digit');
		$this->assertTrue($validation->cc('6759389846573792530', array('switch')), 'Expected Switch 19 digit');
		$this->assertTrue($validation->cc('4936189558712637603', array('switch')), 'Expected Switch 19 digit');
		$this->assertTrue($validation->cc('5641822217393868189', array('switch')), 'Expected Switch 19 digit');
		$this->assertTrue($validation->cc('4903075563780057152', array('switch')), 'Expected Switch 19 digit');
		$this->assertTrue($validation->cc('4936510653566569547', array('switch')), 'Expected Switch 19 digit');
		$this->assertTrue($validation->cc('4936503083627303364', array('switch')), 'Expected Switch 19 digit');
		$this->assertTrue($validation->cc('4936777334398116272', array('switch')), 'Expected Switch 19 digit');
		$this->assertTrue($validation->cc('5641823876900554860', array('switch')), 'Expected Switch 19 digit');
		$this->assertTrue($validation->cc('6759619236903407276', array('switch')), 'Expected Switch 19 digit');
		$this->assertTrue($validation->cc('6759011470269978117', array('switch')), 'Expected Switch 19 digit');
		$this->assertTrue($validation->cc('6333175833997062502', array('switch')), 'Expected Switch 19 digit');
		$this->assertTrue($validation->cc('6759498728789080439', array('switch')), 'Expected Switch 19 digit');
		$this->assertTrue($validation->cc('4903020404168157841', array('switch')), 'Expected Switch 19 digit');
		$this->assertTrue($validation->cc('6759354334874804313', array('switch')), 'Expected Switch 19 digit');
		$this->assertTrue($validation->cc('6759900856420875115', array('switch')), 'Expected Switch 19 digit');
		$this->assertTrue($validation->cc('5641827269346868860', array('switch')), 'Expected Switch 19 digit');
		$this->assertTrue($validation->cc('5641828995047453870', array('switch')), 'Expected Switch 19 digit');
		$this->assertTrue($validation->cc('6333321884754806543', array('switch')), 'Expected Switch 19 digit');
		$this->assertTrue($validation->cc('6333108246283715901', array('switch')), 'Expected Switch 19 digit');
		$this->assertTrue($validation->cc('6759572372800700102', array('switch')), 'Expected Switch 19 digit');
		$this->assertTrue($validation->cc('4903095096797974933', array('switch')), 'Expected Switch 19 digit');
		$this->assertTrue($validation->cc('6333354315797920215', array('switch')), 'Expected Switch 19 digit');
		$this->assertTrue($validation->cc('6759163746089433755', array('switch')), 'Expected Switch 19 digit');
		$this->assertTrue($validation->cc('6759871666634807647', array('switch')), 'Expected Switch 19 digit');
		$this->assertTrue($validation->cc('5641827883728575248', array('switch')), 'Expected Switch 19 digit');
		$this->assertTrue($validation->cc('4936527975051407847', array('switch')), 'Expected Switch 19 digit');
		$this->assertTrue($validation->cc('5641823318396882141', array('switch')), 'Expected Switch 19 digit');
		$this->assertTrue($validation->cc('6759123772311123708', array('switch')), 'Expected Switch 19 digit');
		$this->assertTrue($validation->cc('4903054736148271088', array('switch')), 'Expected Switch 19 digit');
		$this->assertTrue($validation->cc('4936477526808883952', array('switch')), 'Expected Switch 19 digit');
		$this->assertTrue($validation->cc('4936433964890967966', array('switch')), 'Expected Switch 19 digit');
		$this->assertTrue($validation->cc('6333245128906049344', array('switch')), 'Expected Switch 19 digit');
		$this->assertTrue($validation->cc('4936321036970553134', array('switch')), 'Expected Switch 19 digit');
		$this->assertTrue($validation->cc('4936111816358702773', array('switch')), 'Expected Switch 19 digit');
		$this->assertTrue($validation->cc('4936196077254804290', array('switch')), 'Expected Switch 19 digit');
		$this->assertTrue($validation->cc('6759558831206830183', array('switch')), 'Expected Switch 19 digit');
		$this->assertTrue($validation->cc('5641827998830403137', array('switch')), 'Expected Switch 19 digit');
		//VISA 13 digit
		$this->assertTrue($validation->cc('4024007174754', array('visa')), 'Expected VISA 13 digit');
		$this->assertTrue($validation->cc('4104816460717', array('visa')), 'Expected VISA 13 digit');
		$this->assertTrue($validation->cc('4716229700437', array('visa')), 'Expected VISA 13 digit');
		$this->assertTrue($validation->cc('4539305400213', array('visa')), 'Expected VISA 13 digit');
		$this->assertTrue($validation->cc('4728260558665', array('visa')), 'Expected VISA 13 digit');
		$this->assertTrue($validation->cc('4929100131792', array('visa')), 'Expected VISA 13 digit');
		$this->assertTrue($validation->cc('4024007117308', array('visa')), 'Expected VISA 13 digit');
		$this->assertTrue($validation->cc('4539915491024', array('visa')), 'Expected VISA 13 digit');
		$this->assertTrue($validation->cc('4539790901139', array('visa')), 'Expected VISA 13 digit');
		$this->assertTrue($validation->cc('4485284914909', array('visa')), 'Expected VISA 13 digit');
		$this->assertTrue($validation->cc('4782793022350', array('visa')), 'Expected VISA 13 digit');
		$this->assertTrue($validation->cc('4556899290685', array('visa')), 'Expected VISA 13 digit');
		$this->assertTrue($validation->cc('4024007134774', array('visa')), 'Expected VISA 13 digit');
		$this->assertTrue($validation->cc('4333412341316', array('visa')), 'Expected VISA 13 digit');
		$this->assertTrue($validation->cc('4539534204543', array('visa')), 'Expected VISA 13 digit');
		$this->assertTrue($validation->cc('4485640373626', array('visa')), 'Expected VISA 13 digit');
		$this->assertTrue($validation->cc('4929911445746', array('visa')), 'Expected VISA 13 digit');
		$this->assertTrue($validation->cc('4539292550806', array('visa')), 'Expected VISA 13 digit');
		$this->assertTrue($validation->cc('4716523014030', array('visa')), 'Expected VISA 13 digit');
		$this->assertTrue($validation->cc('4024007125152', array('visa')), 'Expected VISA 13 digit');
		$this->assertTrue($validation->cc('4539758883311', array('visa')), 'Expected VISA 13 digit');
		$this->assertTrue($validation->cc('4024007103258', array('visa')), 'Expected VISA 13 digit');
		$this->assertTrue($validation->cc('4916933155767', array('visa')), 'Expected VISA 13 digit');
		$this->assertTrue($validation->cc('4024007159672', array('visa')), 'Expected VISA 13 digit');
		$this->assertTrue($validation->cc('4716935544871', array('visa')), 'Expected VISA 13 digit');
		$this->assertTrue($validation->cc('4929415177779', array('visa')), 'Expected VISA 13 digit');
		$this->assertTrue($validation->cc('4929748547896', array('visa')), 'Expected VISA 13 digit');
		$this->assertTrue($validation->cc('4929153468612', array('visa')), 'Expected VISA 13 digit');
		$this->assertTrue($validation->cc('4539397132104', array('visa')), 'Expected VISA 13 digit');
		$this->assertTrue($validation->cc('4485293435540', array('visa')), 'Expected VISA 13 digit');
		$this->assertTrue($validation->cc('4485799412720', array('visa')), 'Expected VISA 13 digit');
		$this->assertTrue($validation->cc('4916744757686', array('visa')), 'Expected VISA 13 digit');
		$this->assertTrue($validation->cc('4556475655426', array('visa')), 'Expected VISA 13 digit');
		$this->assertTrue($validation->cc('4539400441625', array('visa')), 'Expected VISA 13 digit');
		$this->assertTrue($validation->cc('4485437129173', array('visa')), 'Expected VISA 13 digit');
		$this->assertTrue($validation->cc('4716253605320', array('visa')), 'Expected VISA 13 digit');
		$this->assertTrue($validation->cc('4539366156589', array('visa')), 'Expected VISA 13 digit');
		$this->assertTrue($validation->cc('4916498061392', array('visa')), 'Expected VISA 13 digit');
		$this->assertTrue($validation->cc('4716127163779', array('visa')), 'Expected VISA 13 digit');
		$this->assertTrue($validation->cc('4024007183078', array('visa')), 'Expected VISA 13 digit');
		$this->assertTrue($validation->cc('4041553279654', array('visa')), 'Expected VISA 13 digit');
		$this->assertTrue($validation->cc('4532380121960', array('visa')), 'Expected VISA 13 digit');
		$this->assertTrue($validation->cc('4485906062491', array('visa')), 'Expected VISA 13 digit');
		$this->assertTrue($validation->cc('4539365115149', array('visa')), 'Expected VISA 13 digit');
		$this->assertTrue($validation->cc('4485146516702', array('visa')), 'Expected VISA 13 digit');
		//VISA 16 digit
		$this->assertTrue($validation->cc('4916375389940009', array('visa')), 'Expected VISA 16 digit');
		$this->assertTrue($validation->cc('4929167481032610', array('visa')), 'Expected VISA 16 digit');
		$this->assertTrue($validation->cc('4485029969061519', array('visa')), 'Expected VISA 16 digit');
		$this->assertTrue($validation->cc('4485573845281759', array('visa')), 'Expected VISA 16 digit');
		$this->assertTrue($validation->cc('4485669810383529', array('visa')), 'Expected VISA 16 digit');
		$this->assertTrue($validation->cc('4929615806560327', array('visa')), 'Expected VISA 16 digit');
		$this->assertTrue($validation->cc('4556807505609535', array('visa')), 'Expected VISA 16 digit');
		$this->assertTrue($validation->cc('4532611336232890', array('visa')), 'Expected VISA 16 digit');
		$this->assertTrue($validation->cc('4532201952422387', array('visa')), 'Expected VISA 16 digit');
		$this->assertTrue($validation->cc('4485073797976290', array('visa')), 'Expected VISA 16 digit');
		$this->assertTrue($validation->cc('4024007157580969', array('visa')), 'Expected VISA 16 digit');
		$this->assertTrue($validation->cc('4053740470212274', array('visa')), 'Expected VISA 16 digit');
		$this->assertTrue($validation->cc('4716265831525676', array('visa')), 'Expected VISA 16 digit');
		$this->assertTrue($validation->cc('4024007100222966', array('visa')), 'Expected VISA 16 digit');
		$this->assertTrue($validation->cc('4539556148303244', array('visa')), 'Expected VISA 16 digit');
		$this->assertTrue($validation->cc('4532449879689709', array('visa')), 'Expected VISA 16 digit');
		$this->assertTrue($validation->cc('4916805467840986', array('visa')), 'Expected VISA 16 digit');
		$this->assertTrue($validation->cc('4532155644440233', array('visa')), 'Expected VISA 16 digit');
		$this->assertTrue($validation->cc('4467977802223781', array('visa')), 'Expected VISA 16 digit');
		$this->assertTrue($validation->cc('4539224637000686', array('visa')), 'Expected VISA 16 digit');
		$this->assertTrue($validation->cc('4556629187064965', array('visa')), 'Expected VISA 16 digit');
		$this->assertTrue($validation->cc('4532970205932943', array('visa')), 'Expected VISA 16 digit');
		$this->assertTrue($validation->cc('4821470132041850', array('visa')), 'Expected VISA 16 digit');
		$this->assertTrue($validation->cc('4916214267894485', array('visa')), 'Expected VISA 16 digit');
		$this->assertTrue($validation->cc('4024007169073284', array('visa')), 'Expected VISA 16 digit');
		$this->assertTrue($validation->cc('4716783351296122', array('visa')), 'Expected VISA 16 digit');
		$this->assertTrue($validation->cc('4556480171913795', array('visa')), 'Expected VISA 16 digit');
		$this->assertTrue($validation->cc('4929678411034997', array('visa')), 'Expected VISA 16 digit');
		$this->assertTrue($validation->cc('4682061913519392', array('visa')), 'Expected VISA 16 digit');
		$this->assertTrue($validation->cc('4916495481746474', array('visa')), 'Expected VISA 16 digit');
		$this->assertTrue($validation->cc('4929007108460499', array('visa')), 'Expected VISA 16 digit');
		$this->assertTrue($validation->cc('4539951357838586', array('visa')), 'Expected VISA 16 digit');
		$this->assertTrue($validation->cc('4716482691051558', array('visa')), 'Expected VISA 16 digit');
		$this->assertTrue($validation->cc('4916385069917516', array('visa')), 'Expected VISA 16 digit');
		$this->assertTrue($validation->cc('4929020289494641', array('visa')), 'Expected VISA 16 digit');
		$this->assertTrue($validation->cc('4532176245263774', array('visa')), 'Expected VISA 16 digit');
		$this->assertTrue($validation->cc('4556242273553949', array('visa')), 'Expected VISA 16 digit');
		$this->assertTrue($validation->cc('4481007485188614', array('visa')), 'Expected VISA 16 digit');
		$this->assertTrue($validation->cc('4716533372139623', array('visa')), 'Expected VISA 16 digit');
		$this->assertTrue($validation->cc('4929152038152632', array('visa')), 'Expected VISA 16 digit');
		$this->assertTrue($validation->cc('4539404037310550', array('visa')), 'Expected VISA 16 digit');
		$this->assertTrue($validation->cc('4532800925229140', array('visa')), 'Expected VISA 16 digit');
		$this->assertTrue($validation->cc('4916845885268360', array('visa')), 'Expected VISA 16 digit');
		$this->assertTrue($validation->cc('4394514669078434', array('visa')), 'Expected VISA 16 digit');
		$this->assertTrue($validation->cc('4485611378115042', array('visa')), 'Expected VISA 16 digit');
		//Visa Electron
		$this->assertTrue($validation->cc('4175003346287100', array('electron')), 'Expected Visa Electron');
		$this->assertTrue($validation->cc('4913042516577228', array('electron')), 'Expected Visa Electron');
		$this->assertTrue($validation->cc('4917592325659381', array('electron')), 'Expected Visa Electron');
		$this->assertTrue($validation->cc('4917084924450511', array('electron')), 'Expected Visa Electron');
		$this->assertTrue($validation->cc('4917994610643999', array('electron')), 'Expected Visa Electron');
		$this->assertTrue($validation->cc('4175005933743585', array('electron')), 'Expected Visa Electron');
		$this->assertTrue($validation->cc('4175008373425044', array('electron')), 'Expected Visa Electron');
		$this->assertTrue($validation->cc('4913119763664154', array('electron')), 'Expected Visa Electron');
		$this->assertTrue($validation->cc('4913189017481812', array('electron')), 'Expected Visa Electron');
		$this->assertTrue($validation->cc('4913085104968622', array('electron')), 'Expected Visa Electron');
		$this->assertTrue($validation->cc('4175008803122021', array('electron')), 'Expected Visa Electron');
		$this->assertTrue($validation->cc('4913294453962489', array('electron')), 'Expected Visa Electron');
		$this->assertTrue($validation->cc('4175009797419290', array('electron')), 'Expected Visa Electron');
		$this->assertTrue($validation->cc('4175005028142917', array('electron')), 'Expected Visa Electron');
		$this->assertTrue($validation->cc('4913940802385364', array('electron')), 'Expected Visa Electron');
		//Voyager
		$this->assertTrue($validation->cc('869940697287073', array('voyager')), 'Expected Voyager');
		$this->assertTrue($validation->cc('869934523596112', array('voyager')), 'Expected Voyager');
		$this->assertTrue($validation->cc('869958670174621', array('voyager')), 'Expected Voyager');
		$this->assertTrue($validation->cc('869921250068209', array('voyager')), 'Expected Voyager');
		$this->assertTrue($validation->cc('869972521242198', array('voyager')), 'Expected Voyager');
	}

	function testLuhn(){
		$validation = new Validation();
		$validation->deep = true;

		//American Express
		$validation->check = '370482756063980';
		$this->assertTrue($validation->_luhn(), 'Expected American Express');
		//BankCard
		$validation->check = '5610745867413420';
		$this->assertTrue($validation->_luhn(), 'Expected BankCard');
		//Diners Club 14
		$validation->check = '30155483651028';
		$this->assertTrue($validation->_luhn(), 'Expected Diners Club 14');
		//2004 MasterCard/Diners Club Alliance International 14
		$validation->check = '36747701998969';
		$this->assertTrue($validation->_luhn(), 'Expected MasterCard/Diners Club International');
		//2004 MasterCard/Diners Club Alliance US & Canada 16
		$validation->check = '5597511346169950';
		$this->assertTrue($validation->_luhn(), 'Expected MasterCard/Diners Club US & Canada');
		//Discover
		$validation->check = '6011802876467237';
		$this->assertTrue($validation->_luhn(), 'Expected Discover');
		//enRoute
		$validation->check = '201496944158937';
		$this->assertTrue($validation->_luhn(), 'Expected enRoute');
		//JCB 15 digit
		$validation->check = '210034762247893';
		$this->assertTrue($validation->_luhn(), 'Expected JCB 15 digit');
		//JCB 16 digit
		$validation->check = '3096806857839939';
		$this->assertTrue($validation->_luhn(), 'Expected JCB 16 digit');
		//Maestro (debit card)
		$validation->check = '5020147409985219';
		$this->assertTrue($validation->_luhn(), 'Expected Maestro (debit card)');
		//Mastercard
		$validation->check = '5580424361774366';
		$this->assertTrue($validation->_luhn(), 'Expected Mastercard)');
		//Solo 16
		$validation->check = '6767432107064987';
		$this->assertTrue($validation->_luhn(), 'Expected Solo 16)');
		//Solo 18
		$validation->check = '676714834398858593';
		$this->assertTrue($validation->_luhn(), 'Expected Solo 18)');
		//Solo 19
		$validation->check = '6767838565218340113';
		$this->assertTrue($validation->_luhn(), 'Expected Solo 19)');
		//Switch 16
		$validation->check = '5641829171515733';
		$this->assertTrue($validation->_luhn(), 'Expected Switch 16)');
		//Switch 18
		$validation->check = '493622764224625174';
		$this->assertTrue($validation->_luhn(), 'Expected Switch 18');
		//Switch 19
		$validation->check = '6759603460617628716';
		$this->assertTrue($validation->_luhn(), 'Expected Switch 19');
		//VISA 13 digit
		$validation->check = '4024007174754';
		$this->assertTrue($validation->_luhn(), 'Expected VISA 13 digit');
		//VISA 16 digit
		$validation->check = '4916375389940009';
		$this->assertTrue($validation->_luhn(), 'Expected VISA 16 digit');
		//Visa Electron
		$validation->check = '4175003346287100';
		$this->assertTrue($validation->_luhn(), 'Expected Visa Electron');
		//Voyager
		$validation->check = '869940697287073';
		$this->assertTrue($validation->_luhn(), 'Expected Voyager');

		$validation->check = '0000000000000000';
		$this->assertFalse($validation->_luhn(), 'All zeros');

		$validation->check = '869940697287173';
		$this->assertFalse($validation->_luhn(), 'Expected invalid match');
	}

	function testCustomRegexForCc() {
		$validation = new Validation();
		$this->assertTrue($validation->cc('12332105933743585', null, null, '/123321\\d{11}/'), 'Expected 17 digit');
		$this->assertFalse($validation->cc('1233210593374358', null, null, '/123321\\d{11}/'), 'Expected 17 digit got 16');
		$this->assertFalse($validation->cc('12312305933743585', null, null, '/123321\\d{11}/'), 'Expected 123321 in first 6 positions, got 123123');
	}

	function testCustomRegexForCcWithLuhnCheck() {
		$validation = new Validation();
		$this->assertTrue($validation->cc('12332110426226941', null, true, '/123321\\d{11}/'), 'Expected valid Luhn match');
		$this->assertFalse($validation->cc('12332105933743585', null, true, '/123321\\d{11}/'), 'Expected invalid Luhn match');
		$this->assertFalse($validation->cc('12332105933743587', null, true, '/123321\\d{11}/'), 'Expected invalid Luhn match');
		$this->assertFalse($validation->cc('12312305933743585', null, true, '/123321\\d{11}/'), 'Expected invalid Luhn match');
	}

	function testFastCc() {
		$validation = new Validation();
		//American Express
		$this->assertTrue($validation->cc('370482756063980'), 'Expected American Express');
		//Diners Club 14
		$this->assertTrue($validation->cc('30155483651028'), 'Expected Diners Club 14');
		//2004 MasterCard/Diners Club Alliance International 14
		$this->assertTrue($validation->cc('36747701998969'), 'Expected MasterCard/Diners Club International');
		//2004 MasterCard/Diners Club Alliance US & Canada 16
		$this->assertTrue($validation->cc('5597511346169950'), 'Expected MasterCard/Diners Club US & Canada');
		//Discover
		$this->assertTrue($validation->cc('6011802876467237'), 'Expected Discover');
		//Mastercard
		$this->assertTrue($validation->cc('5580424361774366'), 'Expected Mastercard)');
		//VISA 13 digit
		$this->assertTrue($validation->cc('4024007174754'), 'Expected VISA 13 digit');
		//VISA 16 digit
		$this->assertTrue($validation->cc('4916375389940009'), 'Expected VISA 16 digit');
		//Visa Electron
		$this->assertTrue($validation->cc('4175003346287100'), 'Expected Visa Electron');
	}

	function testAllCc() {
		$validation = new Validation();
		//American Express
		$this->assertTrue($validation->cc('370482756063980', 'all'), 'Expected American Express');
		//BankCard
		$this->assertTrue($validation->cc('5610745867413420', 'all'), 'Expected BankCard');
		//Diners Club 14
		$this->assertTrue($validation->cc('30155483651028', 'all'), 'Expected Diners Club 14');
		//2004 MasterCard/Diners Club Alliance International 14
		$this->assertTrue($validation->cc('36747701998969', 'all'), 'Expected MasterCard/Diners Club International');
		//2004 MasterCard/Diners Club Alliance US & Canada 16
		$this->assertTrue($validation->cc('5597511346169950', 'all'), 'Expected MasterCard/Diners Club US & Canada');
		//Discover
		$this->assertTrue($validation->cc('6011802876467237', 'all'), 'Expected Discover');
		//enRoute
		$this->assertTrue($validation->cc('201496944158937', 'all'), 'Expected enRoute');
		//JCB 15 digit
		$this->assertTrue($validation->cc('210034762247893', 'all'), 'Expected JCB 15 digit');
		//JCB 16 digit
		$this->assertTrue($validation->cc('3096806857839939', 'all'), 'Expected JCB 16 digit');
		//Maestro (debit card)
		$this->assertTrue($validation->cc('5020147409985219', 'all'), 'Expected Maestro (debit card)');
		//Mastercard
		$this->assertTrue($validation->cc('5580424361774366', 'all'), 'Expected Mastercard)');
		//Solo 16
		$this->assertTrue($validation->cc('6767432107064987', 'all'), 'Expected Solo 16)');
		//Solo 18
		$this->assertTrue($validation->cc('676714834398858593', 'all'), 'Expected Solo 18)');
		//Solo 19
		$this->assertTrue($validation->cc('6767838565218340113', 'all'), 'Expected Solo 19)');
		//Switch 16
		$this->assertTrue($validation->cc('5641829171515733', 'all'), 'Expected Switch 16)');
		//Switch 18
		$this->assertTrue($validation->cc('493622764224625174', 'all'), 'Expected Switch 18');
		//Switch 19
		$this->assertTrue($validation->cc('6759603460617628716', 'all'), 'Expected Switch 19');
		//VISA 13 digit
		$this->assertTrue($validation->cc('4024007174754', 'all'), 'Expected VISA 13 digit');
		//VISA 16 digit
		$this->assertTrue($validation->cc('4916375389940009', 'all'), 'Expected VISA 16 digit');
		//Visa Electron
		$this->assertTrue($validation->cc('4175003346287100', 'all'), 'Expected Visa Electron');
		//Voyager
		$this->assertTrue($validation->cc('869940697287073', 'all'), 'Expected Voyager');
	}

	function testAllCcDeep() {
		$validation = new Validation();
		//American Express
		$this->assertTrue($validation->cc('370482756063980', 'all', true), 'Expected American Express');
		//BankCard
		$this->assertTrue($validation->cc('5610745867413420', 'all', true), 'Expected BankCard');
		//Diners Club 14
		$this->assertTrue($validation->cc('30155483651028', 'all', true), 'Expected Diners Club 14');
		//2004 MasterCard/Diners Club Alliance International 14
		$this->assertTrue($validation->cc('36747701998969', 'all', true), 'Expected MasterCard/Diners Club International');
		//2004 MasterCard/Diners Club Alliance US & Canada 16
		$this->assertTrue($validation->cc('5597511346169950', 'all', true), 'Expected MasterCard/Diners Club US & Canada');
		//Discover
		$this->assertTrue($validation->cc('6011802876467237', 'all', true), 'Expected Discover');
		//enRoute
		$this->assertTrue($validation->cc('201496944158937', 'all', true), 'Expected enRoute');
		//JCB 15 digit
		$this->assertTrue($validation->cc('210034762247893', 'all', true), 'Expected JCB 15 digit');
		//JCB 16 digit
		$this->assertTrue($validation->cc('3096806857839939', 'all', true), 'Expected JCB 16 digit');
		//Maestro (debit card)
		$this->assertTrue($validation->cc('5020147409985219', 'all', true), 'Expected Maestro (debit card)');
		//Mastercard
		$this->assertTrue($validation->cc('5580424361774366', 'all', true), 'Expected Mastercard)');
		//Solo 16
		$this->assertTrue($validation->cc('6767432107064987', 'all', true), 'Expected Solo 16)');
		//Solo 18
		$this->assertTrue($validation->cc('676714834398858593', 'all', true), 'Expected Solo 18)');
		//Solo 19
		$this->assertTrue($validation->cc('6767838565218340113', 'all', true), 'Expected Solo 19)');
		//Switch 16
		$this->assertTrue($validation->cc('5641829171515733', 'all'), true, 'Expected Switch 16)');
		//Switch 18
		$this->assertTrue($validation->cc('493622764224625174', 'all', true), 'Expected Switch 18');
		//Switch 19
		$this->assertTrue($validation->cc('6759603460617628716', 'all', true), 'Expected Switch 19');
		//VISA 13 digit
		$this->assertTrue($validation->cc('4024007174754', 'all', true), 'Expected VISA 13 digit');
		//VISA 16 digit
		$this->assertTrue($validation->cc('4916375389940009', 'all', true), 'Expected VISA 16 digit');
		//Visa Electron
		$this->assertTrue($validation->cc('4175003346287100', 'all', true), 'Expected Visa Electron');
		//Voyager
		$this->assertTrue($validation->cc('869940697287073', 'all', true), 'Expected Voyager');
	}

	function testComparison() {
		$validation = new Validation();
		$this->assertTrue($validation->comparison(7, 'is greater', 6), 'Expected 7 greater than 6');
		$this->assertTrue($validation->comparison(7, '>', 6), 'Expected 7 greater than 6');
		$this->assertTrue($validation->comparison(6, 'is less', 7), 'Expected 6 less than 7');
		$this->assertTrue($validation->comparison(6, '<', 7), 'Expected 6 less than 7');
		$this->assertTrue($validation->comparison(7, 'greater or equal', 7), 'Expected 5 greater then or equal to 5');
		$this->assertTrue($validation->comparison(7, '>=', 7), 'Expected 7 greater then or equal to 7');
		$this->assertTrue($validation->comparison(7, 'greater or equal', 6), 'Expected 7 greater then or equal to 6');
		$this->assertTrue($validation->comparison(7, '>=', 6), 'Expected 7 greater then or equal to 6');
		$this->assertTrue($validation->comparison(6, 'less or equal', 7), 'Expected 6 less then or equal to 7');
		$this->assertTrue($validation->comparison(6, '<=', 7), 'Expected 6 less then or equal to 7');
		$this->assertTrue($validation->comparison(7, 'equal to', 7), 'Expected 7 equal to 7');
		$this->assertTrue($validation->comparison(7, '==', 7), 'Expected 7 equal to 7');
		$this->assertTrue($validation->comparison(7, 'not equal', 6), 'Expected 7 not equal 6');
		$this->assertTrue($validation->comparison(7, '!=', 6), 'Expected 7 not equal 6');
		$this->assertFalse($validation->comparison(6, 'is greater', 7), 'Expected 7 greater than 6');
		$this->assertFalse($validation->comparison(6, '>', 7), 'Expected 7 greater than 6');
		$this->assertFalse($validation->comparison(7, 'is less', 6), 'Expected 6 less than 7');
		$this->assertFalse($validation->comparison(7, '<', 6), 'Expected 6 less than 7');
		$this->assertFalse($validation->comparison(6, 'greater or equal', 7), 'Expected 5 greater then or equal to 5');
		$this->assertFalse($validation->comparison(6, '>=', 7), 'Expected 7 greater then or equal to 7');
		$this->assertFalse($validation->comparison(6, 'greater or equal', 7), 'Expected 7 greater then or equal to 6');
		$this->assertFalse($validation->comparison(6, '>=', 7), 'Expected 7 greater then or equal to 6');
		$this->assertFalse($validation->comparison(7, 'less or equal', 6), 'Expected 6 less then or equal to 7');
		$this->assertFalse($validation->comparison(7, '<=', 6), 'Expected 6 less then or equal to 7');
		$this->assertFalse($validation->comparison(7, 'equal to', 6), 'Expected 7 equal to 7');
		$this->assertFalse($validation->comparison(7, '==', 6), 'Expected 7 equal to 7');
		$this->assertFalse($validation->comparison(7, 'not equal', 7), 'Expected 7 not equal 6');
		$this->assertFalse($validation->comparison(7, '!=', 7), 'Expected 7 not equal 6');
	}

	function testComparisonAsArray() {
		$validation = new Validation();
		$this->assertTrue($validation->comparison(array('check1' => 7, 'operator' => 'is greater', 'check2' => 6)), 'Expected 7 greater than 6');
		$this->assertTrue($validation->comparison(array('check1' => 7, 'operator' => '>', 'check2' => 6)), 'Expected 7 greater than 6');
		$this->assertTrue($validation->comparison(array('check1' => 6, 'operator' => 'is less', 'check2' => 7)), 'Expected 6 less than 7');
		$this->assertTrue($validation->comparison(array('check1' => 6, 'operator' => '<', 'check2' => 7)), 'Expected 6 less than 7');
		$this->assertTrue($validation->comparison(array('check1' => 7, 'operator' => 'greater or equal', 'check2' => 7)), 'Expected 5 greater then or equal to 5');
		$this->assertTrue($validation->comparison(array('check1' => 7, 'operator' => '>=', 'check2' => 7)), 'Expected 7 greater then or equal to 7');
		$this->assertTrue($validation->comparison(array('check1' => 7, 'operator' => 'greater or equal','check2' =>  6)), 'Expected 7 greater then or equal to 6');
		$this->assertTrue($validation->comparison(array('check1' => 7, 'operator' => '>=', 'check2' => 6)), 'Expected 7 greater then or equal to 6');
		$this->assertTrue($validation->comparison(array('check1' => 6, 'operator' => 'less or equal', 'check2' => 7)), 'Expected 6 less then or equal to 7');
		$this->assertTrue($validation->comparison(array('check1' => 6, 'operator' => '<=', 'check2' => 7)), 'Expected 6 less then or equal to 7');
		$this->assertTrue($validation->comparison(array('check1' => 7, 'operator' => 'equal to', 'check2' => 7)), 'Expected 7 equal to 7');
		$this->assertTrue($validation->comparison(array('check1' => 7, 'operator' => '==', 'check2' => 7)), 'Expected 7 equal to 7');
		$this->assertTrue($validation->comparison(array('check1' => 7, 'operator' => 'not equal', 'check2' => 6)), 'Expected 7 not equal 6');
		$this->assertTrue($validation->comparison(array('check1' => 7, 'operator' => '!=', 'check2' => 6)), 'Expected 7 not equal 6');
		$this->assertFalse($validation->comparison(array('check1' => 6, 'operator' => 'is greater', 'check2' => 7)), 'Expected 7 greater than 6');
		$this->assertFalse($validation->comparison(array('check1' => 6, 'operator' => '>', 'check2' => 7)), 'Expected 7 greater than 6');
		$this->assertFalse($validation->comparison(array('check1' => 7, 'operator' => 'is less', 'check2' => 6)), 'Expected 6 less than 7');
		$this->assertFalse($validation->comparison(array('check1' => 7, 'operator' => '<', 'check2' => 6)), 'Expected 6 less than 7');
		$this->assertFalse($validation->comparison(array('check1' => 6, 'operator' => 'greater or equal', 'check2' => 7)), 'Expected 5 greater then or equal to 5');
		$this->assertFalse($validation->comparison(array('check1' => 6, 'operator' => '>=', 'check2' => 7)), 'Expected 7 greater then or equal to 7');
		$this->assertFalse($validation->comparison(array('check1' => 6, 'operator' => 'greater or equal', 'check2' => 7)), 'Expected 7 greater then or equal to 6');
		$this->assertFalse($validation->comparison(array('check1' => 6, 'operator' => '>=', 'check2' => 7)), 'Expected 7 greater then or equal to 6');
		$this->assertFalse($validation->comparison(array('check1' => 7, 'operator' => 'less or equal', 'check2' => 6)), 'Expected 6 less then or equal to 7');
		$this->assertFalse($validation->comparison(array('check1' => 7, 'operator' => '<=', 'check2' => 6)), 'Expected 6 less then or equal to 7');
		$this->assertFalse($validation->comparison(array('check1' => 7, 'operator' => 'equal to', 'check2' => 6)), 'Expected 7 equal to 7');
		$this->assertFalse($validation->comparison(array('check1' => 7, 'operator' => '==','check2' =>  6)), 'Expected 7 equal to 7');
		$this->assertFalse($validation->comparison(array('check1' => 7, 'operator' => 'not equal', 'check2' => 7)), 'Expected 7 not equal 6');
		$this->assertFalse($validation->comparison(array('check1' => 7, 'operator' => '!=', 'check2' => 7)), 'Expected 7 not equal 6');
	}

	function testCustom() {
		$validation = new Validation();
		$this->assertTrue($validation->custom('12345', '/(?<!\\S)\\d++(?!\\S)/'), 'Expected an Integer');
		$this->assertFalse($validation->custom('Text', '/(?<!\\S)\\d++(?!\\S)/'), 'Expected an Integer String sent');
		$this->assertFalse($validation->custom('123.45', '/(?<!\\S)\\d++(?!\\S)/'), 'Expected an Integer Float sent');
	}

	function testCustomAsArray() {
		$validation = new Validation();
		$this->assertTrue($validation->custom(array('check' => '12345', 'regex' => '/(?<!\\S)\\d++(?!\\S)/')), 'Expected an Integer');
		$this->assertFalse($validation->custom(array('check' => 'Text', 'regex' => '/(?<!\\S)\\d++(?!\\S)/')), 'Expected an Integer String sent');
		$this->assertFalse($validation->custom(array('check' => '123.45', 'regex' => '/(?<!\\S)\\d++(?!\\S)/')), 'Expected an Integer Float sent');
	}

	function testDateDdmmyyyy() {
		$validation = new Validation();
		$this->assertTrue($validation->date('27-12-2006', array('dmy')), 'Expected valid dd/mm/yyyy date format');
		$this->assertTrue($validation->date('27.12.2006', array('dmy')), 'Expected valid dd/mm/yyyy date format');
		$this->assertTrue($validation->date('27/12/2006', array('dmy')), 'Expected valid dd/mm/yyyy date format');
		$this->assertTrue($validation->date('27 12 2006', array('dmy')), 'Expected valid dd/mm/yyyy date format');
		$this->assertFalse($validation->date('00-00-0000', array('dmy')), 'Expected invalid dd/mm/yyyy date format');
		$this->assertFalse($validation->date('00.00.0000', array('dmy')), 'Expected invalid dd/mm/yyyy date format');
		$this->assertFalse($validation->date('00/00/0000', array('dmy')), 'Expected invalid dd/mm/yyyy date format');
		$this->assertFalse($validation->date('00 00 0000', array('dmy')), 'Expected invalid dd/mm/yyyy date format');
		$this->assertFalse($validation->date('31-11-2006', array('dmy')), 'Expected invalid dd/mm/yyyy date format');
		$this->assertFalse($validation->date('31.11.2006', array('dmy')), 'Expected invalid dd/mm/yyyy date format');
		$this->assertFalse($validation->date('31/11/2006', array('dmy')), 'Expected invalid dd/mm/yyyy date format');
		$this->assertFalse($validation->date('31 11 2006', array('dmy')), 'Expected invalid dd/mm/yyyy date format');
	}

	function testDateDdmmyyyyLeapYear() {
		$validation = new Validation();
		$this->assertTrue($validation->date('29-02-2004', array('dmy')), 'Expected valid leap year dd/mm/yyyy date format');
		$this->assertTrue($validation->date('29.02.2004', array('dmy')), 'Expected valid leap year dd/mm/yyyy date format');
		$this->assertTrue($validation->date('29/02/2004', array('dmy')), 'Expected valid leap year dd/mm/yyyy date format');
		$this->assertTrue($validation->date('29 02 2004', array('dmy')), 'Expected valid leap year dd/mm/yyyy date format');
		$this->assertFalse($validation->date('29-02-2006', array('dmy')), 'Expected invalid leap year dd/mm/yyyy date format');
		$this->assertFalse($validation->date('29.02.2006', array('dmy')), 'Expected invalid leap year dd/mm/yyyy date format');
		$this->assertFalse($validation->date('29/02/2006', array('dmy')), 'Expected invalid leap year dd/mm/yyyy date format');
		$this->assertFalse($validation->date('29 02 2006', array('dmy')), 'Expected invalid leap year dd/mm/yyyy date format');
	}

	function testDateDdmmyy() {
		$validation = new Validation();
		$this->assertTrue($validation->date('27-12-06', array('dmy')), 'Expected valid dd/mm/yy date format');
		$this->assertTrue($validation->date('27.12.06', array('dmy')), 'Expected valid dd/mm/yy date format');
		$this->assertTrue($validation->date('27/12/06', array('dmy')), 'Expected valid dd/mm/yy date format');
		$this->assertTrue($validation->date('27 12 06', array('dmy')), 'Expected valid dd/mm/yy date format');
		$this->assertFalse($validation->date('00-00-00', array('dmy')), 'Expected invalid dd/mm/yy date format');
		$this->assertFalse($validation->date('00.00.00', array('dmy')), 'Expected invalid dd/mm/yy date format');
		$this->assertFalse($validation->date('00/00/00', array('dmy')), 'Expected invalid dd/mm/yy date format');
		$this->assertFalse($validation->date('00 00 00', array('dmy')), 'Expected invalid dd/mm/yy date format');
		$this->assertFalse($validation->date('31-11-06', array('dmy')), 'Expected invalid dd/mm/yy date format');
		$this->assertFalse($validation->date('31.11.06', array('dmy')), 'Expected invalid dd/mm/yy date format');
		$this->assertFalse($validation->date('31/11/06', array('dmy')), 'Expected invalid dd/mm/yy date format');
		$this->assertFalse($validation->date('31 11 06', array('dmy')), 'Expected invalid dd/mm/yy date format');
	}

	function testDateDdmmyyLeapYear() {
		$validation = new Validation();
		$this->assertTrue($validation->date('29-02-04', array('dmy')), 'Expected valid leap year dd/mm/yy date format');
		$this->assertTrue($validation->date('29.02.04', array('dmy')), 'Expected valid leap year dd/mm/yy date format');
		$this->assertTrue($validation->date('29/02/04', array('dmy')), 'Expected valid leap year dd/mm/yy date format');
		$this->assertTrue($validation->date('29 02 04', array('dmy')), 'Expected valid leap year dd/mm/yy date format');
		$this->assertFalse($validation->date('29-02-06', array('dmy')), 'Expected invalid leap year dd/mm/yy date format');
		$this->assertFalse($validation->date('29.02.06', array('dmy')), 'Expected invalid leap year dd/mm/yy date format');
		$this->assertFalse($validation->date('29/02/06', array('dmy')), 'Expected invalid leap year dd/mm/yy date format');
		$this->assertFalse($validation->date('29 02 06', array('dmy')), 'Expected invalid leap year dd/mm/yy date format');
	}

	function testDateDmyy() {
		$validation = new Validation();
		$this->assertTrue($validation->date('7-2-06', array('dmy')), 'Expected valid d/m/yy date format');
		$this->assertTrue($validation->date('7.2.06', array('dmy')), 'Expected valid d/m/yy date format');
		$this->assertTrue($validation->date('7/2/06', array('dmy')), 'Expected valid d/m/yy date format');
		$this->assertTrue($validation->date('7 2 06', array('dmy')), 'Expected valid d/m/yy date format');
		$this->assertFalse($validation->date('0-0-00', array('dmy')), 'Expected invalid d/m/yy date format');
		$this->assertFalse($validation->date('0.0.00', array('dmy')), 'Expected invalid d/m/yy date format');
		$this->assertFalse($validation->date('0/0/00', array('dmy')), 'Expected invalid d/m/yy date format');
		$this->assertFalse($validation->date('0 0 00', array('dmy')), 'Expected invalid d/m/yy date format');
		$this->assertFalse($validation->date('32-2-06', array('dmy')), 'Expected invalid d/m/yy date format');
		$this->assertFalse($validation->date('32.2.06', array('dmy')), 'Expected invalid d/m/yy date format');
		$this->assertFalse($validation->date('32/2/06', array('dmy')), 'Expected invalid d/m/yy date format');
		$this->assertFalse($validation->date('32 2 06', array('dmy')), 'Expected invalid d/m/yy date format');
	}

	function testDateDmyyLeapYear() {
		$validation = new Validation();
		$this->assertTrue($validation->date('29-2-04', array('dmy')), 'Expected valid leap year d/m/yy date format');
		$this->assertTrue($validation->date('29.2.04', array('dmy')), 'Expected valid leap year d/m/yy date format');
		$this->assertTrue($validation->date('29/2/04', array('dmy')), 'Expected valid leap year d/m/yy date format');
		$this->assertTrue($validation->date('29 2 04', array('dmy')), 'Expected valid leap year d/m/yy date format');
		$this->assertFalse($validation->date('29-2-06', array('dmy')), 'Expected invalid leap year d/m/yy date format');
		$this->assertFalse($validation->date('29.2.06', array('dmy')), 'Expected invalid leap year d/m/yy date format');
		$this->assertFalse($validation->date('29/2/06', array('dmy')), 'Expected invalid leap year d/m/yy date format');
		$this->assertFalse($validation->date('29 2 06', array('dmy')), 'Expected invalid leap year d/m/yy date format');
	}

	function testDateDmyyyy() {
		$validation = new Validation();
		$this->assertTrue($validation->date('7-2-2006', array('dmy')), 'Expected valid d/m/yyyy date format');
		$this->assertTrue($validation->date('7.2.2006', array('dmy')), 'Expected valid d/m/yyyy date format');
		$this->assertTrue($validation->date('7/2/2006', array('dmy')), 'Expected valid d/m/yyyy date format');
		$this->assertTrue($validation->date('7 2 2006', array('dmy')), 'Expected valid d/m/yyyy date format');
		$this->assertFalse($validation->date('0-0-0000', array('dmy')), 'Expected invalid d/m/yyyy date format');
		$this->assertFalse($validation->date('0.0.0000', array('dmy')), 'Expected invalid d/m/yyyy date format');
		$this->assertFalse($validation->date('0/0/0000', array('dmy')), 'Expected invalid d/m/yyyy date format');
		$this->assertFalse($validation->date('0 0 0000', array('dmy')), 'Expected invalid d/m/yyyy date format');
		$this->assertFalse($validation->date('32-2-2006', array('dmy')), 'Expected invalid d/m/yyyy date format');
		$this->assertFalse($validation->date('32.2.2006', array('dmy')), 'Expected invalid d/m/yyyy date format');
		$this->assertFalse($validation->date('32/2/2006', array('dmy')), 'Expected invalid d/m/yyyy date format');
		$this->assertFalse($validation->date('32 2 2006', array('dmy')), 'Expected invalid d/m/yyyy date format');
	}

	function testDateDmyyyyLeapYear() {
		$validation = new Validation();
		$this->assertTrue($validation->date('29-2-2004', array('dmy')), 'Expected valid leap year d/m/yyyy date format');
		$this->assertTrue($validation->date('29.2.2004', array('dmy')), 'Expected valid leap year d/m/yyyy date format');
		$this->assertTrue($validation->date('29/2/2004', array('dmy')), 'Expected valid leap year d/m/yyyy date format');
		$this->assertTrue($validation->date('29 2 2004', array('dmy')), 'Expected valid leap year d/m/yyyy date format');
		$this->assertFalse($validation->date('29-2-2006', array('dmy')), 'Expected invalid leap year d/m/yyyy date format');
		$this->assertFalse($validation->date('29.2.2006', array('dmy')), 'Expected invalid leap year d/m/yyyy date format');
		$this->assertFalse($validation->date('29/2/2006', array('dmy')), 'Expected invalid leap year d/m/yyyy date format');
		$this->assertFalse($validation->date('29 2 2006', array('dmy')), 'Expected invalid leap year d/m/yyyy date format');
	}

	function testDateMmddyyyy() {
		$validation = new Validation();
		$this->assertTrue($validation->date('12-27-2006', array('mdy')), 'Expected valid mm/dd/yyyy date format');
		$this->assertTrue($validation->date('12.27.2006', array('mdy')), 'Expected valid mm/dd/yyyy date format');
		$this->assertTrue($validation->date('12/27/2006', array('mdy')), 'Expected valid mm/dd/yyyy date format');
		$this->assertTrue($validation->date('12 27 2006', array('mdy')), 'Expected valid mm/dd/yyyy date format');
		$this->assertFalse($validation->date('00-00-0000', array('mdy')), 'Expected invalid mm/dd/yyyy date format');
		$this->assertFalse($validation->date('00.00.0000', array('mdy')), 'Expected invalid mm/dd/yyyy date format');
		$this->assertFalse($validation->date('00/00/0000', array('mdy')), 'Expected invalid mm/dd/yyyy date format');
		$this->assertFalse($validation->date('00 00 0000', array('mdy')), 'Expected invalid mm/dd/yyyy date format');
		$this->assertFalse($validation->date('11-31-2006', array('mdy')), 'Expected invalid mm/dd/yyyy date format');
		$this->assertFalse($validation->date('11.31.2006', array('mdy')), 'Expected invalid mm/dd/yyyy date format');
		$this->assertFalse($validation->date('11/31/2006', array('mdy')), 'Expected invalid mm/dd/yyyy date format');
		$this->assertFalse($validation->date('11 31 2006', array('mdy')), 'Expected invalid mm/dd/yyyy date format');
	}

	function testDateMmddyyyyLeapYear() {
		$validation = new Validation();
		$this->assertTrue($validation->date('02-29-2004', array('mdy')), 'Expected valid leap year mm/dd/yyyy date format');
		$this->assertTrue($validation->date('02.29.2004', array('mdy')), 'Expected valid leap year mm/dd/yyyy date format');
		$this->assertTrue($validation->date('02/29/2004', array('mdy')), 'Expected valid leap year mm/dd/yyyy date format');
		$this->assertTrue($validation->date('02 29 2004', array('mdy')), 'Expected valid leap year mm/dd/yyyy date format');
		$this->assertFalse($validation->date('02-29-2006', array('mdy')), 'Expected invalid leap year mm/dd/yyyy date format');
		$this->assertFalse($validation->date('02.29.2006', array('mdy')), 'Expected invalid leap year mm/dd/yyyy date format');
		$this->assertFalse($validation->date('02/29/2006', array('mdy')), 'Expected invalid leap year mm/dd/yyyy date format');
		$this->assertFalse($validation->date('02 29 2006', array('mdy')), 'Expected invalid leap year mm/dd/yyyy date format');
	}

	function testDateMmddyy() {
		$validation = new Validation();
		$this->assertTrue($validation->date('12-27-06', array('mdy')), 'Expected valid mm/dd/yy date format');
		$this->assertTrue($validation->date('12.27.06', array('mdy')), 'Expected valid mm/dd/yy date format');
		$this->assertTrue($validation->date('12/27/06', array('mdy')), 'Expected valid mm/dd/yy date format');
		$this->assertTrue($validation->date('12 27 06', array('mdy')), 'Expected valid mm/dd/yy date format');
		$this->assertFalse($validation->date('00-00-00', array('mdy')), 'Expected invalid mm/dd/yy date format');
		$this->assertFalse($validation->date('00.00.00', array('mdy')), 'Expected invalid mm/dd/yy date format');
		$this->assertFalse($validation->date('00/00/00', array('mdy')), 'Expected invalid mm/dd/yy date format');
		$this->assertFalse($validation->date('00 00 00', array('mdy')), 'Expected invalid mm/dd/yy date format');
		$this->assertFalse($validation->date('11-31-06', array('mdy')), 'Expected invalid mm/dd/yy date format');
		$this->assertFalse($validation->date('11.31.06', array('mdy')), 'Expected invalid mm/dd/yy date format');
		$this->assertFalse($validation->date('11/31/06', array('mdy')), 'Expected invalid mm/dd/yy date format');
		$this->assertFalse($validation->date('11 31 06', array('mdy')), 'Expected invalid mm/dd/yy date format');
	}

	function testDateMmddyyLeapYear() {
		$validation = new Validation();
		$this->assertTrue($validation->date('02-29-04', array('mdy')), 'Expected valid leap year mm/dd/yy date format');
		$this->assertTrue($validation->date('02.29.04', array('mdy')), 'Expected valid leap year mm/dd/yy date format');
		$this->assertTrue($validation->date('02/29/04', array('mdy')), 'Expected valid leap year mm/dd/yy date format');
		$this->assertTrue($validation->date('02 29 04', array('mdy')), 'Expected valid leap year mm/dd/yy date format');
		$this->assertFalse($validation->date('02-29-06', array('mdy')), 'Expected invalid leap year mm/dd/yy date format');
		$this->assertFalse($validation->date('02.29.06', array('mdy')), 'Expected invalid leap year mm/dd/yy date format');
		$this->assertFalse($validation->date('02/29/06', array('mdy')), 'Expected invalid leap year mm/dd/yy date format');
		$this->assertFalse($validation->date('02 29 06', array('mdy')), 'Expected invalid leap year mm/dd/yy date format');
	}

	function testDateMdyy() {
		$validation = new Validation();
		$this->assertTrue($validation->date('2-7-06', array('mdy')), 'Expected valid m/d/yy date format');
		$this->assertTrue($validation->date('2.7.06', array('mdy')), 'Expected valid m/d/yy date format');
		$this->assertTrue($validation->date('2/7/06', array('mdy')), 'Expected valid m/d/yy date format');
		$this->assertTrue($validation->date('2 7 06', array('mdy')), 'Expected valid m/d/yy date format');
		$this->assertFalse($validation->date('0-0-00', array('mdy')), 'Expected invalid m/d/yy date format');
		$this->assertFalse($validation->date('0.0.00', array('mdy')), 'Expected invalid m/d/yy date format');
		$this->assertFalse($validation->date('0/0/00', array('mdy')), 'Expected invalid m/d/yy date format');
		$this->assertFalse($validation->date('0 0 00', array('mdy')), 'Expected invalid m/d/yy date format');
		$this->assertFalse($validation->date('2-32-06', array('mdy')), 'Expected invalid m/d/yy date format');
		$this->assertFalse($validation->date('2.32.06', array('mdy')), 'Expected invalid m/d/yy date format');
		$this->assertFalse($validation->date('2/32/06', array('mdy')), 'Expected invalid m/d/yy date format');
		$this->assertFalse($validation->date('2 32 06', array('mdy')), 'Expected invalid m/d/yy date format');
	}

	function testDateMdyyLeapYear() {
		$validation = new Validation();
		$this->assertTrue($validation->date('2-29-04', array('mdy')), 'Expected valid leap year m/d/yy date format');
		$this->assertTrue($validation->date('2.29.04', array('mdy')), 'Expected valid leap year m/d/yy date format');
		$this->assertTrue($validation->date('2/29/04', array('mdy')), 'Expected valid leap year m/d/yy date format');
		$this->assertTrue($validation->date('2 29 04', array('mdy')), 'Expected valid leap year m/d/yy date format');
		$this->assertFalse($validation->date('2-29-06', array('mdy')), 'Expected invalid leap year m/d/yy date format');
		$this->assertFalse($validation->date('2.29.06', array('mdy')), 'Expected invalid leap year m/d/yy date format');
		$this->assertFalse($validation->date('2/29/06', array('mdy')), 'Expected invalid leap year m/d/yy date format');
		$this->assertFalse($validation->date('2 29 06', array('mdy')), 'Expected invalid leap year m/d/yy date format');
	}

	function testDateMdyyyy() {
		$validation = new Validation();
		$this->assertTrue($validation->date('2-7-2006', array('mdy')), 'Expected valid m/d/yyyy date format');
		$this->assertTrue($validation->date('2.7.2006', array('mdy')), 'Expected valid m/d/yyyy date format');
		$this->assertTrue($validation->date('2/7/2006', array('mdy')), 'Expected valid m/d/yyyy date format');
		$this->assertTrue($validation->date('2 7 2006', array('mdy')), 'Expected valid m/d/yyyy date format');
		$this->assertFalse($validation->date('0-0-0000', array('mdy')), 'Expected invalid m/d/yyyy date format');
		$this->assertFalse($validation->date('0.0.0000', array('mdy')), 'Expected invalid m/d/yyyy date format');
		$this->assertFalse($validation->date('0/0/0000', array('mdy')), 'Expected invalid m/d/yyyy date format');
		$this->assertFalse($validation->date('0 0 0000', array('mdy')), 'Expected invalid m/d/yyyy date format');
		$this->assertFalse($validation->date('2-32-2006', array('mdy')), 'Expected invalid m/d/yyyy date format');
		$this->assertFalse($validation->date('2.32.2006', array('mdy')), 'Expected invalid m/d/yyyy date format');
		$this->assertFalse($validation->date('2/32/2006', array('mdy')), 'Expected invalid m/d/yyyy date format');
		$this->assertFalse($validation->date('2 32 2006', array('mdy')), 'Expected invalid m/d/yyyy date format');
	}

	function testDateMdyyyyLeapYear() {
		$validation = new Validation();
		$this->assertTrue($validation->date('2-29-2004', array('mdy')), 'Expected valid leap year m/d/yyyy date format');
		$this->assertTrue($validation->date('2.29.2004', array('mdy')), 'Expected valid leap year m/d/yyyy date format');
		$this->assertTrue($validation->date('2/29/2004', array('mdy')), 'Expected valid leap year m/d/yyyy  date format');
		$this->assertTrue($validation->date('2 29 2004', array('mdy')), 'Expected valid leap year m/d/yyyy  date format');
		$this->assertFalse($validation->date('2-29-2006', array('mdy')), 'Expected invalid leap year m/d/yyyy  date format');
		$this->assertFalse($validation->date('2.29.2006', array('mdy')), 'Expected invalid leap year m/d/yyyy  date format');
		$this->assertFalse($validation->date('2/29/2006', array('mdy')), 'Expected invalid leap year m/d/yyyy  date format');
		$this->assertFalse($validation->date('2 29 2006', array('mdy')), 'Expected invalid leap year m/d/yyyy date format');
	}

	function testDateYyyymmdd() {
		$validation = new Validation();
		$this->assertTrue($validation->date('2006-12-27', array('ymd')), 'Expected valid yyyy/mm/dd date format');
		$this->assertTrue($validation->date('2006.12.27', array('ymd')), 'Expected valid yyyy/mm/dd date format');
		$this->assertTrue($validation->date('2006/12/27', array('ymd')), 'Expected valid yyyy/mm/dd date format');
		$this->assertTrue($validation->date('2006 12 27', array('ymd')), 'Expected valid yyyy/mm/dd date format');
		$this->assertFalse($validation->date('2006-11-31', array('ymd')), 'Expected invalid yyyy/mm/dd date format');
		$this->assertFalse($validation->date('2006.11.31', array('ymd')), 'Expected invalid yyyy/mm/dd date format');
		$this->assertFalse($validation->date('2006/11/31', array('ymd')), 'Expected invalid yyyy/mm/dd date format');
		$this->assertFalse($validation->date('2006 11 31', array('ymd')), 'Expected invalid yyyy/mm/dd date format');
	}

	function testDateYyyymmddLeapYear() {
		$validation = new Validation();
		$this->assertTrue($validation->date('2004-02-29', array('ymd')), 'Expected valid leap year yyyy/mm/dd date format');
		$this->assertTrue($validation->date('2004.02.29', array('ymd')), 'Expected valid leap year yyyy/mm/dd date format');
		$this->assertTrue($validation->date('2004/02/29', array('ymd')), 'Expected valid leap year yyyy/mm/dd date format');
		$this->assertTrue($validation->date('2004 02 29', array('ymd')), 'Expected valid leap year yyyy/mm/dd date format');
		$this->assertFalse($validation->date('2006-02-29', array('ymd')), 'Expected invalid leap year yyyy/mm/dd date format');
		$this->assertFalse($validation->date('2006.02.29', array('ymd')), 'Expected invalid leap year yyyy/mm/dd date format');
		$this->assertFalse($validation->date('2006/02/29', array('ymd')), 'Expected invalid leap year yyyy/mm/dd date format');
		$this->assertFalse($validation->date('2006 02 29', array('ymd')), 'Expected invalid leap year yyyy/mm/dd date format');
	}

	function testDateYymmdd() {
		$validation = new Validation();
		$this->assertTrue($validation->date('06-12-27', array('ymd')), 'Expected valid yyyy/mm/dd date format');
		$this->assertTrue($validation->date('06.12.27', array('ymd')), 'Expected valid yyyy/mm/dd date format');
		$this->assertTrue($validation->date('06/12/27', array('ymd')), 'Expected valid yyyy/mm/dd date format');
		$this->assertTrue($validation->date('06 12 27', array('ymd')), 'Expected valid yyyy/mm/dd date format');
		$this->assertFalse($validation->date('12/27/2600', array('ymd')), 'Expected invalid yyyy/mm/dd date format');
		$this->assertFalse($validation->date('12.27.2600', array('ymd')), 'Expected invalid yyyy/mm/dd date format');
		$this->assertFalse($validation->date('12/27/2600', array('ymd')), 'Expected invalid yyyy/mm/dd date format');
		$this->assertFalse($validation->date('12 27 2600', array('ymd')), 'Expected invalid yyyy/mm/dd date format');
		$this->assertFalse($validation->date('06-11-31', array('ymd')), 'Expected invalid yyyy/mm/dd date format');
		$this->assertFalse($validation->date('06.11.31', array('ymd')), 'Expected invalid yyyy/mm/dd date format');
		$this->assertFalse($validation->date('06/11/31', array('ymd')), 'Expected invalid yyyy/mm/dd date format');
		$this->assertFalse($validation->date('06 11 31', array('ymd')), 'Expected invalid yyyy/mm/dd date format');
	}

	function testDateYymmddLeapYear() {
		$validation = new Validation();
		$this->assertTrue($validation->date('2004-02-29', array('ymd')), 'Expected valid leap year yyyy/mm/dd date format');
		$this->assertTrue($validation->date('2004.02.29', array('ymd')), 'Expected valid leap year yyyy/mm/dd date format');
		$this->assertTrue($validation->date('2004/02/29', array('ymd')), 'Expected valid leap year yyyy/mm/dd date format');
		$this->assertTrue($validation->date('2004 02 29', array('ymd')), 'Expected valid leap year yyyy/mm/dd date format');
		$this->assertFalse($validation->date('2006-02-29', array('ymd')), 'Expected invalid leap year yyyy/mm/dd date format');
		$this->assertFalse($validation->date('2006.02.29', array('ymd')), 'Expected invalid leap year yyyy/mm/dd date format');
		$this->assertFalse($validation->date('2006/02/29', array('ymd')), 'Expected invalid leap year yyyy/mm/dd date format');
		$this->assertFalse($validation->date('2006 02 29', array('ymd')), 'Expected invalid leap year yyyy/mm/dd date format');
	}

	function testDateDdMMMMyyyy() {
		$validation = new Validation();
		$this->assertTrue($validation->date('27 December 2006', array('dMy')), 'Expected valid dd/MMMM/yyyy date format');
		$this->assertTrue($validation->date('27 Dec 2006', array('dMy')), 'Expected valid dd/MM/yyyy date format');
		$this->assertFalse($validation->date('2006 Dec 27', array('dMy')), 'Expected invalid dd/MMM/yyyy date format');
		$this->assertFalse($validation->date('2006 December 27', array('dMy')), 'Expected invalid dd/MMMM/yyyy date format');
	}

	function testDateDdMMMMyyyyLeapYear() {
		$validation = new Validation();
		$this->assertTrue($validation->date('29 February 2004', array('dMy')), 'Expected valid leap year dd/MMMM/yyyy date format');
		$this->assertFalse($validation->date('29 February 2006', array('dMy')), 'Expected invalid leap yeay dd/MMMM/yyyy date format');
	}

	function testDateMmmmDdyyyy() {
		$validation = new Validation();
		$this->assertTrue($validation->date('December 27, 2006', array('Mdy')), 'Expected valid MMMM/dd/yyyy date format');
		$this->assertTrue($validation->date('Dec 27, 2006', array('Mdy')), 'Expected valid MM/dd/yyyy date format');
		$this->assertTrue($validation->date('December 27 2006', array('Mdy')), 'Expected valid MMMM/dd/yyyy date format');
		$this->assertTrue($validation->date('Dec 27 2006', array('Mdy')), 'Expected valid MM/dd/yyyy date format');
		$this->assertFalse($validation->date('27 Dec 2006', array('Mdy')), 'Expected invalid dd/MMM/yyyy date format');
		$this->assertFalse($validation->date('2006 December 27', array('Mdy')), 'Expected invalid dd/MMMM/yyyy date format');
	}

	function testDateMmmmDdyyyyLeapYear() {
		$validation = new Validation();
		$this->assertTrue($validation->date('February 29, 2004', array('Mdy')), 'Expected valid leap year MMMM/dd/yyyy date format');
		$this->assertTrue($validation->date('Feb 29, 2004', array('Mdy')), 'Expected valid leap year MMMM/dd/yyyy date format');
		$this->assertTrue($validation->date('February 29 2004', array('Mdy')), 'Expected valid leap year MMMM/dd/yyyy date format');
		$this->assertTrue($validation->date('Feb 29 2004', array('Mdy')), 'Expected valid leap year MMMM/dd/yyyy date format');
		$this->assertFalse($validation->date('February 29, 2006', array('Mdy')), 'Expected invalid leap yeay MMMM/dd/yyyy date format');
	}

	function testDateMy() {
		$validation = new Validation();
		$this->assertTrue($validation->date('December 2006', array('My')), 'Expected valid MMMM/yyyy date format');
		$this->assertTrue($validation->date('Dec 2006', array('My')), 'Expected valid MM/yyyyy date format');
		$this->assertTrue($validation->date('December/2006', array('My')), 'Expected valid MMMM/yyyy date format');
		$this->assertTrue($validation->date('Dec/2006', array('My')), 'Expected valid MM/yyyy date format');
	}

	function testDateMyNumeric() {
		$validation = new Validation();
		$this->assertTrue($validation->date('12/2006', array('my')), 'Expected valid mm/yyyy date format');
		$this->assertTrue($validation->date('12-2006', array('my')), 'Expected valid mm/yyyy date format');
		$this->assertTrue($validation->date('12.2006', array('my')), 'Expected valid mm/yyyy date format');
		$this->assertTrue($validation->date('12 2006', array('my')), 'Expected valid MM/yyyy date format');
		$this->assertFalse($validation->date('12/06', array('my')), 'Expected invalid mm/yy date format');
		$this->assertFalse($validation->date('12-06', array('my')), 'Expected invalid mm/yy date format');
		$this->assertFalse($validation->date('12.06', array('my')), 'Expected invalid mm/yy date format');
		$this->assertFalse($validation->date('12 06', array('my')), 'Expected invalid MM/yy date format');
	}

	function testDateCustomRegx() {
		$validation = new Validation();
		$this->assertTrue($validation->date('2006-12-27', null, '%^(19|20)[0-9]{2}[- /.](0[1-9]|1[012])[- /.](0[1-9]|[12][0-9]|3[01])$%'), 'Expected valid custom regex to match date');
		$this->assertFalse($validation->date('12-27-2006', null, '%^(19|20)[0-9]{2}[- /.](0[1-9]|1[012])[- /.](0[1-9]|[12][0-9]|3[01])$%'), 'Expected valid custom regex to match date');
	}

	function testDecimal() {
		$validation = new Validation();
		$this->assertTrue($validation->decimal('+1234.54321'), 'Expected valid decimal');
		$this->assertTrue($validation->decimal('-1234.54321'), 'Expected valid decimal');
		$this->assertTrue($validation->decimal('1234.54321'), 'Expected valid decimal');
		$this->assertTrue($validation->decimal('+0123.45e6'), 'Expected valid decimal');
		$this->assertTrue($validation->decimal('-0123.45e6'), 'Expected valid decimal');
		$this->assertTrue($validation->decimal('0123.45e6'), 'Expected valid decimal');
		$this->assertFalse($validation->decimal('string'), 'Expected invalid string');
		$this->assertFalse($validation->decimal('1234'), 'Expected invalid integer');
		$this->assertFalse($validation->decimal('-1234'), 'Expected invalid integer');
		$this->assertFalse($validation->decimal('+1234'), 'Expected invalid integer');
	}

	function testDecimalWithPlaces() {
		$validation = new Validation();
		$this->assertTrue($validation->decimal('.27', '2'), 'Expected valid 2 decimal places');
		$this->assertTrue($validation->decimal(.27, 2), 'Expected valid 2 decimal places');
		$this->assertTrue($validation->decimal(-.27, 2), 'Expected valid 2 decimal places');
		$this->assertTrue($validation->decimal(+.27, 2), 'Expected valid 2 decimal places');
		$this->assertTrue($validation->decimal('.277', '3'), 'Expected valid 3 decimal places');
		$this->assertTrue($validation->decimal(.277, 3), 'Expected valid 3 decimal places');
		$this->assertTrue($validation->decimal(-.277, 3), 'Expected valid 3 decimal places');
		$this->assertTrue($validation->decimal(+.277, 3), 'Expected valid 3 decimal places');
		$this->assertTrue($validation->decimal('1234.5678', '4'), 'Expected valid 4 decimal places');
		$this->assertTrue($validation->decimal(1234.5678, 4), 'Expected valid 4 decimal places');
		$this->assertTrue($validation->decimal(-1234.5678, 4), 'Expected valid 4 decimal places');
		$this->assertTrue($validation->decimal(+1234.5678, 4), 'Expected valid 4 decimal places');
		$this->assertFalse($validation->decimal('1234.5678', '3'), 'Expected invalid 4 decimal places');
		$this->assertFalse($validation->decimal(1234.5678, 3), 'Expected invalid 4 decimal places');
		$this->assertFalse($validation->decimal(-1234.5678, 3), 'Expected invalid 4 decimal places');
		$this->assertFalse($validation->decimal(+1234.5678, 3), 'Expected invalid 4 decimal places');
	}

	function testDecimalCustomRegex() {
		$validation = new Validation();
		$this->assertTrue($validation->decimal('1.54321', null, '/^[-+]?[0-9]+(\\.[0-9]+)?$/s'), 'Expected valid integer and decimal');
		$this->assertFalse($validation->decimal('.54321', null, '/^[-+]?[0-9]+(\\.[0-9]+)?$/s'), 'Expected invalid decimal only');
	}

	function testEmail() {
		$validation = new Validation();
		$this->assertTrue($validation->email('abc.efg@domain.com'), 'Expected valid email');
		$this->assertTrue($validation->email('efg@domain.com'), 'Expected valid email');
		$this->assertTrue($validation->email('abc-efg@domain.com'), 'Expected valid email');
		$this->assertTrue($validation->email('abc_efg@domain.com'), 'Expected valid email');
		$this->assertFalse($validation->email('abc@efg@domain.com'), 'Expected valid email');
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
		$this->assertTrue($validation->email('abc.efg@cakephp.org', null, '/^[A-Z0-9._%-]+@[A-Z0-9.-]+\\.[A-Z]{2,4}$/i'), 'Expected valid email');
		$this->assertFalse($validation->email('abc.efg@com.caphpkeinvalid', null, '/^[A-Z0-9._%-]+@[A-Z0-9.-]+\\.[A-Z]{2,4}$/i'), 'Expected valid email');
	}

	function testIp() {
		$validation = new Validation();
		$this->assertTrue($validation->ip('0.0.0.0'), 'Expected valid IP address');
		$this->assertTrue($validation->ip('255.255.255.255'), 'Expected valid IP address');
		$this->assertFalse($validation->ip('127.0.0'), 'Expected invalid IP address');
		$this->assertFalse($validation->ip('127.0.0.a'), 'Expected invalid IP address');
		$this->assertFalse($validation->ip('127.0.0.256'), 'Expected invalid IP address');
	}

	function testMaxLength() {
		$validation = new Validation();
		$this->assertTrue($validation->maxLength('ab', 3), 'Expected valid length');
		$this->assertTrue($validation->maxLength('abc', 3), 'Expected valid length');
		$this->assertFalse($validation->maxLength('abcd', 3), 'Expected invalid length');
	}

	function testMinLength() {
		$validation = new Validation();
		$this->assertFalse($validation->minLength('ab', 3), 'Expected invalid length');
		$this->assertTrue($validation->minLength('abc', 3), 'Expected valid length');
		$this->assertTrue($validation->minLength('abcd', 3), 'Expected valid length');
	}

	function testUrl() {
		$validation = new Validation();
		$this->assertTrue($validation->url('https://my.gizmoproject.com/gizmo/app?class=MySip;proc=start'), 'Expected valid url');
	}
}
?>