<?php
/*
Plugin Name: Gallery Categories by BestWebSoft
Plugin URI: http://bestwebsoft.com/products/
Description: Add-on for Gallery Plugin by BestWebSoft.
Author: BestWebSoft
Version: 1.0.2
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
		bws_add_general_menu( plugin_basename( __FILE__ ) );
	}
}
/**
 * Initialize plugin
 * 
 */
if ( ! function_exists( 'gllrctgrs_init' ) ) {
	function gllrctgrs_init() {
		global $gllrctgrs_gallery_not_ready, $gllrctgrs_options, $gllrctgrs_plugin_info;		

		require_once( dirname( __FILE__ ) . '/bws_menu/bws_functions.php' );

		if ( empty( $gllrctgrs_plugin_info ) ) {
			if ( ! function_exists( 'get_plugin_data' ) )
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			$gllrctgrs_plugin_info = get_plugin_data( __FILE__ );
		}
		/* Function check if plugin is compatible with current WP version  */
		bws_wp_version_check( plugin_basename( __FILE__ ), $gllrctgrs_plugin_info, '3.2' );

		gllrctgrs_check();
		if ( '' == $gllrctgrs_gallery_not_ready ) {
			gllrctgrs_register_taxonomy();
			if ( empty( $gllrctgrs_options ) )
				$gllrctgrs_options = get_option( 'gllrctgrs_options' );
			if ( empty( $gllrctgrs_options ) ) {
				gllrctgrs_set_options();
				gllrctgrs_add_default_term_all_gallery();
			}
			add_filter( 'pre_get_posts', 'gllrctgrs_categories_get_posts' );
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
			add_option( 'gllrctgrs_options', $gllrctgrs_options_defaults );
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
 * Change contents of main loop
 * 
 */
if ( ! function_exists( 'gllrctgrs_categories_get_posts' ) ) {
	function gllrctgrs_categories_get_posts( $query ) {
		if ( isset( $query->query_vars['gallery_categories'] ) && ( ! is_admin() ) )
			$query->set( 'post_type', array( 'gallery' ) ); 
		return $query;
	}
}
/**
 * Set term slug as value of <option> tag
 * ( used for WP 3.1 - 4.1 )  
 */
if ( ! class_exists( 'Gllr_CategoryDropdown' ) ) {
	class Gllr_CategoryDropdown extends Walker_CategoryDropdown {
		/**
		 * Start the element output.
		 * @param string $output   Passed by reference. Used to append additional content.
		 * @param object $term     Category data object.
		 * @param int    $depth    Depth of category in reference to parents. Default 0.
		 * @param array  $args     An array of arguments.
		 * @param int    $id       ID of the current term.
		 */
		function start_el( &$output, $term, $depth = 0, $args = array(), $id = 0 ) {
			$term_name = apply_filters( 'list_cats', $term->name, $term );
			$output  .= '<option class="level-' . $depth . '" value="' . $term->slug . '"';
			if ( $term->slug == $args['selected'] ) { 
			    $output .= ' selected="selected"';
			}
			$output .= '>';
			$output .= str_repeat( '&nbsp;', $depth * 3 ) . $term_name;
			if ( $args['show_count'] )
			    $output .= '&nbsp;(' . $term->count .')';
			$output .= '</option>';
		}
	}
}

/**
 * Class extends WP class WP_Widget, and create new widget
 * Gallery Categories widget
 */
if ( ! class_exists( 'gallery_categories_widget' ) ) {
	class gallery_categories_widget extends WP_Widget {
		/** 
		 * constructor of class 
		 */
		public function __construct() {
			$widget_ops = array( 'classname' => 'gallery_categories_widget', 'description' => __( "A list or dropdown of Gallery categories.", 'gllrctgrs' ) );
			parent::__construct( 'gallery_categories_widget', __( 'Gallery Categories', 'gllrctgrs' ), $widget_ops );
		}
		/**
		* Function to displaying widget in front end
		*
		*/
		public function widget( $args, $instance ) {
			global $wp_version;
			$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? __( 'Gallery Categories', 'gllrctgrs' ) : $instance['title'], $instance, $this->id_base );
			$c = ! empty( $instance['count'] ) ? '1' : '0';
			$h = ! empty( $instance['hierarchical'] ) ? '1' : '0';
			$d = ! empty( $instance['dropdown'] ) ? '1' : '0';

			/* Get value of HTTP Request */
			if ( isset( $_REQUEST['gallery_categories'] ) ) {
				$term = get_term_by( 'slug', $_REQUEST['gallery_categories'], 'gallery_categories' );
			} else {
				global $wp;
				$http_request = parse_url( add_query_arg( $wp->query_string, '', home_url( $wp->request ) ) );
				if ( isset( $http_request['query'] ) && preg_match( '/gallery_categories/' ,$http_request['query'] ) )
					$term = get_term_by( 'slug', substr( $http_request['query'], strpos( $http_request['query'], "=" ) + 1 ), 'gallery_categories' );
			}

			echo $args['before_widget'];
			if ( $title ) {
				echo $args['before_title'] . $title . $args['after_title'];
			}
			$cat_args = array(
				'orderby'      => 'name',
				'show_count'   => $c,
				'hierarchical' => $h
			);
			if ( $d ) {
				static $first_dropdown = true;
				$dropdown_id     = ( $first_dropdown ) ? 'gllr_cat' : 'gllr_cat_' . $this->number;
				$first_dropdown  = false;
				echo '<label class="screen-reader-text" for="' . esc_attr( $dropdown_id ) . '">' . $title . '</label>';
				if ( 4.2 >= $wp_version ) {
					$cat_args['walker']       = new Gllr_CategoryDropdown();
					$cat_args['selected']     = isset( $term ) && ( ! empty( $term ) ) ? $term->slug : '-1';
				} else {	
					$cat_args['value_field']  = 'slug';
					$cat_args['selected']     = isset( $term ) && ( ! empty( $term ) ) ? $term->term_id : -1;
				}
				$cat_args['show_option_none'] = __( 'Select Gallery Category', 'gllrctgrs' );
				$cat_args['taxonomy']         = 'gallery_categories';
				$cat_args['title_li']         = __( 'Gallery Categories', 'gllrctgrs' );
				$cat_args['name']             = 'gallery_categories';
				$cat_args['id']               = $dropdown_id; ?>
				<form action="<?php bloginfo( 'url' ); ?>/" method="get">
					<?php wp_dropdown_categories( apply_filters( 'widget_categories_dropdown_args', $cat_args ) ); ?>
					<script type='text/javascript'>
						(function() {
							var dropdown = document.getElementById( "<?php echo esc_js( $dropdown_id ); ?>" );
							function onCatChange() {
								if ( dropdown.options[ dropdown.selectedIndex ].value != -1 ) {
									location.href = "<?php echo home_url(); ?>/?gallery_categories=" + dropdown.options[ dropdown.selectedIndex ].value;
								}
							}
							dropdown.onchange = onCatChange;
						})();
					</script>
					<noscript>
						<br />
						<input type="submit" value="<?php _e( 'View', 'gllrctgrs' ); ?>" />
					</noscript>
				</form>
			<?php } else { ?>
				<ul> 
					<?php $cat_args['show_option_none'] = __( 'Gallery Categories', 'gllrctgrs' );
						  $cat_args['taxonomy']         = 'gallery_categories';
						  $cat_args['title_li']         = '';
					wp_list_categories( apply_filters( 'widget_categories_args', $cat_args ) ); ?>
				</ul>
			<?php }
			echo $args['after_widget'];
		}
		/**
		 * Function to save widget settings
		 * @param array()    $new_instance  array with new settings
		 * @param array()    $old_instance  array with old settings
		 * @return array()   $instance      array with updated settings
		 */
		public function update( $new_instance, $old_instance ) {
			$instance 					= $old_instance;
			$instance['title']			= strip_tags( $new_instance['title'] );
			$instance['count']			= ! empty( $new_instance['count'] ) ? 1 : 0;
			$instance['hierarchical']	= ! empty( $new_instance['hierarchical'] ) ? 1 : 0;
			$instance['dropdown']		= ! empty( $new_instance['dropdown'] ) ? 1 : 0;
			return $instance;
		}
		/**
		 * Function to displaying widget settings in back end
		 * @param  array()     $instance  array with widget settings
		 * @return void
		 */
		public function form( $instance ) {
			$instance     = wp_parse_args( ( array ) $instance, array( 'title' => '' ) );
			$title        = esc_attr( $instance['title'] );
			$count        = isset( $instance['count'] ) ? ( bool ) $instance['count'] : false;
			$hierarchical = isset( $instance['hierarchical'] ) ? ( bool ) $instance['hierarchical'] : false;
			$dropdown     = isset( $instance['dropdown'] ) ? ( bool ) $instance['dropdown'] : false; ?>
			<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'gllrctgrs' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></p>
			<p><input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'dropdown' ); ?>" name="<?php echo $this->get_field_name( 'dropdown' ); ?>"<?php checked( $dropdown ); ?> />
			<label for="<?php echo $this->get_field_id( 'dropdown' ); ?>"><?php _e( 'Display as dropdown', 'gllrctgrs' ); ?></label><br />
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'count' ); ?>" name="<?php echo $this->get_field_name( 'count' ); ?>"<?php checked( $count ); ?> />
			<label for="<?php echo $this->get_field_id( 'count' ); ?>"><?php _e( 'Show gallery counts', 'gllrctgrs' ); ?></label><br />
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'hierarchical' ); ?>" name="<?php echo $this->get_field_name( 'hierarchical' ); ?>"<?php checked( $hierarchical ); ?> />
			<label for="<?php echo $this->get_field_id( 'hierarchical' ); ?>"><?php _e( 'Show hierarchy', 'gllrctgrs' ); ?></label></p>
		<?php }
	}
}
/**
 * Function to register widgets
 * 
*/
if ( ! function_exists( 'gllrctgrs_register_widget' ) ) {
	function gllrctgrs_register_widget() {
		global $gllrctgrs_gallery_not_ready;
		load_plugin_textdomain( 'gllrctgrs', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		gllrctgrs_check();
		if ( '' == $gllrctgrs_gallery_not_ready )
			register_widget( 'gallery_categories_widget' );
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
			foreach ( $posts as $post ) {
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
						'slug'			=> 'default'
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
		delete_option( 'widget_gallery_categories_widget' );
		delete_option( 'gllrctgrs_options' );
	}
}

register_activation_hook( __FILE__, 'gllrctgrs_plugin_activate' );
add_action( 'admin_menu', 'gllrctgrs_admin_menu' );
add_action( 'widgets_init', 'gllrctgrs_register_widget' );
add_action( 'init', 'gllrctgrs_init' );
add_action( 'admin_init', 'gllrctgrs_admin_init' );
add_action( 'admin_notices', 'gllrctgrs_show_notices' );
add_filter( 'plugin_row_meta', 'gllrctgrs_register_plugin_links', 10, 2 );
register_uninstall_hook( __FILE__, 'gllrctgrs_plugin_uninstall' );