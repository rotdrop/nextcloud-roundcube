<?php
return ['routes' => [
	['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
	//settings
	['name' => 'settings#setAdminSettings', 'url' => '/ajax/adminSettings.php', 'verb' => 'POST'],
]];
