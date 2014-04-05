<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: page_frame_decorator.cls.php,v $
 * Created on: 2004-06-15
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

/* $Id: page_frame_decorator.cls.php 216 2010-03-11 22:49:18Z ryan.masten $ */

/**
 * Decorates frames for page layout
 *
 * @access private
 * @package dompdf
 */
class Page_Frame_Decorator extends Frame_Decorator {

  /**
   * y value of bottom page margin
   *
   * @var float
   */
  protected $_bottom_page_margin;

  /**
   * Flag indicating page is full.
   *
   * @var bool
   */
  protected $_page_full;

  /**
   * Number of tables currently being reflowed
   *
   * @var int
   */
  protected $_in_table;

  /**
   * The pdf renderer
   *
   * @var Renderer
   */
  protected $_renderer;

  //........................................................................

  /**
   * Class constructor
   *
   * @param Frame $frame the frame to decorate
   */
  function __construct(Frame $frame, DOMPDF $dompdf) {
    parent::__construct($frame, $dompdf);
    $this->_page_full = false;
    $this->_in_table = 0;
    $this->_bottom_page_margin = null;
  }


  /**
   * Set the renderer used for this pdf
   *
   * @param Renderer $renderer the renderer to use
   */
  function set_renderer($renderer) {
    $this->_renderer = $renderer;
  }

  /**
   * Return the renderer used for this pdf
   *
   * @return Renderer
   */
  function get_renderer() {
    return $this->_renderer;
  }

  /**
   * Set the frame's containing block.  Overridden to set $this->_bottom_page_margin.
   *
   * @param float $x
   * @param float $y
   * @param float $w
   * @param float $h
   */
  function set_containing_block($x = null, $y = null, $w = null, $h = null) {
    parent::set_containing_block($x,$y,$w,$h);
    $w = $this->get_containing_block("w");
    if ( isset($h) )
      $this->_bottom_page_margin = $h; // - $this->_frame->get_style()->length_in_pt($this->_frame->get_style()->margin_bottom, $w);
  }

  /**
   * Returns true if the page is full and is no longer accepting frames.
   *
   * @return bool
   */
  function is_full() {
    return $this->_page_full;
  }

  /**
   * Start a new page by resetting the full flag.
   */
  function next_page() {
    $this->_renderer->new_page();
    $this->_page_full = false;
  }

  /**
   * Indicate to the page that a table is currently being reflowed.
   */
  function table_reflow_start() {
    $this->_in_table++;
  }

  /**
   * Indicate to the page that table reflow is finished.
   */
  function table_reflow_end() {
    $this->_in_table--;
  }

  /**
   * Return whether we are currently in a nested table or not
   *
   * @return bool
   */
  function in_nested_table() {
    return $this->_in_table > 1;
  }
  
  /**
   * Check if a forced page break is required before $frame.  This uses the
   * frame's page_break_before property as well as the preceeding frame's
   * page_break_after property.
   *
   * @link http://www.w3.org/TR/CSS21/page.html#forced
   *
   * @param Frame $frame the frame to check
   * @return bool true if a page break occured
   */
  function check_forced_page_break(Frame $frame) {

    // Skip check if page is already split
    if ( $this->_page_full )
      return;

    $block_types = array("block", "list-item", "table");
    $page_breaks = array("always", "left", "right");

    $style = $frame->get_style();

    if ( !in_array($style->display, $block_types) )
      return false;

    // Find the previous block-level sibling
    $prev = $frame->get_prev_sibling();
    while ( $prev && !in_array($prev->get_style()->display, $block_types) )
      $prev = $prev->get_prev_sibling();

    if ( in_array($style->page_break_before, $page_breaks) ) {

      // Prevent cascading splits
      $frame->split();
      // We have to grab the style again here because split() resets
      // $frame->style to the frame's orignal style.
      $frame->get_style()->page_break_before = "auto";
      $this->_page_full = true;
      return true;
    }

    if ( ($prev && in_array($prev->get_style()->page_break_after, $page_breaks)) ) {
      // Prevent cascading splits
      $frame->split();
      $prev->get_style()->page_break_after = "auto";
      $this->_page_full = true;
      return true;
    }

    return false;
  }

