<?php



//==============================================================
$lorem = "<p>Nulla felis erat, imperdiet eu, ullamcorper non, nonummy quis, elit. Suspendisse potenti. Ut a eros at ligula vehicula pretium. Maecenas feugiat pede vel risus. Nulla et lectus. Fusce eleifend neque sit amet erat. Integer consectetuer nulla non orci. Morbi feugiat pulvinar dolor. Cras odio. Donec mattis, nisi id euismod auctor, neque metus pellentesque risus, at eleifend lacus sapien et risus. Phasellus metus. Phasellus feugiat, lectus ac aliquam molestie, leo lacus tincidunt turpis, vel aliquam quam odio et sapien. Mauris ante pede, auctor ac, suscipit quis, malesuada sed, nulla. Integer sit amet odio sit amet lectus luctus euismod. Donec et nulla. Sed quis orci. </p><p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Proin vel sem at odio varius pretium. Maecenas sed orci. Maecenas varius. Ut magna ipsum, tempus in, condimentum at, rutrum et, nisl. Vestibulum interdum luctus sapien. Quisque viverra. Etiam id libero at magna pellentesque aliquet. Nulla sit amet ipsum id enim tempus dictum. Maecenas consectetuer eros quis massa. Mauris semper velit vehicula purus. Duis lacus. Aenean pretium consectetuer mauris. Ut purus sem, consequat ut, fermentum sit amet, ornare sit amet, ipsum. Donec non nunc. Maecenas fringilla. Curabitur libero. In dui massa, malesuada sit amet, hendrerit vitae, viverra nec, tortor. Donec varius. Ut ut dolor et tellus adipiscing adipiscing. </p><p>Proin aliquet lorem id felis. Curabitur vel libero at mauris nonummy tincidunt. Donec imperdiet. Vestibulum sem sem, lacinia vel, molestie et, laoreet eget, urna. Curabitur viverra faucibus pede. Morbi lobortis. Donec dapibus. Donec tempus. Ut arcu enim, rhoncus ac, venenatis eu, porttitor mollis, dui. Sed vitae risus. In elementum sem placerat dui. Nam tristique eros in nisl. Nulla cursus sapien non quam porta porttitor. Quisque dictum ipsum ornare tortor. Fusce ornare tempus enim. </p><p>Maecenas arcu justo, malesuada eu, dapibus ac, adipiscing vitae, turpis. Fusce mollis. Aliquam egestas. In purus dolor, facilisis at, fermentum nec, molestie et, metus. Vestibulum feugiat, orci at imperdiet tincidunt, mauris erat facilisis urna, sagittis ultricies dui nisl et lectus. Sed lacinia, lectus vitae dictum sodales, elit ipsum ultrices orci, non euismod arcu diam non metus. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. In suscipit turpis vitae odio. Integer convallis dui at metus. Fusce magna. Sed sed lectus vitae enim tempor cursus. Cras eu erat vel libero sodales congue. Sed erat est, interdum nec, elementum eleifend, pretium at, nibh. Praesent massa diam, adipiscing id, mollis sed, posuere et, urna. Quisque ut leo. Aliquam interdum hendrerit tortor. Vestibulum elit. Vestibulum et arcu at diam mattis commodo. Nam ipsum sem, ultricies at, rutrum sit amet, posuere nec, velit. Sed molestie mollis dui. </p>";
//==============================================================
//==============================================================
//==============================================================
// Set Header and Footer for ToC
$h = array (
  'odd' => 
  array (
    'R' => 
    array (
      'content' => 'Odd Header for ToC',
      'font-size' => 8,
      'font-style' => 'B',
      'font-family' => 'DejaVuSansCondensed',
    ),
    'line' => 1,
  ),
  'even' => 
  array (
    'L' => 
    array (
      'content' => 'Even Header for ToC',
      'font-size' => 8,
      'font-style' => 'B',
      'font-family' => 'DejaVuSansCondensed',
    ),
    'line' => 1,
  ),
);

$f = array (
  'odd' => 
  array (
    'L' => 
    array (
      'content' => '{DATE Y-m-d}',
      'font-size' => 8,
      'font-style' => 'BI',
      'font-family' => 'DejaVuSansCondensed',
    ),
    'C' => 
    array (
      'content' => 'Odd Footer for ToC',
      'font-size' => 8,
      'font-style' => '',
      'font-family' => '',
    ),
    'R' => 
    array (
      'content' => 'My Handbook',
      'font-size' => 8,
      'font-style' => 'BI',
      'font-family' => 'DejaVuSansCondensed',
    ),
    'line' => 1,
  ),
  'even' => 
  array (
    'L' => 
    array (
      'content' => 'My Handbook',
      'font-size' => 8,
      'font-style' => 'BI',
      'font-family' => 'DejaVuSansCondensed',
    ),
    'C' => 
    array (
      'content' => 'Even Footer for ToC',
      'font-size' => 8,
      'font-style' => '',
      'font-family' => '',
    ),
    'R' => 
    array (
      'content' => '{DATE Y-m-d}',
      'font-size' => 8,
      'font-style' => 'BI',
      'font-family' => 'DejaVuSansCondensed',
    ),
    'line' => 0,
  ),
);

