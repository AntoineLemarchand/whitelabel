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
        'primary' => '#0b0624',
        'secondary' => '#0e2045',
        'primary_text' => '#ffffff',
        'secondary_text' => '#000000',
        'header' => '#0b0624',
        'header_text' => '#ffffff',
        'nav' => '#0e2045',
        'nav_text' => '#ffffff',
        'nav_submenu' => '#0b0624',
        'nav_hover' => '#ffffff',
        'favorite' => '#ffff00',
    ];

    const FILES_DEFAULT = [
        'favicon' => '',
        'logo_file' => '',
        'css_configuration' => '',
    ];

    function __construct() {
        try {
            $this->getFromDB(1);
        } catch (Exception $e) {
            return;
        }
    }

    function prepareInputForUpdate($input) {
        foreach (array_keys(self::FILES_DEFAULT) as $k) {
            if (!isset($input[$k]) && !isset($input['_blank_' . $k])) {
                continue;
            }
            $filepath = GLPI_ROOT . $this->fields[$k];
            $delete = false;
            if ($this->fields[$k] != '' && (
                  isset($input['_blank_' . $k]) ||
                  (isset($input[$k]) && $input[$k] == ''))
            ) {
                unlink($filepath);
                $delete = true;
                if ($k == 'favicon') {
                    copy(Plugin::getPhpDir('whitelabel') . '/bak/favicon.ico.bak',
                        GLPI_ROOT . '/pics/favicon.ico');
                }
            }
            if (isset($input[$k])) {
                $input[$k] = json_decode(stripslashes($input[$k]), true)[0];
                $path = ItsmngUploadHandler::uploadFile(
                    $input[$k]['path'], $input[$k]['name'],
                    Plugin::getPhpDir('whitelabel') . '/uploads',
                    $k);
                $input[$k] = str_replace(GLPI_ROOT, '', $path);
                if ($k == 'favicon') {
                    copy($path, GLPI_ROOT . '/pics/favicon.ico');
                }
            } else if ($delete) {
                $input[$k] = '';
            }
        }
        return $input;
    }

    function post_updateItem($history = 1) {
        $pluginPath = Plugin::getPhpDir('whitelabel');
        $this->generateMainTemplate($pluginPath . '/uploads/whitelabel.scss');
        $this->generateTemplate($pluginPath . '/styles/login_template.scss',
            $pluginPath . '/uploads/login_whitelabel.scss');
    }

    function getVersion() {
        if (isset($this->fields['version'])) {
            return $this->fields['version'];
        }
        return '2.2.0';
    }

    private function getColors($default = false) {
        $values = self::COLORS_DEFAULT;
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

    private function getFiles($default = false) {
        $values = self::FILES_DEFAULT;
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

    function getTheme($default = false) {
        return array_merge($this->getColors($default), $this->getFiles($default));
    }

    private function generateMainTemplate($target) {
        global $CFG_GLPI;

        $content = ":root {\n";
        $colors = $this->getColors();
        foreach ($colors as $k => $v) {
            if ($v != '') {
                $content .= "  --bs-" . str_replace('_', '-', $k) . ": " . $v . ";\n";
            }
        }
        $files = $this->getFiles();
        foreach ($files as $k => $v) {
            if ($v != '') {
                $content .= "  --" . str_replace('_', '-', $k) . ": url('"
                    . $CFG_GLPI['root_doc'] . $v . "');\n";
            }
        }
        $content .= "}\n";
        file_put_contents($target, $content);
    }

    private function generateTemplate($template, $target) {
        $colors = $this->getColors();
        $files = $this->getFiles();
        $template = file_get_contents($template);
        $map = [];
        foreach (array_merge($colors, $files) as $k => $v) {
            $map['%' . $k . '%'] = $v;
        }
        $content = str_replace(array_keys($map), array_values($map), $template);
        file_put_contents($target, $content);
    }

}
