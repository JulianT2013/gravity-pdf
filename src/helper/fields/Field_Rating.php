<?php

namespace GFPDF\Helper\Fields;

use GFPDF\Helper\Helper_Fields;
use GFFormsModel;
use Exception;

/**
 * Gravity Forms Field
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2015, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/* Exit if accessed directly */
if (! defined('ABSPATH')) {
    exit;
}

/*
    This file is part of Gravity PDF.

    Gravity PDF Copyright (C) 2015 Blue Liquid Designs

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/**
 * Controls the display and output of a Gravity Form field
 *
 * @since 4.0
 */
class Field_Rating extends Helper_Fields
{

    /**
     * Display the HTML version of this field
     * @return String
     * @since 4.0
     */
    public function html() {
        $value = apply_filters('gform_entry_field_value', $this->get_value(), $this->field, $this->entry, $this->form);

        return '<div id="field-'. $this->field->id .'" class="gfpdf-'. $this->field->inputType .' gfpdf-field '. $this->field->cssClass . '">'
                    . '<div class="label"><strong>' . esc_html(GFFormsModel::get_label($this->field)) . '</strong></div>'
                    . '<div class="value">' . $value . '</div>'
                . '</div>';
    }

    /**
     * Get the standard GF value of this field
     * @return String/Array
     * @since 4.0
     */
    public function value() {
        if($this->has_cache()) {
            return $this->cache();
        }

        /* Process field */
        $items = explode(',', $this->get_value());

        $value  = array();

        /* Loop through each of the user-selected items */
        foreach($items as $rating) {

            /* Loop through the total choices */
            foreach($this->field->choices as $choice)
            {
                if(trim($choice['value']) == trim($rating))
                {
                    $value[] = $choice['text'];
                    break; /* exit inner loop as soon as found */
                }
            }
        }

        $this->cache($value); /* for backwards compatbility we'll wrap it in an array */
        
        return $this->cache();
    }
}