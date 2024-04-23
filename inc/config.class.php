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
class PluginWhitelabelConfig extends CommonDBTM {
    /**
     * Displays the configuration page for the plugin
     *
     * @return void
     */
    public function showConfigForm() {
        if (!Session::haveRight("plugin_whitelabel_whitelabel",UPDATE)) {
            return false;
        }

        $brand = new PluginWhitelabelBrand();
        $colors = $brand->getTheme();
        $field_labels = [
            'primary_color' => __('Primary Color'),
            'secondary_color' => __('Secondary Color'),
            'primary_text_color' => __('Primary Text Color'),
            'secondary_text_color' => __('Secondary Text Color'),
            'header_background_color' => __('Header Background Color'),
            'header_text_color' => __('Header Text Color'),
            'nav_background_color' => __('Nav Background Color'),
            'nav_text_color' => __('Nav Text Color'),
            'nav_submenu_color' => __('Nav Submenu Color'),
            'nav_hover_color' => __('Nav Hover Color'),
            'favorite_color' => __('Favorite Color'),
        ];

        $form = [
            'action' => Plugin::getWebDir("whitelabel")."/front/config.form.php",
            'buttons' => [
                [
                    'name' => 'update',
                    'type' => 'submit',
                    'value' => __('Save'),
                    'class' => 'btn btn-secondary'
                ],
                [
                    'name' => 'reset',
                    'type' => 'submit',
                    'value' => __('Reset'),
                    'class' => 'btn btn-secondary'
                ]
            ],
            'content' => [
                __('Colors') => [
                    'visible' => true,
                    'inputs' => []
                ],
                __('Files') => [
                    'visible' => true,
                    'inputs' => [
                        sprintf(__('Favicon (%s)', 'whitelabel'), Document::getMaxUploadSize()) => [
                            'id' => 'FavoriteIconFilePicker',
                            'name' => 'favicon',
                            'type' => 'imageUpload',
                            'value' => '',
                            'accept' => '.ico'
                        ],
                        sprintf(__('Logo (%s)', 'whitelabel'), Document::getMaxUploadSize()) => [
                            'id' => 'LogoFilePicker',
                            'name' => 'logo_central',
                            'type' => 'imageUpload',
                            'value' => $colors['logo_central'],
                            'accept' => '.png'
                        ],
                        sprintf(__('Import your CSS configuration (%s)', 'whitelabel'), Document::getMaxUploadSize()) => [
                            'id' => 'CssFilePicker',
                            'name' => 'css_configuration',
                            'type' => 'file',
                            'value' => '',
                            'accept' => '.css'
                        ],
                    ]
                ]
            ]
        ];
        foreach ($field_labels as $name => $title) {
            $form['content'][__('Colors')]['inputs'][$title] = [
                'name' => $name,
                'type' => 'color',
                'value' => $colors[$name],
                'col_lg' => 3,
                'col_md' => 4,
            ];
        }
        renderTwigForm($form);
    }

    /**
     * Creates a directory in the specified path, returns false if it fails
     *
     * @param string $path The path to the folder to create
     * @return bool
     */
    private function createDirectoryIfNotExist(string $path) {
        if (!file_exists($path)) {
           mkdir($path, 0664);
        } elseif (!is_dir($path)) {
            return false;
        }
        return true;
    }

}
