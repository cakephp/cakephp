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
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2008, Cake Software Foundation, Inc.
 * @link				https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package			cake.tests
 * @subpackage		cake.tests.cases.libs
 * @since			CakePHP(tm) v 1.2.0.5432
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
uses('string');
/**
 * Short description for class.
 *
 * @package    cake.tests
 * @subpackage cake.tests.cases.libs
 */
class StringTest extends UnitTestCase {

	function testUuidGeneration() {
		$result = String::uuid();
		$match = preg_match("/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/", $result);
		$this->assertTrue($match);
	}

	function testMultipleUuidGeneration() {
		$check = array();
		$count = rand(10, 1000);
		for($i = 0; $i < $count; $i++) {
			$result = String::uuid();
			$match = preg_match("/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/", $result);
			$this->assertTrue($match);
			$this->assertFalse(in_array($result, $check));
			$check[] = $result;
		}
	}

	function testInsert() {
		$string = '2 + 2 = :sum. Cake is :adjective.';
		$expected = '2 + 2 = 4. Cake is yummy.';
		$r = String::insert($string, array('sum' => '4', 'adjective' => 'yummy'));
		$this->assertEqual($r, $expected);

		$string = '2 + 2 = %sum. Cake is %adjective.';
		$r = String::insert($string, array('sum' => '4', 'adjective' => 'yummy'), array('before' => '%'));
		$this->assertEqual($r, $expected);
		
		$string = '2 + 2 = 2sum2. Cake is 9adjective9.';
		$r = String::insert($string, array('sum' => '4', 'adjective' => 'yummy'), array('format' => '/([\d])%s\\1/'));
		$this->assertEqual($r, $expected);

		$string = '2 + 2 = 12sum21. Cake is 23adjective45.';
		$expected = '2 + 2 = 4. Cake is 23adjective45.';
		$r = String::insert($string, array('sum' => '4', 'adjective' => 'yummy'), array('format' => '/([\d])([\d])%s\\2\\1/'));
		$this->assertEqual($r, $expected);

		$string = '2 + 2 = <sum. Cake is <adjective>.';
		$expected = '2 + 2 = <sum. Cake is yummy.';
		$r = String::insert($string, array('sum' => '4', 'adjective' => 'yummy'), array('before' => '<', 'after' => '>'));
		$this->assertEqual($r, $expected);

		$string = '2 + 2 = \:sum. Cake is :adjective.';
		$expected = '2 + 2 = :sum. Cake is yummy.';
		$r = String::insert($string, array('sum' => '4', 'adjective' => 'yummy'));
		$this->assertEqual($r, $expected);

		$string = '2 + 2 = !:sum. Cake is :adjective.';
		$r = String::insert($string, array('sum' => '4', 'adjective' => 'yummy'), array('escape' => '!'));
		$this->assertEqual($r, $expected);

		$string = '2 + 2 = \%sum. Cake is %adjective.';
		$expected = '2 + 2 = %sum. Cake is yummy.';
		$r = String::insert($string, array('sum' => '4', 'adjective' => 'yummy'), array('before' => '%'));
		$this->assertEqual($r, $expected);

		$string = ':a :b \:a :a';
		$expected = '1 2 :a 1';
		$r = String::insert($string, array('a' => 1, 'b' => 2));
		$this->assertEqual($r, $expected);

		$string = ':a :b :c';
		$expected = '2 3';
		$r = String::insert($string, array('b' => 2, 'c' => 3), array('clean' => true));
		$this->assertEqual($r, $expected);

		$string = ':a :b :c';
		$expected = '1 3';
		$r = String::insert($string, array('a' => 1, 'c' => 3), array('clean' => true));
		$this->assertEqual($r, $expected);

		$string = ':a :b :c';
		$expected = '2 3';
		$r = String::insert($string, array('b' => 2, 'c' => 3), array('clean' => true));
		$this->assertEqual($r, $expected);

		$string = ':a, :b and :c';
		$expected = '2 and 3';
		$r = String::insert($string, array('b' => 2, 'c' => 3), array('clean' => true));
		$this->assertEqual($r, $expected);

	}

