<?php
/*
 * session.php
 *
 * Starts the session with its cookie flags set. Included in place of a bare
 * session_start() so a new page cannot forget them: httponly keeps the cookie
 * out of JavaScript, and secure is set whenever the request arrived over TLS.
 *
 * secure is conditional rather than always on. Forcing it on an http
 * deployment makes the browser drop the cookie, which logs every user out and
 * looks like a broken login rather than a hardening change.
 */

if ( session_status() === PHP_SESSION_NONE )
{
    $us3_session_secure =
        ( ! empty( $_SERVER[ 'HTTPS' ] ) && strtolower( $_SERVER[ 'HTTPS' ] ) !== 'off' )
        || ( isset( $_SERVER[ 'HTTP_X_FORWARDED_PROTO' ] )
             && strtolower( $_SERVER[ 'HTTP_X_FORWARDED_PROTO' ] ) === 'https' )
        || ( isset( $_SERVER[ 'SERVER_PORT' ] ) && (int) $_SERVER[ 'SERVER_PORT' ] === 443 );

    if ( PHP_VERSION_ID >= 70300 )
    {
        ## SameSite needs the array form, which is 7.3 and later.
        session_set_cookie_params( array(
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => $us3_session_secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ) );
    }
    else
    {
        ## 7.2 has no SameSite parameter; the flags that matter still apply.
        session_set_cookie_params( 0, '/', '', $us3_session_secure, true );
    }

    session_start();
}
