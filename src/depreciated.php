<?php

/* For backwards compatibility reasons this file will be in the global namespace */
use GFPDF\Router;
use GFPDF\Helper\Fields\Field_v3_Products;


/**
 * Depreciated Functionality / Classes
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2015, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
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
 * Add's an easy depreciated class abstract fallback
 *
 * @since 4.0
 */
abstract class GFPDF_Depreciated_Abstract {
	/**
	 * Add user depreciation notice for missing methods
	 *
	 * @since  4.0
	 *
	 * @param string $name      The function name to be called
	 * @param array  $arguments An enumerated array containing the parameters passed to the $name'ed method
	 */
	public function __call( $name, $arguments ) {
		trigger_error( sprintf( __( '"%s" has been depreciated as of Gravity PDF 4.0', 'gravity-forms-pdf-extended' ), $name ), E_USER_DEPRECATED );
	}

	/**
	 * Add user depreciation notice for missing methods
	 *
	 * @since  4.0
	 *
	 * @param string $name      The function name to be called
	 * @param array  $arguments An enumerated array containing the parameters passed to the $name'ed method
	 */
	public static function __callStatic( $name, $arguments ) {
		trigger_error( sprintf( __( '"%s" has been depreciated as of Gravity PDF 4.0', 'gravity-forms-pdf-extended' ), $name ), E_USER_DEPRECATED );
	}
}

/**
 * Add backwards compatibility support for our main core class
 *
 * @since 3.0
 */
class GFPDF_Core extends GFPDF_Depreciated_Abstract {

	/**
	 * Setup our v3 template location constants
	 *
	 * @since 4.0
	 */
	public function setup_constants() {

		$data = GPDFAPI:: get_data_class();
		$misc = GPDFAPI::get_misc_class();

		if ( ! defined( 'PDF_SAVE_LOCATION' ) ) {
			define( 'PDF_SAVE_LOCATION', $data->template_tmp_location );
		}

		if ( ! defined( 'PDF_FONT_LOCATION' ) ) {
			define( 'PDF_FONT_LOCATION', $data->template_font_location );
		}

		if ( ! defined( 'PDF_TEMPLATE_LOCATION' ) ) {
			define( 'PDF_TEMPLATE_LOCATION', $misc->get_template_path() );
		}

		if ( ! defined( 'PDF_TEMPLATE_URL_LOCATION' ) ) {
			define( 'PDF_TEMPLATE_URL_LOCATION', $misc->get_template_url() );
		}
	}

	/**
	 * Create aliases of our template path and URLs to match v3
	 *
	 * @since 4.0
	 */
	public function setup_depreciated_paths() {
		global $gfpdf, $gfpdfe_data;

		$gfpdfe_data = $gfpdf->data;

		$gfpdf->data->template_site_location = $gfpdf->misc->get_template_url();
		$gfpdf->data->template_save_location = $gfpdf->data->template_tmp_location;
	}
}

/**
 * Add backwards compatibility support for our PDF generator
 *
 * @since 3.0
 */
class PDFRender extends GFPDF_Depreciated_Abstract {

	/**
	 * Saves the PDF to disk
	 *
	 * @param string  $raw_pdf_string
	 * @param string  $filename
	 * @param integer $id
	 *
	 * @return string Returns the path to the file
	 *
	 * @throws Exception
	 *
	 * @since 3.0
	 */
	public function savePDF( $raw_pdf_string, $filename, $id ) {

		/* create our path */
		$path = PDF_SAVE_LOCATION . $id . '/';
		if ( ! is_dir( $path ) ) {
			if ( ! wp_mkdir_p( $path ) ) {
				throw new Exception( sprintf( 'Could not create directory: %s' ), esc_html( $path ) );
			}
		}

		/* save our PDF */
		if ( ! file_put_contents( $path . $filename, $raw_pdf_string ) ) {
			throw new Exception( sprintf( 'Could not save PDF: %s', $path . $filename ) );
		}

		/* return the path to the PDF */
		return $path . $filename;
	}

	/**
	 * Handles backwards compatibility support for our Tier 2 add on
	 *
	 * @param integer $form_id   The Gravity Form ID
	 * @param integer $lead_id   The Gravity Form Entry ID
	 * @param string  $template  The PDF template name
	 * @param integer $id        The spliced form ID and entry ID
	 * @param string  $output    The PDF output method
	 * @param string  $filename  The PDF filename
	 * @param array   $arguments Any additional arguments to be passed
	 * @param array   $args      The same as $arguments
	 *
	 * @since 4.0
	 *
	 * @return integer The Gravity Form ID
	 */
	public static function prepare_ids( $form_id, $lead_id, $template, $id, $output, $filename, $arguments, $args ) {
		global $lead_ids;
		$lead_ids = $args['lead_ids'];

		return $form_id;
	}
}

