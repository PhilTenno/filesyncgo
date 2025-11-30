<?php

declare(strict_types=1);

$GLOBALS['TL_DCA']['tl_filesync_tokens'] = [
    'config' => [
        'dataContainer' => 'Table',
        'switchToEdit'  => false,
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
            ],
        ],
    ],

    'list' => [
        'sorting' => [
            'mode' => 1,
            'fields' => ['id'],
            'flag' => 1,
            'panelLayout' => 'search,limit',
        ],
        'label' => [
            'fields' => ['name', 'tstamp'],
            'format' => '%s (ID: %s)',
        ],
        'global_operations' => [
            'all' => [
                'label'      => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href'       => 'act=select',
                'class'      => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()"',
            ],
        ],
        'operations' => [
            'edit' => [
                'label' => &$GLOBALS['TL_LANG']['tl_filesync_tokens']['edit'],
                'href'  => 'act=edit',
                'icon'  => 'edit.svg',
            ],
            'delete' => [
                'label' => &$GLOBALS['TL_LANG']['tl_filesync_tokens']['delete'],
                'href'  => 'act=delete',
                'icon'  => 'delete.svg',
            ],
            'show' => [
                'label' => &$GLOBALS['TL_LANG']['tl_filesync_tokens']['show'],
                'href'  => 'act=show',
                'icon'  => 'show.svg',
            ],
        ],
    ],

    'palettes' => [
        'default' => 'name;token_hash',
    ],

    'fields' => [
        'id' => [
            'sql' => "int(10) unsigned NOT NULL auto_increment",
        ],

        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],

        'name' => [
            'label'     => ['Name', 'Bezeichnung zur Identifikation des Tokens'],
            'inputType' => 'text',
            'exclude'   => true,
            'search'    => true,
            'eval'      => ['mandatory'=>true, 'maxlength'=>128, 'tl_class'=>'w50'],
            'sql'       => "varchar(128) NOT NULL default ''",
        ],

        // In der DB wird hier der Hash gespeichert (nicht das Klartext-Token).
        'token_hash' => [
            'label'     => ['Token', 'Das Token wird beim Speichern gehasht und hier gespeichert. Klartext wird nicht persistiert.'],
            // password -> masked input in Backend
            'inputType' => 'password',
            'exclude'   => true,
            'eval'      => [
                'mandatory' => true,
                'maxlength' => 255,
                'doNotTrim' => true,
                // Hinweis: save_callback muss das Klartext-Token hashen.
            ],
            // Save callback: Implementieren z.B. \PhilTenno\FilesyncGo\Dca\TokenDca::hashToken
            'save_callback' => [
                ['\\PhilTenno\\FilesyncGo\\Dca\\TokenDca', 'hashToken'],
            ],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
    ],
];