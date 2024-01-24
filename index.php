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
 * Book GitHub export plugin
 *
 * @package    booktool_github
 * @copyright  2015 David Jones {@link http://djone.es}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(dirname(__FILE__).'/../../../../config.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once($CFG->dirroot.'/mod/book/locallib.php');

require_once( __DIR__ . '/connection_form.php' );

// Can this be put into a support function?
$cmid = required_param('id', PARAM_INT);           // Course Module ID.

$instructions = optional_param( 'instructions', 0, PARAM_INT);


$cm = get_coursemodule_from_id('book', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$book = $DB->get_record('book', ['id' => $cm->instance], '*', MUST_EXIST);

$PAGE->set_url('/mod/book/tool/github/index.php');
$PAGE->navbar->add( 'GitHub tool', new moodle_url( '/mod/book/tool/github/index.php', ['id' => $cmid] ));

$bookurl = new moodle_url( '/mod/book/view.php', ['id' => $cmid]);

require_login($course, false, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/book:read', $context);
require_capability('mod/book:edit', $context);
require_capability('mod/book:viewhiddenchapters', $context);
require_capability( 'booktool/github:export', $context );

// Need to think about what events get added.
// Booktool_exportimscp\event\book_exported::create_from_book($book, $context)->trigger();!

// Show the header and initial display.

// Has this book been configured to use github?

$repodetails = booktool_github_get_repo_details( $book->id );
$repodetails['id'] = $cmid;

if ( $instructions > 0 ) {
    $_SESSION['github_seen_instructions'] = 1;
}

echo $OUTPUT->header();

// If the instructions haven't been seen, display some basic info.
$seeninstructions = array_key_exists( "github_seen_instructions", $_SESSION );
if ( ! $repodetails && ! $seeninstructions ) {
    booktool_github_show_instructions( $cmid );
    echo $OUTPUT->footer();
    die;
}

// Ready to use github connection.

// Get github client and github user details via oauth.
list( $githubclient, $githubuser ) = booktool_github_get_client( $cmid );

// Couldn't authenticate with github, probably never happen.
// TIDY UP.
if ( ! $githubclient ) {
    print '<h1> Cannot authenticate with github</h1>';

    echo $OUTPUT->footer();

    die;
}

// Add the "owner" of this connection as the username from oAuth.
$repodetails['owner'] = $githubuser->getLogin();

// If no repo yet configured, repo_details[ repo|path ] will not exist.

$commits = false;

// Start showing the form.

$form = new connection_form( null, ['id' => $cmid ] );

// Assume it's valid.
$validconnection = true;

if ( $fromform = $form->get_data() ) {
    // Check to see if the repo/path have actually changed.
    // Repo_details should have the existing data.

    $repodefault = get_string( 'repo_form_default', 'booktool_github');
    $pathdefault = get_string( 'repo_path_default', 'booktool_github');

    if ( strcmp( $fromform->repo, $repodefault ) !== 0 &&
         strcmp( $fromform->path, $pathdefault ) !== 0 ) {

        if ( strcmp( $fromform->repo, $repodetails['repo']) !== 0 ||
             strcmp( $fromform->path, $repodetails['path']) !== 0 ) {

            $change = true;

            $repodetails['repo'] = trim( $fromform->repo );
            $repodetails['path'] = trim( $fromform->path );
            $repodetails['bookid'] = $book->id;
            $repodetails['id'] = trim( $fromform->id );

            if ( ! booktool_github_repo_exists($githubclient, $repodetails) ) {
                print get_string( 'form_repo_not_exist_error', 'booktool_github',
                                   $repodetails );
                $change = false;
                $validconnection = false;
                // Does the repo existing on github ?? create it??
                // Maybe add this later - would require more work
                // Create repo https://developer.github.com/v3/repos/#create
                // POST /user/repos.

            } else if ( ! booktool_github_path_exists($githubclient, $repodetails)) {
                // File no exists, so create an empty one.
                if ( ! booktool_github_create_new_file( $githubclient, $repodetails) ) {
                    print get_string( 'form_no_create_file', 'booktool_github',
                                      $repodetails );
                    $change = false;
                    $validconnection = false;
                } else {
                    print "<h3>Able to create file </h3>";
                }
            }
            // Where we save the data.
            if ( $change ) {
                if ( ! booktool_github_put_repo_details( $repodetails ) ) {
                    print "<h1> updateing databse stuff</h1>";
                    print get_string( 'form_no_database_write', 'booktool_github' );
                }
            }

            // If all ok, save it to the database.
        } else { // Didn't change the existing data.
            print "<h1>didn't change the data</h1>";
            print "<xmp> ";
            var_dump( $fromform );
            print "</xmp>";
        }
    } else {  // Didn't change the default form data.
        print get_string( 'form_no_change_default_error', 'booktool_github' );
    }


        // May just continue on at this stage.
}

// Now show the rest of the form.

$giturl = 'http://github.com/' . $repodetails['owner'] . '/' .
            $repodetails['repo'].'/blob/master/'.$repodetails['path'];
$repourl = 'http://github.com/' . $repodetails['owner'] . '/' .
            $repodetails['repo'] . '//';
$gituserurl = 'http://github.com/' . $repodetails['owner'];
$rawgiturl = 'https://cdn.rawgit.com/' . $repodetails['owner'] . '/' .
              $repodetails['repo'] . '/master/' . $repodetails['path'];

$urls = ['book_url' => $bookurl->out(), 'git_url' => $giturl,
               'repo_url' => $repourl, 'git_user_url' => $gituserurl,
               'rawgit_url' => $rawgiturl ];

if ( ! array_key_exists( 'repo', $repodetails ) ) {
    print get_string( 'form_empty', 'booktool_github' );
} else if ( $validconnection ) {
    print get_string( 'form_complete', 'booktool_github', $urls );
} else {
    print get_string( 'form_connection_broken', 'booktool_github' );
}

// How does this handle the no change stuff?
// T$form = new connection_form( null, $repodetails );!
$form->set_data( $repodetails );
$form->display();

if ( $validconnection ) {
    booktool_github_view_status( $cmid, $githubclient, $repodetails, $urls );
}

echo $OUTPUT->footer();



