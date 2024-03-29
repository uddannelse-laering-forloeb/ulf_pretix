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
  if ($element['#form_id'] == 'user_profile_form') {
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
  if ($element['#form_id'] == 'course_node_form' || $element['#form_id'] == 'course_educators_node_form') {
    $user_wrapper =  entity_metadata_wrapper('user', $element['#node']->uid);
    $pretix_enabled = $user_wrapper->field_pretix_enable->value();
    if (!$pretix_enabled) {
      $element['group_pretix']['#access'] = FALSE;
    }
  }
}

/**
 * Implement hook_form_alter().
 */
function ulf_pretix_form_alter(&$form, &$form_state, $form_id) {
  $pretix_node_forms = array(
    'course_node_form',
    'course_educators_node_form'
  );

  // Set default values when the form is loaded.
  if(in_array($form_id, $pretix_node_forms)) {
    $form['#attached']['css'][] = drupal_get_path('module', 'ulf_pretix') . '/css/ulf_pretix.css';
    $form['field_pretix_out_of_stock'][LANGUAGE_NONE]['#attributes']['disabled'] = TRUE;
    $form['field_pretix_wait_list'][LANGUAGE_NONE]['#attributes']['disabled'] = TRUE;

    // Set free for pretix date row if the course is free.
    if($form['field_free'][LANGUAGE_NONE]['#default_value']) {
      $form['field_pretix_date'][LANGUAGE_NONE][0]['field_pretix_free'][LANGUAGE_NONE]['#default_value'] = TRUE;
    }

    // Show hide price field for each row.
    foreach ($form['field_pretix_date'][LANGUAGE_NONE] as $key => $value) {
      if (is_numeric($key)) {
        $form['field_pretix_date'][LANGUAGE_NONE][$key]['field_pretix_price']['#states'] = array(
          'visible' => array(
            ':input[name="field_pretix_date[und][' . $key . '][field_pretix_free][und]"]' => array('checked' => FALSE),
          ),
        );
      }
    }

    // Copy first row values, when creating a new row.
    if (isset($form_state['clicked_button']) && $form_state['clicked_button']['#name'] == 'field_pretix_date_add_more') {
      $date_elements = array_filter(array_keys($form['field_pretix_date']['und']), 'is_numeric');
      $latest_element = max(array_keys($date_elements));
      if ($latest_element) {
        $initial_spaces = $form_state['values']['field_pretix_date'][LANGUAGE_NONE][0]['field_pretix_spaces'][LANGUAGE_NONE][0]['value'];
        $form['field_pretix_date'][LANGUAGE_NONE][$latest_element]['field_pretix_spaces'][LANGUAGE_NONE][0]['value']['#default_value'] = $initial_spaces;
        $initial_startdate = $form_state['values']['field_pretix_date'][LANGUAGE_NONE][0]['field_pretix_start_date'][LANGUAGE_NONE][0]['value'];
        $form['field_pretix_date'][LANGUAGE_NONE][$latest_element]['field_pretix_start_date'][LANGUAGE_NONE][0]['#default_value']['value'] = $initial_startdate;
        $initial_presale = $form_state['values']['field_pretix_date'][LANGUAGE_NONE][0]['field_pretix_presale'][LANGUAGE_NONE][0]['value'];
        $form['field_pretix_date'][LANGUAGE_NONE][$latest_element]['field_pretix_presale'][LANGUAGE_NONE][0]['#default_value']['value'] = $initial_presale;
        $initial_free = $form_state['values']['field_pretix_date'][LANGUAGE_NONE][0]['field_pretix_free'][LANGUAGE_NONE][0]['value'];
        $form['field_pretix_date'][LANGUAGE_NONE][$latest_element]['field_pretix_free'][LANGUAGE_NONE]['#default_value'] = $initial_free;
        $initial_price = $form_state['values']['field_pretix_date'][LANGUAGE_NONE][0]['field_pretix_price'][LANGUAGE_NONE][0]['value'];
        $form['field_pretix_date'][LANGUAGE_NONE][$latest_element]['field_pretix_price'][LANGUAGE_NONE][0]['value']['#default_value'] = $initial_price;
      }
    }
  }
}