	function testUtf8() {
		$string = '!"#$%&\'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\]^_`abcdefghijklmnopqrstuvwxyz{|}~';
		$result = String::utf8($string);
		$expected = array(33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57,
								58, 59, 60, 61, 62, 63, 64, 65, 66, 67, 68, 69, 70, 71, 72, 73, 74, 75, 76, 77, 78, 79, 80, 81, 82,
								83, 84, 85, 86, 87, 88, 89, 90, 91, 92, 93, 94, 95, 96, 97, 98, 99, 100, 101, 102, 103, 104, 105,
								106, 107, 108, 109, 110, 111, 112, 113, 114, 115, 116, 117, 118, 119, 120, 121, 122, 123, 124, 125, 126);
		$this->assertEqual($result, $expected);

		$string = '¡¢£¤¥¦§¨©ª«¬­®¯°±²³´µ¶·¸¹º»¼½¾¿ÀÁÂÃÄÅÆÇÈ';
		$result = String::utf8($string);
		$expected = array(161, 162, 163, 164, 165, 166, 167, 168, 169, 170, 171, 172, 173, 174, 175, 176, 177, 178, 179, 180, 181,
								182, 183, 184, 185, 186, 187, 188, 189, 190, 191, 192, 193, 194, 195, 196, 197, 198, 199, 200);
		$this->assertEqual($result, $expected);

		$string = 'ÉÊËÌÍÎÏÐÑÒÓÔÕÖ×ØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõö÷øùúûüýþÿĀāĂăĄąĆćĈĉĊċČčĎďĐđĒēĔĕĖėĘęĚěĜĝĞğĠġĢģĤĥĦħĨĩĪīĬ';
		$result = String::utf8($string);
		$expected = array(201, 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215, 216, 217, 218, 219, 220, 221,
								222, 223, 224, 225, 226, 227, 228, 229, 230, 231, 232, 233, 234, 235, 236, 237, 238, 239, 240, 241, 242,
								243, 244, 245, 246, 247, 248, 249, 250, 251, 252, 253, 254, 255, 256, 257, 258, 259, 260, 261, 262, 263,
								264, 265, 266, 267, 268, 269, 270, 271, 272, 273, 274, 275, 276, 277, 278, 279, 280, 281, 282, 283, 284,
								285, 286, 287, 288, 289, 290, 291, 292, 293, 294, 295, 296, 297, 298, 299, 300);
		$this->assertEqual($result, $expected);

		$string = 'ĭĮįİıĲĳĴĵĶķĸĹĺĻļĽľĿŀŁłŃńŅņŇňŉŊŋŌōŎŏŐőŒœŔŕŖŗŘřŚśŜŝŞşŠšŢţŤťŦŧŨũŪūŬŭŮůŰűŲųŴŵŶŷŸŹźŻżŽžſƀƁƂƃƄƅƆƇƈƉƊƋƌƍƎƏƐ';
		$result = String::utf8($string);
		$expected = array(301, 302, 303, 304, 305, 306, 307, 308, 309, 310, 311, 312, 313, 314, 315, 316, 317, 318, 319, 320, 321,
								322, 323, 324, 325, 326, 327, 328, 329, 330, 331, 332, 333, 334, 335, 336, 337, 338, 339, 340, 341, 342,
								343, 344, 345, 346, 347, 348, 349, 350, 351, 352, 353, 354, 355, 356, 357, 358, 359, 360, 361, 362, 363,
								364, 365, 366, 367, 368, 369, 370, 371, 372, 373, 374, 375, 376, 377, 378, 379, 380, 381, 382, 383, 384,
								385, 386, 387, 388, 389, 390, 391, 392, 393, 394, 395, 396, 397, 398, 399, 400);
		$this->assertEqual($result, $expected);

		$string = 'ƑƒƓƔƕƖƗƘƙƚƛƜƝƞƟƠơƢƣƤƥƦƧƨƩƪƫƬƭƮƯưƱƲƳƴƵƶƷƸƹƺƻƼƽƾƿǀǁǂǃǄǅǆǇǈǉǊǋǌǍǎǏǐǑǒǓǔǕǖǗǘǙǚǛǜǝǞǟǠǡǢǣǤǥǦǧǨǩǪǫǬǭǮǯǰǱǲǳǴ';
		$result = String::utf8($string);
		$expected = array(401, 402, 403, 404, 405, 406, 407, 408, 409, 410, 411, 412, 413, 414, 415, 416, 417, 418, 419, 420, 421,
								422, 423, 424, 425, 426, 427, 428, 429, 430, 431, 432, 433, 434, 435, 436, 437, 438, 439, 440, 441, 442,
								443, 444, 445, 446, 447, 448, 449, 450, 451, 452, 453, 454, 455, 456, 457, 458, 459, 460, 461, 462, 463,
								464, 465, 466, 467, 468, 469, 470, 471, 472, 473, 474, 475, 476, 477, 478, 479, 480, 481, 482, 483, 484,
								485, 486, 487, 488, 489, 490, 491, 492, 493, 494, 495, 496, 497, 498, 499, 500);
		$this->assertEqual($result, $expected);

		$string = 'əɚɛɜɝɞɟɠɡɢɣɤɥɦɧɨɩɪɫɬɭɮɯɰɱɲɳɴɵɶɷɸɹɺɻɼɽɾɿʀʁʂʃʄʅʆʇʈʉʊʋʌʍʎʏʐʑʒʓʔʕʖʗʘʙʚʛʜʝʞʟʠʡʢʣʤʥʦʧʨʩʪʫʬʭʮʯʰʱʲʳʴʵʶʷʸʹʺʻʼ';
		$result = String::utf8($string);
		$expected = array(601, 602, 603, 604, 605, 606, 607, 608, 609, 610, 611, 612, 613, 614, 615, 616, 617, 618, 619, 620, 621,
								622, 623, 624, 625, 626, 627, 628, 629, 630, 631, 632, 633, 634, 635, 636, 637, 638, 639, 640, 641, 642,
								643, 644, 645, 646, 647, 648, 649, 650, 651, 652, 653, 654, 655, 656, 657, 658, 659, 660, 661, 662, 663,
								664, 665, 666, 667, 668, 669, 670, 671, 672, 673, 674, 675, 676, 677, 678, 679, 680, 681, 682, 683, 684,
								685, 686, 687, 688, 689, 690, 691, 692, 693, 694, 695, 696, 697, 698, 699, 700);
		$this->assertEqual($result, $expected);

		$string = 'ЀЁЂЃЄЅІЇЈЉЊЋЌЍЎЏАБВГДЕЖЗИЙКЛ';
		$result = String::utf8($string);
		$expected = array(1024, 1025, 1026, 1027, 1028, 1029, 1030, 1031, 1032, 1033, 1034, 1035, 1036, 1037, 1038, 1039, 1040, 1041,
								1042, 1043, 1044, 1045, 1046, 1047, 1048, 1049, 1050, 1051);
		$this->assertEqual($result, $expected);

		$string = 'МНОПРСТУФХЦЧШЩЪЫЬЭЮЯабвгдежзийклмнопрстуфхцчшщъыь';
		$result = String::utf8($string);
		$expected = array(1052, 1053, 1054, 1055, 1056, 1057, 1058, 1059, 1060, 1061, 1062, 1063, 1064, 1065, 1066, 1067, 1068, 1069,
								1070, 1071, 1072, 1073, 1074, 1075, 1076, 1077, 1078, 1079, 1080, 1081, 1082, 1083, 1084, 1085, 1086, 1087,
								1088, 1089, 1090, 1091, 1092, 1093, 1094, 1095, 1096, 1097, 1098, 1099, 1100);
		$this->assertEqual($result, $expected);

		$string = 'չպջռսվտ';
		$result = String::utf8($string);
		$expected = array(1401, 1402, 1403, 1404, 1405, 1406, 1407);
		$this->assertEqual($result, $expected);


		$string = 'فقكلمنهوىيًٌٍَُ';
		$result = String::utf8($string);
		$expected = array(1601, 1602, 1603, 1604, 1605, 1606, 1607, 1608, 1609, 1610, 1611, 1612, 1613, 1614, 1615);
		$this->assertEqual($result, $expected);

		$string = '✰✱✲✳✴✵✶✷✸✹✺✻✼✽✾✿❀❁❂❃❄❅❆❇❈❉❊❋❌❍❎❏❐❑❒❓❔❕❖❗❘❙❚❛❜❝❞';
		$result = String::utf8($string);
		$expected = array(10032, 10033, 10034, 10035, 10036, 10037, 10038, 10039, 10040, 10041, 10042, 10043, 10044,
								10045, 10046, 10047, 10048, 10049, 10050, 10051, 10052, 10053, 10054, 10055, 10056, 10057,
								10058, 10059, 10060, 10061, 10062, 10063, 10064, 10065, 10066, 10067, 10068, 10069, 10070,
								10071, 10072, 10073, 10074, 10075, 10076, 10077, 10078);
		$this->assertEqual($result, $expected);

		$string = '⺀⺁⺂⺃⺄⺅⺆⺇⺈⺉⺊⺋⺌⺍⺎⺏⺐⺑⺒⺓⺔⺕⺖⺗⺘⺙⺛⺜⺝⺞⺟⺠⺡⺢⺣⺤⺥⺦⺧⺨⺩⺪⺫⺬⺭⺮⺯⺰⺱⺲⺳⺴⺵⺶⺷⺸⺹⺺⺻⺼⺽⺾⺿⻀⻁⻂⻃⻄⻅⻆⻇⻈⻉⻊⻋⻌⻍⻎⻏⻐⻑⻒⻓⻔⻕⻖⻗⻘⻙⻚⻛⻜⻝⻞⻟⻠';
		$result = String::utf8($string);
		$expected = array(11904, 11905, 11906, 11907, 11908, 11909, 11910, 11911, 11912, 11913, 11914, 11915, 11916, 11917, 11918, 11919,
								11920, 11921, 11922, 11923, 11924, 11925, 11926, 11927, 11928, 11929, 11931, 11932, 11933, 11934, 11935, 11936,
								11937, 11938, 11939, 11940, 11941, 11942, 11943, 11944, 11945, 11946, 11947, 11948, 11949, 11950, 11951, 11952,
								11953, 11954, 11955, 11956, 11957, 11958, 11959, 11960, 11961, 11962, 11963, 11964, 11965, 11966, 11967, 11968,
								11969, 11970, 11971, 11972, 11973, 11974, 11975, 11976, 11977, 11978, 11979, 11980, 11981, 11982, 11983, 11984,
								11985, 11986, 11987, 11988, 11989, 11990, 11991, 11992, 11993, 11994, 11995, 11996, 11997, 11998, 11999, 12000);
		$this->assertEqual($result, $expected);

		$string = '⽅⽆⽇⽈⽉⽊⽋⽌⽍⽎⽏⽐⽑⽒⽓⽔⽕⽖⽗⽘⽙⽚⽛⽜⽝⽞⽟⽠⽡⽢⽣⽤⽥⽦⽧⽨⽩⽪⽫⽬⽭⽮⽯⽰⽱⽲⽳⽴⽵⽶⽷⽸⽹⽺⽻⽼⽽⽾⽿';
		$result = String::utf8($string);
		$expected = array(12101, 12102, 12103, 12104, 12105, 12106, 12107, 12108, 12109, 12110, 12111, 12112, 12113, 12114, 12115, 12116,
								12117, 12118, 12119, 12120, 12121, 12122, 12123, 12124, 12125, 12126, 12127, 12128, 12129, 12130, 12131, 12132,
								12133, 12134, 12135, 12136, 12137, 12138, 12139, 12140, 12141, 12142, 12143, 12144, 12145, 12146, 12147, 12148,
								12149, 12150, 12151, 12152, 12153, 12154, 12155, 12156, 12157, 12158, 12159);
		$this->assertEqual($result, $expected);

		$string = '눡눢눣눤눥눦눧눨눩눪눫눬눭눮눯눰눱눲눳눴눵눶눷눸눹눺눻눼눽눾눿뉀뉁뉂뉃뉄뉅뉆뉇뉈뉉뉊뉋뉌뉍뉎뉏뉐뉑뉒뉓뉔뉕뉖뉗뉘뉙뉚뉛뉜뉝뉞뉟뉠뉡뉢뉣뉤뉥뉦뉧뉨뉩뉪뉫뉬뉭뉮뉯뉰뉱뉲뉳뉴뉵뉶뉷뉸뉹뉺뉻뉼뉽뉾뉿늀늁늂늃늄';
		$result = String::utf8($string);
		$expected = array(45601, 45602, 45603, 45604, 45605, 45606, 45607, 45608, 45609, 45610, 45611, 45612, 45613, 45614, 45615, 45616,
								45617, 45618, 45619, 45620, 45621, 45622, 45623, 45624, 45625, 45626, 45627, 45628, 45629, 45630, 45631, 45632,
								45633, 45634, 45635, 45636, 45637, 45638, 45639, 45640, 45641, 45642, 45643, 45644, 45645, 45646, 45647, 45648,
								45649, 45650, 45651, 45652, 45653, 45654, 45655, 45656, 45657, 45658, 45659, 45660, 45661, 45662, 45663, 45664,
								45665, 45666, 45667, 45668, 45669, 45670, 45671, 45672, 45673, 45674, 45675, 45676, 45677, 45678, 45679, 45680,
								45681, 45682, 45683, 45684, 45685, 45686, 45687, 45688, 45689, 45690, 45691, 45692, 45693, 45694, 45695, 45696,
								45697, 45698, 45699, 45700);
		$this->assertEqual($result, $expected);

		$string = 'ﹰﹱﹲﹳﹴ﹵ﹶﹷﹸﹹﹺﹻﹼﹽﹾﹿﺀﺁﺂﺃﺄﺅﺆﺇﺈﺉﺊﺋﺌﺍﺎﺏﺐﺑﺒﺓﺔﺕﺖﺗﺘﺙﺚﺛﺜﺝﺞﺟﺠﺡﺢﺣﺤﺥﺦﺧﺨﺩﺪﺫﺬﺭﺮﺯﺰ';
		$result = String::utf8($string);
		$expected = array(65136, 65137, 65138, 65139, 65140, 65141, 65142, 65143, 65144, 65145, 65146, 65147, 65148, 65149, 65150, 65151,
								65152, 65153, 65154, 65155, 65156, 65157, 65158, 65159, 65160, 65161, 65162, 65163, 65164, 65165, 65166, 65167,
								65168, 65169, 65170, 65171, 65172, 65173, 65174, 65175, 65176, 65177, 65178, 65179, 65180, 65181, 65182, 65183,
								65184, 65185, 65186, 65187, 65188, 65189, 65190, 65191, 65192, 65193, 65194, 65195, 65196, 65197, 65198, 65199,
								65200);
		$this->assertEqual($result, $expected);

		$string = 'ﺱﺲﺳﺴﺵﺶﺷﺸﺹﺺﺻﺼﺽﺾﺿﻀﻁﻂﻃﻄﻅﻆﻇﻈﻉﻊﻋﻌﻍﻎﻏﻐﻑﻒﻓﻔﻕﻖﻗﻘﻙﻚﻛﻜﻝﻞﻟﻠﻡﻢﻣﻤﻥﻦﻧﻨﻩﻪﻫﻬﻭﻮﻯﻰﻱﻲﻳﻴﻵﻶﻷﻸﻹﻺﻻﻼ';
		$result = String::utf8($string);
		$expected = array(65201, 65202, 65203, 65204, 65205, 65206, 65207, 65208, 65209, 65210, 65211, 65212, 65213, 65214, 65215, 65216,
								65217, 65218, 65219, 65220, 65221, 65222, 65223, 65224, 65225, 65226, 65227, 65228, 65229, 65230, 65231, 65232,
								65233, 65234, 65235, 65236, 65237, 65238, 65239, 65240, 65241, 65242, 65243, 65244, 65245, 65246, 65247, 65248,
								65249, 65250, 65251, 65252, 65253, 65254, 65255, 65256, 65257, 65258, 65259, 65260, 65261, 65262, 65263, 65264,
								65265, 65266, 65267, 65268, 65269, 65270, 65271, 65272, 65273, 65274, 65275, 65276);
		$this->assertEqual($result, $expected);


		$string = 'ａｂｃｄｅｆｇｈｉｊｋｌｍｎｏｐｑｒｓｔｕｖｗｘｙｚ';
		$result = String::utf8($string);
		$expected = array(65345, 65346, 65347, 65348, 65349, 65350, 65351, 65352, 65353, 65354, 65355, 65356, 65357, 65358, 65359, 65360,
								65361, 65362, 65363, 65364, 65365, 65366, 65367, 65368, 65369, 65370);
		$this->assertEqual($result, $expected);


		$string = '｡｢｣､･ｦｧｨｩｪｫｬｭｮｯｰｱｲｳｴｵｶｷｸ';
		$result = String::utf8($string);
		$expected = array(65377, 65378, 65379, 65380, 65381, 65382, 65383, 65384, 65385, 65386, 65387, 65388, 65389, 65390, 65391, 65392,
								65393, 65394, 65395, 65396, 65397, 65398, 65399, 65400);
		$this->assertEqual($result, $expected);

		$string = 'ｹｺｻｼｽｾｿﾀﾁﾂﾃﾄﾅﾆﾇﾈﾉﾊﾋﾌﾍﾎﾏﾐﾑﾒﾓﾔﾕﾖﾗﾘﾙﾚﾛﾜﾝﾞ';
		$result = String::utf8($string);
		$expected = array(65401, 65402, 65403, 65404, 65405, 65406, 65407, 65408, 65409, 65410, 65411, 65412, 65413, 65414, 65415, 65416,
								65417, 65418, 65419, 65420, 65421, 65422, 65423, 65424, 65425, 65426, 65427, 65428, 65429, 65430, 65431, 65432,
								65433, 65434, 65435, 65436, 65437, 65438);
		$this->assertEqual($result, $expected);

		$string = 'Ĥēĺļŏ, Ŵőřļď!';
		$result = String::utf8($string);
		$expected = array(292, 275, 314, 316, 335, 44, 32, 372, 337, 345, 316, 271, 33);
		$this->assertEqual($result, $expected);

		$string = 'Hello, World!';
		$result = String::utf8($string);
		$expected = array(72, 101, 108, 108, 111, 44, 32, 87, 111, 114, 108, 100, 33);
		$this->assertEqual($result, $expected);

		$string = '¨';
		$result = String::utf8($string);
		$expected = array(168);
		$this->assertEqual($result, $expected);

		$string = '¿';
		$result = String::utf8($string);
		$expected = array(191);
		$this->assertEqual($result, $expected);

		$string = 'čini';
		$result = String::utf8($string);
		$expected = array(269, 105, 110, 105);
		$this->assertEqual($result, $expected);

		$string = 'moći';
		$result = String::utf8($string);
		$expected = array(109, 111, 263, 105);
		$this->assertEqual($result, $expected);

		$string = 'državni';
		$result = String::utf8($string);
		$expected = array(100, 114, 382, 97, 118, 110, 105);
		$this->assertEqual($result, $expected);

		$string = '把百度设为首页';
		$result = String::utf8($string);
		$expected = array(25226, 30334, 24230, 35774, 20026, 39318, 39029);
		$this->assertEqual($result, $expected);

		$string = '一二三周永龍';
		$result = String::utf8($string);
		$expected = array(19968, 20108, 19977, 21608, 27704, 40845);
		$this->assertEqual($result, $expected);
	}

