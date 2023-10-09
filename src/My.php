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

use Dotclear\App;
use Dotclear\Module\MyPlugin;

class My extends MyPlugin
{
    /**
     * Public packages folder.
     *
     * @var     string  TINYPACKER_DIR
     */
    public const TINYPACKER_DIR = 'packages';

    /**
     * Excluded files and dirs.
     *
     * @var     array<int,string>   TINYPACKER_EXCLUDE
     */
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

    public static function checkCustomContext(int $context): ?bool
    {
        // Only backend and super admin
        return $context === self::INSTALL ? null : App::task()->checkContext('BACKEND') && App::auth()->isSuperAdmin();
    }
}
