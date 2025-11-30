<?php

use Contao\DC_Table;
use Contao\DataContainer;
use Contao\CoreBundle\DataContainer\PaletteManipulator;

/**
 * DCA: tl_filesync_tokens
 *
 * Place in: resources/dca/tl_filesync_tokens.php
 */

$GLOBALS['TL_DCA']['tl_filesync_tokens'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'enableVersioning' => true,
        'switchToEdit' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'tstamp' => 'index',
            ],
        ],
    ],

    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_SORTED,
            'fields' => ['tstamp'],
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'panelLayout' => 'search,limit',
        ],
        'label' => [
            // show masked token (last_chars) in the list: "xxxx...abcd"
            'fields' => ['last_chars'],
            'format' => 'xxxx... %s',
        ],
        'global_operations' => [
            'all' => [
                'label' => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
            ],
        ],
        'operations' => [
            'edit' => [
                'label' => &$GLOBALS['TL_LANG']['tl_filesync_tokens']['edit'],
                'href' => 'act=edit',
                'icon' => 'edit.svg',
            ],
            'delete' => [
                'label' => &$GLOBALS['TL_LANG']['tl_filesync_tokens']['delete'],
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\'{{\'DELETE%27|translate}}\'))return false;Backend.getScrollOffset()"',
            ],
            'show' => [
                'label' => &$GLOBALS['TL_LANG']['tl_filesync_tokens']['show'],
                'href' => 'act=show',
                'icon' => 'show.svg',
            ],
        ],
    ],

    'palettes' => [
        'default' => '{title_legend},last_chars;{token_legend},token_hash',
    ],

    'fields' => [
        'id' => [
            'sql' => ['type' => 'integer', 'unsigned' => true, 'autoincrement' => true],
        ],

        'tstamp' => [
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
        ],

        // Stored hash column (token_hash). In the backend we accept plaintext input,
        // then hash it in save_callback before persisting into this column.
        'token_hash' => [
            'label' => &$GLOBALS['TL_LANG']['tl_filesync_tokens']['token_hash'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => [
                'maxlength' => 255,
                'tl_class' => 'w50',
                // do not show the stored hash; load_callback will return empty string
            ],
            'load_callback' => [
                // TokenManager::loadTokenCallback returns '' so hash isn't exposed
                ['PhilTenno\\FileSyncGo\\Service\\TokenManager', 'loadTokenCallback'],
            ],
            'save_callback' => [
                // TokenDca::hashToken hashes plaintext and returns hash for storage
                ['PhilTenno\\FileSyncGo\\Dca\\TokenDca', 'hashToken'],
            ],
            'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
        ],

        // Non-sensitive last chars for masked display
        'last_chars' => [
            'label' => &$GLOBALS['TL_LANG']['tl_filesync_tokens']['last_chars'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['readonly' => true, 'tl_class' => 'w50'],
            'sql' => ['type' => 'string', 'length' => 10, 'default' => ''],
        ],
    ],
];