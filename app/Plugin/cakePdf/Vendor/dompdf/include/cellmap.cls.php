<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: cellmap.cls.php,v $
 * Created on: 2004-07-28
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

/* $Id: cellmap.cls.php 216 2010-03-11 22:49:18Z ryan.masten $ */

/**
 * Maps table cells to the table grid.
 *
 * This class resolves borders in tables with collapsed borders and helps
 * place row & column spanned table cells.
 *
 * @access private
 * @package dompdf
 */
class Cellmap {

  /**
   * Border style weight lookup for collapsed border resolution.
   *
   * @var array
   */
  static protected $_BORDER_STYLE_SCORE = array("inset"  => 1,
                                                "groove" => 2,
                                                "outset" => 3,
                                                "ridge"  => 4,
                                                "dotted" => 5,
                                                "dashed" => 6,
                                                "solid"  => 7,
                                                "double" => 8,
                                                "none"   => 0);

  /**
   * The table object this cellmap is attached to.
   *
   * @var Table_Frame_Decorator
   */
  protected $_table;

  /**
   * The total number of rows in the table
   *
   * @var int
   */
  protected $_num_rows;

  /**
   * The total number of columns in the table
   *
   * @var int
   */
  protected $_num_cols;

  /**
   * 2D array mapping <row,column> to frames
   *
   * @var array
   */
  protected $_cells;

  /**
   * 1D array of column dimensions
   *
   * @var array
   */
  protected $_columns;

  /**
   * 1D array of row dimensions
   *
   * @var array
   */
  protected $_rows;

  /**
   * 2D array of border specs
   *
   * @var array
   */
  protected $_borders;

  /**
   * 1D Array mapping frames to (multiple) <row, col> pairs, keyed on
   * frame_id.
   *
   * @var array
   */
  protected $_frames;

  /**
   * Current column when adding cells, 0-based
   *
   * @var int
   */
  protected $__col;

  /**
   * Current row when adding cells, 0-based
   *
   * @var int
   */
  protected $__row;

  //........................................................................

  function __construct(Table_Frame_Decorator $table) {
    $this->_table = $table;
    $this->reset();
  }

  //........................................................................

  function reset() {
    $this->_num_rows = 0;
    $this->_num_cols = 0;

    $this->_cells  = array();
    $this->_frames = array();

    $this->_columns = array();
    $this->_rows = array();

    $this->_borders = array();

    $this->__col = $this->__row = 0;
  }

  //........................................................................

  function get_num_rows() { return $this->_num_rows; }
  function get_num_cols() { return $this->_num_cols; }

  function &get_columns() {
    return $this->_columns;
  }

  function &get_column($i) {
    if ( !isset($this->_columns[$i]) )
      $this->_columns[$i] = array("x" => 0,
                                  "min-width" => 0,
                                  "max-width" => 0,
                                  "used-width" => null,
                                  "absolute" => 0,
                                  "percent" => 0,
                                  "auto" => true);

    return $this->_columns[$i];
  }

  function &get_rows() {
    return $this->_rows;
  }

  function &get_row($j) {
    if ( !isset($this->_rows[$j]) )
      $this->_rows[$j] = array("y" => 0,
                               "first-column" => 0,
                               "height" => null);
    return $this->_rows[$j];
  }

  function get_border($i, $j, $h_v, $prop = null) {
    if ( !isset($this->_borders[$i][$j][$h_v]) )
      $this->_borders[$i][$j][$h_v] = array("width" => 0,
                                           "style" => "solid",
                                           "color" => "black");
    if ( isset($prop) )
      return $this->_borders[$i][$j][$h_v][$prop];

    return $this->_borders[$i][$j][$h_v];
  }

  function get_border_properties($i, $j) {

    $left = $this->get_border($i, $j, "vertical");
    $right = $this->get_border($i, $j+1, "vertical");
    $top = $this->get_border($i, $j, "horizontal");
    $bottom = $this->get_border($i+1, $j, "horizontal");

    return compact("top", "bottom", "left", "right");
  }

  //........................................................................

  function get_spanned_cells($frame) {
    $key = $frame->get_id();

    if ( !isset($this->_frames[$key]) ) {
      throw new DOMPDF_Internal_Exception("Frame not found in cellmap");
    }

    return $this->_frames[$key];

  }

  function frame_exists_in_cellmap($frame) {
    $key = $frame->get_id();
    return isset($this->_frames[$key]);
  }
  
