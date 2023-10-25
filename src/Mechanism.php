<?php

namespace Drupal\filehash;

/**
 * Provides the set of available hash mechanisms.
 */
enum Mechanism {

  case Hash;
  case Sodium;

}
