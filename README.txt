FILE HASH
---------

Hashes of uploaded files, which can be found on a variety of sites from
archive.org to wikileaks.org, allow files to be uniquely identified, allow
duplicate files to be detected, and allow copies to be verified against the
original source.

File Hash module generates and stores MD5, SHA-1 and/or SHA-256 hashes for each
file uploaded to the site.

Hash algorithms can be enabled and disabled by the site administrator.

Hash values are loaded into the $file object where they are available to the
theme and other modules.

Handlers are provided for Views module compatibility. In addition, a
<media:hash> element is added for file attachments in node RSS feeds (file,
image, and media field types are supported).

Tokens are provided for the full hashes: [file:filehash-md5],
[file:filehash-sha1], [file:filehash-sha256], as well as pairtree tokens useful
for content addressable storage.

For example, if the SHA-256 hash for a file is
e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855, you could
store it in the files/e3/b0 directory using these tokens:
[file:filehash-sha256-pair-1]/[file:filehash-sha256-pair-2].

If the "disallow duplicate files" checkbox in File Hash settings is checked, any
duplicate uploaded files will be rejected site-wide. You may also leave this
setting off, and apply the dedupe validator function manually to a particular
file upload form in a custom module:

```
<?php

/**
 * Implements hook_form_alter().
 */
function example_form_alter(&$form, &$form_state, $form_id) {
  if ($form_id == 'node_article_edit_form') {
    foreach ($form['field_image']['widget'] as $item) {
      if (is_array($item) && isset($item['#delta'])) {
        $form['field_image']['widget'][$item['#delta']]['#upload_validators']['filehash_validate_dedupe'] = [];
      }
    }
  }
}

```
