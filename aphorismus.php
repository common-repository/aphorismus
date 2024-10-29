<?php
/*
Plugin Name: Aphorismus
Plugin URI: mailto:ovsyannikov.ivan@gmail.com
Description: The plug-in allows to deduce the random text block (aphorism) on pages, posts or sidebar. Plugin have the widget.
Version: 1.2.0
Author: Ivan Ovsyannikov
Author URI: mailto:ovsyannikov.ivan@gmail.com
*/

if (!defined('PLUGIN_NAME_APHORISMUS')) define('PLUGIN_NAME_APHORISMUS', 'aphorismus');

$php_self = $_SERVER['PHP_SELF'];
if (empty($php_self)) $_SERVER['PHP_SELF'] = $php_self = preg_replace("/(\?.*)?$/",'',$_SERVER["REQUEST_URI"]);
if (strpos($php_self, "wp-content/plugins/" . PLUGIN_NAME_APHORISMUS) !== false){
	$dir = str_replace('\\', '/', dirname(__FILE__));
	$dir = str_replace(array('wp-content/plugins/' . PLUGIN_NAME_APHORISMUS, 'aphorismus.php'), array('', ''), $dir);
	chdir($dir);
	require_once('./wp-load.php');
	define('APHORISMUS_TABLE', $wpdb->prefix . 'aphorismus');
	aphorismus_options();
	if (isset($_GET['type'])){
		if ($_GET['type'] == 'js'){
			header('Content-Type: application/javascript; charset=' . get_option('blog_charset'), true);
			?>
/* JavaScript */
var text = '<?php echo str_replace("'", "\'", get_aphorismus()); ?>';
document.write(text);
			<?php
			}
		elseif ($_GET['type'] == 'xml'){
			require_once(dirname(__FILE__).'/includes/export.php');
			aphorismus_export(true);
			}
		}
	else echo get_aphorismus();
	die;
	}
else {
	global $wpdb;
	define('APHORISMUS_TABLE', $wpdb->prefix . 'aphorismus');
	aphorismus_options();
	}
if (isset($_GET['action'])){
	if ($_GET['action'] == 'export'){
		require_once(dirname(__FILE__).'/includes/export.php');
		aphorismus_export();
		die();
		}
	}

register_activation_hook(__FILE__, 'aphorismus_setup' );
add_action('admin_menu', 'aphorismus_add_admin');
add_action('admin_head', 'aphorismus_admin_head');
if (aphorismus_get_options('aphorismus_admin_show') == 'true'){
	add_action('admin_footer', 'aphorismus_admin_footer');
	}
add_action('init', 'do_filter');
add_action('plugins_loaded', 'aphorismus_init');

/**
 * Add, load, update options
 *
 * @param bool $return
 * @return array
 */
function aphorismus_options($return = false, $options = array()){
	$new_options = array();
	$new_options['aphorismus_template'] = '<p class="aphorismus">%%text%%!<br /><i>%%author%%</i>!</p>';
	$new_options['aphorismus_template_widget'] = '%%text%%!<br /><i>%%author%%</i>!';
	$new_options['aphorismus_widget_title'] = '';
	$new_options['aphorismus_admin_per_page'] = '30';
	$new_options['aphorismus_admin_show'] = '';
	$new_options['aphorismus_admin_show_length'] = '200';
	$new_options['aphorismus_interval_show'] = 'always';
	$new_options['aphorismus_widget_padding'] = 0;
	$aphorismus_options = get_option('aphorismus_options');
	if (!is_array($aphorismus_options)){
		$aphorismus_options = &$new_options;
		update_option('aphorismus_options', $aphorismus_options);
		}
	if (count($aphorismus_options) != count($new_options)){
		$aphorismus_options = array_merge ($new_options, $aphorismus_options);
		update_option('aphorismus_options', $aphorismus_options);
		}
	if (count($options) > 0){
		$aphorismus_options = array_merge ($aphorismus_options, $options);
		update_option('aphorismus_options', $aphorismus_options);
		}
	$GLOBALS['aphorismus_options'] = $aphorismus_options;
	if ($return === true) return $aphorismus_options;
	}

/**
 * Return option
 *
 * @param string $string
 * @return string
 */
function aphorismus_get_options($string = ''){
	global $aphorismus_options;
	if (empty($string)) return false;
	if (!is_array($aphorismus_options)){
		$aphorismus_options = aphorismus_options(true);
		}
	if (!isset($aphorismus_options[$string])) return false;
	return $aphorismus_options[$string];
	}

/**
 * Enable locale
 *
 */
function load_locale(){
	$locale = get_locale();
	if (empty($locale)) $locale = 'en_US';
	$mofile = dirname( __FILE__ )."/locale/".$locale.".mo";
	load_textdomain(PLUGIN_NAME_APHORISMUS, $mofile );
	}

/**
 * Aphorismus setup
 *
 */
function aphorismus_setup(){
	global $wpdb, $charset_collate;
	$create_table = "CREATE TABLE ".APHORISMUS_TABLE." (id int(11) NOT NULL auto_increment, text longtext NOT NULL, author varchar(255), primary key (id) ) ENGINE=MyISAM " . $charset_collate .";";
	if($wpdb->get_var("SHOW TABLES LIKE '".APHORISMUS_TABLE."';") != APHORISMUS_TABLE){
		require_once(ABSPATH.'wp-admin/upgrade-functions.php');
		dbDelta($create_table);
		}
	}

function aphorismus_init(){
	load_locale();
	wp_register_sidebar_widget(PLUGIN_NAME_APHORISMUS, __('Aphorismus', PLUGIN_NAME_APHORISMUS), 'aphorismus_widget', array('description' => __('Use the output of aphorisms in the sidebar', PLUGIN_NAME_APHORISMUS)));
	wp_register_widget_control(PLUGIN_NAME_APHORISMUS, __('Aphorismus', PLUGIN_NAME_APHORISMUS), 'aphorismus_widget_control');
	}

