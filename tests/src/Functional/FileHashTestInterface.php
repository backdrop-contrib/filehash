<?php

namespace Drupal\Tests\filehash\Functional;

/**
 * Set some constants used across tests.
 */
interface FileHashTestInterface {
  const SHA1 = '2aae6c35c94fcfb415dbe95f408b9ce91ee846ed';
  const CONTENTS = 'hello world';

  const DIFFERENT_SHA1 = '1c52309721a59e72dba65e5e285fa54f02bd18f8';
  const DIFFERENT_CONTENTS = 'Different contents';

}
