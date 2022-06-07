<?php

namespace Drupal\filehash\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\DeletedFieldsRepositoryInterface;
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
   * Deleted fields repository.
   *
   * @var \Drupal\Core\Field\DeletedFieldsRepositoryInterface
   */
  protected $deletedFieldsRepository;

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
   *
   * @return string[]
   *   Editable config names.
   */
  protected function getEditableConfigNames() {
    return ['filehash.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, DeletedFieldsRepositoryInterface $deleted_fields_repository, FileHashInterface $filehash, ModuleHandlerInterface $module_handler) {
    parent::__construct($config_factory);
    $this->deletedFieldsRepository = $deleted_fields_repository;
    $this->fileHash = $filehash;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_field.deleted_fields_repository'),
      $container->get('filehash'),
      $container->get('module_handler')
    );
  }

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
    $form['algos'] = [
      '#default_value' => $this->config('filehash.settings')->get('algos'),
      '#description' => $this->t('The checked hash algorithm(s) will be calculated when a file is uploaded. For optimum performance, only enable the hash algorithm(s) you need.'),
      '#options' => $this->fileHash->names(),
      '#title' => $this->t('Enabled hash algorithms'),
      '#type' => 'checkboxes',
    ];
    $form['rehash'] = [
      '#default_value' => $this->config('filehash.settings')->get('rehash'),
      '#description' => $this->t('If enabled, always regenerate the hash when saving a file, even if the hash has been generated previously. This should be enabled if you have modules that modify existing files or apply processing to uploaded files (e.g. core Image module with maximum image resolution set), and you want to keep the hash in sync with the file on disk. If disabled, the file hash represents the hash of the originally uploaded file, and will only be generated if it is missing, which is much faster.'),
      '#title' => $this->t('Always rehash file when saving'),
      '#type' => 'checkbox',
    ];
    $form['original'] = [
      '#default_value' => $this->config('filehash.settings')->get('original'),
      '#description' => $this->t('If enabled, store an additional "original" hash for each uploaded file which will not be updated. This is only useful if the above "always rehash" setting is also enabled (otherwise the file hash itself represents the hash of the originally uploaded file).'),
      '#title' => $this->t('Store an additional original hash for each uploaded file'),
      '#type' => 'checkbox',
    ];
    $form['mime_types'] = [
      '#default_value' => implode(PHP_EOL, $this->config('filehash.settings')->get('mime_types') ?? []),
      '#description' => $this->t('If set, only these MIME types will be hashed. If empty, all files will be hashed. MIME types (e.g. <em>application/octet-stream</em>) can be separated by newline, space, tab or comma.'),
      '#title' => $this->t('List of MIME types to hash'),
      '#type' => 'textarea',
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
      '#type' => 'radios',
    ];
    $form['dedupe_original'] = [
      '#default_value' => $this->config('filehash.settings')->get('dedupe_original'),
      '#description' => $this->t('If enabled, also prevent an uploaded file from being saved if its hash matches the "original" hash of another file. This is useful if you apply processing to uploaded files (e.g. core Image module with maximum image resolution set), and want to check uploads against both the original and derivative file hash. Only active if the above original file hash and dedupe settings are enabled.'),
      '#title' => $this->t('Include original file hashes in duplicate check'),
      '#type' => 'checkbox',
    ];
    $form['#attached']['library'][] = 'filehash/admin';
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * @param mixed[] $form
   *   Renderable form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);
    foreach ($form_state->getValue('algos') as $column => $value) {
      if ($value) {
        if ($this->deletedFieldsRepository->getFieldDefinitions("file-$column")) {
          $form_state->setErrorByName("algos][$column", $this->t('Please run cron first to finish deleting the %label column before enabling it.', [
            '%label' => $this->fileHash->labels()[$column],
          ]));
        }
        if ($form_state->getValue('original') && $this->deletedFieldsRepository->getFieldDefinitions("file-original_$column")) {
          $form_state->setErrorByName('original', $this->t('Please run cron first to finish deleting the %label column before enabling it.', [
            '%label' => $this->fileHash->originalLabels()[$column],
          ]));
        }
      }
    }
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
    $this->config('filehash.settings')
      ->set('algos', $form_state->getValue('algos'))
      ->set('dedupe', $form_state->getValue('dedupe'))
      ->set('rehash', $form_state->getValue('rehash'))
      ->set('original', $form_state->getValue('original'))
      ->set('dedupe_original', $form_state->getValue('dedupe_original'))
      ->set('mime_types', preg_split('/[\s,]+/', $form_state->getValue('mime_types'), -1, PREG_SPLIT_NO_EMPTY))
      ->save();
    parent::submitForm($form, $form_state);
    if (CleanBatch::columns()) {
      $this->messenger()->addStatus($this->t('Please visit the <a href="@url">clean-up tab</a> to remove unused database columns.', [
        '@url' => Url::fromRoute('filehash.clean')->toString(),
      ]));
    }
  }

}
