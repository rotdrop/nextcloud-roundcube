<?php
/**
 * nextCloud - RoundCube mail plugin
 *
 * @author Martin Reinhardt and David Jaedke
 * @author 2019 Leonardo R. Morelli github.com/LeonardoRM
 * @author Claus-Justus Heine
 * @copyright 2020, 2021, 2023 Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2012 Martin Reinhardt contact@martinreinhardt-online.de
 *
 * Nextcloud RoundCube App is free software: you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or (at your option) any later version.
 *
 * Nextcloud RoundCube App is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with Nextcloud RoundCube App. If not, see
 * <http://www.gnu.org/licenses/>.
 */

$url .= '?_task=mail';

script($appName, $assets['js']['asset']);
style($appName, $assets['css']['asset']);

?>

<div id="<?php p($webPrefix); ?>LoaderContainer">
    <img src="<?php p($loadingImage); ?>" id="<?php p($webPrefix); ?>Loader">
</div>
<iframe src="<?php p($url); ?>"
        id="<?php p($webPrefix); ?>Frame"
        class="<?php p($showTopLine); ?>"
        name="<?php p($webPrefix); ?>">
</iframe>
