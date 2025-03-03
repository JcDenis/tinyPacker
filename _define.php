<?php
/**
 * @file
 * @brief       The plugin tinyPacker definition
 * @ingroup     tinyPacker
 *
 * @defgroup    tinyPacker Plugin tinyPacker.
 *
 * Quick pack theme or plugin into public dir.
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

$this->registerModule(
    'Tiny packer',
    'Quick pack theme or plugin into public dir',
    'Jean-Christian Denis',
    '1.4.4',
    [
        'requires'    => [['core', '2.28']],
        'permissions' => 'My',
        'type'        => 'plugin',
        'support'     => 'https://github.com/JcDenis/' . $this->id . '/issues',
        'details'     => 'https://github.com/JcDenis/' . $this->id . '/',
        'repository'  => 'https://raw.githubusercontent.com/JcDenis/' . $this->id . '/master/dcstore.xml',
        'date'        => '2025-03-03T14:08:24+00:00',
    ]
);
