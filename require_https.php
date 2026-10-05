<?php
// Credentials and sessions are HTTPS only: redirect plain HTTP to the configured site.

/**
 * Where a plain HTTP request should be sent, or null when there is nowhere safe.
 *
 * The host comes from $org_site and never from the request. A Host header is
 * attacker-controlled, and an empty $org_site used to leave 'https://' followed
 * directly by REQUEST_URI: for a request to '//elsewhere.example/x' that is
 * 'https:////elsewhere.example/x', which a browser reads as a redirect to
 * elsewhere.example. So the host is validated, and without one this returns
 * null rather than a target that points off the server.
 *
 * REQUEST_URI is appended as-is, which is safe once the host is present: the
 * authority ends at the first slash, so a path beginning '//' stays a path.
 */
## Guarded: login.php and checkuser.php both `include` this file rather than
## `include_once`, and login.php is itself included from checkuser.php's error
## paths. A second plain include redeclared this function and turned every
## failed login into a fatal "Cannot redeclare require_https_target()" (HTTP
## 500) instead of the login page with a message.
if ( ! function_exists( 'require_https_target' ) )
{
function require_https_target( $org_site, $request_uri )
{
    $host = strtok( (string) $org_site, '/' );

    if ( $host === false || ! preg_match( '/^[A-Za-z0-9]([A-Za-z0-9.-]*[A-Za-z0-9])?(:\d{1,5})?$/D', $host ) )
    {
        return null;
    }

    return 'https://' . $host . (string) $request_uri;
}
}

## Same three signals session.php already trusts for its cookie's secure flag:
## a TLS-terminating proxy never sets $_SERVER['HTTPS'], so checking only that
## redirected every request behind one, forever, in a loop.
$us3_is_https =
    ( ! empty( $_SERVER[ 'HTTPS' ] ) && strtolower( $_SERVER[ 'HTTPS' ] ) !== 'off' )
    || ( isset( $_SERVER[ 'HTTP_X_FORWARDED_PROTO' ] )
         && strtolower( $_SERVER[ 'HTTP_X_FORWARDED_PROTO' ] ) === 'https' )
    || ( isset( $_SERVER[ 'SERVER_PORT' ] ) && (int) $_SERVER[ 'SERVER_PORT' ] === 443 );

if ( PHP_SAPI !== 'cli' && ! $us3_is_https )
{
    $target = require_https_target( isset( $org_site ) ? $org_site : '',
                                    isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '/' );

    if ( $target === null )
    {
        // Nothing is served over plain HTTP, so a missing site name fails closed
        // rather than falling back to the request's own host.
        header( 'Retry-After: 300' );
        header( 'HTTP/1.1 503 Service Unavailable', true, 503 );
        exit( "This server has no configured HTTPS site name.\n" );
    }

    header( 'Location: ' . $target, true, 301 );
    exit();
}
