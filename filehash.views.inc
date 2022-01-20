<?php

/**
 * @file
 * Provide views data and handlers for filehash table.
 */

/**
 * Implements hook_views_data().
 */
function filehash_views_data() {
  $data['filehash']['table']['group'] = t('File Hash');
  $data['filehash']['table']['join'] = [
    'file_managed' => [
      'left_field' => 'fid',
      'field' => 'fid',
    ],
  ];
  // Use a nice human-readable name for each hash algorithm.
  $names = filehash_names();
  foreach (filehash_columns() as $column) {
    $data['filehash'][$column] = [
      'title' => t('@algo hash', ['@algo' => $names[$column]]),
      'help' => t('The @algo hash of the file.', ['@algo' => $names[$column]]),
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
