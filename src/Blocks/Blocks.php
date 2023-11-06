<?php // phpcs:ignore Squiz.Commenting.FileComment.Missing

namespace Re_Beehiiv\Blocks;

use Re_Beehiiv\Blocks\Settings;

/**
 * Register Plugin Blocks
 *
 * @package MIDNewsletter
 * @subpackage MIDNewsletterCore
 * @since 1.0.0
 */
class Blocks {


	/**
	 * Register all blocks
	 *
	 * @return void
	 */
	public static function register_all_blocks() {

		Settings::register();

	}
}
