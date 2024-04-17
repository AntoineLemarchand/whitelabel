<?php
/**
 * ---------------------------------------------------------------------
 * LICENSE
 *
 * This file is part of Whitelabel.
 *
 * Formcreator is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Whitelabel is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Whitelabel. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 * @license   http://www.gnu.org/licenses/gpl.txt GPLv3+
 * @link      https://github.com/pluginsGLPI/formcreator/
 * @link      https://pluginsglpi.github.io/formcreator/
 * @link      http://plugins.glpi-project.org/#/plugin/formcreator
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginWhitelabelInstall {
    function isPluginInstalled() {
        global $DB;

        $query = "SHOW TABLES LIKE 'glpi_plugin_whitelabel%'";
        $tables = $DB->request($query);
        return count($tables) > 0;
    }

    function install($migration) {
        global $DB;

        $migration = new Migration(101);
        //get default values for fields
        $default_value_css = new plugin_whitelabel_const();
        if (!$DB->tableExists("glpi_plugin_whitelabel_brand")) {
            $query = "CREATE TABLE glpi_plugin_whitelabel_brand (
                id int(11) NOT NULL AUTO_INCREMENT,
                favicon varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                logo_central varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                css_configuration varchar(255) COLLATE utf8_unicode_ci NOT NULL,";
            foreach ($default_value_css::all_value() as $k => $v){
                $query .= $k." varchar(7) COLLATE utf8_unicode_ci NOT NULL DEFAULT '".$v."',";
        }
        $query .= "PRIMARY KEY (`id`)) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->queryOrDie($query, $DB->error());

        $default_value_css->insert_default_config();
        }

        if (!$DB->tableExists("glpi_plugin_whitelabel_profiles")) {
            $query = "CREATE TABLE `glpi_plugin_whitelabel_profiles` (
                `id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_profiles (id)',
                `right` char(1) collate utf8_unicode_ci default NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

            $DB->queryOrDie($query, $DB->error());

            include_once(GLPI_ROOT."/plugins/whitelabel/inc/profile.class.php");
            PluginWhitelabelProfile::createAdminAccess($_SESSION['glpiactiveprofile']['id']);

            foreach (PluginWhitelabelProfile::getRightsGeneral() as $right) {
                PluginWhitelabelProfile::addDefaultProfileInfos($_SESSION['glpiactiveprofile']['id'],[$right['field'] => $right['default']]);
            }
        }

        // Create backup of resources that will be altered
        if (!file_exists(Plugin::getPhpDir("whitelabel")."/bak/custom.scss.bak")) {
            copy(GLPI_ROOT."/css/custom.scss", Plugin::getPhpDir("whitelabel")."/bak/custom.scss.bak");
            copy(GLPI_ROOT."/pics/favicon.ico", Plugin::getPhpDir("whitelabel")."/bak/favicon.ico.bak");
        }

        // Update 2.0
        if($DB->tableExists("glpi_plugin_whitelabel_brand")) {

            foreach ($default_value_css::all_value() as $k=>$v){
                if(!$DB->fieldExists('glpi_plugin_whitelabel_brand',$k)){
                    $query = "ALTER TABLE glpi_plugin_whitelabel_brand ADD COLUMN ".$k." varchar(7) COLLATE utf8_unicode_ci NOT NULL DEFAULT '".$v."'";
                    $DB->queryOrDie($query, $DB->error());
                }
            }
            if($DB->fieldExists('glpi_plugin_whitelabel_brand', 'brand_color')) {
                $query = "ALTER TABLE glpi_plugin_whitelabel_brand DROP COLUMN brand_color";
                $DB->queryOrDie($query, $DB->error());
            }
        }

        $migration->executeMigration();
        return true;
    }

    function uninstall() {
        global $DB;

        // Drop tables
        if($DB->tableExists('glpi_plugin_whitelabel_brand')) {
            $DB->queryOrDie("DROP TABLE `glpi_plugin_whitelabel_brand`",$DB->error());
        }

        if($DB->tableExists('glpi_plugin_whitelabel_profiles')) {
            $DB->queryOrDie("DROP TABLE `glpi_plugin_whitelabel_profiles`",$DB->error());
        }

        // Clear profiles
        foreach (PluginWhitelabelProfile::getRightsGeneral() as $right) {
            $query = "DELETE FROM `glpi_profilerights` WHERE `name` = '".$right['field']."'";
            $DB->query($query);

            if (isset($_SESSION['glpiactiveprofile'][$right['field']])) {
                unset($_SESSION['glpiactiveprofile'][$right['field']]);
            }
        }

        // Clear uploads
        $files = glob(Plugin::getPhpDir("whitelabel")."/uploads/*"); // Get all file names in `uploads`

        foreach($files as $file){ // Iterate files
            if(is_file($file)) unlink($file); // Delete file
        }

        // Clear patches
        if (is_file(Plugin::getPhpDir("whitelabel")."/bak/custom.scss.bak")) {
            copy(Plugin::getPhpDir("whitelabel")."/bak/custom.scss.bak", GLPI_ROOT."/css/custom.scss");
            copy(Plugin::getPhpDir("whitelabel")."/bak/favicon.ico.bak", GLPI_ROOT."/pics/favicon.ico");
        }

        // Clear bakups
        $files = glob(Plugin::getPhpDir("whitelabel")."/bak/*");

        foreach($files as $file){
            if(is_file($file)) unlink($file);
        }

        return true;
    }

    function upgrade($migration) {
        global $DB;
        $version = table_glpi_plugin_whitelabel_brand::getVersion();

        if (!$version) {
            $DB->queryOrDie("ALTER TABLE `glpi_plugin_whitelabel_brand` ADD `version` VARCHAR(255) NOT NULL DEFAULT '2.2.0' AFTER `id`");
            $version = '2.2.0';
        }

        switch ($version) {
            case '2.2.0':
                $constants = new plugin_whitelabel_const();
                $addedFields = [
                    'header_text_color' => 'menu_text_color',
                    'nav_background_color' => 'menu_color',
                    'nav_text_color' => 'menu_text_color',
                    'header_background_color' => 'primary_color',
                    'header_text_color' => 'header_icons_color',
                ];
                foreach ($addedFields as $new => $old) {
                    $color = $DB->request("SELECT ".$old." FROM glpi_plugin_whitelabel_brand WHERE id = 1");
                    $migration->addField('glpi_plugin_whitelabel_brand', $new,
                        "varchar(7) COLLATE utf8_unicode_ci NOT NULL DEFAULT '".
                        iterator_to_array($color)[0][$old]."'");
                }
                $migration->addField('glpi_plugin_whitelabel_brand', 'favorite_color',
                    "varchar(7) COLLATE utf8_unicode_ci NOT NULL DEFAULT '".$constants->value_key('favorite_color')."'");
                $changedFields = [
                    'header_icons_color' => 'primary_text_color',
                    'menu_color' => 'secondary_color',
                    'menu_text_color' => 'secondary_text_color',
                    'menu_active_color' => 'nav_submenu_color',
                    'menu_onhover_color' => 'nav_hover_color',
                ];
                foreach ($changedFields as $old => $new) {
                    // change the name and the default value of old based on constants
                    $DB->queryOrDie("ALTER TABLE `glpi_plugin_whitelabel_brand` CHANGE `".$old."` `".$new."` varchar(7) COLLATE utf8_unicode_ci NOT NULL DEFAULT '".
                        $constants->value_key($old)."'");
                }

                $migration->executeMigration();
                break;
        }
        return true;
    }
}
