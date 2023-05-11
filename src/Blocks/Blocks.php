<?php

namespace Re_Beehiiv\Blocks;

use Re_Beehiiv\Blocks\Settings;

/**
 * Register Plugin Blocks
 *
 * @package MIDNewsletter
 * @subpackage MIDNewsletterCore
 * @since 1.0.0
 */

class Blocks
{

    // Register all blocks
    public static function register_all_blocks()
    {

         /**
         * Register "Settings" block
         *
         */

		 Settings::register();

    }
}