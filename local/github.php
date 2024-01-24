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
require_once( __DIR__ . '/client/client/GitHubClient.php' );

listcommits();
die;

$owner = 'djplaner';
$repo = 'edc3100';
$path = 'A_2nd_new_file.html';
$username = 'djplaner';
$password = 'n3tmask3r';

$client = new GitHubClient();
$client->setDebug( true );
$client->setCredentials( $username, $password );

$sha = getsha( $client, $owner, $repo, $path );

print "SHAR is " . $sha . "\n";

$statuses = $client->repos->statuses->listStatusesForSpecificRef( $owner, $repo, $sha );

echo "Num statuses is " . count( $statuses ) . "\n";
foreach ($statuses as $status) {
    echo get_class( $status ) . "\n";
    var_dump( $status );
}

function getsha( $client, $owner, $repo, $path) {

    $data = [];
    $response = $client->request( "/repos/$owner/$repo/contents/$path", 'GET', $data, 200, 'GitHubReadmeContent'   );

    return $response->getsha();
}

// Display the content of a file.
function getcontent() {
    $owner = 'djplaner';
    $repo = 'edc3100';
    $path = 'Who_are_you.html';

    $client = new GitHubClient();

    $data = [];
    $response = $client->request( "/repos/$owner/$repo/contents/$path", 'GET', $data, 200, 'GitHubReadmeContent'   );

    print "content is " . base64_decode( $response->getcontent() );

    print "name is " . $response->getName();
}

// Get a list of commits.
function listcommits() {

    $owner = 'djplaner';
    $repo = 'edc3100';
    $path = 'A_2nd_new_file.html';

    $client = new GitHubClient();
    $client->setDebug( true );

    $data = [];
    $data['path'] = $path;

    $before = memory_get_usage();
    $commits = $client->repos->commits->listCommitsOnRepository(
                                $owner, $repo, null, $path );
    $after = memory_get_usage();

    var_dump($commits);
    echo "Count: " . count($commits) . "\n";
    foreach ($commits as $commit) {
        echo get_class($commit) . " - Sha: " . $commit->getsha() . "\n";
        $thecommit = $commit->getAuthor();
        var_dump( $thecommit);
    }

    echo "size is " . convert( $after - $before ) . "\n";
}

function convert($size) {
    $unit = ['b', 'kb', 'mb', 'gb', 'tb', 'pb'];
    return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2).' '.$unit[$i];
}

function createfile() {
    $owner = 'djplaner';
    $repo = 'edc3100';
    $path = 'A_2nd_new_file.html';
    $username = 'djplaner';
    $password = 'n3tmask3r';

    $content = "This will be the content in the second file. The 1st time";

    $client = new GitHubClient();
    $client->setDebug( true );
    $client->setCredentials( $username, $password );

    $data = [];
    $data['message'] = 'First time creating a file';
    $data['content'] = base64_encode( $content );

    $response = $client->request( "/repos/$owner/$repo/contents/$path", 'PUT', $data, 201, 'GitHubReadmeContent');

    var_dump( $response );
}

function updatefile() {
    $content = "This will be the content in the second file. The 4th time";

    $client = new GitHubClient();

    $client->setCredentials( $username, $password );

    $sha = getsha( $client, $owner, $repo, $path );

    print "shar is $sha\n\n";
    $data = [];
    $data['message'] = 'First time creating a file - Update 2';
    $data['content'] = base64_encode( $content );
    $data['sha'] = $sha;
    $data['committer'] = ['name' => 'David Jones',
                                'email' => 'davidthomjones@gmail.com' ];

    $response = $client->request( "/repos/$owner/$repo/contents/$path", 'PUT', $data, 200, 'GitHubReadmeContent'   );

    var_dump( $response );
}
