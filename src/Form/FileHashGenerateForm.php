<?php

namespace Drupal\filehash\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;

/**
 * Implements the file MIME generate settings form.
 */
class FileHashGenerateForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'filehash_generate_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Generate file hashes for all uploaded files?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Are you sure you want to generate hashes for all previously uploaded files? Hashes for @count uploaded files will be generated.', ['@count' => number_format(self::count())]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Generate');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('filehash.admin');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    batch_set([
      'operations' => [['\Drupal\filehash\Form\FileHashGenerateForm::process', []]],
      'finished' => '\Drupal\filehash\Form\FileHashGenerateForm::finished',
      'title' => $this->t('Processing file hash batch'),
      'init_message' => $this->t('File hash batch is starting.'),
      'progress_message' => $this->t('Please wait...'),
      'error_message' => $this->t('File hash batch has encountered an error.'),
      'file' => drupal_get_path('module', 'filehash') . '/src/Form/FileHashGenerateForm.php',
    ]);
  }

  /**
   * Returns count of files in file_managed table.
   */
  public static function count() {
    return \Drupal::database()->query('SELECT COUNT(*) FROM {file_managed}')->fetchField();
  }

  /**
   * Batch process callback.
   */
  public static function process(&$context) {
    if (!isset($context['results']['processed'])) {
      $context['results']['processed'] = 0;
      $context['results']['updated'] = 0;
      $context['sandbox']['count'] = self::count();
    }
    $files = \Drupal::database()->select('file_managed')->fields('file_managed', ['fid'])->range($context['results']['processed'], 1)->execute();
    foreach ($files as $file) {
      // Fully load file object.
      $file = File::load($file->fid);
      $variables = ['%url' => $file->getFileUri()];
      $context['message'] = t('Generated file hash for %url.', $variables);
    }
    $context['results']['processed']++;
    $context['finished'] = $context['sandbox']['count'] ? $context['results']['processed'] / $context['sandbox']['count'] : 1;
  }

  /**
   * Batch finish callback.
   */
  public static function finished($success, $results, $operations) {
    $variables = ['@processed' => $results['processed']];
    if ($success) {
      drupal_set_message(t('Processed @processed files.', $variables));
    }
    else {
      drupal_set_message(t('An error occurred after processing @processed files.', $variables), 'warning');
    }
  }

}