/**
 * Add menu items to the admin panel
 *
 */
function aphorismus_add_admin(){
	$level = 'level_10';
	add_menu_page( __('Aphorismus', PLUGIN_NAME_APHORISMUS), __('Aphorismus', PLUGIN_NAME_APHORISMUS), $level, __FILE__, 'aphorismus_admin', get_option('siteurl').'/wp-content/plugins/'.PLUGIN_NAME_APHORISMUS.'/aphorismus_icon_small.png');
	add_submenu_page(__FILE__, __('Aphorismus', PLUGIN_NAME_APHORISMUS), __('Add aphorism', PLUGIN_NAME_APHORISMUS), $level, 'aphorismus_action', 'aphorismus_action');
	add_submenu_page(__FILE__, __('Aphorismus', PLUGIN_NAME_APHORISMUS), __('Settings', PLUGIN_NAME_APHORISMUS), $level, 'aphorismus_settings', 'aphorismus_settings');
	add_submenu_page(__FILE__, __('Aphorismus', PLUGIN_NAME_APHORISMUS), __('Help', PLUGIN_NAME_APHORISMUS), $level, 'aphorismus_help', 'aphorismus_help');
	}

/**
 * Add WP-filter
 *
 */
function do_filter(){
	add_filter('the_content', 'aphorismus_filter', 1);
	}

/**
 * Post or Page filter
 *
 * @param string $post
 * @return string
 */
function aphorismus_filter($post) {
	if (substr_count($post, '%%aphorismus%%') > 0){
		$post = str_replace('%%aphorismus%%', get_aphorismus(), $post);
		}
	return $post;
	}

/**
 * Show widget
 *
 * @param array $args
 */
function aphorismus_widget($args){
	extract($args);
	echo $before_widget;
	$title = aphorismus_get_options('aphorismus_widget_title');
	if (empty($title)) $title = __('Aphorismus', PLUGIN_NAME_APHORISMUS);
	echo $before_title . $title . $after_title;
	echo "<ul><li style=\"margin: 0; padding: ".aphorismus_get_options('aphorismus_widget_padding')."px\">" . get_aphorismus('widget') . "</li></ul>";
	echo $after_widget;
	}

/**
 * Edit widget parameters
 *
 */
function aphorismus_widget_control(){
	if (isset($_POST['aphorismus_template_submit']) && $_POST['aphorismus_template_submit'] == 'submit'){
		aphorismus_options(false, array(
		'aphorismus_template_widget' => stripslashes(trim($_POST['aphorismus_template'])),
		'aphorismus_widget_title' => stripslashes(trim($_POST['aphorismus_widget_title'])),
		'aphorismus_widget_padding' => trim($_POST['aphorismus_widget_padding'])
		));
		}
	echo "<p>\n";
	echo "<lable for=\"aphorismus_widget_title\">" . __('Widget title', PLUGIN_NAME_APHORISMUS) . ":</lable>\n";
	echo "<input id=\"aphorismus_widget_title\" type=\"text\" name=\"aphorismus_widget_title\" value=\"".aphorismus_get_options('aphorismus_widget_title')."\" style=\"width: 100%;\" />\n";
	echo "</p>\n";
	echo "<p>\n";
	echo "<lable for=\"aphorismus_widget_padding\">" . __('Space from edge of the sidebar', PLUGIN_NAME_APHORISMUS) . ":</lable>\n";
	echo "<input id=\"aphorismus_widget_padding\" type=\"text\" name=\"aphorismus_widget_padding\" value=\"".aphorismus_get_options('aphorismus_widget_padding')."\" maxlength=\"2\" style=\"width: 50px;\" />&nbsp;<small>" . __('pixels', PLUGIN_NAME_APHORISMUS) . "</small>\n";
	echo "</p>\n";
	echo "<p>\n";
	echo "<lable for=\"aphorismus_template\">" . __('Template string', PLUGIN_NAME_APHORISMUS) . ":</lable>\n";
	echo "<textarea rows=\"4\" id=\"aphorismus_template\" name=\"aphorismus_template\" style=\"width: 100%;\">".htmlspecialchars(aphorismus_get_options('aphorismus_template_widget'))."</textarea><br />\n";
	echo "<small>" . __('You can use HTML&ndash;tags. Constants:<br /><code>%%title%%</code> &mdash; Deduces plug-in heading<br /><code>%%text%%</code> &mdash; Deduces the aphorism text<br /><code>%%author%%</code> &mdash; Deduces the author', PLUGIN_NAME_APHORISMUS) . "</small>\n";
	echo "<input type=\"hidden\" id=\"aphorismus_template_submit\" name=\"aphorismus_template_submit\" value=\"submit\" />\n";
	echo "</p>\n";
	}

/**
 * Strip HTML-tags without:
 * <br />
 * <i></i>
 * <b></b>
 * <strong></strong>
 *
 * @param string $string
 * @return string
 */
function aphorismus_strip_tags($string = ''){
	if (empty($string)) return false;
	$tags_array = array('<br/>' => '&lt;br /&gt;', '<br />' => '&lt;br /&gt;', '<i>' => '&lt;i&gt;', '</i>' => '&lt;/i&gt;', '<b>' => '&lt;b&gt;', '</b>' => '&lt;/b&gt;', '<strong>' => '&lt;strong&gt;', '</strong>' => '&lt;/strong&gt;');
	return htmlspecialchars_decode(strip_tags(strtr($string, $tags_array)), ENT_NOQUOTES);
	}

