<?php

namespace GFPDF\Helper;

use GFPDF\Model\Model_PDF;

use Psr\Log\LoggerInterface;

use GFCommon;
use GFMultiCurrency;
use GPDFAPI;

use WP_Error;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Exception;

/**
 * Common Functions shared throughour Gravity PDF
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
 * @since  4.0
 */
class Helper_Misc {

	/**
	 * Holds abstracted functions related to the forms plugin
	 *
	 * @var \GFPDF\Helper\Helper_Form
	 *
	 * @since 4.0
	 */
	protected $form;

	/**
	 * Holds our log class
	 *
	 * @var \Monolog\Logger|LoggerInterface
	 *
	 * @since 4.0
	 */
	protected $log;

	/**
	 * Holds our Helper_Data object
	 * which we can autoload with any data needed
	 *
	 * @var \GFPDF\Helper\Helper_Data
	 *
	 * @since 4.0
	 */
	protected $data;

	/**
	 * Store required classes locally
	 *
	 * @param \Monolog\Logger|LoggerInterface    $log
	 * @param \GFPDF\Helper\Helper_Abstract_Form $form
	 * @param \GFPDF\Helper\Helper_Data          $data
	 *
	 * @since 4.0
	 */
	public function __construct( LoggerInterface $log, Helper_Abstract_Form $form, Helper_Data $data ) {

		/* Assign our internal variables */
		$this->log  = $log;
		$this->form = $form;
		$this->data = $data;
	}

