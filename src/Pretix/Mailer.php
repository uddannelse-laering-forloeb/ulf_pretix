<?php

namespace Drupal\ulf_pretix\Pretix;

/**
 * Mailer.
 */
class Mailer {
  const PRETIX_EVENT_ORDER_PAID_TEMPLATE = 'ulf_pretix_event_order_paid_template';
  const PRETIX_EVENT_ORDER_CANCELED_TEMPLATE = 'ulf_pretix_event_order_canceled_template';

  /**
   * Create an instance.
   */
  public static function create() {
    return new static();
  }

  /**
   * Render mail.
   *
   * @param string $key
   *   The key.
   * @param array $message
   *   The message.
   * @param array $params
   *   The params.
   */
  public function render($key, array &$message, array $params) {
    $template = variable_get($key, '');

    switch ($key) {
      case self::PRETIX_EVENT_ORDER_PAID_TEMPLATE:
      case self::PRETIX_EVENT_ORDER_CANCELED_TEMPLATE:
        $message['subject'] = $params['subject'] ?? $key;
        $message['body'] = '<p>' . $template . '</p>';
        if (isset($params['content'])) {
          $message['body'] .= $params['content'];
        }
        break;
    }
  }

  /**
   * Send mail.
   *
   * @see drupal_mail()
   */
  public function send($key, $to, $language, array $params, $from = NULL, $send = TRUE) {
    return drupal_mail('ulf_pretix', $key, $to, $language, $params, $from, $send);
  }

}
