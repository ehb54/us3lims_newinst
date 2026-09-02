# Dbinst configuration generation

`makeconfig.php` retains its existing three-argument legacy behavior for
operational callers that have not migrated:

```text
php makeconfig.php <db_name> <orgsite> <ipaddress>
```

The safer base-plus-overlay form is explicit:

```text
php makeconfig.php <db_name> <orgsite> <ipaddress> --base-overlay
```

Before using that mode:

- the dbinst repository must already be cloned at the target directory so its
  versioned loader is available;
- `/home/us3/lims/etc/config/dbinst-base.v1.php` must be installed and valid;
- `/home/us3/lims/etc/config/instances` must be writable by the account running
  the generated setup command; and
- the requested address must match the base's internal or external host
  address.

The generator uses the cloned dbinst version's validator, serializes metadata
with PHP's value exporter, writes with temporary files and atomic renames, and
refuses to overwrite an existing overlay or `config.php`.

The current `create_instance.php` setup script requests `--base-overlay` and
checks the installed base and instance directory first. Other callers remain
on legacy generation until they are migrated explicitly.
