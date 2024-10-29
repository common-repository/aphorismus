<?php
function aphorismus_export($show = false){
	global $wpdb;
	$filename = PLUGIN_NAME_APHORISMUS . '_' . date('Y-m-d') . '.xml';
	if ($show === true){
		header('Content-Type: text/xml; charset=' . get_option('blog_charset'), true);
		}
	else {
		header('Content-Description: File Transfer');
		header('Content-Disposition: attachment; filename='.$filename);
		header('Content-Type: application/xml; charset=' . get_option('blog_charset'), true);
		}
	echo "<?xml version=\"1.0\" encoding=\"" . get_option('blog_charset') . "\"  standalone=\"yes\"?>\n";
	echo "<AphorismusBase xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\">\n";
	$aphorisms = $wpdb->get_results("SELECT id, text, author FROM ".APHORISMUS_TABLE." ORDER BY text;", 'ARRAY_A');
	if (!empty($aphorisms)){
		foreach ($aphorisms as $aphorism){
			echo "\t<Aphorism>\n";
			echo "\t\t<Text><![CDATA[" . str_replace(array("\r", "\n"), array("", " "), $aphorism['text']) . "]]></Text>\n";
			if (!empty($aphorism['author'])) echo "\t\t<Author><![CDATA[" . $aphorism['author'] . "]]></Author>\n";
			echo "\t</Aphorism>\n";
			}
		}
	echo "</AphorismusBase>";
	}
?>