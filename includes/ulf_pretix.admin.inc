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

  $form['#validate'][] = 'ulf_pretix_admin_settings_form_validate';

  return system_settings_form($form);
}

/**
 * Admin settings form validation.
 */
function ulf_pretix_admin_settings_form_validate($form, &$form_state) {
  $values = $form_state['values'];
  if (isset($values['ulf_pretix_event_slug_template'])) {
    $template = $values['ulf_pretix_event_slug_template'];
    if (FALSE === strpos($template, '!nid')) {
      form_set_error(
        'ulf_pretix_event_slug_template',
        t('Pretix event slug template must contain <code>!nid</code>.')
      );
    }
  }
}