	/**
	 * Check if the current admin page is a Gravity PDF page
	 *
	 * @since 4.0
	 *
	 * @return boolean
	 */
	public function is_gfpdf_page() {
		if ( is_admin() ) {
			if ( isset( $_GET['page'] ) && 'gfpdf-' === ( substr( $_GET['page'], 0, 6 ) ) ||
			     ( isset( $_GET['subview'] ) && 'PDF' === strtoupper( $_GET['subview'] ) )
			) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if we are on the current global settings page / tab
	 *
	 * @since 4.0
	 *
	 * @param string $name The current page ID to check
	 *
	 * @return boolean
	 */
	public function is_gfpdf_settings_tab( $name ) {
		if ( is_admin() ) {
			if ( $this->is_gfpdf_page() ) {
				$tab = ( isset( $_GET['tab'] ) ) ? $_GET['tab'] : 'general';

				if ( $name === $tab ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Gravity Forms has a 'type' for each field.
	 * Based on that type, attempt to match it to Gravity PDFs field classes
	 *
	 * @param string $type The field type we are looking up
	 *
	 * @return string|boolean       The Fully Qualified Namespaced Class we matched, or false
	 *
	 * @since 4.0
	 */
	public function get_field_class( $type ) {

		/* change our product field types to use a single master product class */
		$convert_product_type = array( 'quantity', 'option', 'shipping', 'total' );

		if ( in_array( strtolower( $type ), $convert_product_type ) ) {
			$type = 'product';
		}

		/* Format the type name correctly */
		$typeArray = explode( '_', $type );
		$typeArray = array_map( 'ucwords', $typeArray );
		$type      = implode( '_', $typeArray );

		/* See if we have a class that matches */
		$fqns = 'GFPDF\Helper\Fields\Field_';

		if ( class_exists( $fqns . $type ) ) {
			return $fqns . $type;
		}

		return false;
	}

	/**
	 * Converts a name into something a human can more easily read
	 *
	 * @param string $name The string to convert
	 *
	 * @return string
	 *
	 * @since  4.0
	 */
	public function human_readable( $name ) {
		$name = str_replace( array( '-', '_' ), ' ', $name );

		return mb_convert_case( $name, MB_CASE_TITLE );
	}

	/**
	 * Takes a full path to the file and converts it to the appropriate class name
	 * This follows the simple rules the file basename has its hyphens and spaces are converted to underscores
	 * then gets converted to sentence case using the underscore as a delimiter
	 *
	 * @param string $file The path to a file
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public function get_config_class_name( $file ) {
		$file = basename( $file, '.php' );
		$file = str_replace( array( '-', ' '), '_', $file );

		/* Using a delimiter with ucwords doesn't appear to work correctly so go old school */
		$file_array = explode( '_', $file );
		array_walk( $file_array, function( &$item ) {
			$item = mb_convert_case( $item, MB_CASE_TITLE, 'UTF-8' );
		} );

		$file = implode( '_', $file_array );

		return $file;
	}

	/**
	 * mPDF currently has no cascading CSS ability to target 'inline' elements. Fix image display issues in header / footer
	 * by adding a specific class name we can target
	 *
	 * @param string $html The HTML to parse
	 *
	 * @return string
	 */
	public function fix_header_footer( $html ) {

		try {
			/* Get the <img> from the DOM and extract required details */
			$wrapper = htmlqp( $html );

			$images = $wrapper->find( 'img' );

			if( sizeof( $images ) > 0 ) {
				/* Loop through each matching element */
				foreach ( $images as $image ) {
					/* Get the current image classes */
					$image_classes = $image->attr( 'class' );

					/* Remove width/height and add a override class */
					$image->removeAttr( 'width' )->removeAttr( 'height' )->addClass( 'header-footer-img' );

					if ( strlen( $image_classes ) > 0 ) {
						/* Wrap in a new div that includes the image classes */
						$image->wrap( '<div class="' . $image_classes . '"></div>' );
					}
				}

				return $wrapper->top( 'body' )->innerHTML5();
			}

			return $html;

		} catch ( Exception $e ) {
			/* if there was any issues we'll just return the $html */
			return $html;
		}
	}

	/**
	 * Processes a hex colour and returns an appopriately contrasting black or white
	 *
	 * @param string $hexcolor The Hex to be inverted
	 *
	 * @return string
	 *
	 * @since    4.0
	 */
	public function get_contrast( $hexcolor ) {
		$hexcolor = str_replace( '#', '', $hexcolor );

		if ( 6 !== strlen( $hexcolor ) ) {
			$hexcolor = str_repeat( substr( $hexcolor, 0, 1 ), 2 ) . str_repeat( substr( $hexcolor, 1, 1 ), 2 ) . str_repeat( substr( $hexcolor, 2, 1 ), 2 );
		}

		$r   = hexdec( substr( $hexcolor, 0, 2 ) );
		$g   = hexdec( substr( $hexcolor, 2, 2 ) );
		$b   = hexdec( substr( $hexcolor, 4, 2 ) );
		$yiq = ( ( $r * 299 ) + ( $g * 587 ) + ( $b * 114 ) ) / 1000;

		return ( $yiq >= 128 ) ? '#000' : '#FFF';
	}

	/**
	 * Change the brightness of the passed in colour
	 *
	 * $diff should be negative to go darker, positive to go lighter and
	 * is subtracted from the decimal (0-255) value of the colour
	 *
	 * @param         $hexcolor Hex colour to be modified
	 * @param integer $diff     amount to change the color
	 *
	 * @return string hex colour
	 *
	 * @since    4.0
	 */
	public function change_brightness( $hexcolor, $diff ) {

		$hexcolor = trim( str_replace( '#', '', $hexcolor ) );

		if ( 6 !== strlen( $hexcolor ) ) {
			$hexcolor = str_repeat( substr( $hexcolor, 0, 1 ), 2 ) . str_repeat( substr( $hexcolor, 1, 1 ), 2 ) . str_repeat( substr( $hexcolor, 2, 1 ), 2 );
		}

		$rgb = str_split( $hexcolor, 2 );

		foreach ( $rgb as &$hex ) {
			$dec = hexdec( $hex );
			$dec += $diff;
			$dec = max( 0, min( 255, $dec ) );
			$hex = str_pad( dechex( $dec ), 2, '0', STR_PAD_LEFT );
		}

		return '#' . implode( $rgb );
	}

	/**
	 * Pass in a background colour and get the appropriate contrasting background and border colour
	 *
	 * @param string $background_hex Hex colour to get contrast of
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function get_background_and_border_contrast( $background_hex ) {

		/* Get contrasting background colour */
		$background_color_contrast = $this->get_contrast( $background_hex );

		/* If the background isn't white we'll go down 20, otherwise go up 20 */
		$contrast_value = ( $background_color_contrast === '#FFF' ) ? 20 : -20;

		/* Get the new contrasting background colour */
		$contrast_background_color = $this->change_brightness( $background_hex, $contrast_value );

		/* Get the new border contrast based on the background contrast colour */
		$border_contrast = ( $background_color_contrast === '#FFF' ) ? 60 : -60;

		/* Finally get a contrasting border colour */
		$contrast_border_color = $this->change_brightness( $background_hex, $border_contrast );

		return array(
			'background' => $contrast_background_color,
			'border'     => $contrast_border_color,
		);
	}

	/**
	 * Push an associative array onto the beginning of an existing array
	 *
	 * @param  array  $array The array to push onto
	 * @param  string $key   The key to use for the newly-pushed array
	 * @param  mixed  $val   The value being pushed
	 *
	 * @return array  The modified array
	 *
	 * @since 4.0
	 */
	public function array_unshift_assoc( $array, $key, $val ) {
		$array         = array_reverse( $array, true );
		$array[ $key ] = $val;

		return array_reverse( $array, true );
	}

	/**
	 * This function recursively deletes all files and folders under the given directory, and then the directory itself
	 * equivalent to Bash: rm -r $dir
	 *
	 * @param string $dir The path to be deleted
	 *
	 * @return bool|WP_Error
	 *
	 * @since 4.0
	 */
	public function rmdir( $dir ) {

		try {
			$files = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator( $dir, RecursiveDirectoryIterator::SKIP_DOTS ),
				RecursiveIteratorIterator::CHILD_FIRST
			);

			foreach ( $files as $fileinfo ) {
				$function = ( $fileinfo->isDir() ) ? 'rmdir' : 'unlink';
				if ( ! @$function( $fileinfo->getRealPath() ) ) {
					throw new Exception( 'Could not run ' . $function . ' on  ' . $fileinfo->getRealPath() );
				}
			}
		} catch ( Exception $e ) {
			$this->log->addError( 'Filesystem Delete Error', array(
				'dir'       => $dir,
				'exception' => $e->getMessage(),
			) );

			return new WP_Error( 'recursion_delete_problem', $e );
		}

		return rmdir( $dir );
	}

	/**
	 * This function recursively copies all files and folders under a given directory
	 * equivalent to Bash: cp -R $dir
	 *
	 * @param  string $source      The path to be copied
	 * @param  string $destination The path to copy to
	 *
	 * @return boolean
	 *
	 * @since 4.0
	 */
	public function copyr( $source, $destination ) {

		try {
			if ( ! is_dir( $destination ) ) {
				if ( ! wp_mkdir_p( $destination ) ) {
					$this->log->addError( 'Failed Creating Folder Structure', array( 'dir' => $destination ) );
					throw new Exception( 'Could not create folder structure at ' . $destination );
				}
			}

			$files = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator( $source, RecursiveDirectoryIterator::SKIP_DOTS ),
				RecursiveIteratorIterator::SELF_FIRST
			);

			foreach ( $files as $fileinfo ) {
				if ( $fileinfo->isDir() && ! file_exists( $destination . $files->getSubPathName() ) ) {
					if ( ! @mkdir( $destination . $files->getSubPathName() ) ) {
						$this->log->addError( 'Failed Creating Folder', array( 'dir' => $destination . $files->getSubPathName() ) );
						throw new Exception( 'Could not create folder at ' . $destination . $files->getSubPathName() );
					}
				} elseif ( ! file_exists( $destination . $files->getSubPathName() ) ) {
					if ( ! @copy( $fileinfo, $destination . $files->getSubPathName() ) ) {
						$this->log->addError( 'Failed Creating File', array( 'file' => $destination . $files->getSubPathName() ) );
						throw new Exception( 'Could not create file at ' . $destination . $files->getSubPathName() );
					}
				}
			}
		} catch ( Exception $e ) {

			$this->log->addError( 'Filesystem Copy Error', array(
				'source'      => $source,
				'destination' => $destination,
				'exception'   => $e,
			) );

			return new WP_Error( 'recursion_copy_problem', $e );
		}

		return true;
	}

	/**
	 * Get a path relative to the root WP directory, provided a user hasn't moved the wp-content directory outside the ABSPATH
	 *
	 * @param  string $path    The relative path
	 * @param  string $replace What ABSPATH should be replaced with
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public function relative_path( $path, $replace = '' ) {
		return str_replace( ABSPATH, $replace, $path );
	}

	/**
	 * Check if the web server can write a file to the path specified
	 *
	 * @param  string $path The path to check
	 *
	 * @return boolean
	 *
	 * @since  4.0
	 */
	public function is_directory_writable( $path ) {
		$tmp_file = $path . '.tmpFile';

		if ( is_writable( $path ) ) {
			if ( touch( $tmp_file ) && is_file( $tmp_file ) ) {
				@unlink( $tmp_file );

				return true;
			}
		}

		return false;
	}

	/**
	 * Modified version of get_upload_path() which just focuses on the base directory
	 * no matter if single or multisite installation
	 * We also only needed the basedir and baseurl so stripped out all the extras
	 *
	 * @return array Base dir and url for the upload directory
	 *
	 * @since 4.0
	 */
	public function get_upload_details() {
		$siteurl     = get_option( 'siteurl' );
		$upload_path = trim( get_option( 'upload_path' ) );
		$dir         = $upload_path;

		if ( empty( $upload_path ) || 'wp-content/uploads' == $upload_path ) {
			$dir = WP_CONTENT_DIR . '/uploads';
		} elseif ( 0 !== strpos( $upload_path, ABSPATH ) ) {
			/* $dir is absolute, $upload_path is (maybe) relative to ABSPATH */
			$dir = path_join( ABSPATH, $upload_path );
		}

		if ( ! $url = get_option( 'upload_url_path' ) ) {
			if ( empty( $upload_path ) || ( 'wp-content/uploads' == $upload_path ) || ( $upload_path == $dir ) ) {
				$url = WP_CONTENT_URL . '/uploads';
			} else {
				$url = trailingslashit( $siteurl ) . $upload_path;
			}
		}

		/*
         * Honor the value of UPLOADS. This happens as long as ms-files rewriting is disabled.
         * We also sometimes obey UPLOADS when rewriting is enabled -- see the next block.
         */
		if ( defined( 'UPLOADS' ) && ! ( is_multisite() && get_site_option( 'ms_files_rewriting' ) ) ) {
			$dir = ABSPATH . UPLOADS;
			$url = trailingslashit( $siteurl ) . UPLOADS;
		}

		return array(
			'path' => $dir,
			'url'  => $url,
		);
	}

	/**
	 * Attempt to convert the current URL to an internal path
	 *
	 * @param  string $url The Url to convert
	 *
	 * @return string|boolean      Path on success or false on failure
	 *
	 * @since  4.0
	 */
	public function convert_url_to_path( $url ) {

		/* If $url is empty we'll return early */
		if ( trim( $url ) == false ) {
			return $url;
		}

		/* Mostly we'll be accessing files in the upload directory, so attempt that first */
		$upload = wp_upload_dir();

		$try_path = str_replace( $upload['baseurl'], $upload['basedir'], $url );

		if ( is_file( $try_path ) ) {
			return $try_path;
		}

		/* If WP_CONTENT_DIR and WP_CONTENT_URL are set we'll try them */
		if ( defined( 'WP_CONTENT_DIR' ) && defined( 'WP_CONTENT_URL' ) ) {
			$try_path = str_replace( WP_CONTENT_URL, WP_CONTENT_DIR, $url );

			if ( is_file( $try_path ) ) {
				return $try_path;
			}
		}

		/* Include our get_home_path functionality */
		if ( ! function_exists( 'get_home_path' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		/* If that didn't work let's try use home_url() and get_home_path() */
		$try_path = str_replace( home_url(), get_home_path(), $url );

		if ( is_file( $try_path ) ) {
			return $try_path;
		}

		/* If that didn't work let's try use site_url() and ABSPATH */
		$try_path = str_replace( site_url(), ABSPATH, $url );

		if ( is_file( $try_path ) ) {
			return $try_path;
		}

		/* If we are here we couldn't locate the file */

		return false;
	}

	/**
	 * Attempt to convert the current path to a URL
	 *
	 * @param  string $path The path to convert
	 *
	 * @return string|boolean      Url on success or false
	 *
	 * @since  4.0
	 */
	public function convert_path_to_url( $path ) {

		/* If $url is empty we'll return early */
		if ( trim( $path ) == false ) {
			return $path;
		}

		/* Mostly we'll be accessing files in the upload directory, so attempt that first */
		$upload = wp_upload_dir();

		$try_url = str_replace( $upload['basedir'], $upload['baseurl'], $path );

		if ( $try_url !== $path ) {
			return $try_url;
		}

		/* If WP_CONTENT_DIR and WP_CONTENT_URL are set we'll try them */
		if ( defined( 'WP_CONTENT_DIR' ) && defined( 'WP_CONTENT_URL' ) ) {
			$try_url = str_replace( WP_CONTENT_DIR, WP_CONTENT_URL, $path );

			if ( $try_url !== $path ) {
				return $try_url;
			}
		}

		/* Include our get_home_path functionality */
		if ( ! function_exists( 'get_home_path' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		/* If that didn't work let's try use home_url() and get_home_path() */
		$try_url = str_replace( get_home_path(), home_url(), $path );

		if ( $try_url !== $path ) {
			return $try_url;
		}

		/* If that didn't work let's try use site_url() and ABSPATH */
		$try_url = str_replace( ABSPATH, site_url(), $path );

		if ( $try_url !== $path ) {
			return $try_url;
		}

		/* If we are here we couldn't locate the file */

		return false;
	}

	/**
	 * Get the arguments array that should be passed to our PDF Template
	 *
	 * @param  array $entry    Gravity Form Entry
	 * @param  array $settings PDF Settings Array
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function get_template_args( $entry, $settings ) {
		global $gfpdf;

		/* Disable the field encryption checks which can slow down our entry queries */
		add_filter( 'gform_is_encrypted_field', '__return_false' );

		$form          = $this->form->get_form( $entry['form_id'] );
		$pdf           = GPDFAPI::get_mvc_class( 'Model_PDF' );
		$form_settings = GPDFAPI::get_mvc_class( 'Model_Form_Settings' );

		return apply_filters( 'gfpdf_template_args', array(

			'form_id'  => $entry['form_id'], /* backwards compat */
			'lead_ids' => $this->get_legacy_ids( $entry['id'], $settings ), /* backwards compat */
			'lead_id'  => apply_filters( 'gfpdfe_lead_id', $entry['id'], $form, $entry, $gfpdf ), /* backwards compat */

			'form'      => $form,
			'entry'     => $entry,
			'lead'      => $entry,
			'form_data' => $pdf->get_form_data( $entry ),
			'fields'    => $this->get_fields_sorted_by_id( $form['id'] ),
			'config'    => $form_settings->get_template_configuration( $settings['template'] ),

			'settings' => $settings,

			'gfpdf' => $gfpdf,

		), $entry, $settings, $form );
	}

	/**
	 * Do a lookup for the current template image (if any) and return the path
	 *
	 * @param  string $template The template name to look for
	 *
	 * @return string Full URL to image
	 *
	 * @since 4.0
	 */
	public function get_template_image( $template ) {

		/* Add our extension */
		$template .= '.png';

		$relative_image_path   = 'src/templates/images/';
		$default_template_path = PDF_PLUGIN_DIR . $relative_image_path;
		$default_template_url  = PDF_PLUGIN_URL . $relative_image_path;

		/* Multisite Location */
		if ( is_multisite() && is_file( $this->data->multisite_template_location . 'images/' . $template ) ) {
			return $this->data->multisite_template_location_url . 'images/' . $template;
		}

		/* Standard Location */
		if ( is_file( $this->data->template_location . 'images/' . $template ) ) {
			return $this->data->template_location_url . 'images/' . $template;
		}

		/* Core plugin file location */
		if ( is_file( $default_template_path . $template ) ) {
			return $default_template_url . $template;
		}

		return null;
	}

	/**
	 * Remove any characters that are invalid in filenames (mostly on Windows systems)
	 *
	 * @param  string $name The string / name to process
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public function strip_invalid_characters( $name ) {
		$characters = array( '/', '\\', '"', '*', '?', '|', ':', '<', '>' );

		return str_replace( $characters, '_', $name );
	}

	/**
	 * Replace all the merge tag fields in the string
	 *
	 * @param  string $string The string to process
	 * @param  array  $form   The Gravity Form array
	 * @param  array  $lead   The Gravity Form Entry Array
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public function do_mergetags( $string, $form, $lead ) {
		/* Unconvert { and } symbols from HTML entities and remove {all_fields} tag */
		$find    = array( '&#123;', '&#125;', '{all_fields}' );
		$replace = array( '{', '}', '' );

		$string = str_replace( $find, $replace, $string );

		return trim( GFCommon::replace_variables( $string, $form, $lead, false, false, false ) );
	}

	/**
	 * Backwards compatibility that allows multiple IDs to be passed to the renderer
	 *
	 * @param  integer $entry_id The fallback ID if none present
	 * @param  array   $settings The current PDF settings
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function get_legacy_ids( $entry_id, $settings ) {

		$leads    = rgget( 'lid' );
		$override = ( isset( $settings['public_access'] ) && $settings['public_access'] == 'Yes' ) ? true : false;

		if ( $leads && ( $override === true || $this->form->has_capability( 'gravityforms_view_entries' ) ) ) {
			$ids = explode( ',', $leads );

			/* ensure all passed ids are integers */
			array_walk( $ids, function( &$id ) {
				$id = (int) $id;
			} );

			/* filter our any zero-value ids */
			$ids = array_filter( $ids );

			if ( sizeof( $ids ) > 0 ) {
				return $ids;
			}
		}

		/* if not processing legacy endpoint, or if invalid IDs were passed we'll return the original entry ID */

		return array( $entry_id );
	}

	/**
	 * Add support for the third-party plugin GF Multi Currency
	 * https://github.com/ilanco/gravity-forms-multi-currency
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function maybe_add_multicurrency_support() {
		if ( class_exists( 'GFMultiCurrency' ) && method_exists( 'GFMultiCurrency', 'admin_pre_render' ) ) {
			$currency = GFMultiCurrency::init();
			add_filter( 'gform_form_post_get_meta', array( $currency, 'admin_pre_render' ) );
		}
	}

	/**
	 * Remove an extension from the end of a string
	 *
	 * @param  string $string
	 * @param  string $type The extension to remove from the end of the string
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public function remove_extension_from_string( $string, $type = '.pdf' ) {
		$type_length = mb_strlen( $type );

		if ( mb_strtolower( mb_substr( $string, -$type_length ) ) === mb_strtolower( $type ) ) {
			$string = mb_substr( $string, 0, -$type_length );
		}

		return $string;
	}

	/**
	 *  Convert our v3 boolean values into 'Yes' or 'No' responses
	 *
	 * @param  mixed $value
	 *
	 * @return mixed
	 *
	 * @since  4.0
	 */
	public function update_depreciated_config( $value ) {

		if ( is_bool( $value ) ) {
			$value = ( $value ) ? 'Yes' : 'No';
		}

		return $value;
	}

	/**
	 * Add an image of the current selected template (if any) to the template and default_template field descriptions
	 *
	 * @param array $settings Any existing settings loaded
	 *
	 * @since 4.0
	 *
	 * @return array
	 */
	public function add_template_image( $settings ) {
		global $gfpdf;

		if ( isset( $settings['template'] ) || isset( $settings['default_template'] ) ) {

			$key = ( isset( $settings['template'] ) ) ? 'template' : 'default_template';

			$current_template = $gfpdf->options->get_form_value( $settings[ $key ] );
			$template_image   = $this->get_template_image( $current_template );

			$settings[ $key ]['desc'] .= '<div id="gfpdf-template-example">';

			if ( ! empty( $template_image ) ) {
				$img = '<img src="' . esc_url( $template_image ) . '" />';
				$settings[ $key ]['desc'] .= $img;
			}

			$settings[ $key ]['desc'] .= '</div>';
		}

		return $settings;
	}

	/**
	 * Determine if the logic should show or hide the item
	 *
	 * @param array $logic
	 * @param array $entry The Gravity Forms entry object
	 *
	 * @return boolean Will always return true if item should be shown, or false if should be hidden
	 *
	 * @since 4.0
	 */
	public function evaluate_conditional_logic( $logic, $entry ) {

		/* exit early if type not found */
		if ( ! isset( $logic['actionType'] ) ) {
			return true;
		}

		$form = $this->form->get_form( $entry['form_id'] );

		/* Do the evaluation */
		$evaluation = GFCommon::evaluate_conditional_logic( $logic, $form, $entry );

		/* If the logic is to hide the item we'll invert the evaluation */
		if ( $logic['actionType'] !== 'show' ) {
			return ! $evaluation;
		}

		return $evaluation;
	}

	/**
	 * Check if running single or multisite and return the working directory path
	 *
	 * @return string Path to working directory
	 *
	 * @since 4.0
	 */
	public function get_template_path() {
		if( is_multisite() ) {
			return $this->data->multisite_template_location;
		}

		return $this->data->template_location;
	}

	/**
	 * Check if running single or multisite and return the working directory URL
	 *
	 * @return string URL to working directory
	 *
	 * @since 4.0
	 */
	public function get_template_url() {
		if( is_multisite() ) {
			return $this->data->multisite_template_location_url;
		}

		return $this->data->template_location_url;
	}

	/**
	 * Takes a Gravity Form ID and returns the list of fields which can be accessed using their ID
	 *
	 * @param integer $form_id The Gravity Form ID
	 *
	 * @return array The field array ordered by the field ID
	 *
	 * @since 4.0
	 */
	public function get_fields_sorted_by_id( $form_id ) {
		$form   = $this->form->get_form( $form_id );
		$fields = array();

		if ( isset( $form['fields'] ) && is_array( $form['fields'] ) ) {
			foreach ( $form['fields'] as $field ) {
				$fields[ $field->id ] = $field;
			}
		}

		return $fields;
	}

}
