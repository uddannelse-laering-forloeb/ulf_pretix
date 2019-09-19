<?php

/**
 * @file
 * Permission settings for the ulf pretix module.
 */

/**
 * Implements hook_permissions().
 *
 * Add permission to restrict access to certain admin paths.
 */
function ulf_pretix_permission() {
  $permissions = array(
    'administer ulf pretix settings' => array(
      'title' => t('Administer pretix settings'),
      'description' => t('Administer pretix settings'),
    ),

    'administer ulf pretix user settings' => array(
      'title' => t('Administer pretix user settings'),
      'description' => t('Administer pretix user settings'),
    ),
  );

  return $permissions;
}

/**
 * Implements hook_secure_permissions().
 *
 * @param $role
 *
 * @return mixed|null
 */
function ulf_pretix_secure_permissions($role) {
  $permissions = array(
    'editor' => array(
      'administer ulf pretix settings',
      'administer ulf pretix user settings',
    ),
  );

  return $permissions[$role] ?? NULL;
}