/**
 * Echo aphorism
 *
 * @param string $to
 * @param int $max_length
 * @echo string or return false
 */
function aphorismus($to = '', $max_length = 0, $tags = true){
	if ($to == 'array') return false;
	echo get_aphorismus($to, $max_length, $tags);
	}

/**
 * Return aphorism string
 *
 * @param string $to
 * @param int $max_length
 * @param bool $tags
 * @return string
 */
function get_aphorismus($to = '', $max_length = 0, $tags = true){
	global $wpdb;
	$text = '';
	$where = '';
	if ($max_length > 0) $where .= "WHERE LENGTH(text) < " . $max_length;
	switch(aphorismus_get_options('aphorismus_interval_show')){
		case 'hour':
			$interval = date("YzH");
			break;
		case 'day':
			$interval = date("Yz");
			break;
		default:
			$interval = '';
			break;
		}
	$result = $wpdb->get_results("SELECT id, text, author FROM ".APHORISMUS_TABLE." ".$where." ORDER BY RAND(".$interval.") LIMIT 1", 'ARRAY_A');
	if (empty($result)){
		return null;
		}
	switch($to){
		case 'array':
			$text = array('id' => $result[0]['id'], 'text' => $result[0]['text'], 'author' => $result[0]['author']);
			break;
		case 'notemplate':
			$text .= $result[0]['text'];
			break;
		case 'widget':
			$text = aphorismus_get_options('aphorismus_template_widget');
			$text = str_replace(array('%%text%%', '%%title%%'), array($result[0]['text'], __('Aphorismus', PLUGIN_NAME_APHORISMUS)), $text);
			if (!empty($result[0]['author'])) $text = str_replace("%%author%%", $result[0]['author'], $text);
			else $text = str_replace("%%author%%", "", $text);
			break;
		default:
			$text = aphorismus_get_options('aphorismus_template');
			$text = str_replace(array('%%text%%', '%%title%%'), array($result[0]['text'], __('Aphorismus', PLUGIN_NAME_APHORISMUS)), $text);
			if (!empty($result[0]['author'])) $text = str_replace("%%author%%", $result[0]['author'], $text);
			else $text = str_replace("%%author%%", "", $text);
			break;
		}
	if ($tags == false) return strip_tags($text);
	else return $text;
	}

/**
 * Aphorismus control
 *
 */
