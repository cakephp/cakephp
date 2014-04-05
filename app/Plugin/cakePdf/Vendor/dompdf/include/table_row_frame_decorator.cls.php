<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: table_row_frame_decorator.cls.php,v $
 * Created on: 2004-06-07
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

/* $Id: table_row_frame_decorator.cls.php 216 2010-03-11 22:49:18Z ryan.masten $ */

/**
 * Decorates Frames for table row layout
 *
 * @access private
 * @package dompdf
 */
class Table_Row_Frame_Decorator extends Frame_Decorator {

  // protected members
  
  function __construct(Frame $frame, DOMPDF $dompdf) {
    parent::__construct($frame, $dompdf);
  }
  
  //........................................................................ 

  /**
   * Remove all non table-cell frames from this row and move them after
   * the table.
   */
  function normalise() {

    // Find our table parent
    $p = Table_Frame_Decorator::find_parent_table($this);
    
    $erroneous_frames = array();
    foreach ($this->get_children() as $child) {      
      $display = $child->get_style()->display;

      if ( $display !== "table-cell" )
        $erroneous_frames[] = $child;
    }
    
    //  dump the extra nodes after the table.
    foreach ($erroneous_frames as $frame) 
      $p->move_after($frame);
  }
  
  
}
