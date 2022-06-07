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
   * @var string[]
   */
  public $columns;

  /**
   * {@inheritdoc}
   *
   * @param mixed[] $form
   *   Renderable form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return mixed[]
   *   Renderable form.
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
    return $this->formatPlural(count($this->columns), 'Are you sure you want to delete all data for one disabled hash algorithm column (@algos)?', 'Are you sure you want to delete all data for @count disabled hash algorithm columns (@algos)?', [
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
   *
   * @param mixed[] $form
   *   Renderable form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    batch_set(CleanBatch::createBatch());
  }

}