function aphorismus_admin(){
	global $wpdb;
	$where = '';
	$search_string = '';
	$action = '';
	$pagenum = isset( $_GET['pagenum'] ) ? absint( $_GET['pagenum'] ) : 0;
	if (empty($pagenum)) $pagenum = 1;
	$per_page = aphorismus_get_options('aphorismus_admin_per_page');
	if (isset($_GET['action']) || isset($_GET['action2'])){
		if ($_GET['action'] == '-1' || !isset($_GET['action'])) $action = $_GET['action2'];
		else $action = $_GET['action'];
		if (isset($_POST['aphorism_text'])){
			$aphorism = aphorismus_strip_tags(stripslashes(trim($_POST['aphorism_text'])));
			$aphorism = str_replace(array("\r", "\n"), array("", " "), $aphorism);
			}
		else $aphorism = '';
		if (isset($_POST['aphorism_author'])){
			$author = strip_tags(stripslashes(trim($_POST['aphorism_author'])));
			}
		else $author = '';
		switch ($action){
			// Adding aphorism
			case 'add':
				if (!empty($aphorism)){
					$wpdb->insert(APHORISMUS_TABLE, array('text' => $aphorism, 'author' => $author), array('%s', '%s'));
					echo "<div id=\"message\" class=\"updated fade\"><p>" . __('Aphorism added', PLUGIN_NAME_APHORISMUS) . "</p></div>\n";
					}
				break;
			// Deleting aphorism
			case 'delete':
				$count = count($_GET['aphorism']);
				if ($count > 0){
					$aphorisms = $_GET['aphorism'];
					for ($p = 0; $p < $count; $p++){
						$wpdb->query("DELETE FROM ".APHORISMUS_TABLE." WHERE id = '".$aphorisms[$p]."';");
						}
					if ($count == 1) echo "<div id=\"message\" class=\"updated fade\"><p>" . __('Aphorism deleted', PLUGIN_NAME_APHORISMUS) . "</p></div>\n";
					else echo "<div id=\"message\" class=\"updated fade\"><p>" . __('Aphorisms deleted', PLUGIN_NAME_APHORISMUS) . "</p></div>\n";
					}
				break;
			// Deleting all aphorisms
			case 'delete_all':
				$wpdb->query("TRUNCATE TABLE ".APHORISMUS_TABLE.";");
				echo "<div id=\"message\" class=\"updated fade\"><p>" . __('All aphorisms deleted', PLUGIN_NAME_APHORISMUS) . "</p></div>\n";
				break;
			// Save settings
			case 'settings':
				$template = stripslashes(trim($_POST['template']));
				if (isset($_POST['admin_show'])) $admin_show = $_POST['admin_show'];
				else $admin_show = '';
				aphorismus_options(false, array(
				'aphorismus_template' => $template,
				'aphorismus_interval_show' => $_POST['interval_show'],
				'aphorismus_admin_per_page' => trim($_POST['admin_per_page']),
				'aphorismus_admin_show_length' => trim($_POST['admin_show_length']),
				'aphorismus_admin_show' => $admin_show
				));
				$per_page = trim($_POST['admin_per_page']);
				echo "<div id=\"message\" class=\"updated fade\"><p>" . __('Settings saved', PLUGIN_NAME_APHORISMUS) . "</p></div>\n";
				break;
			// Updating aphorism
			case 'update':
				if (!empty($aphorism)){
					$id = $_POST['id'];
					$wpdb->update(APHORISMUS_TABLE, array('text' => $aphorism, 'author' => $author), array('id' => $id), array('%s', '%s'), array('%d'));
					echo "<div id=\"message\" class=\"updated fade\"><p>" . __('Aphorism saved', PLUGIN_NAME_APHORISMUS) . "</p></div>\n";
					}
				break;
			// Load 'Edit' form
			case 'edit':
				aphorismus_action($_GET['id']);
				break;
			// Importing aphorisms from file
			case 'import':
				if (isset($_FILES['xmlfile']['tmp_name'])){
					$upload_dir = wp_upload_dir();
					$upload_path = $upload_dir['basedir'];
					if(strpos($upload_path, ABSPATH) === false){
						$upload_path = ABSPATH . $upload_path;
						}
					$upload_file = $upload_path . basename($_FILES['xmlfile']['name']);
					@move_uploaded_file($_FILES['xmlfile']['tmp_name'], $upload_file);
					require_once(dirname(__FILE__).'/includes/import.php');
					$aphorismus_importer = new aphorismus_importer($wpdb);
					if ($aphorismus_importer->aphorismus_importer_file($upload_file)){
						if ($aphorismus_importer->aphorismus_importer_result()){
							echo "<div id=\"message\" class=\"updated fade\"><p>" . sprintf( __('Imported aphorisms: %d', PLUGIN_NAME_APHORISMUS), $aphorismus_importer->aphorismus_importer_count()) . "</p></div>\n";
							}
						}
					@unlink($upload_file);
					}
				break;
			// Importing aphorisms from other site 
			case 'import_http':
				$xmlurl = $_POST['xmlurl'];
				if (strpos($xmlurl, PLUGIN_NAME_APHORISMUS.'/aphorismus.php?type=xml') !== false){
					$xmldata = wp_remote_get($xmlurl);
					if (is_array($xmldata)){
						$xmldata = $xmldata['body'];
						require_once(dirname(__FILE__).'/includes/import.php');
						$aphorismus_importer = new aphorismus_importer($wpdb);
						$aphorismus_importer->aphorismus_importer_result($xmldata);
						echo "<div id=\"message\" class=\"updated fade\"><p>" . sprintf( __('Imported aphorisms: %d', PLUGIN_NAME_APHORISMUS), $aphorismus_importer->aphorismus_importer_count()) . "</p></div>\n";
						}
					}
				break;
			}
		}
	if (isset($_GET['s'])){
		if (!empty($_GET['s'])){
			$search_string = mysql_escape_string(strip_tags(trim($_GET['s'])));
			$where .= "WHERE text LIKE '%".$search_string."%'";
			}
		}
	if ($action != 'edit'){
		$aphorisms_count = $wpdb->get_var("SELECT count(*) result FROM ".APHORISMUS_TABLE." ".$where.";");
		echo "<div class=\"wrap\">\n";
		echo "<div class=\"icon32\" style=\"background: transparent url(".get_option('siteurl')."/wp-content/plugins/".PLUGIN_NAME_APHORISMUS."/aphorismus_icon.png) no-repeat;\"><br /></div>\n";
		echo "<h2>" . __('Aphorismus', PLUGIN_NAME_APHORISMUS) . "";
		if (!empty($search_string)) echo "<span class=\"subtitle\">" . __('Search result') . " &laquo;".$search_string."&raquo;</span>";
		echo "<br /></h2>\n";
		echo "<form name=\"posts-filter\" id=\"posts-filter\" action=\"\" method=\"get\">\n";
		echo "<input type=\"hidden\" name=\"page\" value=\"".PLUGIN_NAME_APHORISMUS."/aphorismus.php\" />\n";
		echo "<p class=\"search-box\">\n";
		echo "<label class=\"hidden\" for=\"aphorismus-search-input\">" . __('Search', PLUGIN_NAME_APHORISMUS) . ":</label>\n";
		echo "<input type=\"text\" class=\"search-input\" id=\"aphorismus-search-input\" name=\"s\" value=\"".$search_string."\" maxlength=\"150\" />\n";
		echo "<input type=\"submit\" value=\"" . __('Search', PLUGIN_NAME_APHORISMUS) . "\" class=\"button\" />\n";
		echo "</p>\n";
		echo "<div class=\"tablenav\">\n";
		if ($aphorisms_count > $per_page){
			$num_pages = ceil($aphorisms_count / $per_page);
			$links_arr = array('base' => get_option('siteurl').'/wp-admin/admin.php?page='.PLUGIN_NAME_APHORISMUS.'/aphorismus.php%_%', 'format' => '&amp;pagenum=%#%', 'prev_text' => __('&laquo;'), 'next_text' => __('&raquo;'), 'total' => $num_pages, 'current' => $pagenum);
			$page_links = paginate_links($links_arr);
			$page_links_text = sprintf( '<span class="displaying-num">' . __('Displaying %s&#8211;%s of %s', PLUGIN_NAME_APHORISMUS) . '</span>%s', number_format_i18n(($pagenum - 1) * $per_page + 1), number_format_i18n(min($pagenum * $per_page, $aphorisms_count)), number_format_i18n($aphorisms_count), $page_links);
			echo "<div class=\"tablenav-pages\">\n";
			echo $page_links_text;
			echo "</div>\n";
			}
		echo "<div class=\"alignleft actions\">\n";
		echo "<select name=\"action\">\n";
		echo "<option value=\"-1\" selected=\"selected\">- " . __('Actions', PLUGIN_NAME_APHORISMUS) . " -</option>\n";
		echo "<option value=\"delete\">" . __('Delete', PLUGIN_NAME_APHORISMUS) . "</option>\n";
		echo "<option value=\"delete_all\">" . __('Delete all', PLUGIN_NAME_APHORISMUS) . "</option>\n";
		echo "</select>\n";
		echo "<input type=\"submit\" value=\"" . __('Apply', PLUGIN_NAME_APHORISMUS) . "\" name=\"doaction\" id=\"doaction\" class=\"button-secondary action\" onclick=\"if (document.forms['posts-filter'].elements['action'].value == 'delete_all'){ if (!confirm('" . __("You are about to delete ALL aphorisms\\n\'Cancel\' to stop, \'OK\' to delete.", PLUGIN_NAME_APHORISMUS) . "')) return false; };\" />&nbsp;<input type=\"button\" name=\"export\" class=\"button\" value=\"" . __('Export', PLUGIN_NAME_APHORISMUS) . "\" onclick=\"document.location.href='".get_option('siteurl')."/wp-admin/admin.php?page=".PLUGIN_NAME_APHORISMUS."/aphorismus.php&amp;action=export';\" />\n";
		echo "<br class=\"clear\" />\n";
		echo "</div>\n";
		echo "</div>\n";
		echo "<table class=\"widefat page fixed\" cellspacing=\"0\">\n";
		echo "<thead>\n";
		echo "<tr>\n";
		echo "<th scope=\"col\" id=\"cb\" class=\"manage-column column-cb check-column\" style=\"\"><input type=\"checkbox\" /></th>\n";
		echo "<th scope=\"col\" id=\"title\" class=\"manage-column column-title\" style=\"\">" . __('Aphorism text', PLUGIN_NAME_APHORISMUS) . "</th>\n";
		echo "<th scope=\"col\" id=\"author\" class=\"manage-column column-author\" style=\"\">" . __('Author', PLUGIN_NAME_APHORISMUS) . "</th>\n";
		echo "</tr>\n";
		echo "</thead>\n";
		echo "<tfoot>\n";
		echo "<tr>\n";
		echo "<th scope=\"col\" id=\"cb\" class=\"manage-column column-cb check-column\" style=\"\"><input type=\"checkbox\" /></th>\n";
		echo "<th scope=\"col\" id=\"title\" class=\"manage-column column-title\" style=\"\">" . __('Aphorism text', PLUGIN_NAME_APHORISMUS) . "</th>\n";
		echo "<th scope=\"col\" id=\"author\" class=\"manage-column column-author\" style=\"\">" . __('Author', PLUGIN_NAME_APHORISMUS) . "</th>\n";
		echo "</tr>\n";
		echo "</tfoot>\n";
		echo "<tbody>\n";
		$aphorisms = $wpdb->get_results("SELECT id, text, author FROM ".APHORISMUS_TABLE." ".$where." ORDER BY text LIMIT ".($per_page * ($pagenum - 1)).",".$per_page.";", 'ARRAY_A');
		if (!empty($aphorisms)){
			foreach ($aphorisms as $aphorism){
				echo "<tr>\n";
				echo "<th scope=\"row\" class=\"check-column\"><input type=\"checkbox\" name=\"aphorism[]\" value=\"" . $aphorism['id'] . "\" /></th>\n";
				echo "<td><a href=\"".get_option('siteurl')."/wp-admin/admin.php?page=".PLUGIN_NAME_APHORISMUS."/aphorismus.php&amp;action=edit&amp;id=".$aphorism['id']."\">" . $aphorism['text'] . "</a></td>\n";
				echo "<td>" . $aphorism['author'] . "</td>\n";
				echo "</tr>\n";
				}
			}
		echo "</tbody>\n";
		echo "</table>\n";
		echo "<div class=\"tablenav\">\n";
		if ($aphorisms_count > $per_page){
			echo "<div class=\"tablenav-pages\">\n";
			echo $page_links_text;
			echo "</div>\n";
			}
		echo "<div class=\"alignleft actions\">\n";
		echo "<select name=\"action2\">\n";
		echo "<option value=\"-1\" selected=\"selected\">- " . __('Actions', PLUGIN_NAME_APHORISMUS) . " -</option>\n";
		echo "<option value=\"delete\">" . __('Delete', PLUGIN_NAME_APHORISMUS) . "</option>\n";
		echo "<option value=\"delete_all\">" . __('Delete all', PLUGIN_NAME_APHORISMUS) . "</option>\n";
		echo "</select>\n";
		echo "<input type=\"submit\" value=\"" . __('Apply', PLUGIN_NAME_APHORISMUS) . "\" name=\"doaction\" id=\"doaction\" class=\"button-secondary action\" onclick=\"if (document.forms['posts-filter'].elements['action2'].value == 'delete_all'){ if (!confirm('" . __("You are about to delete ALL aphorisms\\n\'Cancel\' to stop, \'OK\' to delete.", PLUGIN_NAME_APHORISMUS) . "')) return false; };\" />&nbsp;<input type=\"button\" name=\"export\" class=\"button\" value=\"" . __('Export', PLUGIN_NAME_APHORISMUS) . "\" onclick=\"document.location.href='".get_option('siteurl')."/wp-admin/admin.php?page=".PLUGIN_NAME_APHORISMUS."/aphorismus.php&amp;action=export';\" />\n";
		echo "<br class=\"clear\" />\n";
		echo "</div>\n";
		echo "</div>\n";
		echo "</form>\n";
		echo "<p>". __('The link allowing other Aphorismus to import your aphorisms', PLUGIN_NAME_APHORISMUS) . ":<br /><a href=\"".get_option('siteurl')."/wp-content/plugins/".PLUGIN_NAME_APHORISMUS."/aphorismus.php?type=xml\" target=\"_blank\">".get_option('siteurl')."/wp-content/plugins/".PLUGIN_NAME_APHORISMUS."/aphorismus.php?type=xml</a></p>\n";
		echo "</div>\n";
		}
	}

