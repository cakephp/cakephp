<?php
App::build(array('Pdf' => array('%s' . 'Pdf' . DS)), App::REGISTER);
App::build(array('Pdf/Engine' => array('%s' . 'Pdf/Engine' . DS)), App::REGISTER);
App::uses('PdfView', 'CakePdf.View');