<?php

declare(strict_types=1);

$GLOBALS['TL_DCA']['tl_filesync_rate_entries'] = [
    'config' => [
        'dataContainer' => 'Table',
        'switchToEdit'  => false,
        'enableVersioning' => false,
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'token_id' => 'index',
            ],
        ],
    ],

    'list' => [
        'sorting' => [
            'mode' => 1,
            'fields' => ['window_start DESC'],
            'flag' => 5,
            'panelLayout' => 'filter;sort,limit',
        ],
        'label' => [
            'fields' => ['token_id', 'window_start', 'count'],
            'format' => 'TokenID %s — window %s — count %s',
        ],
        'operations' => [
            'delete' => [
                'label' => &$GLOBALS['TL_LANG']['tl_filesync_rate_entries']['delete'],
                'href'  => 'act=delete',
                'icon'  => 'delete.svg',
            ],
            'show' => [
                'label' => &$GLOBALS['TL_LANG']['tl_filesync_rate_entries']['show'],
                'href'  => 'act=show',
                'icon'  => 'show.svg',
            ],
        ],
    ],

    'palettes' => [
        'default' => 'token_id,window_start,count',
    ],

    'fields' => [
        'id' => [
            'sql' => "int(10) unsigned NOT NULL auto_increment",
        ],

        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],

        'token_id' => [
            'label'     => ['Token ID', 'Referenz auf tl_filesync_tokens.id'],
            'inputType' => 'text',
            'exclude'   => true,
            'eval'      => ['rgxp'=>'digit', 'doNotCopy'=>true, 'tl_class'=>'w50'],
            'sql'       => "int(10) unsigned NOT NULL default '0'",
        ],

        'window_start' => [
            'label'     => ['Window Start', 'Start (Unix Timestamp) des 24h Rolling-Window'],
            'inputType' => 'text',
            'exclude'   => true,
            'eval'      => ['rgxp'=>'digit', 'tl_class'=>'w50'],
            'sql'       => "int(10) unsigned NOT NULL default '0'",
        ],

        'count' => [
            'label'     => ['Count', 'Anzahl Requests im Window'],
            'inputType' => 'text',
            'exclude'   => true,
            'eval'      => ['rgxp'=>'digit', 'tl_class'=>'w50'],
            'sql'       => "int(10) unsigned NOT NULL default '0'",
        ],
    ],
];