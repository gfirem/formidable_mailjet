<div class="mj_schedule_container">
	<div class="mj_schedule_enabled_container">
		<input type="checkbox" target_enabled="mj_schedule_enabled_<?php echo esc_attr( $html_id ) ?>" target="field_<?php echo esc_attr( $html_id ) ?>" class="mj_schedule_enabled" id="mj_schedule_enabled_<?php echo esc_attr( $html_id ) ?>">
	</div>
	<div class="mj_schedule_picker_container">
		<input type="text" class="mj_schedule_picker" id="field_<?php echo esc_attr( $html_id ) ?>" name="<?php echo esc_attr( $field_name ) ?>" value="<?php echo esc_attr( $print_value ); ?>" <?php do_action( 'frm_field_input_html', $field ) ?> />
	</div>
</div>