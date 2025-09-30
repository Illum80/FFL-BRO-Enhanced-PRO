<?php
// Add Davidsons settings section to the settings page

$file = 'ffl-bro-enhanced-pro.php';
$content = file_get_contents($file);

// Find the RSR settings section end and add Davidsons after it
$rsr_section_end = "echo '<input type=\"submit\" class=\"button button-primary\" value=\"Save RSR Settings\" />';";
$davidsons_section = $rsr_section_end . "\n        echo '</form><br>';\n        \n        echo '<h3>🏢 Davidsons Configuration</h3>';\n        echo '<form method=\"post\" action=\"options.php\">';\n        settings_fields('fflbro_settings');\n        echo '<table class=\"form-table\">';\n        echo '<tr><th>API Status</th><td><span style=\"color:#d63638;\">Not Connected - Using Manual CSV Upload</span></td></tr>';\n        echo '<tr><th>Markup %</th><td><input type=\"number\" name=\"fflbro_davidsons_markup\" value=\"' . esc_attr(get_option('fflbro_davidsons_markup', '15')) . '\" step=\"0.1\" class=\"small-text\" />% above dealer cost</td></tr>';\n        echo '</table>';\n        echo '<input type=\"submit\" class=\"button button-primary\" value=\"Save Davidsons Settings\" />';";

$content = str_replace($rsr_section_end, $davidsons_section, $content);

file_put_contents($file, $content);
echo "Davidsons settings section added!\n";
