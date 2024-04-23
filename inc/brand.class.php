<?php
/**
 * ---------------------------------------------------------------------
 * ITSM-NG
 * Copyright (C) 2022 ITSM-NG and contributors.
 *
 * https://www.itsm-ng.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of ITSM-NG.
 *
 * ITSM-NG is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * ITSM-NG is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with ITSM-NG. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */
class PluginWhitelabelBrand extends CommonDBTM {

    const COLORS_DEFAULT = [
        'primary_color' => '#0b0624',
        'secondary_color' => '#0e2045',
        'primary_text_color' => '#ffffff',
        'secondary_text_color' => '#000000',
        'header_background_color' => '#0b0624',
        'header_text_color' => '#ffffff',
        'nav_background_color' => '#0e2045',
        'nav_text_color' => '#ffffff',
        'nav_submenu_color' => '#0b0624',
        'nav_hover_color' => '#ffffff',
        'favorite_color' => '#ffff00',
    ];

    const FILES_DEFAULT = [
        'favicon' => '',
        'logo_central' => '',
        'css_configuration' => '',
    ];

    function __construct() {
        $this->getFromDB(1);
    }

    function prepareInputForUpdate($input) {
        foreach (array_keys(self::FILES_DEFAULT) as $k) {
            $delete = false;
            if ($this->fields[$k] != '' && (
                  isset($input['_blank_' . $k]) ||
                  (isset($input[$k]) && $input[$k] == ''))
            ) {
                unlink(GLPI_DOC_DIR . '/' . $this->fields[$k]);
                $delete = true;
            }
            if (isset($input[$k])) {
                $input[$k] = json_decode(stripslashes($input[$k]), true)[0];
                $path = ItsmngUploadHandler::uploadFile($input[$k]['path'],
                    $input[$k]['name'], ItsmngUploadHandler::PLUGIN);
                $input[$k] = $path;
            } else if ($delete) {
                $input[$k] = '';
            }
        }
        return $input;
    }

    function post_updateItem($history = 1) {
        $pluginPath = Plugin::getPhpDir('whitelabel');
        $files = [
            $pluginPath . '/styles/template.scss' =>
                $pluginPath . '/uploads/whitelabel.scss',
            $pluginPath . '/styles/login_template.scss' =>
                $pluginPath . '/uploads/login_whitelabel.scss',
        ];

        $keys = array_keys(array_merge(self::COLORS_DEFAULT, self::FILES_DEFAULT));
        $map = [];
        foreach ($keys as $key) {
            $map['%' . $key . '%'] = $this->fields[$key];
        }

        foreach ($files as $template => $file) {
            $content = file_get_contents($template);
            $content = str_replace(array_keys($map), array_values($map), $content);
            file_put_contents($file, $content);
        }
    }

    function getVersion() {
        if (isset($this->fields['version'])) {
            return $this->fields['version'];
        }
        return '2.2.0';
    }

    function getTheme($default = false) {
        $values = array_merge(self::COLORS_DEFAULT, self::FILES_DEFAULT);
        if ($default) {
            return $values;
        }
        foreach (array_keys($values) as $k) {
            if ($this->fields[$k] != '') {
                $values[$k] = $this->fields[$k];
            }
        }
        return $values;
    }

    function generateTemplate($template) {
        $pluginPath = Plugin::getPhpDir('whitelabel');
        $file = $pluginPath . '/uploads/' . $template . '.scss';
        $content = file_get_contents($file);
        $keys = array_keys(array_merge(self::COLORS_DEFAULT, self::FILES_DEFAULT));
        $map = [];
        foreach ($keys as $key) {
            $map['%' . $key . '%'] = $this->fields[$key];
        }
        return str_replace(array_keys($map), array_values($map), $content);
    }
}
