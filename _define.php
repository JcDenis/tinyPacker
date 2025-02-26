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
    '1.4.3',
    [
        'requires'    => [['core', '2.28']],
        'permissions' => 'My',
        'type'        => 'plugin',
        'support'     => 'https://github.com/JcDenis/' . basename(__DIR__) . '/issues',
        'details'     => 'https://github.com/JcDenis/' . basename(__DIR__) . '/src/branch/master/README.md',
        'repository'  => 'https://github.com/JcDenis/' . basename(__DIR__) . '/raw/branch/master/dcstore.xml',
    ]
);
