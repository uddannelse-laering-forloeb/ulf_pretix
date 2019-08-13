<?php
/**
 * @file
 * ulf_pretix.strongarm.inc
 */

/**
 * Implements hook_strongarm().
 */
function ulf_pretix_strongarm() {
  $export = array();

  $strongarm = new stdClass();
  $strongarm->disabled = FALSE; /* Edit this to true to make a default strongarm disabled initially */
  $strongarm->api_version = 1;
  $strongarm->name = 'field_bundle_settings_field_collection_item__field_pretix_date';
  $strongarm->value = array(
    'view_modes' => array(),
    'extra_fields' => array(
      'form' => array(),
      'display' => array(),
    ),
  );
  $export['field_bundle_settings_field_collection_item__field_pretix_date'] = $strongarm;

  return $export;
}
