<?php

declare(strict_types=1);

namespace Dotclear\Plugin\tinyPacker;

use Dotclear\App;
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
 * @brief       tinyPacker admin class.
 * @ingroup     tinyPacker
 *
 * Add action and button to modules lists.
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Backend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        App::behavior()->addBehaviors([
            'adminModulesListGetActions' => function (ModulesList $list, string $id, array $_): string {
                return in_array($list->getList(), [
                    'plugin-activate',
                    'theme-activate',
                ]) ? (new Submit([My::id() . '[' . Html::escapeHTML($id) . ']']))->__call('value', [__('Pack')])->render() : '';
            },
            'adminModulesListDoActions' => function (ModulesList $list, array $modules, string $type): void {
                # Pack action
                if (empty($_POST[My::id()])
                 || !is_array($_POST[My::id()])) {
                    return;
                }

                # Repository directory
                $dir = (string) Path::real(
                    App::blog()->publicPath() . DIRECTORY_SEPARATOR . My::TINYPACKER_DIR,
                    false
                );
                if (!empty($dir) && !is_dir($dir)) {
                    Files::makeDir($dir, true);
                }
                if (empty($dir) || !is_writable($dir)) {
                    throw new Exception(__('Destination directory is not writable.'));
                }

                # Module to pack
                $modules = array_keys($_POST[My::id()]);
                $id      = $modules[0];

                $module = $list->modules->getDefine((string) $id);
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

                    foreach (My::TINYPACKER_EXCLUDE as $e) {
                        $zip->addExclusion(sprintf(
                            '#(^|/)(%s)(/|$)#',
                            $e
                        ));
                    }

                    $zip->addDirectory((string) Path::real($module->get('root')), (string) $id, true);
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
}