  function get_frame_position($frame) {
    global $_dompdf_warnings;

    $key = $frame->get_id();

    if ( !isset($this->_frames[$key]) ) {
      throw new DOMPDF_Internal_Exception("Frame not found in cellmap");
    }

    $col = $this->_frames[$key]["columns"][0];
    $row = $this->_frames[$key]["rows"][0];

    if ( !isset($this->_columns[$col])) {
      $_dompdf_warnings[] = "Frame not found in columns array.  Check your table layout for missing or extra TDs.";
      $x = 0;
    } else
      $x = $this->_columns[$col]["x"];

    if ( !isset($this->_rows[$row])) {
      $_dompdf_warnings[] = "Frame not found in row array.  Check your table layout for missing or extra TDs.";
      $y = 0;
    } else
      $y = $this->_rows[$row]["y"];

    return array($x, $y, "x" => $x, "y" => $y);
  }

  function get_frame_width($frame) {
    $key = $frame->get_id();

    if ( !isset($this->_frames[$key]) ) {
      throw new DOMPDF_Internal_Exception("Frame not found in cellmap");
    }

    $cols = $this->_frames[$key]["columns"];
    $w = 0;
    foreach ($cols as $i)
      $w += $this->_columns[$i]["used-width"];

    return $w;

  }

  function get_frame_height($frame) {
    $key = $frame->get_id();

    if ( !isset($this->_frames[$key]) )
      throw new DOMPDF_Internal_Exception("Frame not found in cellmap");

    $rows = $this->_frames[$key]["rows"];
    $h = 0;
    foreach ($rows as $i) {
      if ( !isset($this->_rows[$i]) )  {
        throw new Exception("foo");
      }
      $h += $this->_rows[$i]["height"];
    }
    return $h;

  }


  //........................................................................

  function set_column_width($j, $width) {
    $col =& $this->get_column($j);
    $col["used-width"] = $width;
    $next_col =& $this->get_column($j+1);
    $next_col["x"] = $next_col["x"] + $width;
  }

  function set_row_height($i, $height) {
    $row =& $this->get_row($i);
    if ( $height <= $row["height"] )
      return;

    $row["height"] = $height;
    $next_row =& $this->get_row($i+1);
    $next_row["y"] = $row["y"] + $height;

  }

  //........................................................................


  protected function _resolve_border($i, $j, $h_v, $border_spec) {
    $n_width = $border_spec["width"];
    $n_style = $border_spec["style"];
    $n_color = $border_spec["color"];

    if ( !isset($this->_borders[$i][$j][$h_v]) ) {
      $this->_borders[$i][$j][$h_v] = $border_spec;
      return $this->_borders[$i][$j][$h_v]["width"];
    }

    $o_width = $this->_borders[$i][$j][$h_v]["width"];
    $o_style = $this->_borders[$i][$j][$h_v]["style"];
    $o_color = $this->_borders[$i][$j][$h_v]["color"];

    if ( ($n_style === "hidden" ||
          $n_width  >  $o_width ||
          $o_style === "none")

         or

         ($o_width == $n_width &&
          in_array($n_style, self::$_BORDER_STYLE_SCORE) &&
          self::$_BORDER_STYLE_SCORE[ $n_style ] > self::$_BORDER_STYLE_SCORE[ $o_style ]) )
      $this->_borders[$i][$j][$h_v] = $border_spec;

    return $this->_borders[$i][$j][$h_v]["width"];
  }

  //........................................................................

