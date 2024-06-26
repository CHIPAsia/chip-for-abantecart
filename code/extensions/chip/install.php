<?php
if (! defined ( 'DIR_CORE' )) {
header ( 'Location: static_pages/' );
}

$language_list = $this->model_localisation_language->getLanguages();

$rm = new AResourceManager();
$rm->setType('image');

$result = copy(
    DIR_EXT.'chip/image/paywithchip_logo_small.png',
    DIR_RESOURCE.'image/chip-paywithchip_all.png'
);

$resource = [
    'language_id'   => $this->config->get('storefront_language_id'),
    'name'          => [],
    'title'         => [],
    'description'   => [],
    'resource_path' => 'chip-paywithchip_all.png',
    'resource_code' => '',
];

foreach ($language_list as $lang) {
    $resource['name'][$lang['language_id']] = 'chip_icon.png';
    $resource['title'][$lang['language_id']] = 'chip_payment_storefront_icon';
    $resource['description'][$lang['language_id']] = 'Chip Storefront Icon';
}

$resource_id = $rm->addResource($resource);

if ($resource_id) {
  $settings['chip_payment_storefront_icon'] = $resource_id;
}