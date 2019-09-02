<?php

namespace Drupal\ulf_pretix\Controller;

use Drupal\ulf_pretix\Pretix\Helper;

/**
 * Webhook controller.
 */
class WebhookController {

  /**
   * Handle pretix webhook.
   *
   * @see https://docs.pretix.eu/en/latest/api/webhooks.html#receiving-webhooks
   */
  public function handle($organizerSlug) {
    if ('POST' !== $_SERVER['REQUEST_METHOD']) {
      throw new \RuntimeException('Invalid request');
    }

    $payload = json_decode(file_get_contents('php://input'), TRUE);

    if (empty($payload)) {
      throw new \RuntimeException('Invalid or empty payload');
    }

    if (isset($payload['action'])) {
      switch ($payload['action']) {
        case 'pretix.event.order.placed':
        case 'pretix.event.order.placed.require_approval':
          break;

        case 'pretix.event.order.paid':
          return $this->handleOrderPaid($payload);

        case 'pretix.event.order.canceled':
          return $this->handleOrderCancelled($payload);

        case 'pretix.event.order.expired':
        case 'pretix.event.order.modified':
        case 'pretix.event.order.contact.changed':
        case 'pretix.event.order.changed.*':
        case 'pretix.event.order.refund.created.externally':
        case 'pretix.event.order.approved':
        case 'pretix.event.order.denied':
        case 'pretix.event.checkin':
        case 'pretix.event.checkin.reverted':
          break;
      }
    }

    // @TODO Use payload to fetch data from pretix and do stuff.

    return $payload;
  }

  /**
   * Handle order paid.
   */
  private function handleOrderPaid(array $payload) {
    $organizerSlug = $payload['organizer'] ?? NULL;
    $eventSlug = $payload['event'] ?? NULL;
    $orderCode = $payload['code'] ?? NULL;

    $result = db_select('ulf_pretix_events', 'p')
      ->fields('p')
      ->condition('pretix_organizer_slug', $organizerSlug, '=')
      ->condition('pretix_event_slug', $eventSlug, '=')
      ->execute()
      ->fetch();

    if (isset($result->nid)) {
      $helper = Helper::create();
      $node = node_load($result->nid);
      $client = $helper->getPretixClient($node);
      $result = $client->getOrder($organizerSlug, $eventSlug, $orderCode);

      if (isset($result->error)) {
        return $this->apiError($result, 'Cannot get order');
      }

      $order = $result->data;

      $result = $client->getItems($eventSlug);

      if (isset($result->error)) {
        return $this->apiError($result, 'Cannot get event items');
      }

      $items = $result->data;

      header('Content-type: text/plain'); echo var_export($order, TRUE); die(__FILE__ . ':' . __LINE__ . ':' . __METHOD__);
    }
  }

  /**
   * Handle order cancelled.
   */
  private function handleOrderCancelled($payload) {
    return $payload;
  }

  /**
   * Report API error.
   */
  private function apiError($result, $message) {
    watchdog('ulf_pretix', 'Error: %message: %code %error', [
      '%message' => $message,
      '%code' => $result->code,
      '%error' => $result->error ?? NULL,
      '%data' => $result->data ?? NULL,
    ], WATCHDOG_ERROR);

    return ['error' => $message, 'result' => $result];
  }

}
