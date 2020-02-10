<?php
/**
 * Chocolate Covered Katie importer.
 *
 * @since      1.0.0
 * @package    CCKRecipeImporter
 */

// Make sure the class name matches the file name.
class WPRM_Import_CCK extends WPRM_Import {

	/**
	 * Get the UID of this import source.
	 *
	 * @since    1.20.0
	 */
	public function get_uid() {
		// This should return a uid (no spaces) representing the import source.
		// For example "wp-ultimate-recipe", "easyrecipe", ...

		return 'cck';
	}

	/**
	 * Wether or not this importer requires a manual search for recipes.
	 *
	 * @since    1.20.0
	 */
	public function requires_search() {
		// Set to true when you need to search through the post content (or somewhere else) to actually find recipes.
		// When set to true the "search_recipes" function is required.
		// Usually false is fine as you can find recipes as a custom post type or in a custom table.

		return false;
	}

	/**
	 * Get the name of this import source.
	 *
	 * @since    1.20.0
	 */
	public function get_name() {
		// Display name for this importer.

		return 'Chocolate Covered Katie';
	}

	/**
	 * Get HTML for the import settings.
	 *
	 * @since    1.20.0
	 */
	public function get_settings_html() {
		// Any HTML can be added here if input is required for doing the import.
		// Take a look at the WP Ultimate Recipe importer for an example.
		// Most importers will just need ''.

		return '';
	}

	/**
	 * Get the total number of recipes to import.
	 *
	 * @since    1.20.0
	 */
	public function get_recipe_count() {
		// Return a count for the number of recipes left to import.
		// Don't include recipes that have already been imported.

		$loop = new WP_Query( array(
			'fields' => 'ids',
			'post_type' => 'post',
			'posts_per_page' => 999,
			'meta_query' => array(
				array(
					'key'	=> 'total_time',
				),
				array(
					'key' => '_recipe_imported',
					'value' => 1,
					'compare' => 'NOT EXISTS',
				)
			)
		) );

		return $loop->found_posts;
	}

	/**
	 * Search for recipes to import.
	 *
	 * @since    1.20.0
	 * @param	 int $page Page of recipes to import.
	 */
	public function search_recipes( $page = 0 ) {
		// Only needed if "search_required" returns true.
		// Function will be called with increased $page number until finished is set to true.
		// Will need a custom way of storing the recipes.
		// Take a look at the Easy Recipe importer for an example.

		return array(
			'finished' => true,
			'recipes' => 0,
		);
	}

	/**
	 * Get a list of recipes that are available to import.
	 *
	 * @since    1.20.0
	 * @param	 int $page Page of recipes to get.
	 */
	public function get_recipes( $page = 0 ) {
		// Return an array of recipes to be imported with name and edit URL.
		// If not the same number of recipes as in "get_recipe_count" are returned pagination will be used.

		$loop = new WP_Query( array(
			'post_type' => 'post',
			'posts_per_page' => 20,
			'paged' => $page,
			'meta_query' => array(
				array(
					'key' => 'total_time',
				),
				array(
					'key' => '_recipe_imported',
					'value' => 1,
					'compare' => 'NOT EXISTS',
				)
			)
		) );

		$recipes = array();
		foreach( $loop->posts as $post ) {
			$recipes[ $post->ID ] = array(
				'name' => $post->post_title,
				'url'  => get_edit_post_link( $post->ID )
			);
		}
		return $recipes;

	}

	/**
	 * Get recipe with the specified ID in the import format.
	 *
	 * @since    1.20.0
	 * @param	 mixed $id ID of the recipe we want to import.
	 * @param	 array $post_data POST data passed along when submitting the form.
	 */
	public function get_recipe( $id, $post_data ) {
		// Get the recipe data in WPRM format for a specific ID, corresponding to the ID in the "get_recipes" array.
		// $post_data will contain any input fields set in the "get_settings_html" function.
		// Include any fields to backup in "import_backup".
		$recipe = array(
			'import_id' => 0, // Important! If set to 0 will create the WPRM recipe as a new post. If set to an ID it will update to post with that ID to become a WPRM post type.
			'import_backup' => array(
				'example_recipe_id' => $id,
			),
		);

		// Get and set all the WPRM recipe fields.
		$recipe['name'] = get_the_title( $id );
		$recipe['summary'] = '';
		$recipe['author_name'] = '';
		$recipe['servings_unit'] = '';
		$recipe['notes'] = '';

		$image_id = get_post_meta( $id, 'recipe_thumbnail', true );
		if( empty( $image_id ) )
			$image_id = get_post_thumbnail_id( $id );
		$recipe['image_id'] = $image_id;

		$recipe['servings'] = get_post_meta( $id, 'yield', true );
		$recipe['prep_time'] = 0;
		$recipe['cook_time'] = 0;
		$recipe['total_time'] = get_post_meta( $id, 'total_time', true );

		// Set recipe options.
		$recipe['author_display'] = 'default'; // default, disabled, post_author, custom.
		$recipe['ingredient_links_type'] = 'global'; // global, custom.

		// Ingredients have to follow this array structure consisting of groups first.
		$recipe['ingredients'] = array();

		$ingredient_sections = get_field( 'ingredients_sections', $id );
		foreach( $ingredient_sections as $ingredient_section ) {
			$name = !empty( $ingredient_section['title'] ) ? esc_html( $ingredient_section['title'] ) : '';
			$section = array(
				'name' => $name,
				'ingredients' => array()
			);
			foreach( $ingredient_section['ingredients'] as $ingredient ) {
				$section['ingredients'][] = array( 'raw' => $ingredient['ingredient'] );
			}
			$recipe['ingredients'][] = $section;
		}

		// Instructions have to follow this array structure consisting of groups first.
		$recipe['instructions'] = array(
			array(
				'name' => '', // Group names can be empty.
				'instructions' => array(
					array(
						'text' => wpautop( get_post_meta( $id, 'method__instructions', true ) ),
					),
				),
			),
		);

		return $recipe;
	}

	/**
	 * Replace the original recipe with the newly imported WPRM one.
	 *
	 * @since    1.20.0
	 * @param	 mixed $id ID of the recipe we want replace.
	 * @param	 mixed $wprm_id ID of the WPRM recipe to replace with.
	 * @param	 array $post_data POST data passed along when submitting the form.
	 */
	public function replace_recipe( $id, $wprm_id, $post_data ) {
		// The recipe with ID $id has been imported and we now have a WPRM recipe with ID $wprm_id (can be the same ID).
		// $post_data will contain any input fields set in the "get_settings_html" function.
		// Use this function to do anything after the import, like replacing shortcodes.

		// Mark as migrated so it isn't re-imported
		update_post_meta( $id, '_recipe_imported', 1 );

		// Set parent post that contains recipe
		update_post_meta( $wprm_id, 'wprm_parent_post_id', $id );

		// Add the WPRM shortcode
		$post = get_post( $id );
		if( false !== strpos( $post->post_content, '[insert-recipe-here]' ) ) {
			$content = str_replace( '[insert-recipe-here]', '[wprm-recipe id="' . $wprm_id . '"]', $post->post_content );
		} else {
			$content = $post->post_content .= ' [wprm-recipe id="' . $wprm_id . '"]';
		}
		wp_update_post( array( 'ID' => $id, 'post_content' => $content ) );

	}
}
