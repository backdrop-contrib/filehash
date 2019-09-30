<?php

namespace Drupal\filehash\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements the file hash config form.
 */
class FileHashConfigForm extends ConfigFormBase {

  /**
   * Stores a module manager.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'filehash_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['filehash.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['algos'] = [
      '#default_value' => $this->config('filehash.settings')->get('algos'),
      '#description' => $this->t('The checked hash algorithm(s) will be calculated when a file is saved. For optimum performance, only enable the hash algorithm(s) you need.'),
      '#options' => filehash_names(),
      '#title' => $this->t('Enabled hash algorithms'),
      '#type' => 'checkboxes',
    ];
    $form['dedupe'] = [
      '#default_value' => $this->config('filehash.settings')->get('dedupe'),
      '#description' => $this->t('If checked, prevent duplicate uploaded files from being saved. Note, enabling this setting has privacy implications, as it allows users to determine if a particular file has been uploaded to the site.'),
      '#title' => $this->t('Disallow duplicate files'),
      '#type' => 'checkbox',
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $original_algos = $this->config('filehash.settings')->get('algos');
    $this->config('filehash.settings')
      ->set('algos', $form_state->getValue('algos'))
      ->set('dedupe', $form_state->getValue('dedupe'))
      ->save();
    // Invalidate the views cache if configured algorithms were modified.
    if ($this->moduleHandler->moduleExists('views') && $form_state->getValue('algos') != $original_algos) {
      views_invalidate_cache();
    }
    parent::submitForm($form, $form_state);
  }

}
