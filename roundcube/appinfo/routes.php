<?php
return ['routes' => [
	['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
	//settings
	['name' => 'settings#setUserSettings', 'url' => '/ajax/userSettings.php', 'verb' => 'POST'],
	['name' => 'settings#setAdminSettings', 'url' => '/ajax/adminSettings.php', 'verb' => 'POST'],
]];
