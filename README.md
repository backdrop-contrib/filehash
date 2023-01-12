# File Hash

Hashes of uploaded files, which can be found on a variety of sites from
archive.org to wikileaks.org, allow files to be uniquely identified, allow
duplicate files to be detected, and allow copies to be verified against the
original source.

File Hash module generates and stores hashes for each file uploaded to the site.
The BLAKE2b-128, BLAKE2b-160, BLAKE2b-224, BLAKE2b-256, BLAKE2b-384,
BLAKE2b-512, MD5, SHA<span>-</span>1, SHA<span>-</span>224,
SHA<span>-</span>256, SHA<span>-</span>384, SHA<span>-</span>512/224,
SHA<span>-</span>512/256, SHA<span>-</span>512, SHA3<span>-</span>224,
SHA3<span>-</span>256, SHA3<span>-</span>384 and SHA3<span>-</span>512 hash
algorithms are supported.

If you need to verify a copy of a file, command-line utilities such as `b2sum`
can be used to generate identical file hashes.


## Requirements

Drupal core File module is required.

If you want to use the BLAKE2b hash algorithm, either the Sodium PHP extension
or `paragonie/sodium_compat` polyfill are required.


## Installation

Install as you would normally install a contributed Drupal module.


## Configuration

Hash algorithms can be enabled and disabled by the site administrator at
admin/config/media/filehash.

File hashes for pre-existing files will be generated "lazily," on demand, but
you can generate them in bulk at admin/config/media/filehash/generate or by
running `drush fgen`.

Hash values are stored as fields on the File entity, where they are available to
the theme, Views and other modules.

Tokens are provided for the full hashes: `[file:filehash-md5]`,
`[file:filehash-sha1]`, `[file:filehash-sha256]`, as well as pairtree tokens
useful for content addressable storage.

For example, if the SHA<span>-</span>256 hash for a file is
e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855, you could
store it in the files/e3/b0 directory using these tokens:
`[file:filehash-sha256-pair-1]/[file:filehash-sha256-pair-2]`.

If the "disallow duplicate files" checkbox in File Hash settings is checked, any
duplicate uploaded files will be rejected site-wide. You may also leave this
setting off, and enable the dedupe validator in the field widget settings for a
particular file upload form.


## Implementing custom logic

To implement custom logic not currently supported by built-in config options,
the `filehash` service class can be overridden by a custom class extending
`Drupal\filehash\FileHash` and implementing `Drupal\filehash\FileHashInterface`.
For example, your custom class might provide its own shouldHash() method to
determine whether or not a file should be hashed.


## Entity query support

Because this module adds fields to the file entity, you can use file hashes in
an entity query. For example:

```php
$fids = \Drupal::entityQuery('file')
  ->condition('sha256', 'my sha256 here')
  ->condition('status', 1)
  ->sort('created', 'DESC')
  ->accessCheck(TRUE)
  ->execute();
```


## Visual output

You can use the included Identicon field formatter to visualize each file hash
(in a view, for example) if you install this third-party dependency:

        composer require yzalis/identicon:^2.0


## Maintainers

File Hash module is maintained by [mfb](https://www.drupal.org/u/mfb).

You can support development by [contributing bug reports, feature suggestions
and support requests](https://www.drupal.org/project/issues/filehash) or by
[sponsoring](https://github.com/sponsors/mfb).
