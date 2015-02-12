<?php
/*
Plugin Name: Gallery Categories
Plugin URI: http://bestwebsoft.com/products/
Description: Add-on for Gallery Plugin by BestWebSoft.
Author: BestWebSoft
Version: 1.0.0
Author URI: http://bestwebsoft.com/
License: GPLv3 or later
*/

/*  © Copyright 2015  BestWebSoft  ( http://support.bestwebsoft.com )

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 3, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

global $gllrctgrs_taxonomy;
$gllrctgrs_taxonomy = 'gallery_categories';

/**
 * Function for adding on admin-panel Wordpress page *'bws_plugins'
 *
*/
if ( ! function_exists( 'gllrctgrs_admin_menu' ) ) {
	function gllrctgrs_admin_menu() {
		global $bstwbsftwppdtplgns_options, $bstwbsftwppdtplgns_added_menu;
		$bws_menu_info = get_plugin_data( plugin_dir_path( __FILE__ ) . "bws_menu/bws_menu.php" );
		$bws_menu_version = $bws_menu_info["Version"];
		$base = plugin_basename( __FILE__ );

		if ( ! isset( $bstwbsftwppdtplgns_options ) ) {
			if ( is_multisite() ) {
				if ( ! get_site_option( 'bstwbsftwppdtplgns_options' ) )
					add_site_option( 'bstwbsftwppdtplgns_options', array(), '', 'yes' );
				$bstwbsftwppdtplgns_options = get_site_option( 'bstwbsftwppdtplgns_options' );
			} else {
				if ( ! get_option( 'bstwbsftwppdtplgns_options' ) )
					add_option( 'bstwbsftwppdtplgns_options', array(), '', 'yes' );
				$bstwbsftwppdtplgns_options = get_option( 'bstwbsftwppdtplgns_options' );
			}
		}

		if ( isset( $bstwbsftwppdtplgns_options['bws_menu_version'] ) ) {
			$bstwbsftwppdtplgns_options['bws_menu']['version'][ $base ] = $bws_menu_version;
			unset( $bstwbsftwppdtplgns_options['bws_menu_version'] );
			if ( is_multisite() )
				update_site_option( 'bstwbsftwppdtplgns_options', $bstwbsftwppdtplgns_options, '', 'yes' );
			else
				update_option( 'bstwbsftwppdtplgns_options', $bstwbsftwppdtplgns_options, '', 'yes' );
			require_once( dirname( __FILE__ ) . '/bws_menu/bws_menu.php' );
		} else if ( ! isset( $bstwbsftwppdtplgns_options['bws_menu']['version'][ $base ] ) || $bstwbsftwppdtplgns_options['bws_menu']['version'][ $base ] < $bws_menu_version ) {
			$bstwbsftwppdtplgns_options['bws_menu']['version'][ $base ] = $bws_menu_version;
			if ( is_multisite() )
				update_site_option( 'bstwbsftwppdtplgns_options', $bstwbsftwppdtplgns_options, '', 'yes' );
			else
				update_option( 'bstwbsftwppdtplgns_options', $bstwbsftwppdtplgns_options, '', 'yes' );
			require_once( dirname( __FILE__ ) . '/bws_menu/bws_menu.php' );
		} else if ( ! isset( $bstwbsftwppdtplgns_added_menu ) ) {
			$plugin_with_newer_menu = $base;
			foreach ( $bstwbsftwppdtplgns_options['bws_menu']['version'] as $key => $value ) {
				if ( $bws_menu_version < $value && is_plugin_active( $base ) ) {
					$plugin_with_newer_menu = $key;
				}
			}
			$plugin_with_newer_menu = explode( '/', $plugin_with_newer_menu );
			$wp_content_dir = defined( 'WP_CONTENT_DIR' ) ? basename( WP_CONTENT_DIR ) : 'wp-content';
			if ( file_exists( ABSPATH . $wp_content_dir . '/plugins/' . $plugin_with_newer_menu[0] . '/bws_menu/bws_menu.php' ) )
				require_once( ABSPATH . $wp_content_dir . '/plugins/' . $plugin_with_newer_menu[0] . '/bws_menu/bws_menu.php' );
			else
				require_once( dirname( __FILE__ ) . '/bws_menu/bws_menu.php' );
			$bstwbsftwppdtplgns_added_menu = true;
		}

		add_menu_page( 'BWS Plugins', 'BWS Plugins', 'manage_options', 'bws_plugins', 'bws_add_menu_render', plugins_url( "images/px.png", __FILE__ ), 1001 );
	}
}
/**
 * Initialize plugin
 * 
 */