  function add_frame(Frame $frame) {
    
    $style = $frame->get_style();
    $display = $style->display;

    $collapse = $this->_table->get_style()->border_collapse == "collapse";

    // Recursively add the frames within tables, table-row-groups and table-rows
    if ( $display == "table-row" ||
         $display == "table" ||
         $display == "inline-table" ||
         in_array($display, Table_Frame_Decorator::$ROW_GROUPS) ) {

      $start_row = $this->__row;
      foreach ( $frame->get_children() as $child )
        $this->add_frame( $child );

      if ( $display == "table-row" )
        $this->add_row();

      $num_rows = $this->__row - $start_row - 1;
      $key = $frame->get_id();

      // Row groups always span across the entire table
      $this->_frames[ $key ]["columns"] = range(0,max(0,$this->_num_cols-1));
      $this->_frames[ $key ]["rows"] = range($start_row, max(0, $this->__row - 1));
      $this->_frames[ $key ]["frame"] = $frame;

      if ( $display != "table-row" && $collapse ) {

        $bp = $style->get_border_properties();

        // Resolve the borders
        for ( $i = 0; $i < $num_rows+1; $i++) {
          $this->_resolve_border($start_row + $i, 0, "vertical", $bp["left"]);
          $this->_resolve_border($start_row + $i, $this->_num_cols, "vertical", $bp["right"]);
        }

        for ( $j = 0; $j < $this->_num_cols; $j++) {
          $this->_resolve_border($start_row, $j, "horizontal", $bp["top"]);
          $this->_resolve_border($this->__row, $j, "horizontal", $bp["bottom"]);
        }
      }


      return;
    }

    // Determine where this cell is going
    $colspan = $frame->get_node()->getAttribute("colspan");
    $rowspan = $frame->get_node()->getAttribute("rowspan");

    if ( !$colspan ) {
      $colspan = 1;
      $frame->get_node()->setAttribute("colspan",1);
    }

    if ( !$rowspan ) {
      $rowspan = 1;
      $frame->get_node()->setAttribute("rowspan",1);
    }
    $key = $frame->get_id();

    $bp = $style->get_border_properties();


    // Add the frame to the cellmap
    $max_left = $max_right = 0;

    // Find the next available column (fix by Ciro Mondueri)
    $ac = $this->__col;
    while ( isset($this->_cells[$this->__row][$ac]) )
       $ac++;
    $this->__col = $ac;

    // Rows:
    for ( $i = 0; $i < $rowspan; $i++ ) {
      $row = $this->__row + $i;

      $this->_frames[ $key ]["rows"][] = $row;

      for ( $j = 0; $j < $colspan; $j++)
        $this->_cells[$row][$this->__col + $j] = $frame;

      if ( $collapse ) {
        // Resolve vertical borders
        $max_left = max($max_left, $this->_resolve_border($row, $this->__col, "vertical", $bp["left"]));
        $max_right = max($max_right, $this->_resolve_border($row, $this->__col + $colspan, "vertical", $bp["right"]));
      }
    }

    $max_top = $max_bottom = 0;

    // Columns:
    for ( $j = 0; $j < $colspan; $j++ ) {
      $col = $this->__col + $j;
      $this->_frames[ $key ]["columns"][] = $col;

      if ( $collapse ) {
        // Resolve horizontal borders
        $max_top = max($max_top, $this->_resolve_border($this->__row, $col, "horizontal", $bp["top"]));
        $max_bottom = max($max_bottom, $this->_resolve_border($this->__row + $rowspan, $col, "horizontal", $bp["bottom"]));
      }
    }

    $this->_frames[ $key ]["frame"] = $frame;

    // Handle seperated border model
    if ( !$collapse ) {
      list($h, $v) = $this->_table->get_style()->border_spacing;

      // Border spacing is effectively a margin between cells
      $v = $style->length_in_pt($v) / 2;
      $h = $style->length_in_pt($h) / 2;
      $style->margin = "$v $h";

      // The additional 1/2 width gets added to the table proper

    } else {

      // Drop the frame's actual border
      $style->border_left_width = $max_left / 2;
      $style->border_right_width = $max_right / 2;
      $style->border_top_width = $max_top / 2;
      $style->border_bottom_width = $max_bottom / 2;
      $style->margin = "none";
    }

    // Resolve the frame's width
    list($frame_min, $frame_max) = $frame->get_min_max_width();

    $width = $style->width;

    if ( is_percent($width) ) {
      $var = "percent";
      $val = (float)rtrim($width, "% ") / $colspan;

    } else if ( $width !== "auto" ) {
      $var = "absolute";
      $val = $style->length_in_pt($frame_min) / $colspan;
    }

    $min = 0;
    $max = 0;
    for ( $cs = 0; $cs < $colspan; $cs++ ) {

      // Resolve the frame's width(s) with other cells
      $col =& $this->get_column( $this->__col + $cs );

      // Note: $var is either 'percent' or 'absolute'.  We compare the
      // requested percentage or absolute values with the existing widths
      // and adjust accordingly.
      if ( isset($var) && $val > $col[$var] ) {
        $col[$var] = $val;
        $col["auto"] = false;
      }

      $min += $col["min-width"];
      $max += $col["max-width"];
    }


    if ( $frame_min > $min ) {
      // The frame needs more space.  Expand each sub-column
      $inc = ($frame_min - $min) / $colspan;
      for ($c = 0; $c < $colspan; $c++) {
        $col =& $this->get_column($this->__col + $c);
        $col["min-width"] += $inc;
      }
    }

    if ( $frame_max > $max ) {
      $inc = ($frame_max - $max) / $colspan;
      for ($c = 0; $c < $colspan; $c++) {
        $col =& $this->get_column($this->__col + $c);
        $col["max-width"] += $inc;
      }
    }

    $this->__col += $colspan;
    if ( $this->__col > $this->_num_cols )
      $this->_num_cols = $this->__col;

  }

