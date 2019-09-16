<?php

/**
 * @file
 * Holds alterations for forms related to ulf_pretix module.
 */

use Drupal\ulf_pretix\Pretix\Client;
use Drupal\ulf_pretix\Pretix\EventHelper;

/**
 * Implement hook_form_FORMID_alter().
 */
function ulf_pretix_form_user_profile_form_alter(&$form, &$form_state) {
  $form['field_pretix_enable']['#access'] = user_access('administer ulf pretix user settings');
  $form['field_pretix_url']['#access'] = user_access('administer ulf pretix user settings');
  $form['field_pretix_api_token']['#access'] = user_access('administer ulf pretix user settings');
  $form['field_pretix_organizer_slug']['#access'] = user_access('administer ulf pretix user settings');
  $form['field_pretix_default_event_slug']['#access'] = user_access('administer ulf pretix user settings');

  $user = $form['#user'];
  if (isset($user->uid)) {
    $url = EventHelper::create()->getPretixTemplateEventUrl($user);
    if (NULL !== $url) {
      $form['field_pretix_default_event_slug']['#suffix'] = l($url, $url);
    }

    // Add warning about changing pretix url and organizer slug.
    $helper = EventHelper::create();
    if ($helper->userHasPretixEvents($user)) {
      $form['field_pretix_url']['#prefix'] = '<div class="messages warning">'
        . t("User already has pretix events. Do not change pretix url or organizer slug unless you really know what you're doing!")
        . '</div>';
    }
  }

  $form['#validate'][] = 'ulf_pretix_form_user_profile_form_validate';
}

/**
 * User profile form validation.
 *
 * @see ulf_pretix_form_user_profile_form_alter().
 */
function ulf_pretix_form_user_profile_form_validate($form, &$form_state) {
  $values = $form_state['values'];
  if (isset($values['field_pretix_enable'][LANGUAGE_NONE][0]['value']) && 1 === $values['field_pretix_enable'][LANGUAGE_NONE][0]['value']) {
    $service_url = $values['field_pretix_url'][LANGUAGE_NONE][0]['value'];
    $api_token = $values['field_pretix_api_token'][LANGUAGE_NONE][0]['value'];
    $organizer_slug = $values['field_pretix_organizer_slug'][LANGUAGE_NONE][0]['value'];
    $event_slug = $values['field_pretix_default_event_slug'][LANGUAGE_NONE][0]['value'];

    $helper = EventHelper::create();
    $errors = $helper->validateTemplateEvent($service_url, $api_token, $organizer_slug, $event_slug);

    if (null !== $errors) {
      foreach ($errors as $key => $message) {
        switch ($key) {
          case 'service_url':
            form_set_error('field_pretix_url', $message);
            return;
          case 'api_token':
            form_set_error('field_pretix_api_token', $message);
            return;
          case 'organizer_slug':
            form_set_error('field_pretix_organizer_slug', $message);
            return;
          case 'event_slug':
            form_set_error('field_pretix_default_event_slug', $message);
            return;
          default:
            // Fallback.
            form_set_error('field_pretix_url', $message);
            return;
        }
      }
    }

    drupal_set_message(t('Successfully connected to pretix'));

    // Ensure that web hook is defined.
    $result = $helper->ensureWebhook($service_url, $api_token, $organizer_slug);
    if (isset($result->error)) {
      $message = t('Error creating pretix webhook: %error', ['%error' => $result->error]);
      form_set_error('field_pretix_organizer_slug', $message);
    } else {
      drupal_set_message(t('Successfully created pretix webhook'));
    }
  }
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

    $node = $form['#node'] ?? NULL;
    // Check that we have a valid node object.
    if (isset($node->nid)) {
      $helper = EventHelper::create();
      $markup = theme('node__pretix_info', [
        'node' => $node,
        'pretix_urls' => [
          'event_url' => $helper->getPretixEventUrl($node),
          'event_shop_url' => $helper->getPretixEventShopUrl($node),
          'waitinglist_url' => $helper->getPretixEventUrl($node, 'waitinglist/'),
        ],
      ]);

      // Add content at top of pretix group.
      $form['pretix_info'] = [
        '#markup' => $markup,
        '#weight' => -1000,
      ];
      $form['#group_children']['pretix_info'] = 'group_pretix';

      $wrapper = entity_metadata_wrapper('node', $node);
      if (FALSE === $wrapper->field_pretix_synchronize->value()) {
        $form['field_pretix_synchronize']['#access'] = FALSE;
        $form['field_pretix_date']['#access'] = FALSE;

        $form['pretix_synchronize_info'] = [
          '#prefix' => '<div class="messages warning"><div>',
          '#suffix' => '</div></div>',
          '#markup' => t(
            'Note: This event is not syncronized with pretix. Go to <a href="@url">@url</a> to edit the event in pretix.',
            ['@url' => $helper->getPretixEventUrl($node)]
          ),
          '#weight' => -999,
        ];
        $form['#group_children']['pretix_synchronize_info'] = 'group_pretix';
      }
    }

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
          'required' => array(
            ':input[name="field_pretix_date[und][' . $key . '][field_pretix_free][und]"]' => array('checked' => FALSE),
          ),
        );
      }
    }


    // Copy first row values, when creating a new row.
    if (isset($form_state['clicked_button']) && $form_state['clicked_button']['#name'] == 'field_pretix_date_add_more') {
      $date_elements = array_filter(array_keys($form['field_pretix_date']['und']), 'is_numeric');
      $latest_element = end(array_keys($date_elements));
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
        // @TODO: Use number_field_formatter_view (or something else) for this.
        $field_info = field_info_field('field_pretix_price');
        if (isset($field_info['settings'])) {
          $settings = $field_info['settings'];
          $initial_price = number_format($initial_price, $settings['scale'], $settings['decimal_separator'], '');
        }
        $form['field_pretix_date'][LANGUAGE_NONE][$latest_element]['field_pretix_price'][LANGUAGE_NONE][0]['value']['#default_value'] = $initial_price;
      }
    }

    $form['#validate'][] = 'ulf_pretix_form_validate';

  }
}

/**
 * Validation function for course nodes.
 *
 * @see ulf_pretix_form_alter().
 */
function ulf_pretix_form_validate($form, &$form_state) {
  $values = $form_state['values'];
  if (isset($values['field_pretix_date'][LANGUAGE_NONE])) {
    foreach ($values['field_pretix_date'][LANGUAGE_NONE] as $key => $value) {
      if (is_numeric($key)) {
        $is_free = 1 === $value['field_pretix_free'][LANGUAGE_NONE][0]['value'];
        $price = $value['field_pretix_price'][LANGUAGE_NONE][0]['value'];
        if (!$is_free && empty($price)) {
          form_set_error('field_pretix_date]['.LANGUAGE_NONE.']['.$key.'][field_pretix_price', t('Price is required for non-free dates.'));
        }
      }
    }
  }
}
