<?php
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

class Imagify_Attachment {
	/**
	 * The attachment ID
	 *
	 * @since 1.0
	 *
	 * @var    int
	 * @access public
	 */
	public $id;

	 /**
     * The constructor
     *
	 * @since 1.0
	 *
     * @return void
     **/
	function __construct( $id = 0 ) {
		global $post;

		if ( is_object( $post ) && ! $id ) {
			$this->id = $post->ID;
		} else {
			$this->id = (int) $id;
		}
	}

	/**
	 * Get the attachment backup filepath.
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @return string|false
	 */
	public function get_backup_path() {
		$file_path 		 = get_attached_file( $this->id );
		$upload_dir      = wp_upload_dir();
		$upload_basedir  = trailingslashit( $upload_dir['basedir'] );
		$backup_path     = str_replace( $upload_basedir, $upload_basedir . 'backup/' , $file_path );

		if( file_exists( $backup_path ) ) {
			return $backup_path;
		}

		return false;
	}

	/**
	 * Get the attachment extension.
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @return string
	 */
	public function get_extension() {
		$fullsize_path = get_attached_file( $this->id );
		return pathinfo( $fullsize_path, PATHINFO_EXTENSION );
	}

	/**
	 * Get the attachment optimization data.
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @return array
	 */
	public function get_data() {
		return get_post_meta( $this->id, '_imagify_data', true );
	}

	/**
	 * Get the attachment optimization level.
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @return int
	 */
	public function get_optimization_level() {
		$stats  = $this->get_stats_data();
		$level  = ( isset( $stats['aggressive'] ) ) ? $stats['aggressive'] : get_imagify_option( 'optimization_level', 1 );

		return (int) $level;
	}

	/**
	 * Count number of optimized sizes.
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @return int
	 */
	public function get_optimized_sizes_count() {
		$data  = $this->get_data();
		$sizes = (array) $data['sizes'];
		$count = 0;

		if ( isset( $sizes['full'] ) ) {
			unset( $sizes['full'] );
		}

		foreach ( $sizes as $size ) {
			if ( $size['success'] ) {
				$count++;
			}
		}

		return (int) $count;
	}

	/**
	 * Get the attachment optimization status (success or error).
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @return string
	 */
	public function get_status() {
		return get_post_meta( $this->id, '_imagify_status', true );
	}

	/**
	 * Get the original attachment path.
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @return string
	 */
	public function get_original_path() {
		return get_attached_file( $this->id );
	}

	/**
	 * Get the original attachment size.
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @return string
	 */
	public function get_original_size() {
		$original_size = $this->get_size_data( 'full', 'original_size' );
		$original_size = ( empty( $original_size ) ) ? @filesize( $this->get_original_path() ) : $original_size;
		$original_size = size_format( $original_size, 2 );

		return $original_size;
	}

	/**
	 * Get the original attachment URL.
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @return string
	 */
	public function get_original_url() {
		return wp_get_attachment_url( $this->id );
	}

	/**
	 * Get all metadata sizes.
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @return array
	 */
	public function get_sizes() {
		$metadata = wp_get_attachment_metadata( $this->id );
		return (array) $metadata['sizes'];
	}

	/*
	 * Get the statistics of a specific size.
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @param  string  $size  The thumbnail slug.
	 * @param  string  $key   The specific data slug.
	 * @return array|string
	 */
	public function get_size_data( $size = 'full', $key = '' ) {
		$data  = $this->get_data();
		$stats = array();

		if ( isset( $data['sizes'][ $size ] ) ) {
			$stats = $data['sizes'][ $size ];
		}

		if ( isset( $stats[ $key ] ) ) {
			$stats = $stats[ $key ];
		}

		return $stats;
	}

	/**
	 * Get the global statistics data or a specific one.
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @param  string $key  The specific data slug.
	 * @return array|string
	 */
	public function get_stats_data( $key = '' ) {
		$data  = $this->get_data();
		$stats = '';

		if ( isset( $data['stats'] ) ) {
			$stats = $data['stats'];
		}

		if ( isset( $stats[ $key ] ) ) {
			$stats = $stats[ $key ];
		}

		return $stats;
	}

