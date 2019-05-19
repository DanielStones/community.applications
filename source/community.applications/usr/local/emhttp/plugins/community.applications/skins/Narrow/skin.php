<?PHP
###############################################################
#                                                             #
# Community Applications copyright 2015-2019, Andrew Zawadzki #
#                    All Rights Reserved                      #
#                                                             #
###############################################################

############################################################
#                                                          #
# Routines that actually displays the template containers. #
#                                                          #
############################################################
function display_apps($pageNumber=1,$selectedApps=false,$startup=false) {
	global $communityPaths, $communitySettings;

	$file = readJsonFile($communityPaths['community-templates-displayed']);
	$communityApplications = is_array($file['community']) ? $file['community'] : array();
	$totalApplications = count($communityApplications);

	$display = ( $totalApplications ) ? my_display_apps($communityApplications,$pageNumber,$selectedApps,$startup) : "<div class='ca_NoAppsFound'></div>";

	return $display;
}

# skin specific PHP
#my_display_apps(), getPageNavigation(), displaySearchResults() must accept all parameters
#note that many template entries in my_display_apps() are not actually used in the skin, but are present for future possible use.
function my_display_apps($file,$pageNumber=1,$selectedApps=false,$startup=false) {
	global $communityPaths, $communitySettings, $plugin, $displayDeprecated, $sortOrder;

	$viewMode = "detail";

	$info = getRunningContainers();
	$skin = readJsonFile($communityPaths['defaultSkin']);

	if ( ! $selectedApps ) {
		$selectedApps = array();
	}

	if ( ! $communitySettings['dockerRunning'] && ! $communitySettings['NoInstalls']) {
		$displayHeader = "<script>addWarning('Docker Service Not Enabled - Only Plugins Available To Be Installed Or Managed');</script>";
	}

	$pinnedApps = readJsonFile($communityPaths['pinned']);
	$iconSize = $communitySettings['iconSize'];
	$checkedOffApps = arrayEntriesToObject(@array_merge(@array_values($selectedApps['docker']),@array_values($selectedApps['plugin'])));
	if ( filter_var($startup,FILTER_VALIDATE_BOOLEAN) ) {
		$sortOrder['sortBy'] = "noSort";
	}
	if ( $sortOrder['sortBy'] != "noSort" ) {
		if ( $sortOrder['sortBy'] == "Name" ) $sortOrder['sortBy'] = "SortName";
		usort($file,"mySort");
	}

	$displayHeader .= getPageNavigation($pageNumber,count($file),false)."<br>";

	$columnNumber = 0;
	$appCount = 0;
	$startingApp = ($pageNumber -1) * $communitySettings['maxPerPage'] + 1;
	$startingAppCounter = 0;

	$displayedTemplates = array();
	foreach ($file as $template) {
		if ( $template['Blacklist'] && ! $template['NoInstall'] ) {
			continue;
		}
		$startingAppCounter++;
		if ( $startingAppCounter < $startingApp ) {
			continue;
		}
		$displayedTemplates[] = $template;
	}

	$ct .= $skin[$viewMode]['header'];
	$iconClass = "displayIcon";
		
	$displayTemplate = $skin[$viewMode]['template'];
	$currentServer = file_get_contents($communityPaths['currentServer']);

	# Create entries for skins.  Note that MANY entries are not used in the current skins
	foreach ($displayedTemplates as $template) {
		if ( $currentServer == "Primary Server" && $template['IconHTTPS']) {
			$template['Icon'] = $template['IconHTTPS'];
		}
		$name = $template['SortName'];
		$appName = str_replace(" ","",$template['SortName']);
		$ID = $template['ID'];
		$template['ModeratorComment'] .= $template['CAComment'];
		$selected = $info[$name]['template'];
		$tmpRepo = strpos($template['Repository'],":") ? $template['Repository'] : "{$template['Repository']}:latest";
		$selected = $selected ? ($tmpRepo == $info[$name]['repository']) : false;
		$selected = $template['Uninstall'] ? true : $selected;

		$appType = $template['Plugin'] ? "plugin" : "docker";
		$previousAppName = $template['Plugin'] ? $template['PluginURL'] : $template['Name'];
		$checked = $checkedOffApps[$previousAppName] ? "checked" : "";

		$template['Category'] = categoryList($template['Category']);

		$RepoName = ( $template['Private'] == "true" ) ? $template['RepoName']."<font color=red> (Private)</font>" : $template['RepoName'];
		if ( ! $template['DonateText'] ) {
			$template['DonateText'] = "Donate To Author";
		}
		$template['display_Private'] = ( $template['Private'] == "true" ) ? "<span class='ca_tooltip ca_private' title='Private (dockerHub Conversion)'></span>" : "";
		$template['display_DonateLink'] = $template['DonateLink'] ? "<a class='ca_tooltip donateLink' href='".$template['DonateLink']."' target='_blank' title='".$template['DonateText']."'>Donate To Author</a>" : "";
		$template['display_DonateImage'] = $template['DonateLink'] ? "<a class='ca_tooltip donateLink donate' href='".$template['DonateLink']."' target='_blank' title='".$template['DonateText']."'>Donate</a>" : "";

		$template['display_Project'] = $template['Project'] ? "<a class='ca_tooltip projectLink' target='_blank' title='Click to go the the Project Home Page' href='".$template['Project']."'></a>" : "";
		$template['display_faProject'] = $template['Project'] ? "<a class='ca_tooltip ca_fa-project appIcons' target='_blank' href='{$template['Project']}' title='Go to the project page'></a>" : "";
		$template['display_Support'] = $template['Support'] ? "<a class='ca_tooltip supportLink' href='".$template['Support']."' target='_blank' title='Click to go to the support thread'></a>" : "";
		$template['display_faSupport'] = $template['Support'] ? "<a class='ca_tooltip ca_fa-support appIcons' href='{$template['Support']}' target='_blank' title='Support Thread'></a>" : "";

		$template['display_webPage'] = $template['WebPageURL'] ? "<a class='ca_tooltip webLink' title='Click to go to {$template['SortAuthor']}&#39;s web page' href='".$template['WebPageURL']."' target='_blank'></a>" : "";

		$template['display_ModeratorComment'] .= $template['ModeratorComment'] ? "</span></strong><font color='purple'>".$template['ModeratorComment']."</font>" : "";
		$tempLogo = $template['Logo'] ? "<img src='".$template['Logo']."' height=2.0rem;>" : "";
		$template['display_Repository'] = "<span class='ca_repository'>$RepoName $tempLogo</span>";
		$template['display_Stars'] = $template['stars'] ? "<i class='fa fa-star dockerHubStar' aria-hidden='true'></i> <strong>".$template['stars']."</strong>" : "";
		$template['display_Downloads'] = $template['downloads'] ? "<div class='ca_center'>".number_format($template['downloads'])."</div>" : "<div class='ca_center'>Not Available</div>";

		if ( $pinnedApps[$template['Repository']] ) {
			$pinned = "pinned";
			$pinnedTitle = "Click to unpin this application";
		} else {
			$pinned = "unpinned";
			$pinnedTitle = "Click to pin this application";
		}
		$template['display_pinButton'] = "<span class='ca_tooltip $pinned' title='$pinnedTitle' onclick='pinApp(this,&quot;".$template['Repository']."&quot;);'></span>";
		if ($template['Blacklist']) {
			unset($template['display_pinButton']);
		}
		if ( $template['Uninstall'] ) {
			$template['display_Uninstall'] = "<a class='ca_tooltip ca_fa-delete' title='Uninstall Application' ";
			$template['display_Uninstall'] .= ( $template['Plugin'] ) ? "onclick='uninstallApp(&quot;".$template['MyPath']."&quot;,&quot;".$template['Name']."&quot;);'>" : "onclick='uninstallDocker(&quot;".$template['MyPath']."&quot;,&quot;".$template['Name']."&quot;);'>";
			$template['display_Uninstall'] .= "</a>";
		} else {
			if ( $template['Private'] == "true" ) {
				$template['display_Uninstall'] = "<a class='ca_tooltip  ca_fa-delete' title='Remove Private Application' onclick='deletePrivateApp(&quot;{$template['Path']}&quot;,&quot;{$template['SortName']}&quot;,&quot;{$template['SortAuthor']}&quot;);'></a>";
			}
		}
		$template['display_removable'] = $template['Removable'] && ! $selected ? "<a class='ca_tooltip ca_fa-delete' title='Remove Application From List' onclick='removeApp(&quot;".$template['MyPath']."&quot;,&quot;".$template['Name']."&quot;);'></a>" : "";
		if ( $template['display_Uninstall'] && $template['display_removable'] ) {
			unset($template['display_Uninstall']); # prevent previously installed private apps from having 2 x's in previous apps section
		}

		$template['display_humanDate'] = date("F j, Y",$template['Date']);
		$UpdatedClassType = $template['BrandNewApp'] ? "ca_dateAdded" : "ca_dateUpdated";
		$template['display_dateUpdated'] = ($template['Date'] && $template['NewApp'] ) ? "<span class='$UpdatedClassType'><span class='ca_dateUpdatedDate'>{$template['display_humanDate']}</span></span>" : "";
		$template['display_multi_install'] = ($template['Removable']) ? "<input class='ca_multiselect ca_tooltip' title='Check-off to select multiple reinstalls' type='checkbox' data-name='$previousAppName' data-type='$appType' $checked>" : "";
		if (! $communitySettings['dockerRunning'] && ! $template['Plugin']) {
			unset($template['display_multi_install']);
		}
		if ( $template['Plugin'] ) {
			$template['UpdateAvailable'] = checkPluginUpdate($template['PluginURL']);
		}
		if ( $template['UpdateAvailable'] ) {
			$template['display_UpdateAvailable'] = $template['Plugin'] ? "<br><div class='ca_center'><font color='red'><span class='ca_bold'>Update Available.  Click <a onclick='installPLGupdate(&quot;".basename($template['MyPath'])."&quot;,&quot;".$template['Name']."&quot;);' style='cursor:pointer'>Here</a> to Install</span></div></font>" : "<br><div class='ca_center'><font color='red'><span class='ca_bold'>Update Available.  Click <a href='Docker'>Here</a> to install</span></font></div>";
		}
		if ( ! $template['NoInstall'] && ! $communitySettings['NoInstalls'] ){  # certain "special" categories (blacklist, deprecated, etc) don't allow the installation etc icons
			if ( $template['Plugin'] ) {
				$pluginName = basename($template['PluginURL']);
				if ( checkInstalledPlugin($template) ) {
					$pluginSettings = $pluginName == "community.applications.plg" ? "ca_settings" : plugin("launch","/var/log/plugins/$pluginName");
					$tmpVar = $pluginSettings ? "" : " disabled ";
					$template['display_pluginSettingsIcon'] = $pluginSettings ? "<a class='ca_tooltip ca_fa-pluginSettings appIcons' title='Click to go to the plugin settings' href='$pluginSettings'></a>" : "";
				} else {
					$buttonTitle = $template['MyPath'] ? "Reinstall Plugin" : "Install Plugin";
					$template['display_pluginInstallIcon'] = "<a style='cursor:pointer' class='ca_tooltip ca_fa-install appIcons' title='Click to install this plugin' onclick=installPlugin('".$template['PluginURL']."');></a>";
				}
			} else {
				if ( $communitySettings['dockerRunning'] ) {
					if ( $selected ) {
						$template['display_dockerDefaultIcon'] = "<a class='ca_tooltip ca_fa-install appIcons' title='Click to reinstall the application using default values' href='/Apps/AddContainer?xmlTemplate=default:".addslashes($template['Path'])."' target='_self'></a>";
						$template['display_dockerDefaultIcon'] = $template['BranchID'] ? "<a class='ca_tooltip ca_fa-install appIcons' type='button' style='margin:0px' title='Click to reinstall the application using default values' onclick='displayTags(&quot;$ID&quot;);'></a>" : $template['display_dockerDefaultIcon'];
						$template['display_dockerEditIcon']    = "<a class='ca_tooltip appIcons ca_fa-edit' title='Click to edit the application values' href='/Apps/UpdateContainer?xmlTemplate=edit:".addslashes($info[$name]['template'])."' target='_self'></a>";
						if ( $info[$name]['url'] && $info[$name]['running'] ) {
							$template['dockerWebIcon'] = "<a class='ca_tooltip appIcons ca_fa-globe' href='{$info[$name]['url']}' target='_blank' title='Click To Go To The App&#39;s UI'></a>";
						}
					} else {
						if ( $template['MyPath'] ) {
							$template['display_dockerReinstallIcon'] = "<a class='ca_tooltip ca_fa-install appIcons' title='Click to reinstall' href='/Apps/UpdateContainer?xmlTemplate=user:".addslashes($template['MyPath'])."' target='_self'></a>";
							} else {
							$template['display_dockerInstallIcon'] = "<a class='ca_tooltip ca_fa-install appIcons' title='Click to install' href='Apps/AddContainer?xmlTemplate=default:".addslashes($template['Path'])."' target='_self'></a>";
							$template['display_dockerInstallIcon'] = $template['BranchID'] ? "<a style='cursor:pointer' class='ca_tooltip ca_fa-install appIcons' title='Click to install the application' onclick='displayTags(&quot;$ID&quot;);'></a>" : $template['display_dockerInstallIcon'];
						}
					}
				}
			}
		} else {
			$specialCategoryComment = $template['NoInstall'];
		}
		$warningColor = "warning-white";
		if ( $template['Beta'] ) {
			$template['display_compatible'] .= "This application has been marked as being <span class='ca_italic'>Beta</span>.";
			if (! $template['Blacklist'] && ! $template['Deprecated'] ) {
				$template['display_compatible'] .= "This does NOT neccessarily mean that there will be issues.<br>";
			} else {
				$template['display_compatible'] .= "<br>";
			}
		}
		if ( $template['Deprecated'] ) {
			$template['display_compatible'] .= "This application / template has been deprecated.<br>";
			$warningColor = "warning-yellow";
		}
		if ( ! $template['Compatible'] && ! $template['UnknownCompatible'] ) {
			$template['display_compatible'] .= "NOTE: This application is listed as being NOT compatible with your version of unRaid<br>";
			$template['display_compatibleShort'] = "Incompatible";
			$warningColor = "warning-red";
		}
		if ( $template['Blacklist'] ) {
			$template['display_compatible'] .= "This application / template has been blacklisted.<br>";
			$warningColor = "warning-red";
		}

		if ( $template['ModeratorComment'] || $template['Deprecated'] || ! $template['Compatible'] || $template['Blacklist'] || $template['Beta']) {
			$template['display_warning-text'] = trim("{$template['ModeratorComment']}<br>{$template['display_compatible']}");
		}
		$template['display_faWarning'] = $template['display_warning-text'] ? "<span class='ca_tooltip-warning ca_fa-warning appIcons $warningColor' title='".htmlspecialchars($template['display_warning-text'],ENT_COMPAT | ENT_QUOTES)."'></span>" : "";

		$template['display_author'] = "<a class='ca_tooltip ca_author' onclick='doSearch(false,this.innerText);' title='Search for more applications from {$template['SortAuthor']}'>".$template['Author']."</a>";
		$displayIcon = $template['Icon'];
		$displayIcon = $displayIcon ? $displayIcon : "/plugins/dynamix.docker.manager/images/question.png";
		$template['display_iconSmall'] = "<a onclick='showDesc(".$template['ID'].",&#39;".$name."&#39;);' style='cursor:pointer'><img class='ca_appPopup $iconClass' data-appNumber='$ID' data-appPath='{$template['Path']}' src='$displayIcon'></a>";
		$template['display_iconSelectable'] = "<img class='$iconClass' src='$displayIcon'>";
		$template['display_infoIcon'] = "<a class='ca_appPopup ca_tooltip appIcons ca_fa-info' title='Click for more information' data-appNumber='$ID' data-appPath='{$template['Path']}' data-appName='{$template['Name']}' style='cursor:pointer'></a>";
		if ( isset($ID) ) {
			$template['display_iconClickable'] = "<a class='ca_appPopup' data-appName='{$template['Name']}' data-appNumber='$ID' data-appPath='{$template['Path']}'>".$template['display_iconSelectable']."</a>";
			$template['display_iconSmall'] = "<a class='ca_appPopup' onclick='showDesc(".$template['ID'].",&#39;".$name."&#39;);'><img class='ca_appPopup $iconClass' data-appNumber='$ID' data-appPath='{$template['Path']}' src='".$displayIcon."'></a>";
			$template['display_iconOnly'] = "<img class='$iconClass' src='".$displayIcon."'></img>";
		} else {
			$template['display_iconClickable'] = $template['display_iconSelectable'];
			$template['display_iconSmall'] = "<img src='".$displayIcon."' class='$iconClass'>";
			$template['display_iconOnly'] = $template['display_iconSmall'];
		}
		if ( $template['IconFA'] ) {
			$displayIcon = $template['IconFA'] ?: $template['Icon'];
			$displayIconClass = startsWith($displayIcon,"icon-") ? $displayIcon : "fa fa-$displayIcon";
			$template['display_iconSmall'] = "<a class='ca_appPopup' onclick='showDesc(".$template['ID'].",&#39;".$name."&#39;);'><div class='ca_center'><i class='ca_appPopup $displayIconClass $iconClass' data-appNumber='$ID' data-appPath='{$template['Path']}'></i></div></a>";
			$template['display_iconSelectable'] = "<div class='ca_center'><i class='$displayIconClass $iconClass'></i></div>";
			if ( isset($ID) ) {
				$template['display_iconClickable'] = "<a class='ca_appPopup' data-appName='{$template['Name']}' data-appNumber='$ID' data-appPath='{$template['Path']}' style='cursor:pointer' >".$template['display_iconSelectable']."</a>";
				$template['display_iconSmall'] = "<a class='ca_appPopup' onclick='showDesc(".$template['ID'].",&#39;".$name."&#39;);'><div class='ca_center'><i class='fa fa-$displayIcon ca_appPopup $iconClass' data-appNumber='$ID' data-appPath='{$template['Path']}'></i></div></a>";
				$template['display_iconOnly'] = "<div class='ca_center'><i class='fa fa-$displayIcon $iconClass'></i></div>";
			} else {
				$template['display_iconClickable'] = $template['display_iconSelectable'];
				$template['display_iconSmall'] = "<div class='ca_center'><i class='$displayIconClass $iconClass'></i></div>";
				$template['display_iconOnly'] = $template['display_iconSmall'];
			}
		}

		$template['display_dockerName'] = "<span class='ca_applicationName'>{$template['Name']}</span>";
		$template['Category'] = ($template['Category'] == "UNCATEGORIZED") ? "Uncategorized" : $template['Category'];

		if ( ( $template['Beta'] == "true" ) ) {
			$template['display_dockerBeta'] .= "<span class='ca_tooltip displayBeta' title='Beta Container &#13;See support forum for potential issues'>(Beta)</span>";
		}
# Entries created.  Now display it
		$ct .= vsprintf($displayTemplate,toNumericArray($template));
		$count++;
		if ( $count == $communitySettings['maxPerPage'] ) {
			break;
		}
	}
	$ct .= $skin[$viewMode]['footer'];
	$ct .= getPageNavigation($pageNumber,count($file),false,false)."<br><br><br>";

	if ( $specialCategoryComment ) {
		$displayHeader .= "<span class='specialCategory'><div class='ca_center'>This display is informational <span class='ca_italic'>ONLY</span>. Installations, edits, etc are not possible on this screen, and you must navigate to the appropriate settings and section / category</div><br>";
		$displayHeader .= "<div class='ca_center'>$specialCategoryComment</div></span>";
	}
	return "$displayHeader$ct";
}

