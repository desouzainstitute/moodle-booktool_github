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

require_once( __DIR__ . '/pull_form.php' );

// Can this be put into a support function?
$cmid = required_param('id', PARAM_INT);           // Course Module ID.

$cm = get_coursemodule_from_id('book', $cmid, 0, false, MUST_EXIST);

$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$book = $DB->get_record('book', ['id' => $cm->instance], '*', MUST_EXIST);

$toolurl = new moodle_url( '/mod/book/tool/github/index.php', ['id' => $cmid]);
$bookurl = new moodle_url( '/mod/book/view.php', ['id' => $cmid]);

$PAGE->set_url('/mod/book/tool/github/pull.php');

require_login($course, false, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/book:read', $context);
require_capability('mod/book:edit', $context);
require_capability('mod/book:viewhiddenchapters', $context);
require_capability( 'booktool/github:export', $context );

$PAGE->navbar->add( 'GitHub tool', $toolurl );
$PAGE->navbar->add( get_string('pull_form_crumb', 'booktool_github'),
                    new moodle_url( '/mod/book/tool/github/pull.php',
                                    ['id' => $cmid] ));

// Need to think about what events get added.
// \booktool_exportimscp\event\book_exported::create_from_book($book, $context)->trigger();!

// Show the header and initial display.

// Has this book been configured to use github?

$repodetails = booktool_github_get_repo_details( $book->id );

echo $OUTPUT->header();

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

// Start showing the form.

$form = new pull_form( null, ['id' => $cmid ]);

// Build params for messages.
$giturl = 'http://github.com/' . $repodetails['owner'] . '/' .
            $repodetails['repo'] . '/blob/master/' . $repodetails['path'];
$repourl = 'http://github.com/' . $repodetails['owner'] . '/' .
            $repodetails['repo'] . '//';
$gituserurl = 'http://github.com/' . $repodetails['owner'];

$urls = ['book_url' => $bookurl->out(), 'tool_url' => $toolurl->out(),
                'git_url' => $giturl, 'repo_url' => $repourl,
                'git_user_url' => $gituserurl];

if ( $fromform = $form->get_data() ) {
    // User has submitted the form, they want to do the pull.

    // Grab the book content and combine into a single file.

    // Commit the file.
    if ( booktool_github_pull_book( $githubclient, $repodetails, $book )) {
        print get_string('pull_success', 'booktool_github', $urls);
    } else {
        print get_string('pull_failure', 'booktool_github', $urls);
    }
} else {
    // Just display the initial warning.
    print get_string( 'pull_warning', 'booktool_github', $urls );

    $form->display();
}

echo $OUTPUT->footer();



