<?php
/**
 * Front End Sitemap
 *
 * @package    WordPress
 * @subpackage Sm_dashboard_pages_navigator_tree
 */

namespace SM\Pages_Navigator;

/**
 * Front End Sitemap
 *
 * Lists all pages in site with the exceptions of those marked for inclusion
 */
class Fe_Sitemap {

	/**
	 * Static Initialization
	 */
	public static function register() {
		/* Add Exclude Page Meta Box  */
		add_action( 'add_meta_boxes', [ get_called_class(), 'sm_sitemap_exclude_init' ], 10 );
		add_action( 'save_post', [ get_called_class(), 'sm_sitemap_exclude_save' ] );
		add_action( 'template_redirect', [ get_called_class(), 'sm_google_sitemap' ] );
		add_shortcode( 'sm_sitemap', [ get_called_class(), 'sm_sitemap' ] );
	}

	/**
	 * Create page for gsitemap.
	 */
	public static function sm_create_gsitemap() {
		$new_page_title = 'Google Sitemap';
		$page_check     = get_page_by_title( $new_page_title );
		$new_page       = [
			'post_type'    => 'page',
			'post_title'   => $new_page_title,
			'post_name'    => 'gsitemap',
			'post_content' => 'This page was created by SM Dashboard Pages Navigator Tree and is used for displaying your Google Sitemap. Publish to enable. View and use URL in Google Webmaster Tools to enable sitemap crawling. DO NOT CHANGE SLUG.',
			'post_status'  => 'draft',
			'post_author'  => 1,
		];
		if ( ! isset( $page_check->ID ) ) {
			$new_page_id = wp_insert_post( $new_page );
			update_post_meta( $new_page_id, '_sm_sitemap_exclude_completely', 'yes' );
		}
	}

	/**
	 * Permanently trash google sitemap page.
	 */
	public static function sm_remove_gsitemap() {
		$page_check = get_page_by_title( 'Google Sitemap' );
		if ( isset( $page_check->ID ) ) {
			wp_delete_post( $page_check->ID, true );
		}
	}

	/**
	 * Exclude from sitemap callback functioun for pages.
	 */
	public static function sm_sitemap_exclude_init() {
		add_meta_box(
			'sm_sitemap_exclude',
			__( 'Exclude from Sitemap', 'sm' ),
			[
				get_called_class(),
				'sm_exclude_form',
			],
			'page',
			'side',
			''
		);
	}

	/**
	 * Exclude from sitemap meta box form for pages.
	 */
	public static function sm_exclude_form() {
		global $post;
		$post_data                             = get_post_custom( $post->ID );
		$exclude                               = $post_data['sm_sitemap_exclude'][0];
		$sm_sitemap_exclude_cb_attr            = '';
		$sm_sitemap_exclude_completely_cb_attr = '';
		if ( get_post_meta( $post->ID, '_sm_sitemap_exclude', true ) === 'yes' ) {
			$sm_sitemap_exclude_cb_attr = 'checked="checked"';
		}
		if ( get_post_meta( $post->ID, '_sm_sitemap_exclude_completely', true ) === 'yes' ) {
			$sm_sitemap_exclude_completely_cb_attr = 'checked="checked"';
		}
		?>
		<div style="margin-bottom:10px;">
			<input type="checkbox" name="sm_sitemap_exclude" id="sm_sitemap_exclude"
				   value="yes" <?php echo esc_attr( $sm_sitemap_exclude_cb_attr ); ?> >
			<label for="sm_sitemap_exclude">Display page name in sitemap without link</label>
		</div>
		<input type="checkbox" name="sm_sitemap_exclude_completely" id="sm_sitemap_exclude_completely"
			   value="yes" <?php echo esc_attr( $sm_sitemap_exclude_completely_cb_attr ); ?> >
		<label for="sm_sitemap_exclude_completely">Exclude this page from sitemap</label>
		<?php
		// Output nonce for verification on submit.
		wp_nonce_field( plugin_basename( __FILE__ ), 'sm_sitemap_nonce' );
	}

	/**
	 * Sitemap form exclude autosave callback.
	 *
	 * @return bool|int
	 */
	public static function sm_sitemap_exclude_save() {
		global $post;

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post->ID;
		}

		$posted_post_type = static::get_server_post_param( 'post_type' );
		if ( empty( $posted_post_type ) ) {
			return 0;
		}
		if ( 'page' === static::get_server_post_param( 'post_type' ) ) {
			if ( ! current_user_can( 'edit_page', $post->ID ) ) {
				return 0;
			} elseif ( ! current_user_can( 'edit_post', $post->ID ) ) {
				return 0;
			}
		}

		update_post_meta( $post->ID, '_sm_sitemap_exclude', static::get_server_post_param( 'sm_sitemap_exclude' ) );
		update_post_meta( $post->ID, '_sm_sitemap_exclude_completely', static::get_server_post_param( 'sm_sitemap_exclude_completely' ) );

