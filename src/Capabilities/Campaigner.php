<?php

namespace P4\MasterTheme\Role;

use P4\MasterTheme\Capability;

/**
 * Register custom 'campaigner' role and adds custom capabilities.
 */
class Campaigner {

	/**
	 * @var [[string]] List of capabilities for each standard WordPress role.
	 */
	private const CAPABILITIES_MAP = [
		'administrator' => [
			'edit_campaign',
			'read_campaign',
			'delete_campaign',
			'edit_campaigns',
			'edit_others_campaigns',
			'publish_campaigns',
			'read_private_campaigns',
			'delete_campaigns',
			'delete_private_campaigns',
			'delete_published_campaigns',
			'delete_others_campaigns',
			'edit_private_campaigns',
			'edit_published_campaigns',
			Capability::USE_IMAGE_ARCHIVE_PICKER,
		],
		'editor'        => [
			'edit_campaign',
			'read_campaign',
			'delete_campaign',
			'edit_campaigns',
			'edit_others_campaigns',
			'publish_campaigns',
			'delete_campaigns',
			'delete_published_campaigns',
			'delete_others_campaigns',
			'edit_published_campaigns',
			'read_private_campaigns',
			'edit_private_campaigns',
			'delete_private_campaigns',

			// Needed to allow the editor rule to change the author of a post in the document sidebar. The users data for that
			// control is fetched using the REST API, where WordPress by default doesn't perform a permissions check, however
			// the Wordfence plugin adds this check in `\wordfence::jsonAPIAuthorFilter`.
			'list_users',
			Capability::USE_IMAGE_ARCHIVE_PICKER,
		],
		'author'        => [
			'edit_campaign',
			'read_campaign',
			'delete_campaign',
			'edit_campaigns',
			'publish_campaigns',
			'delete_published_campaigns',
			'edit_published_campaigns',
		],
		'contributor'   => [
			'edit_campaign',
			'read_campaign',
			'edit_campaigns',
			'edit_published_campaigns',
		],
	];

	/**
	 * Add Campaigner role.
	 */
	private static function add_campaigner_role() {
		add_role(
			'campaigner',
			__( 'Campaigner', 'planet4-master-theme-backend' ),
			[
				// General.
				'read'                       => true,

				// Media upload.
				'upload_files'               => true,

				// Edit(own), View, Delete(own) posts.
				'edit_post'                  => true,
				'read_post'                  => true,
				'edit_posts'                 => true,
				'delete_posts'               => true,

				// Campaign capabilities.
				'edit_campaign'              => true,
				'read_campaign'              => true,
				'delete_campaign'            => true,
				'edit_campaigns'             => true,
				'edit_others_campaigns'      => true,
				'publish_campaigns'          => true,
				'read_private_campaigns'     => true,
				'delete_campaigns'           => true,
				'delete_private_campaigns'   => true,
				'delete_published_campaigns' => true,
				'delete_others_campaigns'    => true,
				'edit_private_campaigns'     => true,
				'edit_published_campaigns'   => true,
				'import'                     => true,
			]
		);
	}

	/**
	 * Register campaigner role and add custom capabilities.
	 */
	public static function register_role_and_add_capabilities() {
		foreach ( self::CAPABILITIES_MAP as $role_name => $capabilities ) {
			$role = get_role( $role_name );
			foreach ( $capabilities as $capability ) {
				$role->add_cap( $capability );
			}
		}
		self::add_campaigner_role();
	}
}
