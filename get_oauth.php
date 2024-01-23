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

/**
 * Book IMSCP export plugin -- get_oauth.php
 * - check and update Oauth token from github
 *
 * @package    booktool_github
 * @copyright  2015 David Jones {@link http://djone.es}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(dirname(__FILE__).'/../../../../config.php');
require_once($CFG->dirroot.'/mod/book/tool/github/locallib.php');

require_once(__DIR__ . '/local/PHP-OAuth2/Client.php');
require_once(__DIR__ . '/local/PHP-OAuth2/GrantType/IGrantType.php');
require_once(__DIR__ . '/local/PHP-OAuth2/GrantType/AuthorizationCode.php');

const REDIRECT_URI  = 'http://uhn41.localhost/mod/book/tool/github/get_oauth.php';

// Hard-coded into the tool.
const AUTHORIZATION_ENDPOINT = 'https://github.com/login/oauth/authorize';
const TOKEN_ENDPOINT         = 'https://github.com/login/oauth/access_token';


// Do the Moodle checks.
$id = optional_param('id', -1, PARAM_INT);           // Course Module ID.
$code = optional_param( 'code', '', PARAM_BASE64);
$url = optional_param( 'url', '', PARAM_LOCALURL );

if ( $id == -1 ) {
    print "<h3> ERROR need to get id from STATE</h3>";

    $state = optional_param( 'state', '', PARAM_RAW );
    if ( $state == '' ) {
        print "<h1>failure getting state</h1>";
    } else {
        $params = booktool_github_url_decode_params( $state );
        print "<h4>Params are </h4>";
        var_dump( $params );
        $id = $params->id;
        $state = $params->state;
        $url = $params->url;
    }
} else {
      print "<h1> ID in get auth is " . $id . "</h1>";
}

$cm = get_coursemodule_from_id('book', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$book = $DB->get_record('book', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, false, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/book:read', $context);
require_capability('mod/book:edit', $context);
require_capability('mod/book:viewhiddenchapters', $context);

// This is using straight PHP session stuff.
// Need to replace with proper use.
// Session start().

$clientdetails = booktool_github_get_client_details();

$client = new OAuth2\Client($clientdetails['clientid'],
                            $clientdetails['clientsecret'] );

if ( $code == '' ) {

    $address = 1530;
    $state = hash('sha256', microtime(true).rand().$address);

    $paramarr = ['id' => $id, 'url' => $url, 'state' => $state ];
    $paramstr = booktool_github_url_encode_params( $paramarr );

    // Send user to github oauth login.

    $extras = ['state' => $paramstr, 'scope' => "user,repo" ];
    $authurl = $client->getAuthenticationUrl(AUTHORIZATION_ENDPOINT,
                                        REDIRECT_URI, $extras);
    header('Location: ' . $authurl);
    die('Redirect');
} else {
    // Need to exchange temp code with a proper one.
    $params = ['code' => $_GET['code'],
                    'redirect_uri' => REDIRECT_URI];
    $extras = ['state' => $_GET['state']];
    $response = $client->getAccessToken(TOKEN_ENDPOINT,
                                'authorization_code', $params, $extras);

    // Check for failure.
    if ( $response['code'] != 200 ) {
        print "<h3> Response was " . $response['code'] . "</h3>";
        die;
    }

    parse_str($response['result'], $info);

    if ( array_key_exists( 'access_token', $info ) ) {

        $oauthtoken = $info['access_token'];

        $_SESSION["github_token"] = $oauthtoken;

        // Redirect to URL.
        $url = new moodle_url( $url, ['id' => $id ]);
        redirect ($url );
    } else {
        print "<h1> FAILURE - no token </h1>";
        var_dump( $info );
    }
}


