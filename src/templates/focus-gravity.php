<?php

/**
 * Template Name: Focus Gravity
 * Version: 1.0
 * Description: Focus Gravity providing a classic layout which epitomises Gravity Forms Print Preview. It's the familiar layout you've come to love.
 * Author: Gravity PDF
 * Group: Core
 * License: GPLv2
 * Required PDF Version: 4.0-alpha
 */

/* Prevent direct access to the template */
if ( ! class_exists('GFForms')) {
    return;
}

/**
 * All Gravity PDF 4.x templates have access to the following variables:
 *
 * $form (The current Gravity Form array)
 * $entry (The raw entry data)
 * $form_data (The processed entry data stored in an array)
 * $settings (the current PDF configuration)
 * $fields (an array of Gravity Form fields which can be accessed with their ID number)
 * $config (The initialised template config class – /config/focus-gravity.php)
 * $gfpdf (the main Gravity PDF object containing all our helper classes)
 * $args (contains an array of all variables - the ones being described right now - passed to the template)
 */

/**
 * Load up our template-specific appearance settings
 */
$accent_colour             = ( ! empty($settings['focusgravity_accent_colour'])) ? $settings['focusgravity_accent_colour'] : '#e3e3e3';
$accent_contrast_colour    = $gfpdf->misc->get_contrast($accent_colour);
$secondary_colour          = ( ! empty($settings['focusgravity_secondary_colour'])) ? $settings['focusgravity_secondary_colour'] : '#eaf2fa';
$secondary_contrast_colour = $gfpdf->misc->get_contrast($secondary_colour);

$label_format              = ( ! empty($settings['focusgravity_label_format'])) ? $settings['focusgravity_label_format'] : 'combined_label';

?>

<!-- Include styles needed for the PDF -->
<style>

    /* Handle Gravity Forms CSS Ready Classes */
    .row-separator {
        clear: both;
    }

    .gf_left_half,
    .gf_left_third, .gf_middle_third,
    .gf_list_2col li, .gf_list_3col li, .gf_list_4col li, .gf_list_5col li {
        float: left;
    }

    .gf_right_half,
    .gf_right_third {
        float: right;
    }

    .gf_left_half, .gf_right_half,
    .gf_list_2col li {
        width: 49%;
    }

    .gf_left_third, .gf_middle_third, .gf_right_third,
    .gf_list_3col li {
        width: 32.3%;
    }

    .gf_list_4col li {
        width: 24%;
    }

    .gf_list_5col li {
        width: 19%;
    }

    .gf_left_half, .gf_right_half {
        padding-right: 1%;
    }

    .gf_left_third, .gf_middle_third, .gf_right_third {
        padding-right: 1.505%;
    }

    .gf_right_half, .gf_right_third {
        padding-right: 0;
    }

    /* Don't double float the list items if already floated (mPDF does not support this ) */
    .gf_left_half li, .gf_right_half li,
    .gf_left_third li, .gf_middle_third li, .gf_right_third li {
        width: 100% !important;
        float: none !important;
    }

    /**
     * Headings
     */
    h3 {
        margin: 1.5mm 0 0.5mm;
        padding: 0;
    }

    /**
     * Quiz Style Support
     */
    .gquiz-field {
        color: #666;
    }

    .gquiz-correct-choice {
        font-weight: bold;
        color: black;
    }

    .gf-quiz-img {
        padding-left: 5px !important;
        vertical-align: middle;
    }

    /**
     * Survey Style Support
     */
    .gsurvey-likert-choice-label {
        padding: 4px;
    }

    .gsurvey-likert-choice, .gsurvey-likert-choice-label {
        text-align: center;
    }

    /**
     * Table Support
     */
    th, td {
        font-size: 95%;
    }

    /**
     * List Support
     */
    ul, ol {
        margin: 0;
        padding-left: 1mm;
        padding-right: 1mm;
    }

    li {
        margin: 0;
        padding: 0;
        list-style-position: inside;
    }

    /**
     * Header / Footer
     */
    .alignleft {
        float: left;
    }

    .alignright {
        float: right;
    }

    .aligncenter {
        text-align: center;
    }

    p.alignleft {
        text-align: left;
        float: none;
    }

    p.alignright {
        text-align: right;
        float: none;
    }

    /**
     * Independant Template Styles
     */
    #container {
        border-radius: 5px;
        border: 1px solid <?php echo $accent_colour; ?>;
    }

    #form_title {
        border-top-left-radius: 3px;
        border-top-right-radius: 3px;
    }

    h3 {
        background: <?php echo $accent_colour; ?>;
        color: <?php echo $accent_contrast_colour; ?>;
        margin: 0;
    }

    .gfpdf-page {
        border-top: 1px solid #FFF;
    }

    .gfpdf-field .label {
        font-weight: bold;
        border-bottom: 1px solid <?php echo $accent_colour; ?>;
        background: <?php echo $secondary_colour; ?>;
        color: <?php echo $secondary_contrast_colour; ?>;
    }

    .value, .gfpdf-section-description, .gfpdf-field .label, h3, .gfpdf-html .value {
        padding: 7px 6px 7px 10px;
    }

    .gfpdf-html {
        border-top: 5px solid <?php echo $secondary_colour; ?>;
    }

    table.gfield_list th {
        background: <?php echo $accent_colour; ?>;
        color: <?php echo $accent_contrast_colour; ?>;
    }

    table.entry-products th, table.entry-products td.emptycell {
        background: none;
    }

    <?php if( $label_format == 'combined_label' ): ?>
        .gfpdf-field .label {
            background: none;
            border: none;
            padding-bottom: 0;
        }

        .value {
            padding-top: 0;
        }

        .even {
            background: <?php echo $secondary_colour; ?>;
        }
    <?php else: ?>
        .gfpdf-html .value {
            border-top: 1px solid <?php echo $accent_colour; ?>;
        }
    <?php endif; ?>

