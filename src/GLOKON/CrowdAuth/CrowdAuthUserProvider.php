<?php

/*
 * This file is part of CrowdAuth
 *
 * (c) Daniel McAssey <hello@glokon.me>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GLOKON\CrowdAuth;

use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Auth\GenericUser;
use GLOKON\CrowdAuth\Models\CrowdUser;
use GLOKON\CrowdAuth\Models\CrowdGroup;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Log;

class CrowdAuthUserProvider implements UserProvider {

    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed  $identifier
     * @return GenericUser|null
     */
    public function retrieveById($identifier) {
    
        if ($identifier != null) {
            if (\App::make('crowd-auth')->doesUserExist($identifier)) {
                $userData = \App::make('crowd-auth')->getUser($identifier);
                if ($userData != null) {
                    return new GenericUser([
                            'id' => $userData['user-name'],
                            'username' => $userData['user-name'],
                            'key' => $userData['key'],
                            'displayName' => $userData['display-name'],
                            'firstName' => $userData['first-name'],
                            'lastName' => $userData['last-name'],
                            'email' => $userData['email'],
                            'usergroups' => $userData['groups'],
                        ]);
                }
            }
        }
        return null;
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array  $credentials
     * @return GenericUser|null
     */
    public function retrieveByCredentials(array $credentials) {
        if (isset($credentials['username'])) {
            return $this->retrieveById($credentials['username']);
        }
        return null;
    }

	/**
	 * Retrieve IP address of client
	 * @return string a forced IP addresss or the REMOTE_ADDR
	 */
	public function getClientIp() {
		$forcedClientIp = \Config::get('crowd-auth.force_client_ip');
		$useClientIp = $forcedClientIp ? $forcedClientIp : $_SERVER['REMOTE_ADDR'];
		
		return filter_var($useClientIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
	}
	
	/**
     * Validate a user against the given credentials.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $credentials
     * @return bool
     */
    public function validateCredentials(UserContract $user, array $credentials) {
		if (\App::make('crowd-auth')->canUserLogin($credentials['username'])) {
			$token = \App::make('crowd-auth')->ssoAuthUser($credentials, $this->getClientIp());
			
            if ($token != null && \App::make('crowd-auth')->ssoGetUser($credentials['username'], $token) != null) {
                // Check if user exists in DB, if not add it.
				$crowdUserModel = $this->createClassReferenceByOption('crowd_user_model');
				
                $stored_crowd_user = $crowdUserModel::where('crowd_key', '=', $user->key)->first();
                if ($stored_crowd_user == null) {
                    $stored_crowd_user = $crowdUserModel::create(array(
                        'crowd_key' => $user->key,
                        'username' => $user->username,
                        'email' => $user->email,
                        'display_name' => $user->displayName,
                        'first_name' => $user->firstName,
                        'last_name' => $user->lastName,
                    ));
                }
                $stored_crowd_user->save();
				
				$crowdGroupModel = $this->createClassReferenceByOption('crowd_group_model');
				
                $attached_groups = [];
				
				foreach ($user->usergroups as $usergroup) {
                    // Check if usergroup already exists in the DB, if not add it.
                    $crowdUserGroup = $crowdGroupModel::where('group_name', '=', $usergroup)->first();

                    if ($crowdUserGroup == null) {
                        $crowdUserGroup = $crowdGroupModel::create(array(
                            'group_name' => $usergroup,
                        ));
                    }
					
					$attached_groups[] = $crowdUserGroup->id;
                }
				
				// assign all groups to the user
				$stored_crowd_user->groups()->sync($attached_groups);
                $stored_crowd_user->save();
				
                $user->setRememberToken($token);
				
                return true;
            }
        }
        return false;
    }
	
	private function createClassReferenceByOption($keyName) {
		$clazz = \Config::get('crowd-auth.' . $keyName);
		
		return $clazz;
	}

    /**
     * Retrieve a user by by their unique identifier and "remember me" token.
     *
     * @param  mixed  $identifier
     * @param  string  $token
     * @return GenericUser|null
     */
    public function retrieveByToken($identifier, $token) {
        $userData = \App::make('crowd-auth')->ssoGetUser($identifier, $token);
        if ($userData != null) {
            return $this->retrieveById($userData['user-name']);
        }
        return null;
    }

	/**
     * Update the "remember me" token for the given user in storage.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $token
     * @return void
     */
    public function updateRememberToken(UserContract $user, $token)
    {
	    if ($user != null) {
            $user->setRememberToken(\App::make('crowd-auth')->ssoUpdateToken($token, $this->getClientIp()));
			
			$user->save();
        }
        return null;
    }
}