//==============================================================
include("../mpdf.php");

$mpdf=new mPDF('en-GB-x','A4','','',32,25,27,25,16,13); 

$mpdf->mirrorMargins = 1;

$mpdf->SetDisplayMode('fullpage','two');

// LOAD a stylesheet
$stylesheet = file_get_contents('mpdfstyleA4.css');
$mpdf->WriteHTML($stylesheet,1);	// The parameter 1 tells that this is css/style only and no body/html/text

$mpdf->WriteHTML('<h1>mPDF</h1><h2>Table of Contents & Bookmarks</h2>',2);



// TOC TABLE OF CONTENTS and INDEX+++++++++++++++++++++++++++++++++++++++++++++
//$mpdf->WriteHTML('<pagebreak type="E" />');
//$mpdf->WriteHTML('<TOC font="" font-size="" indent="5" resetpagenum="1" pagenumstyle="A", suppress="off" />');


$mpdf->TOCpagebreakByArray(array(
	'tocfont' => '', 
	'tocfontsize' => '', 
	'tocindent' => '5', 
	'TOCusePaging' => true, 
	'TOCuseLinking' => '', 
	'toc_orientation' => '', 
	'toc_mgl' => '45',
	'toc_mgr' => '35',
	'toc_mgt' => '',
	'toc_mgb' => '',
	'toc_mgh' => '',
	'toc_mgf' => '',
	'toc_ohname' => '',
	'toc_ehname' => '',
	'toc_ofname' => '',
	'toc_efname' => '',
	'toc_ohvalue' => 0,
	'toc_ehvalue' => 0,
	'toc_ofvalue' => -1,
	'toc_efvalue' => -1, 
	'toc_preHTML' => '<h2>Contents</h2>', 
	'toc_postHTML' => '', 
	'toc_bookmarkText' => 'Content list', 
	'resetpagenum' => '1', 
	'pagenumstyle' => 'A', 
	'suppress' => 'off', 
	'orientation' => '', 
	'mgl' => '',
	'mgr' => '',
	'mgt' => '',
	'mgb' => '',
	'mgh' => '',
	'mgf' => '',
	'ohname' => '',
	'ehname' => '',
	'ofname' => '',
	'efname' => '',
	'ohvalue' => 0,
	'ehvalue' => 0,
	'ofvalue' => 0,
	'efvalue' => 0, 
	'toc_id' => 0, 
	'pagesel' => '', 
	'toc_pagesel' => '', 
	'sheetsize' => '', 
	'toc_sheetsize' => ''
	));


$mpdf->setHTMLFooter('<div align="center"><b>{PAGENO} / {nbpg}</b></div>') ;
$mpdf->setHTMLFooter('<div align="center"><b><i>{PAGENO} / {nbpg}</i></b></div>','E') ;


//==============================================================
for ($j = 1; $j<7; $j++) { 
   if ($j==2)	$mpdf->WriteHTML('<pagebreak resetpagenum="0" pagenumstyle="a" />',2);
   if ($j==3)	$mpdf->WriteHTML('<pagebreak resetpagenum="1" pagenumstyle="I" />',2);
   if ($j==4)	$mpdf->WriteHTML('<pagebreak resetpagenum="0" pagenumstyle="i" />',2);
   if ($j==5)	$mpdf->WriteHTML('<pagebreak resetpagenum="0" pagenumstyle="1" />',2);
   if ($j==6)	$mpdf->WriteHTML('<pagebreak resetpagenum="1" pagenumstyle="A" type="NEXT-ODD" /><div style="color:#AA0000">ODD</div>',2);
   for ($x = 1; $x<7; $x++) {
	$mpdf->WriteHTML('<h4>Section '.$j.'.'.$x.'<bookmark content="Section '.$j.'.'.$x.'" level="0" /><tocentry content="Section '.$j.'.'.$x.'" level="0" /></h4>',2);
	$html = '';
	// Split $lorem into words
	$words = preg_split('/([\s,\.]+)/',$lorem,-1,PREG_SPLIT_DELIM_CAPTURE);
	foreach($words as $i => $e) {
	   if($i%2==0) {
		$y =  rand(1,10); 	// every tenth word
		if (preg_match('/^[a-zA-Z]{4,99}$/',$e) && ($y > 8)) {
			// If it is just a word use it as an index entry
			$content = ucfirst(trim($e));
			$html .= '<indexentry content="'.$content.'" />';
			$html .= '<i>'.$e . '</i>';
		}
		else { $html .= $e; }
	   }
	   else { $html .= $e; }
	}
	$mpdf->WriteHTML($html);
   }
}
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
// Index - This should be inserted where it is intended to appear in the document
	$mpdf->AddPage('','E');	
	$mpdf->AddPage();	
	$mpdf->WriteHTML('<h2>Index</h2>',2);
	$mpdf->CreateIndex(2, '', '', 5, 1, 15, 5, 'trebuchet','sans-serif',true);


$mpdf->Output();
exit;
//==============================================================
//==============================================================


?>