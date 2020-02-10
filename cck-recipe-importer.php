<?php
/**
 * Plugin Name: CCK Recipe Importer
 * Description: Imports recipes to WP Recipe Maker for Chocolate Covered Katie
 * Version:     1.0.0
 * Author:      Bill Erickson
 * Author URI:  https://www.billerickson.net/
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License version 2, as published by the
 * Free Software Foundation.  You may NOT assume that you can use any other
 * version of the GPL.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.
 *
 * @package    CCKRecipeImporter
 * @since      1.0.0
 * @copyright  Copyright (c) 2020, Bill Erickson
 * @license    GPL-2.0+
 */

// Plugin directory
define( 'CCK_RI_DIR' , plugin_dir_path( __FILE__ ) );

/**
 * Include custom WPRM importer
 * @link https://www.billerickson.net/custom-importer-for-wp-recipe-maker/
 */
function be_custom_wprm_importer( $directories ) {
	$directories[] = CCK_RI_DIR;
	return $directories;
}
add_filter( 'wprm_importer_directories', 'be_custom_wprm_importer' );
