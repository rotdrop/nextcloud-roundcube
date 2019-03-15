<?php
return ['routes' => [
	['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
	['name' => 'page#refresh', 'url' => '/refresh', 'verb' => 'POST'],
	//settings
	['name' => 'settings#setUserSettings', 'url' => '/ajax/userSettings.php', 'verb' => 'POST'],
	['name' => 'settings#setAdminSettings', 'url' => '/ajax/adminSettings.php', 'verb' => 'POST'],
]];

// $this->create('roundcube_ajax_userSettings', 'ajax/userSettings.php')
// 	->actionInclude(OC_App::getAppPath('roundcube').'/ajax/userSettings.php');
// $this->create('roundcube_ajax_adminSettings', 'ajax/adminSettings.php')
// 	->actionInclude(OC_App::getAppPath('roundcube').'/ajax/adminSettings.php');

// $this->create('roundcube_index', '/')->action(
// 	function($params){
// 		require OC_App::getAppPath('roundcube').'/index.php';
// 	}
// );
// $this->create('roundcube_refresh', '/refresh')->post()->action('\OCA\RoundCube\AuthHelper', 'refresh');