	function testAscii() {
		$utf8 = array(33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57,
							58, 59, 60, 61, 62, 63, 64, 65, 66, 67, 68, 69, 70, 71, 72, 73, 74, 75, 76, 77, 78, 79, 80, 81, 82,
							83, 84, 85, 86, 87, 88, 89, 90, 91, 92, 93, 94, 95, 96, 97, 98, 99, 100, 101, 102, 103, 104, 105,
							106, 107, 108, 109, 110, 111, 112, 113, 114, 115, 116, 117, 118, 119, 120, 121, 122, 123, 124, 125, 126);
		$result = String::ascii($utf8);

		$expected = '!"#$%&\'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\]^_`abcdefghijklmnopqrstuvwxyz{|}~';
		$this->assertEqual($result, $expected);

		$utf8 = array(161, 162, 163, 164, 165, 166, 167, 168, 169, 170, 171, 172, 173, 174, 175, 176, 177, 178, 179, 180, 181,
								182, 183, 184, 185, 186, 187, 188, 189, 190, 191, 192, 193, 194, 195, 196, 197, 198, 199, 200);
		$result = String::ascii($utf8);

		$expected = '¡¢£¤¥¦§¨©ª«¬­®¯°±²³´µ¶·¸¹º»¼½¾¿ÀÁÂÃÄÅÆÇÈ';
		$this->assertEqual($result, $expected);

		$utf8 = array(201, 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215, 216, 217, 218, 219, 220, 221,
								222, 223, 224, 225, 226, 227, 228, 229, 230, 231, 232, 233, 234, 235, 236, 237, 238, 239, 240, 241, 242,
								243, 244, 245, 246, 247, 248, 249, 250, 251, 252, 253, 254, 255, 256, 257, 258, 259, 260, 261, 262, 263,
								264, 265, 266, 267, 268, 269, 270, 271, 272, 273, 274, 275, 276, 277, 278, 279, 280, 281, 282, 283, 284,
								285, 286, 287, 288, 289, 290, 291, 292, 293, 294, 295, 296, 297, 298, 299, 300);
		$result = String::ascii($utf8);
		$expected = 'ÉÊËÌÍÎÏÐÑÒÓÔÕÖ×ØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõö÷øùúûüýþÿĀāĂăĄąĆćĈĉĊċČčĎďĐđĒēĔĕĖėĘęĚěĜĝĞğĠġĢģĤĥĦħĨĩĪīĬ';
		$this->assertEqual($result, $expected);

		$utf8 = array(301, 302, 303, 304, 305, 306, 307, 308, 309, 310, 311, 312, 313, 314, 315, 316, 317, 318, 319, 320, 321,
								322, 323, 324, 325, 326, 327, 328, 329, 330, 331, 332, 333, 334, 335, 336, 337, 338, 339, 340, 341, 342,
								343, 344, 345, 346, 347, 348, 349, 350, 351, 352, 353, 354, 355, 356, 357, 358, 359, 360, 361, 362, 363,
								364, 365, 366, 367, 368, 369, 370, 371, 372, 373, 374, 375, 376, 377, 378, 379, 380, 381, 382, 383, 384,
								385, 386, 387, 388, 389, 390, 391, 392, 393, 394, 395, 396, 397, 398, 399, 400);
		$expected = 'ĭĮįİıĲĳĴĵĶķĸĹĺĻļĽľĿŀŁłŃńŅņŇňŉŊŋŌōŎŏŐőŒœŔŕŖŗŘřŚśŜŝŞşŠšŢţŤťŦŧŨũŪūŬŭŮůŰűŲųŴŵŶŷŸŹźŻżŽžſƀƁƂƃƄƅƆƇƈƉƊƋƌƍƎƏƐ';
		$result = String::ascii($utf8);
		$this->assertEqual($result, $expected);

		$utf8 = array(401, 402, 403, 404, 405, 406, 407, 408, 409, 410, 411, 412, 413, 414, 415, 416, 417, 418, 419, 420, 421,
								422, 423, 424, 425, 426, 427, 428, 429, 430, 431, 432, 433, 434, 435, 436, 437, 438, 439, 440, 441, 442,
								443, 444, 445, 446, 447, 448, 449, 450, 451, 452, 453, 454, 455, 456, 457, 458, 459, 460, 461, 462, 463,
								464, 465, 466, 467, 468, 469, 470, 471, 472, 473, 474, 475, 476, 477, 478, 479, 480, 481, 482, 483, 484,
								485, 486, 487, 488, 489, 490, 491, 492, 493, 494, 495, 496, 497, 498, 499, 500);
		$expected = 'ƑƒƓƔƕƖƗƘƙƚƛƜƝƞƟƠơƢƣƤƥƦƧƨƩƪƫƬƭƮƯưƱƲƳƴƵƶƷƸƹƺƻƼƽƾƿǀǁǂǃǄǅǆǇǈǉǊǋǌǍǎǏǐǑǒǓǔǕǖǗǘǙǚǛǜǝǞǟǠǡǢǣǤǥǦǧǨǩǪǫǬǭǮǯǰǱǲǳǴ';
		$result = String::ascii($utf8);
		$this->assertEqual($result, $expected);

		$utf8 = array(601, 602, 603, 604, 605, 606, 607, 608, 609, 610, 611, 612, 613, 614, 615, 616, 617, 618, 619, 620, 621,
								622, 623, 624, 625, 626, 627, 628, 629, 630, 631, 632, 633, 634, 635, 636, 637, 638, 639, 640, 641, 642,
								643, 644, 645, 646, 647, 648, 649, 650, 651, 652, 653, 654, 655, 656, 657, 658, 659, 660, 661, 662, 663,
								664, 665, 666, 667, 668, 669, 670, 671, 672, 673, 674, 675, 676, 677, 678, 679, 680, 681, 682, 683, 684,
								685, 686, 687, 688, 689, 690, 691, 692, 693, 694, 695, 696, 697, 698, 699, 700);
		$expected = 'əɚɛɜɝɞɟɠɡɢɣɤɥɦɧɨɩɪɫɬɭɮɯɰɱɲɳɴɵɶɷɸɹɺɻɼɽɾɿʀʁʂʃʄʅʆʇʈʉʊʋʌʍʎʏʐʑʒʓʔʕʖʗʘʙʚʛʜʝʞʟʠʡʢʣʤʥʦʧʨʩʪʫʬʭʮʯʰʱʲʳʴʵʶʷʸʹʺʻʼ';
		$result = String::ascii($utf8);
		$this->assertEqual($result, $expected);

		$utf8 = array(1024, 1025, 1026, 1027, 1028, 1029, 1030, 1031, 1032, 1033, 1034, 1035, 1036, 1037, 1038, 1039, 1040, 1041,
								1042, 1043, 1044, 1045, 1046, 1047, 1048, 1049, 1050, 1051);
		$expected = 'ЀЁЂЃЄЅІЇЈЉЊЋЌЍЎЏАБВГДЕЖЗИЙКЛ';
		$result = String::ascii($utf8);
		$this->assertEqual($result, $expected);

		$utf8 = array(1052, 1053, 1054, 1055, 1056, 1057, 1058, 1059, 1060, 1061, 1062, 1063, 1064, 1065, 1066, 1067, 1068, 1069,
								1070, 1071, 1072, 1073, 1074, 1075, 1076, 1077, 1078, 1079, 1080, 1081, 1082, 1083, 1084, 1085, 1086, 1087,
								1088, 1089, 1090, 1091, 1092, 1093, 1094, 1095, 1096, 1097, 1098, 1099, 1100);
		$expected = 'МНОПРСТУФХЦЧШЩЪЫЬЭЮЯабвгдежзийклмнопрстуфхцчшщъыь';
		$result = String::ascii($utf8);
		$this->assertEqual($result, $expected);

		$utf8 = array(1401, 1402, 1403, 1404, 1405, 1406, 1407);
		$expected = 'չպջռսվտ';
		$result = String::ascii($utf8);
		$this->assertEqual($result, $expected);

		$utf8 = array(1601, 1602, 1603, 1604, 1605, 1606, 1607, 1608, 1609, 1610, 1611, 1612, 1613, 1614, 1615);
		$expected = 'فقكلمنهوىيًٌٍَُ';
		$result = String::ascii($utf8);
		$this->assertEqual($result, $expected);

		$utf8 = array(10032, 10033, 10034, 10035, 10036, 10037, 10038, 10039, 10040, 10041, 10042, 10043, 10044,
								10045, 10046, 10047, 10048, 10049, 10050, 10051, 10052, 10053, 10054, 10055, 10056, 10057,
								10058, 10059, 10060, 10061, 10062, 10063, 10064, 10065, 10066, 10067, 10068, 10069, 10070,
								10071, 10072, 10073, 10074, 10075, 10076, 10077, 10078);
		$expected = '✰✱✲✳✴✵✶✷✸✹✺✻✼✽✾✿❀❁❂❃❄❅❆❇❈❉❊❋❌❍❎❏❐❑❒❓❔❕❖❗❘❙❚❛❜❝❞';
		$result = String::ascii($utf8);
		$this->assertEqual($result, $expected);

		$utf8 = array(11904, 11905, 11906, 11907, 11908, 11909, 11910, 11911, 11912, 11913, 11914, 11915, 11916, 11917, 11918, 11919,
								11920, 11921, 11922, 11923, 11924, 11925, 11926, 11927, 11928, 11929, 11931, 11932, 11933, 11934, 11935, 11936,
								11937, 11938, 11939, 11940, 11941, 11942, 11943, 11944, 11945, 11946, 11947, 11948, 11949, 11950, 11951, 11952,
								11953, 11954, 11955, 11956, 11957, 11958, 11959, 11960, 11961, 11962, 11963, 11964, 11965, 11966, 11967, 11968,
								11969, 11970, 11971, 11972, 11973, 11974, 11975, 11976, 11977, 11978, 11979, 11980, 11981, 11982, 11983, 11984,
								11985, 11986, 11987, 11988, 11989, 11990, 11991, 11992, 11993, 11994, 11995, 11996, 11997, 11998, 11999, 12000);
		$expected = '⺀⺁⺂⺃⺄⺅⺆⺇⺈⺉⺊⺋⺌⺍⺎⺏⺐⺑⺒⺓⺔⺕⺖⺗⺘⺙⺛⺜⺝⺞⺟⺠⺡⺢⺣⺤⺥⺦⺧⺨⺩⺪⺫⺬⺭⺮⺯⺰⺱⺲⺳⺴⺵⺶⺷⺸⺹⺺⺻⺼⺽⺾⺿⻀⻁⻂⻃⻄⻅⻆⻇⻈⻉⻊⻋⻌⻍⻎⻏⻐⻑⻒⻓⻔⻕⻖⻗⻘⻙⻚⻛⻜⻝⻞⻟⻠';
		$result = String::ascii($utf8);
		$this->assertEqual($result, $expected);

		$utf8 = array(12101, 12102, 12103, 12104, 12105, 12106, 12107, 12108, 12109, 12110, 12111, 12112, 12113, 12114, 12115, 12116,
								12117, 12118, 12119, 12120, 12121, 12122, 12123, 12124, 12125, 12126, 12127, 12128, 12129, 12130, 12131, 12132,
								12133, 12134, 12135, 12136, 12137, 12138, 12139, 12140, 12141, 12142, 12143, 12144, 12145, 12146, 12147, 12148,
								12149, 12150, 12151, 12152, 12153, 12154, 12155, 12156, 12157, 12158, 12159);
		$expected = '⽅⽆⽇⽈⽉⽊⽋⽌⽍⽎⽏⽐⽑⽒⽓⽔⽕⽖⽗⽘⽙⽚⽛⽜⽝⽞⽟⽠⽡⽢⽣⽤⽥⽦⽧⽨⽩⽪⽫⽬⽭⽮⽯⽰⽱⽲⽳⽴⽵⽶⽷⽸⽹⽺⽻⽼⽽⽾⽿';
		$result = String::ascii($utf8);
		$this->assertEqual($result, $expected);

		$utf8 = array(45601, 45602, 45603, 45604, 45605, 45606, 45607, 45608, 45609, 45610, 45611, 45612, 45613, 45614, 45615, 45616,
								45617, 45618, 45619, 45620, 45621, 45622, 45623, 45624, 45625, 45626, 45627, 45628, 45629, 45630, 45631, 45632,
								45633, 45634, 45635, 45636, 45637, 45638, 45639, 45640, 45641, 45642, 45643, 45644, 45645, 45646, 45647, 45648,
								45649, 45650, 45651, 45652, 45653, 45654, 45655, 45656, 45657, 45658, 45659, 45660, 45661, 45662, 45663, 45664,
								45665, 45666, 45667, 45668, 45669, 45670, 45671, 45672, 45673, 45674, 45675, 45676, 45677, 45678, 45679, 45680,
								45681, 45682, 45683, 45684, 45685, 45686, 45687, 45688, 45689, 45690, 45691, 45692, 45693, 45694, 45695, 45696,
								45697, 45698, 45699, 45700);
		$expected = '눡눢눣눤눥눦눧눨눩눪눫눬눭눮눯눰눱눲눳눴눵눶눷눸눹눺눻눼눽눾눿뉀뉁뉂뉃뉄뉅뉆뉇뉈뉉뉊뉋뉌뉍뉎뉏뉐뉑뉒뉓뉔뉕뉖뉗뉘뉙뉚뉛뉜뉝뉞뉟뉠뉡뉢뉣뉤뉥뉦뉧뉨뉩뉪뉫뉬뉭뉮뉯뉰뉱뉲뉳뉴뉵뉶뉷뉸뉹뉺뉻뉼뉽뉾뉿늀늁늂늃늄';
		$result = String::ascii($utf8);
		$this->assertEqual($result, $expected);

		$utf8 = array(65136, 65137, 65138, 65139, 65140, 65141, 65142, 65143, 65144, 65145, 65146, 65147, 65148, 65149, 65150, 65151,
								65152, 65153, 65154, 65155, 65156, 65157, 65158, 65159, 65160, 65161, 65162, 65163, 65164, 65165, 65166, 65167,
								65168, 65169, 65170, 65171, 65172, 65173, 65174, 65175, 65176, 65177, 65178, 65179, 65180, 65181, 65182, 65183,
								65184, 65185, 65186, 65187, 65188, 65189, 65190, 65191, 65192, 65193, 65194, 65195, 65196, 65197, 65198, 65199,
								65200);
		$expected = 'ﹰﹱﹲﹳﹴ﹵ﹶﹷﹸﹹﹺﹻﹼﹽﹾﹿﺀﺁﺂﺃﺄﺅﺆﺇﺈﺉﺊﺋﺌﺍﺎﺏﺐﺑﺒﺓﺔﺕﺖﺗﺘﺙﺚﺛﺜﺝﺞﺟﺠﺡﺢﺣﺤﺥﺦﺧﺨﺩﺪﺫﺬﺭﺮﺯﺰ';
		$result = String::ascii($utf8);
		$this->assertEqual($result, $expected);

		$utf8 = array(65201, 65202, 65203, 65204, 65205, 65206, 65207, 65208, 65209, 65210, 65211, 65212, 65213, 65214, 65215, 65216,
								65217, 65218, 65219, 65220, 65221, 65222, 65223, 65224, 65225, 65226, 65227, 65228, 65229, 65230, 65231, 65232,
								65233, 65234, 65235, 65236, 65237, 65238, 65239, 65240, 65241, 65242, 65243, 65244, 65245, 65246, 65247, 65248,
								65249, 65250, 65251, 65252, 65253, 65254, 65255, 65256, 65257, 65258, 65259, 65260, 65261, 65262, 65263, 65264,
								65265, 65266, 65267, 65268, 65269, 65270, 65271, 65272, 65273, 65274, 65275, 65276);
		$expected = 'ﺱﺲﺳﺴﺵﺶﺷﺸﺹﺺﺻﺼﺽﺾﺿﻀﻁﻂﻃﻄﻅﻆﻇﻈﻉﻊﻋﻌﻍﻎﻏﻐﻑﻒﻓﻔﻕﻖﻗﻘﻙﻚﻛﻜﻝﻞﻟﻠﻡﻢﻣﻤﻥﻦﻧﻨﻩﻪﻫﻬﻭﻮﻯﻰﻱﻲﻳﻴﻵﻶﻷﻸﻹﻺﻻﻼ';
		$result = String::ascii($utf8);
		$this->assertEqual($result, $expected);

		$utf8 = array(65345, 65346, 65347, 65348, 65349, 65350, 65351, 65352, 65353, 65354, 65355, 65356, 65357, 65358, 65359, 65360,
								65361, 65362, 65363, 65364, 65365, 65366, 65367, 65368, 65369, 65370);
		$expected = 'ａｂｃｄｅｆｇｈｉｊｋｌｍｎｏｐｑｒｓｔｕｖｗｘｙｚ';
		$result = String::ascii($utf8);
		$this->assertEqual($result, $expected);

		$utf8 = array(65377, 65378, 65379, 65380, 65381, 65382, 65383, 65384, 65385, 65386, 65387, 65388, 65389, 65390, 65391, 65392,
								65393, 65394, 65395, 65396, 65397, 65398, 65399, 65400);
		$expected = '｡｢｣､･ｦｧｨｩｪｫｬｭｮｯｰｱｲｳｴｵｶｷｸ';
		$result = String::ascii($utf8);
		$this->assertEqual($result, $expected);

		$utf8 = array(65401, 65402, 65403, 65404, 65405, 65406, 65407, 65408, 65409, 65410, 65411, 65412, 65413, 65414, 65415, 65416,
								65417, 65418, 65419, 65420, 65421, 65422, 65423, 65424, 65425, 65426, 65427, 65428, 65429, 65430, 65431, 65432,
								65433, 65434, 65435, 65436, 65437, 65438);
		$expected = 'ｹｺｻｼｽｾｿﾀﾁﾂﾃﾄﾅﾆﾇﾈﾉﾊﾋﾌﾍﾎﾏﾐﾑﾒﾓﾔﾕﾖﾗﾘﾙﾚﾛﾜﾝﾞ';
		$result = String::ascii($utf8);
		$this->assertEqual($result, $expected);

		$utf8 = array(292, 275, 314, 316, 335, 44, 32, 372, 337, 345, 316, 271, 33);
		$expected = 'Ĥēĺļŏ, Ŵőřļď!';
		$result = String::ascii($utf8);
		$this->assertEqual($result, $expected);

		$utf8 = array(72, 101, 108, 108, 111, 44, 32, 87, 111, 114, 108, 100, 33);
		$expected = 'Hello, World!';
		$result = String::ascii($utf8);
		$this->assertEqual($result, $expected);

		$utf8 = array(168);
		$expected = '¨';
		$result = String::ascii($utf8);
		$this->assertEqual($result, $expected);

		$utf8 = array(191);
		$expected = '¿';
		$result = String::ascii($utf8);
		$this->assertEqual($result, $expected);

		$utf8 = array(269, 105, 110, 105);
		$expected = 'čini';
		$result = String::ascii($utf8);
		$this->assertEqual($result, $expected);

		$utf8 = array(109, 111, 263, 105);
		$expected = 'moći';
		$result = String::ascii($utf8);
		$this->assertEqual($result, $expected);

		$utf8 = array(100, 114, 382, 97, 118, 110, 105);
		$expected = 'državni';
		$result = String::ascii($utf8);
		$this->assertEqual($result, $expected);

		$utf8 = array(25226, 30334, 24230, 35774, 20026, 39318, 39029);
		$expected = '把百度设为首页';
		$result = String::ascii($utf8);
		$this->assertEqual($result, $expected);

		$utf8 = array(19968, 20108, 19977, 21608, 27704, 40845);
		$expected = '一二三周永龍';
		$result = String::ascii($utf8);
		$this->assertEqual($result, $expected);
	}