  /**
   * Determine if a page break is allowed before $frame
   *
   * @param Frame $frame the frame to check
   * @return bool true if a break is allowed, false otherwise
   */
  protected function _page_break_allowed(Frame $frame) {
    /**
     *
     * http://www.w3.org/TR/CSS21/page.html#allowed-page-breaks
     * /*
     * In the normal flow, page breaks can occur at the following places:
     *
     *    1. In the vertical margin between block boxes. When a page
     *    break occurs here, the used values of the relevant
     *    'margin-top' and 'margin-bottom' properties are set to '0'.
     *    2. Between line boxes inside a block box.
     *
     * These breaks are subject to the following rules:
     *
     *   * Rule A: Breaking at (1) is allowed only if the
     *     'page-break-after' and 'page-break-before' properties of
     *     all the elements generating boxes that meet at this margin
     *     allow it, which is when at least one of them has the value
     *     'always', 'left', or 'right', or when all of them are
     *     'auto'.
     *
     *   * Rule B: However, if all of them are 'auto' and the
     *     nearest common ancestor of all the elements has a
     *     'page-break-inside' value of 'avoid', then breaking here is
     *     not allowed.
     *
     *   * Rule C: Breaking at (2) is allowed only if the number of
     *     line boxes between the break and the start of the enclosing
     *     block box is the value of 'orphans' or more, and the number
     *     of line boxes between the break and the end of the box is
     *     the value of 'widows' or more.
     *
     *   * Rule D: In addition, breaking at (2) is allowed only if
     *     the 'page-break-inside' property is 'auto'.
     *
     * If the above doesn't provide enough break points to keep
     * content from overflowing the page boxes, then rules B and D are
     * dropped in order to find additional breakpoints.
     *
     * If that still does not lead to sufficient break points, rules A
     * and C are dropped as well, to find still more break points.
     *
     * [endquote]
     *
     * We will also allow breaks between table rows.  However, when
     * splitting a table, the table headers should carry over to the
     * next page (but they don't yet).
     */

    $block_types = array("block", "list-item", "table");
    dompdf_debug("page-break", "_page_break_allowed(" . $frame->get_node()->nodeName. ")");
    $display = $frame->get_style()->display;

    // Block Frames (1):
    if ( in_array($display, $block_types) ) {

      // Avoid breaks within table-cells
      if ( $this->_in_table ) {
        dompdf_debug("page-break", "In table: " . $this->_in_table);
        return false;
      }

      // Rules A & B

      if ( $frame->get_style()->page_break_before === "avoid" ) {
        dompdf_debug("page-break", "before: avoid");
        return false;
      }

      // Find the preceeding block-level sibling
      $prev = $frame->get_prev_sibling();
      while ( $prev && !in_array($prev->get_style()->display, $block_types) )
        $prev = $prev->get_prev_sibling();

      // Does the previous element allow a page break after?
      if ( $prev && $prev->get_style()->page_break_after === "avoid" ) {
        dompdf_debug("page-break", "after: avoid");
        return false;
      }

      // If both $prev & $frame have the same parent, check the parent's
      // page_break_inside property.
      $parent = $frame->get_parent();
      if ( $prev && $parent && $parent->get_style()->page_break_inside === "avoid" ) {
          dompdf_debug("page-break", "parent inside: avoid");
        return false;
      }

      // To prevent cascading page breaks when a top-level element has
      // page-break-inside: avoid, ensure that at least one frame is
      // on the page before splitting.
      if ( $parent->get_node()->nodeName === "body" && !$prev ) {
        // We are the body's first child
          dompdf_debug("page-break", "Body's first child.");
        return false;
      }

      // If the frame is the first block-level frame, use the value from
      // $frame's parent instead.
      if ( !$prev && $parent )
        return $this->_page_break_allowed( $parent );

      dompdf_debug("page-break", "block: break allowed");
      return true;

    }

    // Inline frames (2):
    else if ( in_array($display, Style::$INLINE_TYPES) ) {

      // Avoid breaks within table-cells
      if ( $this->_in_table ) {
          dompdf_debug("page-break", "In table: " . $this->_in_table);
        return false;
      }

      // Rule C
      $block_parent = $frame->find_block_parent();
      if ( count($block_parent->get_lines() ) < $frame->get_style()->orphans ) {
          dompdf_debug("page-break", "orphans");
        return false;
      }

      // FIXME: Checking widows is tricky without having laid out the
      // remaining line boxes.  Just ignore it for now...

      // Rule D
      $p = $block_parent;
      while ($p) {
        if ( $p->get_style()->page_break_inside === "avoid" ) {
          dompdf_debug("page-break", "parent->inside: avoid");
          return false;
        }
        $p = $p->find_block_parent();
      }

      // To prevent cascading page breaks when a top-level element has
      // page-break-inside: avoid, ensure that at least one frame with
      // some content is on the page before splitting.
      $prev = $frame->get_prev_sibling();
      while ( $prev && ($prev->get_node()->nodeName === "#text" && trim($prev->get_node()->nodeValue) == "") )
        $prev = $prev->get_prev_sibling();

      if ( $block_parent->get_node()->nodeName === "body" && !$prev ) {
        // We are the body's first child
          dompdf_debug("page-break", "Body's first child.");
        return false;
      }

      // Skip breaks on empty text nodes
      if ( $frame->get_node()->nodeName === "#text" &&
           $frame->get_node()->nodeValue == "" )
        return false;

      dompdf_debug("page-break", "inline: break allowed");
      return true;

    // Table-rows
    } else if ( $display === "table-row" ) {

      // Simply check if the parent table's page_break_inside property is
      // not 'avoid'
      $p = Table_Frame_Decorator::find_parent_table($frame);

      while ($p) {
        if ( $p->get_style()->page_break_inside === "avoid" ) {
          dompdf_debug("page-break", "parent->inside: avoid");
          return false;
        }
        $p = $p->find_block_parent();
      }

      // Avoid breaking after the first row of a table
      if ( $p && $p->get_first_child() === $frame) {
         dompdf_debug("page-break", "table: first-row");
        return false;
      }

      // If this is a nested table, prevent the page from breaking
      if ( $this->_in_table > 1 ) {
        dompdf_debug("page-break", "table: nested table");
        return false;
      }

      dompdf_debug("page-break","table-row/row-groups: break allowed");
      return true;

    } else if ( in_array($display, Table_Frame_Decorator::$ROW_GROUPS) ) {

      // Disallow breaks at row-groups: only split at row boundaries
      return false;

    } else {

      dompdf_debug("page-break", "? " . $frame->get_style()->display . "");
      return false;
    }

  }