/**
 * Show aphorismus settings
 *
 */
function aphorismus_settings(){
	echo "<div class=\"wrap\">\n";
	echo "<div class=\"icon32\" style=\"background: transparent url(".get_option('siteurl')."/wp-content/plugins/".PLUGIN_NAME_APHORISMUS."/aphorismus_icon.png) no-repeat;\"><br /></div>\n";
	echo "<h2>" . __('Settings', PLUGIN_NAME_APHORISMUS) . "</h2>\n";
	echo "<form name=\"form\" method=\"post\" action=\"".get_option('siteurl')."/wp-admin/admin.php?page=".PLUGIN_NAME_APHORISMUS."/aphorismus.php&amp;action=settings\" style=\"margin-top: 20px;\">\n";
	echo "<table class=\"form-table\">\n";
	echo "<tbody>\n";
	echo "<tr valign=\"top\">\n";
	echo "<th class=\"no_border\">" . __('Template string', PLUGIN_NAME_APHORISMUS) . "</th>\n";
	echo "<td class=\"no_border\"><textarea name=\"template\" rows=\"5\" style=\"width: 99%;\">".htmlspecialchars(aphorismus_get_options('aphorismus_template'))."</textarea></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<th>&nbsp;</th>\n";
	echo "<td><p class=\"description\" style=\"padding: 0px 0px 20px 2px; margin: 0px;\">" . __('You can use HTML&ndash;tags. Constants:<br /><code>%%title%%</code> &mdash; Deduces plug-in heading<br /><code>%%text%%</code> &mdash; Deduces the aphorism text<br /><code>%%author%%</code> &mdash; Deduces the author', PLUGIN_NAME_APHORISMUS) . "</p></td\n";
	echo "</tr>\n";
	echo "<tr valign=\"top\">\n";
	echo "<th>" . __('The updating period', PLUGIN_NAME_APHORISMUS) . "</th>\n";
	echo "<td>\n";
	echo "<label><input type=\"radio\" name=\"interval_show\" value=\"always\" ";
	if (aphorismus_get_options('aphorismus_interval_show') == 'always') echo "checked=\"checked\"";
	echo " />&nbsp;" . __('at each loading', PLUGIN_NAME_APHORISMUS) . "</label><br />\n";
	echo "<label><input type=\"radio\" name=\"interval_show\" value=\"hour\" ";
	if (aphorismus_get_options('aphorismus_interval_show') == 'hour') echo "checked=\"checked\"";
	echo " />&nbsp;" . __('time at an o\'clock', PLUGIN_NAME_APHORISMUS) . "</label><br />\n";
	echo "<label><input type=\"radio\" name=\"interval_show\" value=\"day\" ";
	if (aphorismus_get_options('aphorismus_interval_show') == 'day') echo "checked=\"checked\"";
	echo " />&nbsp;" . __('time a day', PLUGIN_NAME_APHORISMUS) . "</label>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<th>" . __('Count of aphorisms on page', PLUGIN_NAME_APHORISMUS) . "</th>\n";
	echo "<td><input type=\"text\" name=\"admin_per_page\" maxlength=\"3\" value=\"".aphorismus_get_options('aphorismus_admin_per_page')."\" style=\"width: 50px;\" />&nbsp;<span class=\"description\">" . __('in the admin panel', PLUGIN_NAME_APHORISMUS) . "</span></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<th>" . __('Show aphorisms in your admin screen', PLUGIN_NAME_APHORISMUS) . "</th>\n";
	if (aphorismus_get_options('aphorismus_admin_show') == 'true') $checked = 'checked="checked"';
	else $checked = '';
	echo "<td><input type=\"checkbox\" name=\"admin_show\" value=\"true\" ".$checked." onclick=\"hide_max_length();\"/>&nbsp;<span class=\"description\">" . __('The plugin "Hello Dolly" should be switched off', PLUGIN_NAME_APHORISMUS) . "</span></td>\n";
	echo "</tr>\n";
	echo "<tr id=\"admin_max_length\">\n";
	echo "<th>" . __('...Max length', PLUGIN_NAME_APHORISMUS) . "</th>\n";
	echo "<td><input type=\"text\" name=\"admin_show_length\" maxlength=\"3\" value=\"".aphorismus_get_options('aphorismus_admin_show_length')."\" style=\"width: 50px;\" />&nbsp;<span class=\"description\">" . __('symbols', PLUGIN_NAME_APHORISMUS) . "</span></td>\n";
	echo "</tr>\n";
	echo "</tbody>\n";
	echo "</table>\n";
	echo "<p class=\"submit\" /><input type=\"submit\" value=\"" . __('Save', PLUGIN_NAME_APHORISMUS) . "\" name=\"doaction\" id=\"doaction\" accesskey=\"a\" class=\"button-primary action\" /></p>\n";
	echo "</form>\n";
	?>
	<script type="text/javascript">
	<!--
	if (document.forms['form'].elements['admin_show'].checked == false) document.getElementById('admin_max_length').style.display = 'none';
	else document.getElementById('admin_max_length').style.display = '';
	function hide_max_length(){
		if (document.forms['form'].elements['admin_show'].checked == false) document.getElementById('admin_max_length').style.display = 'none';
		else document.getElementById('admin_max_length').style.display = '';
		}
	//-->
	</script>
	<?php
	}

