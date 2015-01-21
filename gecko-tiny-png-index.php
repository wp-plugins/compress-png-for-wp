<?php
/*
 *	Plugin Name: Compress PNG for WP
 *	Plugin URI: http://www.geckodesigns.com
 *	Description: Compress PNG files using the TinyPNG API.
 *	Version: 1.3.5
 *	Author: Gecko Designs
 *	Author URI: http://www.geckodesigns.com
 *	License: GPLv2
 *
*/

if ( !class_exists( 'GD_Tiny_PNG' ) ) {
	class GD_Tiny_PNG {

		private $url = 'https://api.tinypng.com/shrink';

		public function __construct() {

			define( 'GD_TINY_PNG_META', 'tiny_png_response' );
			define( 'GD_TINY_PNG_PREV_COMPRESSED', 'prev_compressed' );
			define( 'GD_PLUGIN_NAME', 'Compress PNG for WP' );
			$is_auto_shrink = get_option( 'gd_tiny_png_auto_shrink', 'on' );

			if ( 'on' == $is_auto_shrink ) {
				add_filter( 'jpeg_quality', array( &$this, 'jpeg_full_quality' ) );
				add_filter( 'wp_generate_attachment_metadata', array( &$this, 'parse_meta_data' ), 10, 2 );
			}else {

			}

			add_filter( 'manage_media_columns', array( &$this, 'add_gd_tiny_png_media_column' ) );
			add_action( 'manage_media_custom_column', array( &$this, 'render_gd_tiny_png_media_column' ), 10, 2 );
			add_action( 'admin_action_gd_tinypng_compress_existing', array( &$this, 'compress_existing' ) );
			add_action( 'admin_init', array( &$this, 'setup_gd_tiny_png_settings' ) );
			add_action( 'admin_head-upload.php', array( &$this, 'add_bulk_actions_via_javascript' ) );
			add_action( 'admin_action_bulk_compress_png', array( &$this, 'bulk_action_handler' ) );

			if ( !function_exists( 'curl_init' ) ) {
				add_action( 'admin_notices', array( &$this, 'no_curl_admin_notices' ) );
			}

		}


		function jpeg_full_quality( $quality ) { return 100; }

		/**
		 * Displays error message in plugins page, media library, and new media pages.
		 */
		function no_curl_admin_notices() {
			global $pagenow;
			if ( 'plugins.php' == $pagenow ) {
				echo "<div class='error'> The php curl extension is not enabled.
					Compress PNG for WP will not be functional without the use of curl.</div>";
			}elseif ( 'media-new.php' == $pagenow || 'upload.php' == $pagenow ) {
				echo "<div class='error'> The php curl extension is not enabled.
					Compress PNG for WP will not compress any png files.</div>";
			}

		}


		/**
		 * Takes the metadata from the wp_generate_attachment_metadata filter and sends the png file
		 * to Tiny PNG to shrink.
		 *
		 * @param array   $meta The attachment metadata passed from wp_generate_attachment_metadata.
		 * @param int     $id   The attachment id passed from wp_generate_attachment_metadata.
		 * @return array       Since it is a filter, must return the meta passed in.
		 */
		function parse_meta_data( $meta , $id ) {
			//get mime-type
			$mime_type      = get_post_mime_type( $id );
			$prev_compressed = get_post_meta( $id, GD_TINY_PNG_PREV_COMPRESSED, true );
			//initial post meta is an empty string
			if ( !is_array( $prev_compressed ) ) {
				$prev_compressed = array();
			}
			$compressed_sizes = $prev_compressed;

			if ( $mime_type == 'image/png' | $mime_type == 'image/jpeg' ) {
				//get full path to uploaded image
				$upload_dir = wp_upload_dir();
				$base_dir   = $upload_dir['basedir'];

				//get filepath of 'sizes' images in metadata
				$path_split = preg_split( '/\//', $meta['file'] );
				$path_no_filename_array = array_splice( $path_split, 0, count( $path_split )-1 );
				$path_after_uploads = '';
				foreach ( $path_no_filename_array as $value ) {
					$path_after_uploads .= $value.'/';
				}

				//compress thumbnails selected by user in Settings -> Media page.
				$sizes_option = get_option( 'gd_tiny_png_sizes_option' );
				$sizes_meta = $meta['sizes'];
				if ( is_array( $sizes_option ) ) {
					foreach ( $sizes_option as $size_key => $option ) {
						if ( array_key_exists( $size_key, $sizes_meta ) ) {
							$thumb_array = $meta['sizes'][$size_key];
							$this_filepath = $upload_dir['basedir'].'/'.$path_after_uploads.$thumb_array['file'];
							//Only compress if not already compressed
							if ( !in_array( $size_key, $prev_compressed ) ) {
								$msg_meta = $this->tiny_png_request( $this_filepath, $id );
								if ( is_array( $msg_meta ) && array_key_exists( 'ratio', $msg_meta ) ) {
									//successful compression
									array_push( $compressed_sizes, $size_key );
								}
							}
						}
					}
				}

				//compress original file only if not already compressed.
				$filepath = $base_dir.'/'.$meta['file'];
				if ( !in_array( 'original', $prev_compressed ) ) {
					$msg_meta = $this->tiny_png_request( $filepath, $id, true );
					if ( is_array( $msg_meta ) && array_key_exists( 'ratio', $msg_meta ) ) {
						//successful compression
						array_push( $compressed_sizes, 'original' );
					}

				}
				update_post_meta( $id, GD_TINY_PNG_PREV_COMPRESSED,  $compressed_sizes, $prev_compressed );
			}
			return $meta;

		}

		/**
		 * POSTs a file to the Tiny PNG API. Sends result to process_result(). Updates post meta to
		 * keep track of information on the file.
		 *
		 * @param string  $file        File path for file to be sent to Tiny PNG
		 * @param int     $id          Id of the current post related to the file.
		 * @param boolean $is_original Used to determine whether to update post meta or not
		 */
		function tiny_png_request( $file, $id, $is_original = false ) {

			if ( !function_exists( 'curl_init' ) ) {

				$msg_meta  = 'The php curl extension was not enabled and this file was not compressed.';

			} else {

				$key = get_option( 'gd_tiny_png_key', '' );
				$key = trim( $key );

				$request = curl_init();
				$curl_opts = array(
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_URL => $this->url,
					CURLOPT_USERAGENT => GD_PLUGIN_NAME,
					CURLOPT_POST => true,
					CURLOPT_USERPWD => 'api:' . $key,
					CURLOPT_BINARYTRANSFER => true,
					CURLOPT_POSTFIELDS => file_get_contents( $file ),
					//CURLOPT_CAINFO => plugin_dir_path( __FILE__ ) . "cacert.pem",
					CURLOPT_SSL_VERIFYPEER => false
				);
				curl_setopt_array( $request, $curl_opts );
				$response = curl_exec( $request );
				$result_JSON = json_decode( $response );

				if ( 201 === curl_getinfo( $request, CURLINFO_HTTP_CODE ) ) {
					/* Compression was successful, retrieve output from the JSON response. */
					$new_img_url = $result_JSON->{'output'}->{'url'};
					$request = curl_init();
					curl_setopt_array( $request, array(
							CURLOPT_URL => $new_img_url,
							CURLOPT_RETURNTRANSFER => true,
							CURLOPT_SSL_VERIFYPEER => false
						) );
					$new_file = curl_exec( $request );
					$filesize = curl_getinfo($request, CURLINFO_SIZE_DOWNLOAD);
					curl_close( $request );

					if(!$filesize == 0) {
						/* Fetching the compressed image was successful*/
						file_put_contents( $file, $new_file );
						$input_size  = $result_JSON->{'input'}->{'size'};
						$output_size = $result_JSON->{'output'}->{'size'};
						$ratio       = $result_JSON->{'output'}->{'ratio'}*100;
						$msg_meta    = array( 'input'  => $input_size,
											'output' => $output_size,
											'ratio'  => $ratio );
					} else {
						$msg_meta = 'There was an error downloading your compressed image, the original image was saved instead.';	
					}
				} else {
					$msg_meta = $result_JSON->{'error'} . ': ' . $result_JSON->{'message'};
					$msg_meta .= '. There was an error compressing your image, the original image was saved instead.';
				}
			}

			if ( $is_original ) {
				update_post_meta( $id, GD_TINY_PNG_META, $msg_meta );
			}

			return $msg_meta;
		}

		/**
		 * Creates a column in the Media Library for our plugin.
		 *
		 * @param array   $columns The array of columns in the Media Library
		 */
		function add_gd_tiny_png_media_column( $columns ) {
			$columns['gd_tiny_png'] = GD_PLUGIN_NAME;
			return $columns;
		}

		/**
		 * Displays information in our plugin's column in the Media Library. Checks whether each
		 * attachment is a png and if it has been compressed. Displays appropriate information.
		 *
		 * @param string  $column_name Current name of the column passed from            manage_media_custom_column action.
		 * @param int     $id          Id of current attachment passed from manage_media_custom_column action.
		 */
		function render_gd_tiny_png_media_column( $column_name, $id ) {
			if ( 'gd_tiny_png' == $column_name ) {
				// Check if tiny_png_response field is set for this $id
				$post_meta = get_post_meta( $id, GD_TINY_PNG_META );
				$prev_compressed = get_post_meta( $id, GD_TINY_PNG_PREV_COMPRESSED, true );
				$mime_type = get_post_mime_type( $id );

				if ( $mime_type == 'image/png' | $mime_type == 'image/jpeg' ) {
					// If post_meta then display stats this attachment
					if ( isset( $post_meta ) && !empty( $post_meta ) ) {

						$data = $post_meta[0];
						if ( is_array( $data ) ) {
							//use  kB (1000 byte==1 kilobytes) not KiB (1024 byte == 1 Kibibyte) converstion , to match tinyPNG's unit of measurement, wordpress's size_format() displays incorrent unit names, ie. kB instead of KiB and so will not match this filesize
							$input_kb  = round( intval( $data['input'] )/1000 ) ;
							$output_kb = round( intval( $data['output'] )/1000 );

							echo 'Original Size: '.$input_kb.' KB<br/>';
							echo 'Current  Size: '.$output_kb.' KB<br/>';
							echo 'Ratio: '.$data['ratio'].'% <br/>';
							if ( is_array( $prev_compressed ) && !empty( $prev_compressed ) ) {
								echo 'Compressed Sizes: <br/>';
								echo '<div style="padding-left:1em;">';
								$i = 0;
								$len = count($prev_compressed);
								foreach ( $prev_compressed as $size ) {
									if ($i == $len - 1){
										echo $size;
									}else{
										echo "$size, ";
									}
									$i++;
								}
								echo '</div>';
							}
							printf( "<br><a href=\"admin.php?action=gd_tinypng_compress_existing&amp;attachment_ID=%d\">Compress now.</a>",
								$id );

						}
						else {

							echo $data;
							printf( "<br><a href=\"admin.php?action=gd_tinypng_compress_existing&amp;attachment_ID=%d\">Compress now.</a>",
								$id );
						}

					} else {
						echo 'Not compressed yet.';
						//Borrowed from WP Smush.it custom_column() line 614
						printf( "<br><a href=\"admin.php?action=gd_tinypng_compress_existing&amp;attachment_ID=%d\">Compress now.</a>",
							$id );
					}//end post_meta isset if
				} else {
					echo "Not a jpeg/png file, Tiny PNG/JPEG cannot compress.";
				}//end mime-type if
			}//end column name if
		}

		/**
		 * Add settings section, add settings fields, and register settings to the media settings.
		 *
		 */
		function setup_gd_tiny_png_settings() {
			add_settings_section( 'gd_tiny_png_settings', 'Gecko Designs Compress PNG for WP', array( &$this, 'render_gd_tiny_png_settings' ), 'media' );
			add_settings_field( 'gd_tiny_png_key', 'Your Tiny PNG Key', array( &$this, 'gd_tiny_png_render_key_field' ), 'media', 'gd_tiny_png_settings' );
			add_settings_field( 'gd_tiny_png_auto_shrink', 'Automatically shrink files on upload?', array( &$this, 'gd_tiny_png_render_auto_shrink_field' ), 'media', 'gd_tiny_png_settings' );
			add_settings_field( 'gd_tiny_png_sizes_option', 'Which file sizes do you want to shrink?', array( &$this, 'gd_tiny_png_render_sizes_option' ), 'media', 'gd_tiny_png_settings' );

			register_setting( 'media', 'gd_tiny_png_key' );
			register_setting( 'media', 'gd_tiny_png_auto_shrink' );
			register_setting( 'media', 'gd_tiny_png_sizes_option' );

		}

		/**
		 * Empty function from add_settings_section. Displaying of fields is handled in
		 * specific add_settings_field callback functions.
		 */
		function render_gd_tiny_png_settings() {
			add_settings_field( 'gd_tiny_png_key', 'Your Tiny PNG Key', array( &$this, 'gd_tiny_png_render_key_field' ), 'media', 'gd_tiny_png_settings' );
			add_settings_field( 'gd_tiny_png_auto_shrink', 'Automatically shrink files on upload?', array( &$this, 'gd_tiny_png_render_auto_shrink_field' ), 'media', 'gd_tiny_png_settings' );

			register_setting( 'media', 'gd_tiny_png_key' );
			register_setting( 'media', 'gd_tiny_png_auto_shrink' );
		}

		/**
		 * Display text box, label, and description for Tiny PNG key.
		 */
		function gd_tiny_png_render_key_field() {
			$setting = 'gd_tiny_png_key';
			$value = get_option( $setting, '' );?>

	        <input class="all-options" type="text" name="<?php echo $setting ?>" value=" <?php echo esc_attr( $value ); ?>">
	        <span class="description"> &nbsp; A Tiny PNG key is required. Get a free key from <a href="https://tinypng.com/developers" target="_blank">Tiny PNG</a> if needed. (works for both PNG and JPEG)</span>
	        <?php
		}

		/**
		 * Display check box, label, and description for turning on and off auto shrink.
		 */
		function gd_tiny_png_render_auto_shrink_field() {
			$setting = 'gd_tiny_png_auto_shrink';
			$value = get_option( $setting, 'on' );?>

	        <input type="checkbox" name="<?php echo $setting ?>" <?php if ( $value == 'on' ) { echo ' checked="checked" '; } ?>/>
	        <span class="description"> &nbsp; By default GD Tiny PNG will automatically shrink all uploaded JPEG/PNG files. Uncheck this box to disable this feature.</span>
	        <?php

		}

		function gd_tiny_png_render_sizes_option() {
			$setting = 'gd_tiny_png_sizes_option';
			$value = get_option( $setting );?>
			<span class="description"> By default GD Tiny PNG will shrink the original file. Optionally, you can choose to compress the other image sizes created by WordPress here. Remember each additional image size will affect your TinyPNG monthly limit.</span><br>

			<?php
			global $_wp_additional_image_sizes;

			$sizes = array();
			$get_intermediate_image_sizes = get_intermediate_image_sizes();

			// Create the full array with sizes. borrowed from http://codex.wordpress.org/Function_Reference/get_intermediate_image_sizes
			foreach ( $get_intermediate_image_sizes as $_size ) {

				if ( in_array( $_size, array( 'thumbnail', 'medium', 'large' ) ) ) {

					$sizes[ $_size ]['width'] = get_option( $_size . '_size_w' );
					$sizes[ $_size ]['height'] = get_option( $_size . '_size_h' );


				} elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {

					$sizes[ $_size ] = array(
						'width' => $_wp_additional_image_sizes[ $_size ]['width'],
						'height' => $_wp_additional_image_sizes[ $_size ]['height'],

					);

				}

			}

?>
			<?php foreach ( $sizes as $size_key => $size ) : ?>
				 <input type="checkbox" id='<?php echo $size_key ;?>' name="<?php echo $setting.'['.$size_key.']'; ?>" <?php if ( isset( $value[$size_key] ) && $value[$size_key] == 'on' ) { echo ' checked="checked" '; } ?>/>
	        	<label for="<?php echo $setting.'['.$size_key.']'; ?>"><?php echo sprintf( '%s - %d x %d', $size_key, $size['width'], $size['height'] ); ?></label><br>

	        <?php endforeach ;
		}

		/**
		 * Handles shrinking of already exixting files. This function is called from the link in the Media Library column.
		 * Borrowed from WP Smush.it smushit_manual()
		 *
		 * @see  $this->render_gd_tiny_png_media_column
		 */
		function compress_existing() {
			if ( !current_user_can( 'upload_files' ) ) {
				wp_die( "You don't have permission to work with uploaded files." );
			}

			if ( !isset( $_GET['attachment_ID'] ) ) {
				wp_die( 'No attachment ID was provided.' );
			}

			$attachment_ID = intval( $_GET['attachment_ID'] );

			$original_meta = wp_get_attachment_metadata( $attachment_ID );

			$new_meta = $this->parse_meta_data( $original_meta, $attachment_ID );

			wp_update_attachment_metadata( $attachment_ID, $new_meta );

			wp_redirect( preg_replace( '|[^a-z0-9-~+_.?#=&;,/:]|i', '', wp_get_referer( ) ) );
			exit();

		}
		/**
		 * Inserts option at end of bulk options list using javascript.
		 * Borrowed from http://www.viper007bond.com/wordpress-plugins/regenerate-thumbnails/
		 */

		function add_bulk_actions_via_javascript() { ?>
			<script type="text/javascript">
				jQuery(document).ready(function($){
					$('select[name^="action"] option:last-child').after('<option value="bulk_compress_png">Bulk Compress via TinyPNG</option>');
				});
			</script>
		<?php }

		/**
		 * Handles the bulk actions POST.
		 * Sends selected PNGs to parse_meta_data to start the process of shrinking each image.
		 *
		 * @see  $this->parse_meta_data
		 */

		function bulk_action_handler() {
			check_admin_referer( 'bulk-media' );

			if ( empty( $_REQUEST['media'] ) || ! is_array( $_REQUEST['media'] ) )
				return;

			$ids = $_REQUEST['media'];
			foreach ( $ids as $id ) {
				$mime_type = get_post_mime_type( $id );

				if ( $mime_type == 'image/png' | $mime_type == 'image/jpeg'  ) {
					$original_meta = wp_get_attachment_metadata( $id );

					$new_meta = $this->parse_meta_data( $original_meta, $id );

					wp_update_attachment_metadata( $id, $new_meta );

				}
			}
		}
	}//end class GD_TINY_PNG

	$wp_tiny_png = new GD_Tiny_PNG();

}//end if class_exists
?>
