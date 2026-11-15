<?php

declare(strict_types=1);

namespace Dotclear\Plugin\tinyPacker;

use Dotclear\App;
use Dotclear\Helper\Process\TraitProcess;
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
class Backend
{
    use TraitProcess;

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
                    'plugin-deactivate',
                    'theme-activate',
                    'theme-deactivate',
                ]) ? (new Submit([My::id() . '[' . Html::escapeHTML($id) . ']'], __('Pack')))->render() : '';
            },
            'adminModulesListDoActions' => function (ModulesList $list, array $modules, string $type): void {
                # Pack action
                $post = $_POST[My::id()] ?? null;
                if (empty($post) || !is_array($post)) {
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
                $modules = array_keys($post);
                $id      = $modules[0];

                $module = $list->modules->getDefine((string) $id);
                if (!$module->isDefined()) {
                    throw new Exception(__('No such module.'));
                }

                # Packages names
                $version = is_string($version = $module->get('version')) && $version !== '' ? $version : '0';
                $files   = [
                    $type . '-' . $id . '.zip',
                    $type . '-' . $id . '-' . Files::tidyFileName($version) . '.zip',
                ];

                # Create zip
                $replaced = [];
                foreach ($files as $file) {
                    @set_time_limit(300);
                    $path = $dir . DIRECTORY_SEPARATOR . $file;
                    if (file_exists($path)) {
                        $replaced[] = $file;
                    }

                    $fp = @fopen($path, 'wb');
                    if ($fp === false) {
                        throw new Exception(sprintf(__('Unable to write package %s.'), $file));
                    }
                    $zip = new Zip($fp);

                    foreach (My::TINYPACKER_EXCLUDE as $e) {
                        $zip->addExclusion(sprintf(
                            '#(^|/)(%s)(/|$)#',
                            $e
                        ));
                    }

                    $zip->addDirectory((string) Path::real(is_string($root = $module->get('root')) ? $root : ''), (string) $id, true);
                    $zip->write();
                    $zip->close();
                    unset($zip, $fp);
                }

                # Remove packages of older versions of this module
                $removed = [];
                foreach (glob($dir . DIRECTORY_SEPARATOR . $type . '-' . $id . '-*.zip') ?: [] as $old) {
                    $name = basename($old);
                    if ($name !== $files[1] && preg_match('/^' . preg_quote($type . '-' . $id . '-', '/') . '[0-9][^\/]*\.zip$/', $name) && @unlink($old)) {
                        $removed[] = $name;
                    }
                }

                # Report packages, with links
                $url   = App::blog()->settings()->get('system')->getStr('public_url') . '/' . My::TINYPACKER_DIR . '/';
                $links = array_map(fn (string $file): string => '<a href="' . Html::escapeURL($url . $file) . '">' . Html::escapeHTML($file) . '</a>', $files);
                Notices::addSuccessNotice(
                    sprintf(__('Packages created in the blog public directory: %s'), implode(', ', $links)) .
                    ($replaced === [] ? '' : ' ' . sprintf(__('Replaced packages: %s'), Html::escapeHTML(implode(', ', $replaced)))) .
                    ($removed === [] ? '' : ' ' . sprintf(__('Removed packages of older versions: %s'), Html::escapeHTML(implode(', ', $removed)))),
                    ['divtag' => true]
                );

                # Back to the list, with current filters
                $queries = [];
                if (is_string($search = $list->getSearch()) && $search !== '') {
                    $queries['m_search'] = $search;
                }
                if (isset($_REQUEST['m_nav']) && is_string($_REQUEST['m_nav']) && $_REQUEST['m_nav'] !== '') {
                    $queries['m_nav'] = $_REQUEST['m_nav'];
                }
                Http::redirect($list->getURL($queries, true, null, false));
            },
        ]);

        return true;
    }
}
