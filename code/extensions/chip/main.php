<?php
if ( !defined ( 'DIR_CORE' )) {
    header ( 'Location: static_pages/' );
}

$controllers = array(
  'storefront' => array(
      // 'responses/extension/chip',
  ),
  'admin' => array(),
);

$models = array(
  'storefront' => array(
    'extension/chip',
  ),
  'admin' => array(),
);

$languages = array(
  'storefront' => array(),
  'admin' => array(
      'chip/chip'));

$templates = [
  'storefront' => [],
  'admin'      => [],
];