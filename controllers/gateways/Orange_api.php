<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Orange_api extends App_Controller
{

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
            header("HTTP/1.1 400 ");
            header("Status: 400 Bad Request");
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
