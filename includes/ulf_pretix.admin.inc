<?php

/**
 * @file
 * Admin forms, page callbacks and related functions.
 */

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

  $form['ulf_pretix_order_email_template'] = [
    '#type' => 'textarea',
    '#title' => t('Order email template'),
    '#required' => TRUE,
    '#default_value' => variable_get('ulf_pretix_order_email_template'),
  ];

  return system_settings_form($form);
}
