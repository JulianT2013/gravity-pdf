<?php

namespace GFPDF\Helper;

use GFPDF\Model\Model_PDF;

use GFCommon;
use mPDF;

use Exception;

/**
 * Generates our PDF document using mPDF
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2015, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) exit;

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
 * @since 4.0
 */
class Helper_PDF {

    /**
     * Holds our PDF Object
     * @var Object
     * @since 4.0
     */
    public $mpdf;

    /**
     * Holds our Gravity Form Entry Details
     * @var Array
     * @since 4.0
     */
    protected $entry;

    /**
     * Holds our PDF Settings
     * @var Array
     * @since 4.0
     */
    protected $settings;

    /**
     * Controls how the PDF should be output.
     * Whether to display it in the browser, force a download, or save it to disk
     * @var string
     * @since 4.0
     */
    protected $output = 'DISPLAY';

    /**
     * Holds the predetermined paper size
     * @var Mixed (String or Array)
     * @since 4.0
     */
    protected $paper_size;

    /**
     * Holds our paper orientation in mPDF flavour
     * @var String
     * @since 4.0
     */
    protected $orientation;

    /**
     * Holds the full path to the PHP template to load
     * @var String
     * @since 4.0
     */
    protected $template_path;

    /**
     * Holds the PDF filename that should be used
     * @var String
     * @since 4.0
     */
    protected $filename;

    /**
     * Holds the path the PDF should be saved to
     * @var String
     * @since 4.0
     */
    protected $path;

    /**
     * Whether to force the print dialog when the PDF is opened
     * @var boolean
     * @since 4.0
     */
    protected $print = false;

    /**
     * Initialise our class
     * @param Array $entry    The Gravity Form Entry to be processed
     * @param Array $settings The Gravity PDF Settings Array
     * @since 4.0
     */
    public function __construct($entry, $settings) {
        $this->entry = $entry;
        $this->settings = $settings;
    }

    /**
     * A public method to start our PDF creation process
     * @return void
     * @since 4.0
     */
    public function init() {
        $this->setPaper();
        $this->setFilename();
        $this->beginPdf();
        $this->setImageDpi();
        $this->setTextDirection();
        $this->setPdfFormat();
        $this->setPdfSecurity();
    }

    /**
     * Render the HTML to our PDF
     * @param  Array  $args Any arguments that should be passed to the PDF template
     * @param  String $html By pass the template  file and pass in a HTML string directly to the engine. Optional.
     * @return void
     * @since 4.0
     */
    public function renderHtml($args = array(), $html = '') {
        /* Load in our PHP template */
        if(empty($html)) {
            $this->setTemplate();
            $html = $this->loadHtml($args);
        }

        /* check if we should output the HTML to the browser, for debugging */
        $this->maybeDisplayRawHtml($html);

        /* write the HTML to mPDF */
        $this->mpdf->WriteHTML($html);
    }

    /**
     * Create the PDF
     * @return void
     * @since 4.0
     */
    public function generate() {

        /* Process any final settings before outputting */
        $this->showPrintDialog();

        /* allow $mpdf object class to be modified */
        apply_filters('gfpdf_mpdf_class', $this->mpdf, $this->entry, $this->settings);

        apply_filters('gfpdfe_mpdf_class_pre_render', $this->mpdf, $this->entry['form_id'], $this->entry['id'], $this->settings, '', $this->getFilename()); /* backwards compat */
        apply_filters('gfpdfe_pre_render_pdf', $this->mpdf, $this->entry['form_id'], $this->entry['id'], $this->settings, '', $this->getFilename()); /* backwards compat */
        apply_filters('gfpdfe_mpdf_class', $this->mpdf, $this->entry['form_id'], $this->entry['id'], $this->settings, '', $this->getFilename()); /* backwards compat */

        /* If a developer decides to disable all security protocols we don't want the PDF indexed */
        if (!headers_sent()) {
            header( 'X-Robots-Tag: noindex, nofollow', true);
        }

        switch($this->output) {
            case 'DISPLAY':
                $this->mpdf->Output($this->filename, 'I');
                exit;
            break;

            case 'DOWNLOAD':
                $this->mpdf->Output($this->filename, 'D');
                exit;
            break;

            case 'SAVE':
                return $this->mpdf->Output('', 'S');
            break;
        }
    }

