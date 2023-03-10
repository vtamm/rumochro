<?php
/**
 * Underscore.js template.
 *
 * @since 2.0
 * @package fusion-library
 */

?>
<#
	var fieldId = 'undefined' === typeof param.param_name ? param.id : param.param_name;
#>
<div class="hubspot-map-holder">
	<div class="fusion-mapping">
		<span><?php esc_attr_e( 'No form fields or HubSpot communication preferences found.', 'Avada' ); ?></span>
	</div>
</div>
<input type="hidden" id="{{ fieldId }}" name="{{ fieldId }}" value="{{ option_value }}">
