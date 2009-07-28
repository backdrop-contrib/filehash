<?php
// $Id$

/**
 * @file
 * File hash configuration.
 */

/**
 * Implementation of hook_menu().
 */
function filehash_menu() {
  $items['admin/settings/filehash'] = array(
    'title' => 'File hash',
    'description' => 'Configure file hash settings.',
    'page callback' => 'drupal_get_form',
    'page arguments' => array('filehash_settings'),
    'access arguments' => array('administer site configuration'),
  );
  return $items;
}

/**
 * Return file hash settings form.
 */
function filehash_settings() {
  $form['filehash_algos'] = array(
    '#default_value' => array('sha256'),
    '#description' => t('Only the checked hash algorithms will be calculated.'),
    '#options' => array('md5' => t('MD5'), 'sha1' => t('SHA-1'), 'sha256' => t('SHA-256')),
    '#title' => t('Enabled hash algorithms'),
    '#type' => 'checkboxes',
  );
  return system_settings_form($form);
}
