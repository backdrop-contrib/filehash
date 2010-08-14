<?php
// $Id$

/**
 * @file
 * File hash configuration.
 */

/**
 * Return file hash settings form.
 */
function filehash_settings() {
  $form['filehash_algos'] = array(
    '#default_value' => variable_get('filehash_algos', array('sha256')),
    '#description' => t('Only the checked hash algorithms will be calculated.'),
    '#options' => array('md5' => t('MD5'), 'sha1' => t('SHA-1'), 'sha256' => t('SHA-256')),
    '#title' => t('Enabled hash algorithms'),
    '#type' => 'checkboxes',
  );
  return system_settings_form($form);
}
