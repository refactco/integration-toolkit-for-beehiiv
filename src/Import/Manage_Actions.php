<?php // phpcs:ignore Squiz.Commenting.FileComment.Missing

namespace WP_to_Beehiiv_Integration\Import;

use ActionScheduler_Action;

/**
 * Class Manage_Actions
 * This class is responsible for managing scheduled actions
 * This class extends ActionScheduler_Action class because we need to use private data of ActionScheduler_Action class
 *
 * @package WP_to_Beehiiv_Integration\Import
 */
class Manage_Actions extends ActionScheduler_Action {

	/**
	 * Get all auto recurring import action args
	 *
	 * @return array
	 */
	public static function get_auto_action_args() {

		$actions = self::get_actions( 'auto_recurring_import', 'pending' );

		$args = array();
		foreach ( $actions as $action ) {
			$args[] = $action->args['args'];
		}

		return $args;
	}


	/**
	 * Get all scheduled actions for a given group and status
	 *
	 * @param string $group
	 * @param string $status
	 * @return array
	 */
	public static function get_actions( $group = '', $status = '' ) {

		$args = array(
			'hook'     => 'wp_to_beehiiv_integration_bulk_import',
			'group'    => $group ? $group : '',
			'per_page' => -1,
		);

		if ( $status ) {
			$args['status'] = $status;
		}

		$actions = as_get_scheduled_actions( $args );

		return $actions;
	}

	/**
	 * Remove all scheduled actions of auto recurring import
	 *
	 * @return void
	 */
	public static function remove_auto_actions() {

		$actions = self::get_actions( 'auto_recurring_import' );

		foreach ( $actions as $action ) {
			as_unschedule_action( $action->hook, $action->args, $action->args['group'] );
			as_unschedule_all_actions( $action->hook, $action->args, $action->args['group'] );
		}

		$actions = self::get_actions( 'auto_recurring_import_task' );

		foreach ( $actions as $action ) {
			as_unschedule_action( $action->hook, $action->args, $action->args['group'] );
			as_unschedule_all_actions( $action->hook, $action->args, $action->args['group'] );
		}

	}

	/**
	 * Remove all scheduled actions of a given group
	 *
	 * @param string $group
	 * @return void
	 */
	public static function remove_actions( $group ) {
		$actions = self::get_actions( $group );
		foreach ( $actions as $action ) {
			as_unschedule_all_actions( $action->hook, $action->args, $action->args['group'] );
		}
	}
}
