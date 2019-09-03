<?php

namespace Drupal\ulf_pretix\Controller;

use Drupal\ulf_pretix\Pretix\Mailer;
use Drupal\ulf_pretix\Pretix\OrderHelper;

/**
 * Webhook controller.
 */
class WebhookController {

  /**
   * The pretix order helper.
   *
   * @var \Drupal\ulf_pretix\Pretix\OrderHelper
   */
  private $orderHelper;

  /**
   * The mailer.
   *
   * @var \Drupal\ulf_pretix\Pretix\Mailer
   */
  private $mailer;

  /**
   * Create a new instance.
   */
  public static function create() {
    return new static();
  }

  /**
   * Constructor.
   */
  public function __construct() {
    $this->orderHelper = OrderHelper::create();
    $this->mailer = Mailer::create();
  }

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

    $action = $payload['action'] ?? NULL;
    switch ($action) {
      case OrderHelper::PRETIX_EVENT_ORDER_PAID:
      case OrderHelper::PRETIX_EVENT_ORDER_CANCELED:
        return $this->handleOrderUpdated($payload, $action);
    }

    return $payload;
  }

  /**
   * Handle order updated.
   */
  private function handleOrderUpdated(array $payload, $key) {
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
      $node = node_load($result->nid);
      switch ($key) {
        case OrderHelper::PRETIX_EVENT_ORDER_PAID:
          $subject = t('New pretix order: @event_name', ['@event_name' => $node->title]);
          $mailKey = Mailer::PRETIX_EVENT_ORDER_PAID_TEMPLATE;
          break;

        case OrderHelper::PRETIX_EVENT_ORDER_CANCELED:
          $subject = t('pretix order canceled: @event_name', ['@event_name' => $node->title]);
          $mailKey = Mailer::PRETIX_EVENT_ORDER_CANCELED_TEMPLATE;
          break;

        default:
          return $payload;
      }

      $result = $this->orderHelper->setPretixClient($node)
        ->getOrder($organizerSlug, $eventSlug, $orderCode);

      if (isset($result->error)) {
        return $this->apiError($result, 'Cannot get order');
      }
      $order = $result->data;
      $orderLines = $this->orderHelper->getOrderLines($order);
      $content = $this->renderOrder($order, $orderLines);

      $wrapper = entity_metadata_wrapper('node', $node);
      $to = $wrapper->field_pretix_email_recipient->value();
      $language = LANGUAGE_NONE;

      $params = [
        'subject' => $subject,
        'content' => $content,
      ];

      $result = $this->mailer->send($mailKey, $to, $language, $params);

      return $payload;
    }
  }

  /**
   * Handle order canceled.
   */
  private function handleOrderCanceled($payload) {
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
      $node = node_load($result->nid);
      $result = $this->orderHelper->setPretixClient($node)
        ->getOrder($organizerSlug, $eventSlug, $orderCode);

      if (isset($result->error)) {
        return $this->apiError($result, 'Cannot get order');
      }
      $order = $result->data;
      $orderLines = $this->orderHelper->getOrderLines($order);
      $content = $this->renderOrder($order, $orderLines);

      $wrapper = entity_metadata_wrapper('node', $node);
      $to = $wrapper->field_pretix_email_recipient->value();
      $language = LANGUAGE_NONE;
      $params = [
        'subject' => t('Canceled pretix order: @event_name', ['@event_name' => $node->title]),
        'content' => $content,
      ];

      $result = $this->mailer->send(Mailer::PRETIX_EVENT_ORDER_CANCELED_TEMPLATE, $to, $language, $params);

      return $payload;
    }
  }

  /**
   * Render pretix order as plain text.
   *
   * @param object $order
   *   The pretix order.
   * @param object[] $orderLines
   *   The order lines.
   *
   * @return string
   *   The textual representation of the order.
   */
  private function renderOrder($order, array $orderLines) {
    $blocks = [];

    foreach ($orderLines as $line) {
      $block = [
        [
          $line->name['da'] ?? $line->name['en'],
        ],
        [
          t('Start time:'),
          format_date($line->date_from->getTimestamp(), 'long'),
        ],
        [
          t('Quantity:'),
          $line->quantity,
        ],
      ];

      if (isset($line->quotas)) {
        foreach ($line->quotas as $quota) {
          $block[] = [
            t('Availability:'),
            t('@available_number of @total_size', ['@available_number' => $quota->availability->available_number, '@total_size' => $quota->availability->total_size]),
          ];
        }
      }

      if ($line->item_price > 0) {
        $block[] = [
          t('Item price:'),
          number_format($line->item_price, 2),
        ];
        $block[] = [
          t('Total price:'),
          number_format($line->total_price, 2),
        ];
      }

      $block[] = [''];

      $blocks[] = $block;
    }

    return implode(PHP_EOL, array_map(function ($line) {
      return 2 === count($line)
        ? sprintf('%-16s%s', $line[0], $line[1])
        : sprintf('%s', $line[0]);
    }, array_merge(...$blocks)));
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
