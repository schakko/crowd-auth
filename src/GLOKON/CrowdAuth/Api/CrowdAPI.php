<?php

/*
 * This file is part of CrowdAuth
 *
 * (c) Daniel McAssey <hello@glokon.me>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GLOKON\CrowdAuth\Api;
use Log;

class CrowdAPI {

    /**
     * Runs the data against the Crowd RESTful API
     *
     * @param  string  $requestEndpoint
     * @param  string  $requestType
     * @param  array  $requestData
     * @return array
     */
    private function runCrowdAPI($requestEndpoint, $requestType, $requestData)
    {
        $crowdURL = \Config::get('crowd-auth.url');
        $crowdAppName = \Config::get('crowd-auth.app_name');
        $crowdAppPassword = \Config::get('crowd-auth.app_password');
        $crowdHTTPHeaders = array(
                                'Accept: application/json',
                                'Content-Type: application/json',
                            );
							
							$endpointUrl = $crowdURL.'/rest/usermanagement'.$requestEndpoint;
							
							Log::debug("Access endpoint " . $endpointUrl . " with method " . $requestType);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpointUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $crowdHTTPHeaders);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_USERPWD, $crowdAppName.":".$crowdAppPassword);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		
		$sslVerify = \Config::get('crowd-auth.verify_certificate') ? 1 : 0;
		
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $sslVerify);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $sslVerify);
		
        switch ($requestType) {
            case "POST":
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
                break;
            case "DELETE":
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                break;
            case "PUT":
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
                break;
        }
        $crowdOutput = curl_exec($ch);
		
		if ($crowdOutput === FALSE) {
			Log::error("CURL error during Crowd authentication", ['curl_message' => curl_error($ch)]);
		}
		
        $crowdHTTPStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $crowdOutputDecoded = json_decode($crowdOutput, true);
		
		if($crowdHTTPStatus >= 400) {
			Log::error("Invalid response from Crowd", ['http_status' => $crowdHTTPStatus, 'decoded_response' => $crowdOutputDecoded]);

			if ($crowdHTTPStatus == 401) {
				Log::error("Please check Crowd application name and token.");
			}
			else if ($crowdHTTPStatus == 403) {
				Log:error("Please check for whitelisted IPv4 and/or IPv6 address in Crowd for *this* server");
			}
		}

		Log::debug("JSON response received", ['data' => $crowdOutputDecoded]);
		
        return array('status' => $crowdHTTPStatus, 'data' => $crowdOutputDecoded);
    }

    /**
     * Authenticates user and gets SSO token
     *
     * @param  array  $credentials
     * @return string|null
     */
    public function ssoAuthUser($credentials, $user_ip)
    {
        if (is_array($credentials) && isset($credentials["username"]) && isset($credentials["password"])) {
            $apiEndpoint = '/1/session';
            $apiData = array(
                        'username' => $credentials['username'],
                        'password' => $credentials['password'],
                        'validation-factors' => array(
                            'validationFactors' => array(
                                array(
                                    'name'  => 'remote_address',
                                    'value' => $user_ip
                                )
                            )
                        ));

            $apiReturn = $this->runCrowdAPI($apiEndpoint, "POST", $apiData);

            if ($apiReturn['status'] == '201') {
                if ($credentials['username'] == $apiReturn['data']['user']['name']) {
                    return $apiReturn['data']['token'];
                }
            }
        }
        return null;
    }

    /**
     * Retrieves user data from SSO token
     *
     * @param  string  $username
     * @param  string  $token
     * @return array|null
     */
    public function ssoGetUser($username, $token)
    {
        $apiEndpoint = '/1/session/'.$token;
        $apiReturn = $this->runCrowdAPI($apiEndpoint, "GET", array());
        if ($apiReturn['status'] == '200') {
            if ($apiReturn['data']['user']['name'] == $username && $token == $apiReturn['data']['token']) {
                return $this->getUser($apiReturn['data']['user']['name']);
            }
        }
        return null;
    }

    /**	
     * Retrieves the token if matched with sent token
     *
     * @param  string  $token
     * @return string|null
     */
    public function ssoGetToken($token)
    {
        $apiEndpoint = '/1/session/'.$token;
        $apiReturn = $this->runCrowdAPI($apiEndpoint, "GET", array());
        if ($apiReturn['status'] == '200') {
            return $apiReturn['data']['token'];
        }
        return null;
    }

    /**
     * Retrieves and updates the token if matched with sent token
     *
     * @param  string  $token
     * @return string|null
     */
    public function ssoUpdateToken($token, $user_ip)
    {
        $apiEndpoint = '/1/session/'.$token;
        $apiData = array(
                        'validationFactors' => array(
                            'name' => 'remote_address',
                            'value' => $user_ip
                        ));
        $apiReturn = $this->runCrowdAPI($apiEndpoint, "POST", $apiData);
        if ($apiReturn['status'] == '200') {
            return $apiReturn['data']['token'];
        }
        return null;
    }

    /**
     * Invalidates the token when logged out
     *
     * @param  string  $token
     * @return bool
     */
    public function ssoInvalidateToken($token)
    {
        $apiEndpoint = '/1/session/'.$token;
        $apiReturn = $this->runCrowdAPI($apiEndpoint, "DELETE", array());
        if ($apiReturn['status'] == '204') {
            return true;
        }
        return false;
    }

    /**
     * Retrieves all user attributes and data.
     *
     * @param  string  $username
     * @return array|null
     */
    public function getUser($username)
    {
        $apiEndpoint = '/1/user?username='.$username.'&expand=attributes';
        $apiReturn = $this->runCrowdAPI($apiEndpoint, "GET", array());
		
        if ($apiReturn['status'] == '200') {
            $userAttributes = array();
            for ($i = 0; $i < count($apiReturn['data']['attributes']['attributes']); $i++) {
                $currentAttribute = $apiReturn['data']['attributes']['attributes'][$i];
                $userAttributes[$currentAttribute['name']] = $currentAttribute['values'][0];
            }
            $userData = array(
                            'key' => $apiReturn['data']['key'],
                            'user-name' => $apiReturn['data']['name'],
                            'first-name' => $apiReturn['data']['first-name'],
                            'last-name' => $apiReturn['data']['last-name'],
                            'display-name' => $apiReturn['data']['display-name'],
                            'email' => $apiReturn['data']['email'],
                            'attributes' => $userAttributes,
                            'groups' => $this->getUserGroups($apiReturn['data']['name']),
                        );
            return $userData;
        }
        return null;
    }

    /**
     * Gets all groups a user is a direct member of.
     *
     * @param  string  $username
     * @return array|null
     */
    public function getUserGroups($username)
    {
        $apiEndpoint = '/1/user/group/direct?username='.$username;
        $apiReturn = $this->runCrowdAPI($apiEndpoint, "GET", array());
        if ($apiReturn['status'] == '200') {
            $groups = array();
            for ($i = 0; $i < count($apiReturn['data']['groups']); $i++) {
                $groups[] = $apiReturn['data']['groups'][$i]['name'];
            }
            return $groups;
        }
        return null;
    }

    /**
     * Checks to see if user exists by username
     *
     * @param  string  $username
     * @return bool
     */
    public function doesUserExist($username)
    {
        $apiEndpoint = '/1/user?username='.$username;
        $apiReturn = $this->runCrowdAPI($apiEndpoint, "GET", array());
        if ($apiReturn['status'] == '200') {
            return true;
        }
        return false;
    }

    /**
     * Checks to see if the user can login to the application
     *
     * @param  string  $username
     * @return bool
     */
    public function canUserLogin($username)
    {
        $userGroups = $this->getUserGroups($username);
		$allowedGroups = \Config::get('crowd-auth.app_groups');
		
		// do not apply group check if no group is defined
		if (count($allowedGroups) == 0) {
			return true;
		}
		
        if (count($userGroups) > 0) {
            if (count(array_intersect($userGroups, $allowedGroups)) > 0) {
                return true;
            }
        }
		
        return false;
    }
}