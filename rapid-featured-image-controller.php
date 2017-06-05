<?php
/**
 * Plugin Name:       Rapid Featured Image Controller
 * Plugin URI:        https://obiPlabon.im/
 * Description:       Easily add and delete post and page featured image from post or page list screen with very intuative interface.
 * Version:           1.0.0
 * Author:            Obi Plabon
 * Author URI:        http://fb.me/obiPlabon
 * Text Domain:       rfic
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /lang
 * GitHub Plugin URI: https://github.com/<owner>/<repo>
 */
 
/**
 * @package Rapid_Featured_Image_Controller
 * @author obiPlabon <obi.plabon@gmail.com>
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Rapid_Featured_Image_Controller' ) ) :

	class Rapid_Featured_Image_Controller {

		/**
		 * Plugin slug used in various Id related places
		 * @var string
		 */
		private $slug = 'rfic';
		
		/**
		 * Nonce used in add request
		 * @var string
		 */
		private $add_nonce = 'add_rfic_featured_image';
		
		/**
		 * Nonce used in delete request
		 * @var string
		 */
		private $delete_nonce = 'delete_rfic_featured_image';

		public function __construct() {
			// Make sure post has support for featured image
			if ( post_type_supports( 'post', 'thumbnail' ) ) {
				add_filter( 'manage_posts_columns', array( $this, 'add_column' ) );
				add_action( 'manage_posts_custom_column', array( $this, 'display_column' ), 10, 2 );
			}

			// Make sure page has support for featured image
			if ( post_type_supports( 'page', 'thumbnail' ) ) {
				add_filter( 'manage_pages_columns', array( $this, 'add_column' ) );
				add_action( 'manage_pages_custom_column', array( $this, 'display_column' ), 10, 2 );
			}

			// Add admin JavaScript and CSS
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

			// Add ajax request handler
			add_action( 'wp_ajax_rfic_add_featured_image', array( $this, 'add_featured_image' ) );
			add_action( 'wp_ajax_rfic_delete_featured_image', array( $this, 'delete_featured_image' ) );
		}

		/**
		 * Enqueue admin JavaScript and CSS
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
				strtoupper( $this->slug ),
				array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'btnTextNormal' => esc_html__( 'Set Featured Image', 'rfic' ),
					'btnTextEdit' => esc_html__( 'Change Featured Image', 'rfic' ),
					'modalTitle' => esc_html__( 'Featured Image', 'rfic' ),
					'modalBtnTextNormal' => esc_html__( 'Set Featured Image', 'rfic' ),
					'modalBtnTextEdit' => esc_html__( 'Change Featured Image', 'rfic' ),
				));
		}

		/**
		 * Add Rapid Featured Image Controller column
		 * @param array $columns Column items
		 * @return array Modified $columns
		 */
		public function add_column( $columns ) {
			return array_merge( $columns, array(
				$this->slug => sprintf(
					'<span class="dashicons dashicons-format-image" title="%1$s"><span class="screen-reader-text">%1$s</span></span>',
					esc_attr__( 'Featured Image', 'rfic' )
					),
			));
		}

		/**
		 * Output Rapid Featured Image Controller html
		 * @param  string $column  Column name
		 * @param  int $post_id Current post id
		 * @return void
		 */
		public function display_column( $column, $post_id ) {
			if ( $this->slug !== $column ) {
				return;
			}

			$image_id = 0;
			$button_text = __( 'Set Featured Image', 'rfic' );
			$has_image = has_post_thumbnail( $post_id );
			?>

			<div class="RapidFeaturedImageController">
				<figure class="RapidFeaturedImageController__holder">
				<?php
					if ( $has_image ) {
						$button_text = __( 'Change Featured Image', 'rfic' );
						$image_id = get_post_thumbnail_id( $post_id );
						echo wp_get_attachment_image( $image_id, 'thumbnail', '', array( 'class' => 'RapidFeaturedImageController__image' ) );
					}
				?>
				</figure>

				<?php
				printf( '<button type="button" class="RapidFeaturedImageController__button RapidFeaturedImageController__button--add js-RapidFeaturedImageController__add" data-post_id="%1$s" data-image_id="%2$s" data-nonce="%5$s" title="%4$s"><span class="screen-reader-text">%3$s</span><span class="dashicons dashicons-edit"></span></button>',
					esc_attr( $post_id ),
					esc_attr( $image_id ),
					esc_html( $button_text ),
					esc_attr( $button_text ),
					wp_create_nonce( $this->add_nonce )
				);

				printf( '<button type="button" class="RapidFeaturedImageController__button RapidFeaturedImageController__button--delete js-RapidFeaturedImageController__delete" data-post_id="%1$s" data-nonce="%4$s" title="%3$s" %5$s><span class="screen-reader-text">%2$s</span><span class="dashicons dashicons-trash"></span></button>',
						esc_attr( $post_id ),
						esc_html__( 'Delete Featured Image', 'rfic' ),
						esc_attr__( 'Delete Featured Image', 'rfic' ),
						wp_create_nonce( $this->delete_nonce ),
						$has_image ? '' : 'style="display:none"'
					);
				?>

			</div>

			<?php
		}

		/**
		 * Add ajax request handler
		 * @return void
		 */
		public function add_featured_image() {
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
		 * Delete ajax requet handler
		 * @return void
		 */
		public function delete_featured_image() {
			if ( ! isset( $_POST['post_id'] ) || ! wp_verify_nonce( $_POST['nonce'], $this->delete_nonce ) ) {
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

	new Rapid_Featured_Image_Controller;

endif;
