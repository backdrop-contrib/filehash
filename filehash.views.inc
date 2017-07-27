<?php

/**
 * @file
 * Provide views data and handlers for filehash table.
 */

/**
 * Implements hook_views_data().
 */
function filehash_views_data() {
  $data['filehash']['table']['group'] = t('File');
  $data['filehash']['table']['join'] = [
    'file_managed' => [
      'left_field' => 'fid',
      'field' => 'fid',
    ],
  ];
  // Use a nice human-readable name for each hash algorithm.
  $names = filehash_names();
  foreach (filehash_algos() as $algo) {
    $data['filehash'][$algo] = [
      'title' => t('@algo hash', ['@algo' => $names[$algo]]),
      'help' => t('The @algo hash of the file.', ['@algo' => $names[$algo]]),
      'field' => [
        'id' => 'standard',
        'click sortable' => TRUE,
      ],
      'argument' => [
        'id' => 'string',
      ],
      'filter' => [
        'id' => 'string',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];
  }
  return $data;
}
