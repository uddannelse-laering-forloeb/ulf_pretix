<?php

namespace Drupal\ulf_pretix\Controller;

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

    $payload = json_decode(file_get_contents('php://input'));

    if (empty($payload)) {
      throw new \RuntimeException('Invalid or empty payload');
    }

    // @TODO Use payload to fetch data from pretix and do stuff.

    return $payload;
  }

}
