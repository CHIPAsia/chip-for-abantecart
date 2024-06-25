<?php
if ( !defined ( 'DIR_CORE' ) ) {
    header ( 'Location: static_pages/' );
}

if ( !class_exists( 'ChipApiCurl' ) ) {
  require( DIR_EXT.'chip/chip_api_curl.php' );
}

if ( !class_exists( 'ExtensionChip' ) ) {
  require_once( DIR_EXT.'chip/core/chip_hooks.php' );
}

$controllers = [
  'storefront' => [
    'responses/extension/chip',
  ],
  'admin' => [],
];

$models = [
  'storefront' => [
    'extension/chip',
  ],
  'admin' => [
    'extension/chip',
  ],
];

$languages = [
  'storefront' => [],
  'admin' => [
    'chip/chip'
  ]
];

$templates = [
  'storefront' => [
    'responses/chip.tpl',
  ],
  'admin'      => [],
];