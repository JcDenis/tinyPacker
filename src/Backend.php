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

use dcCore;
use Dotclear\Core\Process;
use Dotclear\Core\Backend\ModulesList;
use Dotclear\Core\Backend\Notices;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\File\Zip\Zip;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Exception;

/**
 * tinyPacker admin class.
 *
 * Add action and button to modules lists.
 */
class Backend extends Process
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
        return self::status(defined('DC_CONTEXT_ADMIN') && dcCore::app()->auth->isSuperAdmin());
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        dcCore::app()->addBehaviors([
            'adminModulesListGetActions' => function (ModulesList $list, string $id, array $_): string {
                return in_array($list->getList(), [
                    'plugin-activate',
                    'theme-activate',
                ]) ? (new Submit([self::id() . '[' . Html::escapeHTML($id) . ']']))->value(__('Pack'))->render() : '';
            },
            'adminModulesListDoActions' => function (ModulesList $list, array $modules, string $type): void {
                # Pack action
                if (empty($_POST[self::id()])
                 || !is_array($_POST[self::id()])) {
                    return;
                }

                # Repository directory
                $dir = (string) Path::real(
                    dcCore::app()->blog->public_path . DIRECTORY_SEPARATOR . self::TINYPACKER_DIR,
                    false
                );
                if (!empty($dir) && !is_dir($dir)) {
                    Files::makeDir($dir, true);
                }
                if (empty($dir) || !is_writable($dir)) {
                    throw new Exception(__('Destination directory is not writable.'));
                }

                # Module to pack
                $modules = array_keys($_POST[self::id()]);
                $id      = $modules[0];

                $module = $list->modules->getDefine($id);
                if (!$module->isDefined()) {
                    throw new Exception(__('No such module.'));
                }

                # Packages names
                $files = [
                    $type . '-' . $id . '.zip',
                    $type . '-' . $id . '-' . $module->get('version') . '.zip',
                ];

                # Create zip
                foreach ($files as $file) {
                    @set_time_limit(300);
                    $fp  = fopen($dir . DIRECTORY_SEPARATOR . $file, 'wb');
                    $zip = new Zip($fp);

                    foreach (self::TINYPACKER_EXCLUDE as $e) {
                        $zip->addExclusion(sprintf(
                            '#(^|/)(%s)(/|$)#',
                            $e
                        ));
                    }

                    $zip->addDirectory((string) Path::real($module->get('root')), $id, true);
                    $zip->write();
                    $zip->close();
                    unset($zip, $fp);
                }

                Notices::addSuccessNotice(
                    __('Task successfully executed.')
                );
                Http::redirect($list->getURL());
            },
        ]);

        return true;
    }

    private static function id(): string
    {
        return basename(dirname(__DIR__));
    }
}
