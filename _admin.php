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
declare(strict_types=1);

namespace Dotclear\Plugin\tinyPacker;

/* dotclear */
use dcCore;
use dcPage;

/* clearbricks */
use files;
use fileZip;
use html;
use http;
use path;

/* php */
use Exception;

/**
 * tinyPacker admin class
 *
 * Add action and button to modules lists.
 */
class Admin
{
    private static $init = false;

    public static function init(): bool
    {
        if (defined('DC_CONTEXT_ADMIN')) {
            dcPage::checkSuper();
            self::$init = true;
        }

        return self::$init;
    }

    public static function process(): bool
    {
        if (!self::$init) {
            return false;
        }

        dcCore::app()->addBehavior(
            'adminModulesListGetActions',
            function ($list, $id, $_) {
                return in_array($list->getList(), [
                    'plugin-activate',
                    'theme-activate',
                ]) ? sprintf(
                    '<input type="submit" name="%s[%s]" value="Pack" />',
                    basename(__DIR__),
                    html::escapeHTML($id)
                ) : null;
            }
        );

        dcCore::app()->addBehavior(
            'adminModulesListDoActions',
            function ($list, $modules, $type) {
                # Pack action
                if (empty($_POST[basename(__DIR__)])
                 || !is_array($_POST[basename(__DIR__)])) {
                    return null;
                }

                # Repository directory
                $dir = path::real(
                    dcCore::app()->blog->public_path . '/packages',
                    false
                );

                if (!is_dir($dir)) {
                    files::makeDir($dir, true);
                }
                if (!is_writable($dir)) {
                    throw new Exception(__('Destination directory is not writable.'));
                }

                # Module to pack
                $modules = array_keys($_POST[basename(__DIR__)]);
                $id      = $modules[0];

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

        return true;
    }
}

/* process */
if (Admin::init()) {
    Admin::process();
}
