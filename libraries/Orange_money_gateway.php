<?php

defined('BASEPATH') or exit('No direct script access allowed');

class orange_money_gateway extends App_gateway
{
    public function __construct()
    {
        $this->ci = &get_instance();

        /**
         * REQUIRED
         * Gateway unique id
         * The ID must be alpha/alphanumeric
         */
        $this->setId('orange_money');

        /**
         * REQUIRED
         * Gateway name
         */
        $this->setName('Orange Money');

        /**
         * Add gateway settings
         */
        $this->setSettings(
            [
                [
                    'name'      => 'consumer_key',
                    'encrypted' => true,
                    'label'     => 'Consumer Key',
                ],
                [
                    'name'      => 'merchant_key',
                    'encrypted' => true,
                    'label'     => 'Merchant key',
                ],
                [
                    'name'      => 'merchant_name',
                    'label'     => 'Merchant name',
                ],
                [
                    'name'             => 'currencies',
                    'label'            => 'settings_paymentmethod_currencies',
                    'default_value'    => 'OUV',
                ],
                [
                    'name'          => 'test_mode_enabled',
                    'type'          => 'yes_no',
                    'default_value' => 1,
                    'label'         => 'settings_paymentmethod_testing_mode',
                ],
                [
                    'name'      => 'test_consumer_key',
                    'label'     => 'Test Consumer Key',
                ],
                [
                    'name'      => 'test_merchant_key',
                    'label'     => 'Test Merchant key',
                ],
            ]
        );
    }


    /**
     * REQUIRED FUNCTION
     * @param  array $data
     * @return mixed
     */

    public function process_payment($data)
    {
        $id   = $data["invoiceid"];
        $hash = $data["hash"];
// var_dump($data); die;
        check_invoice_restrictions($id, $hash);

        $accessToken    = $this->hash_token();
        $merchantkey    = $this->merchant_key();
        $merchantName   = $this->merchant_name();
        $currency       = $data['invoice']->currency_name;
        $order_id       = $this->tx_ref($data);

        $url            = $this->url($id, $hash,$order_id);

        $defaultLang    = $data['invoice']->client->default_language;
        $lang           = (($defaultLang == "") || ($defaultLang === 'french'))  ? 'fr' : 'en';

        $payData = array(
            "merchant_key"  => $merchantkey,
            "currency"      => $currency,
            "order_id"      => $order_id,
            "amount"        => $data['amount'],
            "return_url"    => $url["return"],
            "cancel_url"    => $url["cancel"],
            "notif_url"     => $url["notify"],
            "lang"          => $lang,
            "reference"     => $merchantName,
        );

        $process = post_transaction($accessToken, $payData);
        $process = json_decode($process);

        if (isset($process->status) &&  $process->status === 201) {
            $this->ci->session->set_userdata([
                MODULE_NAME . 'notif_token' => $process->notif_token,
                MODULE_NAME . 'pay_token' => $process->pay_token,
                MODULE_NAME . 'order_id' => $order_id,
                MODULE_NAME . 'amount' => $data['amount']
            ]);

            $this->create(array(
                'order_id' => $order_id,
                'amount' => $data['amount'],
                 'token' =>  $process->notif_token
            ));

            redirect($process->payment_url);
        } else {
            $code = (isset($process->status)) ? $process->status : $process->code;
            $message = "orange Money : payment Failed \n ";
            $message .= " code : " . $code;
            $message .= " \n message : " . $process->message;
            $message .= " \n description : " . $process->description;
            log_activity($message);

            set_alert("warning", $process->description);
        }
    }

    /**
     * Gets consumer key for all environments
     * @param  null
     * @return string
     */
    public function consumer_key()
    {
        return $this->getSetting('test_mode_enabled') == '1' ? $this->getSetting('test_consumer_key') : $this->decryptSetting('consumer_key');
    }


    /**
     * Gets merchant key for all environments
     * @param null
     * @return string
     */
    public function merchant_key()
    {
        return $this->getSetting('test_mode_enabled') == '1' ? $this->getSetting('test_merchant_key') : $this->decryptSetting('merchant_key');
    }

    /**
     * Gets merchant key for all environments
     * @param null
     * @return string
     */
    public function merchant_name()
    {
        return $this->getSetting('merchant_name');
    }

    /**
     * Gets merchant key for all environments
     * @param null
     * @return string
     */
    public function currency()
    {
        return $this->getSetting('currencies');
    }

    /**
     * gentransaction referrence FUNCTION
     * @param  array $data
     * @return string
     */
    public function tx_ref($data)
    {
        $tx_ref = $data['invoice']->id . '-' . time();
        return $tx_ref;
    }

    /**
     * get valid access token
     * @param null
     * @return string
     */
    public function hash_token()
    {
        $this->ci->load->helper('orange_money/orange_money');
        $consumer_key = $this->consumer_key();
        return get_valid_token($consumer_key);
    }

    public function url($id, $hash,$order_id)
    {

        $url = array(
            'return' => site_url("orange_money/success/{$id}/{$hash}"),
            'cancel' => site_url("orange_money/cancel/{$id}/{$hash}"),
            'notify' => site_url("orange_money/gateways/orange_api/notify/{$id}/{$hash}")
        );

        return $url;
    }

    public function create($data)
    {
        $this->ci->load->model('orange_money/orange_money_model', 'orangeDb');
        return   $this->ci->orangeDb->create_pending_invoice($data);
    }

    public function api_url()
    {
        return $this->getSetting('test_mode_enabled') == '1' ? "https://api.orange.com/orange-money-webpay/dev/" :  "https://api.orange.com/orange-money-webpay/gn/" ;
    }
}
