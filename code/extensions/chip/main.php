<?php
if ( !defined ( 'DIR_CORE' )) {
    header ( 'Location: static_pages/' );
}

$controllers = [
  'storefront' => [
      // 'responses/extension/chip',
  ],
  'admin' => [],
];

$models = [
  'storefront' => [
    'extension/chip',
  ],
  'admin' => [],
];

$languages = [
  'storefront' => [],
  'admin' => [
    'chip/chip'
  ]
];

$templates = [
  'storefront' => [],
  'admin'      => [],
];