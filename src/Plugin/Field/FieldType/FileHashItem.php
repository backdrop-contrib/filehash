<?php

namespace Drupal\filehash\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\StringItem;

/**
 * Defines the 'filehash' entity field type.
 *
 * @FieldType(
 *   id = "filehash",
 *   label = @Translation("File Hash"),
 *   description = @Translation("A field containing a hexadecimal hash value."),
 *   category = "plain_text",
 *   default_widget = "string_textfield",
 *   default_formatter = "filehash",
 *   no_ui = TRUE
 * )
 */
class FileHashItem extends StringItem {

  /**
   * {@inheritdoc}
   *
   * @return mixed[]
   *   Field settings.
   */
  public static function defaultStorageSettings() {
    $settings = parent::defaultStorageSettings();
    $settings['is_ascii'] = TRUE;
    return $settings;
  }

  /**
   * {@inheritdoc}
   *
   * @return mixed[]
   *   Field schema.
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);
    $schema['columns']['value']['type'] = 'varchar_ascii';
    $schema['indexes']['value'] = ['value'];
    return $schema;
  }

}
