<?php
/**
 * nextCloud - RoundCube mail plugin
 *
 * @author Martin Reinhardt and David Jaedke
 * @author 2019 Leonardo R. Morelli github.com/LeonardoRM
 * @copyright 2012 Martin Reinhardt contact@martinreinhardt-online.de
  *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
return [
  'routes' => [
    [
      'name' => 'page#index',
      'url' => '/',
      'verb' => 'GET'
    ],
    //settings
    [
      'name' => 'admin_settings#set',
      'url' => '/settings/admin/set',
      'verb' => 'POST',
    ],
    [
      'name' => 'personal_settings#set',
      'url' => '/settings/personal/set',
      'verb' => 'POST',
    ],
  ]
];
