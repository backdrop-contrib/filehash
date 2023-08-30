<?php

namespace Drupal\filehash\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Validation\ConstraintManager;
use Drupal\file\Validation\FileValidationEvent;
use Drupal\filehash\FileHashInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Provides a validation listener for file hash dedupe.
 */
class FileValidationSubscriber implements EventSubscriberInterface {

  /**
   * Constructs the file validation subscriber.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected ConstraintManager $constraintManager,
    protected ValidatorInterface $validator,
  ) {
  }

  /**
   * Handles the file validation event.
   *
   * @param \Drupal\file\Validation\FileValidationEvent $event
   *   The event.
   */
  public function onFileValidation(FileValidationEvent $event): void {
    // We only run the dedupe check on initial file creation.
    if ($event->file->id()) {
      return;
    }
    $config = $this->configFactory->get('filehash.settings');
    $dedupe = $config->get('dedupe');
    if (!$dedupe) {
      return;
    }
    $options['strict'] = FileHashInterface::STRICT_DEDUPE == $dedupe;
    $options['original'] = $config->get('dedupe_original') ?? FALSE;
    $constraints[] = $this->constraintManager->create('FileHashDedupe', $options);
    $fileTypedData = $event->file->getTypedData();
    $event->violations->addAll($this->validator->validate($fileTypedData, $constraints));
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [FileValidationEvent::class => 'onFileValidation'];
  }

}