function getPageNavigation($pageNumber,$totalApps,$dockerSearch,$displayCount = true) {
	global $communitySettings;

	if ( $communitySettings['maxPerPage'] < 0 ) { return; }
	$swipeScript = "<script>";
	$my_function = $dockerSearch ? "dockerSearch" : "changePage";
	if ( $dockerSearch ) {
		$communitySettings['maxPerPage'] = 25;
	}
	$totalPages = ceil($totalApps / $communitySettings['maxPerPage']);

	if ($totalPages == 1) {
		return;
	}
	$startApp = ($pageNumber - 1) * $communitySettings['maxPerPage'] + 1;
	$endApp = $pageNumber * $communitySettings['maxPerPage'];
	if ( $endApp > $totalApps ) {
		$endApp = $totalApps;
	}
	$o = "<div class='ca_center'>";
	if ( ! $dockerSearch && $displayCount) {
		$o .= "<span class='pageNavigation'>Displaying $startApp - $endApp (of $totalApps)</span><br>";
	}

	$o .= "<div class='pageNavigation'>";
	$previousPage = $pageNumber - 1;
	$o .= ( $pageNumber == 1 ) ? "<span class='pageLeft pageNumber pageNavNoClick'></span>" : "<span class='pageLeft ca_tooltip pageNumber' onclick='{$my_function}(&quot;$previousPage&quot;)'></span>";
	$swipeScript .= "data.prevpage = $previousPage;";
	$startingPage = $pageNumber - 5;
	if ($startingPage < 3 ) {
		$startingPage = 1;
	} else {
		$o .= "<a class='ca_tooltip pageNumber' onclick='{$my_function}(&quot;1&quot;);'>1</a><span class='pageNumber pageDots'></span>";
	}
	$endingPage = $pageNumber + 5;
	if ( $endingPage > $totalPages ) {
		$endingPage = $totalPages;
	}
	for ($i = $startingPage; $i <= $endingPage; $i++) {
		$o .= ( $i == $pageNumber ) ? "<span class='pageNumber pageSelected'>$i</span>" : "<a class='ca_tooltip pageNumber' onclick='{$my_function}(&quot;$i&quot;);'>$i</a>";
	}
	if ( $endingPage != $totalPages) {
		if ( ($totalPages - $pageNumber ) > 6){
			$o .= "<span class='pageNumber pageDots'></span>";
		}
		if ( ($totalPages - $pageNumber ) >5 ) {
			$o .= "<a class='ca_tooltip pageNumber' onclick='{$my_function}(&quot;$totalPages&quot;);'>$totalPages</a>";
		}
	}
	$nextPage = $pageNumber + 1;
	$o .= ( $pageNumber < $totalPages ) ? "<span class='ca_tooltip pageNumber pageRight' onclick='{$my_function}(&quot;$nextPage&quot;);'></span>" : "<span class='pageRight pageNumber pageNavNoClick'></span>";
	$swipeScript .= ( $pageNumber < $totalPages ) ? "data.nextpage = $nextPage;" : "data.nextpage = 0;";
	$swipeScript .= ( $dockerSearch ) ? "dockerSearchFlag = true;" : "dockerSearchFlag = false";
	$swipeScript .= "</script>";
	$o .= "</div></div><script>data.currentpage = $pageNumber;</script>";
	return $o.$swipeScript;
}

