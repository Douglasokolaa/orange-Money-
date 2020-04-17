<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Orange_money extends App_Controller
{
    public function success($id, $hash)
    {

        check_invoice_restrictions($id, $hash);
        $this->load->library("orange_money_gateway");
        $accessToken        = $this->orange_money_gateway->hash_token();
        $data['order_id']   = $this->session->userdata(MODULE_NAME . 'order_id');
        $data['amount']     = $this->session->userdata(MODULE_NAME . 'amount');
        $data['pay_token']  = $this->session->userdata(MODULE_NAME . 'pay_token');

        if (!$this->check_payment_exist($data['order_id'])) {

            $verify = check_status($data, $accessToken);

            if (isset($verify->status) && ($verify->status === "SUCCESS") && ($verify->order_id == $data['order_id'])) {
                $this->record($data['amount'], $id, $data['order_id']);
            }
        }

        $this->delete_record($this->session->userdata(MODULE_NAME . 'order_id'));
        $this->unset_orange_session();
        redirect(site_url("invoice/{$id}/{$hash}"));
    }

    public function cancel($id, $hash)
    {
        $this->delete_record($this->session->userdata(MODULE_NAME . 'order_id'));
        $this->unset_orange_session();
        redirect(site_url("invoice/{$id}/{$hash}"));
    }

    public function unset_orange_session()
    {
        $this->session->unset_userdata(MODULE_NAME . 'notif_token');
        $this->session->unset_userdata(MODULE_NAME . 'pay_token');
        $this->session->unset_userdata(MODULE_NAME . 'order_id');
        $this->session->unset_userdata(MODULE_NAME . 'amount');
    }

    private function record($amount, $id, $transId)
    {
        $success = $this->orange_money_gateway->addPayment(
            [
                'amount'        => $amount,
                'invoiceid'     => $id,
                'transactionid' => $transId,
                'paymentmethod' => 'Orange Money',
            ]
        );
        if ($success) {
            log_activity('online_payment_recorded_success');
            set_alert('success', _l('online_payment_recorded_success'));
        } else {
            log_activity('online_payment_recorded_success_fail_database' . var_export($this->input->get(), true));
            set_alert('success', _l('online_payment_recorded_success_fail_database'));
        }
    }

    private function check_tranx_exist($order_id)
    {
        $this->load->model('orange_money_model', 'orangeDb');
        return   $this->orangeDb->is_invoice_pending($order_id);
    }

    private function check_payment_exist($order_id)
    {
        $this->load->model('orange_money_model', 'orangeDb');
        return $this->orangeDb->is_payment_record($order_id);
    }

    private function delete_record($order_id)
    {
        $this->load->model('orange_money_model', 'orangeDb');
        $this->orangeDb->delete_pending_invoice($order_id);
    }

    public function notify($id, $hash)
    {
        $payload = @file_get_contents('php://input');
        $payload = json_decode($payload,true);
        if (empty($payload) || !isset($payload['notif_token']) || $payload['status'] != 'SUCCESS') {
            header("HTTP/1.1 404 ");
            header("Status: 404 Not Found");
            die();
        }
        
        check_invoice_restrictions($id,$hash);
        $this->load->model('orange_money_model','orangeDB');
        $valid = $this->orangeDB->get_tranx($payload['notif_token']);

        if ($valid) {
            $this->record($valid->amount,$id,$valid->order_id);
            $this->delete_record($payload['notif_token']);
        }
      
        header("HTTP/1.1 200 OK");
    }
}
