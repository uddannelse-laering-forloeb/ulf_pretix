<?php

/**
 * @file
 * Admin forms, page callbacks and related functions.
 */

use Drupal\ulf_pretix\Pretix\Mailer;

/**
 * Administrative settings form.
 *
 * @param array $form
 *   Form structure.
 * @param array $form_state
 *   Form state values.
 *
 * @return array
 *   Form structure prepared for admin settings.
 *
 * @ingroup forms
 */
function ulf_pretix_admin_settings_form(array $form, array &$form_state) {
  $form['#tree'] = TRUE;

  $form[Mailer::PRETIX_EVENT_ORDER_PAID_TEMPLATE] = [
    '#type' => 'textarea',
    '#title' => t('Order paid email template'),
    '#required' => TRUE,
    '#default_value' => variable_get(Mailer::PRETIX_EVENT_ORDER_PAID_TEMPLATE),
  ];

  $form[Mailer::PRETIX_EVENT_ORDER_CANCELED_TEMPLATE] = [
    '#type' => 'textarea',
    '#title' => t('Order canceled email template'),
    '#required' => TRUE,
    '#default_value' => variable_get(Mailer::PRETIX_EVENT_ORDER_CANCELED_TEMPLATE),
  ];

  $user_manual = variable_get('ulf_pretix_user_manual');
  $form['ulf_pretix_user_manual'] = [
    '#type' => 'fieldset',
    '#tree' => TRUE,
    '#title' => t('User manual'),

    'url' => [
      '#type' => 'textfield',
      '#title' => t('Url'),
      '#required' => TRUE,
      '#default_value' => $user_manual['url'] ?? NULL,
      '#description' => t('Url to ULF/pretix user manual link.'),
    ],

    'link_target' => [
      '#type' => 'select',
      '#options' => [
        '_blank' => t('New window'),
      ],
      '#empty_option' => t('Same window'),
      '#title' => t('Link target'),
      '#default_value' => $user_manual['link_target'] ?? NULL,
      '#description' => t('Target for user manual link.'),
    ],

    'link_text' => [
      '#type' => 'textfield',
      '#title' => t('Link text'),
      '#required' => TRUE,
      '#default_value' => $user_manual['link_text'] ?? t('Read the ULF/pretix user manual'),
    ],
  ];

  $form['#validate'][] = 'ulf_pretix_admin_settings_form_validate';

  return system_settings_form($form);
}

/**
 * Admin settings form validation.
 */
function ulf_pretix_admin_settings_form_validate($form, &$form_state) {
  $values = $form_state['values'];
  if (isset($values['ulf_pretix_user_manual']['url'])) {
    $url = url($values['ulf_pretix_user_manual']['url'], ['absolute' => TRUE]);
    if (!valid_url($url)) {
      form_set_error(
        'ulf_pretix_user_manual][url',
        t('Please enter a valid url.')
      );
    }
  }
}
