<?php

class ModelExtensionChip extends Model
{
  public function getMethod($address)
    {
        $this->load->language('chip/chip');
        if ($this->config->get('chip_status')) {
            $query = $this->db->query(
                "SELECT * 
                 FROM ".$this->db->table("zones_to_locations")." 
                 WHERE location_id = '".(int)$this->config->get('chip_location_id')."' 
                    AND country_id = '".(int)$address['country_id']."' 
                    AND (zone_id = '".(int)$address['zone_id']."' OR zone_id = '0')"
            );

            if (!$this->config->get('chip_location_id')) {
                $status = true;
            } elseif ($query->num_rows) {
                $status = true;
            } else {
                $status = false;
            }
        } else {
            $status = false;
        }

        $method_data = array();

        if ($status) {
            $method_data = array(
                'id'         => 'chip',
                'title'      => $this->language->get('text_title', 'chip/chip'),
                'sort_order' => $this->config->get('chip_sort_order'),
            );
        }

        return $method_data;
    }

    public function get_lock( $order_id ) {
      $this->db->query( "SELECT GET_LOCK('chip_payment_$order_id', 5);" );
    }

    public function release_lock( $order_id ) {
      $this->db->query( "SELECT RELEASE_LOCK('chip_payment_$order_id');" );
    }
}
