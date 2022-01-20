<?php

namespace Drupal\filehash\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\filehash\Batch\CleanBatch;

/**
 * Implements the File Hash clean form.
 */
class FileHashCleanForm extends ConfirmFormBase {

  /**
   * The database columns to be purged.
   *
   * @var array
   */
  public $columns;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->columns = CleanBatch::columns();
    $form = parent::buildForm($form, $form_state);
    if (!$this->columns) {
      unset($form['actions']['submit']);
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'filehash_clean_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Delete data for disabled hash algorithms?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    if (!$this->columns) {
      return $this->t('All clean! No disabled hash algorithms are installed.');
    }
    return $this->t('Are you sure you want to delete data for disabled hash algorithms? @count hash algorithm columns (@algos) will be purged from the database.', [
      '@count' => count($this->columns),
      '@algos' => implode(', ', $this->columns),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->columns ? $this->t('Delete') : $this->t('Ok');
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
  public function getCancelText() {
    return $this->columns ? $this->t('Cancel') : $this->t('Ok');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    batch_set(CleanBatch::createBatch());
  }

}