########################################################################################
#                                                                                      #
# function used to display the navigation (page up/down buttons) for dockerHub results #
#                                                                                      #
########################################################################################
function dockerNavigate($num_pages, $pageNumber) {
	return getPageNavigation($pageNumber,$num_pages * 25, true);
}

##############################################################
#                                                            #
# function that actually displays the results from dockerHub #
#                                                            #
##############################################################
function displaySearchResults($pageNumber) {
	global $communityPaths, $communitySettings, $plugin;

	$tempFile = readJsonFile($communityPaths['dockerSearchResults']);
	$num_pages = $tempFile['num_pages'];
	$file = $tempFile['results'];
	$templates = readJsonFile($communityPaths['community-templates-info']);
	$skin = readJsonFile($communityPaths['defaultSkin']);
	$viewMode = "detail";
	$displayTemplate = $skin[$viewMode]['template'];

	$ct = dockerNavigate($num_pages,$pageNumber)."<br>";
	$ct .= $skin[$viewMode]['header'];

	$columnNumber = 0;
	foreach ($file as $result) {
		$result['display_Repository'] = $result['Repository'];
		$result['Icon'] = "/plugins/dynamix.docker.manager/images/question.png";
		$result['display_dockerName'] = "<a class='ca_tooltip ca_applicationName' style='cursor:pointer;' onclick='mySearch(this.innerText);' title='Search for similar containers'>{$result['Name']}</a>";
		$result['display_author'] = "<a class='ca_tooltip ca_author' onclick='mySearch(this.innerText);' title='Search For Containers From {$result['Author']}'>{$result['Author']}</a>";
		$result['Category'] = "Docker Hub Search";
		$result['display_iconClickable'] = "<i class='displayIcon fa fa-docker'></i>";
		$result['Description'] = $result['Description'] ?: "No description present";
		$result['display_faProject'] = "<a class='ca_tooltip ca_fa-project appIcons' title='Go to dockerHub page' target='_blank' href='{$result['DockerHub']}'></a>";
		$result['display_dockerInstallIcon'] = "<a class='ca_tooltip ca_fa-install appIcons' title='Click To Install' onclick='dockerConvert(&#39;".$result['ID']."&#39;);'></a>";
		$ct .= vsprintf($displayTemplate,toNumericArray($result));
		$count++;
	}
	$ct .= $skin[$viewMode]['footer'];

	echo $ct.dockerNavigate($num_pages,$pageNumber);
}

