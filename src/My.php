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
use Dotclear\Module\MyPlugin;

class My extends MyPlugin
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

    public static function checkCustomContext(int $context): ?bool
    {
        return defined('DC_CONTEXT_ADMIN') && dcCore::app()->auth->isSuperAdmin();
    }
}
