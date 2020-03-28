#!/usr/bin/php
<?PHP
###############################################################
#                                                             #
# Community Applications copyright 2015-2020, Andrew Zawadzki #
#          Licenced under the terms of GNU GPLv2              #
#                                                             #
###############################################################
$docroot = $docroot ?? $_SERVER['DOCUMENT_ROOT'] ?: "/usr/local/emhttp";
$unRaidSettings = parse_ini_file("/etc/unraid-version");
$translations = is_file("$docroot/plugins/dynamix/include/Translations.php");
if ( $translations )
	require_once("$docroot/plugins/dynamix/include/Translations.php");

require_once "/usr/local/emhttp/plugins/community.applications/include/helpers.php";
require_once "/usr/local/emhttp/plugins/community.applications/include/paths.php";
require_once "/usr/local/emhttp/plugins/dynamix.plugin.manager/include/PluginHelpers.php";
require_once "$docroot/plugins/dynamix/include/Wrappers.php";




$dynamix = parse_plugin_cfg("dynamix");

if ( $translations ) {
	$pluginTranslations = @parse_language("$docroot/languages/{$dynamix['locale']}/apps-1.txt");
	$genericTranslations = @parse_language("$docroot/languages/{$dynamix['locale']}/translations.txt");
	$language = array_merge(is_array($genericTranslations) ? $genericTranslations : [],is_array($pluginTranslations) ? $pluginTranslations : [] );
	if ( empty($pluginTranslations) ) 
		$translations = false;
}
function parse_language($file) {
  return array_filter(parse_ini_string(preg_replace(['/"/m','/^(null|yes|no|true|false|on|off|none)=/mi','/^([^>].*)=([^"\'`].*)$/m','/^:((help|plug)\d*)$/m','/^:end$/m'],['\'','$1.=','$1="$2"',"_$1_=\"",'"'],str_replace("=\n","=''\n",file_get_contents($file)))),'strlen');
}


$plugins = glob("/boot/config/plugins/*.plg");
$templates = readJsonFile($caPaths['community-templates-info']);
if ( ! $templates ) {
	echo "You must enter the apps tab before using this script\n";
	return;
}

echo "\n<b>".tr("Updating Support Links")."</b>\n\n";
foreach ($plugins as $plugin) {
	$pluginURL = plugin("pluginURL",$plugin);
	$pluginEntry = searchArray($templates,"PluginURL",$pluginURL);
	if ( $pluginEntry === false ) {
		$pluginEntry = searchArray($templates,"PluginURL",str_replace("https://raw.github.com/","https://raw.githubusercontent.com/",$pluginURL));
	}
	if ( $pluginEntry !== false && $templates[$pluginEntry]['PluginURL']) {
		$xml = simplexml_load_file($plugin);
		if ( ! $templates[$pluginEntry]['Support'] ) {
			continue;
		}
		if ( @plugin("support",$plugin) !== $templates[$pluginEntry]['Support'] ) {
			// remove existing support attribute if it exists
			if ( @plugin("support",$plugin) ) {
				$existing_support = $xml->xpath("//PLUGIN/@support");
				foreach ($existing_support as $node) {
					unset($node[0]);
				}
			}
			$xml->addAttribute("support",$templates[$pluginEntry]['Support']);
			$dom = new DOMDocument('1.0');
			$dom->preserveWhiteSpace = false;
			$dom->formatOutput = true;
			$dom->loadXML($xml->asXML());
			file_put_contents($plugin, $dom->saveXML()); 
			echo "<b>".plugin("name",$plugin)."</b> --> <a href='{$templates[$pluginEntry]['Support']}' target='_blank'>{$templates[$pluginEntry]['Support']}</a>\n";
		}
	}
}
echo "\n\n";
echo tr("Finished Installing. If the DONE button did not appear, then you will need to click the red X in the top right corner")."\n";
?>