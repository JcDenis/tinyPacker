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
use adminModulesList;
use dcCore;
use dcNsProcess;
use dcPage;
use Dotclear\Helper\File\Zip\Zip;

/* clearbricks */
use files;
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
class Backend extends dcNsProcess
{
    /** @var string Public packages folder */
    public const TINYPACKER_DIR = 'packages';

    /** @var array Excluded files and dirs */
    public const TINYPACKER_EXCLUDE = [
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

    public static function init(): bool
    {
        static::$init = defined('DC_CONTEXT_ADMIN') && dcCore::app()->auth->isSuperAdmin();

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        dcCore::app()->addBehaviors([
            'adminModulesListGetActions' => function (adminModulesList $list, string $id, array $_): string {
                return in_array($list->getList(), [
                    'plugin-activate',
                    'theme-activate',
                ]) ? sprintf(
                    '<input type="submit" name="%s[%s]" value="Pack" />',
                    self::id(),
                    html::escapeHTML($id)
                ) : '';
            },
            'adminModulesListDoActions' => function (adminModulesList $list, array $modules, string $type): void {
                # Pack action
                if (empty($_POST[self::id()])
                 || !is_array($_POST[self::id()])) {
                    return;
                }

                # Repository directory
                $dir = (string) path::real(
                    dcCore::app()->blog->public_path . DIRECTORY_SEPARATOR . self::TINYPACKER_DIR,
                    false
                );
                if (!empty($dir) && !is_dir($dir)) {
                    files::makeDir($dir, true);
                }
                if (empty($dir) || !is_writable($dir)) {
                    throw new Exception(__('Destination directory is not writable.'));
                }

                # Module to pack
                $modules = array_keys($_POST[self::id()]);
                $id      = $modules[0];

                if (!$list->modules->moduleExists($id)) {
                    throw new Exception(__('No such module.'));
                }
                $module = $list->modules->getModules($id);

                # Packages names
                $files = [
                    $type . '-' . $id . '.zip',
                    $type . '-' . $id . '-' . $module['version'] . '.zip',
                ];

                # Create zip
                foreach ($files as $file) {
                    @set_time_limit(300);

                    $zip = new Zip($dir . '/' . $file);

                    foreach (self::TINYPACKER_EXCLUDE as $e) {
                        $zip->addExclusion(sprintf(
                            '#(^|/)(%s)(/|$)#',
                            $e
                        ));
                    }

                    $zip->addDirectory((string) path::real($module['root']), $id, true);
                    $zip->close();
                    unset($zip);
                }

                dcPage::addSuccessNotice(
                    __('Task successfully executed.')
                );
                http::redirect($list->getURL());
            },
        ]);

        return true;
    }

    private static function id(): string
    {
        return basename(dirname(__DIR__));
    }
}
