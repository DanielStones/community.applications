<?PHP
###############################################################
#                                                             #
# Community Applications copyright 2015-2020, Andrew Zawadzki #
#                    All Rights Reserved                      #
#                                                             #
###############################################################

require_once "/usr/local/emhttp/plugins/community.applications/include/paths.php";
require_once "/usr/local/emhttp/plugins/community.applications/include/helpers.php";
require_once "/usr/local/emhttp/plugins/dynamix.plugin.manager/include/PluginHelpers.php";

switch ($_POST['action']) {
	case 'checkPlugin':
		$options = getPostArray("options");
		$plugin = $options['plugin'];

		if ( ! $plugin ) {
			echo json_encode(array("updateAvailable"=>false));
			return;
		}

		exec("mkdir -p /tmp/plugins");
		@unlink("/tmp/plugins/$plugin");
		$url = @plugin("pluginURL","/boot/config/plugins/$plugin");
		download_url($url,"/tmp/plugins/$plugin");

		$changes = @plugin("changes","/tmp/plugins/$plugin");
		$version = @plugin("version","/tmp/plugins/$plugin");
		$installedVersion = @plugin("version","/boot/config/plugins/$plugin");
		$min = @plugin("min","/tmp/plugins/$plugin") ?: "6.4.0";
		if ( $changes ) {
			file_put_contents("/tmp/plugins/".pathinfo($plugin, PATHINFO_FILENAME).".txt",$changes);
		} else {
			@unlink("/tmp/plugins/".pathinfo($plugin, PATHINFO_FILENAME).".txt");
		}

		$update = false;
		if ( strcmp($version,$installedVersion) > 0 ) {
			$unraid = parse_ini_file($caPaths['unRaidVersion']);
			$update = (version_compare($min,$unraid['version'],">")) ? false : true;
		}

		echo json_encode(array("updateAvailable" => $update,"version" => $version,"min"=>$min,"changes"=>$changes,"installedVersion"=>$installedVersion));
		break;
		
	case 'addRebootNotice':
		$message = getPost("message",null);
		if (!message) break;
		
		$existing = json_decode(@file_get_contents("/tmp/reboot_notifications"),true) ?: array();
		$existing[] = $message;
		file_put_contents("/tmp/reboot_notifications",json_encode(array_unique($existing),JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
		break;
}

?>