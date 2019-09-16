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
   * Set pretix client.
   */
  public function setClient(Client $client) {
    $this->client = $client;

    return $this;
  }

  /**
   * Get pretix client.
   */
  public function getPretixClient($node) {
    $user = entity_metadata_wrapper('user', $node->uid);

    if (TRUE === $user->field_pretix_enable->value()) {
      $info = $this->loadPretixEventInfo($node, TRUE);
      $data = $info['data'] ?? NULL;
      // pretix_url and pretix_organizer_slug may have changed on the user, so
      // we give priority to the values stored in the node event info.
      $url = $data['pretix_url'] ?? $user->field_pretix_url->value();
      $organizerSlug = $data['pretix_organizer_slug'] ?? $user->field_pretix_organizer_slug->value();
      $apiToken = $user->field_pretix_api_token->value();

      return new Client($url, $apiToken, $organizerSlug);
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
   * Get node by organizer and event.
   *
   * @param string|object $organizer
   *   The organizer.
   * @param string|object $event
   *   The event.
   *
   * @return null|object
   *   The node if found.
   */
  public function getNode($organizer, $event) {
    $organizerSlug = $this->getSlug($organizer);
    $eventSlug = $this->getSlug($event);

    $result = db_select('ulf_pretix_events', 'p')
      ->fields('p')
      ->condition('pretix_organizer_slug', $organizerSlug, '=')
      ->condition('pretix_event_slug', $eventSlug, '=')
      ->execute()
      ->fetch();

    $node = isset($result->nid) ? node_load($result->nid) : NULL;

    return $node ?? NULL;
  }

  /**
   * Load pretix event info from database.
   */
  public function loadPretixEventInfo($node, $reset = FALSE) {
    if (is_array($node)) {
      $info = [];
      foreach ($node as $n) {
        $info[$n->nid] = $this->loadPretixEventInfo($n, $reset);
      }

      return $info;
    }
    else {
      $nid = $node->nid;
      $info = &drupal_static(__METHOD__, []);

      if (!isset($info[$nid]) || $reset) {
        $record = db_select('ulf_pretix_events', 'p')
          ->fields('p')
          ->condition('nid', $nid, '=')
          ->execute()
          ->fetch();

        if (!empty($record)) {
          $info[$nid] = [
            'nid' => $record->nid,
            'pretix_organizer_slug' => $record->pretix_organizer_slug,
            'pretix_event_slug' => $record->pretix_event_slug,
            'data' => json_decode($record->data, TRUE),
          ];
        }
      }

      return $info[$nid] ?? NULL;
    }
  }

  /**
   * Add pretix event.
   *
   * @param object $node
   *   The node.
   * @param object $event
   *   The event.
   * @param array $data
   *   The data.
   * @param bool $reset
   *   If set, the data will be reset.
   *
   * @return array
   *   The event data.
   *
   * @throws \InvalidMergeQueryException
   */
  protected function addPretixEventInfo($node, $event, array $data, $reset = FALSE) {
    $info = $this->loadPretixEventInfo($node, TRUE);

    // The values to store in the database.
    $fields = [];
    if (NULL === $info || $reset) {
      $user = entity_metadata_wrapper('user', $node->uid);
      $fields = [
        'nid' => $node->nid,
        'pretix_organizer_slug' => $user->field_pretix_organizer_slug->value(),
        'pretix_event_slug' => $event->slug,
      ];

      $pretixUrl = rtrim($user->field_pretix_url->value(), '/');
      $data += [
        'pretix_url' => $pretixUrl,
        'pretix_event_url' => $pretixUrl . '/control/event/' . $fields['pretix_organizer_slug'] . '/' . $fields['pretix_event_slug'] . '/',
        'pretix_event_shop_url' => $pretixUrl . '/' . $fields['pretix_organizer_slug'] . '/' . $fields['pretix_event_slug'] . '/',
        'pretix_organizer_slug' => $user->field_pretix_organizer_slug->value(),
        'pretix_event_slug' => $event->slug,
        'event' => $event,
      ];
    }

    // Add any existing data.
    $data += $info['data'] ?? [];

    $fields['data'] = json_encode($data);

    $result = db_merge('ulf_pretix_events')
      ->key(['nid' => $node->nid])
      ->fields($fields)
      ->execute();

    return $data;
  }

  /**
   * Add pretix sub-event info.
   *
   * @param object|null $item
   *   The item collection item.
   * @param object|int $subEvent
   *   The sub-event (id).
   * @param array $data
   *   The data.
   * @param bool $reset
   *   If set, the data will be reset.
   *
   * @return array
   *   The sub-event data.
   *
   * @throws \InvalidMergeQueryException
   */
  public function addPretixSubEventInfo($item, $subEvent, array $data, $reset = FALSE) {
    if (NULL === $item && isset($subEvent->event, $subEvent->id)) {
      $subEventId = $this->getId($subEvent);

      $result = db_select('ulf_pretix_subevents', 'p')
        ->fields('p')
        ->condition('pretix_subevent_id', $subEventId, '=')
        ->execute()
        ->fetch();

      $item = $result;
    }

    list($fieldName, $itemId) = $this->getItemKeys($item);
    $subEventId = $this->getId($subEvent);

    $info = $this->loadPretixSubEventInfo($item, TRUE);
    // The values to store in the database.
    $fields = [];
    if (NULL === $info || $reset) {
      $fields = [
        'field_name' => $fieldName,
        'item_id' => $itemId,
        'pretix_subevent_id' => $subEventId,
      ];

      $data += [
        'pretix_subevent_id' => $subEventId,
      ];
    }

    // Add any existing data.
    $data += $info['data'] ?? [];
    $fields['data'] = json_encode($data);

    db_merge('ulf_pretix_subevents')
      ->key([
        'field_name' => $fieldName,
        'item_id' => $itemId,
        'pretix_subevent_id' => $subEventId,
      ])
      ->fields($fields)
      ->execute();

    return $data;
  }

  /**
   * Load pretix sub-event info from database.
   *
   * @param object $item
   *   The field collection item.
   * @param bool $reset
   *   If set, data will be read from database.
   *
   * @return array|null
   *   The sub-event data.
   */
  public function loadPretixSubEventInfo($item, $reset = FALSE) {
    if (is_array($item)) {
      $info = [];
      foreach ($item as $i) {
        $info[$i->item_id] = $this->loadPretixSubEventInfo($i, $reset);
      }

      return $info;
    }
    else {
      list($fieldName, $itemId) = $this->getItemKeys($item);
      $info = &drupal_static(__METHOD__, []);

      if (!isset($info[$fieldName][$itemId]) || $reset) {
        $record = db_select('ulf_pretix_subevents', 'p')
          ->fields('p')
          ->condition('field_name', $fieldName, '=')
          ->condition('item_id', $itemId, '=')
          ->execute()
          ->fetch();

        if (!empty($record)) {
          $info[$fieldName][$itemId] = [
            'field_name' => $record->field_name,
            'item_id' => (int) $record->item_id,
            'pretix_subevent_id' => (int) $record->pretix_subevent_id,
            'data' => json_decode($record->data, TRUE),
          ];
        }

        return $info[$fieldName][$itemId] ?? NULL;
      }
    }
  }

  /**
   * Get keys for looking up an item in the ulf_pretix_events table.
   *
   * @param object $item
   *   The item_id.
   *
   * @return array
   *   [field_name, item_id].
   */
  private function getItemKeys($item) {
    if ($item instanceof \EntityDrupalWrapper) {
      return [
        $item->field_name->value(),
        (int) $item->item_id->value(),
      ];
    }

    return [
      $item->field_name,
      (int) $item->item_id,
    ];
  }

  /**
   * Get slug.
   *
   * @param string|object $object
   *   The object or object slug.
   *
   * @return string
   *   The object slug.
   */
  protected function getSlug($object) {
    return $object->slug ?? $object;
  }

  /**
   * Get id.
   */
  private function getId($object) {
    return $object->id ?? $object;
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
  public function isApiError($result) {
    return isset($result->error);
  }

  /**
   * Report API error.
   */
  public function apiError($result, $message) {
    watchdog('ulf_pretix', 'Error: %message: %code %error', [
      '%message' => $message,
      '%code' => $result->code,
      '%error' => $result->error ?? NULL,
      '%data' => $result->data ?? NULL,
    ], WATCHDOG_ERROR);

    return ['error' => $message, 'result' => $result];
  }

  /**
   * Get pretix event url.
   */
  public function getPretixEventShopUrl($node) {
    $info = $this->loadPretixEventInfo($node);

    return $info['data']['pretix_event_shop_url'] ?? NULL;
  }

  /**
   * Get pretix event url.
   */
  public function getPretixEventUrl($node, $path = '') {
    $info = $this->loadPretixEventInfo($node);

    if (isset($info['data']['pretix_event_url'])) {
      return $info['data']['pretix_event_url'] . $path;
    }

    return NULL;
  }

}
