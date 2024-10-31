<?php

namespace NoBg\Services;

use Averta\Core\Utility\JSON;
use Averta\WordPress\Utility\Sanitize;
use NoBg\GuzzleHttp\Exception\GuzzleException;
use NoBg\GuzzleHttp\Exception\RequestException;
use WP_Error;

class ImageHttpClient {

	/**
	 * @throws GuzzleException
	 */
	public function sendAttachment( $id, $args = [] ) {

		$result = $this->postAttachment( $id, $args );

		if ( is_wp_error( $result ) ) {
			return [ 'error' => $result->get_error_message() ];
		} else {
			return $result;
		}
	}

	public function downloadAndAttachImage( $imageUrl, $originalAttachmentId = null ) {
		$attachmentId = $this->downloadAndAttachToOriginalAttachment( $imageUrl, $originalAttachmentId );

		if ( is_wp_error( $attachmentId ) ) {
			return [ 'error' => $attachmentId->get_error_message() ];
		} else {
			$link = $this->getAttachmentModalPageUrl( $attachmentId );

			return [ 'id' => $attachmentId, 'link' => esc_url( $link ), 'message' => 'Image downloaded' ];
		}
	}


	/**
	 * Get the process status
	 *
	 * @param $process
	 *
	 * @return mixed|string[]
	 * @throws GuzzleException
	 */
	public function getProcessStatus( $process ) {
		try {
			$response = \NoBg::remote()->get( "api/w1/process/status/" . $process );

			if ( $response->getStatusCode() == 200 ) {
				return JSON::decode( $response->getBody()->getContents(), true );
			} else {
				return [ 'error' => 'Request failed with status code: ' . $response->getStatusCode() ];
			}
		} catch ( RequestException $e ) {
			return [ 'error' => 'Request error exception: ' . $e->getMessage() ];
		}
	}

	/**
	 * Send a attachment image for background removal
	 *
	 * @param $attachment_id
	 * @param $args
	 *
	 * @return mixed|WP_Error
	 * @throws GuzzleException
	 */
	private function postAttachment( $attachment_id, $args = [] ) {

		if ( ! $attachment_id ) {
			return new WP_Error( 'invalid_attachment_id', 'The attachment id is invalid.' );
		}

		$post = get_post( $attachment_id );

		if ( ! $post || $post->post_type !== 'attachment' ) {
			return new WP_Error( 'invalid_attachment', 'Not a valid attachment.' );
		}

		// Get the attachment metadata
		$attachment_metadata = wp_get_attachment_metadata( $attachment_id );
		if ( ! $attachment_metadata ) {
			return new WP_Error( 'invalid_attachment_metadata', 'The attachment metadata is invalid.' );
		}

		// Get the MIME type of the attachment
		$mime_type = get_post_mime_type( $attachment_id );
		if ( ! $mime_type || ! in_array( $mime_type, [ 'image/jpeg', 'image/png', 'image/gif', 'image/webp' ] ) ) {
			return new WP_Error( 'invalid_mime_type', 'This image type is not supported to be processed.' );
		}

		// Get the file path of the attachment
		$file_path = get_attached_file( $attachment_id );
		if ( ! $file_path || ! file_exists( $file_path ) ) {
			return new WP_Error( 'file_not_found', 'Image file does not exist on your server.' );
		}

		list( $width, $height ) = getimagesize( $file_path );
		$pixels = $width * $height;
		if ( $pixels >= 6000000 ) {
			return new WP_Error( 'image_too_large', 'The image dimensions exceed the allowed limit. Only images with a maximum size of 6 megapixels are permitted.' );
		}

		try {
			// attach file
			$multiPart = [
				[
					'name' => 'file',
					'contents' => \NoBg::storage()->filesystem()->read( $file_path ),
					'filename' => Sanitize::fileName( basename( $file_path ) ),
				]
			];
			// include form_data params
			foreach ( $args as $key => $value ) {
				$multiPart[] = [
					'name' => $key,
					'contents' => $value
				];
			}
			// send the file and optional args for process
			$response = \NoBg::remote()->post( "api/w1/c/background/remove/", [
				'multipart' => $multiPart
			] );

			if ( $response->getStatusCode() == 200 ) {
				return JSON::decode( $response->getBody()->getContents(), true );
			} else {
				return new WP_Error( 'request_failed', 'Request failed with status code: ' . $response->getStatusCode() );
			}
		} catch ( RequestException $e ) {
			return new WP_Error( 'request_error_exception', 'Request error exception: ' . $e->getMessage() );
		}
	}