	function testStringPosition() {
		$string = '!"#$%&\'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\]^_`abcdefghijklmnopqrstuvwxyz{|}~';
		$find   = 'A';
		$result = String::strpos($string, $find);
		$expected = 32;
		$this->assertEqual($result, $expected);

		$string = '¡¢£¤¥¦§¨©ª«¬­®¯°±²³´µ¶·¸¹º»¼½¾¿ÀÁÂÃÄÅÆÇÈ';
		$find   = 'Á';
		$result = String::strpos($string, $find);
		$expected = 32;
		$this->assertEqual($result, $expected);

		$string = 'ÉÊËÌÍÎÏÐÑÒÓÔÕÖ×ØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõö÷øùúûüýþÿĀāĂăĄąĆćĈĉĊċČčĎďĐđĒēĔĕĖėĘęĚěĜĝĞğĠġĢģĤĥĦħĨĩĪīĬ';
		$find   = 'é';
		$result = String::strpos($string, $find);
		$expected = 32;
		$this->assertEqual($result, $expected);

		$string = 'ĭĮįİıĲĳĴĵĶķĸĹĺĻļĽľĿŀŁłŃńŅņŇňŉŊŋŌōŎŏŐőŒœŔŕŖŗŘřŚśŜŝŞşŠšŢţŤťŦŧŨũŪūŬŭŮůŰűŲųŴŵŶŷŸŹźŻżŽžſƀƁƂƃƄƅƆƇƈƉƊƋƌƍƎƏƐ';
		$find   = 'ō';
		$result = String::strpos($string, $find);
		$expected = 32;
		$this->assertEqual($result, $expected);

		$string = 'ƑƒƓƔƕƖƗƘƙƚƛƜƝƞƟƠơƢƣƤƥƦƧƨƩƪƫƬƭƮƯưƱƲƳƴƵƶƷƸƹƺƻƼƽƾƿǀǁǂǃǄǅǆǇǈǉǊǋǌǍǎǏǐǑǒǓǔǕǖǗǘǙǚǛǜǝǞǟǠǡǢǣǤǥǦǧǨǩǪǫǬǭǮǯǰǱǲǳǴ';
		$find   = 'Ʊ';
		$result = String::strpos($string, $find);
		$expected = 32;
		$this->assertEqual($result, $expected);

		$string = 'əɚɛɜɝɞɟɠɡɢɣɤɥɦɧɨɩɪɫɬɭɮɯɰɱɲɳɴɵɶɷɸɹɺɻɼɽɾɿʀʁʂʃʄʅʆʇʈʉʊʋʌʍʎʏʐʑʒʓʔʕʖʗʘʙʚʛʜʝʞʟʠʡʢʣʤʥʦʧʨʩʪʫʬʭʮʯʰʱʲʳʴʵʶʷʸʹʺʻʼ';
		$find   = 'ɹ';
		$result = String::strpos($string, $find);
		$expected = 32;
		$this->assertEqual($result, $expected);

		$string = 'ЀЁЂЃЄЅІЇЈЉЊЋЌЍЎЏАБВГДЕЖЗИЙКЛ';
		$find   = 'Д';
		$result = String::strpos($string, $find);
		$expected = 20;
		$this->assertEqual($result, $expected);

		$string = 'МНОПРСТУФХЦЧШЩЪЫЬЭЮЯабвгдежзийклмнопрстуфхцчшщъыь';
		$find   = 'а';
		$result = String::strpos($string, $find);
		$expected = 20;
		$this->assertEqual($result, $expected);

		$string = 'չպջռսվտ';
		$find   = 'վ';
		$result = String::strpos($string, $find);
		$expected = 5;
		$this->assertEqual($result, $expected);

		$string = 'فقكلمنهوىيًٌٍَُ';
		$find   = 'ي';
		$result = String::strpos($string, $find);
		$expected = 9;
		$this->assertEqual($result, $expected);

		$string = '✰✱✲✳✴✵✶✷✸✹✺✻✼✽✾✿❀❁❂❃❄❅❆❇❈❉❊❋❌❍❎❏❐❑❒❓❔❕❖❗❘❙❚❛❜❝❞';
		$find   = '❄';
		$result = String::strpos($string, $find);
		$expected = 20;
		$this->assertEqual($result, $expected);

		$string = '⺀⺁⺂⺃⺄⺅⺆⺇⺈⺉⺊⺋⺌⺍⺎⺏⺐⺑⺒⺓⺔⺕⺖⺗⺘⺙⺛⺜⺝⺞⺟⺠⺡⺢⺣⺤⺥⺦⺧⺨⺩⺪⺫⺬⺭⺮⺯⺰⺱⺲⺳⺴⺵⺶⺷⺸⺹⺺⺻⺼⺽⺾⺿⻀⻁⻂⻃⻄⻅⻆⻇⻈⻉⻊⻋⻌⻍⻎⻏⻐⻑⻒⻓⻔⻕⻖⻗⻘⻙⻚⻛⻜⻝⻞⻟⻠';
		$find   = '⺡';
		$result = String::strpos($string, $find);
		$expected = 32;
		$this->assertEqual($result, $expected);

		$string = '⽅⽆⽇⽈⽉⽊⽋⽌⽍⽎⽏⽐⽑⽒⽓⽔⽕⽖⽗⽘⽙⽚⽛⽜⽝⽞⽟⽠⽡⽢⽣⽤⽥⽦⽧⽨⽩⽪⽫⽬⽭⽮⽯⽰⽱⽲⽳⽴⽵⽶⽷⽸⽹⽺⽻⽼⽽⽾⽿';
		$find   = '⽥';
		$result = String::strpos($string, $find);
		$expected = 32;
		$this->assertEqual($result, $expected);

		$string = '눡눢눣눤눥눦눧눨눩눪눫눬눭눮눯눰눱눲눳눴눵눶눷눸눹눺눻눼눽눾눿뉀뉁뉂뉃뉄뉅뉆뉇뉈뉉뉊뉋뉌뉍뉎뉏뉐뉑뉒뉓뉔뉕뉖뉗뉘뉙뉚뉛뉜뉝뉞뉟뉠뉡뉢뉣뉤뉥뉦뉧뉨뉩뉪뉫뉬뉭뉮뉯뉰뉱뉲뉳뉴뉵뉶뉷뉸뉹뉺뉻뉼뉽뉾뉿늀늁늂늃늄';
		$find   = '뉁';
		$result = String::strpos($string, $find);
		$expected = 32;
		$this->assertEqual($result, $expected);

		$string = 'ﹰﹱﹲﹳﹴ﹵ﹶﹷﹸﹹﹺﹻﹼﹽﹾﹿﺀﺁﺂﺃﺄﺅﺆﺇﺈﺉﺊﺋﺌﺍﺎﺏﺐﺑﺒﺓﺔﺕﺖﺗﺘﺙﺚﺛﺜﺝﺞﺟﺠﺡﺢﺣﺤﺥﺦﺧﺨﺩﺪﺫﺬﺭﺮﺯﺰ';
		$find   = 'ﺐ';
		$result = String::strpos($string, $find);
		$expected = 32;
		$this->assertEqual($result, $expected);

		$string = 'ﺱﺲﺳﺴﺵﺶﺷﺸﺹﺺﺻﺼﺽﺾﺿﻀﻁﻂﻃﻄﻅﻆﻇﻈﻉﻊﻋﻌﻍﻎﻏﻐﻑﻒﻓﻔﻕﻖﻗﻘﻙﻚﻛﻜﻝﻞﻟﻠﻡﻢﻣﻤﻥﻦﻧﻨﻩﻪﻫﻬﻭﻮﻯﻰﻱﻲﻳﻴﻵﻶﻷﻸﻹﻺﻻﻼ';
		$find   = 'ﻑ';
		$result = String::strpos($string, $find);
		$expected = 32;
		$this->assertEqual($result, $expected);

		$string = 'ａｂｃｄｅｆｇｈｉｊｋｌｍｎｏｐｑｒｓｔｕｖｗｘｙｚ';
		$find   = 'ｚ';
		$result = String::strpos($string, $find);
		$expected = 25;
		$this->assertEqual($result, $expected);

		$string = '｡｢｣､･ｦｧｨｩｪｫｬｭｮｯｰｱｲｳｴｵｶｷｸ';
		$find   = 'ｸ';
		$result = String::strpos($string, $find);
		$expected = 23;
		$this->assertEqual($result, $expected);

		$string = 'ｹｺｻｼｽｾｿﾀﾁﾂﾃﾄﾅﾆﾇﾈﾉﾊﾋﾌﾍﾎﾏﾐﾑﾒﾓﾔﾕﾖﾗﾘﾙﾚﾛﾜﾝﾞ';
		$find   = 'ﾙ';
		$result = String::strpos($string, $find);
		$expected = 32;
		$this->assertEqual($result, $expected);

		$string = 'Ĥēĺļŏ, Ŵőřļď!';
		$find   = ',';
		$result = String::strpos($string, $find);
		$expected = 5;
		$this->assertEqual($result, $expected);

		$string = 'Hello, World!';
		$find   = ',';
		$result = String::strpos($string, $find);
		$expected = 5;
		$this->assertEqual($result, $expected);

		$string = '¨';
		$find   = '"';
		$result = String::strpos($string, $find);
		$this->assertFalse($result);

		$string = '¿';
		$find   = '?';
		$result = String::strpos($string, $find);
		$this->assertFalse($result);

		$string = 'čini';
		$find   = 'č';
		$result = String::strpos($string, $find);
		$expected = 0;
		$this->assertEqual($result, $expected);

		$string = 'moći';
		$find   = 'ć';
		$result = String::strpos($string, $find);
		$expected = 2;
		$this->assertEqual($result, $expected);

		$string = 'državni';
		$find   = 'ž';
		$result = String::strpos($string, $find);
		$expected = 2;
		$this->assertEqual($result, $expected);

		$string = '把百度设为首页';
		$find   = '首';
		$result = String::strpos($string, $find);
		$expected = 5;
		$this->assertEqual($result, $expected);

		$string = '一二三周永龍';
		$find   = '周';
		$result = String::strpos($string, $find);
		$expected = 3;
		$this->assertEqual($result, $expected);
	}

