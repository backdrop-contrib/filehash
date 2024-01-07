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
 * Validates the FileHashDedupe constraint.
 */
class FileHashDedupeValidator extends BaseFileConstraintValidator implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Creates a new FileHashDedupeValidator.
   */
  final public function __construct(
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
    foreach ($this->fileHash->getEnabledAlgorithms() as $column) {
      try {
        $fid = $this->fileHash->duplicateLookup($column, $file, $constraint->strict, $constraint->original);
      }
      catch (DatabaseExceptionWrapper $e) {
        $this->fileHash->addColumns();
        $fid = $this->fileHash->duplicateLookup($column, $file, $constraint->strict, $constraint->original);
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
   *
   * @deprecated in filehash:3.0.1 and is removed from filehash:4.0.0. Use
   * \Drupal\filehash\FileHash::duplicateLookup() instead.
   * @see https://www.drupal.org/project/filehash/issues/3412404
   */
  public function duplicateLookup(string $column, FileInterface $file, bool $strict = FALSE, bool $original = FALSE): ?string {
    return $this->fileHash->duplicateLookup($column, $file, $strict, $original);
  }

}
