<?php
/**
 * Case Folding Properties.
 *
 * Provides case mapping of Unicode characters for code points U+0400 through U+04FF
 *
 * @see http://www.unicode.org/Public/UNIDATA/UCD.html
 * @see http://www.unicode.org/Public/UNIDATA/CaseFolding.txt
 * @see http://www.unicode.org/reports/tr21/tr21-5.html
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Config.unicode.casefolding
 * @since         CakePHP(tm) v 1.2.0.5691
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * The upper field is the decimal value of the upper case character
 *
 * The lower filed is an array of the decimal values that form the lower case version of a character.
 *
 *	The status field is:
 * C: common case folding, common mappings shared by both simple and full mappings.
 * F: full case folding, mappings that cause strings to grow in length. Multiple characters are separated by spaces.
 * S: simple case folding, mappings to single characters where different from F.
 * T: special case for uppercase I and dotted uppercase I
 *   - For non-Turkic languages, this mapping is normally not used.
 *   - For Turkic languages (tr, az), this mapping can be used instead of the normal mapping for these characters.
 *     Note that the Turkic mappings do not maintain canonical equivalence without additional processing.
 *     See the discussions of case mapping in the Unicode Standard for more information.
 */
$config['0400_04ff'][] = array('upper' => 1024, 'status' => 'C', 'lower' => array(1104)); /* CYRILLIC CAPITAL LETTER IE WITH GRAVE */
$config['0400_04ff'][] = array('upper' => 1025, 'status' => 'C', 'lower' => array(1105)); /* CYRILLIC CAPITAL LETTER IO */
$config['0400_04ff'][] = array('upper' => 1026, 'status' => 'C', 'lower' => array(1106)); /* CYRILLIC CAPITAL LETTER DJE */
$config['0400_04ff'][] = array('upper' => 1027, 'status' => 'C', 'lower' => array(1107)); /* CYRILLIC CAPITAL LETTER GJE */
$config['0400_04ff'][] = array('upper' => 1028, 'status' => 'C', 'lower' => array(1108)); /* CYRILLIC CAPITAL LETTER UKRAINIAN IE */
$config['0400_04ff'][] = array('upper' => 1029, 'status' => 'C', 'lower' => array(1109)); /* CYRILLIC CAPITAL LETTER DZE */
$config['0400_04ff'][] = array('upper' => 1030, 'status' => 'C', 'lower' => array(1110)); /* CYRILLIC CAPITAL LETTER BYELORUSSIAN-UKRAINIAN I */
$config['0400_04ff'][] = array('upper' => 1031, 'status' => 'C', 'lower' => array(1111)); /* CYRILLIC CAPITAL LETTER YI */
$config['0400_04ff'][] = array('upper' => 1032, 'status' => 'C', 'lower' => array(1112)); /* CYRILLIC CAPITAL LETTER JE */
$config['0400_04ff'][] = array('upper' => 1033, 'status' => 'C', 'lower' => array(1113)); /* CYRILLIC CAPITAL LETTER LJE */
$config['0400_04ff'][] = array('upper' => 1034, 'status' => 'C', 'lower' => array(1114)); /* CYRILLIC CAPITAL LETTER NJE */
$config['0400_04ff'][] = array('upper' => 1035, 'status' => 'C', 'lower' => array(1115)); /* CYRILLIC CAPITAL LETTER TSHE */
$config['0400_04ff'][] = array('upper' => 1036, 'status' => 'C', 'lower' => array(1116)); /* CYRILLIC CAPITAL LETTER KJE */
$config['0400_04ff'][] = array('upper' => 1037, 'status' => 'C', 'lower' => array(1117)); /* CYRILLIC CAPITAL LETTER I WITH GRAVE */
$config['0400_04ff'][] = array('upper' => 1038, 'status' => 'C', 'lower' => array(1118)); /* CYRILLIC CAPITAL LETTER SHORT U */
$config['0400_04ff'][] = array('upper' => 1039, 'status' => 'C', 'lower' => array(1119)); /* CYRILLIC CAPITAL LETTER DZHE */
$config['0400_04ff'][] = array('upper' => 1040, 'status' => 'C', 'lower' => array(1072)); /* CYRILLIC CAPITAL LETTER A */
$config['0400_04ff'][] = array('upper' => 1041, 'status' => 'C', 'lower' => array(1073)); /* CYRILLIC CAPITAL LETTER BE */
$config['0400_04ff'][] = array('upper' => 1042, 'status' => 'C', 'lower' => array(1074)); /* CYRILLIC CAPITAL LETTER VE */
$config['0400_04ff'][] = array('upper' => 1043, 'status' => 'C', 'lower' => array(1075)); /* CYRILLIC CAPITAL LETTER GHE */
$config['0400_04ff'][] = array('upper' => 1044, 'status' => 'C', 'lower' => array(1076)); /* CYRILLIC CAPITAL LETTER DE */
$config['0400_04ff'][] = array('upper' => 1045, 'status' => 'C', 'lower' => array(1077)); /* CYRILLIC CAPITAL LETTER IE */
$config['0400_04ff'][] = array('upper' => 1046, 'status' => 'C', 'lower' => array(1078)); /* CYRILLIC CAPITAL LETTER ZHE */
$config['0400_04ff'][] = array('upper' => 1047, 'status' => 'C', 'lower' => array(1079)); /* CYRILLIC CAPITAL LETTER ZE */
$config['0400_04ff'][] = array('upper' => 1048, 'status' => 'C', 'lower' => array(1080)); /* CYRILLIC CAPITAL LETTER I */
$config['0400_04ff'][] = array('upper' => 1049, 'status' => 'C', 'lower' => array(1081)); /* CYRILLIC CAPITAL LETTER SHORT I */
$config['0400_04ff'][] = array('upper' => 1050, 'status' => 'C', 'lower' => array(1082)); /* CYRILLIC CAPITAL LETTER KA */
$config['0400_04ff'][] = array('upper' => 1051, 'status' => 'C', 'lower' => array(1083)); /* CYRILLIC CAPITAL LETTER EL */
$config['0400_04ff'][] = array('upper' => 1052, 'status' => 'C', 'lower' => array(1084)); /* CYRILLIC CAPITAL LETTER EM */
$config['0400_04ff'][] = array('upper' => 1053, 'status' => 'C', 'lower' => array(1085)); /* CYRILLIC CAPITAL LETTER EN */
$config['0400_04ff'][] = array('upper' => 1054, 'status' => 'C', 'lower' => array(1086)); /* CYRILLIC CAPITAL LETTER O */
$config['0400_04ff'][] = array('upper' => 1055, 'status' => 'C', 'lower' => array(1087)); /* CYRILLIC CAPITAL LETTER PE */
$config['0400_04ff'][] = array('upper' => 1056, 'status' => 'C', 'lower' => array(1088)); /* CYRILLIC CAPITAL LETTER ER */
$config['0400_04ff'][] = array('upper' => 1057, 'status' => 'C', 'lower' => array(1089)); /* CYRILLIC CAPITAL LETTER ES */
$config['0400_04ff'][] = array('upper' => 1058, 'status' => 'C', 'lower' => array(1090)); /* CYRILLIC CAPITAL LETTER TE */
$config['0400_04ff'][] = array('upper' => 1059, 'status' => 'C', 'lower' => array(1091)); /* CYRILLIC CAPITAL LETTER U */
$config['0400_04ff'][] = array('upper' => 1060, 'status' => 'C', 'lower' => array(1092)); /* CYRILLIC CAPITAL LETTER EF */
$config['0400_04ff'][] = array('upper' => 1061, 'status' => 'C', 'lower' => array(1093)); /* CYRILLIC CAPITAL LETTER HA */
$config['0400_04ff'][] = array('upper' => 1062, 'status' => 'C', 'lower' => array(1094)); /* CYRILLIC CAPITAL LETTER TSE */
$config['0400_04ff'][] = array('upper' => 1063, 'status' => 'C', 'lower' => array(1095)); /* CYRILLIC CAPITAL LETTER CHE */
$config['0400_04ff'][] = array('upper' => 1064, 'status' => 'C', 'lower' => array(1096)); /* CYRILLIC CAPITAL LETTER SHA */
$config['0400_04ff'][] = array('upper' => 1065, 'status' => 'C', 'lower' => array(1097)); /* CYRILLIC CAPITAL LETTER SHCHA */
$config['0400_04ff'][] = array('upper' => 1066, 'status' => 'C', 'lower' => array(1098)); /* CYRILLIC CAPITAL LETTER HARD SIGN */
$config['0400_04ff'][] = array('upper' => 1067, 'status' => 'C', 'lower' => array(1099)); /* CYRILLIC CAPITAL LETTER YERU */
$config['0400_04ff'][] = array('upper' => 1068, 'status' => 'C', 'lower' => array(1100)); /* CYRILLIC CAPITAL LETTER SOFT SIGN */
$config['0400_04ff'][] = array('upper' => 1069, 'status' => 'C', 'lower' => array(1101)); /* CYRILLIC CAPITAL LETTER E */
$config['0400_04ff'][] = array('upper' => 1070, 'status' => 'C', 'lower' => array(1102)); /* CYRILLIC CAPITAL LETTER YU */
$config['0400_04ff'][] = array('upper' => 1071, 'status' => 'C', 'lower' => array(1103)); /* CYRILLIC CAPITAL LETTER YA */
$config['0400_04ff'][] = array('upper' => 1120, 'status' => 'C', 'lower' => array(1121)); /* CYRILLIC CAPITAL LETTER OMEGA */
$config['0400_04ff'][] = array('upper' => 1122, 'status' => 'C', 'lower' => array(1123)); /* CYRILLIC CAPITAL LETTER YAT */
$config['0400_04ff'][] = array('upper' => 1124, 'status' => 'C', 'lower' => array(1125)); /* CYRILLIC CAPITAL LETTER IOTIFIED E */
$config['0400_04ff'][] = array('upper' => 1126, 'status' => 'C', 'lower' => array(1127)); /* CYRILLIC CAPITAL LETTER LITTLE YUS */
$config['0400_04ff'][] = array('upper' => 1128, 'status' => 'C', 'lower' => array(1129)); /* CYRILLIC CAPITAL LETTER IOTIFIED LITTLE YUS */
$config['0400_04ff'][] = array('upper' => 1130, 'status' => 'C', 'lower' => array(1131)); /* CYRILLIC CAPITAL LETTER BIG YUS */
$config['0400_04ff'][] = array('upper' => 1132, 'status' => 'C', 'lower' => array(1133)); /* CYRILLIC CAPITAL LETTER IOTIFIED BIG YUS */
$config['0400_04ff'][] = array('upper' => 1134, 'status' => 'C', 'lower' => array(1135)); /* CYRILLIC CAPITAL LETTER KSI */
$config['0400_04ff'][] = array('upper' => 1136, 'status' => 'C', 'lower' => array(1137)); /* CYRILLIC CAPITAL LETTER PSI */
$config['0400_04ff'][] = array('upper' => 1138, 'status' => 'C', 'lower' => array(1139)); /* CYRILLIC CAPITAL LETTER FITA */
$config['0400_04ff'][] = array('upper' => 1140, 'status' => 'C', 'lower' => array(1141)); /* CYRILLIC CAPITAL LETTER IZHITSA */
$config['0400_04ff'][] = array('upper' => 1142, 'status' => 'C', 'lower' => array(1143)); /* CYRILLIC CAPITAL LETTER IZHITSA WITH DOUBLE GRAVE ACCENT */
$config['0400_04ff'][] = array('upper' => 1144, 'status' => 'C', 'lower' => array(1145)); /* CYRILLIC CAPITAL LETTER UK */
$config['0400_04ff'][] = array('upper' => 1146, 'status' => 'C', 'lower' => array(1147)); /* CYRILLIC CAPITAL LETTER ROUND OMEGA */
$config['0400_04ff'][] = array('upper' => 1148, 'status' => 'C', 'lower' => array(1149)); /* CYRILLIC CAPITAL LETTER OMEGA WITH TITLO */
$config['0400_04ff'][] = array('upper' => 1150, 'status' => 'C', 'lower' => array(1151)); /* CYRILLIC CAPITAL LETTER OT */
$config['0400_04ff'][] = array('upper' => 1152, 'status' => 'C', 'lower' => array(1153)); /* CYRILLIC CAPITAL LETTER KOPPA */
$config['0400_04ff'][] = array('upper' => 1162, 'status' => 'C', 'lower' => array(1163)); /* CYRILLIC CAPITAL LETTER SHORT I WITH TAIL */
$config['0400_04ff'][] = array('upper' => 1164, 'status' => 'C', 'lower' => array(1165)); /* CYRILLIC CAPITAL LETTER SEMISOFT SIGN */
$config['0400_04ff'][] = array('upper' => 1166, 'status' => 'C', 'lower' => array(1167)); /* CYRILLIC CAPITAL LETTER ER WITH TICK */
$config['0400_04ff'][] = array('upper' => 1168, 'status' => 'C', 'lower' => array(1169)); /* CYRILLIC CAPITAL LETTER GHE WITH UPTURN */
$config['0400_04ff'][] = array('upper' => 1170, 'status' => 'C', 'lower' => array(1171)); /* CYRILLIC CAPITAL LETTER GHE WITH STROKE */
$config['0400_04ff'][] = array('upper' => 1172, 'status' => 'C', 'lower' => array(1173)); /* CYRILLIC CAPITAL LETTER GHE WITH MIDDLE HOOK */
$config['0400_04ff'][] = array('upper' => 1174, 'status' => 'C', 'lower' => array(1175)); /* CYRILLIC CAPITAL LETTER ZHE WITH DESCENDER */
$config['0400_04ff'][] = array('upper' => 1176, 'status' => 'C', 'lower' => array(1177)); /* CYRILLIC CAPITAL LETTER ZE WITH DESCENDER */
$config['0400_04ff'][] = array('upper' => 1178, 'status' => 'C', 'lower' => array(1179)); /* CYRILLIC CAPITAL LETTER KA WITH DESCENDER */
$config['0400_04ff'][] = array('upper' => 1180, 'status' => 'C', 'lower' => array(1181)); /* CYRILLIC CAPITAL LETTER KA WITH VERTICAL STROKE */
$config['0400_04ff'][] = array('upper' => 1182, 'status' => 'C', 'lower' => array(1183)); /* CYRILLIC CAPITAL LETTER KA WITH STROKE */
$config['0400_04ff'][] = array('upper' => 1184, 'status' => 'C', 'lower' => array(1185)); /* CYRILLIC CAPITAL LETTER BASHKIR KA */
$config['0400_04ff'][] = array('upper' => 1186, 'status' => 'C', 'lower' => array(1187)); /* CYRILLIC CAPITAL LETTER EN WITH DESCENDER */
$config['0400_04ff'][] = array('upper' => 1188, 'status' => 'C', 'lower' => array(1189)); /* CYRILLIC CAPITAL LIGATURE EN GHE */
$config['0400_04ff'][] = array('upper' => 1190, 'status' => 'C', 'lower' => array(1191)); /* CYRILLIC CAPITAL LETTER PE WITH MIDDLE HOOK */
$config['0400_04ff'][] = array('upper' => 1192, 'status' => 'C', 'lower' => array(1193)); /* CYRILLIC CAPITAL LETTER ABKHASIAN HA */
$config['0400_04ff'][] = array('upper' => 1194, 'status' => 'C', 'lower' => array(1195)); /* CYRILLIC CAPITAL LETTER ES WITH DESCENDER */
$config['0400_04ff'][] = array('upper' => 1196, 'status' => 'C', 'lower' => array(1197)); /* CYRILLIC CAPITAL LETTER TE WITH DESCENDER */
$config['0400_04ff'][] = array('upper' => 1198, 'status' => 'C', 'lower' => array(1199)); /* CYRILLIC CAPITAL LETTER STRAIGHT U */
$config['0400_04ff'][] = array('upper' => 1200, 'status' => 'C', 'lower' => array(1201)); /* CYRILLIC CAPITAL LETTER STRAIGHT U WITH STROKE */
$config['0400_04ff'][] = array('upper' => 1202, 'status' => 'C', 'lower' => array(1203)); /* CYRILLIC CAPITAL LETTER HA WITH DESCENDER */
$config['0400_04ff'][] = array('upper' => 1204, 'status' => 'C', 'lower' => array(1205)); /* CYRILLIC CAPITAL LIGATURE TE TSE */
$config['0400_04ff'][] = array('upper' => 1206, 'status' => 'C', 'lower' => array(1207)); /* CYRILLIC CAPITAL LETTER CHE WITH DESCENDER */
$config['0400_04ff'][] = array('upper' => 1208, 'status' => 'C', 'lower' => array(1209)); /* CYRILLIC CAPITAL LETTER CHE WITH VERTICAL STROKE */
$config['0400_04ff'][] = array('upper' => 1210, 'status' => 'C', 'lower' => array(1211)); /* CYRILLIC CAPITAL LETTER SHHA */
$config['0400_04ff'][] = array('upper' => 1212, 'status' => 'C', 'lower' => array(1213)); /* CYRILLIC CAPITAL LETTER ABKHASIAN CHE */
$config['0400_04ff'][] = array('upper' => 1214, 'status' => 'C', 'lower' => array(1215)); /* CYRILLIC CAPITAL LETTER ABKHASIAN CHE WITH DESCENDER */
$config['0400_04ff'][] = array('upper' => 1216, 'status' => 'C', 'lower' => array(1231)); /* CYRILLIC LETTER PALOCHKA */
$config['0400_04ff'][] = array('upper' => 1217, 'status' => 'C', 'lower' => array(1218)); /* CYRILLIC CAPITAL LETTER ZHE WITH BREVE */
$config['0400_04ff'][] = array('upper' => 1219, 'status' => 'C', 'lower' => array(1220)); /* CYRILLIC CAPITAL LETTER KA WITH HOOK */
$config['0400_04ff'][] = array('upper' => 1221, 'status' => 'C', 'lower' => array(1222)); /* CYRILLIC CAPITAL LETTER EL WITH TAIL */
$config['0400_04ff'][] = array('upper' => 1223, 'status' => 'C', 'lower' => array(1224)); /* CYRILLIC CAPITAL LETTER EN WITH HOOK */
$config['0400_04ff'][] = array('upper' => 1225, 'status' => 'C', 'lower' => array(1226)); /* CYRILLIC CAPITAL LETTER EN WITH TAIL */
$config['0400_04ff'][] = array('upper' => 1227, 'status' => 'C', 'lower' => array(1228)); /* CYRILLIC CAPITAL LETTER KHAKASSIAN CHE */
$config['0400_04ff'][] = array('upper' => 1229, 'status' => 'C', 'lower' => array(1230)); /* CYRILLIC CAPITAL LETTER EM WITH TAIL */
$config['0400_04ff'][] = array('upper' => 1232, 'status' => 'C', 'lower' => array(1233)); /* CYRILLIC CAPITAL LETTER A WITH BREVE */
$config['0400_04ff'][] = array('upper' => 1234, 'status' => 'C', 'lower' => array(1235)); /* CYRILLIC CAPITAL LETTER A WITH DIAERESIS */
$config['0400_04ff'][] = array('upper' => 1236, 'status' => 'C', 'lower' => array(1237)); /* CYRILLIC CAPITAL LIGATURE A IE */
$config['0400_04ff'][] = array('upper' => 1238, 'status' => 'C', 'lower' => array(1239)); /* CYRILLIC CAPITAL LETTER IE WITH BREVE */
$config['0400_04ff'][] = array('upper' => 1240, 'status' => 'C', 'lower' => array(1241)); /* CYRILLIC CAPITAL LETTER SCHWA */
$config['0400_04ff'][] = array('upper' => 1242, 'status' => 'C', 'lower' => array(1243)); /* CYRILLIC CAPITAL LETTER SCHWA WITH DIAERESIS */
$config['0400_04ff'][] = array('upper' => 1244, 'status' => 'C', 'lower' => array(1245)); /* CYRILLIC CAPITAL LETTER ZHE WITH DIAERESIS */
$config['0400_04ff'][] = array('upper' => 1246, 'status' => 'C', 'lower' => array(1247)); /* CYRILLIC CAPITAL LETTER ZE WITH DIAERESIS */
$config['0400_04ff'][] = array('upper' => 1248, 'status' => 'C', 'lower' => array(1249)); /* CYRILLIC CAPITAL LETTER ABKHASIAN DZE */
$config['0400_04ff'][] = array('upper' => 1250, 'status' => 'C', 'lower' => array(1251)); /* CYRILLIC CAPITAL LETTER I WITH MACRON */
$config['0400_04ff'][] = array('upper' => 1252, 'status' => 'C', 'lower' => array(1253)); /* CYRILLIC CAPITAL LETTER I WITH DIAERESIS */
$config['0400_04ff'][] = array('upper' => 1254, 'status' => 'C', 'lower' => array(1255)); /* CYRILLIC CAPITAL LETTER O WITH DIAERESIS */
$config['0400_04ff'][] = array('upper' => 1256, 'status' => 'C', 'lower' => array(1257)); /* CYRILLIC CAPITAL LETTER BARRED O */
$config['0400_04ff'][] = array('upper' => 1258, 'status' => 'C', 'lower' => array(1259)); /* CYRILLIC CAPITAL LETTER BARRED O WITH DIAERESIS */
$config['0400_04ff'][] = array('upper' => 1260, 'status' => 'C', 'lower' => array(1261)); /* CYRILLIC CAPITAL LETTER E WITH DIAERESIS */
$config['0400_04ff'][] = array('upper' => 1262, 'status' => 'C', 'lower' => array(1263)); /* CYRILLIC CAPITAL LETTER U WITH MACRON */
$config['0400_04ff'][] = array('upper' => 1264, 'status' => 'C', 'lower' => array(1265)); /* CYRILLIC CAPITAL LETTER U WITH DIAERESIS */
$config['0400_04ff'][] = array('upper' => 1266, 'status' => 'C', 'lower' => array(1267)); /* CYRILLIC CAPITAL LETTER U WITH DOUBLE ACUTE */
$config['0400_04ff'][] = array('upper' => 1268, 'status' => 'C', 'lower' => array(1269)); /* CYRILLIC CAPITAL LETTER CHE WITH DIAERESIS */
$config['0400_04ff'][] = array('upper' => 1270, 'status' => 'C', 'lower' => array(1271)); /* CYRILLIC CAPITAL LETTER GHE WITH DESCENDER */
$config['0400_04ff'][] = array('upper' => 1272, 'status' => 'C', 'lower' => array(1273)); /* CYRILLIC CAPITAL LETTER YERU WITH DIAERESIS */
$config['0400_04ff'][] = array('upper' => 1274, 'status' => 'C', 'lower' => array(1275)); /* CYRILLIC CAPITAL LETTER GHE WITH STROKE AND HOOK */
$config['0400_04ff'][] = array('upper' => 1276, 'status' => 'C', 'lower' => array(1277)); /* CYRILLIC CAPITAL LETTER HA WITH HOOK */
$config['0400_04ff'][] = array('upper' => 1278, 'status' => 'C', 'lower' => array(1279)); /* CYRILLIC CAPITAL LETTER HA WITH STROKE */