	private function downloadAsAttachment( $imageUrl ) {
		// Check user capabilities
		if ( ! current_user_can( 'upload_files' ) ) {
			return new WP_Error( 'insufficient_permissions', 'You do not have sufficient permissions to add images to media library.' );
		}

		// Check if the URL is valid
		if ( filter_var( $imageUrl, FILTER_VALIDATE_URL ) === false ) {
			return new WP_Error( 'invalid_url', 'The provided URL is not valid.' );
		}

		// Get the file name and extension
		$fileName = Sanitize::fileName( basename( $imageUrl ) );
		$fileInfo = pathinfo( $fileName );
		$file_extension = $fileInfo['extension'] ?? '';

		// Check if the file extension is valid
		if ( ! in_array( strtolower( $file_extension ), [ 'jpg', 'jpeg', 'png', 'webp' ] ) ) {
			return new WP_Error( 'invalid_extension', 'The file extension is not allowed.' );
		}

		// Download the image
		$response = wp_remote_get( $imageUrl );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Check the response code
		if ( wp_remote_retrieve_response_code( $response ) != 200 ) {
			return new WP_Error( 'download_failed', 'Failed to download the image.' );
		}

		// Get the image data
		$imageContent = wp_remote_retrieve_body( $response );

		// Upload the image to the WordPress media library
		$upload = wp_upload_bits( $fileName, null, $imageContent );
		if ( $upload['error'] ) {
			return new WP_Error( 'upload_failed', $upload['error'] );
		}

		// Include the image.php file to use wp_insert_attachment()
		require_once( ABSPATH . 'wp-admin/includes/image.php' );

		// Create the attachment
		$attachment = array(
			'post_mime_type' => $upload['type'],
			'post_title' => Sanitize::title( $fileName ),
			'post_content' => '',
			'post_status' => 'inherit',
		);

		// Insert the attachment
		$attachmentId = wp_insert_attachment( $attachment, $upload['file'] );
		if ( is_wp_error( $attachmentId ) ) {
			return $attachmentId;
		}

		// Generate the metadata for the attachment
		$attach_data = wp_generate_attachment_metadata( $attachmentId, $upload['file'] );
		wp_update_attachment_metadata( $attachmentId, $attach_data );

		return $attachmentId;
	}

	private function downloadAndAttachToOriginalAttachment( $imageUrl, $originalAttachmentId = null ) {
		// Check user capabilities
		if ( ! current_user_can( 'upload_files' ) ) {
			return new WP_Error( 'insufficient_permissions', 'You do not have sufficient permissions to add images to media library.' );
		}

		// Check if the URL is valid
		if ( ! filter_var( $imageUrl, FILTER_VALIDATE_URL ) ) {
			return new WP_Error( 'invalid_url', 'Invalid image URL provided.' );
		}

		if (!$originalAttachmentId || !$originalAttachment = get_post($originalAttachmentId)) {
			return new WP_Error( 'original_attachment_not_found', 'Original attachment not found.' );
		}

		// Get file path
		if (!$originalFilePath = get_attached_file($originalAttachmentId)) {
			return new WP_Error( 'original_attachment_not_found', 'Original attachment file not found.' );
		}

		// Get the file name without extension
		$originalFileNameNoExt = pathinfo( $originalFilePath, PATHINFO_FILENAME );

		// Get the file extension
		$originalFileNameNoExtension = pathinfo( $originalFilePath, PATHINFO_EXTENSION );

		// Generate a random suffix
		$randomSuffix = wp_generate_password( 3, false );

		// Create a new file name with the random suffix
		$newFileName = "{$originalFileNameNoExt}-{$randomSuffix}.{$originalFileNameNoExtension}";

		// Download the file to a temporary location
		$tempFile = download_url( $imageUrl );

		// Check for download errors
		if ( is_wp_error( $tempFile ) ) {
			return new WP_Error( 'download_error', 'Failed to download image.', $tempFile->get_error_message() );
		}

		// Get the file type and validate it
		$fileInfo = wp_check_filetype( basename( $tempFile ) );

		if ( ! in_array( $fileInfo['type'], get_allowed_mime_types() ) ) {
			wp_delete_file( $tempFile ); // Clean up temporary file
			return new WP_Error( 'invalid_file_type', 'Invalid file type to upload.' );
		}

		// File information
		$fileData = [
			'name'     => $newFileName,
			'type'     => $fileInfo['type'],
			'tmp_name' => $tempFile, // Temporary file location
			'error'    => 0,
			'size'     => filesize( $tempFile ),
		];

		// Handle the file upload, this will move the file to the uploads directory
		$attachmentId = media_handle_sideload( $fileData, $originalAttachmentId );

		// Include the image.php file to use wp_insert_attachment()
		require_once( ABSPATH . 'wp-admin/includes/image.php' );

		// Check for errors in the upload process
		if ( is_wp_error( $attachmentId ) ) {
			wp_delete_file( $tempFile ); // Clean up temporary file
			return new WP_Error( 'upload_error', 'Failed to handle side loading file.', $attachmentId->get_error_message() );
		}

		return $attachmentId;
	}

	/**
	 * Get the modal edit page of an attachment
	 *
	 * @return string
	 */
	public function getAttachmentModalPageUrl( $attachmentId ) {
		$params = $attachmentId ? [ 'item' => $attachmentId ] : [];
		return add_query_arg( $params, self_admin_url( 'upload.php' ) );
	}

	/**
	 * Get the ID of the last attachment image
	 *
	 * @return false|int|null
	 */
	public function getLastAttachmentImageId() {
		$args = array(
			'post_type' => 'attachment',
			'post_mime_type' => array( 'image/jpeg', 'image/png', 'image/webp' ),
			'posts_per_page' => 1,
			'post_status' => 'inherit', // only published attachments
			'orderby' => 'date',
			'order' => 'DESC',
		);

		$query = new \WP_Query( $args );

		if ( $query->have_posts() ) {
			$query->the_post();

			return get_the_ID();
		}

		return '';
	}


}
