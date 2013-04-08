<?php
/*
Plugin Name: Widgets Master
Plugin URI: http://www.jeroenvanwissen.nl/weblog/wordpress/widgets-master
Description: Control the visibility of Widgets by Categories/Taxonomies, Pages, Post types and more.
Author: Jeroen van Wissen
Author URI: http://www.jeroenvanwissen.nl
Version: 0.2

------------------------------------------------------------------------
Copyright 2012 Jeroen van Wissen

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/

/**
 * Widgets Master class
 *
 * */
class Widgets_Master {

	/**
	 * version
	 *
	 * @var string
	 * */
	protected $version = '0.2';

	/**
	 * constructor
	 *
	 * @since 0.1
	 * @version 0.2
	 * */
	function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'in_widget_form', array( $this, 'widget_options' ), 10, 3 );
		add_filter( 'widget_display_callback', array( $this, 'widget_display_callback' ), 10, 1 );
		add_filter( 'widget_update_callback', array( $this, 'widget_update_callback' ), 10, 2 );
	}

	/**
	 * Load the CSS and JavaScripts needed.
	 *
	 * @since 0.1
	 * @version 0.1
	 * */
	public function enqueue_scripts() {
		if ( is_admin() ) {
			wp_enqueue_style( 'widgets-master', plugins_url( '/css/widgets-master.css', __FILE__ ), array(), $this->version, 'all' );
			wp_enqueue_script( 'widgets-master', plugins_url( '/js/widgets-master.js', __FILE__ ), array( 'jquery' ), $this->version, 'all' );
		}
	}

	/**
	 * Widget update callback function
	 *
	 * @param array   $instance     The widget instance
	 * @param array   $new_instance The widget instance
	 * @param array   $old_instance The widget instance
	 * @return array $instance The widget instance
	 * @since 0.1
	 * @version 0.1
	 * */
	public function widget_update_callback( $instance, $new_instance ) {
		$instance = $new_instance;

		// wpml compatibility
		if ( function_exists( 'icl_object_id' ) && defined( 'ICL_LANGUAGE_CODE' ) ) {
			$instance['language'] = ICL_LANGUAGE_CODE;
		}

		$instance['id_base'] = $_POST['id_base'];

		// show widget on home, check with is_home()
		$instance['page-home'] = false;
		if ( isset( $_POST[$instance['id_base'] . '-widgets-master-home'] ) ) {
			$instance['page-home'] = true;
		}

		// show widget on archive, check with is_archive()
		$instance['page-archive'] = false;
		if ( isset( $_POST[$instance['id_base'] . '-widgets-master-archive'] ) ) {
			$instance['page-archive'] = true;
		}

		// show widget on 404, check with is_404()
		$instance['page-404'] = false;
		if ( isset( $_POST[$instance['id_base'] . '-widgets-master-404'] ) ) {
			$instance['page-404'] = true;
		}

		// show widget on search, check with is_search()
		$instance['page-search'] = false;
		if ( isset( $_POST[$instance['id_base'] . '-widgets-master-search'] ) ) {
			$instance['page-search'] = true;
		}

		// show widget on single, check with is_single()
		$instance['page-single'] = false;
		if ( isset( $_POST[$instance['id_base'] . '-widgets-master-single'] ) ) {
			$instance['page-single'] = true;
		}

		// always show widget when nothing selected..
		$instance['all'] = false;
		if ( !$instance['page-home'] && !$instance['page-archive'] && !$instance['page-404'] && !$instance['page-search'] && !$instance['page-single'] ) {
			$instance['all'] = true;
		}

		// show widget on post type
		if ( isset( $_POST['posttype'] ) && is_array( $_POST['posttype'] ) ) {
			foreach ( $_POST['posttype'] as $posttype ) {
				$instance['type-' . $posttype] = true;
			}
			$instance['all'] = false;
		} else {
			$instance['type-all'] = true;
		}

		// show widget on taxonomy term
		if ( isset( $_POST['term_id'] ) && is_array( $_POST['term_id'] ) ) {
			foreach ( $_POST['term_id'] as $term_id ) {
				$instance['cat-' . $term_id] = true;
			}
			// why is this line?
			$instance['type-all'] = false;
			$instance['all'] = false;
		} else {
			$instance['cat-all'] = true;
		}

		// show widget on page
		if ( isset( $_POST['page_id'] ) && is_array( $_POST['page_id'] ) ) {
			foreach ( $_POST['page_id'] as $page_id ) {
				$instance['page-' . $page_id] = true;
			}
			$instance['all'] = false;
		} else {
			$instance['page-all'] = true;
		}

		return $instance;
	}

	/**
	 * Widget display callback function
	 *
	 * @param array   $instance The widget instance
	 * @return mixed $instance The widget instance or false
	 * @since 0.1
	 * @version 0.1
	 * */
	public function widget_display_callback( $instance ) {
		$show = false;

		// check for home / frontpage
		if ( ( is_home() && isset( $instance['page-home'] ) )  || ( is_front_page() && isset( $instance['page-home'] ) ) ) {
			$show = $instance['page-home'];

			// check for category
		} else if ( is_category() ) {
				$show = isset( $instance['cat-' . get_query_var( 'cat' )] ) ? ( $instance['cat-' . get_query_var( 'cat' )] ) : false;
				if ( !$show ) {
					$show = isset( $instance['cat-all'] ) ? ( $instance['cat-all'] ) : false;
				}

				// check for taxonomy/term
			} else if ( is_tax() ) {
				$term = get_term_by( 'slug', get_query_var( 'term' ), get_query_var( 'taxonomy' ) );
				$show = isset( $instance['cat-' . $term->term_id] ) ? ( $instance['cat-' . $term->term_id] ) : false;
				if ( !$show ) {
					$show = isset( $instance['cat-all'] ) ? ( $instance['cat-all'] ) : false;
				}

				// check for archive
			} else if ( is_archive() && isset( $instance['page-archive'] ) ) {
				$show = $instance['page-archive'];

				// check for single
			} else if ( is_single() ) {
				$type = get_post_type();
				if ( $type != 'page' and $type != 'post' ) {
					$show = isset( $instance['type-' . $type] ) ? ( $instance['type-' . $type] ) : false;
				}
				if ( !$show ) {
					$show = isset( $instance['page-single'] ) ? ( $instance['page-single'] ) : false;
				}
				if ( !$show ) {
					global $post;
					$terms = wp_get_object_terms( $post->ID, get_taxonomies() );
					foreach ( $terms as $term ) {
						if ( $show ) {
							continue;
						}
						if ( function_exists( 'icl_object_id' ) ) {
							$term_id = icl_object_id( $term->term_id, $term->taxonomy, true, $instance['language'] );
						} else {
							$term_id = $term->term_id;
						}
						$show = isset( $instance['cat-' . $term_id] ) ? ( $instance['cat-' . $term_id] ) : false;
					}
				}
				if ( !$show ) {
					$show = isset( $instance['type-all'] ) ? ( $instance['type-all'] ) : false;
				}

				// check for 404
			} else if ( is_404() ) {
				$show = isset( $instance['page-404'] ) ? ( $instance['page-404'] ) : false;

				// check for search
			} else if ( is_search() ) {
				$show = isset( $instance['page-search'] ) ? ( $instance['page-search'] ) : false;

				// check for page
			} else {
			global $wp_query;
			$post_id = $wp_query->get_queried_object_id();
			$show    = isset( $instance['page-' . $post_id] ) ? ( $instance['page-' . $post_id] ) : false;
			if ( !$show ) {
				$show = isset( $instance['page-all'] ) ? ( $instance['page-all'] ) : false;
			}
		}

		// Show widget when no options set.
		if ( !$show && isset( $instance['all'] ) && $instance['all'] == true ) {
			return $instance;

			// Show widget when options never saved. ( fist time use )
		} else if ( !$show && !isset( $instance['all'] ) ) {
				return $instance;

				// Show widget when options say so.
			} else if ( $show ) {
				return $instance;

				// Don't show widget
			} else {
			return false;
		}

	}

	/**
	 * Widget Options function
	 *
	 * @param object  $widget
	 * @param array   $return
	 * @param array   $instance
	 * @since 0.1
	 * @version 0.1
	 * */
	public function widget_options( $widget, $return, $instance ) {
?>
		<div id="<?php echo $widget->id_base; ?>-widgets-master" class="widgets-master">
			<h4 class="widgets-master-heading"><?php echo __( 'Widgets Master Display Conditions', 'widgets-master' ); ?></h4>
			<ul id="<?php echo $widget->id_base; ?>-widgets-master-tabs" class="widgets-master-tabs">
				<li class="active <?php echo $widget->id_base; ?>-widgets-master-pages"><a class="active" href="javascript:void(null);"><?php echo __( 'Pages', 'widgets-master' ); ?></a></li>
				<li class="<?php echo $widget->id_base; ?>-widgets-master-category"><a href="javascript:void(null);"><?php echo __( 'Taxonomies', 'widgets-master' ); ?></a></li>
				<li class="<?php echo $widget->id_base; ?>-widgets-master-posttypes"><a href="javascript:void(null);"><?php echo __( 'Post Types', 'widgets-master' ); ?></a></li>
			</ul>
			<div class="<?php echo $widget->id_base; ?>-widgets-master-pages tabs-panel">
				<ul>
					<?php $this->widget_page_list( $widget, $instance ); ?>
				</ul>
			</div>
			<div class="<?php echo $widget->id_base; ?>-widgets-master-category tabs-panel" style="display:none;">
				<ul>
					<?php $this->widget_category_list( $widget, $instance ); ?>
				</ul>
			</div>
			<div class="<?php echo $widget->id_base; ?>-widgets-master-posttypes tabs-panel" style="display:none;">
				<ul>
					<?php $this->widget_posttypes_list( $widget, $instance ); ?>
				</ul>
			</div>
		</div>
		<div class="<?php echo $widget->id_base; ?>-widgets-master-extra widgets-master-box extra">
			<label for="<?php echo $widget->id_base; ?>-widgets-master-home"><input type="checkbox" name="<?php echo $widget->id_base; ?>-widgets-master-home" class="widgets-master-checkbox" <?php echo isset( $instance['page-home'] ) && $instance['page-home'] == 1 ? 'checked=checked' : ''; ?>> <?php echo __( 'Homepage', 'widgets-master' ); ?></label><br />
			<label for="<?php echo $widget->id_base; ?>-widgets-master-archive"><input type="checkbox" name="<?php echo $widget->id_base; ?>-widgets-master-archive" class="widgets-master-checkbox" <?php echo isset( $instance['page-archive'] ) && $instance['page-archive'] == 1 ? 'checked=checked' : ''; ?>> <?php echo __( 'Archive', 'widgets-master' ); ?></label><br />
			<label for="<?php echo $widget->id_base; ?>-widgets-master-404"><input type="checkbox" name="<?php echo $widget->id_base; ?>-widgets-master-404" class="widgets-master-checkbox" <?php echo isset( $instance['page-404'] ) && $instance['page-404'] == 1 ? 'checked=checked' : ''; ?>> <?php echo __( '404 - Page not found', 'widgets-master' ); ?></label><br />
			<label for="<?php echo $widget->id_base; ?>-widgets-master-search"><input type="checkbox" name="<?php echo $widget->id_base; ?>-widgets-master-search" class="widgets-master-checkbox" <?php echo isset( $instance['page-search'] ) && $instance['page-search'] == 1 ? 'checked=checked' : ''; ?>> <?php echo __( 'Search', 'widgets-master' ); ?></label><br />
			<label for="<?php echo $widget->id_base; ?>-widgets-master-single"><input type="checkbox" name="<?php echo $widget->id_base; ?>-widgets-master-single" class="widgets-master-checkbox" <?php echo isset( $instance['page-single'] ) && $instance['page-single'] == 1 ? 'checked=checked' : ''; ?>> <?php echo __( 'Single', 'widgets-master' ); ?></label><br />
		</div>
		<?php
	}

	/**
	 * Widget Page list function
	 *
	 * @param object  $widget
	 * @param array   $instance
	 * @since 0.1
	 * @version 0.2
	 * */
	public function widget_page_list( $widget, $instance ) {
		$pages = get_pages();
		foreach ( $pages as $page ) {
			$checked = isset( $instance['page-' . $page->ID] ) ? 'checked=checked' : '';
			echo '<li><input type="checkbox" name="page_id[]" value="' . $page->ID . '" ' . $checked . '> ' . $page->post_title . '</li>';
		}
	}

	/**
	 * Widget Category list function
	 *
	 * @param object  $widget
	 * @param array   $instance
	 * @since 0.1
	 * @version 0.2
	 * */
	public function widget_category_list( $widget, $instance ) {
		$taxonomies = get_taxonomies( array( 'public' => true ), 'names', 'and' );
		foreach ( $taxonomies as $taxonomy ) {
			echo '<h3>' . $taxonomy . '</h3>';
			$terms = get_terms( $taxonomy, array( 'hide_empty' => 0 ) );
			if ( is_array( $terms ) && count( $terms ) > 0 ) {
				echo '<ul>';
				foreach ( $terms as $term ) {
					$checked = isset( $instance['cat-' . $term->term_id] ) ? 'checked=checked' : '';
					echo '<li><input type="checkbox" name="term_id[]" value="' . $term->term_id . '" ' . $checked . '> ' . $term->name . '</li>';
				}
				echo '</ul>';
			}
		}
	}

	/**
	 * Widget PostTypes list function
	 *
	 * @param object  $widget
	 * @param array   $instance
	 * @since 0.1
	 * @version 0.2
	 * */
	public function widget_posttypes_list( $widget, $instance ) {
		$posttypes = get_post_types( array( '_builtin' => false ) );
		foreach ( $posttypes as $posttype ) {
			$checked = isset( $instance['type-' . $posttype] ) ? 'checked=checked' : '';
			echo '<li><input type="checkbox" name="posttype[]" value="' . $posttype . '" ' . $checked . ' > ' . ucfirst( $posttype ) . '</li>';
		}
	}

} // END class Widgets_Master

$widgets_master = new Widgets_Master();
