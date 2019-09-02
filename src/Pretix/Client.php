<?php

namespace Drupal\ulf_pretix\Pretix;

/**
 * Pretix client.
 *
 * @see https://docs.pretix.eu/en/latest/api/resources/index.html
 */
class Client {
  /**
   * The pretix url.
   *
   * @var string
   */
  private $url;

  /**
   * The pretix api token.
   *
   * @var string
   */
  private $apiToken;

  /**
   * The pretix organizer slug.
   *
   * @var string
   */
  private $organizerSlug;

  /**
   * Constructor.
   *
   * @param string $url
   *   The api url.
   * @param string $apiToken
   *   The api token.
   * @param string $organizerSlug
   *   The organizer slug.
   */
  public function __construct($url, $apiToken, $organizerSlug) {
    $this->url = trim($url, '/');
    $this->apiToken = $apiToken;
    $this->organizerSlug = $organizerSlug;
  }

  /**
   * Set organizer slug.
   *
   * @param string $organizerSlug
   *   The organizer slug.
   *
   * @return $this
   */
  public function setOrganizerSlug($organizerSlug) {
    $this->organizerSlug = $organizerSlug;

    return $this;
  }

  /**
   * Get API endpoints.
   */
  public function getApiEndpoints() {
    return $this->get('');
  }

  /**
   * Get organizers.
   */
  public function getOrganizers() {
    return $this->get('organizers/');
  }

  /**
   * Get Events.
   */
  public function getEvents() {
    return $this->get('organizers/' . $this->organizerSlug . '/events/');
  }

  /**
   * Get event.
   */
  public function getEvent($eventSlug) {
    return $this->get('organizers/' . $this->organizerSlug . '/events/' . $eventSlug . '/');
  }

  /**
   * Create event.
   */
  public function createEvent($data) {
    return $this->post('organizers/' . $this->organizerSlug . '/events/', [
      'data' => $data,
    ]);
  }

  /**
   * Clone event.
   */
  public function cloneEvent($event, array $data) {
    $eventSlug = $this->getSlug($event);

    return $this->post('organizers/' . $this->organizerSlug . '/events/' . $eventSlug . '/clone/', [
      'data' => $data,
    ]);
  }

  /**
   * Update event.
   */
  public function updateEvent($event, $data) {
    $eventSlug = $this->getSlug($event);

    return $this->patch('organizers/' . $this->organizerSlug . '/events/' . $eventSlug . '/', [
      'data' => $data,
    ]);
  }

  /**
   * Delete event.
   */
  public function deleteEvent($event) {
    $eventSlug = $this->getSlug($event);

    return $this->delete('organizers/' . $this->organizerSlug . '/events/' . $eventSlug . '/');
  }

  /**
   * Get items (products).
   */
  public function getItems($event) {
    $eventSlug = $this->getSlug($event);

    return $this->get('organizers/' . $this->organizerSlug . '/events/' . $eventSlug . '/items/');
  }

  /**
   * Get quotas.
   */
  public function getQuotas($event, array $options = []) {
    $eventSlug = $this->getSlug($event);

    return $this->get('organizers/' . $this->organizerSlug . '/events/' . $eventSlug . '/quotas/', $options);
  }

  /**
   * Create quota.
   */
  public function createQuota($event, array $data) {
    $eventSlug = $this->getSlug($event);

    return $this->post('organizers/' . $this->organizerSlug . '/events/' . $eventSlug . '/quotas/', ['data' => $data]);
  }

  /**
   * Update quota.
   */
  public function updateQuota($event, $quota, array $data) {
    $eventSlug = $this->getSlug($event);
    $quotaId = $this->getId($quota);

    return $this->patch('organizers/' . $this->organizerSlug . '/events/' . $eventSlug . '/quotas/' . $quotaId . '/', ['data' => $data]);
  }

  /**
   * Get sub-events (event series dates).
   */
  public function getSubEvents($event) {
    $eventSlug = $this->getSlug($event);

    return $this->get('organizers/' . $this->organizerSlug . '/events/' . $eventSlug . '/subevents/');
  }

  /**
   * Create sub-event.
   */
  public function createSubEvent($event, $data) {
    $eventSlug = $this->getSlug($event);

    return $this->post('organizers/' . $this->organizerSlug . '/events/' . $eventSlug . '/subevents/', [
      'data' => $data,
    ]);
  }