/**
 * Add backwards compatibility support for our common class
 *
 * @since 3.0
 */
class PDF_Common extends GFPDF_Depreciated_Abstract {

	/**
	 * Takes over for setup_ids() but is now called much earlier in the process
	 *
	 * @return boolean
	 *
	 * @since 4.0
	 */
	public static function get_ids() {
		global $form_id, $lead_id, $lead_ids;

		$form_id  = ( $form_id ) ? $form_id : absint( rgget( 'fid' ) );
		$lead_ids = ( $lead_id ) ? array( $lead_id ) : explode( ',', rgget( 'lid' ) );

		/* If form ID and lead ID hasn't been set stop the PDF from attempting to generate */
		if ( empty( $form_id ) || empty( $lead_ids ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get the base upload directory details
	 *
	 * @return array
	 *
	 * @since 3.0
	 */
	public static function get_upload_dir() {
		$misc = GPDFAPI::get_misc_class();
		return $misc->get_upload_details();
	}

	/**
	 * Convert merge tags to real Gravity Form values
	 *
	 * @param  string  $string
	 * @param  integer $form_id
	 * @param  integer $lead_id
	 *
	 * @return string
	 *
	 * @since 3.0
	 */
	public static function do_mergetags( $string, $form_id, $lead_id ) {
		$misc = GPDFAPI::get_misc_class();
		$form = GPDFAPI::get_form_class();

		return $misc->do_mergetags( $string, $form->get_form( $form_id ), $form->get_entry( $lead_id ) );
	}

	/**
	 * Allow users to view the $form_data array, if it exists
	 *
	 * @param array $form_data
	 *
	 * @since 4.0
	 */
	public static function view_data( $form_data ) {
		$form = GPDFAPI::get_form_class();

		if ( isset( $_GET['data'] ) && $form->has_capability( 'gravityforms_view_settings' ) ) {
			print '<pre>';
			print_r( $form_data );
			print '</pre>';
			exit;
		}
	}

	/**
	 * Get $_POST key, or return nothing
	 *
	 * @param string $name Key Name
	 *
	 * @return string
	 *
	 * @since 3.0
	 */
	public static function post( $name ) {
		if ( isset( $_POST[ $name ] ) ) {
			return $_POST[ $name ];
		}

		return '';
	}

	/**
	 * Get $_GET key, or return nothing
	 *
	 * @param string $name Key Name
	 *
	 * @return string
	 *
	 * @since 3.0
	 */
	public static function get( $name ) {
		if ( isset( $_GET[ $name ] ) ) {
			return $_GET[ $name ];
		}

		return '';
	}

	/**
	 * Get the name of the PDF based on the Form and the submission
	 *
	 * @param integer $form_id
	 * @param integer $lead_id
	 *
	 * @return string
	 *
	 * @since 3.0
	 */
	public static function get_pdf_filename( $form_id, $lead_id ) {
		return "form-$form_id-entry-$lead_id.pdf";
	}

	/**
	 * Remove any characters that are invalid in filenames (mostly on Windows systems)
	 *
	 * @param string $name The string / name to process
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public static function remove_invalid_characters( $name ) {
		$misc = GPDFAPI::get_form_class();
		return $misc->strip_invalid_characters( $name );
	}
}

/**
 * Add depreciated functionality for generating our standard PDF HTML
 *
 * @since 3.0
 */
class GFPDFEntryDetail extends GFPDF_Depreciated_Abstract {

	/**
	 * First legacy wrapper to generate our PDF HTML layout
	 *
	 * @param  array   $form The Gravity Form array
	 * @param  array   $lead The Gravity Form entry
	 * @param  boolean $allow_display_empty_fields
	 * @param  boolean $show_html
	 * @param  boolean $show_page_name
	 * @param  boolean $return
	 *
	 * @return string  If $return is `true` the generated HTML will be returned
	 *
	 * @since 3.0
	 */
	public static function lead_detail_grid( $form, $lead, $allow_display_empty_fields = false, $show_html = false, $show_page_name = false, $return = false ) {
		$config = array(
			'meta' => array(
				'empty_field' => $allow_display_empty_fields,
				'return'      => $return,
				'html_field'  => $show_html,
				'page_names'  => $show_page_name,
			),
		);

		return self::do_lead_detail_grid( $form, $lead, $config );
	}

	/**
	 * Second legacy wrapper to generate our PDF HTML layout
	 *
	 * @param  array $form   The Gravity Form array
	 * @param  array $lead   The Gravity Form entry
	 * @param  array $config The PDF Configuration
	 *
	 * @return string        If $config['meta']['echo'] is false the HTML will be returned
	 *
	 * @since 3.7
	 */
	public static function do_lead_detail_grid( $form, $lead, $config = array() ) {

		/* Convert old config values to our new ones */
		if ( ! isset( $config['meta'] ) ) {
			$config = array( 'meta' => $config );
		}

		return self::generate_v3_html_structure( $form, $lead, $config['meta'] );
	}

	/**
	 * Loop through our form and output the results
	 *
	 * @param  array $form   The Gravity Form array
	 * @param  array $lead   The Gravity Form entry
	 * @param  array $config The PDF Configuration
	 *
	 * @return array|void
	 *
	 * @since 4.0
	 */
	public static function generate_v3_html_structure( $form, $lead, $config ) {

		/* Setup our variables */
		$model        = GPDFAPI::get_mvc_class( 'Model_PDF' );
		$products     = new Field_v3_Products( new GF_Field(), $lead, GPDFAPI::get_form_class(), GPDFAPI::get_misc_class() );
		$has_products = false;
		$page_number  = 0;

		/* Change the standardised HTML field output to be v3 compatible */
		add_filter( 'gfpdf_field_html_value', array( 'GFPDFEntryDetail', 'legacy_html_format'), 10, 5 );
		add_filter( 'gfpdf_field_class', array( 'GFPDFEntryDetail', 'load_legacy_html_classes'), 10, 3 );

		/* Setup field to return the form data, if needed */
		$results = array(
			'title' => '',
			'field' => array(),
		);

		/* Output the form title */
		if ( $config['return'] ) {
			$results['title'] = $styles . '<h2 id="details" class="default">' . $form['title'] . '</h2>';
		} else {
			?>

			<?php echo $styles ; ?>
			<div id='container'>
			<h2 id='details' class='default'><?php echo $form['title'] ?></h2>
			<?php
		}

		/* Loop through our fields and output */
		foreach ( $form['fields'] as $field ) {

			/* Skip any fields with the css class 'exclude' or any hidden fields */
			if ( strpos( $field->cssClass, 'exclude' ) !== false  || GFFormsModel::is_field_hidden( $form, $field, array(), $lead ) ) {
				continue;
			}

			/* Skip over any product fields, if needed */
			if ( GFCommon::is_product_field( $field->type ) ) {
				$has_products = true;
				continue;
			}

			/* Check if we should display the page names */
			if ( $config['page_names'] === true && (int) $field->pageNumber !== $page_number && isset( $form['pagination']['pages'][ $page_number ] ) ) {
				if ( $config['return'] ) {
					$results['field'][] = '<h2 id="field-' . $field->id . '" class="default entry-view-page-break">' . $form['pagination']['pages'][ $page_number ] . '</h2>';
				} else {
					?>
					<h2 id="field-<?php echo $field->id; ?>"
					    class="default entry-view-page-break"><?php echo $form['pagination']['pages'][ $page_number ]; ?></h2>
					<?php
				}
				$page_number++;
			}

			/* Output each field type */
			$input = RGFormsModel::get_input_type( $field );
			$excluded = array( 'captcha', 'password', 'page' );

			/* Skip over any fields we don't want to include */
			if( in_array( $input, $excluded ) ) {
				continue;
			}

			/* Load our class */
			$class = $model->get_field_class( $field, $form, $lead, $products );

			self::load_legacy_css( $field );

			/* Check if HTML field should be included */
			if( $input == 'html' ) {

				if( $config['html_field'] === true ) {
					$html = $class->html();

					if ( $config['return'] ) {
						$results['field'][] = $html;
					} else {
						echo $html;
					}
				}

				continue;
			}

			/* Only load our HTML if the field is NOT empty, or the 'empty_field' config option is true */
			if ( $config['empty_field'] === true || ! $class->is_empty() ) {

				$html = ( $field->type !== 'section' ) ? $class->html() : $class->html( $config['section_content'] );

				if ( $config['return'] ) {
					$results['field'][] = $html;
				} else {
					echo $html;
				}
			}
		}

		/* Output product table, if needed */
		if ( $has_products && ! $products->is_empty() ) {
			$products = $products->html();

			if ( $config['return'] ) {
				$results['field'][] = $products;
			} else {
				echo $products;
			}
		}

		/* Return the HTML structure */
		if ( $config['return'] ) {
			return $results;
		} else {
			?>
			</div><!-- Close container -->
			<?php
		}
	}

	/**
	 * Replaces the v4 HTML structure with the legacy v3 code to prevent backwards compatibility problems
	 *
	 * @param string $html The original field HTML which we'll be discarding
	 * @param string $value The field value
	 * @param boolean $label Whether to show or hide the field's label
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public static function legacy_html_format( $html, $value, $show_label, $label, $field ) {

		$html = '<div id="field-' . $field->id . '" class="' . $field->cssClass . '">';

		if ( $show_label ) {
			$html .= '<div class="strong">' . $label . '</div>';
		}

		/* If the field value is empty we'll add a non-breaking space to act like a character and maintain proper layout */
		if ( strlen( trim( $value ) ) === 0 ) {
			$value = '&nbsp;';
		}

		$html .= '<div class="value">' . $value . '</div>'
		         . '</div>';

		return $html;
	}

	/**
	 * Replace some of our Gravity PDF fields with legacy versions to match the old HTML structure
	 *
	 * @param object $class The original Gravity PDF field class being processed
	 * @param object $field The Gravity Form field object being processed
	 * @param array $entry The current Gravity Form array of entry data
	 *
	 * @return object
	 *
	 * @since 4.0
	 */
	public static function load_legacy_html_classes( $class, $field, $entry ) {

		switch( get_class( $field ) ) {
			case 'GF_Field_Section':
				$class = new GFPDF\Helper\Fields\Field_v3_Section( $field, $entry, GPDFAPI::get_form_class(), GPDFAPI::get_misc_class() );
			break;

			case 'GF_Field_List':
				$class = new GFPDF\Helper\Fields\Field_v3_List( $field, $entry, GPDFAPI::get_form_class(), GPDFAPI::get_misc_class() );
			break;
		}

		return $class;
	}

	/**
	 * Our default template used a number of legacy classes.
	 * To keep backwards compatible, we will manually assign when needed.
	 *
	 * @param  GF_Field $field The Gravity Form Fields
	 *
	 * @return void (classes are passed by reference)
	 *
	 * @since 4.0
	 */
	public static function load_legacy_css( GF_Field $field ) {
		static $counter = 1;

		/* Because multiple PDFs can be processed at the same time and will share the same field classes we'll only update the css once */
		if ( strpos( $field->cssClass, 'gfpdf-field-processed' ) !== false ) {
			return;
		}

		/* Add odd / even rows */
		$field->cssClass = ( $counter++ % 2 ) ? $field->cssClass . ' odd' : ' even';

		switch ( $field->type ) {
			case 'html':
				$field->cssClass .= ' entry-view-html-value';
			break;

			case 'section':
				$field->cssClass .= ' entry-view-section-break-content';
			break;

			default:
				$field->cssClass .= ' entry-view-field-value';
			break;
		}

		$field->cssClass .= ' gfpdf-field-processed';
	}

	/**
	 * Get the $form_data array
	 *
	 * @param array $form The Gravity Form array
	 * @param array $lead The Gravity Form entry
	 *
	 * @return array
	 *
	 * @since 3.0
	 */
	public static function lead_detail_grid_array( $form, $lead ) {
		$model = GPDFAPI::get_pdf_class( 'model' );

		return $model->get_form_data( $lead );
	}

	/**
	 * Generate a standard Gravity Forms product table based on the form / entry data
	 *
	 * @param array $form The Gravity Form array
	 * @param array $lead The Gravity Form entry
	 *
	 * @return void
	 *
	 * @since 3.0
	 */
	public static function product_table( $form, $lead ) {
		GPDFAPI::product_table( $lead );
	}

	/**
	 * Public method for outputting likert (survey addon field)
	 *
	 * @param array   $form     The Gravity Form array
	 * @param array   $lead     The Gravity Form entry
	 * @param integer $field_id The field ID to output
	 *
	 * @return string
	 *
	 * @since 3.0
	 */
	public static function get_likert( $form, $lead, $field_id ) {
		return GPDFAPI::likert_table( $lead, $field_id, true );
	}
}

/**
 * Classes included for backwards compatibility
 *
 * @since 3.0
 */
class PDFGenerator extends GFPDF_Depreciated_Abstract {
}

class GFPDFE_DATA extends GFPDF_Depreciated_Abstract {
}

class GFPDF_InstallUpdater extends GFPDF_Depreciated_Abstract {
}

class GFPDF_Notices extends GFPDF_Depreciated_Abstract {
}

class PDF_Generator extends GFPDF_Depreciated_Abstract {
}

class GFPDF_Core_Model extends GFPDF_Depreciated_Abstract {
}

class GFPDF_Settings_Model extends GFPDF_Depreciated_Abstract {
}

class GFPDF_Settings extends GFPDF_Depreciated_Abstract {
}