	function testOffsetStringPosition() {
		$string = 'abcdabcdabcd';
		$find   = 'a';
		$result = String::strpos($string, $find, 5);
		$expected = 8;
		$this->assertEqual($result, $expected);

		$string = '¡¢£¤¡¢£¤¡¢£¤';
		$find   = '¡';
		$result = String::strpos($string, $find, 5);
		$expected = 8;
		$this->assertEqual($result, $expected);

		$string = 'ÉÊËÌÉÊËÌÉÊËÌ';
		$find   = 'É';
		$result = String::strpos($string, $find, 5);
		$expected = 8;
		$this->assertEqual($result, $expected);

		$string = 'ĭĮįİĭĮįİĭĮįİ';
		$find   = 'ĭ';
		$result = String::strpos($string, $find, 5);
		$expected = 8;
		$this->assertEqual($result, $expected);

		$string ='ƑƒƓƔƑƒƓƔƑƒƓƔ';
		$find   = 'Ƒ';
		$result = String::strpos($string, $find, 5);
		$expected = 8;
		$this->assertEqual($result, $expected);

		$string = 'əɚɛɜəɚɛɜəɚɛɜ';
		$find   = 'ə';
		$result = String::strpos($string, $find, 5);
		$expected = 8;
		$this->assertEqual($result, $expected);

		$string = 'ЀЁЂЃЀЁЂЃЀЁЂЃ';
		$find   = 'Ѐ';
		$result = String::strpos($string, $find, 5);
		$expected = 8;
		$this->assertEqual($result, $expected);

		$string = 'МНОПМНОПМНОП';
		$find   = 'М';
		$result = String::strpos($string, $find, 5);
		$expected = 8;
		$this->assertEqual($result, $expected);

		$string = 'չպջռչպջռչպջռ';
		$find   = 'չ';
		$result = String::strpos($string, $find, 5);
		$expected = 8;
		$this->assertEqual($result, $expected);

		$string = 'فقكلفقكلفقكل';
		$find   = 'ف';
		$result = String::strpos($string, $find, 5);
		$expected = 8;
		$this->assertEqual($result, $expected);

		$string = '✰✱✲✳✰✱✲✳✰✱✲✳';
		$find   = '✰';
		$result = String::strpos($string, $find, 5);
		$expected = 8;
		$this->assertEqual($result, $expected);

		$string = '⺀⺁⺂⺃⺀⺁⺂⺃⺀⺁⺂⺃';
		$find   = '⺀';
		$result = String::strpos($string, $find, 5);
		$expected = 8;
		$this->assertEqual($result, $expected);

		$string = '⽅⽆⽇⽈⽅⽆⽇⽈⽅⽆⽇⽈';
		$find   = '⽅';
		$result = String::strpos($string, $find, 5);
		$expected = 8;
		$this->assertEqual($result, $expected);

		$string = '눡눢눣눤눡눢눣눤눡눢눣눤';
		$find   = '눡';
		$result = String::strpos($string, $find, 5);
		$expected = 8;
		$this->assertEqual($result, $expected);

		$string = 'ﹲﹳﹴ﹵ﹲﹳﹴ﹵ﹲﹳﹴ﹵';
		$find   = 'ﹲ';
		$result = String::strpos($string, $find, 5);
		$expected = 8;
		$this->assertEqual($result, $expected);

		$string = 'ﻓﻔﻕﻖﻓﻔﻕﻖﻓﻔﻕﻖ';
		$find   = 'ﻓ';
		$result = String::strpos($string, $find, 5);
		$expected = 8;
		$this->assertEqual($result, $expected);

		$string = 'ａｂｃｄａｂｃｄａｂｃｄ';
		$find   = 'ａ';
		$result = String::strpos($string, $find, 5);
		$expected = 8;
		$this->assertEqual($result, $expected);

		$string = 'ｨｩｪｫｨｩｪｫｨｩｪｫ';
		$find   = 'ｨ';
		$result = String::strpos($string, $find, 5);
		$expected = 8;
		$this->assertEqual($result, $expected);

		$string = 'ｹｺｻｼｽｾｿﾀﾁﾂﾃﾄﾅﾆﾇﾈﾉﾊﾋﾌﾍﾎﾏﾐﾑﾒﾓﾔﾕﾖﾗﾘﾙﾚﾛﾜﾝﾞ';
		$find   = 'ﾙ';
		$result = String::strpos($string, $find);
		$expected = 32;
		$this->assertEqual($result, $expected);

		$string = 'ЀЁЀЁЀЁ';
		$find   = '';
		$result = String::strpos($string, $find, 5);
		$expected = 8;
		//$this->assertEqual($result, $expected);

		$string = 'ЩЪЩЪЩЪ';
		$find   = '';
		$result = String::strpos($string, $find, 5);
		$expected = 8;
		//$this->assertEqual($result, $expected);

		$string = 'Ĥēĺļŏ, Ŵőřļď!';
		$find   = 'ļ';
		$result = String::strpos($string, $find, 4);
		$expected = 10;
		$this->assertEqual($result, $expected);

		$string = 'Hello, World!';
		$find   = 'l';
		$result = String::strpos($string, $find, 4);
		$expected = 10;
		$this->assertEqual($result, $expected);

		$string = 'čini';
		$find   = 'i';
		$result = String::strpos($string, $find, 2);
		$expected = 3;
		$this->assertEqual($result, $expected);

		$string = 'moći';
		$find   = 'ć';
		$result = String::strpos($string, $find, 3);
		$this->assertFalse($result);

		$string = 'državni';
		$find   = 'ž';
		$result = String::strpos($string, $find, 3);
		$this->assertFalse($result);

		$string = '把百度设为首页';
		$find   = '首';
		$result = String::strpos($string, $find, 6);
		$this->assertFalse($result);

		$string = '一二三周永龍';
		$find   = '周';
		$result = String::strpos($string, $find, 4);
		$this->assertFalse($result);
	}

