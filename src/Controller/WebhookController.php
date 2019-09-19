<?php

namespace Drupal\ulf_pretix\Controller;

use Drupal\ulf_pretix\Pretix\Mailer;
use Drupal\ulf_pretix\Pretix\OrderHelper;
use Drupal\ulf_pretix\Pretix\EventHelper;

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
   * @param string $organizerSlug
   *   The organizer slug.
   *
   * @see https://docs.pretix.eu/en/latest/api/webhooks.html#receiving-webhooks
   *
   * @return array
   *   The payload.
   *
   * @throws \InvalidMergeQueryException
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
   *
   * @param array $payload
   *   The payload.
   * @param string $action
   *   The action.
   *
   * @return array
   *   The payload.
   *
   * @throws \InvalidMergeQueryException
   */
  private function handleOrderUpdated(array $payload, $action) {
    $organizerSlug = $payload['organizer'] ?? NULL;
    $eventSlug = $payload['event'] ?? NULL;
    $orderCode = $payload['code'] ?? NULL;

    $node = $this->orderHelper->getNode($organizerSlug, $eventSlug);

    if (NULL !== $node) {
      switch ($action) {
        case OrderHelper::PRETIX_EVENT_ORDER_PAID:
          $subject = t('New pretix order: @event_name',
            ['@event_name' => $node->title]);
          $mailKey = Mailer::PRETIX_EVENT_ORDER_PAID_TEMPLATE;
          break;

        case OrderHelper::PRETIX_EVENT_ORDER_CANCELED:
          $subject = t('pretix order canceled: @event_name',
            ['@event_name' => $node->title]);
          $mailKey = Mailer::PRETIX_EVENT_ORDER_CANCELED_TEMPLATE;
          break;

        default:
          return $payload;
      }

      $result = $this->orderHelper->setPretixClient($node)
        ->getOrder($organizerSlug, $eventSlug, $orderCode);
      if ($this->orderHelper->isApiError($result)) {
        return $this->orderHelper->apiError($result, 'Cannot get order');
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

      if (TRUE === $wrapper->field_pretix_synchronize->value()) {
        // @TODO Do we really need the availability info on sub-events?
        // Having it on the event should be more than enough.
        $subEvents = array_column($order->positions, 'subevent');
        $processed = [];
        foreach ($subEvents as $subEvent) {
          if (isset($processed[$subEvent->id])) {
            continue;
          }

          $result = $this->orderHelper->getSubEventAvailability($subEvent);
          if (!$this->orderHelper->isApiError($result)) {
            $subEventData['availability'] = $result->data->results;
          }
          if (!empty($subEventData)) {
            $info = $this->orderHelper->addPretixSubEventInfo(
              NULL,
              $subEvent,
              $subEventData);
          }
        }
      }

      // Update availability on event node.
      EventHelper::create()->updateEventAvailability($node);
    }

    return $payload;
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

    return implode(PHP_EOL, array_map(static function ($line) {
      return 2 === count($line)
        ? sprintf('%-16s%s', $line[0], $line[1])
        : sprintf('%s', $line[0]);
    }, array_merge(...$blocks)));
  }

}
