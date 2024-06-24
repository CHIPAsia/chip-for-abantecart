<?php
class ModelExtensionChip extends Model {
  public $error = array();

  public function getTimezoneList() {
    $list_time_zones = DateTimeZone::listIdentifiers( DateTimeZone::ALL );

    $formatted_time_zones = array();
    foreach ( $list_time_zones as $mtz ) {
      $formatted_time_zones[]= [
        'timezone' => $mtz,
        'name' => str_replace( "_"," ",$mtz ),
      ];
    }
    
    return $formatted_time_zones;
  }
}