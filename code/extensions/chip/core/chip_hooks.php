<?php

class ExtensionChip extends Extension
{
    protected $registry;
    protected $r_data;

    public function __construct()
    {
        $this->registry = Registry::getInstance();
    }

    protected function _is_enabled($that)
    {
        return $that->config->get('chip_status');
    }

    //Hook to extension edit in the admin
    public function onControllerPagesExtensionExtensions_UpdateData()
    {
        $that = $this->baseObject;
        $current_ext_id = $that->request->get['extension'];
        if (IS_ADMIN === true && $current_ext_id == 'chip' && $this->baseObject_method == 'edit') {
            $html = '<a class="btn btn-white tooltips" target="_blank" href="https://gate.chip-in.asia" title="Visit CHIP Dashboard">
                        <i class="fa fa-external-link fa-lg"></i>
                    </a>';
            $that->view->addHookVar('extension_toolbar_buttons', $html);
        }
    }

    //Hook to enable payment details tab in admin
    public function onControllerPagesSaleOrderTabs_UpdateData()
    {
        $that = $this->baseObject;
        $order_id = $that->data['order_id'];
        //are we logged in and in admin?
        if (IS_ADMIN && $that->user->isLogged()) {
            //check if tab is not yet enabled.
            if (in_array('payment_details', $that->data['groups'])) {
                return null;
            }
            //check if we this order is used CHIP payment
            $that->load->model('checkout/order', 'storefront');
            $order_info = $that->model_checkout_order->getOrder($order_id);

            if (!has_value($order_info['payment_method_data'])) {
              return;
            }
            
            $payment_method_data = unserialize( $order_info['payment_method_data'] );
            
            if (!isset($payment_method_data['id']) OR !isset($payment_method_data['checkout_url'])) {
              return;
            }
            $that->data['groups'][] = 'payment_details';
            $that->data['link_payment_details'] = $that->html->getSecureURL('sale/order/payment_details', '&order_id='.$order_id.'&extension=chip');
            //reload main view data with updated tab
            $that->view->batchAssign($that->data);
        }
    }

    //Hook to payment details page to show information
    public function onControllerPagesSaleOrder_UpdateData()
    {
        $that = $this->baseObject;

        $order_id = $that->request->get['order_id'];
        //are we logged to admin and correct method called?
        if (IS_ADMIN && $that->user->isLogged() && $this->baseObject_method == 'payment_details') {

            if($that->request->get['extension'] != 'chip'){
                return null;
            }

            //build HTML to show
            $that->load->language('chip/chip');
            $that->load->model('extension/chip');

            $that->load->model('checkout/order');
            $order_info = $that->model_checkout_order->getOrder($order_id);

            if (!has_value($order_info['payment_method_data'])) {
              return;
            }
            
            $payment_method_data = unserialize( $order_info['payment_method_data'] );
            
            if (!isset($payment_method_data['id']) OR !isset($payment_method_data['checkout_url'])) {
              return;
            }

            $view = new AView($this->registry, 0);
            
            $view->assign('order_id', $order_id);
            $view->assign('test_mode', $payment_method_data['test_mode']);
            $view->assign('checkout_url', $payment_method_data['checkout_url']);

            $view->batchAssign($that->language->getASet('chip/chip'));
            $this->baseObject->view->addHookVar('extension_payment_details',
                $view->fetch('pages/sale/chip_payment_details.tpl'));
        }

    }

    public function afterControllerPagesExtensionExtensions_ProcessData($nama_function) {
      $that = $this->baseObject;

      if (IS_ADMIN && $that->user->isLogged() && $this->baseObject_method == 'edit') {
        if($that->request->get['extension'] != 'chip'){
          return null;
        }
        if (!$that->request->is_POST()) {
          return null;
        }

        $this->insert_public_key($that);
      }
    }

    public function afterControllerResponsesListingGridExtension_UpdateData($nama_function) {
      $that = $this->baseObject;
      
      if (IS_ADMIN && $that->user->isLogged() && $this->baseObject_method == 'update') {
        if($that->request->get['id'] != 'chip'){
          return null;
        }
        
        if (!$that->request->is_POST()) {
          return null;
        }

        $this->insert_public_key($that);
      }
    }

    private function insert_public_key($that) {
        if ( !isset( $that->request->post['chip_api_secret'] ) ) {
          return;
        }

        $chip = ChipApiCurl::get_instance($that->request->post['chip_api_secret'], '');
        $public_key = $chip->public_key();

        if (is_array($public_key)) {
          return;
        }

        $webhook_public_key = str_replace( '\n', "\n", $public_key );

        $store_id = (int) $that->session->data['current_store_id'];
        if ($that->request->get_or_post('store_id')) {
            $store_id = $that->request->get_or_post('store_id');
        }

        $save_data = [
          'chip_public_key' => $webhook_public_key,
          'store_id' => $store_id,
        ];
        $that->extension_manager->editSetting('chip', $save_data);
    }
}