</style>

<!-- Output our HTML markup -->
<?php

/**
 * Load our core-specific styles from our PDF settings which will be passed to the PDF template $config array
 */
$show_form_title      = ( ! empty($settings['show_form_title']) && $settings['show_form_title'] == 'Yes') ? true : false;
$show_page_names      = ( ! empty($settings['show_page_names']) && $settings['show_page_names'] == 'Yes') ? true : false;
$show_html            = ( ! empty($settings['show_html']) && $settings['show_html'] == 'Yes') ? true : false;
$show_section_content = ( ! empty($settings['show_section_content']) && $settings['show_section_content'] == 'Yes') ? true : false;
$enable_conditional   = ( ! empty($settings['enable_conditional']) && $settings['enable_conditional'] == 'Yes') ? true : false;
$show_empty           = ( ! empty($settings['show_empty']) && $settings['show_empty'] == 'Yes') ? true : false;

/**
 * Set up our configuration array to control what is and is not shown in the generated PDF
 *
 * @var array
 */
$config = array(
    'settings' => $settings,
    'meta'     => array(
        'echo'                => true, /* whether to output the HTML or return it */
        'exclude'             => true, /* whether we should exclude fields with a CSS value of 'exclude'. Default to true */
        'empty'               => $show_empty, /* whether to show empty fields or not. Default is false */
        'conditional'         => $enable_conditional, /* whether we should skip fields hidden with conditional logic. Default to true. */
        'show_title'          => $show_form_title, /* whether we should show the form title. Default to true */
        'section_content'     => $show_section_content, /* whether we should include a section breaks content. Default to false */
        'page_names'          => $show_page_names, /* whether we should show the form's page names. Default to false */
        'html_field'          => $show_html, /* whether we should show the form's html fields. Default to false */
        'individual_products' => false, /* Whether to show individual fields in the entry. Default to false - they are grouped together at the end of the form */
    ),
);

/**
 * Generate our HTML markup
 *
 * You can access Gravity PDFs common functions and classes through our API wrapper class "GPDFAPI"
 */
$pdf = GPDFAPI::get_pdf_class();
$pdf->process_html_structure($entry, GPDFAPI::get_pdf_class('model'), $config);