	/**
	 * Check if the attachment is optimized.
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @return bool   True if the attachment is optimized.
	 */
	public function is_optimized() {
		return ( 'success' === $this->get_status() ) > 0;
	}

	/**
	 * Check if the attachment exceeding the limit size (> 5mo).
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @return bool   True if the attachment is skipped.
	 */
	public function is_exceeded() {
		$filepath = $this->get_original_path();
		$size     = 0;

		if ( file_exists( $filepath ) ) {
			$size = filesize( $filepath );
		}

		return ( $size > IMAGIFY_MAX_BYTES ) > 0;
	}

	/**
	 * Check if the attachment has a backup of the original size.
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @return bool   True if the attachment has a backup.
	 */
	public function has_backup() {
		return (bool) $this->get_backup_path();
	}

	/**
	 * Check if the attachment has an error.
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @return bool   True if the attachment has an error.
	 */
	public function has_error() {
		$has_error = $this->get_size_data( 'full', 'error' );
		return ( is_string( $has_error ) ) > 0;
	}

	/**
	 * Delete the backup file.
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @return void
	 */
	public function delete_backup() {
		$backup_path = $this->get_backup_path();

		if ( ! empty( $backup_path ) ) {
			@unlink( $backup_path );
		}
	}

	/**
	 * Fills statistics data with values from $data array
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @static
	 * @param array   $data		 The statistics data
	 * @param object  $response  The API response
	 * @param int  	  $id   	 The attachment ID
	 * @param int  	  $url  	 The attachment URL
	 * @param string  $size 	 The attachment size key
	 * @return bool|array  False if the original size has an error or an array contains the data for other result
	 */
	static public function fill_data( $data, $response, $id, $url, $size = 'full' ) {
		if ( is_wp_error( $response ) ) {
			$data['sizes'][ $size ] = array(
				'success' => false,
				'error'   => $response->get_error_message()
			);

			if ( 'full' === $size ) {
				update_post_meta( $id, '_imagify_data', $data );
				update_post_meta( $id, '_imagify_status', 'error' );

				return false;
			}
		} else {
			$data['sizes'][ $size ] = array(
				'success' 		 => true,
				'file_url'		 => $url,
				'original_size'  => $response->original_size,
				'optimized_size' => $response->new_size,
				'percent'        => $response->percent
			);

			$data['stats']['original_size']  += ( isset( $response->original_size ) ) ? $response->original_size : 0;
			$data['stats']['optimized_size'] += ( isset( $response->new_size ) ) ? $response->new_size : 0;
		}

		return $data;
	}

