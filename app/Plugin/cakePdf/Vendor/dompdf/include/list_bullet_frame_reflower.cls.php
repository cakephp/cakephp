<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: list_bullet_frame_reflower.cls.php,v $
 * Created on: 2004-06-23
 *
 * Copyright (c) 2004 - Benj Carson <benjcarson@digitaljunkies.ca>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this library in the file LICENSE.LGPL; if not, write to the
 * Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA
 * 02111-1307 USA
 *
 * Alternatively, you may distribute this software under the terms of the
 * PHP License, version 3.0 or later.  A copy of this license should have
 * been distributed with this file in the file LICENSE.PHP .  If this is not
 * the case, you can obtain a copy at http://www.php.net/license/3_0.txt.
 *
 * The latest version of DOMPDF might be available at:
 * http://www.dompdf.com/
 *
 * @link http://www.dompdf.com/
 * @copyright 2004 Benj Carson
 * @author Benj Carson <benjcarson@digitaljunkies.ca>
 * @package dompdf

 */

/* $Id: list_bullet_frame_reflower.cls.php 216 2010-03-11 22:49:18Z ryan.masten $ */

/**
 * Reflows list bullets
 *
 * @access private
 * @package dompdf
 */
class List_Bullet_Frame_Reflower extends Frame_Reflower {

  function __construct(Frame_Decorator $frame) { parent::__construct($frame); }
    
  //........................................................................

  function reflow() {
    $style = $this->_frame->get_style();

    $style->width = $this->_frame->get_width();
    $this->_frame->position();

    if ( $style->list_style_position === "inside" ) {
      $p = $this->_frame->find_block_parent();
      $p->add_frame_to_line($this->_frame);
    }

  }
}
