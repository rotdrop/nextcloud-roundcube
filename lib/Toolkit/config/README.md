## What is this?

This folders contains some config examples for Nextcloud, e.g. to
better support archive MIME-types and the like. Please make sure to
also read the
[Nextcloud admin-documentation](https://docs.nextcloud.com/server/latest/admin_manual)
before (then non-)blindly using these examples.

## Content

- [nextcloud/mimetypemapping.json](nextcloud/mimetypemapping.json)
  - see [Nextcloud MIME-type configuration](https://docs.nextcloud.com/server/latest/admin_manual/configuration_mimetypes/index.html)
  - Note that is neccessary to run
    ```
    occ maintenance:mimetype:update-db
    ```
    after adding the content of this file to the nextcloud
    configuration. If you don't like the mappings, just customize to
    your needs and/or place a pull-
- [nextcloud/mimetypealiases.json](nextcloud/mimetypealiases.json)
  - see [Nextcloud MIME-type configuration](https://docs.nextcloud.com/server/latest/admin_manual/configuration_mimetypes/index.html)
  - Note that is neccessary to run
    ```
    occ maintenance:mimetype:update-js
    ```
    after adding the content of this file to the nextcloud
    configuration. The file just maps all supported archive and
    compression type to `package/x-generic`. If you do not like this,
    then customize! Note that the effect of these mime-type aliases is
    just the modification of the file-type icon in the UI.
