<?php

/**
 * @file
 * Holds alterations for forms related to ulf_pretix module.
 */

/**
 * Implement hook_form_FORMID_alter().
 */
function ulf_pretix_form_user_profile_form_alter(&$form, &$form_state) {
  $form['field_pretix_enable']['#access'] = user_access('administer ulf pretix user settings');
  $form['field_pretix_url']['#access'] = user_access('administer ulf pretix user settings');
  $form['field_pretix_organiser_slug']['#access'] = user_access('administer ulf pretix user settings');
  $form['field_pretix_standard_event_slug']['#access'] = user_access('administer ulf pretix user settings');
  $form['field_pretix_api_token_secret_']['#access'] = user_access('administer ulf pretix user settings');
}


/**
 * Implements hook_field_group_build_pre_render_alter().
 */
function ulf_pretix_field_group_build_pre_render_alter(&$element) {
  // ID needed due to https://www.drupal.org/project/field_group/issues/2318197
  if (user_access('administer ulf pretix user settings')) {
    $element['group_pretix']['#attributes']['id'] = 'pretix-enable';
    $element['group_pretix']['#id'] = 'pretix-enable';
    $element['group_pretix']['#states'] = array(
      'visible' => array(
        ':input[id="edit-field-pretix-enable-und"]' => array('checked' => TRUE),
      ),
    );
  }
}