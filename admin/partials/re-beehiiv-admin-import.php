<?php
/**
 * Import Page
 *
 * @package Re_Beehiiv
 */

use Re_Beehiiv\Import\Manage_Actions;


if ( ! defined( 'WPINC' ) ) {
	die;
}

$re_tab  = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : false; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$is_auto = $re_tab === 'auto-import';

$is_canceled = false;
if ( isset( $_GET['cancel'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$is_canceled = true;
}

$is_running = false;
if ( ! $is_auto ) {
	$manual_import_data = get_option( 're_beehiiv_manual_import_progress', array() );
	$is_running = ! empty( $manual_import_data ) && $manual_import_data['status'] !== 'finished' ? true : false;
}

if ( $is_canceled ) {
	$is_running = false;
	add_action(
		're_beehiiv_admin_notices',
		function() {
			?>
		<div class="re-beehiiv-import--notice re-beehiiv-import--notice-canceled">
			<h4><?php esc_html_e( 'Importing posts from Beehiiv is cancelled.', 're-beehiiv' ); ?></h4>
			<p class="description"><?php esc_html_e( 'The import process is cancelled. You can start the import process again.', 're-beehiiv' ); ?></p>
			<?php require_once RE_BEEHIIV_PATH . 'admin/partials/components/progressbar.php'; ?>
		</div>
			<?php
		}
	);

	//delete all rows pending roe from the re_beehiiv_import table
	$manual_import_data = get_option( 're_beehiiv_manual_import_progress', array() );
	Re_Beehiiv\Import\Import_Table::delete_row_by_group($manual_import_data['group_name']);

	//delete the current manual import option from the database
	delete_option( 're_beehiiv_manual_import_progress' );
}

if ( $is_running ) {
	$left_items	    = $manual_import_data['posts_progress']['pending_items'] ?? 0;
	$total_items    = $manual_import_data['posts_progress']['total_items'] ?? 0;
	$complete_items = $total_items - $left_items;

	$group_name       = $manual_import_data['group_name'];
	// notice with progress bar
	add_action(
		're_beehiiv_admin_notices',
		function() use ( $complete_items, $total_items, $group_name ) {
			$cancel_nonce = wp_create_nonce( 're_beehiiv_cancel_import' );
			$cancel_url   = add_query_arg(
				array(
					'page'   => 're-beehiiv-import',
					'cancel' => $group_name,
					'nonce'  => $cancel_nonce,
				),
				admin_url( 'admin.php' )
			);
			?>
		<div class="re-beehiiv-import--notice">
			<h4><?php esc_html_e( 'Importing posts from Beehiiv is in progress.', 're-beehiiv' ); ?></h4>
			<p class="description"><?php esc_html_e( 'The import process is currently running in the background. You may proceed with your work and close this page, but please be patient and wait until it is complete.', 're-beehiiv' ); ?>
			<br><strong><?php esc_html_e( 'Progress: ', 're-beehiiv' ); ?><span class="number" id="imported_count"><?php echo $complete_items . '</span> / <span class="number" id="total_count">' . $total_items; ?></span></strong></p>
			<a class="re-beehiiv-button-secondary re-beehiiv-button-cancel" id="re-beehiiv-import--cancel" href="<?php echo esc_url( $cancel_url ); ?>"><?php esc_html_e( 'Cancel', 're-beehiiv' ); ?></a>
			<?php require_once RE_BEEHIIV_PATH . 'admin/partials/components/progressbar.php'; ?>
		</div>
			<?php
		}
	);
}


// get all taxonomies based on post type
$post_types = get_post_types(
	array(
		'public' => true,
	),
	'objects'
);


$taxonomies = array();
foreach ( $post_types as $re_post_type ) {
	if ( $re_post_type->name === 'attachment' ) {
		continue;
	}
	$post_type_taxonomies = get_object_taxonomies( $re_post_type->name, 'objects' );

	foreach ( $post_type_taxonomies as $re_taxonomy ) {
		if ( $re_taxonomy->public != 1 || $re_taxonomy->name === 'post_format' ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
			continue;
		}
		$taxonomies[ $re_post_type->name ][] = array(
			'name'  => $re_taxonomy->name,
			'label' => $re_taxonomy->label,
		);
	}
}

$taxonomy_terms = array();
foreach ( $taxonomies as $re_post_type => $re_taxonomy ) {
	foreach ( $re_taxonomy as $re_tax ) {
		$terms = get_terms(
			array(
				'taxonomy'   => $re_tax['name'],
				'hide_empty' => false,
			)
		);
		$taxonomy_terms[ $re_post_type ][ $re_tax['name'] ] = $terms;
	}
}

$wp_post_status = get_post_stati( array( 'show_in_admin_all_list' => true ), 'objects' );
$post_statuses  = array();
// Filter post statuses.

foreach ( $wp_post_status as $post_status => $post_status_object ) {
	if ( 'future' === $post_status ) {
		continue;
	}
	$post_statuses[] = array(
		'name'  => $post_status,
		'label' => $post_status_object->label,
	);
}


$default_args         = array(
	'auto'           => 'manual',
	'content_type'   => 'free_web_content',
	'beehiiv-status' => 'confirmed',
	'post_tags'      => '1',
	'post_status'    => array(
		'confirmed' => 'publish',
	),
	'import_method'  => 'new_and_update',
	'cron_time'      => '6',
);
$is_auto_action_exist = false;
if ( $is_auto ) {
	$args                 = Manage_Actions::get_auto_action_args();
	$is_auto_action_exist = ! empty( $args ) ? true : false;

	if ( $is_auto_action_exist ) {
		$args = reset( $args );
	}

	$default_args = wp_parse_args( $args, $default_args );
}
if ( $is_auto_action_exist ) {
	add_action(
		're_beehiiv_admin_notices',
		function() use ( $args ) {

			if ( isset( $args['taxonomy_term'] ) && isset( $args['taxonomy'] ) ) {
				$term = get_term( $args['taxonomy_term'], $args['taxonomy'] );
			}

			$is_new_item_add         = $args['import_method'] !== 'update';
			$is_existing_item_update = $args['import_method'] !== 'new';
			?>
			<div class="re-beehiiv-import--notice">
				<h4><?php esc_html_e( 'Auto Import is set', 're-beehiiv' ); ?></h4>
				<p class="description">
					<?php
					$post_status_str = '';
					foreach ( $args['post_status'] as $status => $post_status ) {
						if ( 'confirmed' === $status ) {
							$status = __( 'published', 're-beehiiv' );
						}
						$post_status_str .= sprintf(
							// Translators: %1$s: beehiiv post status, %2$s: post status.
							esc_html__( '"%1$s" will be "%2$s"', 're-beehiiv' ),
							ucwords( $status ),
							ucwords( $post_status )
						);

						// Add comma if not last item.
						if ( end( $args['post_status'] ) !== $post_status ) {
							$post_status_str .= ' and ';
						}
					}
					$post_status_str = sprintf(
						// Translators: %s: "Published" will be "Publish" and "Archived" will be "Draft"
						esc_html__( 'Posts with status %s', 're-beehiiv' ),
						$post_status_str
					);
					if ( ! $term instanceof WP_Error ) {
						echo sprintf(
							// Translators: %1$s: cron time, %2$s: post type, %3$s: taxonomy, %4$s: term name, %5$s: post status, %6$s: new item add, %7$s: existing item update.
							esc_html__( 'Current Auto Import is set to run every "%1$s" hours and will import to "%2$s" post type and "%3$s" taxonomy with "%4$s" term. %5$s. The new items will %6$s imported and the Existing posts will %7$s updated. You can modify these settings below to customize the automatic import process to your needs.', 're-beehiiv' ),
							'<strong>' . esc_html( $args['cron_time'] ) . '</strong>',
							'<strong>' . esc_html( $args['post_type'] ) . '</strong>',
							'<strong>' . esc_html( $args['taxonomy'] ) . '</strong>',
							'<strong>' . esc_html( $term->name ) . '</strong>',
							$post_status_str, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							( $is_new_item_add === true ? esc_html__( 'be', 're-beehiiv' ) : esc_html__( 'not be', 're-beehiiv' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
							( $is_existing_item_update === true ? esc_html__( 'be', 're-beehiiv' ) : esc_html__( 'not be', 're-beehiiv' ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
						);
					} else {
						// Translators: %1$s: cron time, %2$s: post type, %3$s: post status, %4$s: new item add, %5$s: existing item update.
						echo sprintf( esc_html__( 'Current Auto Import is set to run every "%1$s" hours and will import to "%2$s" post type. %3$s. The new items will %4$s imported and the Existing posts will %5$s updated. You can modify these settings below to customize the automatic import process to your needs.', 're-beehiiv' ), '<strong>' . esc_html( $args['cron_time'] ) . '</strong>', '<strong>' . esc_html( $args['post_type'] ) . '</strong>', $post_status_str, ( $is_new_item_add === true ? esc_html__( 'be', 're-beehiiv' ) : esc_html__( 'not be', 're-beehiiv' ) ), ( $is_existing_item_update === true ? esc_html__( 'be', 're-beehiiv' ) : esc_html__( 'not be', 're-beehiiv' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
					}
					?>
				</p>
			</div>
			<?php
		}
	);
}
$import_title = $is_auto ? __( 'Auto', 're-beehiiv' ) : __( 'Manual', 're-beehiiv' );
?>
<script>
var AllTaxonomies = <?php echo wp_json_encode( $taxonomies ); ?>;
var AllTaxonomyTerms = <?php echo wp_json_encode( $taxonomy_terms ); ?>;
var AllPostStatuses = <?php echo wp_json_encode( $post_statuses ); ?>;
var AllDefaultArgs = <?php echo wp_json_encode( $default_args ); ?>;
</script>
<div class="re-beehiiv-wrap">


	<?php require_once 'components/header.php'; ?>
	<div class="re-beehiiv-heading">
		<h1>
		<?php
		esc_html_e( 'Import Content', 're-beehiiv' );
		?>
		</h1>

					<p>
						<?php esc_html_e( 'Choose how to import content from Beehiiv to your WordPress Site.', 're-beehiiv' ); ?>
						<br>
						<?php 
							if ( !$is_auto ) {
								esc_html_e( 'This feature allows you to pull content from Beehiiv and publish it on your WordPress website.', 're-beehiiv' ); 
							} else {
								esc_html_e( 'Set up an automatic process to periodically fetch and integrate content from Beehiiv into your WordPress website.' ); 
							}
						?>
					</p>	
	</div>

	<div class="re-beehiiv-tabs">
		<nav class="nav-tab-wrapper">
			<a class="re-nav-tab <?php echo $is_auto ? '' : 're-nav-tab-active'; ?>" data-tab="re-beehiiv-import" id="re-beehiiv-import-tab" href="<?php echo esc_url( admin_url( 'admin.php?page=re-beehiiv-import' ) ); ?>"><?php esc_html_e( 'Manual Import', 're-beehiiv' ); ?></a>
			<a class="re-nav-tab <?php echo $is_auto ? 're-nav-tab-active' : ''; ?>" data-tab="re-beehiiv-auto-import" id="re-beehiiv-auto-import-tab" href="<?php echo esc_url( admin_url( 'admin.php?page=re-beehiiv-import&tab=auto-import' ) ); ?>"><?php esc_html_e( 'Auto Import', 're-beehiiv' ); ?></a>
		</nav>
	</div>

	<div class="re-beehiiv-wrapper border-t-0">
		<div class="re-beehiiv-import--notices <?php echo ! $is_running && $is_canceled ? 're-beehiiv-import-form--hide_canceled' : ''; ?>" id="re-beehiiv-import--notices">
			<div class="hidden re-beehiiv-import--notice re-beehiiv-import--notice-error">
				<h4><?php esc_html_e( 'Please fix the following errors:', 're-beehiiv' ); ?></h4>
				<ul>
				</ul>
			</div>
			<?php do_action( 're_beehiiv_admin_notices' ); ?>
			<!-- convert notice above to new format -->
		</div>
		<?php if ( ! $is_running || ( $is_canceled && ! $is_running ) ) : ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="re-beehiiv-import-form" class="re-beehiiv-import-form">
			<div class="re-beehiiv-import-fields">
				<div class="re-beehiiv-import-fields--step import-fields--step1 <?php echo ! $is_auto_action_exist ? 'active' : ''; ?>">
					<h2 class="re-beehiiv-import-fields--step--title" data-error-count="0"><?php esc_html_e( 'Step1: Choose data from Beehiiv', 're-beehiiv' ); ?></h2>
					<div class="re-beehiiv-import-fields--step--content">
						<fieldset>
							<label for="re-beehiiv-content_type" class="pr-2"><strong><?php esc_html_e( 'Content Type', 're-beehiiv' ); ?></strong>
							<small id="step1_content_type">
							   <i class="fa-solid fa-circle-question" style="color: #65696c;"></i>
							</small>
						</label>
							<?php
							$content_types = array(
								'free_web_content'    => __( 'Free', 're-beehiiv' ),
								'premium_web_content' => __( 'Premium', 're-beehiiv' ),
							);

							foreach ( $content_types as $content_type => $label ) {
								if ( is_array( $default_args['content_type'] ) ) {
									$checked = in_array( $content_type, $default_args['content_type'], true ) ? 'checked' : '';
								} else {
									$checked = ( $default_args['content_type'] === $content_type ) ? 'checked' : '';
								}
								?>
								<label class="pr-2 d-block" >
									<input type="checkbox" name="re-beehiiv-content_type[]" id="re-beehiiv-content_type" value="<?php echo esc_attr( $content_type ); ?>" <?php echo esc_attr( $checked ); ?>> <?php echo esc_html( $label ); ?>
								</label>
								<?php
							}
							?>
							<p class="description"><?php esc_html_e( 'Select the type of content you want to import.', 're-beehiiv' ); ?></p>
						</fieldset>

						<fieldset>
							<label for="re-beehiiv-beehiiv-status[]" class="pr-2">
								<strong><?php esc_html_e( 'Post Status', 're-beehiiv' ); ?></strong>
								<small id="step1_post_status">
									<i class="fa-solid fa-circle-question" style="color: #65696c;"></i>
								</small>
							</label>
							<?php
							$beehiiv_statuses = array(
								'confirmed' => __( 'Published', 're-beehiiv' ),
								'archived'  => __( 'Archived', 're-beehiiv' ),
								'draft'     => __( 'Draft', 're-beehiiv' ),
							);

							foreach ( $beehiiv_statuses as $beehiiv_status => $label ) {
								if ( is_array( $default_args['beehiiv-status'] ) ) {
									$checked = in_array( $beehiiv_status, $default_args['beehiiv-status'], true ) ? 'checked' : '';
								} else {
									$checked = ( $default_args['beehiiv-status'] === $beehiiv_status ) ? 'checked' : '';
								}
								?>
								<label class="pr-2 d-block">
									<input type="checkbox" name="re-beehiiv-beehiiv-status[]" id="re-beehiiv-beehiiv-status"  value="<?php echo esc_attr( $beehiiv_status ); ?>" <?php echo esc_attr( $checked ); ?>> <?php echo esc_html( $label ); ?>
								</label>
								<?php
							}
							?>
							<p class="description"><?php esc_html_e( 'Choose the status of the content you want to import.', 're-beehiiv' ); ?></p>
						</fieldset>
					</div>
				</div>
				<div class="re-beehiiv-import-fields--step import-fields--step2" data-error-count="0">
					<h2 class="re-beehiiv-import-fields--step--title" data-error-count="0"><?php esc_html_e( 'Step 2: Import data to WordPress', 're-beehiiv' ); ?></h2>
					<div class="re-beehiiv-import-fields--step--content">
						<fieldset>
							<label class="d-block" for="re-beehiiv-post_type">
								<strong><?php esc_html_e( 'Select Post Type and Taxonomy', 're-beehiiv' ); ?></strong>
								<small id="step2_post_type">
									<i class="fa-solid fa-circle-question" style="color: #65696c;"></i>
								</small>
							</label>
							<select name="re-beehiiv-post_type" id="re-beehiiv-post_type" required>
								<option value="0"><?php esc_html_e( 'Select Post Type', 're-beehiiv' ); ?></option>
								<?php
								foreach ( $post_types as $re_post_type ) {
									if ( $re_post_type->name === 'attachment' ) {
										continue;
									}
									echo '<option value="' . esc_attr( $re_post_type->name ) . '">' . esc_html( $re_post_type->labels->singular_name ) . '</option>';
								}
								?>
							</select>
							<select name="re-beehiiv-taxonomy" id="re-beehiiv-taxonomy" class="hidden re-beehiiv-taxonomy" required>
								<option value="0"><?php esc_html_e( 'Select Post Type First', 're-beehiiv' ); ?></option>
							</select>
							<select name="re-beehiiv-taxonomy_term" id="re-beehiiv-taxonomy_term" class="hidden re-beehiiv-taxonomy_term" required>
								<option value="0"><?php esc_html_e( 'Select Term', 're-beehiiv' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'Choose the post type and taxonomy for the imported content.', 're-beehiiv' ); ?></p>
						</fieldset>
						<fieldset>
							<label for="re-beehiiv-post_author" class="d-block">
								<strong><?php esc_html_e( 'Content Author', 're-beehiiv' ); ?></strong>
								<small id="step2_post_author">
									<i class="fa-solid fa-circle-question" style="color: #65696c;"></i>
								</small>
							</label>
							<select name="re-beehiiv-post_author" id="re-beehiiv-post_author" required>
								<option value="0"><?php esc_html_e( 'Select Author', 're-beehiiv' ); ?></option>
								<?php
								$authors = get_users( array( 'role__in' => array( 'author', 'editor', 'administrator' ) ) );
								foreach ( $authors as $author ) {
									echo '<option value="' . esc_attr( $author->ID ) . '">' . esc_html( $author->display_name ) . '</option>';
								}
								?>
							</select>
							<p class="description"><?php esc_html_e( 'Assign the imported posts to a specific user.', 're-beehiiv' ); ?></p>
						</fieldset>
						<fieldset>
							<label for="re-beehiiv-post_tags">
								<strong><?php esc_html_e( 'Beehiiv Tags', 're-beehiiv' ); ?></strong>
								<small id="step2_post_tags">
									<i class="fa-solid fa-circle-question" style="color: #65696c;"></i>
								</small>
							</label>
							<select name="re-beehiiv-post_tags-taxonomy" id="re-beehiiv-post_tags-taxonomy" class="re-beehiiv-post_tags-taxonomy">
								<option value="0"><?php esc_html_e( 'Select post type first', 're-beehiiv' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'To import tags from Beehiiv, select the taxonomy and term you want to use for the imported tags.', 're-beehiiv' ); ?></p>
						</fieldset>
						<fieldset id="re-beehiiv-post_status">
							<label for="re-beehiiv-post_status">
								<strong><?php esc_html_e( 'Post Status', 're-beehiiv' ); ?></strong>
								<small id="step2_post_status">
									<i class="fa-solid fa-circle-question" style="color: #65696c;"></i>
								</small>
							</label>
							<div class="re-beehiiv-post_status--fields"></div>
							<p class="description"><?php esc_html_e( 'Assign Post Status to the imported content for each Post Status selected in step 1.', 're-beehiiv' ); ?></p>
						</fieldset>

						<fieldset>
							<label for="re-beehiiv-import_method"><strong><?php esc_html_e( 'Import Option', 're-beehiiv' ); ?></strong>
								<small id="step2_import_method">
									<i class="fa-solid fa-circle-question" style="color: #65696c;"></i>
								</small>
							</label>
							<?php
							$import_methods = array(
								'new'            => __( 'Import new items', 're-beehiiv' ),
								'update'         => __( 'Update existing items', 're-beehiiv' ),
								'new_and_update' => __( 'Do both', 're-beehiiv' ),
							);

							foreach ( $import_methods as $import_method => $label ) {
								if ( is_array( $default_args['import_method'] ) ) {
									$checked = in_array( $import_method, $default_args['import_method'], true ) ? 'checked' : '';
								} else {
									$checked = ( $default_args['import_method'] === $import_method ) ? 'checked' : '';
								}
								?>
								<label class="pr-2 d-block">
									<input type="radio" name="re-beehiiv-import_method" id="re-beehiiv-import_method" value="<?php echo esc_attr( $import_method ); ?>" <?php echo esc_attr( $checked ); ?>> <?php echo esc_html( $label ); ?>
								</label>
								<?php
							}
							?>
							<p class="description"><?php esc_html_e( 'Choose the desired action for importing data.', 're-beehiiv' ); ?></p>
						</fieldset>
						<?php if ( $is_auto ) : ?>
							<fieldset>
								<label for="re-beehiiv-cron_time" class="d-block">
									<strong><?php esc_html_e( 'Import Schedule', 're-beehiiv' ); ?></strong>
									<small id="step2_cron_time">
										<i class="fa-solid fa-circle-question" style="color: #65696c;"></i>
									</small>
						    	</label>
								<input type="number" name="re-beehiiv-cron_time" id="re-beehiiv-cron_time" value="<?php echo esc_attr( $default_args['cron_time'] ); ?>" min="1" required placeholder="<?php esc_attr_e( 'Enter interval in hours', 're-beehiiv' ); ?>">
									<p class="description"><?php esc_html_e( 'Enter the desired time intervals in hours and set the frequency of auto imports from your Beehiiv to your WordPress site.', 're-beehiiv' ); ?></p>
							</fieldset>
						<?php endif; ?>
					</div>
				</div>
			</div>
			<input type="hidden" name="action" value="<?php echo $is_auto ? 're_beehiiv_auto_import' : 're_beehiiv_manual_import'; ?>">
			<input type="hidden" name="re_beehiiv_import_nonce" id="re_beehiiv_import_nonce" value="<?php echo esc_attr( wp_create_nonce( 're_beehiiv_import_nonce' ) ); ?>">
			<?php
			$disabled = $is_running && ! $is_auto ? 'disabled' : '';
			if ( $disabled ) {
				echo '<p>';
				esc_html_e( 'It is not possible to initiate another manual import while the current one is still in progress. refresh the page to update the status.', 're-beehiiv' );
				echo '</p>';
			}
			$submit_text = $is_auto ? __( 'Save', 're-beehiiv' ) : __( 'Start Import', 're-beehiiv' );
			submit_button( $submit_text, 'primary components-button is-primary', 're-beehiiv-start-import', false, $disabled );
			?>
		</form>
		<?php endif; ?>
	</div>
</div>
