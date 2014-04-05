# CakePdf plugin

[![Build Status](https://secure.travis-ci.org/ceeram/CakePdf.png)](http://travis-ci.org/ceeram/CakePdf)

Plugin containing CakePdf lib which will use a pdf engine to convert html to pdf.

Current engines:
* DomPdf
* Mpdf
* Tcpdf
* Wkhtmltopdf (requires additional installation) **RECOMMENDED ENGINE**


## Requirements

* PHP 5.2.8
* CakePHP 2.1+
* wkhtmltopdf (optional) See: http://code.google.com/p/wkhtmltopdf/
* pdftk (optional) See: http://www.pdflabs.com/tools/pdftk-the-pdf-toolkit/


## Installation

_[Manual]_

1. Download this: http://github.com/ceeram/CakePdf/zipball/master
2. Unzip that download.
3. Copy the resulting folder to app/Plugin
4. Rename the folder you just copied to CakePdf

_[GIT Submodule]_

In your app directory type:
```bash
git submodule add git://github.com/ceeram/CakePdf.git Plugin/CakePdf
git submodule init
git submodule update
```

_[GIT Clone]_

In your plugin directory type
```bash
git clone git://github.com/ceeram/CakePdf.git CakePdf
```


## Setup

In `app/Config/bootstrap.php` add:
```php
CakePlugin::load('CakePdf', array('bootstrap' => true, 'routes' => true));
```


## Configuration

Use `Configure::write('CakePdf', $config);` or set Controller property `$pdfConfig` (only when used with PdfView)
You need to define at least `$config['engine']`. When using CakePdf directly you can also pass the config array to constructor.
The value for engine should have the `Plugin.ClassName` format without the Engine suffix

Configuration options:
* engine: Engine to be used (required)
* options: Engine options, this may vary between Engines
* pageSize: Change the default size, defaults to A4
* orientation: Change the default orientation, defaults to potrait
* margin: Array or margins with the keys: bottom, left, right, top and their values
* title: Title of the document
* encoding: Change the encoding, defaults to UTF-8
* binary: Path to binary (WkHtmlToPdfEngine only), defaults to /usr/bin/wkhtmltopdf
* download: Set to true to force a download, only when using PdfView
* filename: Filename for the document when using forced download

Example:
```php
<?php
    Configure::write('CakePdf', array(
        'engine' => 'CakePdf.WkHtmlToPdf',
        'options' => array(
            'print-media-type' => false,
            'outline' => true,
            'dpi' => 96
        ),
        'margin' => array(
            'bottom' => 15,
            'left' => 50,
            'right' => 30,
            'top' => 45
        ),
        'orientation' => 'landscape',
        'download' => true
    ));
?>

<?php
    class InvoicesController extends AppController {
        //in your Invoices controller you could set additional configs, or override the global ones:
        public function view($id = null) {
            $this->Invoice->id = $id;
            if (!$this->Invoice->exists()) {
                throw new NotFoundException(__('Invalid invoice'));
            }
            $this->pdfConfig = array(
                'orientation' => 'portrait',
                'filename' => 'Invoice_' . $id
            );
            $this->set('invoice', $this->Invoice->read(null, $id));
        }
    }
?>
```


## Usage

You can use CakePdf in 2 ways, read carefully which one you actually need.
Many people mix both ways and dont get the expected results.


### 1: Render as pdf (including forced download) in the browser with PdfView

You can create pdf view and layout files for your controller actions and have them automatically rendered.
Place the view templates in a 'pdf' subdir, for instance `app/View/Invoices/pdf/view.ctp`
Layouts will be in `app/View/Layouts/pdf/default.ctp`

Make sure your InvoicesController has RequestHandler Component in the `$components` array.
Browse to http://localhost/invoices/view/1.pdf

Additionally you can map resources by adding `Router::mapResources(array('Invoices'));` to your routes
file and you can access the same document at http://localhost/invoices/1.pdf


### 2: Create pdf for email attachment, file storage etc.

You can use CakePdf lib to create raw pdf data with a view template.
The view file path would look like `app/View/Pdf/newsletter.ctp`.
Layout file path would be like `app/View/Layouts/pdf/default.ctp`
Note that layouts for both usage types are within same directory, but the view templates use different file paths
Optionally you can also write the raw data to file.

Example:
```php
<?php
    $CakePdf = new CakePdf();
    $CakePdf->template('newsletter', 'default');
    //get the pdf string returned
    $pdf = $CakePdf->output();
    //or write it to file directly
    $pdf = $CakePdf->write(APP . 'files' . DS . 'newsletter.pdf');
```


## Encryption

You can optionally encrypt the PDF with permissions

To use encryption you first need to select a crypto engine. Currently we support the following crypto engines:
* Pdftk


### Usage

Add the following in your bootstrap.

```php
Configure::write('CakePdf.crypto', 'CakePdf.Pdftk');
```

Options in pdfConfig:
* protect: Set to true to enable encryption
* userPassword (optional): Set a password to open the PDF file
* ownerPassword (optional): Set the password to unlock the locked permissions
* one of the above must be present, either userPassword or ownerPassword
* permissions (optional): Define the permissions

Permissions:

By default, we deny all permissions.

To allow all permissions:

Set 'permission' to true

To allow specific permissions:

Set 'permissions' to an array with a combination of the following available permissions:
* print
* degraded_print
* modify,
* assembly,
* copy_contents,
* screen_readers,
* annotate,
* fill_in


## Note about static assets

Use absolute urls for static assets in your view templates for pdf.
If you use `HtmlHelper::image()`, `HtmlHelper::script()` or `HtmlHelper::css()` make sure you have `$options['fullBase'] = true`

Another solution would be to create a `AppHelper` of which it would force `$options['fullBase'] = true` for PDF requests. e.g:
```php
class AppHelper extends Helper {
    public function assetUrl($path, $options = array()) {
    	if (!empty($this->request->params['ext']) && $this->request->params['ext'] === 'pdf') {
			$options['fullBase'] = true;
		}
		return parent::assetUrl($path, $options);
	}
}
```


## Thanks

Many thanks to Kim Biesbjerg and Jelle Henkens for their contributions.
Want your name here as well? Create a pull request for improvements/other pdf engines.


## License

Copyright (c) 2013 Ceeram

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
