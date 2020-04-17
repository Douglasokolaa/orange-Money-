<?php

defined('BASEPATH') or exit('No direct script access allowed');

add_option(MODULE_NAME . 'access_token', '');
add_option(MODULE_NAME . 'expires_in', '');


$orangeNotifyEndpoint = "\n\$config['csrf_exclude_uris'] = array_merge(\$config['csrf_exclude_uris'],";
$orangeNotifyEndpoint .= "['orange_money/notify/([0-9a-z]+)\/([0-9a-z]+)']);\n" ;
$orangeNotifyEndpoint .= "\$config['csrf_exclude_uris'] = array_unique(\$config['csrf_exclude_uris']);\n\n";
if(!(strpos(file_get_contents(APPPATH. 'config/config.php'),$orangeNotifyEndpoint))){
    file_put_contents(APPPATH. 'config\config.php',$orangeNotifyEndpoint,FILE_APPEND);
}

$CI = &get_instance();

$CI->db->query(

    "CREATE TABLE IF NOT EXISTS " . db_prefix() . "orange_transactions (

        `order_id` varchar(100) NOT NULL UNIQUE,

        `amount` varchar(50) NOT NULL,

        `notif_token` varchar(255) NOT NULL,

        `tranx_time` DATETIME NOT NULL,

        PRIMARY KEY (`order_id`)

        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;"

);
