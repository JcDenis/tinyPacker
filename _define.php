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

if (!isset($this) || !is_object($this) || !method_exists($this, 'registerModule') || !isset($this->id) || !is_string($this->id)) {
    return;
}

$this->registerModule(
    'Tiny packer',
    'Quick pack theme or plugin into public dir',
    'Jean-Christian Denis',
    '1.7',
    [
        'requires'    => [['core', '2.39']],
        'permissions' => 'My',
        'type'        => 'plugin',
        'support'     => 'https://github.com/JcDenis/' . $this->id . '/issues',
        'details'     => 'https://github.com/JcDenis/' . $this->id . '/',
        'repository'  => 'https://raw.githubusercontent.com/JcDenis/' . $this->id . '/master/dcstore.xml',
        'date'        => '2026-11-15T15:00:07+00:00',
    ]
);
