<?php
/**
 * Template used to display role options.
 *
 * @package Fusion Builder
 * @subpackage Templates
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

// Return early if user role does not have any of the basic capabilities.
if ( ! AWB_Access_Control::wp_role_has_core_capability( $role, [ 'manage_options', 'edit_posts' ], 'or' ) ) {
	return;
}

$role_id = esc_html( strtolower( str_replace( [ ' ', '-' ], '_', $role['name'] ) ) );
?>
<div class="awb-access-control-item">
	<button class="awb-access-control-item-title" type="button" data-target="<?php echo esc_html( $role_id ); ?>">
		<?php echo esc_html( translate_user_role( $role['name'] ) ); ?>
	</button>
	<div id="<?php echo esc_html( $role_id ); ?>" class="awb-access-control-item-accordion">
	<div class="awb-access-control-item-accordion-body">
			<ul>
			<?php if ( AWB_Access_Control::wp_role_has_core_capability( $role, [ 'manage_options' ] ) ) : ?>
				<li>
					<label for="global-options-<?php echo esc_html( $role_id ); ?>">
						<input name="capabilities[<?php echo esc_html( $role_id ); ?>][]" type="checkbox" <?php AWB_Access_Control::maybe_checked( $role_id, 'global_options' ); ?> value="global_options" id="global-options-<?php echo esc_html( $role_id ); ?>"/><?php echo esc_html__( 'Global Options', 'fusion-builder' ); ?>
					</lable>
				</li>
			<?php endif; ?>
			<?php if ( AWB_Access_Control::wp_role_has_core_capability( $role, [ 'edit_posts' ] ) ) : ?>
				<li>
					<label for="global-elements-<?php echo esc_html( $role_id ); ?>">
						<input name="capabilities[<?php echo esc_html( $role_id ); ?>][]" type="checkbox" <?php AWB_Access_Control::maybe_checked( $role_id, 'global_elements' ); ?> value="global_elements" id="global-elements-<?php echo esc_html( $role_id ); ?>"/><?php echo esc_html__( 'Global Elements', 'fusion-builder' ); ?>
					</lable>
				</li>
				<?php foreach ( $post_types as $post_type ) : ?>
					<?php
					$post_type = 'avada_library' !== $post_type ? get_post_type_object( $post_type ) : AWB_Access_Control::get_library_object();

					if ( ! is_object( $post_type ) || ! isset( $post_type->name ) || ! isset( $post_type->label ) || ( in_array( $post_type->name, [ 'post', 'page' ], true ) && ! isset( $role['capabilities'][ 'edit_' . $post_type->name . 's' ] ) ) ) {
						continue;
					}

					$item_id        = $role_id . '-' . $post_type->name; // here.
					$maybe_disabled = '';

					if ( 'fusion_tb_layout' === $post_type->name && ! AWB_Access_Control::wp_role_has_core_capability( $role, [ 'manage_options' ] ) ) {
						continue;
					}
					?>
					<li class="title-label"><?php echo esc_html( AWB_Access_Control::get_post_type_label( $post_type ) ); ?>
						<ul class="awb-access-items-cpt">
						<?php if ( ! in_array( $post_type->name, [ 'post', 'page' ], true ) ) : ?>
							<?php $maybe_disabled = 'checked' === AWB_Access_Control::maybe_checked( $role_id, $post_type->name . '_dashboard_menu', false ) ? '' : 'disabled'; ?>
						<li class="awb-access-item-cpt awb-dashboard-access">
							<label for="dashboard-menu-<?php echo esc_html( $item_id ); ?>">
								<input name="capabilities[<?php echo esc_html( $role_id ); ?>][]" type="checkbox" <?php AWB_Access_Control::maybe_checked( $role_id, $post_type->name . '_dashboard_menu' ); ?> value="<?php echo esc_html( $post_type->name ); ?>_dashboard_menu" class="awb-access-control-dashboard-menu" id="dashboard-menu-<?php echo esc_html( $item_id ); ?>"/><?php echo esc_html__( 'Dashboard Menu', 'fusion-builder' ); ?>
							</lable>
						</li>
					<?php endif; ?>
						<?php if ( ! in_array( $post_type->name, [ 'fusion_icons', 'fusion_tb_layout' ], true ) ) : ?>
							<?php if ( 'slide' !== $post_type->name ) : ?>
							<li class="awb-access-item-cpt" <?php echo esc_html( $maybe_disabled ); ?>>
								<label for="avada-builder-<?php echo esc_html( $item_id ); ?>">
									<input name="capabilities[<?php echo esc_html( $role_id ); ?>][]" type="checkbox" <?php echo esc_html( $maybe_disabled ); ?> <?php AWB_Access_Control::maybe_checked( $role_id, $post_type->name . '_avada_builder' ); ?> value="<?php echo esc_html( $post_type->name ); ?>_avada_builder" id="avada-builder-<?php echo esc_html( $item_id ); ?>"/><?php echo esc_html__( 'Back-end Builder', 'fusion-builder' ); ?>
								</lable>
							</li>
							<li class="awb-access-item-cpt <?php echo esc_html( $maybe_disabled ); ?>">
								<label for="avada-live-<?php echo esc_html( $item_id ); ?>">
									<input name="capabilities[<?php echo esc_html( $role_id ); ?>][]" type="checkbox" <?php echo esc_html( $maybe_disabled ); ?> <?php AWB_Access_Control::maybe_checked( $role_id, $post_type->name . '_avada_live' ); ?> value="<?php echo esc_html( $post_type->name ); ?>_avada_live" id="avada-live-<?php echo esc_html( $item_id ); ?>"/><?php echo esc_html__( 'Live Builder', 'fusion-builder' ); ?>
								</lable>
							</li>
						<?php endif; ?>
						<li class="awb-access-item-cpt" <?php echo esc_html( $maybe_disabled ); ?>>
							<label for="page-options-<?php echo esc_html( $item_id ); ?>">
								<input name="capabilities[<?php echo esc_html( $role_id ); ?>][]" type="checkbox" <?php echo esc_html( $maybe_disabled ); ?> <?php AWB_Access_Control::maybe_checked( $role_id, $post_type->name . '_page_options' ); ?> value="<?php echo esc_html( $post_type->name ); ?>_page_options" id="page-options-<?php echo esc_html( $item_id ); ?>"/><?php echo esc_html__( 'Page Options', 'fusion-builder' ); ?>
							</lable>
						</li>
							<?php if ( 'fusion_form' === $post_type->name ) : ?>
							<li class="awb-access-item-cpt" <?php echo esc_html( $maybe_disabled ); ?>>
								<label for="forms-submissions-<?php echo esc_html( $item_id ); ?>">
									<input name="capabilities[<?php echo esc_html( $role_id ); ?>][]" type="checkbox" <?php echo esc_html( $maybe_disabled ); ?> <?php AWB_Access_Control::maybe_checked( $role_id, $post_type->name . '_submissions' ); ?> value="<?php echo esc_html( $post_type->name ); ?>_submissions" id="forms-submissions-<?php echo esc_html( $item_id ); ?>"/><?php echo esc_html__( 'View Submissions', 'fusion-builder' ); ?>
								</lable>
							</li>
						<?php endif; ?>
						<?php endif; ?>
					</ul>
				</li>
				<?php endforeach; ?>
				<?php endif; ?>
			</ul>
		</div>
	</div>
</div>