  /**
   * Check if $frame will fit on the page.  If the frame does not fit,
   * the frame tree is modified so that a page break occurs in the
   * correct location.
   *
   * @param Frame $frame the frame to check
   * @return Frame the frame following the page break
   */
  function check_page_break(Frame $frame) {
    // Do not split if we have already
    if ( $this->_page_full )
      return false;

    // Determine the frame's maximum y value
    $max_y = $frame->get_position("y") + $frame->get_margin_height();

    // If a split is to occur here, then the bottom margins & paddings of all
    // parents of $frame must fit on the page as well:
    $p = $frame->get_parent();
    while ( $p ) {
      $style = $p->get_style();
      $max_y += $style->length_in_pt(array($style->margin_bottom,
                                           $style->padding_bottom,
                                           $style->border_bottom_width));
      $p = $p->get_parent();
    }


    // Check if $frame flows off the page
    if ( $max_y <= $this->_bottom_page_margin )
      // no: do nothing
      return false;

    dompdf_debug("page-break", "check_page_break");
    dompdf_debug("page-break", "in_table: " . $this->_in_table);


    // yes: determine page break location
    $iter = $frame;
    $flg = false;

    $in_table = $this->_in_table;

    dompdf_debug("page-break","Starting search");
    while ( $iter ) {
//       echo "\nbacktrack: " .$iter->get_node()->nodeName ." ".spl_object_hash($iter->get_node()). "";
      if ( $iter === $this ) {
         dompdf_debug("page-break", "reached root.");
        // We've reached the root in our search.  Just split at $frame.
        break;
      }

      if ( $this->_page_break_allowed($iter) ) {
        dompdf_debug("page-break","break allowed, splitting.");
        $iter->split();
        $this->_page_full = true;
        $this->_in_table = $in_table;
        return true;
      }

      if ( !$flg && $next = $iter->get_last_child() ) {
         dompdf_debug("page-break", "following last child.");

        if ( in_array($next->get_style()->display, Style::$TABLE_TYPES) )
          $this->_in_table++;

        $iter = $next;
        continue;
      }

      if ( $next = $iter->get_prev_sibling() ) {
         dompdf_debug("page-break", "following prev sibling.");

        if ( in_array($next->get_style()->display, Style::$TABLE_TYPES) &&
             !in_array($iter->get_style()->display, Style::$TABLE_TYPES) )
          $this->_in_table++;

        else if ( !in_array($next->get_style()->display, Style::$TABLE_TYPES) &&
                  in_array($iter->get_style()->display, Style::$TABLE_TYPES) )
          $this->_in_table--;

        $iter = $next;
        $flg = false;
        continue;
      }

      if ( $next = $iter->get_parent() ) {
         dompdf_debug("page-break", "following parent.");

        if ( in_array($iter->get_style()->display, Style::$TABLE_TYPES) )
          $this->_in_table--;

        $iter = $next;
        $flg = true;
        continue;
      }

      break;

    }

    $this->_in_table = $in_table;

    // No valid page break found.  Just break at $frame.
    dompdf_debug("page-break", "no valid break found, just splitting.");

    // If we are in a table, backtrack to the nearest top-level table row
    if ( $this->_in_table ) {
      $num_tables = $this->_in_table - 1;

      $iter = $frame;
      while ( $iter && $num_tables && $iter->get_style->display !== "table" ) {
        $iter = $iter->get_parent();
        $num_tables--;
      }

      $iter = $frame;
      while ($iter && $iter->get_style()->display !== "table-row" )
        $iter = $iter->get_parent();

      $iter->split();
      $this->_page_full = true;
      return true;
    }

    $frame->split();
    $this->_page_full = true;
    return true;

  }

  //........................................................................

  function split($frame = null) {
    // Do nothing
  }

}
