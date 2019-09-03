<?php

namespace Drupal\ulf_pretix\Pretix;

/**
 * Abstract helper.
 */
abstract class AbstractHelper {

  /**
   * The pretix client.
   *
   * @var \Drupal\ulf_pretix\Pretix\Client
   */
  protected $client;

  /**
   * Create an instance of the helper.
   */
  public static function create() {
    return new static();
  }

  /**
   * Get pretix client.
   */
  public function getPretixClient($node) {
    $wrapper = entity_metadata_wrapper('user', $node->uid);
    if (TRUE === $wrapper->field_pretix_enable->value()) {
      return new Client(
        $wrapper->field_pretix_url->value(),
        $wrapper->field_pretix_api_token->value(),
        $wrapper->field_pretix_organizer_slug->value()
      );
    }

    return NULL;
  }

  /**
   * Set pretix client.
   */
  public function setPretixClient($node) {
    $this->client = $this->getPretixClient($node);

    return $this;
  }

  /**
   * Report error.
   */
  protected function error($message) {
    watchdog('ulf_pretix', 'Error: %message', [
      '%message' => $message,
    ], WATCHDOG_ERROR);

    return ['error' => $message];
  }

  /**
   * Test if an API result is an error.
   */
  protected function isApiError($result) {
    return isset($result->error);
  }

  /**
   * Report API error.
   */
  protected function apiError($result, $message) {
    watchdog('ulf_pretix', 'Error: %message: %code %error', [
      '%message' => $message,
      '%code' => $result->code,
      '%error' => $result->error ?? NULL,
      '%data' => $result->data ?? NULL,
    ], WATCHDOG_ERROR);

    return ['error' => $message, 'result' => $result];
  }

}
