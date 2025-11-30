<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;

/**
 * DCA: tl_settings - filesyncgo token field
 *
 * Place this file at: src/Resources/contao/dca/tl_settings.php
 * It uses PaletteManipulator (same pattern as your News‑Pull extension).
 */

/*
 * Add legend + field to the default palette
 */
PaletteManipulator::create()
    ->addLegend('filesyncgo_legend', 'aliases_legend', PaletteManipulator::POSITION_BEFORE)
    ->addField('filesyncgo_token', 'filesyncgo_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_settings');

/*
 * Field definition
 *
 * Note: 'sql' provided as array so Contao Install/Manager reliably detects the DB column.
 * load_callback / save_callback reference the TokenManager service methods.
 */
$GLOBALS['TL_DCA']['tl_settings']['fields']['filesyncgo_token'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_settings']['filesyncgo_token'],
    'exclude' => true,
    'inputType' => 'text',
    'eval' => [
        'maxlength' => 32,
        'tl_class' => 'w50',
        'decodeEntities' => true,
        'placeholder' => 'Max 32 URL-safe chars',
    ],
    'load_callback' => [
        ['PhilTenno\\FileSyncGo\\Service\\TokenManager', 'loadTokenCallback'],
    ],
    'save_callback' => [
        ['PhilTenno\\FileSyncGo\\Service\\TokenManager', 'saveTokenCallback'],
    ],
    'sql' => [
        'type' => 'string',
        'length' => 255,
        'default' => '',
    ],
];