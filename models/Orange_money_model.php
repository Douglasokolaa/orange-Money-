<?php

defined('BASEPATH') or exit('No direct script access allowed');
class Orange_money_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function create_pending_invoice($data)
    {
        $time = date('Y-m-d H:i:s');
        $pdata = array(
            'order_id' => $data['order_id'],
            'amount' => $data['amount'],
            'notif_token' => $data['token'],
            'tranx_time' => $time
        );
      //  var_dump($data); exit ;
        $this->db->insert(db_prefix().'orange_transactions',$pdata);

        if ($this->db->affected_rows() > 0) {
            return true;
        }
        return false;

    }

    public function is_invoice_pending($notif_token)
    {
        return  (bool) total_rows(db_prefix() . 'orange_transactions', ['notif_token' => $notif_token]) > 0;
    }

    public function delete_pending_invoice($order_id)
    {
        $this->db->where('notif_token', $order_id);
        $this->db->delete(db_prefix() . 'orange_transactions');
    }

    public function is_payment_record($transactionid)
    {
        return  (bool) total_rows(db_prefix() . 'invoicepaymentrecords', ['transactionid' => $transactionid]) > 0;
    }
    
    public function get_tranx($notif_token)
    {
        $this->db->where('notif_token',$notif_token);
        $result = $this->db->get(db_prefix().'orange_transactions')->row();
        return $result;
    }
}   