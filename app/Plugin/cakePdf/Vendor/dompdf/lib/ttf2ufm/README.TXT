To embed TrueType fonts (.TTF) files, you need to extract the font metrics and 
build the required tables using the provided utility (/fonts/ttf2ufm). 

TTF2UFM is a modified version of Mark Heath's TTF 2 PT1 converter 
(http://ttf2pt1.sourceforge.net/) by Steven Wittens <steven@acko.net> 
(http://www.acko.net/blog/ufpdf). ttf2ufm, is included in /ttf2ufm-src. 
The /fonts/ttf2ufm folder contains a compiled Windows binary. 
TTF 2 UFM is identical to TTF 2 PT1 except that it also generates a .ufm file 
for usage with makefontuni.php.


Setting up a Truetype font for usage with TCPDF:
  1) Generate the font's .ufm metrics file by processing it with the provided 
     ttf2ufm program (modified ttf2pt1). For example:
     $ ttf2ufm -a -F myfont.ttf
  2) Run makefontuni.php with the .ttf and .ufm filenames as argument:
     $ php -q makefontuni.php myfont.ttf myfont.ufm
  3) Copy the resulting .php, .z and .ctg.z file to the TCPDF font directory.
  4) Rename php font files variations for bold and italic using the following schema:
  	[basic-font-name]b.php for bold variation
  	[basic-font-name]i.php for oblique variation
  	[basic-font-name]bi.php for bold oblique variation
  5) Convert all fonts filenames to lowercase.
  	