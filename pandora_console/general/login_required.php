<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

/**
 * @package General
 */

global $config;

if (is_ajax()) {
	
	$save_identification = get_parameter ('save_required_wizard', 0);
	
	// Updates the values get on the identification wizard
	if ($save_identification) {
		$email = get_parameter ('email', false);
		$timezone = get_parameter ('timezone', false);
		$language = get_parameter ('language', false);
		
		if ($email !== false) config_update_value ('language', $language);
		if ($timezone !== false) config_update_value ('timezone', $timezone);
		if ($email !== false) db_process_sql_update ('tusuario', 
								array ('email' => $email), array('id_user' => $config['id_user']));
								
		config_update_value ('initial_wizard', 1);
		
	}
	
	return;
}

$email = db_get_value ('email', 'tusuario', 'id_user', $config['id_user']);
//Avoid to show default email
if ($email == 'admin@example.com') $email = '';

// Prints first step pandora registration
echo '<div id="login_id_dialog" title="' .
	__('Pandora FMS instance identification wizard') . '" style="display: none;">';
	
	echo '<div style="font-size: 10pt; margin: 20px;">';
	echo __('Pandora FMS requires an identification of each administrator. Please, fill next information:');
	echo '</div>';
	
	echo '<div style="">';
		$table = new StdClass();
		$table->class = 'databox filters';
		$table->width = '100%';
		$table->data = array ();
		$table->size = array();
		$table->size[0] = '40%';
		$table->style[0] = 'font-weight:bold';
		$table->size[1] = '60%';
		$table->border = '5px solid';
		
		$table->data[0][0] = __('Language code for Pandora');
		$table->data[0][1] = html_print_select_from_sql (
			'SELECT id_language, name FROM tlanguage',
			'language', $config['language'] , '', '', '', true);
		
		$zone_name = array('Africa' => __('Africa'), 'America' => __('America'), 'Antarctica' => __('Antarctica'), 'Arctic' => __('Arctic'), 'Asia' => __('Asia'), 'Atlantic' => __('Atlantic'), 'Australia' => __('Australia'), 'Europe' => __('Europe'), 'Indian' => __('Indian'), 'Pacific' => __('Pacific'), 'UTC' => __('UTC'));

		if ($zone_selected == "") {
			if ($config["timezone"] != "") {
				list($zone) = explode("/", $config["timezone"]);
				$zone_selected = $zone;
			}
			else {
				$zone_selected = 'Europe';
			}
		}

		$timezones = timezone_identifiers_list();
		foreach ($timezones as $timezone) {
			if (strpos($timezone, $zone_selected) !== false) { 
				$timezone_n[$timezone] = $timezone;
			}
		}			
		
		$table->data[2][0] = __('Timezone setup'). ' ' . ui_print_help_tip(
			__('Must have the same time zone as the system or database to avoid mismatches of time.'), true);
		$table->data[2][1] = html_print_select($zone_name, 'zone', $zone_selected, 'show_timezone()', '', '', true);
		$table->data[2][1] .= "&nbsp;&nbsp;". html_print_select($timezone_n, 'timezone', $config["timezone"], '', '', '', true);
		
		$table->data[4][0] = __('E-mail');
		$table->data[4][1] = html_print_input_text ('email', $email, '', 50, 255, true);
		
		html_print_table ($table);
	echo '</div>';
	
	echo '<div style="position:absolute; margin: 0 auto; bottom: 0px; right: 10px; border: 1px solid #FFF; width: 570px">';
		echo '<div style="float: right; width: 20%;">';
		html_print_submit_button("Register", 'id_dialog_button', false, 'class="ui-button-dialog ui-widget ui-state-default ui-corner-all ui-button-text-only sub ok" style="width:100px;"');  
		echo '</div>';
		echo '<div id="all-required" style="float: right; margin-right: 30px; display: none; color: red;">';
			echo __("All fields required");
		echo '</div>';
	echo '</div>';
	
echo '</div>';

?>

<script type="text/javascript" language="javascript">
/* <![CDATA[ */

////////////////////////////////////////////////////////////////////////
//HELPER FUNCTIONS
function show_timezone () {
	zone = $("#zone").val();
	console.log("Z: " +zone);
	$.ajax({
		type: "POST",
		url: "ajax.php",
		data: "page=godmode/setup/setup&select_timezone=1&zone=" + zone,
		dataType: "json",
		success: function(data) {
			$("#timezone").empty();
			jQuery.each (data, function (id, value) {
				timezone = value;
				$("select[name='timezone']").append($("<option>").val(timezone).html(timezone));
			});
		}
	});
}

////////////////////////////////////////////////////////////////////////
//EVENT FUNCTIONS
$("#submit-id_dialog_button").click (function () {
	
	//All fields required
	if ($("#text-email").val() == '') {
		$("#all-required").show();
	} else {
		var timezone = $("#timezone").val();
		var language = $("#language").val();
		var email_identification = $("#text-email").val();
		
		jQuery.post ("ajax.php",
			{"page": "general/login_required",
			"save_required_wizard": 1,
			"email": email_identification,
			"language": language,
			"timezone": timezone},
			function (data) {}
		);
					
		$("#login_id_dialog").dialog('close');
		first_time_identification ();
	}
});

////////////////////////////////////////////////////////////////////////
//DISPLAY
$(document).ready (function () {
	
	$("#login_id_dialog").dialog({
		resizable: true,
		draggable: true,
		modal: true,
		height: 280,
		width: 630,
		overlay: {
				opacity: 0.5,
				background: "black"
			},
		closeOnEscape: false,
		open: function(event, ui) { $(".ui-dialog-titlebar-close").hide(); }
	});
});

/* ]]> */
</script>