/**
 * Add or Edit aphorism
 *
 * @param int $id
 */
function aphorismus_action($id = 0){
	global $wpdb;
	$text = '';
	$author = '';
	if (isset($_GET['id']) && $id == 0) $id = $_GET['id'];
	if ($id > 0){
		$aphorism = $wpdb->get_results("SELECT id, text, author FROM ".APHORISMUS_TABLE." WHERE id = '".$id."';", 'ARRAY_A');
		$text = $aphorism[0]['text'];
		$author = $aphorism[0]['author'];
		}
	echo "<div class=\"wrap\">\n";
	echo "<div class=\"icon32\" style=\"background: transparent url(".get_option('siteurl')."/wp-content/plugins/".PLUGIN_NAME_APHORISMUS."/aphorismus_icon.png) no-repeat;\"><br /></div>\n";
	if ($id > 0) echo "<h2>" . __('Editing aphorism', PLUGIN_NAME_APHORISMUS) . "</h2>\n";
	else echo "<h2>" . __('Adding aphorism', PLUGIN_NAME_APHORISMUS) . "</h2>\n";
	if ($id == 0){
		echo "<div class=\"clear\" style=\"padding-bottom: 30px; display: block;\">\n";
		echo "<ul class=\"subsubsub\" >\n";
		echo "<li><a href=\"".get_option('siteurl')."/wp-admin/admin.php?page=aphorismus_action\"";
		if (!isset($_GET['form_import']) && !isset($_GET['form_http'])) echo " class=\"current\"";
		echo">" . __('Form', PLUGIN_NAME_APHORISMUS) . "</a> |</li>\n";
		echo "<li><a href=\"".get_option('siteurl')."/wp-admin/admin.php?page=aphorismus_action&amp;form_import=true\"";
		if (isset($_GET['form_import'])) echo " class=\"current\"";
		echo ">" . __('Import from file', PLUGIN_NAME_APHORISMUS) . "</a> |</li>\n";
		echo "<li><a href=\"".get_option('siteurl')."/wp-admin/admin.php?page=aphorismus_action&amp;form_http=true\"";
		if (isset($_GET['form_http'])) echo " class=\"current\"";
		echo ">" . __('Import from other site', PLUGIN_NAME_APHORISMUS) . "</a> </li>\n";
		echo "</ul>\n";
		echo "</div>\n";
		}
	if (isset($_GET['form_import'])){
		echo "<form name=\"form\" method=\"post\" enctype=\"multipart/form-data\" action=\"".get_option('siteurl')."/wp-admin/admin.php?page=".PLUGIN_NAME_APHORISMUS."/aphorismus.php&amp;";
		echo "action=import";
		echo "\" style=\"margin-top: 20px;\">\n";
		echo __('XML&ndash;file', PLUGIN_NAME_APHORISMUS) . " <input name=\"xmlfile\" type=\"file\" />&nbsp;<input type=\"submit\" value=\"" . __('Import', PLUGIN_NAME_APHORISMUS) . "\" name=\"doaction\" id=\"doaction\" class=\"button action\" />\n";
		echo "</form>\n";
		}
	elseif (isset($_GET['form_http'])){
		echo "<form name=\"form\" method=\"post\" action=\"".get_option('siteurl')."/wp-admin/admin.php?page=".PLUGIN_NAME_APHORISMUS."/aphorismus.php&amp;";
		echo "action=import_http";
		echo "\" style=\"margin-top: 20px;\">\n";
		echo __('Link to XML', PLUGIN_NAME_APHORISMUS) . " <input name=\"xmlurl\" type=\"text\" style=\"width: 50%;\" />&nbsp;<input type=\"submit\" value=\"" . __('Import', PLUGIN_NAME_APHORISMUS) . "\" name=\"doaction\" id=\"doaction\" class=\"button action\" />\n";
		echo "</form>\n";
		}
	else {
		echo "<form name=\"form\" method=\"post\" action=\"".get_option('siteurl')."/wp-admin/admin.php?page=".PLUGIN_NAME_APHORISMUS."/aphorismus.php&amp;";
		if ($id > 0) echo "action=update";
		else echo "action=add";
		echo "\" style=\"margin-top: 20px;\">\n";
		echo "<dl>\n";
		echo "<dt>" . __('Aphorism text', PLUGIN_NAME_APHORISMUS) . "</dt>\n";
		echo "<dd><p class=\"description\">" . __('You can use following tags:', PLUGIN_NAME_APHORISMUS) . "<br />&lt;br&nbsp;/&gt;, &lt;i&gt;, &lt;b&gt;, &lt;strong&gt;</p><textarea name=\"aphorism_text\" rows=\"6\" style=\"width: 400px;\">".$text."</textarea></dd>\n";
		echo "<dt>" . __('Author', PLUGIN_NAME_APHORISMUS) . "</dt>\n";
		echo "<dd><input type=\"text\" name=\"aphorism_author\" value=\"".$author."\" maxlength=\"255\" style=\"width: 400px;\" /></dd>\n";
		echo "</dl>\n";
		echo "<br class=\"clear\" />\n";
		echo "<input type=\"submit\" value=\"";
		if ($id > 0) _e ('Save', PLUGIN_NAME_APHORISMUS);
		else _e ('Add', PLUGIN_NAME_APHORISMUS);
		echo "\" name=\"doaction\" id=\"doaction\" accesskey=\"a\" class=\"button-primary action\" />\n";
		if ($id > 0) echo "<input type=\"hidden\" name=\"id\" value=\"".$id."\" />\n";
		echo "</form>\n";
		}
	echo "</div>\n";
	}

