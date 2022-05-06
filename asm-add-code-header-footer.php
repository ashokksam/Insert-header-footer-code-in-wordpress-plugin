<?php

/**
 * Plugin Name: Simply Insert Code in Header and Footer
 * Plugin URI: http://www.ashokkumawat.com/
 * Version: 1.0.1
 * Requires at least: 4.6
 * Requires PHP: 5.2
 * Tested up to: 5.7
 * Author: ashokkumawatasm
 * Author URI: http://www.ashokkumawat.com/
 * Description: Easy to add code in header footer script, HTML, CSS code.
 * License: GPLv2 or later
 */

/**
 * Insert Code In Headers and Footers Starts
 */


function asm_adminPanelsAndMetaBoxes()
{
    add_submenu_page('options-general.php', 'Insert Code in Header Footer', 'Insert Code in Header Footer', 'manage_options', 'asm-insert-codein-headers-and-footers', 'asm_admin_setting');
}

function asm_admin_setting()
{
    if ($_REQUEST['submitheaderfootersetting']) {
        update_option('asm_insert_header', $_REQUEST['asm_insert_header']);
        update_option('asm_insert_footer', $_REQUEST['asm_insert_footer']);
    }
    $asm_insert_footer = get_option('asm_insert_footer');
    $asm_insert_header = get_option('asm_insert_header');
    $editor_args = array('type' => 'text/html');
    $settings = wp_enqueue_code_editor($editor_args);
    wp_add_inline_script('code-editor', sprintf('jQuery( function() { wp.codeEditor.initialize( "asm_insert_header", %s ); } );', wp_json_encode($settings)));
    wp_add_inline_script('code-editor', sprintf('jQuery( function() { wp.codeEditor.initialize( "asm_insert_footer", %s ); } );', wp_json_encode($settings)));
    echo '<form method="post"><div class="headercode">';
    echo '<span>Header Code</span>';
    echo '	<textarea name="asm_insert_header" id="asm_insert_header" class="widefat" rows="8" style="font-family:Courier New;" >' . $asm_insert_header . '</textarea>';
    echo '</div>';
    echo '<div class="footercode">';
    echo '<span>Footer Code</span>';
    echo '	<textarea name="asm_insert_footer" id="asm_insert_footer" class="widefat" rows="8" style="font-family:Courier New;" >' . $asm_insert_footer . '</textarea>';
    echo '</div>';
    echo '<input type="submit" name="submitheaderfootersetting" class="btn button"></form>';
}

add_action('init', 'asm_adminPanelsAndMetaBoxes');

add_action('wp_head', 'asm_inser_code_in_wp_head');
function asm_inser_code_in_wp_head()
{
    echo get_option('asm_insert_header');
}

add_action('wp_footer', 'asm_inser_code_in_wp_footer');
function asm_inser_code_in_wp_footer()
{
    echo get_option('asm_insert_footer');
}
