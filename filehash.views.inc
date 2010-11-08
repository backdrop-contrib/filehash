<?php
// $Id$
/**
 * @file
 *
 * Provide views data and handlers for filehash table.
 */

/**
 * Implements hook_views_data()
 */
function filehash_views_data() {
  $data['filehash']['table']['group']  = t('File');
  $data['filehash']['table']['join'] = array(
    'file_managed' => array(
      'left_field' => 'fid',
      'field' => 'fid',
     ),
  );
  $title = array('md5' => t('MD5 hash'), 'sha1' => t('SHA-1 hash'), 'sha256' => t('SHA-256 hash'));
  $help = array('md5' => t('The MD5 hash of the file.'), 'sha1' => t('The SHA-1 hash of the file.'), 'sha256' => t('The SHA-256 hash of the file.'));
  foreach (filehash_algos() as $algo) {
    $data['filehash'][$algo] = array(
      'title' => $title[$algo],
      'help' => $help[$algo],
      'field' => array(
        'click sortable' => TRUE,
      ),
      'argument' => array(
        'handler' => 'views_handler_argument_string',
      ),
      'filter' => array(
        'handler' => 'views_handler_filter_string',
      ),
      'sort' => array(
        'handler' => 'views_handler_sort',
      ),
    );
  }
  return $data;
}
