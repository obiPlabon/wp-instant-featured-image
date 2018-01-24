<?php
/**
 * Plugin Name:       WP Instant Featured Image
 * Plugin URI:        https://github.com/obiPlabon/wp-instant-featured-image
 * Description:       Easily add, remove and update your post or page featured image from post or page list screen. It's super intuitive.
 * Version:           1.0.0
 * Author:            Obi Plabon
 * Author URI:        https://obiPlabon.im
 * Text Domain:       wp-ifi
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /lang
 * GitHub Plugin URI: https://github.com/obiPlabon/wp-instant-featured-image
 * 
 * 
 * @package WP_Instant_Featured_Image
 * @author obiPlabon <obi.plabon@gmail.com>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Instant_Featured_Image' ) ) :

	class WP_Instant_Featured_Image {

		/**
		 * Plugin slug used in various Id related places
		 * 
		 * @var string
		 */
		private $slug = 'wp-ifi';
		
		/**
		 * Nonce used in add request
		 * 
		 * @var string
		 */
		private $add_nonce = 'add_wp_ifi';
		
		/**
		 * Nonce used in remove request
		 * 
		 * @var string
		 */
		private $remove_nonce = 'remove_wp_ifi';

		/**
		 * List of supported post types
		 *
		 * @var array
		 */
		private $supported_post_types = array();

		public function __construct() {
			// Stop early if user cannot upload file
			if ( ! current_user_can( 'upload_files' ) ) {
				return;
			}

			// Set supported post types
			$this->supported_post_types = apply_filters( 'wp_ifi_supported_post_types', array( 'post', 'page' ) );

			// Make sure post has support for featured image
			foreach ( $this->supported_post_types as $post_type ) {
				if ( current_theme_supports( 'post-thumbnails', $post_type ) && post_type_supports( $post_type, 'thumbnail' ) ) {
					add_filter( 'manage_posts_columns', array( $this, 'add_column' ) );
					add_action( 'manage_posts_custom_column', array( $this, 'display_column' ), 10, 2 );
				}
			}

			// Add admin JavaScript and CSS
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

			// Add ajax request handler
			add_action( 'wp_ajax_wp_ifi_add', array( $this, 'handle_add_request' ) );
			add_action( 'wp_ajax_wp_ifi_remove', array( $this, 'handle_remove_request' ) );
		}

		/**
		 * Enqueue admin JavaScript and CSS
		 * 
		 * @return void
		 */
		public function enqueue_assets() {
			wp_enqueue_style(
				$this->slug,
				plugin_dir_url( __FILE__ ) . 'assets/css/style.css',
				array(),
				'1.0.0'
				);

			wp_enqueue_media();
			wp_enqueue_script(
				$this->slug,
				plugin_dir_url( __FILE__ ) . 'assets/js/script.js',
				array( 'jquery' ),
				'1.0.0',
				true
				);

			wp_localize_script(
				$this->slug,
				'WP_IFI',
				array(
					'ajaxurl'            => esc_raw_url( admin_url( 'admin-ajax.php' ) ),
					'btnTextNormal'      => esc_html__( 'Set Featured Image', 'wp-ifi' ),
					'btnTextEdit'        => esc_html__( 'Change Featured Image', 'wp-ifi' ),
					'modalTitle'         => esc_html__( 'Featured Image', 'wp-ifi' ),
					'modalBtnTextNormal' => esc_html__( 'Set Featured Image', 'wp-ifi' ),
					'modalBtnTextEdit'   => esc_html__( 'Change Featured Image', 'wp-ifi' ),
				)
			);
		}

		/**
		 * Add a custom column
		 * 
		 * @param array $columns Column items
		 * @return array Modified $columns
		 */
		public function add_column( $columns ) {
			return array_merge( $columns, array(
				$this->slug => sprintf(
					'<span class="dashicons dashicons-format-image" title="%1$s"><span class="screen-reader-text">%1$s</span></span>',
					esc_attr__( 'Featured Image', 'wp-ifi' )
					),
			));
		}

		/**
		 * Render custom column content
		 * 
		 * @param  string $column Column name
		 * @param  int $post_id Current post id
		 * @return void
		 */
		public function display_column( $column, $post_id ) {
			if ( $this->slug !== $column ) {
				return;
			}

			$image_id = 0;
			$button_text = __( 'Set Featured Image', 'wp-ifi' );
			$has_image = has_post_thumbnail( $post_id );
			?>

			<div class="wp-ifi">
				<figure class="wp-ifi__figure">
				<?php
					if ( $has_image ) {
						$button_text = __( 'Change Featured Image', 'wp-ifi' );
						$image_id = get_post_thumbnail_id( $post_id );
						echo wp_get_attachment_image( $image_id, 'thumbnail', '', array( 'class' => 'wp-ifi__image' ) );
					}
				?>
				</figure>

				<?php
				printf( '<button type="button" class="wp-ifi__button wp-ifi__button--add js-wp-ifi__add" data-post_id="%1$s" data-image_id="%2$s" data-nonce="%5$s" title="%4$s"><span class="screen-reader-text">%3$s</span><span class="dashicons dashicons-edit"></span></button>',
					esc_attr( $post_id ),
					esc_attr( $image_id ),
					esc_html( $button_text ),
					esc_attr( $button_text ),
					wp_create_nonce( $this->add_nonce )
				);

				printf( '<button type="button" class="wp-ifi__button wp-ifi__button--remove js-wp-ifi__remove" data-post_id="%1$s" data-nonce="%4$s" title="%3$s" %5$s><span class="screen-reader-text">%2$s</span><span class="dashicons dashicons-trash"></span></button>',
						esc_attr( $post_id ),
						esc_html__( 'Remove Featured Image', 'wp-ifi' ),
						esc_attr__( 'Remove Featured Image', 'wp-ifi' ),
						wp_create_nonce( $this->remove_nonce ),
						$has_image ? '' : 'style="display:none"'
					);
				?>

			</div>

			<?php
		}

		/**
		 * Add ajax request handler
		 * 
		 * @return void
		 */
		public function handle_add_request() {
			if ( ! isset( $_POST['post_id'] ) || ! isset( $_POST['image_id'] ) ) {
				return;
			}

			if ( ! wp_verify_nonce( $_POST['nonce'], $this->add_nonce ) ) {
				return;
			}

			$image_id = absint( $_POST['image_id'] );
			$post_id = absint( $_POST['post_id'] );

			if ( set_post_thumbnail( $post_id, $image_id ) ) {
				wp_send_json_success();
			} else {
				wp_send_json_error();
			}
			exit;
		}
		
		/**
		 * Remove ajax requet handler
		 * 
		 * @return void
		 */
		public function handle_remove_request() {
			if ( ! isset( $_POST['post_id'] ) || ! wp_verify_nonce( $_POST['nonce'], $this->remove_nonce ) ) {
				return;
			}

			$post_id = absint( $_POST['post_id'] );
			
			if ( delete_post_thumbnail( $post_id ) ) {
				wp_send_json_success();
			} else {
				wp_send_json_error();
			}
			exit;
		}
	}

	new WP_Instant_Featured_Image;

endif;
