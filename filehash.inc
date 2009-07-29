<?php
// $Id$

/**
 * @file
 * Generate hashes for each file.
 */

/**
 * Return array of enabled hash algorithms.
 */
function filehash_algos() {
  return array_diff(variable_get('filehash_algos', array('sha256')), array(0));
}

/**
 * Implementation of hook_file_delete().
 */
function filehash_file_delete($file) {
  db_delete('filehash')
    ->condition('fid', $file->fid)
    ->execute();
}

/**
 * Implementation of hook_file_insert().
 */
function filehash_file_insert($file) {
  filehash_save($file);
}

/**
 * Implementation of hook_file_load().
 */
function filehash_file_load($files) {
  $algos = filehash_algos();
  $result = db_select('filehash')
    ->fields('filehash')
    ->condition('fid', array_keys($files), 'IN')
    ->execute();
  foreach ($result as $record) {
    foreach ($algos as $algo) {
      $files[$record->fid]->filehash[$algo] = $record->$algo;
    }
  }
  // Generate hash if it does not already exist for the file.
  foreach ($files as $fid => $file) {
    foreach ($algos as $algo) {
      if (empty($file->filehash[$algo])) {
        filehash_save($files[$fid]);
        break;
      }
    }
  }
}

/**
 * Implementation of hook_file_update().
 */
function filehash_file_update($file) {  
  filehash_save($file);
}

/**
 * Calculate and save the file hashes.
 */
function filehash_save($file) {
  $file->filehash = array_fill_keys(array('md5', 'sha1', 'sha256'), '');
  foreach (filehash_algos() as $algo) {
    $file->filehash[$algo] = hash_file($algo, $file->filepath);
  }
  db_merge('filehash')
    ->key(array('fid' => $file->fid))
    ->fields($file->filehash)
    ->execute();
}
