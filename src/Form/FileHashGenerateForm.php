<?php

namespace Drupal\filehash\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\filehash\Batch\GenerateBatch;

/**
 * Implements the file MIME generate settings form.
 */
class FileHashGenerateForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
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
    return $this->t('Are you sure you want to generate hashes for all previously uploaded files? Hashes for @count uploaded files will be generated.', ['@count' => number_format(GenerateBatch::count())]);
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
    batch_set(GenerateBatch::createBatch());
  }

}
