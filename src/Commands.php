<?php
/**
 * Commands.
 */

namespace P4\MasterTheme;

use P4\MasterTheme\Commands\CloudflarePurge;
use P4\MasterTheme\Commands\DetectMissingImageSizes;
use P4\MasterTheme\Commands\RunActivator;
use P4\MasterTheme\Commands\SaveCloudflareKey;

/**
 * Class with a static function just because PHP can't autoload functions.
 */
class Commands {
	/**
	 * Add some WP_CLI commands if we're in CLI.
	 */
	public static function load() {
		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
			return;
		}
		RunActivator::register();
		SaveCloudflareKey::register();
		CloudflarePurge::register();
		DetectMissingImageSizes::register();
	}
}
