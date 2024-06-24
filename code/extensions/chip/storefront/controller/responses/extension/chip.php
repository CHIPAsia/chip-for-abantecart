<?php

class ControllerResponsesExtensionChip extends AController
{
    public function main()
    {

        $item = $this->html->buildElement(
            [
                'type'  => 'button',
                'name'  => 'back',
                'style' => 'button',
                'text'  => $this->language->get('button_back'),
            ]
        );
        $this->view->assign('button_back', $item);

        $item = $this->html->buildElement(
            [
                'type'  => 'button',
                'name'  => 'checkout',
                'style' => 'button btn-primary',
                'text'  => $this->language->get('button_confirm'),
            ]
        );
        $this->view->assign('button_confirm', $item);

        $this->view->assign('text_wait', $this->language->get('text_wait'));

        $this->view->assign('back', $this->html->getSecureURL(
                ($this->request->get['rt'] == 'checkout/guest_step_3'
                    ? 'checkout/guest_step_2'
                    : 'checkout/payment'),
                '&mode=edit', true));
        $this->processTemplate('responses/chip.tpl');
    }

    public function api()
    {
        $data = [];

        $data['text_note'] = $this->language->get('text_note');
        $data['process_rt'] = 'chip/api_confirm';

        $this->load->library('json');
        $this->response->setOutput(AJson::encode($data));
    }

    public function api_confirm()
    {
        $data = [];

        $this->confirm();
        $data['success'] = 'completed';

        
    }

    public function confirm()
    {
        $this->load->model('checkout/order');
        $this->load->library('json');
        
        $secret_key = $this->config->get('chip_api_secret');
        $brand_id   = $this->config->get('chip_brand_id');
        $pm_white   = $this->config->get('chip_payment_method_whitelist');
        $send_rec   = $this->config->get('chip_send_receipt') == '1';
        
        $success_redirect = $this->config->get('chip_success_redirect');
        $success_callback = $this->config->get('chip_success_callback');

        $order_status = $this->config->get('config_order_status_id');
        $order_id     = $this->session->data['order_id'];
        // Todo: Make this string translatable
        $comment      = 'Attempt to create purchase with Brand ID: ' . $brand_id;

        $this->model_checkout_order->confirm($order_id, $order_status, $comment );

        $redirect_url = $this->html->getSecureURL('extension/chip/redirect_url', '&order_id=' . $order_id);
        $callback_url = $this->html->getSecureURL('extension/chip/callback_url');

        $order_info = $this->model_checkout_order->getOrder($order_id);

        // Todo: Make switch button to toggle enable/disable automatic conversion
        $currency = $this->config->get( 'config_currency' );
        $order_total = $order_info['total'];
        if ( $this->currency->has( 'MYR' ) ) {
          $order_total = $this->currency->convert($order_total, $currency, 'MYR');
          $currency = 'MYR';
        }

        $params = [
          'success_callback' => $callback_url,
          'success_redirect' => $redirect_url,
          'failure_redirect' => $redirect_url,
          'cancel_redirect'  => $redirect_url,
          'send_receipt'     => $send_rec,
          'creator_agent'    => 'AbanteCart: 1.0.0',
          'reference'        => $order_id,
          // Todo: update to abantecart
          'platform'         => 'web',
          'purchase' => [
            'total_override' => round( $order_total * 100 ),
            'due_strict'     => $this->config->get('due_strict') == '1',
            'timezone'       => 'Asia/Kuala_Lumpur',
            'currency'       => $currency,
            'products'       => [],
          ],
          'brand_id' => $brand_id,
          'client' => [
            // Todo: add option for merchant to set email fallback
            'email'                   => $order_info['email'],
            'phone'                   => substr( $order_info['telephone'], 0, 32 ),
            'full_name'               => substr( $order_info['payment_firstname'] . ' ' . $order_info['payment_lastname'], 0, 128 ),
            'street_address'          => substr( $order_info['payment_address_1'] . ' ' . $order_info['payment_address_2'], 0, 128 ),
            'country'                 => substr( $order_info['payment_iso_code_2'], 0, 2 ),
            'city'                    => substr( $order_info['payment_city'], 0, 128 ),
            'zip_code'                => substr( $order_info['payment_postcode'], 0, 32 ),
            'state'                   => substr( $order_info['payment_zone'], 0, 128 ),
            'shipping_street_address' => substr( $order_info['shipping_address_1'] . ' ' . $order_info['shipping_address_2'], 0, 128 ),
            'shipping_country'        => substr( $order_info['shipping_iso_code_2'], 0, 2 ),
            'shipping_city'           => substr( $order_info['shipping_city'], 0, 128 ),
            'shipping_zip_code'       => substr( $order_info['shipping_postcode'], 0, 32 ),
            'shipping_state'          => substr( $order_info['shipping_zone'], 0, 128 ),
          ],
        ];

        // Remove empty key pairs
        foreach ( $params['client'] as $key => $value ) {
          if ( empty( $value ) ) {
            unset( $params['client'][$key] );
          }
        }

        if ( !empty( $order_info['comment'] ) ) {
          $params['purchase']['notes'] = substr( $order_info['comment'], 0, 10000 );
        }

        $order_product_query = $this->db->query(
          "SELECT *
          FROM ".$this->db->table("order_products")."
          WHERE order_id = '".(int) $order_id."'"
        );

        foreach ($order_product_query->rows as $product) {
          $price = round( $product['price'] * 100 );
          
          if ( $price < 0 ) {
            $price = 0;
          }

          $product_name = $product['name'];
          if ( !empty( $product['sku'] ) ) {
            $product_name .= ' | ' . $product['sku'];
          }

          $params['purchase']['products'][] = array(
            'name'     => substr( $product_name, 0, 256 ),
            'price'    => $price,
            'quantity' => $product['quantity']
          );
        }

        if ( $success_redirect == '1' ) {
          unset( $params['success_redirect'] );
        }

        if ( $success_callback == '1' ) {
          unset( $params['success_callback'] );
        }

        if ( $pm_white != '0' ) {
          $payment_method_whitelist = unserialize($pm_white);
          foreach ( ['razer_atome', 'razer_grabpay', 'razer_tng', 'razer_shopeepay','razer_maybankqr'] as $ewallet ) {
            if ( in_array($ewallet, $payment_method_whitelist ) ) {
              if ( !in_array( 'razer', $payment_method_whitelist ) ) {
                $payment_method_whitelist[]= 'razer';
                break;
              }
            }
          }
          $params['payment_method_whitelist'] = $payment_method_whitelist;
        }

        // $this->load->model('extension/chip');
        // $this->model_extension_chip->

        $chip = ChipApiCurl::get_instance($secret_key, $brand_id);
        $payment = $chip->create_payment($params);

        if ( array_key_exists('id', $payment) ) {
          $payment = array_intersect_key(
            $payment, 
            array_flip(['id', 'checkout_url'])
          );
        }

        // $this->config->get('chip_status_success_paid')
        $this->response->addJSONHeader();
        $this->response->setOutput(AJson::encode($payment));
    }
}