    /**
     * Save the PDF to our tmp directory
     * @param  String $raw_pdf_string  The generated PDF to be saved
     * @return Mixed                   The full path to the file or false if failed
     * @since  4.0
     */
    public function savePdf($raw_pdf_string) {

        if(empty($this->path)) {
            $this->setPath();
        }
        
        /* create our path */
        if(! is_dir($this->path)) {
            if(! wp_mkdir_p($this->path)) {
                throw new Exception(sprintf('Could not create directory: %s'), esc_html($this->path));
            }
        }

        /* save our PDF */
        if(! file_put_contents($this->path . $this->filename, $raw_pdf_string)) {
            throw new Exception(sprintf('Could not save PDF: %s', $this->path . $this->filename));
        }

        return $this->path . $this->filename;
    }

    /**
     * Public endpoint to allow users to control how the generated PDF will be displayed
     * @param String $type Only display, download or save options are valid
     * @since 4.0
     */
    public function setOutputType($type) {
        $valid = array('DISPLAY', 'DOWNLOAD', 'SAVE');

        if(! in_array(strtoupper($type), $valid)) {
            throw new Exception(sprintf('Display type not valid. Use %s', implode(', ', $valid)));
            return;
        }

        $this->output = strtoupper($type);
    }


    /**
     * Public Method to mark the PDF document creator
     * @return void
     * @since 4.0
     */
    public function setCreator($text = '') {
        if(empty($text)) {
            $this->mpdf->SetCreator('Gravity PDF v' . PDF_EXTENDED_VERSION . '. https://gravitypdf.com');
        } else {
            $this->mpdf->SetCreator($text);
        }
    }

    /**
     * Public Method to set how the PDF should be displyed when first open
     * @param Mixed $mode A string or integer setting the zoom mode
     * @param String $layout The PDF layout format
     * @return void
     * @since 4.0
     */
    public function setDisplayMode($mode = 'fullpage', $layout = 'continuous') {

        $valid_mode = array('fullpage', 'fullwidth', 'real', 'default');
        $valid_layout = array('single', 'continuous', 'two', 'twoleft', 'tworight', 'default');

        /* check the mode */
        if(! in_array(strtolower($mode), $valid_mode)) {
            /* determine if the mode is an integer */
            if(!is_int( $mode ) || $mode <= 10) {
                throw new Exception(sprintf('Mode must be an number value more than 10 or one of these types: %s', implode(', ', $valid_mode)));
            }
        }

        /* check theh layout */
        if(! in_array(strtolower($mode), $valid_mode)) {
            throw new Exception(sprintf('Layout must be one of these types: %s', implode(', ', $valid_mode)));
        }

        $this->mpdf->SetDisplayMode($mode, $layout);
    }


    /**
     * Public Method to allow the print dialog to be display when PDF is opened
     * @param boolean $print
     * @return void
     * @since 4.0
     */
    public function setPrintDialog($print = true) {
        if(! is_bool($print)) {
            throw new Exception('Only boolean values true and false can been passed to setPrintDialog().');
        }

        $this->print = $print;
    }

    /**
     * Generic PDF JS Setter function
     * @param String $js The PDF Javascript to execute
     * @since 4.0
     */
    public function setJS($js) {
        $this->mpdf->SetJS($js);
    }

    /**
     * Get the current PDF Name
     * @return String
     * @since 4.0
     */
    public function getFilename() {
        return $this->filename;
    }

    /**
     * Get the current Gravity Form Entry
     * @return String
     * @since 4.0
     */
    public function getEntry() {
        return $this->entry;
    }

    /**
     * Get the current PDF Settings
     * @return String
     * @since 4.0
     */
    public function getSettings() {
        return $this->settings;
    }
    /**
     * Generate the PDF filename used
     * @return void
     * @since 4.0
     */
    public function setFilename() {
        /* Process mergetags */
        $model = new Model_PDF();
        $this->filename = $this->getExtension($model->get_pdf_name($this->settings, $this->entry), '.pdf');
    }

