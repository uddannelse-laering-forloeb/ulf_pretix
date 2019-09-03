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

  $form[Mailer::PRETIX_EVENT_ORDER_CANCELLED_TEMPLATE] = [
    '#type' => 'textarea',
    '#title' => t('Order cancelled email template'),
    '#required' => TRUE,
    '#default_value' => variable_get(Mailer::PRETIX_EVENT_ORDER_CANCELLED_TEMPLATE),
  ];

  return system_settings_form($form);
}
