<?php

namespace Drupal\filehash\Plugin\Validation\Constraint;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\file\Plugin\Validation\Constraint\BaseFileConstraintValidator;
use Drupal\filehash\FileHashInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates the FileSizeLimitConstraint.
 */
class FileHashDedupeValidator extends BaseFileConstraintValidator implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Creates a new FileSizeConstraintValidator.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected AccountInterface $currentUser,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected FileHashInterface $fileHash,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('filehash'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, Constraint $constraint): void {
    $file = $this->assertValueIsFile($value);
    if (!$constraint instanceof FileHashDedupe) {
      throw new UnexpectedTypeException($constraint, FileHashDedupe::class);
    }
    foreach ($this->fileHash->columns() as $column) {
      try {
        $fid = $this->duplicateLookup($column, $file, $constraint->strict, $constraint->original);
      }
      catch (DatabaseExceptionWrapper $e) {
        $this->fileHash->addColumns();
        $fid = $this->duplicateLookup($column, $file, $constraint->strict, $constraint->original);
      }
      if ($fid) {
        if ($this->currentUser->hasPermission('access files overview')) {
          try {
            $url = Url::fromRoute('view.files.page_2', ['arg_0' => $fid], ['attributes' => ['target' => '_blank']]);
            $this->context->addViolation($this->t('This file has already been uploaded as %filename.', [
              '%filename' => Link::fromTextAndUrl($this->entityTypeManager->getStorage('file')->load($fid)->label(), $url)->toString(),
            ]));
          }
          catch (\Exception $e) {
            // Maybe the view was disabled?
            $this->context->addViolation($constraint->message);
          }
        }
        else {
          $this->context->addViolation($constraint->message);
        }
        break;
      }
    }

  }

  /**
   * Returns file ID for any duplicates.
   */
  public function duplicateLookup(string $column, FileInterface $file, bool $strict = FALSE, bool $original = FALSE): ?string {
    if (is_null($file->{$column}->value)) {
      return NULL;
    }
    // @fixme This code results in *multiple* SQL joins on the file_managed
    // table; if slow maybe it should be refactored to use a normal database
    // query? See also https://www.drupal.org/project/drupal/issues/2875033
    $query = $this->entityTypeManager->getStorage('file')->getQuery('AND');
    if ($original && $this->configFactory->get('filehash.settings')->get('original')) {
      $group = $query->orConditionGroup()
        ->condition("original_$column", $file->{$column}->value, '=')
        ->condition($column, $file->{$column}->value, '=');
      $query->condition($group);
    }
    else {
      $query->condition($column, $file->{$column}->value);
    }
    if (!$strict) {
      $query->condition('status', FileInterface::STATUS_PERMANENT, '=');
    }
    $results = $query->range(0, 1)
      ->accessCheck(FALSE)
      ->execute();
    return reset($results) ?: NULL;
  }

}
