<?php

/*
 *	Plugin Name: Compress PNG for WP
 *	Plugin URI: http://www.geckodesigns.com
 *	Description: Compress PNG files using the TinyPNG API.
 *	Version: 1.0.2
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
			define( 'GD_PLUGIN_NAME', 'Compress PNG for WP' );
			$is_auto_shrink = get_option( 'gd_tiny_png_auto_shrink', 'on' );

			if ( 'on' == $is_auto_shrink ) {
				add_filter( 'wp_generate_attachment_metadata', array( &$this, 'parse_meta_data' ), 10, 2 );
			}else {

			}

			add_filter( 'manage_media_columns', array( &$this, 'add_gd_tiny_png_media_column' ) );
			add_action( 'manage_media_custom_column', array( &$this, 'render_gd_tiny_png_media_column' ), 10, 2 );
			add_action( 'admin_action_gd_tinypng_compress_existing', array( &$this, 'compress_existing' ) );
			add_action( 'admin_init', array( &$this, 'setup_gd_tiny_png_settings' ) );

			if ( !function_exists( 'curl_init' ) ) {
				add_action( 'admin_notices', array( &$this, 'no_curl_admin_notices' ) );
			}

		}

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


			if ( 'image/png' == $mime_type ) {
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
				//loop through each file in sizes array and shrink
				$sizes_array = $meta['sizes'];
				foreach ( $sizes_array as $image ) {
					$filepath = $upload_dir['basedir'].'/'.$path_after_uploads.$image['file'];

					$this->tiny_png_request( $filepath, $id );
				}

				//shrink original file
				$filepath = $base_dir.'/'.$meta['file'];

				$this->tiny_png_request( $filepath, $id );

			}
			return $meta;

		}

		/**
		 * POSTs a file to the Tiny PNG API. Sends result to process_result(). Updates post meta to
		 * keep track of information on the file.
		 *
		 * @param string  $file File path for file to be sent to Tiny PNG
		 * @param int     $id   Id of the current post related to the file.
		 */
		function tiny_png_request( $file, $id ) {

			if ( !function_exists( 'curl_init' ) ) {

				$msg_meta  = 'The php curl extension was not enabled and this file was not compressed.';
			}
			else {
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

				if ( 201 === curl_getinfo( $request, CURLINFO_HTTP_CODE ) ) {
					$is_error = false;
					/* Compression was successful, retrieve output from Location header. */
					$headers = substr( $response, 0, curl_getinfo( $request, CURLINFO_HEADER_SIZE ) );
					foreach ( explode( "\r\n", $headers ) as $header ) {
						$header_array = explode(",", $header);
						$url_str = $header_array[3];
						
						$url_array = explode('"', $url_str);
						$new_img_url = $url_array[3];

							$request = curl_init();
							curl_setopt_array( $request, array(
									CURLOPT_URL => $new_img_url,
									CURLOPT_RETURNTRANSFER => true,
									CURLOPT_SSL_VERIFYPEER => false
								) );
							$new_file = curl_exec( $request );
							curl_close($request);
							
							file_put_contents( $file, $new_file );
					}

				} else {

					$is_error = true;
				}

				$msg_meta          = $this->process_result( $response, $is_error );
			}

			update_post_meta( $id, GD_TINY_PNG_META, $msg_meta );

		}

		/**
		 * Process the latest result.
		 *
		 * @param string  $result   The result from the curl request
		 * @param boolean $is_error If response ok or not
		 * @return array of api response data or error string
		 */
		function process_result( $result, $is_error ) {
			$result_JSON    = json_decode( $result );//gets result as an array

			if ( $is_error ) {
				$msg = $result_JSON->{'error'} . ': ' . $result_JSON->{'message'};
				$msg .= '. There was an error compressing your image, the original image was saved instead.';
			}else {
				$input_size  = $result_JSON->{'input'}->{'size'};
				$output_size = $result_JSON->{'output'}->{'size'};
				$ratio       = $result_JSON->{'output'}->{'ratio'};
				$msg         = array( 'input'  => $input_size,
					'output' => $output_size,
					'ratio'  => $ratio );
			}

			return $msg;
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
				$mime_type = get_post_mime_type( $id );

				if ( 'image/png' == $mime_type ) {
					// If post_meta then display stats this attachment
					if ( isset( $post_meta ) && !empty( $post_meta ) ) {

						$data = $post_meta[0];
						if ( is_array( $data ) ) {
							$input_kb  = round( intval( $data['input'] )/1024 ) ;
							$output_kb = round( intval( $data['output'] )/1024 );

							echo 'Original Size: '.$input_kb.' KB<br/>';
							echo 'Current  Size: '.$output_kb.' KB<br/>';
							echo 'Ratio: '.$data['ratio'];
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
					echo "Not a png file, Tiny PNG cannot compress.";
				}//end mime-type if
			}//end column name if
		}

		/**
		 * Add settings section, add settings fields, and register settings to the media settings.
		 *
		 */
		function setup_gd_tiny_png_settings() {
			add_settings_section( 'gd_tiny_png_settings', 'Gecko Designs Tiny PNG', array( &$this, 'render_gd_tiny_png_settings' ), 'media' );
			add_settings_field( 'gd_tiny_png_key', 'Your Tiny PNG Key', array( &$this, 'gd_tiny_png_render_key_field' ), 'media', 'gd_tiny_png_settings' );
			add_settings_field( 'gd_tiny_png_auto_shrink', 'Automatically shrink files on upload?', array( &$this, 'gd_tiny_png_render_auto_shrink_field' ), 'media', 'gd_tiny_png_settings' );

			register_setting( 'media', 'gd_tiny_png_key' );
			register_setting( 'media', 'gd_tiny_png_auto_shrink' );
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
	        <span class="description"> &nbsp; A Tiny PNG key is required. Get a free key from <a href="https://tinypng.com/" target="_blank">Tiny PNG</a> if needed.</span>
	        <?php
		}

		/**
		 * Display check box, label, and description for turning on and off auto shrink.
		 */
		function gd_tiny_png_render_auto_shrink_field() {
			$setting = 'gd_tiny_png_auto_shrink';
			$value = get_option( $setting, 'on' );?>

	        <input type="checkbox" name="<?php echo $setting ?>" <?php if ( $value == 'on' ) { echo ' checked="checked" '; } ?>/>
	        <span class="description"> &nbsp; By default GD Tiny PNG will automatically shrink all uploaded PNG files. Uncheck this box to disable this feature.</span>
	        <?php

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


	}//end class GD_TINY_PNG

	$wp_tiny_png = new GD_Tiny_PNG();

}//end if class_exists
?>