	/**
	 * Optimize all sizes with Imagify.
	 *
	 * @access public
	 * @param  int 	  $is_aggressive   The optimization level (1=aggressive, 0=normal)
	 * @param  array  $metadata   	   The attachment meta data
	 * @return array  $optimized_data  The optimization data
	 */
	public function optimize( $is_aggressive = null, $metadata = array() ) {
		$is_aggressive = ( is_null( $is_aggressive ) ) ? (int) get_imagify_option( 'optimization_level', 1 ) : (int) $is_aggressive;

		$id = $this->id;
		$sizes         = ( isset( $metadata['sizes'] ) ) ? $metadata['sizes'] : $this->get_sizes();
		$data          = array(
			'stats' => array(
				'aggressive'     => $is_aggressive,
				'original_size'  => 0,
				'optimized_size' => 0,
				'percent'    	 => 0,
			)
		);

		// Get file path & URL for original image
		$attachment_path = $this->get_original_path();
		$attachment_url  = $this->get_original_url();

		// TO DO: use wp_attachment_is_image when we can optimize all image formats
		//if ( ( $id || wp_attachment_is_image( $id ) === false ) {
		if ( ! $id || ! in_array( $this->get_extension(), array( 'png', 'jpg', 'jpeg' ) )  ) {
			return;
		}

		// Check if the full size is already optimized
		if ( $this->is_optimized() && ( $this->get_optimization_level() == $is_aggressive ) ) {
			return;
		}

		/**
		 * Fires before optimizing an attachment.
		 *
		 * @since 1.0
		 *
		 * @param int $id The attachment ID
		*/
		do_action( 'before_imagify_optimize_attachment', $id );

		// Optimize the original size
		$response = do_imagify( $attachment_path, get_imagify_option( 'backup', false ), $is_aggressive );
		$data 	  = $this->fill_data( $data, $response, $id, $attachment_url );

		if( (bool) ! $data ) {
			return;
		}

		if ( (bool) $sizes ) {
			foreach ( $sizes as $size_key => $size_data ) {
				// Check if this size has to be optimized
				if ( array_key_exists( $size_key, get_imagify_option( 'disallowed-sizes', array() ) ) && ! imagify_is_active_for_network() ) {
					$data['sizes'][ $size_key ] = array(
						'success' => false,
						'error'   => __( 'This size isn\'t authorized to be optimized. Update your Imagify settings if you want to optimize it.', 'imagify' )
					);
					continue;
				}

				$thumbnail_path = trailingslashit( dirname( $attachment_path ) ) . $size_data['file'];
				$thumbnail_url  = trailingslashit( dirname( $attachment_url ) ) . $size_data['file'];

				// Optimize the thumbnail size
				$response = do_imagify( $thumbnail_path, false, $is_aggressive );
				$data     = $this->fill_data( $data, $response, $id, $thumbnail_url, $size_key );

				/**
				* Filter the optimization data of a specific thumbnail
				*
				* @since 1.0
				*
				* @param array   $data   		  The statistics data
				* @param object  $response   	  The API response
				* @param int     $id   			  The attachment ID
				* @param string  $thumbnail_path  The attachment path
				* @param string  $thumbnail_url   The attachment URL
				* @param string  $size_key   	  The attachment size key
				* @param bool    $is_aggressive   The optimization level
				* @return array  $data  		  The new optimization data
				*/
				$data = apply_filters( 'imagify_fill_thumbnail_data', $data, $response, $id, $thumbnail_path, $thumbnail_url, $size_key, $is_aggressive );
			}
		}

		$data['stats']['percent'] = round( ( ( $data['stats']['original_size'] - $data['stats']['optimized_size'] ) / $data['stats']['original_size'] ) * 100, 2, PHP_ROUND_HALF_UP );

		update_post_meta( $id, '_imagify_data', $data );
		update_post_meta( $id, '_imagify_status', 'success' );

		$optimized_data = $this->get_data();

		/**
		 * Fires after optimizing an attachment.
		 *
		 * @since 1.0
		 *
		 * @param int    $id   			  The attachment ID
		 * @param array  $optimized_data  The optimization data
		*/
		do_action( 'after_imagify_optimize_attachment', $id, $optimized_data );

		return $optimized_data;
	}

	/**
	 * Process an attachment restoration from the backup file.
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @return void
	 */
	public function restore() {
		$id              = $this->id;
		$backup_path     = $this->get_backup_path();
		$attachment_path = get_attached_file( $id );

		/**
		 * Fires before restoring an attachment.
		 *
		 * @since 1.0
		 *
		 * @param int $id The attachment ID
		*/
		do_action( 'before_imagify_restore_attachment', $id );

		// Create the original image from the backup
		@copy( $backup_path, $attachment_path );

		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
		}

		remove_filter( 'wp_generate_attachment_metadata', '_imagify_optimize_attachment', PHP_INT_MAX );
		wp_generate_attachment_metadata( $id, $attachment_path );

		// Remove old optimization data
		delete_post_meta( $id, '_imagify_data' );
		delete_post_meta( $id, '_imagify_status' );

		/**
		 * Fires after restoring an attachment.
		 *
		 * @since 1.0
		 *
		 * @param int $id The attachment ID
		*/
		do_action( 'after_imagify_restore_attachment', $id );
	}
}