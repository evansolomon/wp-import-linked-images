<?php
/*
Plugin Name: Import Linked Images
Description: Automatically download external images used in posts and replace them with the local copies.
Version: 1.0
Author: Evan Solomon
Author URI: http://evansolomon.me
*/

class Import_Linked_Images {
	static function replace_external_images( $content ) {
		$external_images = self::get_external_images( stripslashes( $content ) );

		foreach ( $external_images as $external_image ) {
			$local_html = media_sideload_image( $external_image, get_the_ID() );
			if ( is_wp_error( $local_html ) )
				continue;

			// media_sideload_image() only returns HTML
			// See http://core.trac.wordpress.org/ticket/19629
			preg_match( "#<img src='([^']+)'#", $local_html, $local_src );
			if ( ! $local_src[1] )
				continue;

			$content = str_replace( $external_image, $local_src[1], $content );
		}

		return $content;
	}

	function get_external_images( $content ) {
		$images = array();
		$siteurl = get_option( 'siteurl' );

		preg_match_all( '#<img[^>]* src=[\'"]?([^>\'" ]+)#', $content, $sources );
		foreach ( $sources[1] as $src ) {
			// Has to at least look like a valid domain
			if ( ! preg_match( '#^https?://#', $src ) )
				continue;

			// ...but not this domain
			if ( $siteurl == substr( $src, 0, strlen( $siteurl ) ) )
				continue;

			$images[] = $src;
		}

		return $images;
	}
}

add_filter( 'content_save_pre', array( 'Import_Linked_Images', 'replace_external_images' ) );
