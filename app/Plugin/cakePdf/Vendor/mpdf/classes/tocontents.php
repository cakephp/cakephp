<?php

class tocontents {

var $mpdf = null;
var $_toc;
var $TOCmark;
var $TOCfont;
var $TOCfontsize;
var $TOCindent;
var $TOCheader;
var $TOCfooter;
var $TOCpreHTML;
var $TOCpostHTML;
var $TOCbookmarkText;
var $TOCusePaging;
var $TOCuseLinking;
var $TOCorientation;
var $TOC_margin_left;
var $TOC_margin_right;
var $TOC_margin_top;
var $TOC_margin_bottom;
var $TOC_margin_header;
var $TOC_margin_footer;
var $TOC_odd_header_name;
var $TOC_even_header_name;
var $TOC_odd_footer_name;
var $TOC_even_footer_name;
var $TOC_odd_header_value;
var $TOC_even_header_value;
var $TOC_odd_footer_value;
var $TOC_even_footer_value;
var $TOC_page_selector;
var $m_TOC; 

function tocontents(&$mpdf) {
	$this->mpdf = $mpdf;
	$this->_toc=array();
	$this->TOCmark = 0;
	$this->m_TOC=array();
}

function TOCpagebreak($tocfont='', $tocfontsize='', $tocindent='', $TOCusePaging=true, $TOCuseLinking='', $toc_orientation='', $toc_mgl='',$toc_mgr='',$toc_mgt='',$toc_mgb='',$toc_mgh='',$toc_mgf='',$toc_ohname='',$toc_ehname='',$toc_ofname='',$toc_efname='',$toc_ohvalue=0,$toc_ehvalue=0,$toc_ofvalue=0, $toc_efvalue=0, $toc_preHTML='', $toc_postHTML='', $toc_bookmarkText='', $resetpagenum='', $pagenumstyle='', $suppress='', $orientation='', $mgl='',$mgr='',$mgt='',$mgb='',$mgh='',$mgf='',$ohname='',$ehname='',$ofname='',$efname='',$ohvalue=0,$ehvalue=0,$ofvalue=0,$efvalue=0, $toc_id=0, $pagesel='', $toc_pagesel='', $sheetsize='', $toc_sheetsize='') {
		if (strtoupper($toc_id)=='ALL') { $toc_id = '_mpdf_all'; }
		else if (!$toc_id) { $toc_id = 0; }
		else { $toc_id = strtolower($toc_id); }

		if (!$tocfont) { $tocfont = $this->mpdf->default_font; }
		if (!$tocfontsize) { $tocfontsize = $this->mpdf->default_font_size; }
		if (!$tocindent && $tocindent !== 0) { $tocindent = 5; }
		if ($TOCusePaging === false || strtolower($TOCusePaging) == "off" || $TOCusePaging === 0 || $TOCusePaging === "0" || $TOCusePaging === "") { $TOCusePaging = false; }
		else { $TOCusePaging = true; }
		if (!$TOCuseLinking) { $TOCuseLinking = false; }
		if ($toc_id) {
			$this->m_TOC[$toc_id]['TOCmark'] = $this->mpdf->page; 
			$this->m_TOC[$toc_id]['TOCfont'] = $tocfont;
			$this->m_TOC[$toc_id]['TOCfontsize'] = $tocfontsize;
			$this->m_TOC[$toc_id]['TOCindent'] = $tocindent;
			$this->m_TOC[$toc_id]['TOCorientation'] = $toc_orientation;
			$this->m_TOC[$toc_id]['TOCuseLinking'] = $TOCuseLinking;
			$this->m_TOC[$toc_id]['TOCusePaging'] = $TOCusePaging;

			if ($toc_preHTML) { $this->m_TOC[$toc_id]['TOCpreHTML'] = $toc_preHTML; }
			if ($toc_postHTML) { $this->m_TOC[$toc_id]['TOCpostHTML'] = $toc_postHTML; }
			if ($toc_bookmarkText) { $this->m_TOC[$toc_id]['TOCbookmarkText'] = $toc_bookmarkText; }

			$this->m_TOC[$toc_id]['TOC_margin_left'] = $toc_mgl;
			$this->m_TOC[$toc_id]['TOC_margin_right'] = $toc_mgr;
			$this->m_TOC[$toc_id]['TOC_margin_top'] = $toc_mgt;
			$this->m_TOC[$toc_id]['TOC_margin_bottom'] = $toc_mgb;
			$this->m_TOC[$toc_id]['TOC_margin_header'] = $toc_mgh;
			$this->m_TOC[$toc_id]['TOC_margin_footer'] = $toc_mgf;
			$this->m_TOC[$toc_id]['TOC_odd_header_name'] = $toc_ohname;
			$this->m_TOC[$toc_id]['TOC_even_header_name'] = $toc_ehname;
			$this->m_TOC[$toc_id]['TOC_odd_footer_name'] = $toc_ofname;
			$this->m_TOC[$toc_id]['TOC_even_footer_name'] = $toc_efname;
			$this->m_TOC[$toc_id]['TOC_odd_header_value'] = $toc_ohvalue;
			$this->m_TOC[$toc_id]['TOC_even_header_value'] = $toc_ehvalue;
			$this->m_TOC[$toc_id]['TOC_odd_footer_value'] = $toc_ofvalue;
			$this->m_TOC[$toc_id]['TOC_even_footer_value'] = $toc_efvalue;
			$this->m_TOC[$toc_id]['TOC_page_selector'] = $toc_pagesel;
			$this->m_TOC[$toc_id]['TOCsheetsize'] = $toc_sheetsize;
		}
		else {
			$this->TOCmark = $this->mpdf->page; 
			$this->TOCfont = $tocfont;
			$this->TOCfontsize = $tocfontsize;
			$this->TOCindent = $tocindent;
			$this->TOCorientation = $toc_orientation;
			$this->TOCuseLinking = $TOCuseLinking;
			$this->TOCusePaging = $TOCusePaging;

			if ($toc_preHTML) { $this->TOCpreHTML = $toc_preHTML; }
			if ($toc_postHTML) { $this->TOCpostHTML = $toc_postHTML; }
			if ($toc_bookmarkText) { $this->TOCbookmarkText = $toc_bookmarkText; }

			$this->TOC_margin_left = $toc_mgl;
			$this->TOC_margin_right = $toc_mgr;
			$this->TOC_margin_top = $toc_mgt;
			$this->TOC_margin_bottom = $toc_mgb;
			$this->TOC_margin_header = $toc_mgh;
			$this->TOC_margin_footer = $toc_mgf;
			$this->TOC_odd_header_name = $toc_ohname;
			$this->TOC_even_header_name = $toc_ehname;
			$this->TOC_odd_footer_name = $toc_ofname;
			$this->TOC_even_footer_name = $toc_efname;
			$this->TOC_odd_header_value = $toc_ohvalue;
			$this->TOC_even_header_value = $toc_ehvalue;
			$this->TOC_odd_footer_value = $toc_ofvalue;
			$this->TOC_even_footer_value = $toc_efvalue;
			$this->TOC_page_selector = $toc_pagesel;
			$this->TOCsheetsize = $toc_sheetsize;
		}
}

// Initiate, and Mark a place for the Table of Contents to be inserted
function TOC($tocfont='', $tocfontsize=8, $tocindent=5, $resetpagenum='', $pagenumstyle='', $suppress='', $toc_orientation='', $TOCusePaging=true, $TOCuseLinking=false, $toc_id=0) {
		if (strtoupper($toc_id)=='ALL') { $toc_id = '_mpdf_all'; }
		else if (!$toc_id) { $toc_id = 0; }
		else { $toc_id = strtolower($toc_id); }
		// To use odd and even pages
		// Cannot start table of contents on an even page
		if (($this->mpdf->mirrorMargins) && (($this->mpdf->page)%2==0)) {	// EVEN
			if ($this->mpdf->ColActive) {
				if (count($this->mpdf->columnbuffer)) { $this->mpdf->printcolumnbuffer(); }
			}
			$this->mpdf->AddPage($this->mpdf->CurOrientation,'',$resetpagenum, $pagenumstyle, $suppress);
		}
		else { 
			$this->mpdf->PageNumSubstitutions[] = array('from'=>$this->mpdf->page, 'reset'=> $resetpagenum, 'type'=>$pagenumstyle, 'suppress'=>$suppress);
		}
		if (!$tocfont) { $tocfont = $this->mpdf->default_font; }
		if (!$tocfontsize) { $tocfontsize = $this->mpdf->default_font_size; }
		if ($toc_id) {
			$this->m_TOC[$toc_id]['TOCmark'] = $this->mpdf->page; 
			$this->m_TOC[$toc_id]['TOCfont'] = $tocfont;
			$this->m_TOC[$toc_id]['TOCfontsize'] = $tocfontsize;
			$this->m_TOC[$toc_id]['TOCindent'] = $tocindent;
			$this->m_TOC[$toc_id]['TOCorientation'] = $toc_orientation;
			$this->m_TOC[$toc_id]['TOCuseLinking'] = $TOCuseLinking;
			$this->m_TOC[$toc_id]['TOCusePaging'] = $TOCusePaging;
		}
		else {
			$this->TOCmark = $this->mpdf->page; 
			$this->TOCfont = $tocfont;
			$this->TOCfontsize = $tocfontsize;
			$this->TOCindent = $tocindent;
			$this->TOCorientation = $toc_orientation;
			$this->TOCuseLinking = $TOCuseLinking;
			$this->TOCusePaging = $TOCusePaging;
		}
}


function insertTOC() {
	$notocs = 0;
	if ($this->TOCmark) { $notocs = 1; }
	$notocs += count($this->m_TOC);

	if ($notocs==0) { return; }

	if (count($this->m_TOC)) { reset($this->m_TOC); }
	$added_toc_pages = 0;

	if ($this->mpdf->ColActive) { $this->mpdf->SetColumns(0); }
	if (($this->mpdf->mirrorMargins) && (($this->mpdf->page)%2==1)) {	// ODD
		$this->mpdf->AddPage($this->mpdf->CurOrientation);
		$extrapage = true;
	}
	else { $extrapage = false; }

	for ($toci = 0; $toci<$notocs; $toci++) {
		if ($toci==0 && $this->TOCmark) {
			$toc_id = 0;
			$toc_page = $this->TOCmark; 
			$tocfont = $this->TOCfont;
			$tocfontsize = $this->TOCfontsize;
			$tocindent = $this->TOCindent;
			$toc_orientation = $this->TOCorientation;
			$TOCuseLinking = $this->TOCuseLinking;
			$TOCusePaging = $this->TOCusePaging;
			$toc_preHTML = $this->TOCpreHTML;
			$toc_postHTML = $this->TOCpostHTML;
			$toc_bookmarkText = $this->TOCbookmarkText;
			$toc_mgl = $this->TOC_margin_left;
			$toc_mgr = $this->TOC_margin_right;
			$toc_mgt = $this->TOC_margin_top;
			$toc_mgb = $this->TOC_margin_bottom;
			$toc_mgh = $this->TOC_margin_header;
			$toc_mgf = $this->TOC_margin_footer;
			$toc_ohname = $this->TOC_odd_header_name;
			$toc_ehname = $this->TOC_even_header_name;
			$toc_ofname = $this->TOC_odd_footer_name;
			$toc_efname = $this->TOC_even_footer_name;
			$toc_ohvalue = $this->TOC_odd_header_value;
			$toc_ehvalue = $this->TOC_even_header_value;
			$toc_ofvalue = $this->TOC_odd_footer_value;
			$toc_efvalue = $this->TOC_even_footer_value;
			$toc_page_selector = $this->TOC_page_selector;
			$toc_sheet_size = $this->TOCsheetsize;
		}
		else {
			$arr = current($this->m_TOC);

			$toc_id = key($this->m_TOC);
			$toc_page = $this->m_TOC[$toc_id]['TOCmark'];
			$tocfont = $this->m_TOC[$toc_id]['TOCfont'];
			$tocfontsize = $this->m_TOC[$toc_id]['TOCfontsize'];
			$tocindent = $this->m_TOC[$toc_id]['TOCindent'];
			$toc_orientation = $this->m_TOC[$toc_id]['TOCorientation'];
			$TOCuseLinking = $this->m_TOC[$toc_id]['TOCuseLinking'];
			$TOCusePaging = $this->m_TOC[$toc_id]['TOCusePaging'];
			if (isset($this->m_TOC[$toc_id]['TOCpreHTML'])) { $toc_preHTML = $this->m_TOC[$toc_id]['TOCpreHTML']; }
			else { $toc_preHTML = ''; }
			if (isset($this->m_TOC[$toc_id]['TOCpostHTML'])) { $toc_postHTML = $this->m_TOC[$toc_id]['TOCpostHTML']; }
			else { $toc_postHTML = ''; }
			if (isset($this->m_TOC[$toc_id]['TOCbookmarkText'])) { $toc_bookmarkText = $this->m_TOC[$toc_id]['TOCbookmarkText']; }
			else { $toc_bookmarkText = ''; }	// *BOOKMARKS*
			$toc_mgl = $this->m_TOC[$toc_id]['TOC_margin_left'];
			$toc_mgr = $this->m_TOC[$toc_id]['TOC_margin_right'];
			$toc_mgt = $this->m_TOC[$toc_id]['TOC_margin_top'];
			$toc_mgb = $this->m_TOC[$toc_id]['TOC_margin_bottom'];
			$toc_mgh = $this->m_TOC[$toc_id]['TOC_margin_header'];
			$toc_mgf = $this->m_TOC[$toc_id]['TOC_margin_footer'];
			$toc_ohname = $this->m_TOC[$toc_id]['TOC_odd_header_name'];
			$toc_ehname = $this->m_TOC[$toc_id]['TOC_even_header_name'];
			$toc_ofname = $this->m_TOC[$toc_id]['TOC_odd_footer_name'];
			$toc_efname = $this->m_TOC[$toc_id]['TOC_even_footer_name'];
			$toc_ohvalue = $this->m_TOC[$toc_id]['TOC_odd_header_value'];
			$toc_ehvalue = $this->m_TOC[$toc_id]['TOC_even_header_value'];
			$toc_ofvalue = $this->m_TOC[$toc_id]['TOC_odd_footer_value'];
			$toc_efvalue = $this->m_TOC[$toc_id]['TOC_even_footer_value'];
			$toc_page_selector = $this->m_TOC[$toc_id]['TOC_page_selector'];
			$toc_sheet_size = $this->m_TOC[$toc_id]['TOCsheetsize'];
			next($this->m_TOC);
		}
		if ($this->TOCheader) { $this->mpdf->SetHeader($this->TOCheader); }
		else if ($this->TOCheader !== false) { $this->mpdf->SetHeader(); }

		if (!$tocindent && $tocindent !== 0) { $tocindent = 5; }
		if (!$toc_orientation) { $toc_orientation= $this->mpdf->DefOrientation; }
		$this->mpdf->AddPage($toc_orientation, '', '', '', "on", $toc_mgl, $toc_mgr, $toc_mgt, $toc_mgb, $toc_mgh, $toc_mgf, $toc_ohname, $toc_ehname, $toc_ofname, $toc_efname, $toc_ohvalue, $toc_ehvalue, $toc_ofvalue, $toc_efvalue, $toc_page_selector, $toc_sheet_size );

		if ($this->TOCfooter) { $this->mpdf->SetFooter($this->TOCfooter); }
		else if ($this->TOCfooter !== false) { $this->mpdf->SetFooter(); }

		$tocstart=count($this->mpdf->pages);
		if ($toc_preHTML) { $this->mpdf->WriteHTML($toc_preHTML); }

		foreach($this->_toc as $t) {
		 if ($t['toc_id']==='_mpdf_all' || $t['toc_id']===$toc_id ) {
		   $tpgno = $this->mpdf->docPageNum($t['p']);
		   $lineheightcorr = 2-$t['l'];
		   //Offset
		   $level=$t['l'];

		   if ($TOCuseLinking) { $tlink = $t['link']; }
		   else  { $tlink = ''; }

		   if ($this->mpdf->directionality == 'rtl') {
			$weight='';
			if($level==0)
				$weight='B';
			$str=$t['t'];
			$fullstr = $str;
			$this->mpdf->SetFont($tocfont,$weight,$tocfontsize,true,true);
			$PageCellSize=$this->mpdf->GetStringWidth($tpgno )+2;
			$strsize=$this->mpdf->GetStringWidth($str);
			$repdots = $this->mpdf->GetStringWidth(str_repeat('.',5));	// mPDF 5.3.07
			$cw = count(explode(' ',$str));
			while (($strsize + $repdots +4 + $PageCellSize) > $this->mpdf->pgwidth && $cw>1) {	// mPDF 5.3.07
				$str = implode(' ',explode(' ',$str,-1));
				$strsize=$this->mpdf->GetStringWidth($str);
				$cw = count(explode(' ',$str));
			}
			$sl = strlen($str);
			$rem = substr($fullstr, ($sl+1));

			$this->mpdf->magic_reverse_dir($str, true, $this->mpdf->directionality);	// *RTL*

			if ($TOCusePaging) {
				//Page number
				$this->mpdf->SetFont($tocfont,'',$tocfontsize);
				$this->mpdf->Cell($PageCellSize,$this->mpdf->FontSize+$lineheightcorr,$tpgno ,0,0,'L',0,$tlink);

				//Filling dots
				$w=$this->mpdf->w-$this->mpdf->lMargin-$this->mpdf->rMargin-$PageCellSize-($level*$tocindent)-($strsize+2);
				$nb=intval($w/$this->mpdf->GetCharWidth('.',false));	// mPDF 5.3.04
				if ($nb>0) { 
					$dots=str_repeat('.',$nb);
					$this->mpdf->Cell($w+2,$this->mpdf->FontSize+$lineheightcorr,$dots,0,0,'L');
				}
				// Text
				$this->mpdf->SetFont($tocfont,$weight,$tocfontsize);
				$this->mpdf->Cell($strsize-($level*$tocindent),$this->mpdf->FontSize+$lineheightcorr,$str,0,1,'R',0,$tlink);
			}
			else {
				// Text
				$this->mpdf->SetFont($tocfont,$weight,$tocfontsize);
				$this->mpdf->Cell($this->mpdf->pgwidth -($level*$tocindent),$this->mpdf->FontSize+$lineheightcorr,$str,0,1,'R',0,$tlink);
			}

			if ($rem) {
				$this->mpdf->x += 10;
				$this->mpdf->SetFont($tocfont,$weight,$tocfontsize,true,true);
				$this->mpdf->MultiCell($this->mpdf->pgwidth -($level*$tocindent)-15,$this->mpdf->FontSize+$lineheightcorr,$rem,0,R,0,$tlink,'rtl',true); 
			}
		   }
		   // LTR
		   else {
			// Text
			$weight='';
			if($level==0)
				$weight='B';
			$str=$t['t'];
			$fullstr = $str;
			$this->mpdf->SetFont($tocfont,$weight,$tocfontsize,true,true);
			if($level>0 && $tocindent) { $this->mpdf->Cell($level*$tocindent,$this->mpdf->FontSize+$lineheightcorr); }

			// Font-specific ligature substitution for Indic fonts
			if (isset($this->mpdf->CurrentFont['indic']) && $this->mpdf->CurrentFont['indic']) $this->mpdf->ConvertIndic($str);	// *INDIC*

			$PageCellSize=$this->mpdf->GetStringWidth($tpgno )+2;
			$strsize=$this->mpdf->GetStringWidth($str);
			$repdots = $this->mpdf->GetStringWidth(str_repeat('.',5));	// mPDF 5.3.07
			$cw = count(explode(' ',$str));
			while (($strsize + $repdots +4+ $PageCellSize + ($level*$tocindent)) > $this->mpdf->pgwidth && $cw>1) {	// mPDF 5.3.07
				$str = implode(' ',explode(' ',$str,-1));
				$strsize=$this->mpdf->GetStringWidth($str);
				$cw = count(explode(' ',$str));
			}
			$sl = strlen($str);
			$rem = substr($fullstr, ($sl+1));

			if ($TOCusePaging) {
				// Text
				$this->mpdf->Cell($strsize+2,$this->mpdf->FontSize+$lineheightcorr,$str,0,0,'',0,$tlink);

				//Filling dots
				$this->mpdf->SetFont($tocfont,'',$tocfontsize);
				$w=$this->mpdf->w-$this->mpdf->lMargin-$this->mpdf->rMargin-$PageCellSize-($level*$tocindent)-($strsize+2);
				$nb=intval($w/$this->mpdf->GetCharWidth('.',false));	// mPDF 5.3.04
				if ($nb>0) { $dots=str_repeat('.',$nb); }
				else { $this->mpdf->y += $this->mpdf->lineheight; $dots=str_repeat('.',5); }	// ..... 5 dots?
				$this->mpdf->Cell($w,$this->mpdf->FontSize+$lineheightcorr,$dots,0,0,'R');

				//Page number
				$this->mpdf->Cell($PageCellSize,$this->mpdf->FontSize+$lineheightcorr,$tpgno ,0,1,'R',0,$tlink);
			}
			else {
				// Text only
				$this->mpdf->Cell($strsize+2,$this->mpdf->FontSize+$lineheightcorr,$str,0,1,'',0,$tlink);	// forces new line
			}
			if ($rem) {
				$this->mpdf->x +=  5 + $PageCellSize + ($level*$tocindent);
				$this->mpdf->SetFont($tocfont,$weight,$tocfontsize,true,true);
				$this->mpdf->MultiCell($strsize+2,$this->mpdf->FontSize+$lineheightcorr,$rem,0,L,0,$tlink,'ltr',true); 
			}

		   }	// *RTL*
		 } 
		}

		if ($toc_postHTML) { $this->mpdf->WriteHTML($toc_postHTML); }
		$this->mpdf->AddPage($toc_orientation,'E');

		$n_toc = $this->mpdf->page - $tocstart + 1;

		if ($toci==0 && $this->TOCmark) {
			$TOC_start = $tocstart ;
			$TOC_end = $this->mpdf->page;
			$TOC_npages = $n_toc;
		}
		else {
			$this->m_TOC[$toc_id]['start'] = $tocstart ;
			$this->m_TOC[$toc_id]['end'] = $this->mpdf->page;
			$this->m_TOC[$toc_id]['npages'] = $n_toc;
		}
	}

	$s = '';

	$s .= $this->mpdf->PrintBodyBackgrounds();

	$s .= $this->mpdf->PrintPageBackgrounds();
	$this->mpdf->pages[$this->mpdf->page] = preg_replace('/(___BACKGROUND___PATTERNS'.date('jY').')/', "\n".$s."\n".'\\1', $this->mpdf->pages[$this->mpdf->page]);
	$this->mpdf->pageBackgrounds = array();

	//Page footer
	$this->mpdf->InFooter=true;
	$this->mpdf->Footer();
	$this->mpdf->InFooter=false;

	// 2nd time through to move pages etc.
	$added_toc_pages = 0;
	if (count($this->m_TOC)) { reset($this->m_TOC); }

	for ($toci = 0; $toci<$notocs; $toci++) {
		if ($toci==0 && $this->TOCmark) {
			$toc_id = 0;
			$toc_page = $this->TOCmark + $added_toc_pages; 
			$toc_orientation = $this->TOCorientation;
			$TOCuseLinking = $this->TOCuseLinking;
			$TOCusePaging = $this->TOCusePaging;
			$toc_bookmarkText = $this->TOCbookmarkText;	// *BOOKMARKS*

			$tocstart = $TOC_start ;
			$tocend = $n = $TOC_end;
			$n_toc = $TOC_npages;
		}
		else {
			$arr = current($this->m_TOC);

			$toc_id = key($this->m_TOC);
			$toc_page = $this->m_TOC[$toc_id]['TOCmark'] + $added_toc_pages;
			$toc_orientation = $this->m_TOC[$toc_id]['TOCorientation'];
			$TOCuseLinking = $this->m_TOC[$toc_id]['TOCuseLinking'];
			$TOCusePaging = $this->m_TOC[$toc_id]['TOCusePaging'];
			$toc_bookmarkText = $this->m_TOC[$toc_id]['TOCbookmarkText'];	// *BOOKMARKS*

			$tocstart = $this->m_TOC[$toc_id]['start'] ;
			$tocend = $n = $this->m_TOC[$toc_id]['end'] ;
			$n_toc = $this->m_TOC[$toc_id]['npages'] ;

			next($this->m_TOC);
		}

		// Now pages moved
		$added_toc_pages += $n_toc;

		$this->mpdf->MovePages($toc_page, $tocstart, $tocend) ;
		$this->mpdf->pgsIns[$toc_page] = $tocend - $tocstart + 1;

/*-- BOOKMARKS --*/
		// Insert new Bookmark for Bookmark
		if ($toc_bookmarkText) {
			$insert = -1;
			foreach($this->mpdf->BMoutlines as $i=>$o) {
				if($o['p']<$toc_page) {	// i.e. before point of insertion
					$insert = $i;
				}
			}
			$txt = $this->mpdf->purify_utf8_text($toc_bookmarkText);
			if ($this->mpdf->text_input_as_HTML) {
				$txt = $this->mpdf->all_entities_to_utf8($txt);
			}
			$newBookmark[0] = array('t'=>$txt,'l'=>0,'y'=>0,'p'=>$toc_page );
			array_splice($this->mpdf->BMoutlines,($insert+1),0,$newBookmark);
		}
/*-- END BOOKMARKS --*/

	}

	// Delete empty page that was inserted earlier
	if ($extrapage) {
		unset($this->mpdf->pages[count($this->mpdf->pages)]);
		$this->mpdf->page--;	// Reset page pointer
	}


}


function openTagTOC($attr) {
	if (isset($attr['FONT-SIZE']) && $attr['FONT-SIZE']) { $tocfontsize = $attr['FONT-SIZE']; } else { $tocfontsize = ''; }
	if (isset($attr['FONT']) && $attr['FONT']) { $tocfont = $attr['FONT']; } else { $tocfont = ''; }
	if (isset($attr['INDENT']) && $attr['INDENT']) { $tocindent = $attr['INDENT']; } else { $tocindent = ''; }
	if (isset($attr['RESETPAGENUM']) && $attr['RESETPAGENUM']) { $resetpagenum = $attr['RESETPAGENUM']; } else { $resetpagenum = ''; }
	if (isset($attr['PAGENUMSTYLE']) && $attr['PAGENUMSTYLE']) { $pagenumstyle = $attr['PAGENUMSTYLE']; } else { $pagenumstyle= ''; }
	if (isset($attr['SUPPRESS']) && $attr['SUPPRESS']) { $suppress = $attr['SUPPRESS']; } else { $suppress = ''; }
	if (isset($attr['TOC-ORIENTATION']) && $attr['TOC-ORIENTATION']) { $toc_orientation = $attr['TOC-ORIENTATION']; } else { $toc_orientation = ''; }
	if (isset($attr['PAGING']) && (strtoupper($attr['PAGING'])=='OFF' || $attr['PAGING']==='0')) { $paging = false; }
	else { $paging = true; }
	if (isset($attr['LINKS']) && (strtoupper($attr['LINKS'])=='ON' || $attr['LINKS']==1)) { $links = true; }
	else { $links = false; }
	if (isset($attr['NAME']) && $attr['NAME']) { $toc_id = strtolower($attr['NAME']); } else { $toc_id = 0; }
	$this->TOC($tocfont,$tocfontsize,$tocindent,$resetpagenum, $pagenumstyle, $suppress, $toc_orientation, $paging, $links, $toc_id);
}


function openTagTOCPAGEBREAK($attr) {
	if (isset($attr['NAME']) && $attr['NAME']) { $toc_id = strtolower($attr['NAME']); } else { $toc_id = 0; }
	if ($toc_id) {
	  if (isset($attr['FONT-SIZE'])) { $this->m_TOC[$toc_id]['TOCfontsize'] = $attr['FONT-SIZE']; } else { $this->m_TOC[$toc_id]['TOCfontsize'] = $this->mpdf->default_font_size; }
	  if (isset($attr['FONT'])) { $this->m_TOC[$toc_id]['TOCfont'] = $attr['FONT']; } else { $this->m_TOC[$toc_id]['TOCfont'] = $this->mpdf->default_font; }
	  if (isset($attr['INDENT']) && $attr['INDENT']) { $this->m_TOC[$toc_id]['TOCindent'] = $attr['INDENT']; } else { $this->m_TOC[$toc_id]['TOCindent'] = ''; }
	  if (isset($attr['TOC-ORIENTATION']) && $attr['TOC-ORIENTATION']) { $this->m_TOC[$toc_id]['TOCorientation'] = $attr['TOC-ORIENTATION']; } else { $this->m_TOC[$toc_id]['TOCorientation'] = ''; }
	  if (isset($attr['PAGING']) && (strtoupper($attr['PAGING'])=='OFF' || $attr['PAGING']==='0')) { $this->m_TOC[$toc_id]['TOCusePaging'] = false; }
	  else { $this->m_TOC[$toc_id]['TOCusePaging'] = true; }
	  if (isset($attr['LINKS']) && (strtoupper($attr['LINKS'])=='ON' || $attr['LINKS']==1)) { $this->m_TOC[$toc_id]['TOCuseLinking'] = true; }
	  else { $this->m_TOC[$toc_id]['TOCuseLinking'] = false; }

	  $this->m_TOC[$toc_id]['TOC_margin_left'] = $this->m_TOC[$toc_id]['TOC_margin_right'] = $this->m_TOC[$toc_id]['TOC_margin_top'] = $this->m_TOC[$toc_id]['TOC_margin_bottom'] = $this->m_TOC[$toc_id]['TOC_margin_header'] = $this->m_TOC[$toc_id]['TOC_margin_footer'] = '';
	  if (isset($attr['TOC-MARGIN-RIGHT'])) { $this->m_TOC[$toc_id]['TOC_margin_right'] = $this->mpdf->ConvertSize($attr['TOC-MARGIN-RIGHT'],$this->mpdf->w,$this->mpdf->FontSize,false); }
	  if (isset($attr['TOC-MARGIN-LEFT'])) { $this->m_TOC[$toc_id]['TOC_margin_left'] = $this->mpdf->ConvertSize($attr['TOC-MARGIN-LEFT'],$this->mpdf->w,$this->mpdf->FontSize,false); }
	  if (isset($attr['TOC-MARGIN-TOP'])) { $this->m_TOC[$toc_id]['TOC_margin_top'] = $this->mpdf->ConvertSize($attr['TOC-MARGIN-TOP'],$this->mpdf->w,$this->mpdf->FontSize,false); }
	  if (isset($attr['TOC-MARGIN-BOTTOM'])) { $this->m_TOC[$toc_id]['TOC_margin_bottom'] = $this->mpdf->ConvertSize($attr['TOC-MARGIN-BOTTOM'],$this->mpdf->w,$this->mpdf->FontSize,false); }
	  if (isset($attr['TOC-MARGIN-HEADER'])) { $this->m_TOC[$toc_id]['TOC_margin_header'] = $this->mpdf->ConvertSize($attr['TOC-MARGIN-HEADER'],$this->mpdf->w,$this->mpdf->FontSize,false); }
	  if (isset($attr['TOC-MARGIN-FOOTER'])) { $this->m_TOC[$toc_id]['TOC_margin_footer'] = $this->mpdf->ConvertSize($attr['TOC-MARGIN-FOOTER'],$this->mpdf->w,$this->mpdf->FontSize,false); }
	  $this->m_TOC[$toc_id]['TOC_odd_header_name'] = $this->m_TOC[$toc_id]['TOC_even_header_name'] = $this->m_TOC[$toc_id]['TOC_odd_footer_name'] = $this->m_TOC[$toc_id]['TOC_even_footer_name'] = '';
	  if (isset($attr['TOC-ODD-HEADER-NAME']) && $attr['TOC-ODD-HEADER-NAME']) { $this->m_TOC[$toc_id]['TOC_odd_header_name'] = $attr['TOC-ODD-HEADER-NAME']; }
	  if (isset($attr['TOC-EVEN-HEADER-NAME']) && $attr['TOC-EVEN-HEADER-NAME']) { $this->m_TOC[$toc_id]['TOC_even_header_name'] = $attr['TOC-EVEN-HEADER-NAME']; }
	  if (isset($attr['TOC-ODD-FOOTER-NAME']) && $attr['TOC-ODD-FOOTER-NAME']) { $this->m_TOC[$toc_id]['TOC_odd_footer_name'] = $attr['TOC-ODD-FOOTER-NAME']; }
	  if (isset($attr['TOC-EVEN-FOOTER-NAME']) && $attr['TOC-EVEN-FOOTER-NAME']) { $this->m_TOC[$toc_id]['TOC_even_footer_name'] = $attr['TOC-EVEN-FOOTER-NAME']; }
	  $this->m_TOC[$toc_id]['TOC_odd_header_value'] = $this->m_TOC[$toc_id]['TOC_even_header_value'] = $this->m_TOC[$toc_id]['TOC_odd_footer_value'] = $this->m_TOC[$toc_id]['TOC_even_footer_value'] = 0;
	  if (isset($attr['TOC-ODD-HEADER-VALUE']) && ($attr['TOC-ODD-HEADER-VALUE']=='1' || strtoupper($attr['TOC-ODD-HEADER-VALUE'])=='ON')) { $this->m_TOC[$toc_id]['TOC_odd_header_value'] = 1; }
	  else if (isset($attr['TOC-ODD-HEADER-VALUE']) && ($attr['TOC-ODD-HEADER-VALUE']=='-1' || strtoupper($attr['TOC-ODD-HEADER-VALUE'])=='OFF')) { $this->m_TOC[$toc_id]['TOC_odd_header_value'] = -1; }
	  if (isset($attr['TOC-EVEN-HEADER-VALUE']) && ($attr['TOC-EVEN-HEADER-VALUE']=='1' || strtoupper($attr['TOC-EVEN-HEADER-VALUE'])=='ON')) { $this->m_TOC[$toc_id]['TOC_even_header_value'] = 1; }
	  else if (isset($attr['TOC-EVEN-HEADER-VALUE']) && ($attr['TOC-EVEN-HEADER-VALUE']=='-1' || strtoupper($attr['TOC-EVEN-HEADER-VALUE'])=='OFF')) { $this->m_TOC[$toc_id]['TOC_even_header_value'] = -1; }
	  if (isset($attr['TOC-ODD-FOOTER-VALUE']) && ($attr['TOC-ODD-FOOTER-VALUE']=='1' || strtoupper($attr['TOC-ODD-FOOTER-VALUE'])=='ON')) { $this->m_TOC[$toc_id]['TOC_odd_footer_value'] = 1; }
	  else if (isset($attr['TOC-ODD-FOOTER-VALUE']) && ($attr['TOC-ODD-FOOTER-VALUE']=='-1' || strtoupper($attr['TOC-ODD-FOOTER-VALUE'])=='OFF')) { $this->m_TOC[$toc_id]['TOC_odd_footer_value'] = -1; }
	  if (isset($attr['TOC-EVEN-FOOTER-VALUE']) && ($attr['TOC-EVEN-FOOTER-VALUE']=='1' || strtoupper($attr['TOC-EVEN-FOOTER-VALUE'])=='ON')) { $this->m_TOC[$toc_id]['TOC_even_footer_value'] = 1; }
	  else if (isset($attr['TOC-EVEN-FOOTER-VALUE']) && ($attr['TOC-EVEN-FOOTER-VALUE']=='-1' || strtoupper($attr['TOC-EVEN-FOOTER-VALUE'])=='OFF')) { $this->m_TOC[$toc_id]['TOC_even_footer_value'] = -1; }
	  if (isset($attr['TOC-PAGE-SELECTOR']) && $attr['TOC-PAGE-SELECTOR']) { $this->m_TOC[$toc_id]['TOC_page_selector'] = $attr['TOC-PAGE-SELECTOR']; }
	  else { $this->m_TOC[$toc_id]['TOC_page_selector'] = ''; }
	  if (isset($attr['TOC-SHEET-SIZE']) && $attr['TOC-SHEET-SIZE']) { $this->m_TOC[$toc_id]['TOCsheetsize'] = $attr['TOC-SHEET-SIZE']; } else { $this->m_TOC[$toc_id]['TOCsheetsize'] = ''; }


	  if (isset($attr['TOC-PREHTML']) && $attr['TOC-PREHTML']) { $this->m_TOC[$toc_id]['TOCpreHTML'] = htmlspecialchars_decode($attr['TOC-PREHTML'],ENT_QUOTES); }
	  if (isset($attr['TOC-POSTHTML']) && $attr['TOC-POSTHTML']) { $this->m_TOC[$toc_id]['TOCpostHTML'] = htmlspecialchars_decode($attr['TOC-POSTHTML'],ENT_QUOTES); }
	  
	  if (isset($attr['TOC-BOOKMARKTEXT']) && $attr['TOC-BOOKMARKTEXT']) { $this->m_TOC[$toc_id]['TOCbookmarkText'] = htmlspecialchars_decode($attr['TOC-BOOKMARKTEXT'],ENT_QUOTES); }	// *BOOKMARKS*
	}
	else {
	  if (isset($attr['FONT-SIZE'])) { $this->TOCfontsize = $attr['FONT-SIZE']; } else { $this->TOCfontsize = $this->mpdf->default_font_size; }
	  if (isset($attr['FONT'])) { $this->TOCfont = $attr['FONT']; } else { $this->TOCfont = $this->mpdf->default_font; }
	  if (isset($attr['INDENT']) && $attr['INDENT']) { $this->TOCindent = $attr['INDENT']; } else { $this->TOCindent = ''; }
	  if (isset($attr['TOC-ORIENTATION']) && $attr['TOC-ORIENTATION']) { $this->TOCorientation = $attr['TOC-ORIENTATION']; } else { $this->TOCorientation = ''; }
	  if (isset($attr['PAGING']) && (strtoupper($attr['PAGING'])=='OFF' || $attr['PAGING']==='0')) { $this->TOCusePaging = false; }
	  else { $this->TOCusePaging = true; }
	  if (isset($attr['LINKS']) && (strtoupper($attr['LINKS'])=='ON' || $attr['LINKS']==1)) { $this->TOCuseLinking = true; }
	  else { $this->TOCuseLinking = false; }

	  $this->TOC_margin_left = $this->TOC_margin_right = $this->TOC_margin_top = $this->TOC_margin_bottom = $this->TOC_margin_header = $this->TOC_margin_footer = '';
	  if (isset($attr['TOC-MARGIN-RIGHT'])) { $this->TOC_margin_right = $this->mpdf->ConvertSize($attr['TOC-MARGIN-RIGHT'],$this->mpdf->w,$this->mpdf->FontSize,false); }
	  if (isset($attr['TOC-MARGIN-LEFT'])) { $this->TOC_margin_left = $this->mpdf->ConvertSize($attr['TOC-MARGIN-LEFT'],$this->mpdf->w,$this->mpdf->FontSize,false); }
	  if (isset($attr['TOC-MARGIN-TOP'])) { $this->TOC_margin_top = $this->mpdf->ConvertSize($attr['TOC-MARGIN-TOP'],$this->mpdf->w,$this->mpdf->FontSize,false); }
	  if (isset($attr['TOC-MARGIN-BOTTOM'])) { $this->TOC_margin_bottom = $this->mpdf->ConvertSize($attr['TOC-MARGIN-BOTTOM'],$this->mpdf->w,$this->mpdf->FontSize,false); }
	  if (isset($attr['TOC-MARGIN-HEADER'])) { $this->TOC_margin_header = $this->mpdf->ConvertSize($attr['TOC-MARGIN-HEADER'],$this->mpdf->w,$this->mpdf->FontSize,false); }
	  if (isset($attr['TOC-MARGIN-FOOTER'])) { $this->TOC_margin_footer = $this->mpdf->ConvertSize($attr['TOC-MARGIN-FOOTER'],$this->mpdf->w,$this->mpdf->FontSize,false); }
	  $this->TOC_odd_header_name = $this->TOC_even_header_name = $this->TOC_odd_footer_name = $this->TOC_even_footer_name = '';
	  if (isset($attr['TOC-ODD-HEADER-NAME']) && $attr['TOC-ODD-HEADER-NAME']) { $this->TOC_odd_header_name = $attr['TOC-ODD-HEADER-NAME']; }
	  if (isset($attr['TOC-EVEN-HEADER-NAME']) && $attr['TOC-EVEN-HEADER-NAME']) { $this->TOC_even_header_name = $attr['TOC-EVEN-HEADER-NAME']; }
	  if (isset($attr['TOC-ODD-FOOTER-NAME']) && $attr['TOC-ODD-FOOTER-NAME']) { $this->TOC_odd_footer_name = $attr['TOC-ODD-FOOTER-NAME']; }
	  if (isset($attr['TOC-EVEN-FOOTER-NAME']) && $attr['TOC-EVEN-FOOTER-NAME']) { $this->TOC_even_footer_name = $attr['TOC-EVEN-FOOTER-NAME']; }
	  $this->TOC_odd_header_value = $this->TOC_even_header_value = $this->TOC_odd_footer_value = $this->TOC_even_footer_value = 0;
	  if (isset($attr['TOC-ODD-HEADER-VALUE']) && ($attr['TOC-ODD-HEADER-VALUE']=='1' || strtoupper($attr['TOC-ODD-HEADER-VALUE'])=='ON')) { $this->TOC_odd_header_value = 1; }
	  else if (isset($attr['TOC-ODD-HEADER-VALUE']) && ($attr['TOC-ODD-HEADER-VALUE']=='-1' || strtoupper($attr['TOC-ODD-HEADER-VALUE'])=='OFF')) { $this->TOC_odd_header_value = -1; }
	  if (isset($attr['TOC-EVEN-HEADER-VALUE']) && ($attr['TOC-EVEN-HEADER-VALUE']=='1' || strtoupper($attr['TOC-EVEN-HEADER-VALUE'])=='ON')) { $this->TOC_even_header_value = 1; }
	  else if (isset($attr['TOC-EVEN-HEADER-VALUE']) && ($attr['TOC-EVEN-HEADER-VALUE']=='-1' || strtoupper($attr['TOC-EVEN-HEADER-VALUE'])=='OFF')) { $this->TOC_even_header_value = -1; }
	  if (isset($attr['TOC-ODD-FOOTER-VALUE']) && ($attr['TOC-ODD-FOOTER-VALUE']=='1' || strtoupper($attr['TOC-ODD-FOOTER-VALUE'])=='ON')) { $this->TOC_odd_footer_value = 1; }
	  else if (isset($attr['TOC-ODD-FOOTER-VALUE']) && ($attr['TOC-ODD-FOOTER-VALUE']=='-1' || strtoupper($attr['TOC-ODD-FOOTER-VALUE'])=='OFF')) { $this->TOC_odd_footer_value = -1; }
	  if (isset($attr['TOC-EVEN-FOOTER-VALUE']) && ($attr['TOC-EVEN-FOOTER-VALUE']=='1' || strtoupper($attr['TOC-EVEN-FOOTER-VALUE'])=='ON')) { $this->TOC_even_footer_value = 1; }
	  else if (isset($attr['TOC-EVEN-FOOTER-VALUE']) && ($attr['TOC-EVEN-FOOTER-VALUE']=='-1' || strtoupper($attr['TOC-EVEN-FOOTER-VALUE'])=='OFF')) { $this->TOC_even_footer_value = -1; }
	  if (isset($attr['TOC-PAGE-SELECTOR']) && $attr['TOC-PAGE-SELECTOR']) { $this->TOC_page_selector = $attr['TOC-PAGE-SELECTOR']; }
	  else { $this->TOC_page_selector = ''; }
	  if (isset($attr['TOC-SHEET-SIZE']) && $attr['TOC-SHEET-SIZE']) { $this->TOCsheetsize = $attr['TOC-SHEET-SIZE']; } else { $this->TOCsheetsize = ''; }


	  if (isset($attr['TOC-PREHTML']) && $attr['TOC-PREHTML']) { $this->TOCpreHTML = htmlspecialchars_decode($attr['TOC-PREHTML'],ENT_QUOTES); }
	  if (isset($attr['TOC-POSTHTML']) && $attr['TOC-POSTHTML']) { $this->TOCpostHTML = htmlspecialchars_decode($attr['TOC-POSTHTML'],ENT_QUOTES); }
	  if (isset($attr['TOC-BOOKMARKTEXT']) && $attr['TOC-BOOKMARKTEXT']) { $this->TOCbookmarkText = htmlspecialchars_decode($attr['TOC-BOOKMARKTEXT'],ENT_QUOTES); }	
	}

	if ($this->mpdf->y == $this->mpdf->tMargin && (!$this->mpdf->mirrorMargins ||($this->mpdf->mirrorMargins && $this->mpdf->page % 2==1))) { 
		if ($toc_id) { $this->m_TOC[$toc_id]['TOCmark'] = $this->mpdf->page; }
		else { $this->TOCmark = $this->mpdf->page; }
		// Don't add a page
		if ($this->mpdf->page==1 && count($this->mpdf->PageNumSubstitutions)==0) { 
			$resetpagenum = '';
			$pagenumstyle = '';
			$suppress = '';
			if (isset($attr['RESETPAGENUM'])) { $resetpagenum = $attr['RESETPAGENUM']; }
			if (isset($attr['PAGENUMSTYLE'])) { $pagenumstyle = $attr['PAGENUMSTYLE']; }
			if (isset($attr['SUPPRESS'])) { $suppress = $attr['SUPPRESS']; }
			if (!$suppress) { $suppress = 'off'; }
			if (!$resetpagenum) { $resetpagenum= 1; }
			$this->mpdf->PageNumSubstitutions[] = array('from'=>1, 'reset'=> $resetpagenum, 'type'=>$pagenumstyle, 'suppress'=> $suppress);
		}
		return array(true, $toc_id);
	}
	// No break - continues as PAGEBREAK...
	return array(false, $toc_id);
}


}

?>