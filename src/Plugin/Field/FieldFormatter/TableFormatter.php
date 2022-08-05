<?php

namespace Drupal\filehash\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Plugin\Field\FieldFormatter\DescriptionAwareFileFormatterBase;
use Drupal\filehash\FileHashInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'filehash_table' formatter.
 *
 * @FieldFormatter(
 *   id = "filehash_table",
 *   label = @Translation("Table of files with hashes"),
 *   field_types = {
 *     "file",
 *     "image",
 *   }
 * )
 */
class TableFormatter extends DescriptionAwareFileFormatterBase {

  /**
   * The File Hash service.
   *
   * @var \Drupal\filehash\FileHashInterface
   */
  public $fileHash;

  /**
   * {@inheritdoc}
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param mixed[] $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param mixed[] $third_party_settings
   *   Any third party settings.
   * @param \Drupal\filehash\FileHashInterface $filehash
   *   File hash service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, FileHashInterface $filehash) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->fileHash = $filehash;
  }

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container.
   * @param mixed[] $configuration
   *   Formatter configuration.
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id, $plugin_definition, $configuration['field_definition'], $configuration['settings'],
      $configuration['label'], $configuration['view_mode'], $configuration['third_party_settings'],
      $container->get('filehash')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @return mixed[]
   *   Renderable table.
   *
   * @phpstan-ignore-next-line Parameter $items does not specify its types.
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    // @phpstan-ignore-next-line Ignore parameter $items type mismatch.
    if ($files = $this->getEntitiesToView($items, $langcode)) {
      $header = [
        $this->t('Attachment'),
        $this->t('Size'),
        $this->fileHash->labels()[$this->getSetting('algo')],
      ];
      $rows = [];
      foreach ($files as $file) {
        if (isset($file->_referringItem)) {
          $item = $file->_referringItem;
        }
        $rows[] = [
          [
            'data' => [
              '#theme' => 'file_link',
              '#file' => $file,
              '#description' => ($this->getSetting('use_description_as_link_text') && isset($item)) ? $item->description : NULL,
              '#cache' => ['tags' => $file->getCacheTags()],
            ],
          ],
          ['data' => format_size(method_exists($file, 'getSize') ? $file->getSize() : 0)],
          [
            'data' => [
              '#type' => 'html_tag',
              '#tag' => 'span',
              '#value' => $file->{$this->getSetting('algo')}->value ?? '',
              '#attributes' => ['class' => ['filehash-value']],
              '#attached' => ['library' => ['filehash/field']],
            ],
          ],
        ];
      }

      $elements[0] = [
        '#theme' => 'table__filehash_formatter_table',
        '#header' => $header,
        '#rows' => $rows,
      ];
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   *
   * @return mixed[]
   *   Formatter settings.
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();
    $columns = \Drupal::service('filehash')->columns();
    $settings['algo'] = array_pop($columns);
    return $settings;
  }

  /**
   * {@inheritdoc}
   *
   * @param mixed[] $form
   *   Renderable settings form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return mixed[]
   *   Renderable settings form.
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $names = $this->fileHash->names();
    $options = [];
    foreach ($this->fileHash->columns() as $column) {
      $options[$column] = $names[$column];
    }
    $form['algo'] = [
      '#title' => $this->t('Hash algorithm'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('algo'),
      '#options' => $options,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $names = $this->fileHash->names();
    if (isset($names[$this->getSetting('algo')])) {
      $summary[] = $this->t('@algo hash', ['@algo' => $names[$this->getSetting('algo')]]);
    }
    return $summary;
  }

}
