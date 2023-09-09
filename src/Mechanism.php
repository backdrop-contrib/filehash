<?php

/**
 * @file
 * Provides the set of available hash mechanisms.
 */

namespace Drupal\filehash;

enum Mechanism {

  case Hash;
  case Sodium;

}
