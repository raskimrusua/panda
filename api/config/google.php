<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Google OAuth — sign-in credential verification
    |--------------------------------------------------------------------------
    |
    | The PWA renders a "Continue with Google" button (@react-oauth/google).
    | The credential is a Google-issued ID token (JWT). We verify it against
    | Google's tokeninfo endpoint and MUST match the audience to this client
    | id — otherwise any valid Google token (from any app) would authenticate.
    |
    | The client id is public by design (it ships in the JS bundle); the
    | security boundary is the audience check + email_verified, not secrecy.
    |
    */

    'oauth_client_id' => env('GOOGLE_OAUTH_CLIENT_ID', ''),

];
