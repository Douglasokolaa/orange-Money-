<?php
/**
 * Ensures that the module init file can't be accessed directly, only within the application.
 */
defined('BASEPATH') or exit('No direct script access allowed');
/*
Module Name: Orange Money
Description: Orange Money module for invoice payment.
Author: Boxvibe Technologies 
Author URI: https://www.boxvibe.com
Version: 1.0.0
Requires at least: 2.4.1
*/
define('MODULE_NAME','orange_money');
register_payment_gateway(MODULE_NAME.'_gateway', MODULE_NAME);
register_activation_hook('orange_money','activate_orange_money');

function activate_orange_money(){
    require __DIR__. '/install.php';
}