    /**
     * Get the current PDF path
     * @return String
     * @since 4.0
     */
    public function getPath() {
        return $this->path;
    }

    /**
     * Sets the path the PDF should be saved to
     * @param string $path
     * @return void
     * @since 4.0
     */
    public function setPath($path = '') {
        global $gfpdf;

        if(empty($path)) {
            /* build our PDF path location */
            $path = $gfpdf->data->template_tmp_location . '/' . $this->entry['form_id'] . $this->entry['id'] . '/';
        } else {
            /* ensure the path ends with a forward slash */
            if(substr($path, -1) !== '/') {
                $path .= '/';
            }
        }

        $this->path = $path;
    }

    /**
     * Initialise our mPDF object
     * @return void
     * @since 4.0
     */
    protected function beginPdf() {
        $this->mpdf = new mPDF('', $this->paper_size, 0, '', 15, 15, 16, 16, 9, 9, $this->orientation);
    }

    /**
     * Set up the paper size and orentation
     * @return void
     * @since 4.0
     */
    protected function setPaper() {

        /* Get the paper size from the settings */
        $paper_size = (isset($this->settings['pdf_size'])) ? strtoupper($this->settings['pdf_size']) : 'A4';

        $valid_paper_size = array(
            '4A0', '2A0',
            'A0', 'A1', 'A2', 'A3', 'A4', 'A5', 'A6', 'A7', 'A8', 'A9', 'A10',
            'B0', 'B1', 'B2', 'B3', 'B4', 'B5', 'B6', 'B7', 'B8', 'B9', 'B10',
            'C0', 'C1', 'C2', 'C3', 'C4', 'C5', 'C6', 'C7', 'C8', 'C9', 'C10',
            'RA0', 'RA1', 'RA2', 'RA3', 'RA4',
            'SRA0', 'SRA1', 'SRA2', 'SRA3', 'SRA4',
            'LETTER', 'LEGAL', 'LEDGER', 'TABLOID', 'EXECUTIVE', 'FOILIO', 'B', 'A', 'DEMY', 'ROYAL', 'CUSTOM'
        );

        if(! in_array($paper_size, $valid_paper_size)) {
            throw new Exception(sprintf('Paper size not valid. Use %s', implode(', ', $valid)));
            return;
        }
        
        /* set our paper size and orientation based on user selection */
        if($paper_size == 'CUSTOM') {
            $this->setCustomPaperSize();
            $this->setOrientation(true);
        } else {
            $this->setPaperSize($paper_size);
            $this->setOrientation();
        }
    }

    /**
     * Set our paper size using pre-defined values
     * @return void
     * @since 4.0
     */
    protected function setPaperSize($size) {
        $this->paper_size = $size;
    }

    /**
     * Set our custom paper size which will be a 2-key array signifying the
     * width and height of the paper stock
     * @return void
     * @since 4.0
     */
    protected function setCustomPaperSize() {
        $custom_paper_size = (isset($this->settings['custom_paper_size'])) ? $this->settings['custom_paper_size'] : array();

        if(sizeof($custom_paper_size) !== 3) {
            throw new Exception('Custom paper size not valid. Array should contain three keys: width, height and unit type');
        }

        $this->paper_size = $this->getPaperSize($custom_paper_size);
        
    }

    /**
     * Ensure the custom paper size has the correct values
     * @param  Array $size
     * @return Array
     * @since  4.0
     */
    protected function getPaperSize($size) {
        $size[0] = ($size[2] == 'inches') ? (int) $size[0] * 25.4 : (int) $size[0];
        $size[1] = ($size[2] == 'inches') ? (int) $size[1] * 25.4 : (int) $size[1];

        /* tidy up custom paper size array */
        unset($size[2]);

        return $size;
    }

    /**
     * Set the page orientation based on the paper size selected
     * @param Boolean $custom Whether a predefined paper size was used, or a custom size
     * @return void
     * @since 4.0
     */
    protected function setOrientation($custom = false) {

        $orientation = (isset($this->settings['orientation'])) ? strtolower($this->settings['orientation']) : 'portrait';

        if($custom) {
            $this->orientation = ($orientation == 'landscape') ? 'L' : 'P';
        } else {
            $this->orientation = ($orientation == 'landscape') ? '-L' : '';
        }
    }