############################################################################
# Function to convert a template's associative tags to static numeric tags #
# (Because the associate tag order can change depending upon the template) #
############################################################################
function toNumericArray($template) {
	global $communitySettings;

	return array(
		$template['Repository'],              # 1
		$template['Author'],                  # 2
		$template['Name'],                    # 3
		$template['DockerHubName'],           # 4
		$template['Beta'],                    # 5
		$template['Changes'],                 # 6
		$template['Date'],                    # 7
		$template['RepoName'],                # 8
		$template['Project'],                 # 9
		$template['ID'],                      #10
		$template['Base'],                    #11
		$template['BaseImage'],               #12
		$template['SortAuthor'],              #13
		$template['SortName'],                #14
		$template['Licence'],                 #15
		$template['Plugin'],                  #16
		$template['PluginURL'],               #17
		$template['PluginAuthor'],            #18
		$template['MinVer'],                  #19
		$template['MaxVer'],                  #20
		$template['Category'],                #21
		$template['Description'],             #22
		$template['Overview'],                #23
		$template['Downloads'],               #24
		$template['Stars'],                   #25
		$template['Announcement'],            #26
		$template['Support'],                 #27
		$template['IconWeb'],                 #28
		$template['DonateText'],              #29
		$template['DonateImg'],               #30 - Deprecated Tag Do Not Use
		$template['DonateLink'],              #31
		$template['PopUpDescription'],        #32 - No longer implemented
		$template['ModeratorComment'],        #33
		$template['Compatible'],              #34
		$template['display_DonateLink'],      #35
		$template['display_Project'],         #36
		$template['display_Support'],         #37
		$template['display_UpdateAvailable'], #38
		$template['display_ModeratorComment'],#39
		$template['display_Repository'],      #40
		$template['display_Stars'],           #41
		$template['display_Downloads'],       #42
		$template['display_pinButton'],       #43
		$template['display_Uninstall'],       #44
		$template['display_removable'],       #45
		$template['display_newIcon'],         #46 # Do not use -> no longer implemented
		$template['display_changes'],         #47 # Do not use -> no longer implemented
		$template['display_webPage'],         #48
		$template['display_humanDate'],       #49
		$template['display_pluginSettings'],  #50 # do not use -> no longer implemented
		$template['display_pluginInstall'],   #51 # do not use -> no longer implemented
		$template['display_dockerDefault'],   #52 # do not use -> no longer implemented
		$template['display_dockerEdit'],      #53 # do not use -> no longer implemented
		$template['display_dockerReinstall'], #54 # do not use -> no longer implemented
		$template['display_dockerInstall'],   #55 # do not use -> no longer implemented
		$template['display_dockerDisable'],   #56 # do not use -> no longer implemented
		$template['display_compatible'],      #57
		$template['display_compatibleShort'], #58
		$template['display_author'],          #59
		$template['display_iconSmall'],       #60
		$template['display_iconSelectable'],  #61
		$template['display_popupDesc'],       #62  # Do not use -> no longer implemented
		$template['display_updateAvail'],     #63  *** NO LONGER USED - USE #38 instead
		$template['display_dateUpdated'],     #64
		$template['display_iconClickable'],   #65
		str_replace("-"," ",$template['display_dockerName']),      #66
		$template['Path'],                    #67
		$template['display_pluginInstallIcon'],#68
		$communitySettings['defaultReinstall'] == "true" ? $template['display_dockerDefaultIcon'] : "",#69
		$template['display_dockerEditIcon'],  #70
		$template['display_dockerReinstallIcon'], #71
		$template['display_dockerInstallIcon'], #72
		$template['display_pluginSettingsIcon'], #73
		$template['dockerWebIcon'],            #74
		$template['display_multi_install'],     #75
		$template['display_DonateImage'],      #76
		$template['display_dockerBeta'],        #77
"<span class='ca_applicationName'>".str_replace("-"," ",$template['display_dockerName'])."</span>{$template['display_Private']}<br><span class='ca_author'>{$template['display_author']}</span><br><span class='ca_categories'>{$template['Category']}</span>",  #78
		$template['display_faSupport'],  #79
		$template['display_faProject'],     #80
		$template['display_iconOnly'],   #81
		$template['display_infoIcon'],  #82
		$template['display_faWarning'], #83
		$template['CardDescription']	#84
	);
}
?>