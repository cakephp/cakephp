<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Validation;

use Cake\Core\Configure;
use Cake\Filesystem\File;
use Cake\I18n\I18n;
use Cake\TestSuite\TestCase;
use Cake\Validation\Validation;
use Locale;

require_once __DIR__ . '/stubs.php';

/**
 * Test Case for Validation Class
 *
 */
class ValidationTest extends TestCase
{

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->_appEncoding = Configure::read('App.encoding');
        $this->locale = Locale::getDefault();
        Locale::setDefault('en_US');
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        Configure::write('App.encoding', $this->_appEncoding);
        Locale::setDefault($this->locale);
    }

    /**
     * testNotEmpty method
     *
     * @return void
     */
    public function testNotEmpty()
    {
        $this->assertTrue(Validation::notBlank('abcdefg'));
        $this->assertTrue(Validation::notBlank('fasdf '));
        $this->assertTrue(Validation::notBlank('fooo' . chr(243) . 'blabla'));
        $this->assertTrue(Validation::notBlank('abçďĕʑʘπй'));
        $this->assertTrue(Validation::notBlank('José'));
        $this->assertTrue(Validation::notBlank('é'));
        $this->assertTrue(Validation::notBlank('π'));
        $this->assertFalse(Validation::notBlank("\t "));
        $this->assertFalse(Validation::notBlank(""));
    }

    /**
     * testNotEmptyISO88591Encoding method
     *
     * @return void
     */
    public function testNotEmptyISO88591AppEncoding()
    {
        Configure::write('App.encoding', 'ISO-8859-1');
        $this->assertTrue(Validation::notBlank('abcdefg'));
        $this->assertTrue(Validation::notBlank('fasdf '));
        $this->assertTrue(Validation::notBlank('fooo' . chr(243) . 'blabla'));
        $this->assertTrue(Validation::notBlank('abçďĕʑʘπй'));
        $this->assertTrue(Validation::notBlank('José'));
        $this->assertTrue(Validation::notBlank(utf8_decode('José')));
        $this->assertFalse(Validation::notBlank("\t "));
        $this->assertFalse(Validation::notBlank(""));
    }

    /**
     * testAlphaNumeric method
     *
     * @return void
     */
    public function testAlphaNumeric()
    {
        $this->assertTrue(Validation::alphaNumeric('frferrf'));
        $this->assertTrue(Validation::alphaNumeric('12234'));
        $this->assertTrue(Validation::alphaNumeric('1w2e2r3t4y'));
        $this->assertTrue(Validation::alphaNumeric('0'));
        $this->assertTrue(Validation::alphaNumeric('abçďĕʑʘπй'));
        $this->assertTrue(Validation::alphaNumeric('ˇˆๆゞ'));
        $this->assertTrue(Validation::alphaNumeric('אกあアꀀ豈'));
        $this->assertTrue(Validation::alphaNumeric('ǅᾈᾨ'));
        $this->assertTrue(Validation::alphaNumeric('ÆΔΩЖÇ'));

        $this->assertFalse(Validation::alphaNumeric('12 234'));
        $this->assertFalse(Validation::alphaNumeric('dfd 234'));
        $this->assertFalse(Validation::alphaNumeric("0\n"));
        $this->assertFalse(Validation::alphaNumeric("\n"));
        $this->assertFalse(Validation::alphaNumeric("\t"));
        $this->assertFalse(Validation::alphaNumeric("\r"));
        $this->assertFalse(Validation::alphaNumeric(' '));
        $this->assertFalse(Validation::alphaNumeric(''));
    }

    /**
     * testAlphaNumericPassedAsArray method
     *
     * @return void
     */
    public function testAlphaNumericPassedAsArray()
    {
        $this->assertTrue(Validation::alphaNumeric('frferrf'));
        $this->assertTrue(Validation::alphaNumeric('12234'));
        $this->assertTrue(Validation::alphaNumeric('1w2e2r3t4y'));
        $this->assertTrue(Validation::alphaNumeric('0'));
        $this->assertFalse(Validation::alphaNumeric('12 234'));
        $this->assertFalse(Validation::alphaNumeric('dfd 234'));
        $this->assertFalse(Validation::alphaNumeric("\n"));
        $this->assertFalse(Validation::alphaNumeric("\t"));
        $this->assertFalse(Validation::alphaNumeric("\r"));
        $this->assertFalse(Validation::alphaNumeric(' '));
        $this->assertFalse(Validation::alphaNumeric(''));
        $this->assertFalse(Validation::alphaNumeric(['foo']));
    }

    /**
     * testLengthBetween method
     *
     * @return void
     */
    public function testLengthBetween()
    {
        $this->assertTrue(Validation::lengthBetween('abcdefg', 1, 7));
        $this->assertTrue(Validation::lengthBetween('', 0, 7));
        $this->assertTrue(Validation::lengthBetween('אกあアꀀ豈', 1, 7));

        $this->assertFalse(Validation::lengthBetween('abcdefg', 1, 6));
        $this->assertFalse(Validation::lengthBetween('ÆΔΩЖÇ', 1, 3));
    }

    /**
     * testcc method
     *
     * @return void
     */
    public function testCc()
    {
        //American Express
        $this->assertTrue(Validation::cc('370482756063980', ['amex']));
        $this->assertTrue(Validation::cc('349106433773483', ['amex']));
        $this->assertTrue(Validation::cc('344671486204764', ['amex']));
        $this->assertTrue(Validation::cc('344042544509943', ['amex']));
        $this->assertTrue(Validation::cc('377147515754475', ['amex']));
        $this->assertTrue(Validation::cc('375239372816422', ['amex']));
        $this->assertTrue(Validation::cc('376294341957707', ['amex']));
        $this->assertTrue(Validation::cc('341779292230411', ['amex']));
        $this->assertTrue(Validation::cc('341646919853372', ['amex']));
        $this->assertTrue(Validation::cc('348498616319346', ['amex']));
        //BankCard
        $this->assertTrue(Validation::cc('5610745867413420', ['bankcard']));
        $this->assertTrue(Validation::cc('5610376649499352', ['bankcard']));
        $this->assertTrue(Validation::cc('5610091936000694', ['bankcard']));
        $this->assertTrue(Validation::cc('5602248780118788', ['bankcard']));
        $this->assertTrue(Validation::cc('5610631567676765', ['bankcard']));
        $this->assertTrue(Validation::cc('5602238211270795', ['bankcard']));
        $this->assertTrue(Validation::cc('5610173951215470', ['bankcard']));
        $this->assertTrue(Validation::cc('5610139705753702', ['bankcard']));
        $this->assertTrue(Validation::cc('5602226032150551', ['bankcard']));
        $this->assertTrue(Validation::cc('5602223993735777', ['bankcard']));
        //Diners Club 14
        $this->assertTrue(Validation::cc('30155483651028', ['diners']));
        $this->assertTrue(Validation::cc('36371312803821', ['diners']));
        $this->assertTrue(Validation::cc('38801277489875', ['diners']));
        $this->assertTrue(Validation::cc('30348560464296', ['diners']));
        $this->assertTrue(Validation::cc('30349040317708', ['diners']));
        $this->assertTrue(Validation::cc('36567413559978', ['diners']));
        $this->assertTrue(Validation::cc('36051554732702', ['diners']));
        $this->assertTrue(Validation::cc('30391842198191', ['diners']));
        $this->assertTrue(Validation::cc('30172682197745', ['diners']));
        $this->assertTrue(Validation::cc('30162056566641', ['diners']));
        $this->assertTrue(Validation::cc('30085066927745', ['diners']));
        $this->assertTrue(Validation::cc('36519025221976', ['diners']));
        $this->assertTrue(Validation::cc('30372679371044', ['diners']));
        $this->assertTrue(Validation::cc('38913939150124', ['diners']));
        $this->assertTrue(Validation::cc('36852899094637', ['diners']));
        $this->assertTrue(Validation::cc('30138041971120', ['diners']));
        $this->assertTrue(Validation::cc('36184047836838', ['diners']));
        $this->assertTrue(Validation::cc('30057460264462', ['diners']));
        $this->assertTrue(Validation::cc('38980165212050', ['diners']));
        $this->assertTrue(Validation::cc('30356516881240', ['diners']));
        $this->assertTrue(Validation::cc('38744810033182', ['diners']));
        $this->assertTrue(Validation::cc('30173638706621', ['diners']));
        $this->assertTrue(Validation::cc('30158334709185', ['diners']));
        $this->assertTrue(Validation::cc('30195413721186', ['diners']));
        $this->assertTrue(Validation::cc('38863347694793', ['diners']));
        $this->assertTrue(Validation::cc('30275627009113', ['diners']));
        $this->assertTrue(Validation::cc('30242860404971', ['diners']));
        $this->assertTrue(Validation::cc('30081877595151', ['diners']));
        $this->assertTrue(Validation::cc('38053196067461', ['diners']));
        $this->assertTrue(Validation::cc('36520379984870', ['diners']));
        //2004 MasterCard/Diners Club Alliance International 14
        $this->assertTrue(Validation::cc('36747701998969', ['diners']));
        $this->assertTrue(Validation::cc('36427861123159', ['diners']));
        $this->assertTrue(Validation::cc('36150537602386', ['diners']));
        $this->assertTrue(Validation::cc('36582388820610', ['diners']));
        $this->assertTrue(Validation::cc('36729045250216', ['diners']));
        //2004 MasterCard/Diners Club Alliance US & Canada 16
        $this->assertTrue(Validation::cc('5597511346169950', ['diners']));
        $this->assertTrue(Validation::cc('5526443162217562', ['diners']));
        $this->assertTrue(Validation::cc('5577265786122391', ['diners']));
        $this->assertTrue(Validation::cc('5534061404676989', ['diners']));
        $this->assertTrue(Validation::cc('5545313588374502', ['diners']));
        //Discover
        $this->assertTrue(Validation::cc('6011802876467237', ['disc']));
        $this->assertTrue(Validation::cc('6506432777720955', ['disc']));
        $this->assertTrue(Validation::cc('6011126265283942', ['disc']));
        $this->assertTrue(Validation::cc('6502187151579252', ['disc']));
        $this->assertTrue(Validation::cc('6506600836002298', ['disc']));
        $this->assertTrue(Validation::cc('6504376463615189', ['disc']));
        $this->assertTrue(Validation::cc('6011440907005377', ['disc']));
        $this->assertTrue(Validation::cc('6509735979634270', ['disc']));
        $this->assertTrue(Validation::cc('6011422366775856', ['disc']));
        $this->assertTrue(Validation::cc('6500976374623323', ['disc']));
        //enRoute
        $this->assertTrue(Validation::cc('201496944158937', ['enroute']));
        $this->assertTrue(Validation::cc('214945833739665', ['enroute']));
        $this->assertTrue(Validation::cc('214982692491187', ['enroute']));
        $this->assertTrue(Validation::cc('214901395949424', ['enroute']));
        $this->assertTrue(Validation::cc('201480676269187', ['enroute']));
        $this->assertTrue(Validation::cc('214911922887807', ['enroute']));
        $this->assertTrue(Validation::cc('201485025457250', ['enroute']));
        $this->assertTrue(Validation::cc('201402662758866', ['enroute']));
        $this->assertTrue(Validation::cc('214981579370225', ['enroute']));
        $this->assertTrue(Validation::cc('201447595859877', ['enroute']));
        //JCB 15 digit
        $this->assertTrue(Validation::cc('210034762247893', ['jcb']));
        $this->assertTrue(Validation::cc('180078671678892', ['jcb']));
        $this->assertTrue(Validation::cc('180010559353736', ['jcb']));
        $this->assertTrue(Validation::cc('210095474464258', ['jcb']));
        $this->assertTrue(Validation::cc('210006675562188', ['jcb']));
        $this->assertTrue(Validation::cc('210063299662662', ['jcb']));
        $this->assertTrue(Validation::cc('180032506857825', ['jcb']));
        $this->assertTrue(Validation::cc('210057919192738', ['jcb']));
        $this->assertTrue(Validation::cc('180031358949367', ['jcb']));
        $this->assertTrue(Validation::cc('180033802147846', ['jcb']));
        //JCB 16 digit
        $this->assertTrue(Validation::cc('3096806857839939', ['jcb']));
        $this->assertTrue(Validation::cc('3158699503187091', ['jcb']));
        $this->assertTrue(Validation::cc('3112549607186579', ['jcb']));
        $this->assertTrue(Validation::cc('3112332922425604', ['jcb']));
        $this->assertTrue(Validation::cc('3112001541159239', ['jcb']));
        $this->assertTrue(Validation::cc('3112162495317841', ['jcb']));
        $this->assertTrue(Validation::cc('3337562627732768', ['jcb']));
        $this->assertTrue(Validation::cc('3337107161330775', ['jcb']));
        $this->assertTrue(Validation::cc('3528053736003621', ['jcb']));
        $this->assertTrue(Validation::cc('3528915255020360', ['jcb']));
        $this->assertTrue(Validation::cc('3096786059660921', ['jcb']));
        $this->assertTrue(Validation::cc('3528264799292320', ['jcb']));
        $this->assertTrue(Validation::cc('3096469164130136', ['jcb']));
        $this->assertTrue(Validation::cc('3112127443822853', ['jcb']));
        $this->assertTrue(Validation::cc('3096849995802328', ['jcb']));
        $this->assertTrue(Validation::cc('3528090735127407', ['jcb']));
        $this->assertTrue(Validation::cc('3112101006819234', ['jcb']));
        $this->assertTrue(Validation::cc('3337444428040784', ['jcb']));
        $this->assertTrue(Validation::cc('3088043154151061', ['jcb']));
        $this->assertTrue(Validation::cc('3088295969414866', ['jcb']));
        $this->assertTrue(Validation::cc('3158748843158575', ['jcb']));
        $this->assertTrue(Validation::cc('3158709206148538', ['jcb']));
        $this->assertTrue(Validation::cc('3158365159575324', ['jcb']));
        $this->assertTrue(Validation::cc('3158671691305165', ['jcb']));
        $this->assertTrue(Validation::cc('3528523028771093', ['jcb']));
        $this->assertTrue(Validation::cc('3096057126267870', ['jcb']));
        $this->assertTrue(Validation::cc('3158514047166834', ['jcb']));
        $this->assertTrue(Validation::cc('3528274546125962', ['jcb']));
        $this->assertTrue(Validation::cc('3528890967705733', ['jcb']));
        $this->assertTrue(Validation::cc('3337198811307545', ['jcb']));
        //Maestro (debit card)
        $this->assertTrue(Validation::cc('5020147409985219', ['maestro']));
        $this->assertTrue(Validation::cc('5020931809905616', ['maestro']));
        $this->assertTrue(Validation::cc('5020412965470224', ['maestro']));
        $this->assertTrue(Validation::cc('5020129740944022', ['maestro']));
        $this->assertTrue(Validation::cc('5020024696747943', ['maestro']));
        $this->assertTrue(Validation::cc('5020581514636509', ['maestro']));
        $this->assertTrue(Validation::cc('5020695008411987', ['maestro']));
        $this->assertTrue(Validation::cc('5020565359718977', ['maestro']));
        $this->assertTrue(Validation::cc('6339931536544062', ['maestro']));
        $this->assertTrue(Validation::cc('6465028615704406', ['maestro']));
        //Mastercard
        $this->assertTrue(Validation::cc('5580424361774366', ['mc']));
        $this->assertTrue(Validation::cc('5589563059318282', ['mc']));
        $this->assertTrue(Validation::cc('5387558333690047', ['mc']));
        $this->assertTrue(Validation::cc('5163919215247175', ['mc']));
        $this->assertTrue(Validation::cc('5386742685055055', ['mc']));
        $this->assertTrue(Validation::cc('5102303335960674', ['mc']));
        $this->assertTrue(Validation::cc('5526543403964565', ['mc']));
        $this->assertTrue(Validation::cc('5538725892618432', ['mc']));
        $this->assertTrue(Validation::cc('5119543573129778', ['mc']));
        $this->assertTrue(Validation::cc('5391174753915767', ['mc']));
        $this->assertTrue(Validation::cc('5510994113980714', ['mc']));
        $this->assertTrue(Validation::cc('5183720260418091', ['mc']));
        $this->assertTrue(Validation::cc('5488082196086704', ['mc']));
        $this->assertTrue(Validation::cc('5484645164161834', ['mc']));
        $this->assertTrue(Validation::cc('5171254350337031', ['mc']));
        $this->assertTrue(Validation::cc('5526987528136452', ['mc']));
        $this->assertTrue(Validation::cc('5504148941409358', ['mc']));
        $this->assertTrue(Validation::cc('5240793507243615', ['mc']));
        $this->assertTrue(Validation::cc('5162114693017107', ['mc']));
        $this->assertTrue(Validation::cc('5163104807404753', ['mc']));
        $this->assertTrue(Validation::cc('5590136167248365', ['mc']));
        $this->assertTrue(Validation::cc('5565816281038948', ['mc']));
        $this->assertTrue(Validation::cc('5467639122779531', ['mc']));
        $this->assertTrue(Validation::cc('5297350261550024', ['mc']));
        $this->assertTrue(Validation::cc('5162739131368058', ['mc']));
        //Solo 16
        $this->assertTrue(Validation::cc('6767432107064987', ['solo']));
        $this->assertTrue(Validation::cc('6334667758225411', ['solo']));
        $this->assertTrue(Validation::cc('6767037421954068', ['solo']));
        $this->assertTrue(Validation::cc('6767823306394854', ['solo']));
        $this->assertTrue(Validation::cc('6334768185398134', ['solo']));
        $this->assertTrue(Validation::cc('6767286729498589', ['solo']));
        $this->assertTrue(Validation::cc('6334972104431261', ['solo']));
        $this->assertTrue(Validation::cc('6334843427400616', ['solo']));
        $this->assertTrue(Validation::cc('6767493947881311', ['solo']));
        $this->assertTrue(Validation::cc('6767194235798817', ['solo']));
        //Solo 18
        $this->assertTrue(Validation::cc('676714834398858593', ['solo']));
        $this->assertTrue(Validation::cc('676751666435130857', ['solo']));
        $this->assertTrue(Validation::cc('676781908573924236', ['solo']));
        $this->assertTrue(Validation::cc('633488724644003240', ['solo']));
        $this->assertTrue(Validation::cc('676732252338067316', ['solo']));
        $this->assertTrue(Validation::cc('676747520084495821', ['solo']));
        $this->assertTrue(Validation::cc('633465488901381957', ['solo']));
        $this->assertTrue(Validation::cc('633487484858610484', ['solo']));
        $this->assertTrue(Validation::cc('633453764680740694', ['solo']));
        $this->assertTrue(Validation::cc('676768613295414451', ['solo']));
        //Solo 19
        $this->assertTrue(Validation::cc('6767838565218340113', ['solo']));
        $this->assertTrue(Validation::cc('6767760119829705181', ['solo']));
        $this->assertTrue(Validation::cc('6767265917091593668', ['solo']));
        $this->assertTrue(Validation::cc('6767938856947440111', ['solo']));
        $this->assertTrue(Validation::cc('6767501945697390076', ['solo']));
        $this->assertTrue(Validation::cc('6334902868716257379', ['solo']));
        $this->assertTrue(Validation::cc('6334922127686425532', ['solo']));
        $this->assertTrue(Validation::cc('6334933119080706440', ['solo']));
        $this->assertTrue(Validation::cc('6334647959628261714', ['solo']));
        $this->assertTrue(Validation::cc('6334527312384101382', ['solo']));
        //Switch 16
        $this->assertTrue(Validation::cc('5641829171515733', ['switch']));
        $this->assertTrue(Validation::cc('5641824852820809', ['switch']));
        $this->assertTrue(Validation::cc('6759129648956909', ['switch']));
        $this->assertTrue(Validation::cc('6759626072268156', ['switch']));
        $this->assertTrue(Validation::cc('5641822698388957', ['switch']));
        $this->assertTrue(Validation::cc('5641827123105470', ['switch']));
        $this->assertTrue(Validation::cc('5641823755819553', ['switch']));
        $this->assertTrue(Validation::cc('5641821939587682', ['switch']));
        $this->assertTrue(Validation::cc('4936097148079186', ['switch']));
        $this->assertTrue(Validation::cc('5641829739125009', ['switch']));
        $this->assertTrue(Validation::cc('5641822860725507', ['switch']));
        $this->assertTrue(Validation::cc('4936717688865831', ['switch']));
        $this->assertTrue(Validation::cc('6759487613615441', ['switch']));
        $this->assertTrue(Validation::cc('5641821346840617', ['switch']));
        $this->assertTrue(Validation::cc('5641825793417126', ['switch']));
        $this->assertTrue(Validation::cc('5641821302759595', ['switch']));
        $this->assertTrue(Validation::cc('6759784969918837', ['switch']));
        $this->assertTrue(Validation::cc('5641824910667036', ['switch']));
        $this->assertTrue(Validation::cc('6759139909636173', ['switch']));
        $this->assertTrue(Validation::cc('6333425070638022', ['switch']));
        $this->assertTrue(Validation::cc('5641823910382067', ['switch']));
        $this->assertTrue(Validation::cc('4936295218139423', ['switch']));
        $this->assertTrue(Validation::cc('6333031811316199', ['switch']));
        $this->assertTrue(Validation::cc('4936912044763198', ['switch']));
        $this->assertTrue(Validation::cc('4936387053303824', ['switch']));
        $this->assertTrue(Validation::cc('6759535838760523', ['switch']));
        $this->assertTrue(Validation::cc('6333427174594051', ['switch']));
        $this->assertTrue(Validation::cc('5641829037102700', ['switch']));
        $this->assertTrue(Validation::cc('5641826495463046', ['switch']));
        $this->assertTrue(Validation::cc('6333480852979946', ['switch']));
        $this->assertTrue(Validation::cc('5641827761302876', ['switch']));
        $this->assertTrue(Validation::cc('5641825083505317', ['switch']));
        $this->assertTrue(Validation::cc('6759298096003991', ['switch']));
        $this->assertTrue(Validation::cc('4936119165483420', ['switch']));
        $this->assertTrue(Validation::cc('4936190990500993', ['switch']));
        $this->assertTrue(Validation::cc('4903356467384927', ['switch']));
        $this->assertTrue(Validation::cc('6333372765092554', ['switch']));
        $this->assertTrue(Validation::cc('5641821330950570', ['switch']));
        $this->assertTrue(Validation::cc('6759841558826118', ['switch']));
        $this->assertTrue(Validation::cc('4936164540922452', ['switch']));
        //Switch 18
        $this->assertTrue(Validation::cc('493622764224625174', ['switch']));
        $this->assertTrue(Validation::cc('564182823396913535', ['switch']));
        $this->assertTrue(Validation::cc('675917308304801234', ['switch']));
        $this->assertTrue(Validation::cc('675919890024220298', ['switch']));
        $this->assertTrue(Validation::cc('633308376862556751', ['switch']));
        $this->assertTrue(Validation::cc('564182377633208779', ['switch']));
        $this->assertTrue(Validation::cc('564182870014926787', ['switch']));
        $this->assertTrue(Validation::cc('675979788553829819', ['switch']));
        $this->assertTrue(Validation::cc('493668394358130935', ['switch']));
        $this->assertTrue(Validation::cc('493637431790930965', ['switch']));
        $this->assertTrue(Validation::cc('633321438601941513', ['switch']));
        $this->assertTrue(Validation::cc('675913800898840986', ['switch']));
        $this->assertTrue(Validation::cc('564182592016841547', ['switch']));
        $this->assertTrue(Validation::cc('564182428380440899', ['switch']));
        $this->assertTrue(Validation::cc('493696376827623463', ['switch']));
        $this->assertTrue(Validation::cc('675977939286485757', ['switch']));
        $this->assertTrue(Validation::cc('490302699502091579', ['switch']));
        $this->assertTrue(Validation::cc('564182085013662230', ['switch']));
        $this->assertTrue(Validation::cc('493693054263310167', ['switch']));
        $this->assertTrue(Validation::cc('633321755966697525', ['switch']));
        $this->assertTrue(Validation::cc('675996851719732811', ['switch']));
        $this->assertTrue(Validation::cc('493699211208281028', ['switch']));
        $this->assertTrue(Validation::cc('493697817378356614', ['switch']));
        $this->assertTrue(Validation::cc('675968224161768150', ['switch']));
        $this->assertTrue(Validation::cc('493669416873337627', ['switch']));
        $this->assertTrue(Validation::cc('564182439172549714', ['switch']));
        $this->assertTrue(Validation::cc('675926914467673598', ['switch']));
        $this->assertTrue(Validation::cc('564182565231977809', ['switch']));
        $this->assertTrue(Validation::cc('675966282607849002', ['switch']));
        $this->assertTrue(Validation::cc('493691609704348548', ['switch']));
        $this->assertTrue(Validation::cc('675933118546065120', ['switch']));
        $this->assertTrue(Validation::cc('493631116677238592', ['switch']));
        $this->assertTrue(Validation::cc('675921142812825938', ['switch']));
        $this->assertTrue(Validation::cc('633338311815675113', ['switch']));
        $this->assertTrue(Validation::cc('633323539867338621', ['switch']));
        $this->assertTrue(Validation::cc('675964912740845663', ['switch']));
        $this->assertTrue(Validation::cc('633334008833727504', ['switch']));
        $this->assertTrue(Validation::cc('493631941273687169', ['switch']));
        $this->assertTrue(Validation::cc('564182971729706785', ['switch']));
        $this->assertTrue(Validation::cc('633303461188963496', ['switch']));
        //Switch 19
        $this->assertTrue(Validation::cc('6759603460617628716', ['switch']));
        $this->assertTrue(Validation::cc('4936705825268647681', ['switch']));
        $this->assertTrue(Validation::cc('5641829846600479183', ['switch']));
        $this->assertTrue(Validation::cc('6759389846573792530', ['switch']));
        $this->assertTrue(Validation::cc('4936189558712637603', ['switch']));
        $this->assertTrue(Validation::cc('5641822217393868189', ['switch']));
        $this->assertTrue(Validation::cc('4903075563780057152', ['switch']));
        $this->assertTrue(Validation::cc('4936510653566569547', ['switch']));
        $this->assertTrue(Validation::cc('4936503083627303364', ['switch']));
        $this->assertTrue(Validation::cc('4936777334398116272', ['switch']));
        $this->assertTrue(Validation::cc('5641823876900554860', ['switch']));
        $this->assertTrue(Validation::cc('6759619236903407276', ['switch']));
        $this->assertTrue(Validation::cc('6759011470269978117', ['switch']));
        $this->assertTrue(Validation::cc('6333175833997062502', ['switch']));
        $this->assertTrue(Validation::cc('6759498728789080439', ['switch']));
        $this->assertTrue(Validation::cc('4903020404168157841', ['switch']));
        $this->assertTrue(Validation::cc('6759354334874804313', ['switch']));
        $this->assertTrue(Validation::cc('6759900856420875115', ['switch']));
        $this->assertTrue(Validation::cc('5641827269346868860', ['switch']));
        $this->assertTrue(Validation::cc('5641828995047453870', ['switch']));
        $this->assertTrue(Validation::cc('6333321884754806543', ['switch']));
        $this->assertTrue(Validation::cc('6333108246283715901', ['switch']));
        $this->assertTrue(Validation::cc('6759572372800700102', ['switch']));
        $this->assertTrue(Validation::cc('4903095096797974933', ['switch']));
        $this->assertTrue(Validation::cc('6333354315797920215', ['switch']));
        $this->assertTrue(Validation::cc('6759163746089433755', ['switch']));
        $this->assertTrue(Validation::cc('6759871666634807647', ['switch']));
        $this->assertTrue(Validation::cc('5641827883728575248', ['switch']));
        $this->assertTrue(Validation::cc('4936527975051407847', ['switch']));
        $this->assertTrue(Validation::cc('5641823318396882141', ['switch']));
        $this->assertTrue(Validation::cc('6759123772311123708', ['switch']));
        $this->assertTrue(Validation::cc('4903054736148271088', ['switch']));
        $this->assertTrue(Validation::cc('4936477526808883952', ['switch']));
        $this->assertTrue(Validation::cc('4936433964890967966', ['switch']));
        $this->assertTrue(Validation::cc('6333245128906049344', ['switch']));
        $this->assertTrue(Validation::cc('4936321036970553134', ['switch']));
        $this->assertTrue(Validation::cc('4936111816358702773', ['switch']));
        $this->assertTrue(Validation::cc('4936196077254804290', ['switch']));
        $this->assertTrue(Validation::cc('6759558831206830183', ['switch']));
        $this->assertTrue(Validation::cc('5641827998830403137', ['switch']));
        //VISA 13 digit
        $this->assertTrue(Validation::cc('4024007174754', ['visa']));
        $this->assertTrue(Validation::cc('4104816460717', ['visa']));
        $this->assertTrue(Validation::cc('4716229700437', ['visa']));
        $this->assertTrue(Validation::cc('4539305400213', ['visa']));
        $this->assertTrue(Validation::cc('4728260558665', ['visa']));
        $this->assertTrue(Validation::cc('4929100131792', ['visa']));
        $this->assertTrue(Validation::cc('4024007117308', ['visa']));
        $this->assertTrue(Validation::cc('4539915491024', ['visa']));
        $this->assertTrue(Validation::cc('4539790901139', ['visa']));
        $this->assertTrue(Validation::cc('4485284914909', ['visa']));
        $this->assertTrue(Validation::cc('4782793022350', ['visa']));
        $this->assertTrue(Validation::cc('4556899290685', ['visa']));
        $this->assertTrue(Validation::cc('4024007134774', ['visa']));
        $this->assertTrue(Validation::cc('4333412341316', ['visa']));
        $this->assertTrue(Validation::cc('4539534204543', ['visa']));
        $this->assertTrue(Validation::cc('4485640373626', ['visa']));
        $this->assertTrue(Validation::cc('4929911445746', ['visa']));
        $this->assertTrue(Validation::cc('4539292550806', ['visa']));
        $this->assertTrue(Validation::cc('4716523014030', ['visa']));
        $this->assertTrue(Validation::cc('4024007125152', ['visa']));
        $this->assertTrue(Validation::cc('4539758883311', ['visa']));
        $this->assertTrue(Validation::cc('4024007103258', ['visa']));
        $this->assertTrue(Validation::cc('4916933155767', ['visa']));
        $this->assertTrue(Validation::cc('4024007159672', ['visa']));
        $this->assertTrue(Validation::cc('4716935544871', ['visa']));
        $this->assertTrue(Validation::cc('4929415177779', ['visa']));
        $this->assertTrue(Validation::cc('4929748547896', ['visa']));
        $this->assertTrue(Validation::cc('4929153468612', ['visa']));
        $this->assertTrue(Validation::cc('4539397132104', ['visa']));
        $this->assertTrue(Validation::cc('4485293435540', ['visa']));
        $this->assertTrue(Validation::cc('4485799412720', ['visa']));
        $this->assertTrue(Validation::cc('4916744757686', ['visa']));
        $this->assertTrue(Validation::cc('4556475655426', ['visa']));
        $this->assertTrue(Validation::cc('4539400441625', ['visa']));
        $this->assertTrue(Validation::cc('4485437129173', ['visa']));
        $this->assertTrue(Validation::cc('4716253605320', ['visa']));
        $this->assertTrue(Validation::cc('4539366156589', ['visa']));
        $this->assertTrue(Validation::cc('4916498061392', ['visa']));
        $this->assertTrue(Validation::cc('4716127163779', ['visa']));
        $this->assertTrue(Validation::cc('4024007183078', ['visa']));
        $this->assertTrue(Validation::cc('4041553279654', ['visa']));
        $this->assertTrue(Validation::cc('4532380121960', ['visa']));
        $this->assertTrue(Validation::cc('4485906062491', ['visa']));
        $this->assertTrue(Validation::cc('4539365115149', ['visa']));
        $this->assertTrue(Validation::cc('4485146516702', ['visa']));
        //VISA 16 digit
        $this->assertTrue(Validation::cc('4916375389940009', ['visa']));
        $this->assertTrue(Validation::cc('4929167481032610', ['visa']));
        $this->assertTrue(Validation::cc('4485029969061519', ['visa']));
        $this->assertTrue(Validation::cc('4485573845281759', ['visa']));
        $this->assertTrue(Validation::cc('4485669810383529', ['visa']));
        $this->assertTrue(Validation::cc('4929615806560327', ['visa']));
        $this->assertTrue(Validation::cc('4556807505609535', ['visa']));
        $this->assertTrue(Validation::cc('4532611336232890', ['visa']));
        $this->assertTrue(Validation::cc('4532201952422387', ['visa']));
        $this->assertTrue(Validation::cc('4485073797976290', ['visa']));
        $this->assertTrue(Validation::cc('4024007157580969', ['visa']));
        $this->assertTrue(Validation::cc('4053740470212274', ['visa']));
        $this->assertTrue(Validation::cc('4716265831525676', ['visa']));
        $this->assertTrue(Validation::cc('4024007100222966', ['visa']));
        $this->assertTrue(Validation::cc('4539556148303244', ['visa']));
        $this->assertTrue(Validation::cc('4532449879689709', ['visa']));
        $this->assertTrue(Validation::cc('4916805467840986', ['visa']));
        $this->assertTrue(Validation::cc('4532155644440233', ['visa']));
        $this->assertTrue(Validation::cc('4467977802223781', ['visa']));
        $this->assertTrue(Validation::cc('4539224637000686', ['visa']));
        $this->assertTrue(Validation::cc('4556629187064965', ['visa']));
        $this->assertTrue(Validation::cc('4532970205932943', ['visa']));
        $this->assertTrue(Validation::cc('4821470132041850', ['visa']));
        $this->assertTrue(Validation::cc('4916214267894485', ['visa']));
        $this->assertTrue(Validation::cc('4024007169073284', ['visa']));
        $this->assertTrue(Validation::cc('4716783351296122', ['visa']));
        $this->assertTrue(Validation::cc('4556480171913795', ['visa']));
        $this->assertTrue(Validation::cc('4929678411034997', ['visa']));
        $this->assertTrue(Validation::cc('4682061913519392', ['visa']));
        $this->assertTrue(Validation::cc('4916495481746474', ['visa']));
        $this->assertTrue(Validation::cc('4929007108460499', ['visa']));
        $this->assertTrue(Validation::cc('4539951357838586', ['visa']));
        $this->assertTrue(Validation::cc('4716482691051558', ['visa']));
        $this->assertTrue(Validation::cc('4916385069917516', ['visa']));
        $this->assertTrue(Validation::cc('4929020289494641', ['visa']));
        $this->assertTrue(Validation::cc('4532176245263774', ['visa']));
        $this->assertTrue(Validation::cc('4556242273553949', ['visa']));
        $this->assertTrue(Validation::cc('4481007485188614', ['visa']));
        $this->assertTrue(Validation::cc('4716533372139623', ['visa']));
        $this->assertTrue(Validation::cc('4929152038152632', ['visa']));
        $this->assertTrue(Validation::cc('4539404037310550', ['visa']));
        $this->assertTrue(Validation::cc('4532800925229140', ['visa']));
        $this->assertTrue(Validation::cc('4916845885268360', ['visa']));
        $this->assertTrue(Validation::cc('4394514669078434', ['visa']));
        $this->assertTrue(Validation::cc('4485611378115042', ['visa']));
        //Visa Electron
        $this->assertTrue(Validation::cc('4175003346287100', ['electron']));
        $this->assertTrue(Validation::cc('4913042516577228', ['electron']));
        $this->assertTrue(Validation::cc('4917592325659381', ['electron']));
        $this->assertTrue(Validation::cc('4917084924450511', ['electron']));
        $this->assertTrue(Validation::cc('4917994610643999', ['electron']));
        $this->assertTrue(Validation::cc('4175005933743585', ['electron']));
        $this->assertTrue(Validation::cc('4175008373425044', ['electron']));
        $this->assertTrue(Validation::cc('4913119763664154', ['electron']));
        $this->assertTrue(Validation::cc('4913189017481812', ['electron']));
        $this->assertTrue(Validation::cc('4913085104968622', ['electron']));
        $this->assertTrue(Validation::cc('4175008803122021', ['electron']));
        $this->assertTrue(Validation::cc('4913294453962489', ['electron']));
        $this->assertTrue(Validation::cc('4175009797419290', ['electron']));
        $this->assertTrue(Validation::cc('4175005028142917', ['electron']));
        $this->assertTrue(Validation::cc('4913940802385364', ['electron']));
        //Voyager
        $this->assertTrue(Validation::cc('869940697287073', ['voyager']));
        $this->assertTrue(Validation::cc('869934523596112', ['voyager']));
        $this->assertTrue(Validation::cc('869958670174621', ['voyager']));
        $this->assertTrue(Validation::cc('869921250068209', ['voyager']));
        $this->assertTrue(Validation::cc('869972521242198', ['voyager']));
    }

    /**
     * testLuhn method
     *
     * @return void
     */
    public function testLuhn()
    {
        //American Express
        $this->assertTrue(Validation::luhn('370482756063980'));
        //BankCard
        $this->assertTrue(Validation::luhn('5610745867413420'));
        //Diners Club 14
        $this->assertTrue(Validation::luhn('30155483651028'));
        //2004 MasterCard/Diners Club Alliance International 14
        $this->assertTrue(Validation::luhn('36747701998969'));
        //2004 MasterCard/Diners Club Alliance US & Canada 16
        $this->assertTrue(Validation::luhn('5597511346169950'));
        //Discover
        $this->assertTrue(Validation::luhn('6011802876467237'));
        //enRoute
        $this->assertTrue(Validation::luhn('201496944158937'));
        //JCB 15 digit
        $this->assertTrue(Validation::luhn('210034762247893'));
        //JCB 16 digit
        $this->assertTrue(Validation::luhn('3096806857839939'));
        //Maestro (debit card)
        $this->assertTrue(Validation::luhn('5020147409985219'));
        //Mastercard
        $this->assertTrue(Validation::luhn('5580424361774366'));
        //Solo 16
        $this->assertTrue(Validation::luhn('6767432107064987'));
        //Solo 18
        $this->assertTrue(Validation::luhn('676714834398858593'));
        //Solo 19
        $this->assertTrue(Validation::luhn('6767838565218340113'));
        //Switch 16
        $this->assertTrue(Validation::luhn('5641829171515733'));
        //Switch 18
        $this->assertTrue(Validation::luhn('493622764224625174'));
        //Switch 19
        $this->assertTrue(Validation::luhn('6759603460617628716'));
        //VISA 13 digit
        $this->assertTrue(Validation::luhn('4024007174754'));
        //VISA 16 digit
        $this->assertTrue(Validation::luhn('4916375389940009'));
        //Visa Electron
        $this->assertTrue(Validation::luhn('4175003346287100'));
        //Voyager
        $this->assertTrue(Validation::luhn('869940697287073'));

        $this->assertFalse(Validation::luhn('0000000000000000'));

        $this->assertFalse(Validation::luhn('869940697287173'));
    }

    /**
     * testCustomRegexForCc method
     *
     * @return void
     */
    public function testCustomRegexForCc()
    {
        $this->assertTrue(Validation::cc('370482756063980', null, false, '/123321\\d{11}/'));
        $this->assertFalse(Validation::cc('1233210593374358', null, false, '/123321\\d{11}/'));
    }

    /**
     * testCustomRegexForCcWithLuhnCheck method
     *
     * @return void
     */
    public function testCustomRegexForCcWithLuhnCheck()
    {
        $this->assertTrue(Validation::cc('12332110426226941', null, true, '/123321\\d{11}/'));
        $this->assertFalse(Validation::cc('12332105933743585', null, true, '/123321\\d{11}/'));
        $this->assertFalse(Validation::cc('12332105933743587', null, true, '/123321\\d{11}/'));
        $this->assertFalse(Validation::cc('12312305933743585', null, true, '/123321\\d{11}/'));
    }

    /**
     * testFastCc method
     *
     * @return void
     */
    public function testFastCc()
    {
        // too short
        $this->assertFalse(Validation::cc('123456789012'));
        //American Express
        $this->assertTrue(Validation::cc('370482756063980'));
        //Diners Club 14
        $this->assertTrue(Validation::cc('30155483651028'));
        //2004 MasterCard/Diners Club Alliance International 14
        $this->assertTrue(Validation::cc('36747701998969'));
        //2004 MasterCard/Diners Club Alliance US & Canada 16
        $this->assertTrue(Validation::cc('5597511346169950'));
        //Discover
        $this->assertTrue(Validation::cc('6011802876467237'));
        //Mastercard
        $this->assertTrue(Validation::cc('5580424361774366'));
        //VISA 13 digit
        $this->assertTrue(Validation::cc('4024007174754'));
        //VISA 16 digit
        $this->assertTrue(Validation::cc('4916375389940009'));
        //Visa Electron
        $this->assertTrue(Validation::cc('4175003346287100'));
    }

    /**
     * testAllCc method
     *
     * @return void
     */
    public function testAllCc()
    {
        //American Express
        $this->assertTrue(Validation::cc('370482756063980', 'all'));
        //BankCard
        $this->assertTrue(Validation::cc('5610745867413420', 'all'));
        //Diners Club 14
        $this->assertTrue(Validation::cc('30155483651028', 'all'));
        //2004 MasterCard/Diners Club Alliance International 14
        $this->assertTrue(Validation::cc('36747701998969', 'all'));
        //2004 MasterCard/Diners Club Alliance US & Canada 16
        $this->assertTrue(Validation::cc('5597511346169950', 'all'));
        //Discover
        $this->assertTrue(Validation::cc('6011802876467237', 'all'));
        //enRoute
        $this->assertTrue(Validation::cc('201496944158937', 'all'));
        //JCB 15 digit
        $this->assertTrue(Validation::cc('210034762247893', 'all'));
        //JCB 16 digit
        $this->assertTrue(Validation::cc('3096806857839939', 'all'));
        //Maestro (debit card)
        $this->assertTrue(Validation::cc('5020147409985219', 'all'));
        //Mastercard
        $this->assertTrue(Validation::cc('5580424361774366', 'all'));
        //Solo 16
        $this->assertTrue(Validation::cc('6767432107064987', 'all'));
        //Solo 18
        $this->assertTrue(Validation::cc('676714834398858593', 'all'));
        //Solo 19
        $this->assertTrue(Validation::cc('6767838565218340113', 'all'));
        //Switch 16
        $this->assertTrue(Validation::cc('5641829171515733', 'all'));
        //Switch 18
        $this->assertTrue(Validation::cc('493622764224625174', 'all'));
        //Switch 19
        $this->assertTrue(Validation::cc('6759603460617628716', 'all'));
        //VISA 13 digit
        $this->assertTrue(Validation::cc('4024007174754', 'all'));
        //VISA 16 digit
        $this->assertTrue(Validation::cc('4916375389940009', 'all'));
        //Visa Electron
        $this->assertTrue(Validation::cc('4175003346287100', 'all'));
        //Voyager
        $this->assertTrue(Validation::cc('869940697287073', 'all'));
    }

    /**
     * testAllCcDeep method
     *
     * @return void
     */
    public function testAllCcDeep()
    {
        //American Express
        $this->assertTrue(Validation::cc('370482756063980', 'all', true));
        //BankCard
        $this->assertTrue(Validation::cc('5610745867413420', 'all', true));
        //Diners Club 14
        $this->assertTrue(Validation::cc('30155483651028', 'all', true));
        //2004 MasterCard/Diners Club Alliance International 14
        $this->assertTrue(Validation::cc('36747701998969', 'all', true));
        //2004 MasterCard/Diners Club Alliance US & Canada 16
        $this->assertTrue(Validation::cc('5597511346169950', 'all', true));
        //Discover
        $this->assertTrue(Validation::cc('6011802876467237', 'all', true));
        //enRoute
        $this->assertTrue(Validation::cc('201496944158937', 'all', true));
        //JCB 15 digit
        $this->assertTrue(Validation::cc('210034762247893', 'all', true));
        //JCB 16 digit
        $this->assertTrue(Validation::cc('3096806857839939', 'all', true));
        //Maestro (debit card)
        $this->assertTrue(Validation::cc('5020147409985219', 'all', true));
        //Mastercard
        $this->assertTrue(Validation::cc('5580424361774366', 'all', true));
        //Solo 16
        $this->assertTrue(Validation::cc('6767432107064987', 'all', true));
        //Solo 18
        $this->assertTrue(Validation::cc('676714834398858593', 'all', true));
        //Solo 19
        $this->assertTrue(Validation::cc('6767838565218340113', 'all', true));
        //Switch 16
        $this->assertTrue(Validation::cc('5641829171515733', 'all', true));
        //Switch 18
        $this->assertTrue(Validation::cc('493622764224625174', 'all', true));
        //Switch 19
        $this->assertTrue(Validation::cc('6759603460617628716', 'all', true));
        //VISA 13 digit
        $this->assertTrue(Validation::cc('4024007174754', 'all', true));
        //VISA 16 digit
        $this->assertTrue(Validation::cc('4916375389940009', 'all', true));
        //Visa Electron
        $this->assertTrue(Validation::cc('4175003346287100', 'all', true));
        //Voyager
        $this->assertTrue(Validation::cc('869940697287073', 'all', true));
    }

    /**
     * testComparison method
     *
     * @return void
     */
    public function testComparison()
    {
        $this->assertFalse(Validation::comparison(7, null, 6));
        $this->assertTrue(Validation::comparison(7, 'is greater', 6));
        $this->assertTrue(Validation::comparison(7, '>', 6));
        $this->assertTrue(Validation::comparison(6, 'is less', 7));
        $this->assertTrue(Validation::comparison(6, '<', 7));
        $this->assertTrue(Validation::comparison(7, 'greater or equal', 7));
        $this->assertTrue(Validation::comparison(7, '>=', 7));
        $this->assertTrue(Validation::comparison(7, 'greater or equal', 6));
        $this->assertTrue(Validation::comparison(7, '>=', 6));
        $this->assertTrue(Validation::comparison(6, 'less or equal', 7));
        $this->assertTrue(Validation::comparison(6, '<=', 7));
        $this->assertTrue(Validation::comparison(7, 'equal to', 7));
        $this->assertTrue(Validation::comparison(7, '==', 7));
        $this->assertTrue(Validation::comparison(7, 'not equal', 6));
        $this->assertTrue(Validation::comparison(7, '!=', 6));
        $this->assertFalse(Validation::comparison(6, 'is greater', 7));
        $this->assertFalse(Validation::comparison(6, '>', 7));
        $this->assertFalse(Validation::comparison(7, 'is less', 6));
        $this->assertFalse(Validation::comparison(7, '<', 6));
        $this->assertFalse(Validation::comparison(6, 'greater or equal', 7));
        $this->assertFalse(Validation::comparison(6, '>=', 7));
        $this->assertFalse(Validation::comparison(6, 'greater or equal', 7));
        $this->assertFalse(Validation::comparison(6, '>=', 7));
        $this->assertFalse(Validation::comparison(7, 'less or equal', 6));
        $this->assertFalse(Validation::comparison(7, '<=', 6));
        $this->assertFalse(Validation::comparison(7, 'equal to', 6));
        $this->assertFalse(Validation::comparison(7, '==', 6));
        $this->assertFalse(Validation::comparison(7, 'not equal', 7));
        $this->assertFalse(Validation::comparison(7, '!=', 7));

        $this->assertTrue(Validation::comparison('6.5', '!=', 6));
        $this->assertTrue(Validation::comparison('6.5', '<', 7));
    }

    /**
     * Test comparison casting values before comparisons.
     *
     * @return void
     */
    public function testComparisonTypeChecks()
    {
        $this->assertFalse(Validation::comparison('\x028', '>=', 1), 'hexish encoding fails');
        $this->assertFalse(Validation::comparison('0b010', '>=', 1), 'binary string data fails');
        $this->assertFalse(Validation::comparison('0x01', '>=', 1), 'hex string data fails');
        $this->assertFalse(Validation::comparison('0x1', '>=', 1), 'hex string data fails');

        $this->assertFalse(Validation::comparison('\x028', '>=', 1.5), 'hexish encoding fails');
        $this->assertFalse(Validation::comparison('0b010', '>=', 1.5), 'binary string data fails');
        $this->assertFalse(Validation::comparison('0x02', '>=', 1.5), 'hex string data fails');
    }

    /**
     * testCustom method
     *
     * @return void
     */
    public function testCustom()
    {
        $this->assertTrue(Validation::custom('12345', '/(?<!\\S)\\d++(?!\\S)/'));
        $this->assertFalse(Validation::custom('Text', '/(?<!\\S)\\d++(?!\\S)/'));
        $this->assertFalse(Validation::custom('123.45', '/(?<!\\S)\\d++(?!\\S)/'));
        $this->assertFalse(Validation::custom('missing regex'));
    }

    /**
     * testCustomAsArray method
     *
     * @return void
     */
    public function testCustomAsArray()
    {
        $this->assertTrue(Validation::custom('12345', '/(?<!\\S)\\d++(?!\\S)/'));
        $this->assertFalse(Validation::custom('Text', '/(?<!\\S)\\d++(?!\\S)/'));
        $this->assertFalse(Validation::custom('123.45', '/(?<!\\S)\\d++(?!\\S)/'));
    }

    /**
     * testDateTimeObject
     *
     * @return void
     */
    public function testDateTimeObject()
    {
        $dateTime = new \DateTime();
        $this->assertTrue(Validation::date($dateTime));
        $this->assertTrue(Validation::time($dateTime));
        $this->assertTrue(Validation::dateTime($dateTime));
        $this->assertTrue(Validation::localizedTime($dateTime));

        $dateTime = new \DateTimeImmutable();
        $this->assertTrue(Validation::date($dateTime));
        $this->assertTrue(Validation::time($dateTime));
        $this->assertTrue(Validation::dateTime($dateTime));
        $this->assertTrue(Validation::localizedTime($dateTime));
    }

    /**
     * testDateDdmmyyyy method
     *
     * @return void
     */
    public function testDateDdmmyyyy()
    {
        $this->assertTrue(Validation::date('27-12-2006', ['dmy']));
        $this->assertTrue(Validation::date('27.12.2006', ['dmy']));
        $this->assertTrue(Validation::date('27/12/2006', ['dmy']));
        $this->assertTrue(Validation::date('27 12 2006', ['dmy']));
        $this->assertFalse(Validation::date('00-00-0000', ['dmy']));
        $this->assertFalse(Validation::date('00.00.0000', ['dmy']));
        $this->assertFalse(Validation::date('00/00/0000', ['dmy']));
        $this->assertFalse(Validation::date('00 00 0000', ['dmy']));
        $this->assertFalse(Validation::date('31-11-2006', ['dmy']));
        $this->assertFalse(Validation::date('31.11.2006', ['dmy']));
        $this->assertFalse(Validation::date('31/11/2006', ['dmy']));
        $this->assertFalse(Validation::date('31 11 2006', ['dmy']));
    }

    /**
     * testDateDdmmyyyyLeapYear method
     *
     * @return void
     */
    public function testDateDdmmyyyyLeapYear()
    {
        $this->assertTrue(Validation::date('29-02-2004', ['dmy']));
        $this->assertTrue(Validation::date('29.02.2004', ['dmy']));
        $this->assertTrue(Validation::date('29/02/2004', ['dmy']));
        $this->assertTrue(Validation::date('29 02 2004', ['dmy']));
        $this->assertFalse(Validation::date('29-02-2006', ['dmy']));
        $this->assertFalse(Validation::date('29.02.2006', ['dmy']));
        $this->assertFalse(Validation::date('29/02/2006', ['dmy']));
        $this->assertFalse(Validation::date('29 02 2006', ['dmy']));
    }

    /**
     * testDateDdmmyy method
     *
     * @return void
     */
    public function testDateDdmmyy()
    {
        $this->assertTrue(Validation::date('27-12-06', ['dmy']));
        $this->assertTrue(Validation::date('27.12.06', ['dmy']));
        $this->assertTrue(Validation::date('27/12/06', ['dmy']));
        $this->assertTrue(Validation::date('27 12 06', ['dmy']));
        $this->assertFalse(Validation::date('00-00-00', ['dmy']));
        $this->assertFalse(Validation::date('00.00.00', ['dmy']));
        $this->assertFalse(Validation::date('00/00/00', ['dmy']));
        $this->assertFalse(Validation::date('00 00 00', ['dmy']));
        $this->assertFalse(Validation::date('31-11-06', ['dmy']));
        $this->assertFalse(Validation::date('31.11.06', ['dmy']));
        $this->assertFalse(Validation::date('31/11/06', ['dmy']));
        $this->assertFalse(Validation::date('31 11 06', ['dmy']));
    }

    /**
     * testDateDdmmyyLeapYear method
     *
     * @return void
     */
    public function testDateDdmmyyLeapYear()
    {
        $this->assertTrue(Validation::date('29-02-04', ['dmy']));
        $this->assertTrue(Validation::date('29.02.04', ['dmy']));
        $this->assertTrue(Validation::date('29/02/04', ['dmy']));
        $this->assertTrue(Validation::date('29 02 04', ['dmy']));
        $this->assertFalse(Validation::date('29-02-06', ['dmy']));
        $this->assertFalse(Validation::date('29.02.06', ['dmy']));
        $this->assertFalse(Validation::date('29/02/06', ['dmy']));
        $this->assertFalse(Validation::date('29 02 06', ['dmy']));
    }

    /**
     * testDateDmyy method
     *
     * @return void
     */
    public function testDateDmyy()
    {
        $this->assertTrue(Validation::date('7-2-06', ['dmy']));
        $this->assertTrue(Validation::date('7.2.06', ['dmy']));
        $this->assertTrue(Validation::date('7/2/06', ['dmy']));
        $this->assertTrue(Validation::date('7 2 06', ['dmy']));
        $this->assertFalse(Validation::date('0-0-00', ['dmy']));
        $this->assertFalse(Validation::date('0.0.00', ['dmy']));
        $this->assertFalse(Validation::date('0/0/00', ['dmy']));
        $this->assertFalse(Validation::date('0 0 00', ['dmy']));
        $this->assertFalse(Validation::date('32-2-06', ['dmy']));
        $this->assertFalse(Validation::date('32.2.06', ['dmy']));
        $this->assertFalse(Validation::date('32/2/06', ['dmy']));
        $this->assertFalse(Validation::date('32 2 06', ['dmy']));
    }

    /**
     * testDateDmyyLeapYear method
     *
     * @return void
     */
    public function testDateDmyyLeapYear()
    {
        $this->assertTrue(Validation::date('29-2-04', ['dmy']));
        $this->assertTrue(Validation::date('29.2.04', ['dmy']));
        $this->assertTrue(Validation::date('29/2/04', ['dmy']));
        $this->assertTrue(Validation::date('29 2 04', ['dmy']));
        $this->assertFalse(Validation::date('29-2-06', ['dmy']));
        $this->assertFalse(Validation::date('29.2.06', ['dmy']));
        $this->assertFalse(Validation::date('29/2/06', ['dmy']));
        $this->assertFalse(Validation::date('29 2 06', ['dmy']));
    }

    /**
     * testDateDmyyyy method
     *
     * @return void
     */
    public function testDateDmyyyy()
    {
        $this->assertTrue(Validation::date('7-2-2006', ['dmy']));
        $this->assertTrue(Validation::date('7.2.2006', ['dmy']));
        $this->assertTrue(Validation::date('7/2/2006', ['dmy']));
        $this->assertTrue(Validation::date('7 2 2006', ['dmy']));
        $this->assertFalse(Validation::date('0-0-0000', ['dmy']));
        $this->assertFalse(Validation::date('0.0.0000', ['dmy']));
        $this->assertFalse(Validation::date('0/0/0000', ['dmy']));
        $this->assertFalse(Validation::date('0 0 0000', ['dmy']));
        $this->assertFalse(Validation::date('32-2-2006', ['dmy']));
        $this->assertFalse(Validation::date('32.2.2006', ['dmy']));
        $this->assertFalse(Validation::date('32/2/2006', ['dmy']));
        $this->assertFalse(Validation::date('32 2 2006', ['dmy']));
    }

    /**
     * testDateDmyyyyLeapYear method
     *
     * @return void
     */
    public function testDateDmyyyyLeapYear()
    {
        $this->assertTrue(Validation::date('29-2-2004', ['dmy']));
        $this->assertTrue(Validation::date('29.2.2004', ['dmy']));
        $this->assertTrue(Validation::date('29/2/2004', ['dmy']));
        $this->assertTrue(Validation::date('29 2 2004', ['dmy']));
        $this->assertFalse(Validation::date('29-2-2006', ['dmy']));
        $this->assertFalse(Validation::date('29.2.2006', ['dmy']));
        $this->assertFalse(Validation::date('29/2/2006', ['dmy']));
        $this->assertFalse(Validation::date('29 2 2006', ['dmy']));
    }

    /**
     * testDateMmddyyyy method
     *
     * @return void
     */
    public function testDateMmddyyyy()
    {
        $this->assertTrue(Validation::date('12-27-2006', ['mdy']));
        $this->assertTrue(Validation::date('12.27.2006', ['mdy']));
        $this->assertTrue(Validation::date('12/27/2006', ['mdy']));
        $this->assertTrue(Validation::date('12 27 2006', ['mdy']));
        $this->assertFalse(Validation::date('00-00-0000', ['mdy']));
        $this->assertFalse(Validation::date('00.00.0000', ['mdy']));
        $this->assertFalse(Validation::date('00/00/0000', ['mdy']));
        $this->assertFalse(Validation::date('00 00 0000', ['mdy']));
        $this->assertFalse(Validation::date('11-31-2006', ['mdy']));
        $this->assertFalse(Validation::date('11.31.2006', ['mdy']));
        $this->assertFalse(Validation::date('11/31/2006', ['mdy']));
        $this->assertFalse(Validation::date('11 31 2006', ['mdy']));
    }

    /**
     * testDateMmddyyyyLeapYear method
     *
     * @return void
     */
    public function testDateMmddyyyyLeapYear()
    {
        $this->assertTrue(Validation::date('02-29-2004', ['mdy']));
        $this->assertTrue(Validation::date('02.29.2004', ['mdy']));
        $this->assertTrue(Validation::date('02/29/2004', ['mdy']));
        $this->assertTrue(Validation::date('02 29 2004', ['mdy']));
        $this->assertFalse(Validation::date('02-29-2006', ['mdy']));
        $this->assertFalse(Validation::date('02.29.2006', ['mdy']));
        $this->assertFalse(Validation::date('02/29/2006', ['mdy']));
        $this->assertFalse(Validation::date('02 29 2006', ['mdy']));
    }

    /**
     * testDateMmddyy method
     *
     * @return void
     */
    public function testDateMmddyy()
    {
        $this->assertTrue(Validation::date('12-27-06', ['mdy']));
        $this->assertTrue(Validation::date('12.27.06', ['mdy']));
        $this->assertTrue(Validation::date('12/27/06', ['mdy']));
        $this->assertTrue(Validation::date('12 27 06', ['mdy']));
        $this->assertFalse(Validation::date('00-00-00', ['mdy']));
        $this->assertFalse(Validation::date('00.00.00', ['mdy']));
        $this->assertFalse(Validation::date('00/00/00', ['mdy']));
        $this->assertFalse(Validation::date('00 00 00', ['mdy']));
        $this->assertFalse(Validation::date('11-31-06', ['mdy']));
        $this->assertFalse(Validation::date('11.31.06', ['mdy']));
        $this->assertFalse(Validation::date('11/31/06', ['mdy']));
        $this->assertFalse(Validation::date('11 31 06', ['mdy']));
    }

    /**
     * testDateMmddyyLeapYear method
     *
     * @return void
     */
    public function testDateMmddyyLeapYear()
    {
        $this->assertTrue(Validation::date('02-29-04', ['mdy']));
        $this->assertTrue(Validation::date('02.29.04', ['mdy']));
        $this->assertTrue(Validation::date('02/29/04', ['mdy']));
        $this->assertTrue(Validation::date('02 29 04', ['mdy']));
        $this->assertFalse(Validation::date('02-29-06', ['mdy']));
        $this->assertFalse(Validation::date('02.29.06', ['mdy']));
        $this->assertFalse(Validation::date('02/29/06', ['mdy']));
        $this->assertFalse(Validation::date('02 29 06', ['mdy']));
    }

    /**
     * testDateMdyy method
     *
     * @return void
     */
    public function testDateMdyy()
    {
        $this->assertTrue(Validation::date('2-7-06', ['mdy']));
        $this->assertTrue(Validation::date('2.7.06', ['mdy']));
        $this->assertTrue(Validation::date('2/7/06', ['mdy']));
        $this->assertTrue(Validation::date('2 7 06', ['mdy']));
        $this->assertFalse(Validation::date('0-0-00', ['mdy']));
        $this->assertFalse(Validation::date('0.0.00', ['mdy']));
        $this->assertFalse(Validation::date('0/0/00', ['mdy']));
        $this->assertFalse(Validation::date('0 0 00', ['mdy']));
        $this->assertFalse(Validation::date('2-32-06', ['mdy']));
        $this->assertFalse(Validation::date('2.32.06', ['mdy']));
        $this->assertFalse(Validation::date('2/32/06', ['mdy']));
        $this->assertFalse(Validation::date('2 32 06', ['mdy']));
    }

    /**
     * testDateMdyyLeapYear method
     *
     * @return void
     */
    public function testDateMdyyLeapYear()
    {
        $this->assertTrue(Validation::date('2-29-04', ['mdy']));
        $this->assertTrue(Validation::date('2.29.04', ['mdy']));
        $this->assertTrue(Validation::date('2/29/04', ['mdy']));
        $this->assertTrue(Validation::date('2 29 04', ['mdy']));
        $this->assertFalse(Validation::date('2-29-06', ['mdy']));
        $this->assertFalse(Validation::date('2.29.06', ['mdy']));
        $this->assertFalse(Validation::date('2/29/06', ['mdy']));
        $this->assertFalse(Validation::date('2 29 06', ['mdy']));
    }

    /**
     * testDateMdyyyy method
     *
     * @return void
     */
    public function testDateMdyyyy()
    {
        $this->assertTrue(Validation::date('2-7-2006', ['mdy']));
        $this->assertTrue(Validation::date('2.7.2006', ['mdy']));
        $this->assertTrue(Validation::date('2/7/2006', ['mdy']));
        $this->assertTrue(Validation::date('2 7 2006', ['mdy']));
        $this->assertFalse(Validation::date('0-0-0000', ['mdy']));
        $this->assertFalse(Validation::date('0.0.0000', ['mdy']));
        $this->assertFalse(Validation::date('0/0/0000', ['mdy']));
        $this->assertFalse(Validation::date('0 0 0000', ['mdy']));
        $this->assertFalse(Validation::date('2-32-2006', ['mdy']));
        $this->assertFalse(Validation::date('2.32.2006', ['mdy']));
        $this->assertFalse(Validation::date('2/32/2006', ['mdy']));
        $this->assertFalse(Validation::date('2 32 2006', ['mdy']));
    }

    /**
     * testDateMdyyyyLeapYear method
     *
     * @return void
     */
    public function testDateMdyyyyLeapYear()
    {
        $this->assertTrue(Validation::date('2-29-2004', ['mdy']));
        $this->assertTrue(Validation::date('2.29.2004', ['mdy']));
        $this->assertTrue(Validation::date('2/29/2004', ['mdy']));
        $this->assertTrue(Validation::date('2 29 2004', ['mdy']));
        $this->assertFalse(Validation::date('2-29-2006', ['mdy']));
        $this->assertFalse(Validation::date('2.29.2006', ['mdy']));
        $this->assertFalse(Validation::date('2/29/2006', ['mdy']));
        $this->assertFalse(Validation::date('2 29 2006', ['mdy']));
    }

    /**
     * testDateYyyymmdd method
     *
     * @return void
     */
    public function testDateYyyymmdd()
    {
        $this->assertTrue(Validation::date('2006-12-27', ['ymd']));
        $this->assertTrue(Validation::date('2006.12.27', ['ymd']));
        $this->assertTrue(Validation::date('2006/12/27', ['ymd']));
        $this->assertTrue(Validation::date('2006 12 27', ['ymd']));
        $this->assertFalse(Validation::date('2006-11-31', ['ymd']));
        $this->assertFalse(Validation::date('2006.11.31', ['ymd']));
        $this->assertFalse(Validation::date('2006/11/31', ['ymd']));
        $this->assertFalse(Validation::date('2006 11 31', ['ymd']));
    }

    /**
     * testDateYyyymmddLeapYear method
     *
     * @return void
     */
    public function testDateYyyymmddLeapYear()
    {
        $this->assertTrue(Validation::date('2004-02-29', ['ymd']));
        $this->assertTrue(Validation::date('2004.02.29', ['ymd']));
        $this->assertTrue(Validation::date('2004/02/29', ['ymd']));
        $this->assertTrue(Validation::date('2004 02 29', ['ymd']));
        $this->assertFalse(Validation::date('2006-02-29', ['ymd']));
        $this->assertFalse(Validation::date('2006.02.29', ['ymd']));
        $this->assertFalse(Validation::date('2006/02/29', ['ymd']));
        $this->assertFalse(Validation::date('2006 02 29', ['ymd']));
    }

    /**
     * testDateYymmdd method
     *
     * @return void
     */
    public function testDateYymmdd()
    {
        $this->assertTrue(Validation::date('06-12-27', ['ymd']));
        $this->assertTrue(Validation::date('06.12.27', ['ymd']));
        $this->assertTrue(Validation::date('06/12/27', ['ymd']));
        $this->assertTrue(Validation::date('06 12 27', ['ymd']));
        $this->assertFalse(Validation::date('12/27/2600', ['ymd']));
        $this->assertFalse(Validation::date('12.27.2600', ['ymd']));
        $this->assertFalse(Validation::date('12/27/2600', ['ymd']));
        $this->assertFalse(Validation::date('12 27 2600', ['ymd']));
        $this->assertFalse(Validation::date('06-11-31', ['ymd']));
        $this->assertFalse(Validation::date('06.11.31', ['ymd']));
        $this->assertFalse(Validation::date('06/11/31', ['ymd']));
        $this->assertFalse(Validation::date('06 11 31', ['ymd']));
    }

    /**
     * testDateYymmddLeapYear method
     *
     * @return void
     */
    public function testDateYymmddLeapYear()
    {
        $this->assertTrue(Validation::date('2004-02-29', ['ymd']));
        $this->assertTrue(Validation::date('2004.02.29', ['ymd']));
        $this->assertTrue(Validation::date('2004/02/29', ['ymd']));
        $this->assertTrue(Validation::date('2004 02 29', ['ymd']));
        $this->assertFalse(Validation::date('2006-02-29', ['ymd']));
        $this->assertFalse(Validation::date('2006.02.29', ['ymd']));
        $this->assertFalse(Validation::date('2006/02/29', ['ymd']));
        $this->assertFalse(Validation::date('2006 02 29', ['ymd']));
    }

    /**
     * testDateDdMMMMyyyy method
     *
     * @return void
     */
    public function testDateDdMMMMyyyy()
    {
        $this->assertTrue(Validation::date('27 December 2006', ['dMy']));
        $this->assertTrue(Validation::date('27 Dec 2006', ['dMy']));
        $this->assertFalse(Validation::date('2006 Dec 27', ['dMy']));
        $this->assertFalse(Validation::date('2006 December 27', ['dMy']));
    }

    /**
     * testDateDdMMMMyyyyLeapYear method
     *
     * @return void
     */
    public function testDateDdMMMMyyyyLeapYear()
    {
        $this->assertTrue(Validation::date('29 February 2004', ['dMy']));
        $this->assertFalse(Validation::date('29 February 2006', ['dMy']));
    }

    /**
     * testDateMmmmDdyyyy method
     *
     * @return void
     */
    public function testDateMmmmDdyyyy()
    {
        $this->assertTrue(Validation::date('December 27, 2006', ['Mdy']));
        $this->assertTrue(Validation::date('Dec 27, 2006', ['Mdy']));
        $this->assertTrue(Validation::date('December 27 2006', ['Mdy']));
        $this->assertTrue(Validation::date('Dec 27 2006', ['Mdy']));
        $this->assertFalse(Validation::date('27 Dec 2006', ['Mdy']));
        $this->assertFalse(Validation::date('2006 December 27', ['Mdy']));
        $this->assertTrue(Validation::date('Sep 12, 2011', ['Mdy']));
    }

    /**
     * testDateMmmmDdyyyyLeapYear method
     *
     * @return void
     */
    public function testDateMmmmDdyyyyLeapYear()
    {
        $this->assertTrue(Validation::date('February 29, 2004', ['Mdy']));
        $this->assertTrue(Validation::date('Feb 29, 2004', ['Mdy']));
        $this->assertTrue(Validation::date('February 29 2004', ['Mdy']));
        $this->assertTrue(Validation::date('Feb 29 2004', ['Mdy']));
        $this->assertFalse(Validation::date('February 29, 2006', ['Mdy']));
    }

    /**
     * testDateMy method
     *
     * @return void
     */
    public function testDateMy()
    {
        $this->assertTrue(Validation::date('December 2006', ['My']));
        $this->assertTrue(Validation::date('Dec 2006', ['My']));
        $this->assertTrue(Validation::date('December/2006', ['My']));
        $this->assertTrue(Validation::date('Dec/2006', ['My']));
    }

    /**
     * testDateMyNumeric method
     *
     * @return void
     */
    public function testDateMyNumeric()
    {
        $this->assertTrue(Validation::date('01/2006', ['my']));
        $this->assertTrue(Validation::date('12-2006', ['my']));
        $this->assertTrue(Validation::date('12.2006', ['my']));
        $this->assertTrue(Validation::date('12 2006', ['my']));
        $this->assertTrue(Validation::date('01/06', ['my']));
        $this->assertTrue(Validation::date('12-06', ['my']));
        $this->assertTrue(Validation::date('12.06', ['my']));
        $this->assertTrue(Validation::date('12 06', ['my']));
        $this->assertFalse(Validation::date('13 06', ['my']));
        $this->assertFalse(Validation::date('13 2006', ['my']));
    }

    /**
     * testDateYmNumeric method
     *
     * @return void
     */
    public function testDateYmNumeric()
    {
        $this->assertTrue(Validation::date('2006/12', ['ym']));
        $this->assertTrue(Validation::date('2006-12', ['ym']));
        $this->assertTrue(Validation::date('2006-12', ['ym']));
        $this->assertTrue(Validation::date('2006 12', ['ym']));
        $this->assertTrue(Validation::date('2006 12', ['ym']));
        $this->assertTrue(Validation::date('1900-01', ['ym']));
        $this->assertTrue(Validation::date('2153-01', ['ym']));
        $this->assertTrue(Validation::date('06/12', ['ym']));
        $this->assertTrue(Validation::date('06-12', ['ym']));
        $this->assertTrue(Validation::date('06-12', ['ym']));
        $this->assertTrue(Validation::date('06 12', ['ym']));
        $this->assertFalse(Validation::date('2006/12 ', ['ym']));
        $this->assertFalse(Validation::date('2006/12/', ['ym']));
        $this->assertFalse(Validation::date('06/12 ', ['ym']));
        $this->assertFalse(Validation::date('06/13 ', ['ym']));
    }

    /**
     * testDateY method
     *
     * @return void
     */
    public function testDateY()
    {
        $this->assertTrue(Validation::date('1900', ['y']));
        $this->assertTrue(Validation::date('1984', ['y']));
        $this->assertTrue(Validation::date('2006', ['y']));
        $this->assertTrue(Validation::date('2008', ['y']));
        $this->assertTrue(Validation::date('2013', ['y']));
        $this->assertTrue(Validation::date('2104', ['y']));
        $this->assertTrue(Validation::date('1899', ['y']));
        $this->assertFalse(Validation::date('20009', ['y']));
        $this->assertFalse(Validation::date(' 2012', ['y']));
        $this->assertFalse(Validation::date('3000', ['y']));
        $this->assertFalse(Validation::date('1799', ['y']));
    }

    /**
     * test date validation when passing an array
     *
     * @return void
     */
    public function testDateArray()
    {
        $date = ['year' => 2014, 'month' => 2, 'day' => 14];
        $this->assertTrue(Validation::date($date));
        $date = ['year' => 'farts', 'month' => 'derp', 'day' => 'farts'];
        $this->assertFalse(Validation::date($date));

        $date = ['year' => 2014, 'month' => 2, 'day' => 14];
        $this->assertTrue(Validation::date($date, 'mdy'));
    }

    /**
     * test datetime validation when passing an array
     *
     * @return void
     */
    public function testDateTimeArray()
    {
        $date = [
            'year' => 2014,
            'month' => '02',
            'day' => '14',
            'hour' => '12',
            'minute' => '14',
            'second' => '15',
            'meridian' => 'pm'
        ];
        $this->assertTrue(Validation::datetime($date));

        $date = ['year' => 2014, 'month' => 2, 'day' => 14, 'hour' => 13, 'minute' => 14, 'second' => 15];
        $this->assertTrue(Validation::datetime($date));

        $date = [
            'year' => 2014, 'month' => 2, 'day' => 14,
            'hour' => 1, 'minute' => 14, 'second' => 15,
            'meridian' => 'am'
        ];
        $this->assertTrue(Validation::datetime($date));
        $this->assertTrue(Validation::datetime($date, 'mdy'));

        $date = [
            'year' => '2014', 'month' => '02', 'day' => '14',
            'hour' => 'farts', 'minute' => 'farts'
        ];
        $this->assertFalse(Validation::datetime($date));
    }

    /**
     * Test validating dates with multiple formats
     *
     * @return void
     */
    public function testDateMultiple()
    {
        $this->assertTrue(Validation::date('2011-12-31', ['ymd', 'dmy']));
        $this->assertTrue(Validation::date('31-12-2011', ['ymd', 'dmy']));
    }

    /**
     * testTime method
     *
     * @return void
     */
    public function testTime()
    {
        $this->assertTrue(Validation::time('00:00'));
        $this->assertTrue(Validation::time('23:59'));
        $this->assertFalse(Validation::time('24:00'));
        $this->assertTrue(Validation::time('12:00'));
        $this->assertTrue(Validation::time('12:01'));
        $this->assertTrue(Validation::time('12:01am'));
        $this->assertTrue(Validation::time('12:01pm'));
        $this->assertTrue(Validation::time('1pm'));
        $this->assertTrue(Validation::time('1 pm'));
        $this->assertTrue(Validation::time('1 PM'));
        $this->assertTrue(Validation::time('01:00'));
        $this->assertFalse(Validation::time('1:00'));
        $this->assertTrue(Validation::time('1:00pm'));
        $this->assertFalse(Validation::time('13:00pm'));
        $this->assertFalse(Validation::time('9:00'));
    }

    /**
     * test time validation when passing an array
     *
     * @return void
     */
    public function testTimeArray()
    {
        $date = ['hour' => 13, 'minute' => 14, 'second' => 15];
        $this->assertTrue(Validation::time($date));

        $date = [
            'hour' => 1, 'minute' => 14, 'second' => 15,
            'meridian' => 'am'
        ];
        $this->assertTrue(Validation::time($date));

        $date = [
            'hour' => 12, 'minute' => 14, 'second' => 15,
            'meridian' => 'pm'
        ];
        $this->assertTrue(Validation::time($date));

        $date = [
            'hour' => 'farts', 'minute' => 'farts'
        ];
        $this->assertFalse(Validation::time($date));

        $date = [];
        $this->assertFalse(Validation::time($date));
    }

    /**
     * Tests that it is possible to pass a median (AM, PM) to the dateTime validation
     *
     * @return void
     */
    public function testDateTimeWithMeriadian()
    {
        $this->assertTrue(Validation::dateTime('10/04/2007 1:50 AM', ['dmy']));
        $this->assertTrue(Validation::dateTime('12/04/2017 1:38 PM', ['dmy']));
        $this->assertTrue(Validation::dateTime('10/04/2007 1:50 am', ['dmy']));
        $this->assertTrue(Validation::dateTime('12/04/2017 1:38 pm', ['dmy']));
        $this->assertTrue(Validation::dateTime('12/04/2017 1:38pm', ['dmy']));
        $this->assertTrue(Validation::dateTime('12/04/2017 1:38AM', ['dmy']));
        $this->assertTrue(Validation::dateTime('12/04/2017, 1:38AM', ['dmy']));
        $this->assertTrue(Validation::dateTime('28/10/2015, 3:21 PM', ['dmy']));
        $this->assertFalse(Validation::dateTime('12/04/2017 58:38AM', ['dmy']));
    }

    /**
     * Test localizedTime
     *
     * @return void
     */
    public function testLocalizedTime()
    {
        $locale = I18N::locale();

        $this->assertFalse(Validation::localizedTime('', 'date'));
        $this->assertFalse(Validation::localizedTime('invalid', 'date'));

        // English (US)
        I18N::locale('en_US');
        $this->assertTrue(Validation::localizedTime('12/31/2006', 'date'));
        $this->assertTrue(Validation::localizedTime('6.40pm', 'time'));
        $this->assertTrue(Validation::localizedTime('12/31/2006 6.40pm', 'datetime'));
        $this->assertTrue(Validation::localizedTime('December 31, 2006', 'date'));

        $this->assertFalse(Validation::localizedTime('31. Dezember 2006', 'date')); // non-US format
        $this->assertFalse(Validation::localizedTime('18:40', 'time')); // non-US format

        // German
        I18N::locale('de_DE');
        $this->assertTrue(Validation::localizedTime('31.12.2006', 'date'));
        $this->assertTrue(Validation::localizedTime('31. Dezember 2006', 'date'));
        $this->assertTrue(Validation::localizedTime('18:40', 'time'));

        $this->assertFalse(Validation::localizedTime('December 31, 2006', 'date')); // non-German format

        // Russian
        I18N::locale('ru_RU');
        $this->assertTrue(Validation::localizedTime('31 декабря 2006', 'date'));

        $this->assertFalse(Validation::localizedTime('December 31, 2006', 'date')); // non-Russian format

        I18N::locale($locale);
    }


    /**
     * testBoolean method
     *
     * @return void
     */
    public function testBoolean()
    {
        $this->assertTrue(Validation::boolean('0'));
        $this->assertTrue(Validation::boolean('1'));
        $this->assertTrue(Validation::boolean(0));
        $this->assertTrue(Validation::boolean(1));
        $this->assertTrue(Validation::boolean(true));
        $this->assertTrue(Validation::boolean(false));
        $this->assertFalse(Validation::boolean('true'));
        $this->assertFalse(Validation::boolean('false'));
        $this->assertFalse(Validation::boolean('-1'));
        $this->assertFalse(Validation::boolean('2'));
        $this->assertFalse(Validation::boolean('Boo!'));
    }

    /**
     * testDateCustomRegx method
     *
     * @return void
     */
    public function testDateCustomRegx()
    {
        $this->assertTrue(Validation::date('2006-12-27', null, '%^(19|20)[0-9]{2}[- /.](0[1-9]|1[012])[- /.](0[1-9]|[12][0-9]|3[01])$%'));
        $this->assertFalse(Validation::date('12-27-2006', null, '%^(19|20)[0-9]{2}[- /.](0[1-9]|1[012])[- /.](0[1-9]|[12][0-9]|3[01])$%'));
    }

    /**
     * Test numbers with any number of decimal places, including none.
     *
     * @return void
     */
    public function testDecimalWithPlacesNull()
    {
        $this->assertTrue(Validation::decimal('+1234.54321', null));
        $this->assertTrue(Validation::decimal('-1234.54321', null));
        $this->assertTrue(Validation::decimal('1234.54321', null));
        $this->assertTrue(Validation::decimal('+0123.45e6', null));
        $this->assertTrue(Validation::decimal('-0123.45e6', null));
        $this->assertTrue(Validation::decimal('0123.45e6', null));
        $this->assertTrue(Validation::decimal(1234.56, null));
        $this->assertTrue(Validation::decimal(1234.00, null));
        $this->assertTrue(Validation::decimal(1234., null));
        $this->assertTrue(Validation::decimal('1234.00', null));
        $this->assertTrue(Validation::decimal(.0, null));
        $this->assertTrue(Validation::decimal(.00, null));
        $this->assertTrue(Validation::decimal('.00', null));
        $this->assertTrue(Validation::decimal(.01, null));
        $this->assertTrue(Validation::decimal('.01', null));
        $this->assertTrue(Validation::decimal('1234', null));
        $this->assertTrue(Validation::decimal('-1234', null));
        $this->assertTrue(Validation::decimal('+1234', null));
        $this->assertTrue(Validation::decimal((float)1234, null));
        $this->assertTrue(Validation::decimal((double)1234, null));
        $this->assertTrue(Validation::decimal((int)1234, null));

        $this->assertFalse(Validation::decimal('', null));
        $this->assertFalse(Validation::decimal('string', null));
        $this->assertFalse(Validation::decimal('1234.', null));
    }

    /**
     * Test numbers with any number of decimal places greater than 0, or a float|double.
     *
     * @return void
     */
    public function testDecimalWithPlacesTrue()
    {
        $this->assertTrue(Validation::decimal('+1234.54321', true));
        $this->assertTrue(Validation::decimal('-1234.54321', true));
        $this->assertTrue(Validation::decimal('1234.54321', true));
        $this->assertTrue(Validation::decimal('+0123.45e6', true));
        $this->assertTrue(Validation::decimal('-0123.45e6', true));
        $this->assertTrue(Validation::decimal('0123.45e6', true));
        $this->assertTrue(Validation::decimal(1234.56, true));
        $this->assertTrue(Validation::decimal(1234.00, true));
        $this->assertTrue(Validation::decimal(1234., true));
        $this->assertTrue(Validation::decimal('1234.00', true));
        $this->assertTrue(Validation::decimal(.0, true));
        $this->assertTrue(Validation::decimal(.00, true));
        $this->assertTrue(Validation::decimal('.00', true));
        $this->assertTrue(Validation::decimal(.01, true));
        $this->assertTrue(Validation::decimal('.01', true));
        $this->assertTrue(Validation::decimal((float)1234, true));
        $this->assertTrue(Validation::decimal((double)1234, true));

        $this->assertFalse(Validation::decimal('', true));
        $this->assertFalse(Validation::decimal('string', true));
        $this->assertFalse(Validation::decimal('1234.', true));
        $this->assertFalse(Validation::decimal((int)1234, true));
        $this->assertFalse(Validation::decimal('1234', true));
        $this->assertFalse(Validation::decimal('-1234', true));
        $this->assertFalse(Validation::decimal('+1234', true));
    }

    /**
     * Test numbers with exactly that many number of decimal places.
     *
     * @return void
     */
    public function testDecimalWithPlacesNumeric()
    {
        $this->assertTrue(Validation::decimal('.27', '2'));
        $this->assertTrue(Validation::decimal(0.27, 2));
        $this->assertTrue(Validation::decimal(-0.27, 2));
        $this->assertTrue(Validation::decimal(0.27, 2));
        $this->assertTrue(Validation::decimal('0.277', '3'));
        $this->assertTrue(Validation::decimal(0.277, 3));
        $this->assertTrue(Validation::decimal(-0.277, 3));
        $this->assertTrue(Validation::decimal(0.277, 3));
        $this->assertTrue(Validation::decimal('1234.5678', '4'));
        $this->assertTrue(Validation::decimal(1234.5678, 4));
        $this->assertTrue(Validation::decimal(-1234.5678, 4));
        $this->assertTrue(Validation::decimal(1234.5678, 4));
        $this->assertTrue(Validation::decimal('.00', 2));
        $this->assertTrue(Validation::decimal(.01, 2));
        $this->assertTrue(Validation::decimal('.01', 2));

        $this->assertFalse(Validation::decimal('', 1));
        $this->assertFalse(Validation::decimal('string', 1));
        $this->assertFalse(Validation::decimal(1234., 1));
        $this->assertFalse(Validation::decimal('1234.', 1));
        $this->assertFalse(Validation::decimal(.0, 1));
        $this->assertFalse(Validation::decimal(.00, 2));
        $this->assertFalse(Validation::decimal((float)1234, 1));
        $this->assertFalse(Validation::decimal((double)1234, 1));
        $this->assertFalse(Validation::decimal((int)1234, 1));
        $this->assertFalse(Validation::decimal('1234.5678', '3'));
        $this->assertFalse(Validation::decimal(1234.5678, 3));
        $this->assertFalse(Validation::decimal(-1234.5678, 3));
        $this->assertFalse(Validation::decimal(1234.5678, 3));
    }

    /**
     * Test decimal() with invalid places parameter.
     *
     * @return void
     */
    public function testDecimalWithInvalidPlaces()
    {
        $this->assertFalse(Validation::decimal('.27', 'string'));
        $this->assertFalse(Validation::decimal(1234.5678, (array)true));
        $this->assertFalse(Validation::decimal(-1234.5678, (object)true));
    }

    /**
     * testDecimalCustomRegex method
     *
     * @return void
     */
    public function testDecimalCustomRegex()
    {
        $this->assertTrue(Validation::decimal('1.54321', null, '/^[-+]?[0-9]+(\\.[0-9]+)?$/s'));
        $this->assertFalse(Validation::decimal('.54321', null, '/^[-+]?[0-9]+(\\.[0-9]+)?$/s'));
    }

    /**
     * Test localized floats with decimal.
     *
     * @return void
     */
    public function testDecimalLocaleSet()
    {
        $this->skipIf(DS === '\\', 'The locale is not supported in Windows and affects other tests.');
        $this->skipIf(Locale::setDefault('da_DK') === false, "The Danish locale isn't available.");

        $this->assertTrue(Validation::decimal(1.54), '1.54 should be considered a valid float');
        $this->assertTrue(Validation::decimal('1.54'), '"1.54" should be considered a valid float');

        $this->assertTrue(Validation::decimal(12345.67), '12345.67 should be considered a valid float');
        $this->assertTrue(Validation::decimal('12,345.67'), '"12,345.67" should be considered a valid float');
    }

    /**
     * testEmail method
     *
     * @return void
     */
    public function testEmail()
    {
        $this->assertTrue(Validation::email('abc.efg@domain.com'));
        $this->assertTrue(Validation::email('efg@domain.com'));
        $this->assertTrue(Validation::email('abc-efg@domain.com'));
        $this->assertTrue(Validation::email('abc_efg@domain.com'));
        $this->assertTrue(Validation::email('raw@test.ra.ru'));
        $this->assertTrue(Validation::email('abc-efg@domain-hyphened.com'));
        $this->assertTrue(Validation::email("p.o'malley@domain.com"));
        $this->assertTrue(Validation::email('abc+efg@domain.com'));
        $this->assertTrue(Validation::email('abc&efg@domain.com'));
        $this->assertTrue(Validation::email('abc.efg@12345.com'));
        $this->assertTrue(Validation::email('abc.efg@12345.co.jp'));
        $this->assertTrue(Validation::email('abc@g.cn'));
        $this->assertTrue(Validation::email('abc@x.com'));
        $this->assertTrue(Validation::email('henrik@sbcglobal.net'));
        $this->assertTrue(Validation::email('sani@sbcglobal.net'));

        // all ICANN TLDs
        $this->assertTrue(Validation::email('abc@example.aero'));
        $this->assertTrue(Validation::email('abc@example.asia'));
        $this->assertTrue(Validation::email('abc@example.biz'));
        $this->assertTrue(Validation::email('abc@example.cat'));
        $this->assertTrue(Validation::email('abc@example.com'));
        $this->assertTrue(Validation::email('abc@example.coop'));
        $this->assertTrue(Validation::email('abc@example.edu'));
        $this->assertTrue(Validation::email('abc@example.gov'));
        $this->assertTrue(Validation::email('abc@example.info'));
        $this->assertTrue(Validation::email('abc@example.int'));
        $this->assertTrue(Validation::email('abc@example.jobs'));
        $this->assertTrue(Validation::email('abc@example.mil'));
        $this->assertTrue(Validation::email('abc@example.mobi'));
        $this->assertTrue(Validation::email('abc@example.museum'));
        $this->assertTrue(Validation::email('abc@example.name'));
        $this->assertTrue(Validation::email('abc@example.net'));
        $this->assertTrue(Validation::email('abc@example.org'));
        $this->assertTrue(Validation::email('abc@example.pro'));
        $this->assertTrue(Validation::email('abc@example.tel'));
        $this->assertTrue(Validation::email('abc@example.travel'));
        $this->assertTrue(Validation::email('someone@st.t-com.hr'));

        // gTLD's
        $this->assertTrue(Validation::email('example@host.local'));
        $this->assertTrue(Validation::email('example@x.org'));
        $this->assertTrue(Validation::email('example@host.xxx'));

        // strange, but technically valid email addresses
        $this->assertTrue(Validation::email('S=postmaster/OU=rz/P=uni-frankfurt/A=d400/C=de@gateway.d400.de'));
        $this->assertTrue(Validation::email('customer/department=shipping@example.com'));
        $this->assertTrue(Validation::email('$A12345@example.com'));
        $this->assertTrue(Validation::email('!def!xyz%abc@example.com'));
        $this->assertTrue(Validation::email('_somename@example.com'));

        // Unicode
        $this->assertTrue(Validation::email('some@eräume.foo'));
        $this->assertTrue(Validation::email('äu@öe.eräume.foo'));
        $this->assertTrue(Validation::email('Nyrée.surname@example.com'));

        // invalid addresses
        $this->assertFalse(Validation::email('abc@example'));
        $this->assertFalse(Validation::email('abc@example.c'));
        $this->assertFalse(Validation::email('abc@example.com.'));
        $this->assertFalse(Validation::email('abc.@example.com'));
        $this->assertFalse(Validation::email('abc@example..com'));
        $this->assertFalse(Validation::email('abc@example.com.a'));
        $this->assertFalse(Validation::email('abc;@example.com'));
        $this->assertFalse(Validation::email('abc@example.com;'));
        $this->assertFalse(Validation::email('abc@efg@example.com'));
        $this->assertFalse(Validation::email('abc@@example.com'));
        $this->assertFalse(Validation::email('abc efg@example.com'));
        $this->assertFalse(Validation::email('abc,efg@example.com'));
        $this->assertFalse(Validation::email('abc@sub,example.com'));
        $this->assertFalse(Validation::email("abc@sub'example.com"));
        $this->assertFalse(Validation::email('abc@sub/example.com'));
        $this->assertFalse(Validation::email('abc@yahoo!.com'));
        $this->assertFalse(Validation::email('abc@example_underscored.com'));
        $this->assertFalse(Validation::email('raw@test.ra.ru....com'));
    }

    /**
     * testEmailDeep method
     *
     * @return void
     */
    public function testEmailDeep()
    {
        $this->skipIf(gethostbynamel('example.abcd'), 'Your DNS service responds for non-existant domains, skipping deep email checks.');

        $this->assertTrue(Validation::email('abc.efg@cakephp.org', true));
        $this->assertFalse(Validation::email('abc.efg@caphpkeinvalid.com', true));
    }

    /**
     * testEmailCustomRegex method
     *
     * @return void
     */
    public function testEmailCustomRegex()
    {
        $this->assertTrue(Validation::email('abc.efg@cakephp.org', null, '/^[A-Z0-9._%-]+@[A-Z0-9.-]+\\.[A-Z]{2,4}$/i'));
        $this->assertFalse(Validation::email('abc.efg@com.caphpkeinvalid', null, '/^[A-Z0-9._%-]+@[A-Z0-9.-]+\\.[A-Z]{2,4}$/i'));
    }

    /**
     * testEqualTo method
     *
     * @return void
     */
    public function testEqualTo()
    {
        $this->assertTrue(Validation::equalTo("1", "1"));
        $this->assertFalse(Validation::equalTo(1, "1"));
        $this->assertFalse(Validation::equalTo("", null));
        $this->assertFalse(Validation::equalTo("", false));
        $this->assertFalse(Validation::equalTo(0, false));
        $this->assertFalse(Validation::equalTo(null, false));
    }

    /**
     * testIpV4 method
     *
     * @return void
     */
    public function testIpV4()
    {
        $this->assertTrue(Validation::ip('0.0.0.0', 'ipv4'));
        $this->assertTrue(Validation::ip('192.168.1.156'));
        $this->assertTrue(Validation::ip('255.255.255.255'));
        $this->assertFalse(Validation::ip('127.0.0'));
        $this->assertFalse(Validation::ip('127.0.0.a'));
        $this->assertFalse(Validation::ip('127.0.0.256'));
        $this->assertFalse(Validation::ip('2001:0db8:85a3:0000:0000:8a2e:0370:7334', 'ipv4'), 'IPv6 is not valid IPv4');
    }

    /**
     * testIp v6
     *
     * @return void
     */
    public function testIpv6()
    {
        $this->assertTrue(Validation::ip('2001:0db8:85a3:0000:0000:8a2e:0370:7334', 'IPv6'));
        $this->assertTrue(Validation::ip('2001:db8:85a3:0:0:8a2e:370:7334', 'IPv6'));
        $this->assertTrue(Validation::ip('2001:db8:85a3::8a2e:370:7334', 'IPv6'));
        $this->assertTrue(Validation::ip('2001:0db8:0000:0000:0000:0000:1428:57ab', 'IPv6'));
        $this->assertTrue(Validation::ip('2001:0db8:0000:0000:0000::1428:57ab', 'IPv6'));
        $this->assertTrue(Validation::ip('2001:0db8:0:0:0:0:1428:57ab', 'IPv6'));
        $this->assertTrue(Validation::ip('2001:0db8:0:0::1428:57ab', 'IPv6'));
        $this->assertTrue(Validation::ip('2001:0db8::1428:57ab', 'IPv6'));
        $this->assertTrue(Validation::ip('2001:db8::1428:57ab', 'IPv6'));
        $this->assertTrue(Validation::ip('0000:0000:0000:0000:0000:0000:0000:0001', 'IPv6'));
        $this->assertTrue(Validation::ip('::1', 'IPv6'));
        $this->assertTrue(Validation::ip('::ffff:12.34.56.78', 'IPv6'));
        $this->assertTrue(Validation::ip('::ffff:0c22:384e', 'IPv6'));
        $this->assertTrue(Validation::ip('2001:0db8:1234:0000:0000:0000:0000:0000', 'IPv6'));
        $this->assertTrue(Validation::ip('2001:0db8:1234:ffff:ffff:ffff:ffff:ffff', 'IPv6'));
        $this->assertTrue(Validation::ip('2001:db8:a::123', 'IPv6'));
        $this->assertTrue(Validation::ip('fe80::', 'IPv6'));
        $this->assertTrue(Validation::ip('::ffff:192.0.2.128', 'IPv6'));
        $this->assertTrue(Validation::ip('::ffff:c000:280', 'IPv6'));

        $this->assertFalse(Validation::ip('123', 'IPv6'));
        $this->assertFalse(Validation::ip('ldkfj', 'IPv6'));
        $this->assertFalse(Validation::ip('2001::FFD3::57ab', 'IPv6'));
        $this->assertFalse(Validation::ip('2001:db8:85a3::8a2e:37023:7334', 'IPv6'));
        $this->assertFalse(Validation::ip('2001:db8:85a3::8a2e:370k:7334', 'IPv6'));
        $this->assertFalse(Validation::ip('1:2:3:4:5:6:7:8:9', 'IPv6'));
        $this->assertFalse(Validation::ip('1::2::3', 'IPv6'));
        $this->assertFalse(Validation::ip('1:::3:4:5', 'IPv6'));
        $this->assertFalse(Validation::ip('1:2:3::4:5:6:7:8:9', 'IPv6'));
        $this->assertFalse(Validation::ip('::ffff:2.3.4', 'IPv6'));
        $this->assertFalse(Validation::ip('::ffff:257.1.2.3', 'IPv6'));
        $this->assertFalse(Validation::ip('255.255.255.255', 'ipv6'), 'IPv4 is not valid IPv6');
    }

    /**
     * testMaxLength method
     *
     * @return void
     */
    public function testMaxLength()
    {
        $this->assertTrue(Validation::maxLength('ab', 3));
        $this->assertTrue(Validation::maxLength('abc', 3));
        $this->assertTrue(Validation::maxLength('ÆΔΩЖÇ', 10));

        $this->assertFalse(Validation::maxLength('abcd', 3));
        $this->assertFalse(Validation::maxLength('ÆΔΩЖÇ', 3));
    }

    /**
     * testMinLength method
     *
     * @return void
     */
    public function testMinLength()
    {
        $this->assertFalse(Validation::minLength('ab', 3));
        $this->assertFalse(Validation::minLength('ÆΔΩЖÇ', 10));

        $this->assertTrue(Validation::minLength('abc', 3));
        $this->assertTrue(Validation::minLength('abcd', 3));
        $this->assertTrue(Validation::minLength('ÆΔΩЖÇ', 2));
    }

    /**
     * testUrl method
     *
     * @return void
     */
    public function testUrl()
    {
        $this->assertTrue(Validation::url('http://www.cakephp.org'));
        $this->assertTrue(Validation::url('http://cakephp.org'));
        $this->assertTrue(Validation::url('http://www.cakephp.org/somewhere#anchor'));
        $this->assertTrue(Validation::url('http://192.168.0.1'));
        $this->assertTrue(Validation::url('https://www.cakephp.org'));
        $this->assertTrue(Validation::url('https://cakephp.org'));
        $this->assertTrue(Validation::url('https://www.cakephp.org/somewhere#anchor'));
        $this->assertTrue(Validation::url('https://192.168.0.1'));
        $this->assertTrue(Validation::url('ftps://www.cakephp.org/pub/cake'));
        $this->assertTrue(Validation::url('ftps://cakephp.org/pub/cake'));
        $this->assertTrue(Validation::url('ftps://192.168.0.1/pub/cake'));
        $this->assertTrue(Validation::url('ftp://www.cakephp.org/pub/cake'));
        $this->assertTrue(Validation::url('ftp://cakephp.org/pub/cake'));
        $this->assertTrue(Validation::url('ftp://192.168.0.1/pub/cake'));
        $this->assertTrue(Validation::url('sftp://192.168.0.1/pub/cake'));
        $this->assertTrue(Validation::url('https://my.domain.com/gizmo/app?class=MySip;proc=start'));
        $this->assertTrue(Validation::url('www.domain.tld'));
        $this->assertTrue(Validation::url('http://123456789112345678921234567893123456789412345678951234567896123.com'));
        $this->assertTrue(Validation::url('http://www.domain.com/blogs/index.php?blog=6&tempskin=_rss2'));
        $this->assertTrue(Validation::url('http://www.domain.com/blogs/parenth()eses.php'));
        $this->assertTrue(Validation::url('http://www.domain.com/index.php?get=params&amp;get2=params'));
        $this->assertTrue(Validation::url('http://www.domain.com/ndex.php?get=params&amp;get2=params#anchor'));
        $this->assertTrue(Validation::url('http://www.domain.com/real%20url%20encodeing'));
        $this->assertTrue(Validation::url('http://en.wikipedia.org/wiki/Architectural_pattern_(computer_science)'));
        $this->assertTrue(Validation::url('http://www.cakephp.org', true));
        $this->assertTrue(Validation::url('http://example.com/~userdir/'));
        $this->assertTrue(Validation::url('http://underscore_subdomain.example.org'));
        $this->assertTrue(Validation::url('http://_jabber._tcp.gmail.com'));
        $this->assertTrue(Validation::url('http://www.domain.longttldnotallowed'));
        $this->assertFalse(Validation::url('ftps://256.168.0.1/pub/cake'));
        $this->assertFalse(Validation::url('ftp://256.168.0.1/pub/cake'));
        $this->assertFalse(Validation::url('http://w_w.domain.co_m'));
        $this->assertFalse(Validation::url('http://www.domain.12com'));
        $this->assertFalse(Validation::url('http://www.-invaliddomain.tld'));
        $this->assertFalse(Validation::url('http://www.domain.-invalidtld'));
        $this->assertFalse(Validation::url('http://this-domain-is-too-loooooong-by-icann-rules-maximum-length-is-63.com'));
        $this->assertFalse(Validation::url('http://www.underscore_domain.org'));
        $this->assertFalse(Validation::url('http://_jabber._tcp.g_mail.com'));
        $this->assertFalse(Validation::url('http://en.(wikipedia).org/'));
        $this->assertFalse(Validation::url('http://www.domain.com/fakeenco%ode'));
        $this->assertFalse(Validation::url('--.example.com'));
        $this->assertFalse(Validation::url('www.cakephp.org', true));

        $this->assertTrue(Validation::url('http://example.com/~userdir/subdir/index.html'));
        $this->assertTrue(Validation::url('http://www.zwischenraume.de'));
        $this->assertTrue(Validation::url('http://www.zwischenraume.cz'));
        $this->assertTrue(Validation::url('http://www.last.fm/music/浜崎あゆみ'), 'utf8 path failed');
        $this->assertTrue(Validation::url('http://www.electrohome.ro/images/239537750-284232-215_300[1].jpg'));
        $this->assertTrue(Validation::url('http://www.eräume.foo'));
        $this->assertTrue(Validation::url('http://äüö.eräume.foo'));

        $this->assertTrue(Validation::url('http://cakephp.org:80'));
        $this->assertTrue(Validation::url('http://cakephp.org:443'));
        $this->assertTrue(Validation::url('http://cakephp.org:2000'));
        $this->assertTrue(Validation::url('http://cakephp.org:27000'));
        $this->assertTrue(Validation::url('http://cakephp.org:65000'));

        $this->assertTrue(Validation::url('[2001:0db8::1428:57ab]'));
        $this->assertTrue(Validation::url('[::1]'));
        $this->assertTrue(Validation::url('[2001:0db8::1428:57ab]:80'));
        $this->assertTrue(Validation::url('[::1]:80'));
        $this->assertTrue(Validation::url('http://[2001:0db8::1428:57ab]'));
        $this->assertTrue(Validation::url('http://[::1]'));
        $this->assertTrue(Validation::url('http://[2001:0db8::1428:57ab]:80'));
        $this->assertTrue(Validation::url('http://[::1]:80'));

        $this->assertFalse(Validation::url('[1::2::3]'));
    }

    public function testUuid()
    {
        $this->assertTrue(Validation::uuid('00000000-0000-0000-0000-000000000000'));
        $this->assertTrue(Validation::uuid('550e8400-e29b-11d4-a716-446655440000'));
        $this->assertFalse(Validation::uuid('BRAP-e29b-11d4-a716-446655440000'));
        $this->assertTrue(Validation::uuid('550E8400-e29b-11D4-A716-446655440000'));
        $this->assertFalse(Validation::uuid('550e8400-e29b11d4-a716-446655440000'));
        $this->assertFalse(Validation::uuid('550e8400-e29b-11d4-a716-4466440000'));
        $this->assertFalse(Validation::uuid('550e8400-e29b-11d4-a71-446655440000'));
        $this->assertFalse(Validation::uuid('550e8400-e29b-11d-a716-446655440000'));
        $this->assertFalse(Validation::uuid('550e8400-e29-11d4-a716-446655440000'));
    }

    /**
     * testInList method
     *
     * @return void
     */
    public function testInList()
    {
        $this->assertTrue(Validation::inList('one', ['one', 'two']));
        $this->assertTrue(Validation::inList('two', ['one', 'two']));
        $this->assertFalse(Validation::inList('three', ['one', 'two']));
        $this->assertFalse(Validation::inList('1one', [0, 1, 2, 3]));
        $this->assertFalse(Validation::inList('one', [0, 1, 2, 3]));
        $this->assertTrue(Validation::inList('2', [1, 2, 3]));
        $this->assertFalse(Validation::inList('2x', [1, 2, 3]));
        $this->assertFalse(Validation::inList(2, ['1', '2x', '3']));
        $this->assertFalse(Validation::inList('One', ['one', 'two']));

        // No hexadecimal for numbers.
        $this->assertFalse(Validation::inList('0x7B', ['ABC', '123']));
        $this->assertFalse(Validation::inList('0x7B', ['ABC', 123]));

        // case insensitive
        $this->assertTrue(Validation::inList('one', ['One', 'Two'], true));
        $this->assertTrue(Validation::inList('Two', ['one', 'two'], true));
        $this->assertFalse(Validation::inList('three', ['one', 'two'], true));
    }

    /**
     * testRange method
     *
     * @return void
     */
    public function testRange()
    {
        $this->assertFalse(Validation::range(20, 100, 1));
        $this->assertTrue(Validation::range(20, 1, 100));
        $this->assertFalse(Validation::range(.5, 1, 100));
        $this->assertTrue(Validation::range(.5, 0, 100));
        $this->assertTrue(Validation::range(5));
        $this->assertTrue(Validation::range(-5, -10, 1));
        $this->assertFalse(Validation::range('word'));
        $this->assertTrue(Validation::range(5.1));
        $this->assertTrue(Validation::range(2.1, 2.1, 3.2));
        $this->assertTrue(Validation::range(3.2, 2.1, 3.2));
        $this->assertFalse(Validation::range(2.099, 2.1, 3.2));
    }

    /**
     * Test range type checks
     *
     * @return void
     */
    public function testRangeTypeChecks()
    {
        $this->assertFalse(Validation::range('\x028', 1, 5), 'hexish encoding fails');
        $this->assertFalse(Validation::range('0b010', 1, 5), 'binary string data fails');
        $this->assertFalse(Validation::range('0x01', 1, 5), 'hex string data fails');
        $this->assertFalse(Validation::range('0x1', 1, 5), 'hex string data fails');

        $this->assertFalse(Validation::range('\x028', 1, 5), 'hexish encoding fails');
        $this->assertFalse(Validation::range('0b010', 1, 5), 'binary string data fails');
        $this->assertFalse(Validation::range('0x02', 1, 5), 'hex string data fails');
    }

    /**
     * testExtension method
     *
     * @return void
     */
    public function testExtension()
    {
        $this->assertTrue(Validation::extension('extension.jpeg'));
        $this->assertTrue(Validation::extension('extension.JPEG'));
        $this->assertTrue(Validation::extension('extension.gif'));
        $this->assertTrue(Validation::extension('extension.GIF'));
        $this->assertTrue(Validation::extension('extension.png'));
        $this->assertTrue(Validation::extension('extension.jpg'));
        $this->assertTrue(Validation::extension('extension.JPG'));
        $this->assertFalse(Validation::extension('noextension'));
        $this->assertTrue(Validation::extension('extension.pdf', ['PDF']));
        $this->assertFalse(Validation::extension('extension.jpg', ['GIF']));
        $this->assertTrue(Validation::extension(['extension.JPG', 'extension.gif', 'extension.png']));
        $this->assertTrue(Validation::extension(['file' => ['name' => 'file.jpg']]));
        $this->assertTrue(Validation::extension([
            'file1' => ['name' => 'file.jpg'],
            'file2' => ['name' => 'file.jpg'],
            'file3' => ['name' => 'file.jpg']
        ]));
        $this->assertFalse(Validation::extension(
            [
                'file1' => ['name' => 'file.jpg'],
                'file2' => ['name' => 'file.jpg'],
                'file3' => ['name' => 'file.jpg']
            ],
            ['gif']
        ));

        $this->assertFalse(Validation::extension(['noextension', 'extension.JPG', 'extension.gif', 'extension.png']));
        $this->assertFalse(Validation::extension(['extension.pdf', 'extension.JPG', 'extension.gif', 'extension.png']));
    }

    /**
     * testMoney method
     *
     * @return void
     */
    public function testMoney()
    {
        $this->assertTrue(Validation::money('100'));
        $this->assertTrue(Validation::money('100.11'));
        $this->assertTrue(Validation::money('100.112'));
        $this->assertTrue(Validation::money('100.1'));
        $this->assertTrue(Validation::money('100.111,1'));
        $this->assertTrue(Validation::money('100.111,11'));
        $this->assertFalse(Validation::money('100.111,111'));

        $this->assertTrue(Validation::money('$100'));
        $this->assertTrue(Validation::money('$100.11'));
        $this->assertTrue(Validation::money('$100.112'));
        $this->assertTrue(Validation::money('$100.1'));
        $this->assertFalse(Validation::money('$100.1111'));
        $this->assertFalse(Validation::money('text'));

        $this->assertTrue(Validation::money('100', 'right'));
        $this->assertTrue(Validation::money('100.11$', 'right'));
        $this->assertTrue(Validation::money('100.112$', 'right'));
        $this->assertTrue(Validation::money('100.1$', 'right'));
        $this->assertFalse(Validation::money('100.1111$', 'right'));

        $this->assertTrue(Validation::money('€100'));
        $this->assertTrue(Validation::money('€100.11'));
        $this->assertTrue(Validation::money('€100.112'));
        $this->assertTrue(Validation::money('€100.1'));
        $this->assertFalse(Validation::money('€100.1111'));

        $this->assertTrue(Validation::money('100', 'right'));
        $this->assertTrue(Validation::money('100.11€', 'right'));
        $this->assertTrue(Validation::money('100.112€', 'right'));
        $this->assertTrue(Validation::money('100.1€', 'right'));
        $this->assertFalse(Validation::money('100.1111€', 'right'));
    }

    /**
     * Test Multiple Select Validation
     *
     * @return void
     */
    public function testMultiple()
    {
        $this->assertTrue(Validation::multiple([0, 1, 2, 3]));
        $this->assertTrue(Validation::multiple([50, 32, 22, 0]));
        $this->assertTrue(Validation::multiple(['str', 'var', 'enum', 0]));
        $this->assertFalse(Validation::multiple(''));
        $this->assertFalse(Validation::multiple(null));
        $this->assertFalse(Validation::multiple([]));
        $this->assertTrue(Validation::multiple([0]));
        $this->assertTrue(Validation::multiple(['0']));

        $this->assertTrue(Validation::multiple([0, 3, 4, 5], ['in' => range(0, 10)]));
        $this->assertFalse(Validation::multiple([0, 15, 20, 5], ['in' => range(0, 10)]));
        $this->assertFalse(Validation::multiple([0, 5, 10, 11], ['in' => range(0, 10)]));
        $this->assertFalse(Validation::multiple(['boo', 'foo', 'bar'], ['in' => ['foo', 'bar', 'baz']]));
        $this->assertFalse(Validation::multiple(['foo', '1bar'], ['in' => range(0, 10)]));

        $this->assertFalse(Validation::multiple([1, 5, 10, 11], ['max' => 3]));
        $this->assertTrue(Validation::multiple([0, 5, 10, 11], ['max' => 4]));
        $this->assertFalse(Validation::multiple([0, 5, 10, 11, 55], ['max' => 4]));
        $this->assertTrue(Validation::multiple(['foo', 'bar', 'baz'], ['max' => 3]));
        $this->assertFalse(Validation::multiple(['foo', 'bar', 'baz', 'squirrel'], ['max' => 3]));

        $this->assertTrue(Validation::multiple([0, 5, 10, 11], ['min' => 3]));
        $this->assertTrue(Validation::multiple([0, 5, 10, 11, 55], ['min' => 3]));
        $this->assertFalse(Validation::multiple(['foo', 'bar', 'baz'], ['min' => 5]));
        $this->assertFalse(Validation::multiple(['foo', 'bar', 'baz', 'squirrel'], ['min' => 10]));

        $this->assertTrue(Validation::multiple([0, 5, 9], ['in' => range(0, 10), 'max' => 5]));
        $this->assertTrue(Validation::multiple(['0', '5', '9'], ['in' => range(0, 10), 'max' => 5]));

        $this->assertFalse(Validation::multiple([0, 5, 9, 8, 6, 2, 1], ['in' => range(0, 10), 'max' => 5]));
        $this->assertFalse(Validation::multiple([0, 5, 9, 8, 11], ['in' => range(0, 10), 'max' => 5]));

        $this->assertTrue(Validation::multiple([0, 5, 9], ['in' => range(0, 10), 'max' => 5, 'min' => 3]));
        $this->assertFalse(Validation::multiple(['', '5', '9'], ['max' => 5, 'min' => 3]));
        $this->assertFalse(Validation::multiple([0, 5, 9, 8, 6, 2, 1], ['in' => range(0, 10), 'max' => 5, 'min' => 2]));
        $this->assertFalse(Validation::multiple([0, 5, 9, 8, 11], ['in' => range(0, 10), 'max' => 5, 'min' => 2]));

        $this->assertFalse(Validation::multiple(['2x', '3x'], ['in' => [1, 2, 3, 4, 5]]));
        $this->assertFalse(Validation::multiple([2, 3], ['in' => ['1x', '2x', '3x', '4x']]));
        $this->assertFalse(Validation::multiple(['one'], ['in' => ['One', 'Two']]));
        $this->assertFalse(Validation::multiple(['Two'], ['in' => ['one', 'two']]));

        // case insensitive
        $this->assertTrue(Validation::multiple(['one'], ['in' => ['One', 'Two']], true));
        $this->assertTrue(Validation::multiple(['Two'], ['in' => ['one', 'two']], true));
        $this->assertFalse(Validation::multiple(['three'], ['in' => ['one', 'two']], true));
    }

    /**
     * testNumeric method
     *
     * @return void
     */
    public function testNumeric()
    {
        $this->assertFalse(Validation::numeric('teststring'));
        $this->assertFalse(Validation::numeric('1.1test'));
        $this->assertFalse(Validation::numeric('2test'));

        $this->assertTrue(Validation::numeric('2'));
        $this->assertTrue(Validation::numeric(2));
        $this->assertTrue(Validation::numeric(2.2));
        $this->assertTrue(Validation::numeric('2.2'));
    }

    /**
     * testNaturalNumber method
     *
     * @return void
     */
    public function testNaturalNumber()
    {
        $this->assertFalse(Validation::naturalNumber('teststring'));
        $this->assertFalse(Validation::naturalNumber('5.4'));
        $this->assertFalse(Validation::naturalNumber(99.004));
        $this->assertFalse(Validation::naturalNumber('0,05'));
        $this->assertFalse(Validation::naturalNumber('-2'));
        $this->assertFalse(Validation::naturalNumber(-2));
        $this->assertFalse(Validation::naturalNumber('0'));
        $this->assertFalse(Validation::naturalNumber('050'));

        $this->assertTrue(Validation::naturalNumber('2'));
        $this->assertTrue(Validation::naturalNumber(49));
        $this->assertTrue(Validation::naturalNumber('0', true));
        $this->assertTrue(Validation::naturalNumber(0, true));
    }

    /**
     * testDatetime method
     *
     * @return void
     */
    public function testDatetime()
    {
        $this->assertTrue(Validation::datetime('27-12-2006 01:00', 'dmy'));
        $this->assertTrue(Validation::datetime('27-12-2006 01:00', ['dmy']));
        $this->assertFalse(Validation::datetime('27-12-2006 1:00', 'dmy'));

        $this->assertTrue(Validation::datetime('27.12.2006 1:00pm', 'dmy'));
        $this->assertFalse(Validation::datetime('27.12.2006 13:00pm', 'dmy'));

        $this->assertTrue(Validation::datetime('27/12/2006 1:00pm', 'dmy'));
        $this->assertFalse(Validation::datetime('27/12/2006 9:00', 'dmy'));

        $this->assertFalse(Validation::datetime('27 12 2006 1:00pm', 'dmy'));
        $this->assertFalse(Validation::datetime('27 12 2006 24:00', 'dmy'));

        $this->assertFalse(Validation::datetime('00-00-0000 1:00pm', 'dmy'));
        $this->assertFalse(Validation::datetime('00.00.0000 1:00pm', 'dmy'));
        $this->assertFalse(Validation::datetime('00/00/0000 1:00pm', 'dmy'));
        $this->assertFalse(Validation::datetime('00 00 0000 1:00pm', 'dmy'));
        $this->assertFalse(Validation::datetime('31-11-2006 1:00pm', 'dmy'));
        $this->assertFalse(Validation::datetime('31.11.2006 1:00pm', 'dmy'));
        $this->assertFalse(Validation::datetime('31/11/2006 1:00pm', 'dmy'));
        $this->assertFalse(Validation::datetime('31 11 2006 1:00pm', 'dmy'));
    }

    /**
     * testMimeType method
     *
     * @return void
     */
    public function testMimeType()
    {
        $image = CORE_TESTS . 'test_app/webroot/img/cake.power.gif';
        $File = new File($image, false);

        $this->skipIf(!$File->mime(), 'Cannot determine mimeType');

        $this->assertTrue(Validation::mimeType($image, ['image/gif']));
        $this->assertTrue(Validation::mimeType(['tmp_name' => $image], ['image/gif']));
        $this->assertTrue(Validation::mimeType(['tmp_name' => $image], '#image/.+#'));
        $this->assertTrue(Validation::mimeType($image, ['image/GIF']));

        $this->assertFalse(Validation::mimeType($image, ['image/png']));
        $this->assertFalse(Validation::mimeType(['tmp_name' => $image], ['image/png']));
    }

    /**
     * testMimeTypeFalse method
     *
     * @expectedException \RuntimeException
     * @return void
     */
    public function testMimeTypeFalse()
    {
        $image = CORE_PATH . 'Cake/Test/TestApp/webroot/img/cake.power.gif';
        $File = new File($image, false);
        $this->skipIf($File->mime(), 'mimeType can be determined, no Exception will be thrown');
        Validation::mimeType($image, ['image/gif']);
    }

    /**
     * testUploadError method
     *
     * @return void
     */
    public function testUploadError()
    {
        $this->assertTrue(Validation::uploadError(0));
        $this->assertTrue(Validation::uploadError(['error' => 0]));
        $this->assertTrue(Validation::uploadError(['error' => '0']));

        $this->assertFalse(Validation::uploadError(2));
        $this->assertFalse(Validation::uploadError(['error' => 2]));
        $this->assertFalse(Validation::uploadError(['error' => '2']));

        $this->assertFalse(Validation::uploadError(UPLOAD_ERR_NO_FILE));
        $this->assertFalse(Validation::uploadError(UPLOAD_ERR_FORM_SIZE, true));
        $this->assertFalse(Validation::uploadError(UPLOAD_ERR_INI_SIZE, true));
        $this->assertFalse(Validation::uploadError(UPLOAD_ERR_NO_TMP_DIR, true));
        $this->assertTrue(Validation::uploadError(UPLOAD_ERR_NO_FILE, true));
    }

    /**
     * testFileSize method
     *
     * @return void
     */
    public function testFileSize()
    {
        $image = TEST_APP . 'webroot/img/cake.power.gif';
        $this->assertTrue(Validation::fileSize($image, '<', 1024));
        $this->assertTrue(Validation::fileSize(['tmp_name' => $image], 'isless', 1024));
        $this->assertTrue(Validation::fileSize($image, '<', '1KB'));
        $this->assertTrue(Validation::fileSize($image, '>=', 200));
        $this->assertTrue(Validation::fileSize($image, '==', 201));
        $this->assertTrue(Validation::fileSize($image, '==', '201B'));

        $this->assertFalse(Validation::fileSize($image, 'isgreater', 1024));
        $this->assertFalse(Validation::fileSize(['tmp_name' => $image], '>', '1KB'));
    }

    /**
     * Test uploaded file validation.
     *
     * @return void
     */
    public function testUploadedFileErrorCode()
    {
        $this->assertFalse(Validation::uploadedFile('derp'));
        $invalid = [
            'name' => 'testing'
        ];
        $this->assertFalse(Validation::uploadedFile($invalid));

        $file = [
            'name' => 'cake.power.gif',
            'tmp_name' => TEST_APP . 'webroot/img/cake.power.gif',
            'error' => UPLOAD_ERR_OK,
            'type' => 'image/gif',
            'size' => 201
        ];
        $this->assertTrue(Validation::uploadedFile($file));

        $file['error'] = UPLOAD_ERR_NO_FILE;
        $this->assertFalse(Validation::uploadedFile($file), 'Error upload should fail.');
    }

    /**
     * Test uploaded file validation.
     *
     * @return void
     */
    public function testUploadedFileMimeType()
    {
        $file = [
            'name' => 'cake.power.gif',
            'tmp_name' => TEST_APP . 'webroot/img/cake.power.gif',
            'error' => UPLOAD_ERR_OK,
            'type' => 'text/plain',
            'size' => 201
        ];
        $options = [
            'types' => ['text/plain']
        ];
        $this->assertFalse(Validation::uploadedFile($file, $options), 'Incorrect mimetype.');

        $options = [
            'types' => ['image/gif', 'image/png']
        ];
        $this->assertTrue(Validation::uploadedFile($file, $options));
    }

    /**
     * Test uploaded file validation.
     *
     * @return void
     */
    public function testUploadedFileSize()
    {
        $file = [
            'name' => 'cake.power.gif',
            'tmp_name' => TEST_APP . 'webroot/img/cake.power.gif',
            'error' => UPLOAD_ERR_OK,
            'type' => 'text/plain',
            'size' => 201
        ];
        $options = [
            'minSize' => 500
        ];
        $this->assertFalse(Validation::uploadedFile($file, $options), 'Too small');

        $options = [
            'maxSize' => 100
        ];
        $this->assertFalse(Validation::uploadedFile($file, $options), 'Too big');
    }

    /**
     * Test uploaded file validation.
     *
     * @return void
     */
    public function testUploadedFileNoFile()
    {
        $file = [
            'name' => '',
            'tmp_name' => TEST_APP . 'webroot/img/cake.power.gif',
            'error' => UPLOAD_ERR_NO_FILE,
            'type' => '',
            'size' => 0
        ];
        $options = [
            'optional' => true,
            'minSize' => 500,
            'types' => ['image/gif', 'image/png']
        ];
        $this->assertTrue(Validation::uploadedFile($file, $options), 'No file should be ok.');

        $options = [
            'optional' => false
        ];
        $this->assertFalse(Validation::uploadedFile($file, $options), 'File is required.');
    }

    /**
     * Test uploaded file validation.
     *
     * @return void
     */
    public function testUploadedFileWithDifferentFileParametersOrder()
    {
        $file = [
            'name' => 'cake.power.gif',
            'error' => UPLOAD_ERR_OK,
            'tmp_name' => TEST_APP . 'webroot/img/cake.power.gif',
            'type' => 'text/plain',
            'size' => 201
        ];
        $options = [];
        $this->assertTrue(Validation::uploadedFile($file, $options), 'Wrong order');
    }

    /**
     * Test the compareWith method.
     *
     * @return void
     */
    public function testCompareWith()
    {
        $context = [
            'data' => [
                'other' => 'a value'
            ]
        ];
        $this->assertTrue(Validation::compareWith('a value', 'other', $context));

        $context = [
            'data' => [
                'other' => 'different'
            ]
        ];
        $this->assertFalse(Validation::compareWith('a value', 'other', $context));

        $context = [];
        $this->assertFalse(Validation::compareWith('a value', 'other', $context));
    }

    /**
     * testContainsNonAlphaNumeric method
     *
     * @return void
     */
    public function testContainNonAlphaNumeric()
    {
        $this->assertFalse(Validation::containsNonAlphaNumeric('abcdefghijklmnopqrstuvwxyz'));
        $this->assertFalse(Validation::containsNonAlphaNumeric('ABCDEFGHIJKLMNOPQRSTUVWXYZ'));
        $this->assertFalse(Validation::containsNonAlphaNumeric('0123456789'));
        $this->assertFalse(Validation::containsNonAlphaNumeric('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'));

        $this->assertTrue(Validation::containsNonAlphaNumeric('#'));
        $this->assertTrue(Validation::containsNonAlphaNumeric("0\n"));
        $this->assertTrue(Validation::containsNonAlphaNumeric("\n"));
        $this->assertTrue(Validation::containsNonAlphaNumeric("\t"));
        $this->assertTrue(Validation::containsNonAlphaNumeric("\r"));
        $this->assertTrue(Validation::containsNonAlphaNumeric(" "));

        $this->assertTrue(Validation::containsNonAlphaNumeric('#abcdef'));
        $this->assertTrue(Validation::containsNonAlphaNumeric('abc#def'));
        $this->assertTrue(Validation::containsNonAlphaNumeric('abcdef#'));
        $this->assertTrue(Validation::containsNonAlphaNumeric('abc def'));
        $this->assertTrue(Validation::containsNonAlphaNumeric("abcdef\n"));

        $this->assertTrue(Validation::containsNonAlphaNumeric('##abcdef', 2));
        $this->assertTrue(Validation::containsNonAlphaNumeric('abcdef##', 2));
        $this->assertTrue(Validation::containsNonAlphaNumeric('#abcdef#', 2));
        $this->assertTrue(Validation::containsNonAlphaNumeric('#abc#def', 2));
        $this->assertTrue(Validation::containsNonAlphaNumeric('abc#def#', 2));

        $this->assertTrue(Validation::containsNonAlphaNumeric('#♥abcdef', 2));
        $this->assertTrue(Validation::containsNonAlphaNumeric('abcdef#♥', 2));
        $this->assertTrue(Validation::containsNonAlphaNumeric('#abcdef♥', 2));
        $this->assertTrue(Validation::containsNonAlphaNumeric('#abc♥def', 2));
        $this->assertTrue(Validation::containsNonAlphaNumeric('abc#def♥', 2));

        $this->assertTrue(Validation::containsNonAlphaNumeric('#♥abcdef', 2));
        $this->assertTrue(Validation::containsNonAlphaNumeric('abcdef#♥', 2));
        $this->assertTrue(Validation::containsNonAlphaNumeric('#abcdef♥', 2));
        $this->assertTrue(Validation::containsNonAlphaNumeric('#abc♥def', 2));
        $this->assertTrue(Validation::containsNonAlphaNumeric('abc#def♥', 2));

        $this->assertTrue(Validation::containsNonAlphaNumeric('###abcdef', 2));
        $this->assertTrue(Validation::containsNonAlphaNumeric('abc###def', 2));
        $this->assertTrue(Validation::containsNonAlphaNumeric('abcdef###', 2));
        $this->assertTrue(Validation::containsNonAlphaNumeric('#abc#def#', 2));

        $this->assertFalse(Validation::containsNonAlphaNumeric('##abcdef', 3));
        $this->assertFalse(Validation::containsNonAlphaNumeric('abcdef##', 3));
        $this->assertFalse(Validation::containsNonAlphaNumeric('abc##def', 3));
        $this->assertFalse(Validation::containsNonAlphaNumeric('ab#cd#ef', 3));
    }

    /**
     * Test the geoCoordinate method.
     *
     * @return void
     */
    public function testGeoCoordinate()
    {
        $this->assertTrue(Validation::geoCoordinate('51.165691, 10.451526'));
        $this->assertTrue(Validation::geoCoordinate('-25.274398, 133.775136'));
        $this->assertFalse(Validation::geoCoordinate('51.165691 10.451526'));
        $this->assertFalse(Validation::geoCoordinate('-245.274398, -133.775136'));
        $this->assertTrue(Validation::geoCoordinate('51.165691', ['format' => 'lat']));
        $this->assertTrue(Validation::geoCoordinate('10.451526', ['format' => 'long']));
    }

    /**
     * Test the geoCoordinate method.
     *
     * @return void
     */
    public function testLatitude()
    {
        $this->assertTrue(Validation::latitude('51.165691'));
        $this->assertFalse(Validation::latitude('200.23552'));
    }

    /**
     * Test the geoCoordinate method.
     *
     * @return void
     */
    public function testLongitude()
    {
        $this->assertTrue(Validation::longitude('10.451526'));
        $this->assertFalse(Validation::longitude('-190.52236'));
    }

    /**
     * Test isInteger
     *
     * @return void
     */
    public function testIsInteger()
    {
        $this->assertTrue(Validation::isInteger(-10));
        $this->assertTrue(Validation::isInteger(0));
        $this->assertTrue(Validation::isInteger(10));
        $this->assertTrue(Validation::isInteger('-10'));
        $this->assertTrue(Validation::isInteger('0'));
        $this->assertTrue(Validation::isInteger('10'));

        $this->assertFalse(Validation::isInteger('2.5'));
        $this->assertFalse(Validation::isInteger([]));
        $this->assertFalse(Validation::isInteger(new \StdClass));
        $this->assertFalse(Validation::isInteger('2 bears'));
    }

    /**
     * Test ascii
     *
     * @return void
     */
    public function testAscii()
    {
        $this->assertTrue(Validation::ascii('1 big blue bus.'));
        $this->assertTrue(Validation::ascii(',.<>[]{;/?\)()'));

        $this->assertFalse(Validation::ascii([]));
        $this->assertFalse(Validation::ascii(1001));
        $this->assertFalse(Validation::ascii(3.14));
        $this->assertFalse(Validation::ascii(new \StdClass));

        // Latin-1 supplement
        $this->assertFalse(Validation::ascii('some' . "\xc2\x82" . 'value'));
        $this->assertFalse(Validation::ascii('some' . "\xc3\xbf" . 'value'));

        // End of BMP
        $this->assertFalse(Validation::ascii('some' . "\xef\xbf\xbd" . 'value'));

        // Start of supplementary multilingual plane
        $this->assertFalse(Validation::ascii('some' . "\xf0\x90\x80\x80" . 'value'));
    }

    /**
     * Test utf8 basic
     *
     * @return void
     */
    public function testUtf8Basic()
    {
        $this->assertFalse(Validation::utf8([]));
        $this->assertFalse(Validation::utf8(1001));
        $this->assertFalse(Validation::utf8(3.14));
        $this->assertFalse(Validation::utf8(new \StdClass));
        $this->assertTrue(Validation::utf8('1 big blue bus.'));
        $this->assertTrue(Validation::utf8(',.<>[]{;/?\)()'));

        // Latin-1 supplement
        $this->assertTrue(Validation::utf8('some' . "\xc2\x82" . 'value'));
        $this->assertTrue(Validation::utf8('some' . "\xc3\xbf" . 'value'));

        // End of BMP
        $this->assertTrue(Validation::utf8('some' . "\xef\xbf\xbd" . 'value'));

        // Start of supplementary multilingual plane
        $this->assertFalse(Validation::utf8('some' . "\xf0\x90\x80\x80" . 'value'));

        // Grinning face
        $this->assertFalse(Validation::utf8('some' . "\xf0\x9f\x98\x80" . 'value'));
    }

    /**
     * Test utf8 extended
     *
     * @return void
     */
    public function testUtf8Extended()
    {
        $this->assertFalse(Validation::utf8([], ['extended' => true]));
        $this->assertFalse(Validation::utf8(1001, ['extended' => true]));
        $this->assertFalse(Validation::utf8(3.14, ['extended' => true]));
        $this->assertFalse(Validation::utf8(new \StdClass, ['extended' => true]));
        $this->assertTrue(Validation::utf8('1 big blue bus.', ['extended' => true]));
        $this->assertTrue(Validation::utf8(',.<>[]{;/?\)()', ['extended' => true]));

        // Latin-1 supplement
        $this->assertTrue(Validation::utf8('some' . "\xc2\x82" . 'value', ['extended' => true]));
        $this->assertTrue(Validation::utf8('some' . "\xc3\xbf" . 'value', ['extended' => true]));

        // End of BMP
        $this->assertTrue(Validation::utf8('some' . "\xef\xbf\xbd" . 'value', ['extended' => true]));

        // Start of supplementary multilingual plane
        $this->assertTrue(Validation::utf8('some' . "\xf0\x90\x80\x80" . 'value', ['extended' => true]));

        // Grinning face
        $this->assertTrue(Validation::utf8('some' . "\xf0\x9f\x98\x80" . 'value', ['extended' => true]));
    }
}
