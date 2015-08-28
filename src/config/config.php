<?php

/*
 * This file is part of CrowdAuth
 *
 * (c) Daniel McAssey <hello@glokon.me>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return array(

    /*
    |--------------------------------------------------------------------------
    | Crowd Auth: Crowd URL
    |--------------------------------------------------------------------------
    | Please specify the URL to your crowd service for authentication, it must
    | NOT end in a forward slash, must NOT be redirected and be a publicly accessible URL.
    */

    'url' => 'https://crowd.example.com:8080/crowd',
    
    /*
    |--------------------------------------------------------------------------
    | Crowd Auth: Check TLS certificate
    |--------------------------------------------------------------------------
    | Check SSL/TLS certificate of endpoint
    */
    
    'verify_certificate' => true,
    
    /*
    |--------------------------------------------------------------------------
    | Crowd Auth: Application Name
    |--------------------------------------------------------------------------
    | Here is where you specify your application name that you use for your
    | crowd application.
    */
    
    'app_name' => 'my_appname',

    /*
    |--------------------------------------------------------------------------
    | Crowd Auth: Application Password
    |--------------------------------------------------------------------------
    | Here is where you specify your password that you use for your crowd
    | application.
    */

    'app_password' => 'my_app_password',

    /*
    |--------------------------------------------------------------------------
    | Crowd Auth: Usable User Groups
    |--------------------------------------------------------------------------
    |
    | Here is where you define each of the groups that have access to your
    | application. If this array is empty, no group membership check is applied.
    */

    'app_groups' => array(
        'app-administrators',
        'app-users',
    ),
    
    /*
    |--------------------------------------------------------------------------
    | Crowd Auth: Model for Crowd users
    |--------------------------------------------------------------------------
    |
    | Map Crowd users to this class. You can inherit from the default class
    */
    
    'crowd_user_model' => 'GLOKON\CrowdAuth\Model\CrowdUser',

    /*
    |--------------------------------------------------------------------------
    | Crowd Auth: Model for Crowd groups
    |--------------------------------------------------------------------------
    |
    | Map Crowd groups to this class. You can inherit from the default class
    */
    
    'crowd_group_model' => 'GLOKON\CrowdAuth\Model\CrowdGroup',

    /*
    |--------------------------------------------------------------------------
    | Crowd Auth: Force IP address of client
    |--------------------------------------------------------------------------
    |
    | Useful for debugging if you access the REST API through localhost/127.0.0.1 or ::1.
    | In most cases this should be null and the detected REMOTE_ADDR is used.
    */
    
    'force_client_ip' => null,
);
