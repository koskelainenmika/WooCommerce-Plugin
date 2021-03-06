<?php

namespace WooCommerceCustobar;

defined('ABSPATH') or exit;

use WooCommerceCustobar\Synchronization\ProductSync;
use WooCommerceCustobar\Synchronization\CustomerSync;
use WooCommerceCustobar\Synchronization\SaleSync;

/**
 * Class DataUpload
 *
 * @package WooCommerceCustobar
 */
class DataUpload {

  public static function uploadCustobarData($endpoint, $data) {

    $responseData = new \stdClass;

    $body = json_encode($data);
    $apiToken = \WC_Admin_Settings::get_option( 'custobar_api_setting_token', false );
    $companyDomain = \WC_Admin_Settings::get_option( 'custobar_api_setting_company', false );
    $url = sprintf('https://%s.custobar.com/api', $companyDomain) . $endpoint;

    $response = wp_remote_request($url, array(
      'method' => 'POST',
      'headers' => array(
        'Content-Type'  => 'application/json',
        'Authorization' => 'Token ' . $apiToken
      ),
      'body' => $body
    ));

    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);

    // form response data
    $responseData->code = $response_code;
    $responseData->body = $response_body;

    // do wc logging
    if (!in_array($response_code, array(200, 201)) || is_wp_error($response_body)) {
      wc_get_logger()->warning('Custobar data upload failed', array(
        'source'        => 'woocommerce-custobar'
      ));
    } else {
      wc_get_logger()->info('Sent request to Custobar API, $body: ' . print_r($body,1), array(
        'source'        => 'woocommerce-custobar'
      ));
    }

    // return response
    return $responseData;

  }

  public static function addHooks() {
    add_action( 'wp_ajax_custobar_export', __CLASS__ . '::jxExport' );
    add_action( 'wp_ajax_custobar_api_test', __CLASS__ . '::apiTest' );
  }

  public static function jxExport() {

    // environment checks
    $plugin = new Plugin();
    if ($plugin::isWooCommerceActived() && $plugin::hasAllSettingsDefined()) {

      $recordType = sanitize_text_field( $_POST['recordType'] );
      switch( $recordType ) {
        case 'customer':
          $apiResponse = CustomerSync::batchUpdate();
          $apiResponse->stats = self::fetchSyncStatCustomers();
          break;
        case 'sale':
          $apiResponse = SaleSync::batchUpdate();
          $apiResponse->stats = self::fetchSyncStatSales();
          break;
        case 'product':
          $apiResponse = ProductSync::batchUpdate();
          $apiResponse->stats = self::fetchSyncStatProducts();
          break;
      }

      $apiResponse->recordType = $recordType;

    } else {
      $response = array(
        'code' => 420
      );
      print json_encode( $response );
      wp_die();
    }

    if( $apiResponse ) {
      print json_encode( $apiResponse );
    } else {
      $response = array(
        'code' => 440
      );
      print json_encode( $response );
    }


    wp_die();

  }

  public static function fetchSyncStatProducts() {

    $stat = new \stdClass;

    $products = wc_get_products(array('limit' => -1));
    $stat->total = count( $products );

    $tracker = ProductSync::trackerFetch();
    $stat->synced = count( $tracker['data'] );
    if( $stat->total > 0 ) {
      $stat->synced_percent = number_format(($stat->synced / $stat->total) * 100) . '%';
    } else {
      $stat->synced_percent = '-';
    }

    $updatedTimestamp = $tracker['updated'];
    if( $updatedTimestamp ) {
      $stat->updated = date('Y-m-d g:i:sA', $updatedTimestamp);
    } else {
      $stat->updated = '-';
    }

    return $stat;

  }

  public static function fetchSyncStatSales() {

    $stat = new \stdClass;

    $orders = \wc_get_orders(array(
      'posts_per_page' => -1,
      'orderby'        => 'date',
      'order'          => 'ASC',
    ));
    $salesCount = 0;
    foreach ($orders as $order) {
      foreach ($order->get_items() as $order_item) {
        $salesCount++;
      }
    }
    $stat->total = $salesCount;

    $tracker = SaleSync::trackerFetch();
    $stat->synced = count( $tracker['data'] );

    if( $stat->total > 0 ) {
      $stat->synced_percent = number_format(($stat->synced / $stat->total) * 100) . '%';
    } else {
      $stat->synced_percent = '-';
    }

    $updatedTimestamp = $tracker['updated'];
    if( $updatedTimestamp ) {
      $stat->updated = date('Y-m-d g:i:sA', $updatedTimestamp);
    } else {
      $stat->updated = '-';
    }

    return $stat;

  }

  public static function fetchSyncStatCustomers() {

    $stat = new \stdClass;
    $tracker = CustomerSync::trackerFetch();

    $orders = \wc_get_orders(array(
      'posts_per_page' => -1,
      'orderby'        => 'date',
      'order'          => 'ASC',
    ));
    $customerIds = [];
    foreach ($orders as $order) {
      $customerIds[] = $order->get_user_id();
    }
    $customerIds = array_unique( $customerIds );

    $stat->total = count( $customerIds );
    $stat->synced = count( $tracker['data'] );
    if( $stat->total > 0 ) {
      $stat->synced_percent = number_format(($stat->synced / $stat->total) * 100) . '%';
    } else {
      $stat->synced_percent = '-';
    }
    $updatedTimestamp = $tracker['updated'];
    if( $updatedTimestamp ) {
      $stat->updated = date('Y-m-d g:i:sA', $updatedTimestamp);
    } else {
      $stat->updated = '-';
    }

    return $stat;

  }

  public static function apiTest() {

    $apiToken = \WC_Admin_Settings::get_option( 'custobar_api_setting_token', false );
    $companyDomain = \WC_Admin_Settings::get_option( 'custobar_api_setting_company', false );
    $url = sprintf('https://%s.custobar.com/api', $companyDomain) . '/data/customers/';

    $response = wp_remote_request($url, array(
      'method' => 'GET',
      'headers' => array(
        'Content-Type'  => 'application/json',
        'Authorization' => 'Token ' . $apiToken
      )
    ));

    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);

    if( $response_code == 200 ) {
      $message = "Successful test, your site is connected to Custobar.";
    } else {
      $message = "Sorry the test failed, please check your API token and domain and try again. If the problems persists please contact Custobar support.";
    }

    $response = array(
      'url'     => $url,
      'code'    => $response_code,
      'body'    => $response_body,
      'message' => $message
    );
    print json_encode( $response );

    wp_die();

  }

}
