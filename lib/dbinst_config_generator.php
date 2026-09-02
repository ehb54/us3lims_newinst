<?php
/* Pure helpers for generating schema-v1 dbinst overlays and config shims. */

function us3_newinst_config_fail( $message )
{
  throw new RuntimeException( 'newinst configuration: ' . $message );
}

function us3_newinst_overlay_contract( $instance, $values )
{
  return us3_dbinst_config_overlay_contract( $instance, $values );
}

function us3_newinst_overlay_source( $contract )
{
  return us3_dbinst_config_overlay_source( $contract );
}

function us3_newinst_config_shim_source( $instance )
{
  return us3_dbinst_config_shim_source( $instance );
}

/*
 * Read a loader's declared contract version without executing it.
 *
 * The file cannot simply be included to ask: another checkout's copy is
 * already loaded in the cases this exists for, and PHP would fatal on the
 * redeclare. A loader with no version declaration is treated as unreadable
 * rather than assumed compatible.
 */
function us3_newinst_loader_contract_version( $path )
{
  $source = @file_get_contents( $path );
  if ( $source === false )
    us3_newinst_config_fail( "dbinst loader is unreadable: $path" );

  if ( !preg_match( '/function\s+us3_dbinst_config_contract_version\s*\(\s*\)'
                    . '\s*\{\s*return\s+(\d+)\s*;/', $source, $matches ) )
    us3_newinst_config_fail(
      "dbinst loader declares no contract version: $path" );

  return (int) $matches[ 1 ];
}

/*
 * Make the target checkout's configuration contract available.
 *
 * Generation must validate an overlay against the contract of the dbinst
 * version that will consume it. Normally that is a plain require of the
 * loader shipped in that checkout. But every instance has its own checkout,
 * so a process generating a second instance already has a first loader's
 * functions defined and PHP cannot redeclare them.
 *
 * Reusing whatever loaded first is only safe when it implements the same
 * contract. When it does not, the alternative to failing here is validating
 * the overlay against the wrong version's rules and writing a file the
 * consuming dbinst will reject or, worse, silently misread. The remedy is one
 * process per instance, which is what the generated setup script already does.
 */
function us3_newinst_require_dbinst_loader( $dbinst_dir )
{
  $loader = rtrim( $dbinst_dir, '/' ) . '/lib/dbinst_config_loader.php';
  if ( !is_file( $loader ) )
    us3_newinst_config_fail( "dbinst loader is missing: $loader" );

  if ( !function_exists( 'us3_dbinst_config_load_base' ) )
  {
    require_once $loader;
    return $loader;
  }

  if ( !function_exists( 'us3_dbinst_config_contract_version' ) )
    us3_newinst_config_fail(
      'a dbinst configuration loader that declares no contract version is '
      . 'already loaded in this process, so it cannot be shown to match '
      . "$loader; generate this instance in its own process" );

  $loaded = us3_dbinst_config_contract_version();
  $target = us3_newinst_loader_contract_version( $loader );
  if ( $loaded !== $target )
    us3_newinst_config_fail(
      "a v$loaded dbinst configuration loader is already loaded in this "
      . "process but $loader implements v$target; generate this instance in "
      . 'its own process so its own loader is used' );

  return $loader;
}

function us3_newinst_write_temp( $destination, $source, $mode )
{
  $directory = dirname( $destination );
  if ( !is_dir( $directory ) || !is_writable( $directory ) )
    us3_newinst_config_fail( "directory is missing or unwritable: $directory" );

  $temporary = tempnam( $directory, '.us3-config-' );
  if ( $temporary === false )
    us3_newinst_config_fail( "could not create temporary file in $directory" );

  if ( file_put_contents( $temporary, $source ) === false )
  {
    @unlink( $temporary );
    us3_newinst_config_fail( "could not write temporary file in $directory" );
  }

  if ( !chmod( $temporary, $mode ) )
  {
    @unlink( $temporary );
    us3_newinst_config_fail( "could not set permissions on temporary file" );
  }

  return $temporary;
}

/*
 * Write both files without replacing an existing configuration. The overlay
 * is harmless until the shim exists; if the second install fails it is removed.
 */
function us3_newinst_write_base_overlay( $instance, $overlay_values,
                                         $config_root, $dbinst_dir )
{
  $config_root = rtrim( $config_root, '/' );
  $dbinst_dir = rtrim( $dbinst_dir, '/' );
  us3_newinst_require_dbinst_loader( $dbinst_dir );
  us3_dbinst_config_assert_instance( $instance );

  $base_values = us3_dbinst_config_load_base( $config_root );
  $expected_dir = us3_dbinst_config_path_with_slash(
    $base_values[ 'dbinst_root' ] ) . $instance;
  if ( rtrim( $expected_dir, '/' ) !== $dbinst_dir )
    us3_newinst_config_fail(
      "dbinst directory does not match the v1 base dbinst_root" );

  $contract = us3_newinst_overlay_contract( $instance, $overlay_values );
  us3_dbinst_config_validate_overlay( $contract, $instance );

  $overlay_path = $config_root . '/instances/' . $instance . '.php';
  $shim_path = $dbinst_dir . '/config.php';
  if ( file_exists( $overlay_path ) )
    us3_newinst_config_fail( "overlay already exists: $overlay_path" );
  if ( file_exists( $shim_path ) )
    us3_newinst_config_fail( "config.php already exists: $shim_path" );

  $overlay_temp = us3_newinst_write_temp(
    $overlay_path, us3_newinst_overlay_source( $contract ), 0640 );
  $shim_temp = us3_newinst_write_temp(
    $shim_path, us3_newinst_config_shim_source( $instance ), 0644 );

  ## Install with link(), not rename(). rename() silently REPLACES an existing
  ## destination, so the file_exists() checks above are only a fast path with a
  ## readable message: two generators running at once would both pass them and
  ## the loser's overlay would be destroyed without any error. link() fails
  ## when the destination exists, which makes the check and the install one
  ## operation and makes the documented refusal-to-overwrite actually hold.
  if ( !@link( $overlay_temp, $overlay_path ) )
  {
    $overlay_exists = file_exists( $overlay_path ) || is_link( $overlay_path );
    @unlink( $overlay_temp );
    @unlink( $shim_temp );
    if ( $overlay_exists )
      us3_newinst_config_fail( "overlay already exists: $overlay_path" );
    us3_newinst_config_fail( "could not install overlay: $overlay_path" );
  }
  @unlink( $overlay_temp );

  if ( !@link( $shim_temp, $shim_path ) )
  {
    $shim_exists = file_exists( $shim_path ) || is_link( $shim_path );
    @unlink( $shim_temp );
    @unlink( $overlay_path );
    if ( $shim_exists )
      us3_newinst_config_fail( "config.php already exists: $shim_path" );
    us3_newinst_config_fail( "could not install config.php: $shim_path" );
  }
  @unlink( $shim_temp );

  return array( 'overlay' => $overlay_path, 'shim' => $shim_path );
}
