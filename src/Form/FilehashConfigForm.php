<?php

/**
 * @file
 * Contains \Drupal\filehash\Form\FilehashConfigForm.
 */

namespace Drupal\filehash\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;

/**
 * Implements the file hash config form.
 */
class FilehashConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormID() {
    return 'filehash_config_form';
  }

  /**
   * {@inheritdoc}.
   */
  protected function getEditableConfigNames() {
    return ['filehash.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['algos'] = array(
      '#default_value' => $this->config('filehash.settings')->get('algos'),
      '#description' => t('The checked hash algorithm(s) will be calculated when a file is saved. For optimum performance, only enable the hash algorithm(s) you need.'),
      '#options' => filehash_names(),
      '#title' => t('Enabled hash algorithms'),
      '#type' => 'checkboxes',
    );
    $form['dedupe'] = array(
      '#default_value' => $this->config('filehash.settings')->get('dedupe'),
      '#description' => t('If checked, prevent duplicate uploaded files from being saved. Note, enabling this setting has privacy implications, as it allows users to determine if a particular file has been uploaded to the site.'),
      '#title' => t('Disallow duplicate files'),
      '#type' => 'checkbox',
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('filehash.settings')
      ->set('algos', $form_state->getValue('algos'))
      ->set('dedupe', $form_state->getValue('dedupe'))
      ->save();
    parent::submitForm($form, $form_state);
  }
}
