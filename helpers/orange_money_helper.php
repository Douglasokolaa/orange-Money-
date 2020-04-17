<?php

use function GuzzleHttp\json_decode;

defined('BASEPATH') or exit('No direct script access allowed');

function get_valid_token($consumer_key)
{
    $token = get_option(MODULE_NAME . 'access_token');
    $expires_in = get_option(MODULE_NAME . 'expires_in');
    $today_date = time();

    if ($expires_in === '' || (int) $today_date <= (int) $expires_in) {
        $token = get_new_token($consumer_key);
    }

    return $token;
}

function request_token($consumer_key)
{
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.orange.com/oauth/v2/token",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => "grant_type=client_credentials",
        //  CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => array(
            "Authorization: Basic {$consumer_key}",
            "Content-Type: application/x-www-form-urlencoded"
        ),
    ));

    $response = curl_exec($curl);

    if (curl_errno($curl)) {
        $error_msg = curl_error($curl);
    }

    curl_close($curl);
    return json_decode($response);
}


function get_new_token($consumer_key)
{
    $response = request_token($consumer_key);

    if ($response) {
        if (!isset($response->error)) {
            $token = $response->access_token;
            $expires_in = $response->expires_in;
            update_option(MODULE_NAME . 'access_token', $token);
            update_option(MODULE_NAME . 'expires_in', $expires_in);
        } else {
            set_alert("warning", var_export(json_decode(json_encode($response), true)));
            log_activity(var_export(json_decode(json_encode($response), true)));
        }
        return $token;
    }
}

function post_transaction($accessToken, $data)
{

    $postData = json_encode($data);


    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.orange.com/orange-money-webpay/dev/v1/webpayment",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CUSTOMREQUEST => "POST",
        //      CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_HTTPHEADER => array(
            "Accept:  application/json",
            "Authorization:  Bearer {$accessToken}",
            "Content-Type:  application/json",
        ),
    ));

    $response = curl_exec($curl);

    if (curl_errno($curl)) {
        $error_msg = curl_error($curl);
    }

    curl_close($curl);

    return $response;
}

function check_status($data, $accessToken)
{
    $data = json_encode($data);
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.orange.com/orange-money-webpay/dev/v1/transactionstatus",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_FOLLOWLOCATION => true,
        //     CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_HTTPHEADER => array(
            "Accept:  application/json",
            "Authorization: Bearer {$accessToken}",
            "Content-Type:  application/json",
        ),
    ));

    $response = curl_exec($curl);

    if (curl_errno($curl)) {
        $error_msg = curl_error($curl);
    }

    curl_close($curl);

    $response = json_decode($response);
    return $response;
}
