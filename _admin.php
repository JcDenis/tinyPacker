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

if (!tinyPacker::repositoryDir($core)) {
    return null;
}

$core->addBehavior(
    'adminModulesListGetActions',
    ['tinyPacker', 'adminModulesGetActions']
);
$core->addBehavior(
    'adminModulesListDoActions',
    ['tinyPacker', 'adminModulesDoActions']
);

/**
 * @ingroup DC_PLUGIN_TINYPACKER
 * @brief Quick create packages of modules from admin to public dir.
 * @since 2.6
 */
class tinyPacker
{
    /**
     * Blog's public sub-directory where to put packages
     * @var string
     */
    public static $sub_dir = 'packages';

    /**
     * Add button to create package to modules lists
     * @param  object $list adminModulesList instance
     * @param  string $id    Module id
     * @param  arrray $_    Module properties
     * @return string       HTML submit button
     */
    public static function adminModulesGetActions($list, $id, $_)
    {
        if ($list->getList() != 'plugin-activate' 
         && $list->getList() != 'theme-activate') {
            return null;
        }

        return 
        '<input type="submit" name="tinypacker[' .
        html::escapeHTML($id) . ']" value="Pack" />';
    }

    /**
     * Create package on modules lists action
     * @param  object $list      adminModulesList instance
     * @param  array $modules    Selected modules ids
     * @param  string $type      List type (plugins|themes)
     * @throws Exception         If no public dir or module
     * @return null              Null
     */
    public static function adminModulesDoActions($list, $modules, $type)
    {
        # Pack action
        if (empty($_POST['tinypacker']) 
         || !is_array($_POST['tinypacker'])) {
            return null;
        }

        $modules = array_keys($_POST['tinypacker']);
        $id = $modules[0];

        # Repository directory
        if (($root = self::repositoryDir($list->core)) === false) {
            throw new Exception(
                __('Destination directory is not writable.'
            ));
        }

        # Module to pack
        if (!$list->modules->moduleExists($id)) {
            throw new Exception(__('No such module.'));
        }
        $module = $list->modules->getModules($id);

        # Excluded files and dirs
        $exclude = array(
            '\.',
            '\.\.',
            '__MACOSX',
            '\.svn',
            '\.hg.*?',
            '\.git.*?',
            'CVS',
            '\.directory',
            '\.DS_Store',
            'Thumbs\.db'
        );

        # Packages names
        $files = array(
            $type . '-' . $id . '.zip',
            $type . '-' . $id . '-' . $module['version'] . '.zip'
        );

        # Create zip
        foreach($files as $f) {

            @set_time_limit(300);
            $fp = fopen($root . '/' . $f, 'wb');

            $zip = new fileZip($fp);

            foreach($exclude AS $e) {
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

    /**
     * Check and create directories used by packer
     * @param  object $core        dcCore instance
     * @return string|boolean      Cleaned path or false on error
     */
    public static function repositoryDir($core)
    {
        $dir = path::real(
            $core->blog->public_path . '/' . tinyPacker::$sub_dir, 
            false
        );

        try {
            if (!is_dir($dir)) {
                files::makeDir($dir, true);
            }
            if (is_writable($dir)) {

                return $dir;
            }
        }
        catch(Exception $e) {}

        return false;
    }
}