  /**
   * Update sub-event.
   */
  public function updateSubEvent($event, $subEvent, $data) {
    $eventSlug = $this->getSlug($event);
    $subEventId = $this->getId($subEvent);

    return $this->patch('organizers/' . $this->organizerSlug . '/events/' . $eventSlug . '/subevents/' . $subEventId . '/', [
      'data' => $data,
    ]);
  }

  /**
   * Delete sub-event.
   */
  public function deleteSubEvent($event, $subEvent) {
    $eventSlug = $this->getSlug($event);
    $subEventId = $this->getId($subEvent);

    return $this->delete('organizers/' . $this->organizerSlug . '/events/' . $eventSlug . '/subevents/' . $subEventId . '/');
  }

  /**
   * Get webhooks.
   */
  public function getWebhooks() {
    return $this->get('organizers/' . $this->organizerSlug . '/webhooks/');
  }

  /**
   * Get webhook.
   */
  public function getWebhook($id) {
    return $this->get('organizers/' . $this->organizerSlug . '/webhooks/' . $id);
  }

  /**
   * Create webhook.
   */
  public function createWebhook(array $data) {
    return $this->post('organizers/' . $this->organizerSlug . '/webhooks/', [
      'data' => $data,
    ]);
  }

  /**
   * Update webhook.
   */
  public function updateWebhook($webhook, array $data) {
    return $this->patch('organizers/' . $this->organizerSlug . '/webhooks/' . $webhook->id . '/', [
      'data' => $data,
    ]);
  }

  /**
   * Get order.
   *
   * @param string|object $organizer
   *   The organizer.
   * @param string|object $event
   *   The event.
   * @param string $code
   *   The code.
   */
  public function getOrder($organizer, $event, $code) {
    $organizerSlug = $this->getSlug($organizer);
    $eventSlug = $this->getSlug($event);

    return $this->get('organizers/' . $organizerSlug . '/events/' . $eventSlug . '/orders/' . $code . '/');
  }

  /**
   * Get request.
   */
  private function get($path, array $options = []) {
    return $this->request('GET', $path, $options);
  }

  /**
   * Post request.
   */
  private function post($path, array $options = []) {
    return $this->request('POST', $path, $options);
  }

  /**
   * Patch request.
   */
  private function patch($path, array $options = []) {
    return $this->request('PATCH', $path, $options);
  }

  /**
   * DELETE request.
   */
  private function delete($path) {
    return $this->request('DELETE', $path);
  }

  /**
   * Request.
   */
  private function request($method, $path, array $options = []) {
    $headers = [
      'accept' => 'application/json, text/javascript',
      'authorization' => 'Token ' . $this->apiToken,
      'content-type' => 'application/json',
    ];

    if (isset($options['query'])) {
      $query = $options['query'];
      unset($options['query']);
      if (is_array($query)) {
        $query = http_build_query($query);
      }
      $path .= (FALSE === strpos($path, '?') ? '?' : '&') . $query;
    }

    $url = $this->url . '/api/v1/' . $path;

    $options += [
      'method' => $method,
      'headers' => $headers,
    ];

    if (isset($options['data']) && is_array($options['data'])) {
      $options['data'] = json_encode($options['data']);
    }

    $result = drupal_http_request($url, $options);

    if (isset($result->error)) {
      watchdog('ulf_pretix',
                             '@error: @method @data (Request: @request, URL: @url)',
                             [
                               '@error' => $result->error,
                               '@method' => $options['method'],
                               '@data' => $result->data ?? NULL,
                               '@request' => $result->request ?? NULL,
                               '@url' => $url,
                             ],
                             $severity = WATCHDOG_ERROR);
    }

    if (isset($result->data)) {
      $data = json_decode($result->data);
      if ($data) {
        $result->data = $data;
      }
    }

    return $result;
  }

  /**
   * Get event slug.
   *
   * @param string|object $event
   *   The event or event slug.
   *
   * @return string
   *   The event slug.
   */
  private function getSlug($event) {
    return $event->slug ?? $event;
  }

  /**
   * Get id.
   */
  private function getId($object) {
    return $object->id ?? $object;
  }

}
