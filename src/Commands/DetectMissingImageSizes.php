<?php

namespace P4\MasterTheme\Commands;

use WP_CLI;
use WP_Query;

class DetectMissingImageSizes extends Command {

	/**
	 * @inheritDoc
	 */
	protected static function get_name(): string {
		return 'p4-detect-missing-image-sizes';
	}

	/**
	 * @inheritDoc
	 */
	protected static function get_short_description(): string {
		return 'Check all images that have no sizes in metadata.';
	}

	/**
	 * @inheritDoc
	 */
	public static function execute( ?array $args, ?array $assoc_args ): void {
		$query_images_args = array(
			'post_type'      => 'attachment',
			'post_mime_type' => 'image',
			'post_status'    => 'inherit',
			'posts_per_page' => - 1,
		);

		$query_images = new WP_Query( $query_images_args );

		$per_amount_of_sizes = [];
		foreach ( $query_images->posts as $image ) {
			$meta  = wp_get_attachment_metadata( $image->ID );
			$sizes = $meta['sizes'];

			$per_amount_of_sizes[ count( $sizes ) ][] = $image->ID;
//			WP_CLI::log( print_r( $meta, true ) );
		}
		ksort( $per_amount_of_sizes );
		WP_CLI::log( print_r( $per_amount_of_sizes, true ) );
		foreach ( $per_amount_of_sizes as $amount => $ids ) {
			$count_ids = count( $ids );
			WP_CLI::log( "$amount SIZES: $count_ids" );

		}
		WP_CLI::log( 'These have no sizes:' );
		WP_CLI::log( print_r( $per_amount_of_sizes[0], true ) );
	}
}