if ( ! function_exists( 'gllrctgrs_init' ) ) {
	function gllrctgrs_init() {
		global $gllrctgrs_gallery_not_ready, $gllrctgrs_options;
		load_plugin_textdomain( 'gllrctgrs', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		gllrctgrs_version_check();
		gllrctgrs_check();
		if ( '' == $gllrctgrs_gallery_not_ready ) {
			gllrctgrs_register_taxonomy();
			if ( empty( $gllrctgrs_options ) )
				$gllrctgrs_options = get_option( 'gllrctgrs_options' );
			if ( empty( $gllrctgrs_options ) ) {
				gllrctgrs_set_options();
				gllrctgrs_add_default_term_all_gallery();
			}
			add_action( 'after-gallery_categories-table', 'gllrctgrs_add_notice_below_table' );
			add_filter( 'manage_edit-gallery_categories_columns', 'gllrctgrs_add_column' );
			add_filter( 'manage_gallery_categories_custom_column', 'gllrctgrs_fill_column', 10, 3 );
			add_filter( 'manage_edit-gallery_columns', 'gllrctgrs_add_gallery_column' );
			add_action( 'manage_gallery_posts_custom_column', 'gllrctgrs_fill_gallery_column' );
			add_action( 'post_updated', 'gllrctgrs_default_term' );			
			add_action( 'restrict_manage_posts', 'gllrctgrs_taxonomy_filter' );
			add_filter( 'gallery_categories_row_actions', 'gllrctgrs_hide_delete_link', 10, 2 );
			add_action( 'admin_footer-edit-tags.php', 'gllrctgrs_hide_delete_cb' );
			add_action( 'delete_term_taxonomy', 'gllrctgrs_delete_term', 10, 1 );
		}
	}
}
/**
 * Call function assignment of default term for all gallery at the time of activation plugin
 *
*/
if ( ! function_exists( 'gllrctgrs_admin_init' ) ) {
	function gllrctgrs_admin_init() {
		global $bws_plugin_info, $gllrctgrs_plugin_info;
		if ( ! $gllrctgrs_plugin_info ) /* Add variable for bws_menu */
			$gllrctgrs_plugin_info = get_plugin_data( __FILE__ );
		if ( ! isset( $bws_plugin_info ) || empty( $bws_plugin_info ) )
			$bws_plugin_info = array( 'id' => '183', 'version' => $gllrctgrs_plugin_info['Version'] );
	}
}
/**
 * Register settings function
 *
*/
if ( ! function_exists( 'gllrctgrs_set_options' ) ) {
	function gllrctgrs_set_options() {
		global $gllrctgrs_options, $gllrctgrs_plugin_info;
		if ( ! $gllrctgrs_plugin_info )
			$gllrctgrs_plugin_info = get_plugin_data( __FILE__ );
		$gllrctgrs_options_defaults	=	array(
			'plugin_option_version'		=> $gllrctgrs_plugin_info['Version'],
			'default_gallery_category'	=> ''
		);
		if ( ! get_option( 'gllrctgrs_options' ) ) /* Install the option defaults */
			add_option( 'gllrctgrs_options', $gllrctgrs_options_defaults, '', 'yes' );
		$gllrctgrs_options = get_option( 'gllrctgrs_options' ); /* Get options from the database */

		/* Array merge incase this version has added new options */
		if ( ! isset( $gllrctgrs_options['plugin_option_version'] ) || $gllrctgrs_options['plugin_option_version'] != $gllrctgrs_plugin_info['Version'] ) {
			$gllrctgrs_options = array_merge( $gllrctgrs_options_defaults, $gllrctgrs_options );
			$gllrctgrs_options['plugin_option_version'] = $gllrctgrs_plugin_info['Version'];
			update_option( 'gllrctgrs_options', $gllrctgrs_options );
		}
	}
}
/**
 * Function check if plugin is compatible with current WP version
 *
*/ 
if ( ! function_exists ( 'gllrctgrs_version_check' ) ) {
	function gllrctgrs_version_check() {
		global $wp_version, $gllrctgrs_plugin_info;
		$require_wp		=	"3.2"; /* Wordpress at least requires version*/
		$plugin			=	plugin_basename( __FILE__ );
		if ( version_compare( $wp_version, $require_wp, "<" ) ) {
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			if ( is_plugin_active( $plugin ) ) {
				deactivate_plugins( $plugin );
				if ( ! $gllrctgrs_plugin_info )
					$gllrctgrs_plugin_info = get_plugin_data( __FILE__ );
				$admin_url = ( function_exists( 'get_admin_url' ) ) ? get_admin_url( null, 'plugins.php' ) : esc_url( '/wp-admin/plugins.php' );
				wp_die( "<strong>" . $gllrctgrs_plugin_info['Name'] . " </strong> " . __( 'requires', 'gllrctgrs' ) . " <strong>WordPress " . $require_wp . "</strong> " . __( 'or higher, that is why it has been deactivated! Please upgrade WordPress and try again.', 'gllrctgrs' ) . "<br /><br />" . __( 'Back to the WordPress', 'gllrctgrs' ) . " <a href='" . $admin_url . "'>" . __( 'Plugins page', 'gllrctgrs' ) . "</a>." );
			}
		}
	}
}
/**
 * Checking for the existence of Gallery Plugin or Gallery Plugin Pro
 *
*/
if ( ! function_exists( 'gllrctgrs_check' ) ) {
	function gllrctgrs_check() {
		global $gllrctgrs_gallery_not_ready;
		if ( ! function_exists( 'get_plugins' ) )
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		$all_plugins = get_plugins();
		if ( ! ( array_key_exists( 'gallery-plugin/gallery-plugin.php', $all_plugins ) || array_key_exists( 'gallery-plugin-pro/gallery-plugin-pro.php', $all_plugins ) ) ) {
			$gllrctgrs_gallery_not_ready = __( 'Gallery Plugin not found.</br>Please install and activate this plugin to make sure Gallery Categories plugin works correctly.</br>You can download Gallery Plugin from', 'gllrctgrs' ) . ' <a href="' . esc_url( 'http://bestwebsoft.com/products/gallery-plugin/' ) . '" title="' . __( 'Developers website', 'gllrctgrs' ) . '"target="_blank">' . __( "plugin authors' website", 'gllrctgrs' ) . '&nbsp' . '</a>' . __( 'or', 'gllrctgrs' ) . '&nbsp' . '<a href="' . esc_url( 'https://wordpress.org/plugins/gallery-plugin/' ) . '" title="Wordpress" target="_blank">' . __( 'WordPress.', 'gllrctgrs' ) . '</a>';
		} else {
			if ( ! ( is_plugin_active( 'gallery-plugin/gallery-plugin.php' ) || is_plugin_active( 'gallery-plugin-pro/gallery-plugin-pro.php' ) ) ) {
				$gllrctgrs_gallery_not_ready = __( 'Gallery Plugin is not activated.</br>Please activate the plugin to make sure Gallery Categories plugin works correctly.', 'gllrctgrs' );
			} elseif ( ( is_plugin_active( 'gallery-plugin/gallery-plugin.php' ) && isset( $all_plugins['gallery-plugin/gallery-plugin.php']['Version'] ) && $all_plugins['gallery-plugin/gallery-plugin.php']['Version'] < '4.2.7' ) || 
				( is_plugin_active( 'gallery-plugin-pro/gallery-plugin-pro.php' ) && isset( $all_plugins['gallery-plugin-pro/gallery-plugin-pro.php']['Version'] ) && $all_plugins['gallery-plugin-pro/gallery-plugin-pro.php']['Version'] < '1.4.3' ) ) {
				$gllrctgrs_gallery_not_ready = __( 'Gallery Plugin version is outdated.</br>Please update the plugin to make sure Gallery Categories plugin works correctly.', 'gllrctgrs' );
			}
		}
	}
}
/**
 * Function registration taxonomy for gallery
 *
*/
if ( ! function_exists( 'gllrctgrs_register_taxonomy' ) ) {
	function gllrctgrs_register_taxonomy() {
		register_taxonomy( 'gallery_categories', 'gallery',
			array(
				'hierarchical' 		=> true,
				'labels'			=> array(
					'name' 						=> __( 'Gallery Categories', 'gllrctgrs' ),
					'singular_name' 			=> __( 'Gallery Category', 'gllrctgrs' ),
					'add_new' 					=> __( 'Add Gallery Category', 'gllrctgrs' ),
					'add_new_item'				=> __( 'Add New Gallery Category', 'gllrctgrs' ),
					'edit' 						=> __( 'Edit Gallery Category', 'gllrctgrs' ),
					'edit_item' 				=> __( 'Edit Gallery Category', 'gllrctgrs' ),
					'new_item' 					=> __( 'New Gallery Category', 'gllrctgrs' ),
					'view' 						=> __( 'View Gallery Category', 'gllrctgrs' ),
					'view_item' 				=> __( 'View Gallery Category', 'gllrctgrs' ),
					'search_items'	 			=> __( 'Find Gallery Category', 'gllrctgrs' ),
					'not_found' 				=> __( 'No Gallery Categories found', 'gllrctgrs' ),
					'not_found_in_trash' 		=> __( 'No Gallery Categories found in Trash', 'gllrctgrs' ),
					'parent' 					=> __( 'Parent Gallery Category', 'gllrctgrs' ),
				),
				'rewrite' 			=> true,
				'show_ui'			=> true,
				'query_var'			=> true,
				'sort'				=> true,
				'map_meta_cap'		=> true
			)
		);
	}
}
/**
 * Activation function plugin
 *
*/
if ( ! function_exists( 'gllrctgrs_plugin_activate' ) ) {
	function gllrctgrs_plugin_activate() {
		global $gllrctgrs_gallery_not_ready;
		gllrctgrs_check();
		if ( '' == $gllrctgrs_gallery_not_ready ) {
			gllrctgrs_set_options();
			if ( ! taxonomy_exists( 'gallery_categories' ) ) {
				gllrctgrs_register_taxonomy();
				gllrctgrs_add_default_term_all_gallery();
			}
		}
	}
}
/**
 * Function assignment of default term for all gallery at the time of activation plugin
 *
*/
if ( ! function_exists( 'gllrctgrs_add_default_term_all_gallery' ) ) {
	function gllrctgrs_add_default_term_all_gallery() {
		global $post, $gllrctgrs_taxonomy, $gllrctgrs_options;
		$posts = get_posts( array(
			'posts_per_page'=>	-1,
			'post_type'		=> 'gallery'
		) );
		$def_term  = 'Default';
		if ( ! empty( $posts ) ) {
			foreach( $posts as $post ) {
				if ( ! has_term( '', $gllrctgrs_taxonomy, $post ) ) { /* Checked and updated if necessary term*/
					wp_set_object_terms( $post->ID, $def_term, $gllrctgrs_taxonomy );
				}
			}
			$terms = get_terms( $gllrctgrs_taxonomy, 'fields=ids' );
			if ( is_array( $terms ) )
				$def_term_id = min( $terms );
		} else {
			if ( ! term_exists( $def_term, $gllrctgrs_taxonomy ) ) {
				$def_term_info = wp_insert_term( $def_term, $gllrctgrs_taxonomy,
					array( 
						'description'	=> '',
						'slug'			=> 'default', 
					)
				);
			}
			if ( is_array( $def_term_info ) )
				$def_term_id = ( array_shift( $def_term_info ) );
		}
		$gllrctgrs_options['default_gallery_category'] = intval( $def_term_id );
		update_option( 'gllrctgrs_options', $gllrctgrs_options );
	}
}
/**
 * Add notises on plugins page if Gallery plugin is not installed or not active
 *
*/
if ( ! function_exists( 'gllrctgrs_show_notices' ) ) {
	function gllrctgrs_show_notices() { 
		global $post, $hook_suffix, $gllrctgrs_gallery_not_ready;
		$post_type = ( isset( $post ) )? $post->post_type == 'gallery': false;
		if ( isset( $_GET['post_type'] ) && empty( $post ) ) {
			if ( $_GET['post_type'] == 'gallery' ) {
				$post_type = true;
			}
		}
		if ( $hook_suffix == 'plugins.php' || ( isset( $_REQUEST['page'] ) && ( $_REQUEST['page'] == 'bws_plugins' || $_REQUEST['page'] == 'gallery-plugin.php' || $_REQUEST['page'] == 'gallery-plugin-pro.php' ) ) || $post_type ) {
			if ( '' != $gllrctgrs_gallery_not_ready ) { ?>
				<div class="error">
					<p><strong><?php _e( 'WARNING:', 'gllrctgrs' ) ; ?></strong><?php echo '&nbsp' . $gllrctgrs_gallery_not_ready; ?></p>
				</div>
			<?php }
		}
	}
}
/**
 * Add notises on taxonomy page about insert shortcode
 *
*/
if ( ! function_exists( 'gllrctgrs_add_notice_below_table' ) ) {
	function gllrctgrs_add_notice_below_table( $gllrctgrs_taxonomy ) {
		global $gllrctgrs_options; ?>
		<div class="form-wrap" style="font-style: italic">
			<p style="padding-bottom: 20px"><?php
				echo '<strong>' . __( 'Note:', 'gllrctgrs') . '</strong> ' . __( 'If you want to display a short description with screenshots from any category with a link to the Single Gallery Page, please add the shortcode', 'gllrctgrs' ) . '<br /><strong>[print_gllr cat_id=Your_gallery_category_id]</strong>' . '&nbsp' .  __( 'to your post or page', 'gllrctgrs' ); ?>
			</p>
			<p>
				<?php if ( ! empty( $gllrctgrs_options['default_gallery_category'] ) ) {
					$def_term = get_term( $gllrctgrs_options['default_gallery_category'], $gllrctgrs_taxonomy );
					if ( ! empty( $def_term ) ) {
						$def_term_name = $def_term->name;
						if ( ! empty( $def_term_name ) ) {
							echo '<strong>' . __( 'Note:', 'gllrctgrs') . '</strong> ' . __( 'When deleting a category, the galleries that belong to this category will not be deleted. Instead, these galleries will be moved to the category', 'gllrctgrs' ) . '&nbsp'. '<strong>' . $def_term_name . '</strong>.';
						}
					}
				} ?>
			</p>
		</div>
	<?php }
}
/**
 * Add action links on plugin page in to Plugin Description block
 *
*/ 
if ( ! function_exists( 'gllrctgrs_register_plugin_links' ) ) {
	function gllrctgrs_register_plugin_links( $links, $file ) {
		$base = plugin_basename( __FILE__ );
		if ( $file == $base ) {
			$links[]	= '<a href="http://wordpress.org/plugins/gallery-categories/faq/" target="_blank">' . __( 'FAQ', 'gllrctgrs' ) . '</a>';
			$links[]	= '<a href="http://support.bestwebsoft.com">' . __( 'Support', 'gllrctgrs' ) . '</a>';
		}
		return $links;
	}
}
/**
 * Function for adding column in taxonomy
 *
*/
if ( ! function_exists( 'gllrctgrs_add_column' ) ) {
	function gllrctgrs_add_column( $column ) {
		$column['shortcode'] = __( 'Shortcode', 'gllrctgrs' );
		return $column;
	}
}
/**
 * Function for filling column in taxonomy
 *
*/
if ( ! function_exists( 'gllrctgrs_fill_column' ) ) {
	function gllrctgrs_fill_column( $out, $column, $id ) {
		if ( $column == 'shortcode' ) {
			$out = '<span class="gllr_code gllrprfssnl_code">[print_gllr cat_id=' . $id . ']</span>';
		}
		return $out;
	}
}
/**
 * Function for adding column in gallery
 *
*/
if ( ! function_exists( 'gllrctgrs_add_gallery_column' ) ) {
	function gllrctgrs_add_gallery_column( $column ) {
		$column['gallery_categories'] = __( 'Gallery Categories', 'gllrctgrs' );
		return $column;
	}
}
/**
 * Function for filling column in gallery
 *
*/
if ( ! function_exists( 'gllrctgrs_fill_gallery_column' ) ) {
	function gllrctgrs_fill_gallery_column( $column ) {
		global $post, $gllrctgrs_taxonomy;
		if ( $column == 'gallery_categories' ) {
			$terms = get_the_terms( $post->ID, $gllrctgrs_taxonomy );
			$out = '';
			if ( $terms ) {
				foreach ( $terms as $term ) {
					$out .= '<a href="edit.php?post_type=gallery&amp;gallery_categories=' . $term->slug .'">' . $term->name . '</a><br />';
				}
				echo trim( $out );
			}
		}
	}
}
/**
 * Function assignment of default term for new gallery while updated post
 *
*/
if ( ! function_exists( 'gllrctgrs_default_term' ) ) {
	function gllrctgrs_default_term( $post_ID ) {
		global $gllrctgrs_options, $gllrctgrs_taxonomy;
		$post = get_post( $post_ID );
		$post_type = $post->post_type;
		if ( $post_type == 'gallery' ) {
			if ( ! has_term( '', $gllrctgrs_taxonomy, $post ) ) {
				wp_set_object_terms( $post->ID, $gllrctgrs_options['default_gallery_category'], $gllrctgrs_taxonomy );
			}
		}
	}
}
/**
 * Function for adding taxonomy filter in gallery
 *
*/
if ( ! function_exists( 'gllrctgrs_taxonomy_filter' ) ) {
	function gllrctgrs_taxonomy_filter() {
		global $typenow, $gllrctgrs_taxonomy;
		if ( $typenow == 'gallery' ) {
			$current_taxonomy = isset( $_GET[ $gllrctgrs_taxonomy ] ) ? $_GET[ $gllrctgrs_taxonomy ] : '';
			$gllrctgrs_taxonomy_obj = get_taxonomy( $gllrctgrs_taxonomy );
			if ( !empty( $gllrctgrs_taxonomy_obj ) )
				$gllrctgrs_taxonomy_name = $gllrctgrs_taxonomy_obj->labels->name;
			$terms = get_terms( $gllrctgrs_taxonomy );
			if ( count( $terms ) > 0 ) { ?>
				<select name="<?php echo $gllrctgrs_taxonomy; ?>" id="<?php echo $gllrctgrs_taxonomy; ?>" class="gllrctgrs_postform">
					<option value=''><?php echo __( 'All', 'gllrctgrs' ) . '&nbsp;' . $gllrctgrs_taxonomy_name; ?></option>
					<?php foreach ( $terms as $term ) { ?>
						<option value="<?php echo $term->slug; ?>"<?php if ( $current_taxonomy == $term->slug ) echo 'selected="selected"'; ?>><?php echo $term->name . ' ( ' . $term->count . ' ) ' ?></option>
					<?php } ?>
				</select>
			<?php }
		}		
	}
}
/**
 * Function for hide delete link ( protect default category from deletion )
 *
*/ 
if ( ! function_exists( 'gllrctgrs_hide_delete_link' ) ) {
	function gllrctgrs_hide_delete_link( $actions, $tag ) {
		global $gllrctgrs_options;
		if ( empty( $gllrctgrs_options ) )
			$gllrctgrs_options = get_option( 'gllrctgrs_options' );
		if ( $tag->term_id == $gllrctgrs_options['default_gallery_category'] )
			unset( $actions['delete'] );
		return $actions;
	}
}
/**
 * Function for hide delete chekbox ( protect default category from deletion )
 *
*/
if ( ! function_exists( 'gllrctgrs_hide_delete_cb' ) ) {
	function gllrctgrs_hide_delete_cb() {
		global $gllrctgrs_options, $gllrctgrs_taxonomy;
		if ( ! isset( $_GET['taxonomy'] ) || $_GET['taxonomy'] != $gllrctgrs_taxonomy ) return; ?>
		<style type="text/css">
			input[value="<?php echo $gllrctgrs_options['default_gallery_category']; ?>"] {
				display: none;
			}
		</style>
	<?php }
}
/**
 * Function for reassignment categories after delete any category,
 * Protect default category from deletion 
 *
*/
if ( ! function_exists( 'gllrctgrs_delete_term' ) ) {
	function gllrctgrs_delete_term( $tt_id ) {
		global $post, $tag_ID, $gllrctgrs_taxonomy, $gllrctgrs_options;
		$term = get_term_by( 'term_taxonomy_id', $tt_id, $gllrctgrs_taxonomy );
		if ( !empty( $term ) ) {
			if ( empty( $gllrctgrs_options ) ) 
				$gllrctgrs_options = get_option( 'gllrctgrs_options' );
			$terms = get_terms( $gllrctgrs_taxonomy, array(
				'orderby'	=> 'count',
				'hide_empty'=> 0,
				'fields'	=> 'ids'
				) );
			if ( !empty( $terms ) ) {
				$args = array(
					'post_type'		=>	'gallery',
					'posts_per_page'=>	-1,
					'tax_query' 	=>	array(
						array(
							'taxonomy'	=>	$gllrctgrs_taxonomy,
							'field'		=>	'id',
							'terms'		=>	$terms,
							'operator'	=>	'NOT IN'
						)
					)
				);
				$new_query = new WP_Query( $args );
				if ( $new_query->have_posts() ) {
					$posts = $new_query->posts;
					foreach ( $posts as $post ) {
						wp_set_object_terms( $post->ID, $gllrctgrs_options['default_gallery_category'], $gllrctgrs_taxonomy );
					}
				}
				wp_reset_query();
			}
			if ( $tag_ID == $gllrctgrs_options['default_gallery_category'] ) {
				wp_die( __( "Can't delete", 'gllrctgrs' ) );
			}
		}
	}
}
/**
 * Function for delete options
 *
*/ 
if ( ! function_exists( 'gllrctgrs_plugin_uninstall' ) ) {
	function gllrctgrs_plugin_uninstall() {
		delete_option( 'gllrctgrs_options' );
	}
}

register_activation_hook( __FILE__, 'gllrctgrs_plugin_activate' );
add_action( 'admin_menu', 'gllrctgrs_admin_menu' );
add_action( 'init', 'gllrctgrs_init' );
add_action( 'admin_init', 'gllrctgrs_admin_init' );
add_action( 'admin_notices', 'gllrctgrs_show_notices' );
add_filter( 'plugin_row_meta', 'gllrctgrs_register_plugin_links', 10, 2 );
register_uninstall_hook( __FILE__, 'gllrctgrs_plugin_uninstall' );
?>