<?php

if (!defined('DIR_CORE')) {
  header('Location: static_pages/');
}

function settingsValidation($data)
{
  foreach ($data as $key => $value) {
    if ( $key == 'chip_api_secret' ) {
      if ( empty( $value ) ) {
        return array( 'result' => false, 'errors' => array( 'chip_api_secret' => 'Error: Secret key cannot be blank!' ) );
      }

      $chip = ChipApiCurl::get_instance($value, '');
      $public_key = $chip->public_key();

      if (is_array($public_key)) {
        return array( 'result' => false, 'errors' => array( 'chip_api_secret' => print_r($public_key['__all__'][0]['message'], true) ) );
      }

      $webhook_public_key = str_replace( '\n', "\n", $public_key );
      
      if ( !openssl_pkey_get_public( $webhook_public_key ) ) {
        return array( 'result' => false, 'errors' => array( 'chip_api_secret' => $public_key ) );
      }
    }
  }

  return array('result' => true, 'errors' => array());
}