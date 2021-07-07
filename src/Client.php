<?php


namespace AvidBase;


class Client
{
    protected $_baseUrl = "https://dev-api.avidbase.com/";

    protected $_account;
    protected $_apiKey;

    protected $_client;

    protected $_machineAccessToken;
    protected $_userAccessToken;

    public function __construct($account, $apiKey)
    {
        $this->_account = $account;
        $this->_apiKey = $apiKey;
        $this->_client = new \GuzzleHttp\Client([
            'base_uri' => $this->_baseUrl,
        ]);
    }

    // Validates whether the machine access token is available or not and generates one if now available
    private function isValidMachineAccessToken()
    {
        if (empty($this->_machineAccessToken)) {
            if (!$this->generateToken()) {
                return false;
            }
        }
        return true;
    }

    // Generate a new machine access token using api key
    private function generateToken()
    {
        $data = [
            "api_key" => $this->_apiKey,
        ];
        try {
            $response = $this->_client->request('POST', "v1/account/" . $this->_account . "/token", ['json' => $data]);
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            return false;
        }

        // Set the machine access token if it exists
        if ($response->hasHeader('Access-Token')) {
            $this->_machineAccessToken = $response->getHeader('Access-Token')[0];
            return true;
        }

        return false;
    }

    // Return user access token if available after logging in
    public function GetUserAccessToken()
    {
        return $this->_userAccessToken;
    }

    // Authenticate the existing user using email and password
    public function Login($email, $password)
    {
        $data = [
            "email" => $email,
            "password" => $password,
            "account_uuid" => $this->_account,
        ];

        try {
            $response = $this->_client->request('POST', "v1/auth", ['json' => $data]);
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            return [];
        }

        // Set the machine access token if it exists
        if ($response->hasHeader('Access-Token')) {
            $this->_userAccessToken = $response->getHeader('Access-Token')[0];
            return json_decode($response->getBody()->getContents(), true);
        }

        return [];
    }

    // List all the users using machine access token
    public function ListUsers()
    {
        if ($this->isValidMachineAccessToken()) {
            try {
                $response = $this->_client->request('GET', "v1/user", ["headers" => ["Access-Token" => $this->_machineAccessToken]]);
            } catch (\GuzzleHttp\Exception\GuzzleException $e) {
                return [];
            }

            // See if the response is success or not
            if ($response->getStatusCode() == 200) {
                return json_decode($response->getBody()->getContents(), true);
            }
        }

        return [];
    }

    // Create a new user using machine access token
    public function CreateUser(User $user)
    {
        if ($this->isValidMachineAccessToken()) {
            $data = [
                "first_name" => $user->FirstName,
                "last_name" => $user->LastName,
                "email" => $user->Email,
                "password" => $user->Password,
            ];

            try {
                $response = $this->_client->request('POST', "v1/user", ['json' => $data, "headers" => ["Access-Token" => $this->_machineAccessToken]]);
            } catch (\GuzzleHttp\Exception\GuzzleException $e) {
                return false;
            }

            // See if the response is success or not
            if ($response->getStatusCode() == 200) {
                return true;
            }
        }

        return false;
    }

    // Update an existing user using user id and machine access token
    public function UpdateUser($userId, User $user)
    {
        if ($this->isValidMachineAccessToken()) {
            $data = [
                "first_name" => $user->FirstName,
                "last_name" => $user->LastName,
                "email" => $user->Email,
                "password" => $user->Password,
            ];

            try {
                $response = $this->_client->request('POST', "v1/user/" . $userId, ['json' => $data, "headers" => ["Access-Token" => $this->_machineAccessToken]]);
            } catch (\GuzzleHttp\Exception\GuzzleException $e) {
                return false;
            }

            // See if the response is success or not
            if ($response->getStatusCode() == 200) {
                return true;
            }
        }

        return false;
    }
}