<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die;
require('PHP-OAuth2/Client.php');
require('PHP-OAuth2/GrantType/IGrantType.php');
require('PHP-OAuth2/GrantType/AuthorizationCode.php');

// Created on github by owner of Moodle instance.
// Will have to be a configuration option.
const CLIENT_ID     = 'b8340758e05e8280f5ef';
const CLIENT_SECRET = '805f9a89dd7c19171fd4d86ae1bd8eec6ebef19a';

// Will need to be created based on BASE_URL of Moodle install but the.
// Final stages can be calculated based on Moodle location of tool.
const REDIRECT_URI           = 'http://localhost:8080/moodle/mod/Book/tool/github/local/oauth_self.php';

// Hard-coded into the tool.
const AUTHORIZATION_ENDPOINT = 'https://github.com/login/oauth/authorize';
const TOKEN_ENDPOINT         = 'https://github.com/login/oauth/access_token';

// Will probably need to store this locally.
$address = 1530;
$state = hash('sha256', microtime(true).rand().$address);

$client = new OAuth2\Client(CLIENT_ID, CLIENT_SECRET);

// Pass user-agent as parameter?

if (!isset($_GET['code'])) {
    // Send user to github oauth login.

    print "<h1>STARTING STEP 1</h1>";
    $extras = ['state' => $state, 'scope' => "user"];
    $authurl = $client->getAuthenticationUrl(AUTHORIZATION_ENDPOINT,
                                                REDIRECT_URI, $extras);
    header('Location: ' . $authurl);
    die('Redirect');
} else {
    // Need to exchange temp code with a proper one.

    print "<h1>STARTING STEP 2</h1>";
    $params = ['code' => $_GET['code'], 'redirect_uri' => REDIRECT_URI];
    $extras = ['state' => $_GET['state'] ];
    print "<strong>local params</strong> <pre>" . var_dump( $params ) . "</pre>";
    $response = $client->getAccessToken(TOKEN_ENDPOINT, 'authorization_code',
                                        $params, $extras);

    // Check for failure.
    if ( $response['code'] != 200 ) {
        print "<h3> Response was " . $response['code'] . "</h3>";
        die;
    }

    print "<h1>STARTING STEP 3</h1>";
    var_dump( $response );

    parse_str($response['result'], $info);

    print "<h1>Got access token " . $info['access_token'] . "</h1>";

    $client->setAccessToken($info['access_token']);
    $response = $client->fetch('https://api.github.com/user/emails');

    // Do something with the response.
    print "<xmp>";
    var_dump($response, $response['result']);
    print "</xmp>";
}



