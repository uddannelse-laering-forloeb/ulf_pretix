<?php
/**
 * @file
 * ulf_pretix.field_group.inc
 */

/**
 * Implements hook_field_group_info().
 */
function ulf_pretix_field_group_info() {
  $field_groups = array();

  $field_group = new stdClass();
  $field_group->disabled = FALSE; /* Edit this to true to make a default field_group disabled initially */
  $field_group->api_version = 1;
  $field_group->identifier = 'group_pretix|user|user|form';
  $field_group->group_name = 'group_pretix';
  $field_group->entity_type = 'user';
  $field_group->bundle = 'user';
  $field_group->mode = 'form';
  $field_group->parent_name = '';
  $field_group->data = array(
    'label' => 'Pretix',
    'weight' => '21',
    'children' => array(
      0 => 'field_pretix_url',
      1 => 'field_pretix_organiser_slug',
      2 => 'field_pretix_standard_event_slug',
      3 => 'field_pretix_api_token_secret_',
    ),
    'format_type' => 'fieldset',
    'format_settings' => array(
      'label' => 'Pretix',
      'instance_settings' => array(
        'required_fields' => 1,
        'id' => '',
        'classes' => '',
        'description' => '',
      ),
      'formatter' => 'open',
    ),
  );
  $field_groups['group_pretix|user|user|form'] = $field_group;

  // Translatables
  // Included for use with string extractors like potx.
  t('Pretix');

  return $field_groups;
}
