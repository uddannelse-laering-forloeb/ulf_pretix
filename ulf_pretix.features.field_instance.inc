<?php
/**
 * @file
 * ulf_pretix.features.field_instance.inc
 */

/**
 * Implements hook_field_default_field_instances().
 */
function ulf_pretix_field_default_field_instances() {
  $field_instances = array();

  // Exported field_instance: 'user-user-field_pretix_api_token_secret_'.
  $field_instances['user-user-field_pretix_api_token_secret_'] = array(
    'bundle' => 'user',
    'default_value' => NULL,
    'deleted' => 0,
    'description' => 'The Api token is found in the pretix configuration',
    'display' => array(
      'default' => array(
        'label' => 'above',
        'module' => 'text',
        'settings' => array(),
        'type' => 'text_default',
        'weight' => 19,
      ),
    ),
    'entity_type' => 'user',
    'field_name' => 'field_pretix_api_token_secret_',
    'label' => 'API token secret ',
    'required' => 0,
    'settings' => array(
      'text_processing' => 0,
      'user_register_form' => 0,
    ),
    'widget' => array(
      'active' => 1,
      'module' => 'text',
      'settings' => array(
        'maxlength_js' => 0,
        'maxlength_js_label' => 'Content limited to @limit characters, remaining: <strong>@remaining</strong>',
        'size' => 60,
      ),
      'type' => 'text_textfield',
      'weight' => 27,
    ),
  );

  // Exported field_instance: 'user-user-field_pretix_enable'.
  $field_instances['user-user-field_pretix_enable'] = array(
    'bundle' => 'user',
    'default_value' => array(
      0 => array(
        'value' => 0,
      ),
    ),
    'deleted' => 0,
    'description' => 'Enable pretix for this user',
    'display' => array(
      'default' => array(
        'label' => 'above',
        'module' => 'list',
        'settings' => array(),
        'type' => 'list_default',
        'weight' => 20,
      ),
    ),
    'entity_type' => 'user',
    'field_name' => 'field_pretix_enable',
    'label' => 'Enable pretix',
    'required' => 0,
    'settings' => array(
      'user_register_form' => 0,
    ),
    'widget' => array(
      'active' => 1,
      'module' => 'options',
      'settings' => array(
        'display_label' => 1,
      ),
      'type' => 'options_onoff',
      'weight' => 20,
    ),
  );

  // Exported field_instance: 'user-user-field_pretix_organiser_slug'.
  $field_instances['user-user-field_pretix_organiser_slug'] = array(
    'bundle' => 'user',
    'default_value' => NULL,
    'deleted' => 0,
    'description' => 'The part of the url immediately after domain name',
    'display' => array(
      'default' => array(
        'label' => 'above',
        'module' => 'text',
        'settings' => array(),
        'type' => 'text_default',
        'weight' => 17,
      ),
    ),
    'entity_type' => 'user',
    'field_name' => 'field_pretix_organiser_slug',
    'label' => 'Organiser slug',
    'required' => 0,
    'settings' => array(
      'text_processing' => 0,
      'user_register_form' => 0,
    ),
    'widget' => array(
      'active' => 1,
      'module' => 'text',
      'settings' => array(
        'maxlength_js' => 0,
        'maxlength_js_label' => 'Content limited to @limit characters, remaining: <strong>@remaining</strong>',
        'size' => 60,
      ),
      'type' => 'text_textfield',
      'weight' => 25,
    ),
  );

  // Exported field_instance: 'user-user-field_pretix_standard_event_slug'.
  $field_instances['user-user-field_pretix_standard_event_slug'] = array(
    'bundle' => 'user',
    'default_value' => NULL,
    'deleted' => 0,
    'description' => 'The part of the url immediately after organizer slug',
    'display' => array(
      'default' => array(
        'label' => 'above',
        'module' => 'text',
        'settings' => array(),
        'type' => 'text_default',
        'weight' => 18,
      ),
    ),
    'entity_type' => 'user',
    'field_name' => 'field_pretix_standard_event_slug',
    'label' => 'Standard event slug',
    'required' => 0,
    'settings' => array(
      'text_processing' => 0,
      'user_register_form' => 0,
    ),
    'widget' => array(
      'active' => 1,
      'module' => 'text',
      'settings' => array(
        'maxlength_js' => 0,
        'maxlength_js_label' => 'Content limited to @limit characters, remaining: <strong>@remaining</strong>',
        'size' => 60,
      ),
      'type' => 'text_textfield',
      'weight' => 26,
    ),
  );

  // Exported field_instance: 'user-user-field_pretix_url'.
  $field_instances['user-user-field_pretix_url'] = array(
    'bundle' => 'user',
    'default_value' => NULL,
    'deleted' => 0,
    'description' => 'Fx. https://pretix.eu/',
    'display' => array(
      'default' => array(
        'label' => 'above',
        'module' => 'text',
        'settings' => array(),
        'type' => 'text_default',
        'weight' => 16,
      ),
    ),
    'entity_type' => 'user',
    'field_name' => 'field_pretix_url',
    'label' => 'Pretix domain name',
    'required' => 0,
    'settings' => array(
      'text_processing' => 0,
      'user_register_form' => 0,
    ),
    'widget' => array(
      'active' => 0,
      'module' => 'html5_tools',
      'settings' => array(),
      'type' => 'urlwidget',
      'weight' => 24,
    ),
  );

  // Translatables
  // Included for use with string extractors like potx.
  t('API token secret ');
  t('Enable pretix');
  t('Enable pretix for this user');
  t('Fx. https://pretix.eu/');
  t('Organiser slug');
  t('Pretix domain name');
  t('Standard event slug');
  t('The Api token is found in the pretix configuration');
  t('The part of the url immediately after domain name');
  t('The part of the url immediately after organizer slug');

  return $field_instances;
}
