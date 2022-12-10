<?php
/**
 * @brief tinyPacker, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugin
 *
 * @author Jean-Christian Denis
 *
 * @copyright Jean-Christian Denis
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
if (!defined('DC_CONTEXT_ADMIN')) {
    return null;
}

dcCore::app()->addBehavior(
    'adminModulesListGetActions',
    function ($list, $id, $_) {
        if (!in_array($list->getList(), [
            'plugin-activate',
            'theme-activate',
        ])) {
            return null;
        }

        return
        '<input type="submit" name="tinypacker[' .
        html::escapeHTML($id) . ']" value="Pack" />';
    }
);

dcCore::app()->addBehavior(
    'adminModulesListDoActions',
    function ($list, $modules, $type) {
        # Pack action
        if (empty($_POST['tinypacker'])
         || !is_array($_POST['tinypacker'])) {
            return null;
        }

        $modules = array_keys($_POST['tinypacker']);
        $id      = $modules[0];

        # Repository directory
        $dir = path::real(
            dcCore::app()->blog->public_path . '/' . (
                defined('TINYPACKER_SUBDIR') ? TINYPACKER_SUBDIR : 'packages'
            ),
            false
        );

        if (!is_dir($dir)) {
            files::makeDir($dir, true);
        }
        if (!is_writable($dir)) {
            throw new Exception(__('Destination directory is not writable.'));
        }

        # Module to pack
        if (!$list->modules->moduleExists($id)) {
            throw new Exception(__('No such module.'));
        }
        $module = $list->modules->getModules($id);

        # Excluded files and dirs
        $exclude = [
            '\.',
            '\.\.',
            '__MACOSX',
            '\.svn',
            '\.hg.*?',
            '\.git.*?',
            'CVS',
            '\.directory',
            '\.DS_Store',
            'Thumbs\.db',
            '_disabled',
        ];

        # Packages names
        $files = [
            $type . '-' . $id . '.zip',
            $type . '-' . $id . '-' . $module['version'] . '.zip',
        ];

        # Create zip
        foreach ($files as $f) {
            @set_time_limit(300);
            $fp = fopen($dir . '/' . $f, 'wb');

            $zip = new fileZip($fp);

            foreach ($exclude as $e) {
                $zip->addExclusion(sprintf(
                    '#(^|/)(%s)(/|$)#',
                    $e
                ));
            }

            $zip->addDirectory($module['root'], $id, true);
            $zip->write();
            $zip->close();
            unset($zip);
        }

        dcPage::addSuccessNotice(
            __('Task successfully executed.')
        );
        http::redirect($list->getURL());
    }
);
