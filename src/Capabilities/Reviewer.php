<?php

declare(strict_types=1);

namespace P4\MasterTheme\Role;

/**
 * Register custom 'reviewer' role and adds its capabilities.
 */
class Reviewer {
	/**
	 * Add Reviewer role.
	 */
	public static function register_role() {
		add_role(
			'reviewer',
			__( 'Reviewer', 'planet4-master-theme-backend' ),
			[
				'read_private_posts' => true,
				'read_private_pages' => true,
			]
		);
	}
}