  //........................................................................

  function add_row() {

    $this->__row++;
    $this->_num_rows++;

    // Find the next available column
    $i = 0;
    while ( isset($this->_cells[$this->__row][$i]) )
      $i++;

    $this->__col = $i;

  }

  //........................................................................

  /**
   * Remove a row from the cellmap.
   *
   * @param Frame
   */
  function remove_row(Frame $row) {

    $key = $row->get_id();
    if ( !isset($this->_frames[$key]) )
      return;  // Presumably this row has alredy been removed

    $this->_row = $this->_num_rows--;

    $rows = $this->_frames[$key]["rows"];
    $columns = $this->_frames[$key]["columns"];

    // Remove all frames from this row
    foreach ( $rows as $r ) {
      foreach ( $columns as $c ) {
        if ( isset($this->_cells[$r][$c]) ) {
          $frame = $this->_cells[$r][$c];
          unset($this->_frames[ $frame->get_id() ]);
          unset($this->_cells[$r][$c]);
        }
      }
      unset($this->_rows[$r]);
    }

    unset($this->_frames[$key]);

  }

  /**
   * Remove a row group from the cellmap.
   *
   * @param Frame $group  The group to remove
   */
  function remove_row_group(Frame $group) {

    $key = $group->get_id();
    if ( !isset($this->_frames[$key]) )
      return;  // Presumably this row has alredy been removed

    $iter = $group->get_first_child();
    while ($iter) {
      $this->remove_row($iter);
      $iter = $iter->get_next_sibling();
    }

    unset($this->_frames[$key]);
  }

  /**
   * Update a row group after rows have been removed
   *
   * @param Frame $group    The group to update
   * @param Frame $last_row The last row in the row group
   */
  function update_row_group(Frame $group, Frame $last_row) {

    $g_key = $group->get_id();
    $r_key = $last_row->get_id();

    $r_rows = $this->_frames[$r_key]["rows"];
    $this->_frames[$g_key]["rows"] = range( $this->_frames[$g_key]["rows"][0], end($r_rows) );

  }

  //........................................................................

  function assign_x_positions() {
    // Pre-condition: widths must be resolved and assigned to columns and
    // column[0]["x"] must be set.

    $x = $this->_columns[0]["x"];
    foreach ( array_keys($this->_columns) as $j ) {
      $this->_columns[$j]["x"] = $x;
      $x += $this->_columns[$j]["used-width"];

    }

  }

  function assign_frame_heights() {
    // Pre-condition: widths and heights of each column & row must be
    // calcluated

    foreach ( $this->_frames as $arr ) {
      $frame = $arr["frame"];

      $h = 0;
      foreach( $arr["rows"] as $row ) {
        if ( !isset($this->_rows[$row]) )
          // The row has been removed because of a page split, so skip it.
          continue;
        $h += $this->_rows[$row]["height"];
      }

      if ( $frame instanceof Table_Cell_Frame_Decorator )
        $frame->set_cell_height($h);
      else
        $frame->get_style()->height = $h;
    }

  }

  //........................................................................

  /**
   * Re-adjust frame height if the table height is larger than its content
   */
  function set_frame_heights($table_height, $content_height) {


    // Distribute the increased height proportionally amongst each row
    foreach ( $this->_frames as $arr ) {
      $frame = $arr["frame"];

      $h = 0;
      foreach ($arr["rows"] as $row ) {
        if ( !isset($this->_rows[$row]) )
          continue;

        $h += $this->_rows[$row]["height"];
      }

      $new_height = ($h / $content_height) * $table_height;

      if ( $frame instanceof Table_Cell_Frame_Decorator )
        $frame->set_cell_height($new_height);
      else
        $frame->get_style()->height = $new_height;
    }

  }

  //........................................................................

  // Used for debugging:
  function __toString() {
    $str = "";
    $str .= "Columns:<br/>";
    $str .= pre_r($this->_columns, true);
    $str .=  "Rows:<br/>";
    $str .= pre_r($this->_rows, true);

    $str .=  "Frames:<br/>";
    $arr = array();
    foreach ( $this->_frames as $key => $val )
      $arr[$key] = array("columns" => $val["columns"], "rows" => $val["rows"]);

    $str .= pre_r($arr, true);

    if ( php_sapi_name() == "cli" )
      $str = strip_tags(str_replace(array("<br/>","<b>","</b>"),
                                    array("\n",chr(27)."[01;33m", chr(27)."[0m"),
                                    $str));
    return $str;
  }
}
