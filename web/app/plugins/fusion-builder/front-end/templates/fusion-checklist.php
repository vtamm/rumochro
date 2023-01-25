<?php
/**
 * Underscore.js template
 *
 * @package fusion-builder
 * @since 2.0
 */

?>
<script type="text/html" id="tmpl-fusion_checklist-shortcode">
<ul {{{ _.fusionGetAttributes( checklistShortcode ) }}}></ul>
</script>

<script type="text/html" id="tmpl-fusion_li_item-shortcode">
<span {{{ _.fusionGetAttributes( checklistShortcodeSpan ) }}}>
	<# if ( 'numbered' === parentValues.type ) { #>
		{{counter}}
	<# } else { #>
		<i {{{ _.fusionGetAttributes( checklistShortcodeIcon ) }}}></i>
	<# } #>
</span>
<div {{{ _.fusionGetAttributes( checklistShortcodeItemContent ) }}}>{{{ FusionPageBuilderApp.renderContent( output, cid, false ) }}}</div>
</script>
