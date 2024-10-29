<?php
class aphorismus_importer {
	var $wpdb;
	var $file;
	var $xml_array = array();
	var $data = '';
	var $import_count = 0;

	function aphorismus_importer(&$wpdb){
		$this->wpdb = $wpdb;
		}

	function aphorismus_importer_file($data_file = ''){
		if (!empty($data_file)) $this->file = $data_file;
		else return false;
		if (file_exists($this->file)){
			$check = wp_check_filetype($this->file, array('xml' => 'application/xml'));
			if ($check['type'] != 'application/xml') return false;
			$fp = fopen($this->file, "r");
			$this->data = fread($fp, filesize($this->file));
			fclose($fp);
			return true;
			}
		else return false;
		}

	function aphorismus_importer_result($data = ''){
		$this->xml_array = $this->aphorismus_importer_parser($data);
		if (count($this->xml_array) > 0){
			for ($e = 0; $e < count($this->xml_array); $e++){
				if (!isset($this->xml_array[$e]['author'])) $this->xml_array[$e]['author'] = '';
				$this->xml_array[$e]['text'] = mysql_escape_string($this->xml_array[$e]['text']);
				$result = $this->wpdb->get_row("SELECT * FROM ".APHORISMUS_TABLE." WHERE text LIKE '".$this->xml_array[$e]['text']."';", 'ARRAY_A');
				if (count($result) == 0){
					$this->xml_array[$e]['text'] = aphorismus_strip_tags(stripslashes(trim($this->xml_array[$e]['text'])));
					$this->xml_array[$e]['author'] = strip_tags(stripslashes(trim($this->xml_array[$e]['author'])));
					$this->xml_array[$e]['text'] = str_replace(array("\r", "\n"), array("", " "), $this->xml_array[$e]['text']);
					$this->wpdb->insert(APHORISMUS_TABLE, array('text' => $this->xml_array[$e]['text'], 'author' => $this->xml_array[$e]['author']), array('%s', '%s'));
					$this->import_count++;
					}				
				}
			}
		return true;
		}

	function aphorismus_importer_parser($data = ''){
		if (empty($data)) $data = $this->data;
		$xml_parser = xml_parser_create();
		xml_parse_into_struct($xml_parser, $data, $values, $index);
		xml_parser_free($xml_parser);
		$count = 0;
		$xml_array = array();
		foreach ($values as $xml_elements){
			$value = '';
			if ($xml_elements['tag'] == 'TEXT'){
				$xml_array[$count]['text'] = $xml_elements['value'];
				$count++;
				}
			elseif ($xml_elements['tag'] == 'AUTHOR'){
				$count--;
				$xml_array[$count]['author'] = $xml_elements['value'];
				$count++;
				}
			}
		return $xml_array;
		}

	function aphorismus_importer_count(){
		return $this->import_count;
		}
	}
?>