		return true;
	}

	/**
	 * Recusive function that when called loops through all pages of all levels up to 6 lvs deep.
	 *
	 * @param int $parent_id parent id for recursion.
	 * @param int $lvl       level deep for recursion.
	 *
	 * @return string
	 */
	public static function sm_pages_recursive( $parent_id, $lvl ) {
		$output = '';
		// get child pages.
		$args  = [
			'child_of' => $parent_id,
			'parent'   => $parent_id,
		];
		$pages = get_pages( $args );

		if ( $pages ) {
			$lvl ++;
			$hlvl = $lvl + 1;
			if ( $hlvl > 6 ) {
				$hlvl = 6;
			}

			$output .= "<ul class='level$lvl'>" . PHP_EOL;
			// loop through pages and add them to list.
			foreach ( $pages as $page ) {
				if ( get_post_meta( $page->ID, '_sm_sitemap_exclude_completely', true ) !== 'yes' ) {
					$output .= "<li id='$page->ID' class=\"treelimb\">" . PHP_EOL;
					$output .= "<div class='treeleaf'>" . PHP_EOL;
					// if exclude box checked or page uses 404template just publish page title without link, otherwise create link to page.
					if ( get_post_meta( $page->ID, '_wp_page_template', true ) === 'tpl-404.php' ||
						 get_post_meta( $page->ID, '_sm_sitemap_exclude', true ) === 'yes' ) {
						$output .= "<span class=\"level$hlvl\" >" . $page->post_title . '</span> ' . PHP_EOL;
						if ( current_user_can( 'edit_posts' ) ) {
							$output .= '<a class="editPage" href="' . get_edit_post_link( $page->ID ) . '">[edit page]</a>' . PHP_EOL;
						}
					} else {
						$output .= "<span class=\"level$hlvl\" ><a href=\"" . get_permalink( $page->ID ) . '">' . $page->post_title . '</a></span> ' . PHP_EOL;
						if ( current_user_can( 'edit_posts' ) ) {
							$output .= '<a class="editPage" href="' . get_edit_post_link( $page->ID ) . '">[edit page]</a>' . PHP_EOL;
						}
					}

					$output .= '</div>' . PHP_EOL;
					// recall function to see if child pages have children.
					$output .= static::sm_pages_recursive( $page->ID, $lvl );

					$output .= '</li>' . PHP_EOL;
				}
			}
			$output .= '</ul>' . PHP_EOL;
		}

		return $output;
	}

	/**
	 * Recursive function to dive into all pages and build them
	 *
	 * @param int $parent_id Parent Id.
	 * @param int $lvl       The level.
	 *
	 * @return string
	 */
	public static function sm_google_sitemap_recursive( $parent_id, $lvl ) {
		$output = '';
		// get child pages.
		$args = [
			'child_of'    => $parent_id,
			'parent'      => $parent_id,
			'post_type'   => 'page',
			'post_status' => 'publish',
		];
		// @codingStandardsIgnoreLine get_pages is fine to use ignore rule
		$the_pages = get_pages( $args );

		$args = [
			'post_parent' => $parent_id,
			'post_type'   => 'post',
			'numberposts' => - 1,
		];

		// @codingStandardsIgnoreLine get_posts is fine to use ignore rule
		$the_posts = get_posts( $args );

		$pages = array_merge( $the_pages, $the_posts );

		if ( $pages ) {

			// loop through pages and add them to list.
			foreach ( $pages as $page ) {

				// if exclude box checked or page uses 404template just publish page title without link, otherwise create link to page.
				if ( get_post_meta( $page->ID, '_wp_page_template', true ) === 'tpl-404.php' ||
					 get_post_meta( $page->ID, '_sm_sitemap_exclude', true ) === 'yes' ||
					 get_post_meta( $page->ID, '_sm_sitemap_exclude_completely', true ) === 'yes' ) {
					$output .= '';
				} else {
					$output .= '<url>' . PHP_EOL;
					$output .= '<loc>' . get_permalink( $page->ID ) . '</loc>' . PHP_EOL;
					$output .= '<lastmod>' . preg_replace( '~\s.*~', '', $page->post_modified ) . '</lastmod>' . PHP_EOL;
					$output .= '</url>' . PHP_EOL;
				}

				// recall function to see if child pages have children.
				$output .= self::sm_google_sitemap_recursive( $page->ID, $lvl );
			}
		}// end if pages
		return $output;
	}

	/**
	 * Get function which wraps XML around sitemap
	 */
	public static function get_sm_google_sitemap() {
		$ouput = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
		// extend output.
		$ouput .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
		$ouput .= static::sm_google_sitemap_recursive( 0, 0 ) . PHP_EOL;
		$ouput .= '</urlset> ' . PHP_EOL;

		return $ouput;
	}

	/**
	 * Dispalys google sitemap when gsitemap page is called.
	 */
	public static function sm_google_sitemap() {
		if ( is_page( 'gsitemap' ) ) {
			header( 'Content-type: text/xml' );
			add_action( 'template_redirect', 'sm_google_sitemap' );
			add_shortcode( 'sm_sitemap', 'sm_sitemap' );
			// @codingStandardsIgnoreLine TODO Escape XML
			echo static::get_sm_google_sitemap();
			exit();
		}
	}

	/**
	 * Shorcode [sm_sitemap] inserts sitemap into page content
	 *
	 * @param array $atts    The attributes.
	 * @param null  $content The content.
	 *
	 * @return string
	 */
	public static function sm_sitemap( $atts, $content = null ) {
		$ouput = '<style>' . PHP_EOL;
		// extend output.
		$ouput .= '#smSitemap h1,#smSitemap h2,#smSitemap h3,#smSitemap h4,#smSitemap h5,#smSitemap h6 { display:inline; }' . PHP_EOL;
		$ouput .= '</style>' . PHP_EOL;
		$ouput .= '<div id="smSitemap">' . static::sm_pages_recursive( 0, 0 ) . '</div>' . PHP_EOL;

		return $ouput;
	}

	/**
	 * Utility helper function for sanatizing globals
	 *
	 * @param string $key $_POST key that you want the value of.
	 *
	 * @return string
	 */
	public static function get_server_post_param( $key ) {
		if ( isset( $_POST[ $key ], $_POST['sm_sitemap_nonce'] ) &&
			 wp_verify_nonce( sanitize_key( $_POST['sm_sitemap_nonce'] ), plugin_basename( __FILE__ ) ) ) {
			return sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
		} else {
			return false;
		}

	}
}
