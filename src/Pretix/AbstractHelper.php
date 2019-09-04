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
   * Save pretix info on a node.
   *
   * @param object $node
   *   The node.
   * @param object $user
   *   The user.
   * @param object $event
   *   The event.
   * @param array $data
   *   The data.
   */
  protected function savePretixEventInfo($node, $user, $event, array $data = []) {
    $info = $this->loadPretixEventInfo($node, TRUE);
    $existingData = $info['data'] ?? [];

    $url = $user->field_pretix_url->value();
    $data += [
      'pretix_url' => $url,
      'pretix_event_url' => $this->getPretixEventUrl($node),
      'pretix_event_shop_url' => $this->getPretixEventShopUrl($node),
      'pretix_organizer_slug' => $user->field_pretix_organizer_slug->value(),
    ] + $existingData;
    $info = [
      'nid' => $node->nid,
      'pretix_organizer_slug' => $user->field_pretix_organizer_slug->value(),
      'pretix_event_slug' => $event->slug,
      'data' => json_encode($data),
    ];

    $result = db_merge('ulf_pretix_events')
      ->key(['nid' => $node->nid])
      ->fields($info)
      ->execute();

    return $info;
  }

  /**
   * Save pretix sub-event info.
   */
  protected function savePretixSubEventInfo($item, $subEvent, array $data = []) {
    $info = $this->loadPretixSubEventInfo($item, TRUE);
    $existingData = $info['data'] ?? [];

    $data += $existingData;

    list($fieldName, $itemId) = $this->getItemKeys($item);
    $info = [
      'field_name' => $fieldName,
      'item_id' => $itemId,
      'pretix_subevent_id' => $this->getId($subEvent),
      'data' => json_encode($data),
    ];

    db_merge('ulf_pretix_subevents')
      ->key([
        'field_name' => $info['field_name'],
        'item_id' => $info['item_id'],
        'pretix_subevent_id' => $info['pretix_subevent_id'],
      ])
      ->fields($info)
      ->execute();

    return $info;
  }

  /**
   * Add data to pretix sub-event.
   *
   * @param object $item
   *   The item or a sub-event.
   * @param array $data
   *   The data to add.
   */
  public function addPretixSubEventInfo($item, array $data) {
    // Check if item is a sub-event object.
    if (isset($item->event, $item->id)) {
      $subEventId = $this->getId($item);

      $result = db_select('ulf_pretix_subevents', 'p')
        ->fields('p')
        ->condition('pretix_subevent_id', $subEventId, '=')
        ->execute()
        ->fetch();

      $item = $result;
    }

    $info = $this->loadPretixSubEventInfo($item, TRUE);
    $existingData = $info['data'] ?? [];

    $data += $existingData;
    $info['data'] = json_encode($data);

    db_merge('ulf_pretix_subevents')
      ->key([
        'field_name' => $info['field_name'],
        'item_id' => $info['item_id'],
        'pretix_subevent_id' => $info['pretix_subevent_id'],
      ])
      ->fields($info)
      ->execute();
  }

  /**
   * Load pretix sub-event info from database.
   *
   * @param object $item
   *   The field collection item.
   * @param bool $reset
   *   If set, data will be read from database.
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

}