    /**
     * Get the correct path to the PHP template we should load into mPDF
     * @return void
     * @since 4.0
     */
    protected function setTemplate() {
        global $gfpdf;

        $template = (isset($this->settings['template'])) ? $this->getExtension($this->settings['template']) : '';

        /* Allow a user to change the current template if they have the appropriate capabilities */
        if(rgget('template') && is_user_logged_in() && GFCommon::current_user_can_any( 'gravityforms_edit_settings' )) {
            $template = $this->get_template_filename(rgget('template'));
        }

        /**
         * Check for the template's existance
         * We'll first look for a user-overridding template
         * Then check our default templates
         */
        $default_template_path = PDF_PLUGIN_DIR . 'initialisation/templates/';

        if(is_file( $gfpdf->data->template_location . $template )) {
            $this->template_path = $gfpdf->data->template_location . $template;
        } else if( is_file( $default_template_path . $template)) {
            $this->template_path = $default_template_path . $template;
        } else {
            throw new Exception('Could not find the template: ' . esc_html($template));
        }
    }


    /**
     * Ensure an extension is added to the end of the name
     * @param  String $name The PHP template
     * @return String
     * @since  4.0
     */
    protected function getExtension($name, $extension = '.php') {
        if(substr($name, -strlen($extension)) !== $extension) {
            $name = $name . $extension;
        }

        return $name;
    }

    /**
     * Load our PHP template file and return the buffered HTML
     * @return String The buffered HTML to pass into mPDF
     * @since 4.0
     */
    protected function loadHtml($args = array()) {
        /* for backwards compatibility extract the $args variable */
        extract($args, EXTR_SKIP); /* skip any arguments that would clash - i.e filename, args, output, path, this */

        ob_start();
        include $this->template_path;
        return ob_get_clean();
    }


    /**
     * Allow site admins to view the RAW HTML if needed
     * @param  String $html
     * @return void
     * @since 4.0
     */
    protected function maybeDisplayRawHtml($html) {
        if($this->output !== 'SAVE' && rgget('html') && GFCommon::current_user_can_any( 'gravityforms_edit_settings' )) {
            echo $html;
            exit;
        }
    }

    /**
     * Prompt the print dialog box
     * @return void
     * @since 4.0
     */
    protected function showPrintDialog() {
        if($this->print) {
            $this->setJS('this.print();');
        }
    }

    /**
     * Sets the image DPI in the PDF
     * @return void
     * @since 4.0
     */
    protected function setImageDpi() {
        $dpi = (isset($this->settings['image_dpi'])) ? (int) $this->settings['image_dpi'] : 96;

        $this->mpdf->img_dpi = $dpi;
    }

    /**
     * Sets the text direction in the PDF (RTL support)
     * @return void
     * @since 4.0
     */
    protected function setTextDirection() {
        $rtl = (isset($this->settings['rtl'])) ? $this->settings['rtl'] : 'No';

        if(strtolower($rtl) == 'yes') {
            $this->mpdf->SetDirectionality('rtl');
        }
    }

    /**
     * Set the correct PDF Format
     * Normal, PDF/A-1b or PDF/X-1a
     * @return void
     * @since 4.0
     */
    protected function setPdfFormat() {
        switch(strtolower($this->settings['format'])) {
            case 'pdfa1b':
                $this->mpdf->PDFA     = true;
                $this->mpdf->PDFAauto = true;
            break;

            case 'pdfx1a':
                $this->mpdf->PDFX     = true;
                $this->mpdf->PDFXauto = true;
            break;
        }
    }

    /**
     * Add PDF Security, if able
     * @return void
     * @since 4.0
     */
    protected function setPdfSecurity() {
        /* Security settings cannot be applied to pdfa1b or pdfx1a formats */
        if(strtolower($this->settings['format']) == 'normal' && strtolower($this->settings['security'] == 'Yes')) {

            $password = (isset($this->settings['password'])) ? $this->settings['password'] : '';
            $privileges = (isset($this->settings['privileges'])) ? $this->settings['privileges'] : array();

            $this->mpdf->SetProtection($privileges, $password, '', 128);
        }
    }

}