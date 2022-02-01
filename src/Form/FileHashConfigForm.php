<?php

namespace Drupal\filehash\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\filehash\Batch\CleanBatch;
use Drupal\filehash\FileHashInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements the file hash config form.
 */
class FileHashConfigForm extends ConfigFormBase {

  /**
   * File Hash service.
   *
   * @var \Drupal\filehash\FileHashInterface
   */
  protected $fileHash;

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
  public function __construct(ConfigFactoryInterface $config_factory, FileHashInterface $filehash, ModuleHandlerInterface $module_handler) {
    parent::__construct($config_factory);
    $this->fileHash = $filehash;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('filehash'),
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
      '#options' => $this->fileHash->names(),
      '#title' => $this->t('Enabled hash algorithms'),
      '#type' => 'checkboxes',
    ];
    $form['rehash'] = [
      '#default_value' => $this->config('filehash.settings')->get('rehash'),
      '#description' => $this->t('If checked, always regenerate the hash when saving a file, even if the hash has been generated previously. This should be enabled if you have modules that modify the file on disk and you want to make sure the hash is in sync. If disabled, only generate the hash if it is missing, which is much faster.'),
      '#title' => $this->t('Always rehash file when saving'),
      '#type' => 'checkbox',
    ];
    $form['original'] = [
      '#default_value' => $this->config('filehash.settings')->get('original'),
      '#description' => $this->t('If checked, store an additional "original" hash for each uploaded file which will not be updated.'),
      '#title' => $this->t('Store an additional original hash for each uploaded file'),
      '#type' => 'checkbox',
    ];
    $form['dedupe'] = [
      '#default_value' => $this->config('filehash.settings')->get('dedupe'),
      '#description' => $this->t('If enabled, prevent duplicate uploaded files from being saved when the file already exists as a permanent file. If strict, also include temporary files in the duplicate check, which prevents duplicates from being uploaded at the same time. If off, you can still disallow duplicate files in the widget settings for any particular file upload field. Note, enabling this setting has privacy implications, as it allows users to determine if a particular file has been uploaded to the site.'),
      '#title' => $this->t('Disallow duplicate files'),
      '#options' => [
        $this->t('Off (use field widget settings)'),
        $this->t('Enabled'),
        $this->t('Strict'),
      ],
      '#type' => 'select',
    ];
    $form['#attached']['library'][] = 'filehash/admin';
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('filehash.settings')
      ->set('algos', $form_state->getValue('algos'))
      ->set('dedupe', $form_state->getValue('dedupe'))
      ->set('rehash', $form_state->getValue('rehash'))
      ->set('original', $form_state->getValue('original'))
      ->save();
    parent::submitForm($form, $form_state);
    if (CleanBatch::columns()) {
      $this->messenger()->addStatus($this->t('Please visit the <a href="@url">clean-up tab</a> to remove unused database columns.', [
        '@url' => Url::fromRoute('filehash.clean')->toString(),
      ]));
    }
  }

}