/**
 * Show help
 *
 */
function aphorismus_help(){
	echo "<div class=\"wrap\">\n";
	echo "<div class=\"icon32\" style=\"background: transparent url(".get_option('siteurl')."/wp-content/plugins/" . PLUGIN_NAME_APHORISMUS . "/aphorismus_icon.png) no-repeat;\"><br /></div>\n";
	echo "<h2>" . __('Help', PLUGIN_NAME_APHORISMUS) . "</h2>\n";
	echo "<h3>" . __('Addition of aphorisms', PLUGIN_NAME_APHORISMUS) . "</h3>\n";
	echo sprintf( __('<p>You can use %s for XML-file creation. Open &laquo;aphorismus.xls&raquo; in MS Excel 2003 SP3 (or higher), fill the table, press &laquo;Save as&raquo; and will choose XML-data (not a table XML).</p>', PLUGIN_NAME_APHORISMUS), '&laquo;<a href="'.get_option('siteurl') . '/wp-content/plugins/' . PLUGIN_NAME_APHORISMUS . '/aphorismus.xls">aphorismus.xls</a>&raquo;') . "\n";
	echo __('<p>Attention! Try to avoid a symbol &laquo;&&raquo;! :)</p>', PLUGIN_NAME_APHORISMUS) . "\n";
	echo "<h3>" . __('Installation', PLUGIN_NAME_APHORISMUS) . "</h3>\n";
	echo __('<p>The Aphorismus shows aphorisms on pages, in posts or on the sidebar. You can use</p>', PLUGIN_NAME_APHORISMUS) . "\n";
	echo "<p class=\"tt\"><b>%%aphorismus%%</b></p>\n";
	echo __('<p>for display in posts or on pages, or to use</p>', PLUGIN_NAME_APHORISMUS) . "\n";
	echo "<p class=\"tt\"><b>&lt;?php if (function_exists('aphorismus')) aphorismus(); ?&gt;</b></p>\n";
	echo __('<p>in templates.</p><p>At plugin activation, he automatically adds new widget. If in a template of your blog there is a sidebar, you can place on it widget. To make it it is possible in the management widget menu.</p>', PLUGIN_NAME_APHORISMUS) . "\n";
	echo __('<p>You can deduce aphorisms on other sites:</p>', PLUGIN_NAME_APHORISMUS) . "\n";
	echo "<p class=\"tt\"><b>&lt;script type=\"text/javascript\" src=\"" . get_option('siteurl') . "/wp-content/plugins/" . PLUGIN_NAME_APHORISMUS . "/aphorismus.php?type=js\"&gt;&lt;/script&gt;</b></p>\n";
	echo "<p>" . __('The link allowing other Aphorismus to import your aphorisms', PLUGIN_NAME_APHORISMUS) . ":</p>\n";
	echo "<p class=\"tt\"><a href=\"".get_option('siteurl')."/wp-content/plugins/".PLUGIN_NAME_APHORISMUS."/aphorismus.php?type=xml\" target=\"_blank\" style=\"font-weight: bold;\">".get_option('siteurl')."/wp-content/plugins/".PLUGIN_NAME_APHORISMUS."/aphorismus.php?type=xml</a></p>\n";
	echo "<h3 style=\"margin-top: 30px;\">" . __('Arguments', PLUGIN_NAME_APHORISMUS) . "</h3>\n";
	echo "<p class=\"tt\"><b>aphorismus</b> ( [ string <i>\$arg</i> ] [, int <i>\$max_length</i> ] [, bool <i>\$tags</i> ] )</p>\n";
	echo __('<p><b>aphorismus()</b> echo string.</p>', PLUGIN_NAME_APHORISMUS) . "\n";
	echo "<p class=\"tt\"><b>get_aphorismus</b> ( [ string <i>\$arg</i> ] [, int <i>\$max_length</i> ] [, bool <i>\$tags</i> ] )</p>\n";
	echo __('<p><b>get_aphorismus()</b> return string.</p>', PLUGIN_NAME_APHORISMUS) . "\n";
	echo __('<p><i>arg</i> values:</p><ul class="list"><li><i>widget</i> &mdash; string with a widget template</li><li><i>notemplate</i> &mdash; string without a template (and Author)</li><li><i>array</i> &mdash; return array [id], [text], [author] (not work with <i>aphorismus()</i>!)</li></ul>', PLUGIN_NAME_APHORISMUS) . "\n";
	echo __('<p>If the <i>arg</i> is empty or not set, function will deduce string with a output template.</p>', PLUGIN_NAME_APHORISMUS) . "\n";
	echo __('<p><i>max_length</i> defines the maximum length of a deduced aphorism. By default is <i>0</i>, thus the maximum length is not limited.</p>', PLUGIN_NAME_APHORISMUS) . "\n";
	echo __('<p><i>tags</i> resolve or forbid a conclusion of the text of an aphorism with tags. Default value is <i>true</i>. Value <i>false</i> forbids.</p>', PLUGIN_NAME_APHORISMUS) . "\n";
	echo "<br class=\"clear\" />\n";	
	echo "</div>\n";
	}

/**
 * Paste style to the admin_head
 *
 */
function aphorismus_admin_head(){
	?>
	<style type="text/css">
	.tt {border-left: solid 4px #e3e4e5; padding: 5px 0px 5px 10px; color: #636363; font-family: 'Lucida Console', 'Consolas', courier;}
	.form_table th.no_border, .form_table td.no_border {border: 0px;}
	#aphorismus_block {position: absolute; top: 5px; margin: 0; padding: 10px; right: 250px; width: 30%; background: #ffffff; -webkit-border-radius: 6px; -moz-border-radius: 6px; -o-border-radius: 6px; border-radius: 6px; box-shadow: 0 0 6px -2px #000000; opacity: 0.9;}
	#aphorismus_block p {font-size: 9pt; text-align: left; margin: 0px; padding: 0px;}
	ul.list {list-style: disc outside;}
	ul.list li {margin-left: 30px;}
	</style>
	<?php
	}

function aphorismus_admin_footer(){
	if (aphorismus_get_options('aphorismus_admin_show_length') > 0){
		echo "<div id=\"aphorismus_block\"><p>" . get_aphorismus('notemplate', aphorismus_get_options('aphorismus_admin_show_length')) . "</p></div>\n";
		}
	}
?>