	function testStrtoupper() {
		$string = '!"#$%&\'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\]^_`abcdefghijklmnopqrstuvwxyz{|}~';
		$result = String::strtoupper($string);
		$expected = '!"#$%&\'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\]^_`ABCDEFGHIJKLMNOPQRSTUVWXYZ{|}~';
		$this->assertEqual($result, $expected);

		$string = 'à';
		$result = String::strtoupper($string);
		$expected = 'À';
		$this->assertEqual($result, $expected);

		$string = 'á';
		$result = String::strtoupper($string);
		$expected = 'Á';
		$this->assertEqual($result, $expected);

		$string = 'â';
		$result = String::strtoupper($string);
		$expected = 'Â';
		$this->assertEqual($result, $expected);

		$string = 'ã';
		$result = String::strtoupper($string);
		$expected = 'Ã';
		$this->assertEqual($result, $expected);

		$string = 'ä';
		$result = String::strtoupper($string);
		$expected = 'Ä';
		$this->assertEqual($result, $expected);

		$string = 'å';
		$result = String::strtoupper($string);
		$expected = 'Å';
		$this->assertEqual($result, $expected);

		$string = 'æ';
		$result = String::strtoupper($string);
		$expected = 'Æ';
		$this->assertEqual($result, $expected);

		$string = 'ç';
		$result = String::strtoupper($string);
		$expected = 'Ç';
		$this->assertEqual($result, $expected);

		$string = 'è';
		$result = String::strtoupper($string);
		$expected = 'È';
		$this->assertEqual($result, $expected);

		$string = 'é';
		$result = String::strtoupper($string);
		$expected = 'É';
		$this->assertEqual($result, $expected);

		$string = 'ê';
		$result = String::strtoupper($string);
		$expected = 'Ê';
		$this->assertEqual($result, $expected);

		$string = 'ë';
		$result = String::strtoupper($string);
		$expected = 'Ë';
		$this->assertEqual($result, $expected);

		$string = 'ì';
		$result = String::strtoupper($string);
		$expected = 'Ì';
		$this->assertEqual($result, $expected);

		$string = 'í';
		$result = String::strtoupper($string);
		$expected = 'Í';
		$this->assertEqual($result, $expected);

		$string = 'î';
		$result = String::strtoupper($string);
		$expected = 'Î';
		$this->assertEqual($result, $expected);

		$string = 'ï';
		$result = String::strtoupper($string);
		$expected = 'Ï';
		$this->assertEqual($result, $expected);

		$string = 'ð';
		$result = String::strtoupper($string);
		$expected = 'Ð';
		$this->assertEqual($result, $expected);

		$string = 'ñ';
		$result = String::strtoupper($string);
		$expected = 'Ñ';
		$this->assertEqual($result, $expected);

		$string = 'ò';
		$result = String::strtoupper($string);
		$expected = 'Ò';
		$this->assertEqual($result, $expected);

		$string = 'ó';
		$result = String::strtoupper($string);
		$expected = 'Ó';
		$this->assertEqual($result, $expected);

		$string = 'ô';
		$result = String::strtoupper($string);
		$expected = 'Ô';
		$this->assertEqual($result, $expected);

		$string = 'õ';
		$result = String::strtoupper($string);
		$expected = 'Õ';
		$this->assertEqual($result, $expected);

		$string = 'ö';
		$result = String::strtoupper($string);
		$expected = 'Ö';
		$this->assertEqual($result, $expected);

		$string = 'ø';
		$result = String::strtoupper($string);
		$expected = 'Ø';
		$this->assertEqual($result, $expected);

		$string = 'ù';
		$result = String::strtoupper($string);
		$expected = 'Ù';
		$this->assertEqual($result, $expected);

		$string = 'ú';
		$result = String::strtoupper($string);
		$expected = 'Ú';
		$this->assertEqual($result, $expected);

		$string = 'û';
		$result = String::strtoupper($string);
		$expected = 'Û';
		$this->assertEqual($result, $expected);

		$string = 'ü';
		$result = String::strtoupper($string);
		$expected = 'Ü';
		$this->assertEqual($result, $expected);

		$string = 'ý';
		$result = String::strtoupper($string);
		$expected = 'Ý';
		$this->assertEqual($result, $expected);

		$string = 'þ';
		$result = String::strtoupper($string);
		$expected = 'Þ';
		$this->assertEqual($result, $expected);

		$string = 'àáâãäåæçèéêëìíîïðñòóôõöøùúûüýþ';
		$result = String::strtoupper($string);
		$expected = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞ';
		$this->assertEqual($result, $expected);

		$string = 'ā';
		$result = String::strtoupper($string);
		$expected = 'Ā';
		$this->assertEqual($result, $expected);

		$string = 'ă';
		$result = String::strtoupper($string);
		$expected = 'Ă';
		$this->assertEqual($result, $expected);

		$string = 'ą';
		$result = String::strtoupper($string);
		$expected = 'Ą';
		$this->assertEqual($result, $expected);

		$string = 'ć';
		$result = String::strtoupper($string);
		$expected = 'Ć';
		$this->assertEqual($result, $expected);

		$string = 'ĉ';
		$result = String::strtoupper($string);
		$expected = 'Ĉ';
		$this->assertEqual($result, $expected);

		$string = 'ċ';
		$result = String::strtoupper($string);
		$expected = 'Ċ';
		$this->assertEqual($result, $expected);

		$string = 'č';
		$result = String::strtoupper($string);
		$expected = 'Č';
		$this->assertEqual($result, $expected);

		$string = 'ď';
		$result = String::strtoupper($string);
		$expected = 'Ď';
		$this->assertEqual($result, $expected);

		$string = 'đ';
		$result = String::strtoupper($string);
		$expected = 'Đ';
		$this->assertEqual($result, $expected);

		$string = 'ē';
		$result = String::strtoupper($string);
		$expected = 'Ē';
		$this->assertEqual($result, $expected);

		$string = 'ĕ';
		$result = String::strtoupper($string);
		$expected = 'Ĕ';
		$this->assertEqual($result, $expected);

		$string = 'ė';
		$result = String::strtoupper($string);
		$expected = 'Ė';
		$this->assertEqual($result, $expected);

		$string = 'ę';
		$result = String::strtoupper($string);
		$expected = 'Ę';
		$this->assertEqual($result, $expected);

		$string = 'ě';
		$result = String::strtoupper($string);
		$expected = 'Ě';
		$this->assertEqual($result, $expected);

		$string = 'ĝ';
		$result = String::strtoupper($string);
		$expected = 'Ĝ';
		$this->assertEqual($result, $expected);

		$string = 'ğ';
		$result = String::strtoupper($string);
		$expected = 'Ğ';
		$this->assertEqual($result, $expected);

		$string = 'ġ';
		$result = String::strtoupper($string);
		$expected = 'Ġ';
		$this->assertEqual($result, $expected);

		$string = 'ģ';
		$result = String::strtoupper($string);
		$expected = 'Ģ';
		$this->assertEqual($result, $expected);

		$string = 'ĥ';
		$result = String::strtoupper($string);
		$expected = 'Ĥ';
		$this->assertEqual($result, $expected);

		$string = 'ħ';
		$result = String::strtoupper($string);
		$expected = 'Ħ';
		$this->assertEqual($result, $expected);

		$string = 'ĩ';
		$result = String::strtoupper($string);
		$expected = 'Ĩ';
		$this->assertEqual($result, $expected);

		$string = 'ī';
		$result = String::strtoupper($string);
		$expected = 'Ī';
		$this->assertEqual($result, $expected);

		$string = 'ĭ';
		$result = String::strtoupper($string);
		$expected = 'Ĭ';
		$this->assertEqual($result, $expected);

		$string = 'į';
		$result = String::strtoupper($string);
		$expected = 'Į';
		$this->assertEqual($result, $expected);

		$string = 'ĳ';
		$result = String::strtoupper($string);
		$expected = 'Ĳ';
		$this->assertEqual($result, $expected);

		$string = 'ĵ';
		$result = String::strtoupper($string);
		$expected = 'Ĵ';
		$this->assertEqual($result, $expected);

		$string = 'ķ';
		$result = String::strtoupper($string);
		$expected = 'Ķ';
		$this->assertEqual($result, $expected);

		$string = 'ĺ';
		$result = String::strtoupper($string);
		$expected = 'Ĺ';
		$this->assertEqual($result, $expected);

		$string = 'ļ';
		$result = String::strtoupper($string);
		$expected = 'Ļ';
		$this->assertEqual($result, $expected);

		$string = 'ľ';
		$result = String::strtoupper($string);
		$expected = 'Ľ';
		$this->assertEqual($result, $expected);

		$string = 'ŀ';
		$result = String::strtoupper($string);
		$expected = 'Ŀ';
		$this->assertEqual($result, $expected);

		$string = 'ł';
		$result = String::strtoupper($string);
		$expected = 'Ł';
		$this->assertEqual($result, $expected);

		$string = 'ń';
		$result = String::strtoupper($string);
		$expected = 'Ń';
		$this->assertEqual($result, $expected);

		$string = 'ņ';
		$result = String::strtoupper($string);
		$expected = 'Ņ';
		$this->assertEqual($result, $expected);

		$string = 'ň';
		$result = String::strtoupper($string);
		$expected = 'Ň';
		$this->assertEqual($result, $expected);

		$string = 'ŋ';
		$result = String::strtoupper($string);
		$expected = 'Ŋ';
		$this->assertEqual($result, $expected);

		$string = 'ō';
		$result = String::strtoupper($string);
		$expected = 'Ō';
		$this->assertEqual($result, $expected);

		$string = 'ŏ';
		$result = String::strtoupper($string);
		$expected = 'Ŏ';
		$this->assertEqual($result, $expected);

		$string = 'ő';
		$result = String::strtoupper($string);
		$expected = 'Ő';
		$this->assertEqual($result, $expected);

		$string = 'œ';
		$result = String::strtoupper($string);
		$expected = 'Œ';
		$this->assertEqual($result, $expected);

		$string = 'ŕ';
		$result = String::strtoupper($string);
		$expected = 'Ŕ';
		$this->assertEqual($result, $expected);

		$string = 'ŗ';
		$result = String::strtoupper($string);
		$expected = 'Ŗ';
		$this->assertEqual($result, $expected);

		$string = 'ř';
		$result = String::strtoupper($string);
		$expected = 'Ř';
		$this->assertEqual($result, $expected);

		$string = 'ś';
		$result = String::strtoupper($string);
		$expected = 'Ś';
		$this->assertEqual($result, $expected);

		$string = 'ŝ';
		$result = String::strtoupper($string);
		$expected = 'Ŝ';
		$this->assertEqual($result, $expected);

		$string = 'ş';
		$result = String::strtoupper($string);
		$expected = 'Ş';
		$this->assertEqual($result, $expected);

		$string = 'š';
		$result = String::strtoupper($string);
		$expected = 'Š';
		$this->assertEqual($result, $expected);

		$string = 'ţ';
		$result = String::strtoupper($string);
		$expected = 'Ţ';
		$this->assertEqual($result, $expected);

		$string = 'ť';
		$result = String::strtoupper($string);
		$expected = 'Ť';
		$this->assertEqual($result, $expected);

		$string = 'ŧ';
		$result = String::strtoupper($string);
		$expected = 'Ŧ';
		$this->assertEqual($result, $expected);

		$string = 'ũ';
		$result = String::strtoupper($string);
		$expected = 'Ũ';
		$this->assertEqual($result, $expected);

		$string = 'ū';
		$result = String::strtoupper($string);
		$expected = 'Ū';
		$this->assertEqual($result, $expected);

		$string = 'ŭ';
		$result = String::strtoupper($string);
		$expected = 'Ŭ';
		$this->assertEqual($result, $expected);

		$string = 'ů';
		$result = String::strtoupper($string);
		$expected = 'Ů';
		$this->assertEqual($result, $expected);

		$string = 'ű';
		$result = String::strtoupper($string);
		$expected = 'Ű';
		$this->assertEqual($result, $expected);

		$string = 'ų';
		$result = String::strtoupper($string);
		$expected = 'Ų';
		$this->assertEqual($result, $expected);

		$string = 'ŵ';
		$result = String::strtoupper($string);
		$expected = 'Ŵ';
		$this->assertEqual($result, $expected);

		$string = 'ŷ';
		$result = String::strtoupper($string);
		$expected = 'Ŷ';
		$this->assertEqual($result, $expected);

		$string = 'ź';
		$result = String::strtoupper($string);
		$expected = 'Ź';
		$this->assertEqual($result, $expected);

		$string = 'ż';
		$result = String::strtoupper($string);
		$expected = 'Ż';
		$this->assertEqual($result, $expected);

		$string = 'ž';
		$result = String::strtoupper($string);
		$expected = 'Ž';
		$this->assertEqual($result, $expected);

		$string = 'āăąćĉċčďđēĕėęěĝğġģĥħĩīĭįĳĵķĺļľŀłńņňŋōŏőœŕŗřśŝşšţťŧũūŭůűųŵŷźżž';
		$result = String::strtoupper($string);
		$expected = 'ĀĂĄĆĈĊČĎĐĒĔĖĘĚĜĞĠĢĤĦĨĪĬĮĲĴĶĹĻĽĿŁŃŅŇŊŌŎŐŒŔŖŘŚŜŞŠŢŤŦŨŪŬŮŰŲŴŶŹŻŽ';
		$this->assertEqual($result, $expected);

		$string = 'Ĥēĺļŏ, Ŵőřļď!';
		$result = String::strtoupper($string);
		$expected = 'ĤĒĹĻŎ, ŴŐŘĻĎ!';
		$this->assertEqual($result, $expected);
	}

	function testCaseInsensitiveStringPosition() {
		$string = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$find   = 'f';
		$result = String::stripos($string, $find);
		$expected = 5;
		$this->assertEqual($result, $expected);

		$string = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞ';
		$find   = 'å';
		$result = String::stripos($string, $find);
		$expected = 5;
		$this->assertEqual($result, $expected);

		$string = 'ĀĂĄĆĈĊČĎĐĒĔĖĘĚĜĞĠĢĤĦĨĪĬĮĲĴĶĹĻĽĿŁŃŅŇŊŌŎŐŒŔŖŘŚŜŞŠŢŤŦŨŪŬŮŰŲŴŶŹŻŽ';
		$find   = 'ċ';
		$result = String::stripos($string, $find);
		$expected = 5;
		$this->assertEqual($result, $expected);
	}
}
?>