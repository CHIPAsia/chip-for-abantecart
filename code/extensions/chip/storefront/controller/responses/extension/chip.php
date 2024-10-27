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
        $comment      = sprintf($this->language->get('order_confirm_comment', 'chip_chip'), $brand_id);

        $this->model_checkout_order->confirm($order_id, $order_status, $comment );

        $redirect_url = $this->html->getSecureURL('extension/chip/redirect_url', '&order_id=' . $order_id);
        $callback_url = $this->html->getSecureURL('extension/chip/callback_url');

        $order_info = $this->model_checkout_order->getOrder($order_id);

        $currency = $this->config->get( 'config_currency' );
        $order_total = $order_info['total'];
        if ( $this->config->get('chip_automatic_currency_conversion') == '1' AND $this->currency->has( 'MYR' ) ) {
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
            'due_strict'     => $this->config->get('chip_due_strict') == '1',
            'timezone'       => $this->config->get('chip_timezone'),
            'currency'       => $currency,
            'products'       => [],
          ],
          'brand_id' => $brand_id,
          'client' => [
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

        if ( empty( $params['client']['email'] ) ) {
          $params['client']['email'] = $this->config->get('chip_email_fallback');
        }

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
          $currency = $this->config->get( 'config_currency' );
          $price = $product['price'];
          if ( $this->config->get('chip_automatic_currency_conversion') == '1' AND $this->currency->has( 'MYR' ) ) {
            $price = $this->currency->convert($price, $currency, 'MYR');
            $currency = 'MYR';
          }

          $price = round( $price * 100 );
          
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

        $chip = ChipApiCurl::get_instance($secret_key, $brand_id);
        $payment = $chip->create_payment($params);

        if ( array_key_exists('id', $payment) ) {
          $this->model_checkout_order->updatePaymentMethodData($order_id, $payment);

          $payment = array_intersect_key(
            $payment, 
            array_flip(['id', 'checkout_url', 'is_test'])
          );
        }

        $purchase_id_comment_text = $this->language->get('purchase_id_comment', 'chip_chip');
        $purchase_id_comment = sprintf($purchase_id_comment_text, $payment['id']);
        $this->model_checkout_order->addHistory($order_id, $order_status, $purchase_id_comment);

        if ( $payment['is_test'] ) {
          $test_mode_comment_text = $this->language->get('test_mode_comment', 'chip_chip');
          $test_mode_comment = sprintf($test_mode_comment_text, $payment['id']);
          $this->model_checkout_order->addHistory($order_id, $order_status, $test_mode_comment);
        }

        $this->response->addJSONHeader();
        $this->response->setOutput(AJson::encode($payment));
    }

  public function redirect_url() {
    if (!$this->request->is_GET()) {
      exit('Only accept GET request');
    }

    $order_id = (int)$this->request->get_or_post('order_id');
    $success_id = $this->config->get('chip_status_success_paid');

    $this->load->model( 'checkout/order' );
    $this->load->model( 'extension/chip' );
    
    $this->model_extension_chip->get_lock($order_id);

    $order_info = $this->model_checkout_order->getOrder($order_id);
    if (!$order_info) {
      return null;
    }

    if (!has_value($order_info['payment_method_data'])) {
      return null;
    }

    if ($order_info['order_status_id'] == $success_id) {
      redirect($this->html->getSecureURL('checkout/finalize'));
    }

    $payment_method_data = unserialize( $order_info['payment_method_data'] );

    $purchase_id = $payment_method_data['id'];
    $chip = ChipApiCurl::get_instance( $this->config->get( 'chip_api_secret' ), '' );
    $payment = $chip->get_payment( $purchase_id );
    $this->model_checkout_order->updatePaymentMethodData($order_id, $payment);

    if ( $payment['status'] == 'paid' ) {
      $purchase_paid_comment_text = $this->language->get('purchase_paid_comment', 'chip_chip');
      $purchase_paid_comment = sprintf($purchase_paid_comment_text, $payment['id']);
      
      $notify_customer = $this->config->get( 'chip_notify_customer_on_success' ) == '1';
      $this->model_checkout_order->update($order_id, $success_id, $purchase_paid_comment, $notify_customer);

      $this->model_extension_chip->release_lock($order_id);

      redirect($this->html->getSecureURL('checkout/finalize'));
    }

    $pKey = $this->session->data['fc']['product_key'];
    redirect($this->html->getSecureURL('checkout/fast_checkout', $pKey ? '&fc=1&product_key='.$pKey : ''));
  }

  public function callback_url() {
    if ($this->request->is_GET()) {
      redirect($this->html->getNonSecureURL('index/home'));
    }

    if ( !isset($_SERVER['HTTP_X_SIGNATURE']) ) {
      exit('No X Signature received from headers');
    }

    if ( empty($content = file_get_contents('php://input')) ) {
      exit('No input received');
    }

    $this->load->library('json');
    $webhook = AJson::decode($content, true);

    $event_type = $webhook['event_type'];

    if (!in_array($event_type, ['purchase.paid'])) {
      exit('No supported event type');
    }

    $public_key = $this->config->get('chip_public_key');

    if ( openssl_verify( $content,  base64_decode($_SERVER['HTTP_X_SIGNATURE']), $public_key, 'sha256WithRSAEncryption' ) != 1 ) {
      // This verification method cannot be used due to AbanteCart cleaning of $_SERVER variable
      // header( 'Forbidden', true, 403 );
      // exit('Invalid X Signature');
    }

    $chip = ChipApiCurl::get_instance( $this->config->get( 'chip_api_secret' ), '' );
    $webhook = $chip->get_payment( $webhook['id'] );

    if ($webhook['status'] != 'paid') {
      exit('Status is not paid');
    }

    $this->load->model( 'checkout/order' );
    $this->load->model( 'extension/chip' );

    $order_id = (int)$webhook['reference'];
    $this->model_extension_chip->get_lock($order_id);
    $order_info = $this->model_checkout_order->getOrder($order_id);
    
    if ($order_info['order_status_id'] == $this->config->get('chip_status_success_paid')) {
      exit;
    }

    $this->model_checkout_order->updatePaymentMethodData($order_id, $webhook);

    if ( $webhook['status'] == 'paid' ) {
      $purchase_paid_comment_text = $this->language->get('purchase_paid_comment', 'chip_chip');
      $purchase_paid_comment = sprintf($purchase_paid_comment_text, $webhook['id']);
      
      $notify_customer = $this->config->get( 'chip_notify_customer_on_success' ) == '1';
      $this->model_checkout_order->update($order_id, $this->config->get('chip_status_success_paid'), $purchase_paid_comment, $notify_customer);

      $this->model_extension_chip->release_lock($order_id);
    }
  }
}
