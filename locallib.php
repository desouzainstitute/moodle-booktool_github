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
 * Book github export lib
 *
 * @package    booktool_github
 * @copyright  2015 David Jones {@link http://djone.es}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

global $CFG;

require_once(dirname(__FILE__).'/lib.php');
require_once(__DIR__ . '/../../locallib.php');
require_once( __DIR__ . '/local/client/client/GitHubClient.php' );

const GITHUB_TOKEN_NAME = "github_token";

/***************************************************
 * github client specific calls
 */

/**
 * ( $githubclient, $githubuser ) = booktool_github_get_client( $id )
 * - get OAuth connection with github
 * - create a github client object to communicate with github
 * - get details of github user
 */

function booktool_github_get_client( $id ) {

    $attempts = 0;
    // GET_TOKEN:!
    $oauthtoken = booktool_github_get_oauth_token( $id);
    $attempts++;

    if ( $oauthtoken  ) {
        $client = new GitHubClient();
        $client->setAuthType( 'Oauth' );
        $client->setOauthToken( $oauthtoken );

        // Replace this with get user details.
        try {
            $user = $client->users->getTheAuthenticatedUser();
        } catch ( Exception $e ) {
            // Oops problem, probably a 401, try and fix.
            $msg = $e->getMessage();
            preg_match( '/.*actual status \[([^ ]*)\].*/', $msg, $matches );

            if ( $attempts > 2 ) {
                print "<h1> looks like I'm in a loop ";
                // Need to handle this, is this a failure?
                return [false, false];
            } else if ( $matches[1] == 401 ) {
                // SHOULD DELETE token from session.
                unset( $_SESSION['github_token'] );
                // Goto GET_TOKEN;!
            }
        }
        return [$client, $user];
    }
}

/**
 * does a given repo exist
 */

function booktool_github_repo_exists( $githubclient, $repodetails ) {

    $data = [];
    $request = "/repos/" . $repodetails['owner'] . "/" . $repodetails['repo'];
    try {
        $response = $githubclient->request($request, 'GET', $data, 200,
                                            'GitHubFullRepo');
    } catch ( Exception $e ) {
        return false;
    }

    return true;
}

/**
 * does a file exist within a repo
 **/

function booktool_github_path_exists( $githubclient, $repodetails ) {

    // $githubclient->setDebug( true );

    $data = [];
    $request = "/repos/" . $repodetails['owner'] . "/" . $repodetails['repo'] .
               "/contents/" . rawurlencode( $repodetails['path'] );
    // print "<h3> does it exist? request is $request </h3>";

    try {
        $response = $githubclient->request($request, 'GET', $data, 200,
                                            'GitHubReadmeContent');
    } catch ( Exception $e ) {
        return false;
    }

    return true;
}

/**
 * create an new empty file
 * - return true if worked
 */

function booktool_github_create_new_file( $githubclient, $repodetails, $content='' ) {

    // $githubclient->setDebug( true );

    $request = "/repos/" . $repodetails['owner'] . "/" . $repodetails['repo'] .
               "/contents/" . rawurlencode( $repodetails['path'] );
    $data = [];
    $data['message'] = 'Creating new file - Moodle book github tool';
    $data['content'] = base64_encode( $content );

    try {
        $response = $githubclient->request($request, 'PUT', $data, 201,
                                            'GitHubReadmeContent');
    } catch ( Exception $e ) {
        return false;
    }

    return true;
}




/**
 * ( repo, path ) = get_repo_details( $bookid );
 * - return an array of basic information about the connection
 *   that is required by the github_client
 *   - repo is the name of the github repo
 *   - path is the full path for the file from the report connected with book
 * - return false if can't get the information
 **/

function booktool_github_get_repo_details( $bookid ) {
    global $DB;

    // Repo and path are contained in database table github_connections.
    $result = $DB->get_record( 'booktool_github_connections',
                                ['bookid' => $bookid] );

    // If no data, then just return false.
    if ( ! $result ) {
        return false;
    }

    return ['connection_id' => $result->id,
                  'bookid' => $result->bookid,
                  'repo' => $result->repository,
                  'path' => $result->path,
                  'pushedtime' => $result->pushedtime,
                  'pushedrevision' => $result->pushedrevision ];
}

/**
 * bool = put_repo_details( $repodetails )
 * - either insert or update repo details in the database
 * - dependent on whether repo_details has an id
 */

function booktool_github_put_repo_details( $repodetails ) {
    global $DB;

    // Make sure all the required values available.
    $checks = ['pushedrevision', 'pushedtime'];
    foreach ($checks as $check) {
        if ( ! array_key_exists( $check, $repodetails )) {
            $repodetails[$check] = '';
        }
    }

    $record = new StdClass();
    $record->bookid       = $repodetails['bookid'];
    $record->repository   = $repodetails['repo'];
    $record->path         = $repodetails['path'];
    $record->pushedrevision = $repodetails['pushedrevision'];
    $record->pushedtime = $repodetails['pushedtime'];

    if ( array_key_exists( 'connection_id', $repodetails ) &&
         $repodetails['connection_id'] > 0) {
        // Update an existing entry if no id or id is 0.
        // I.e. the form was empty.
        $record->id = $repodetails['connection_id'];

        $DB->update_record( 'booktool_github_connections', $record );
        return true;
    }
    // Insert a new entry.

    return $DB->insert_record( 'booktool_github_connections', $record );
}


/**
 * $commits = getCommits( );
 * - return an array of GitHubCommit objects
 */

function booktool_github_get_commits( $githubclient, $repodetails) {

    try {
        $commits = $githubclient->repos->commits->listCommitsOnRepository(
                            $repodetails['owner'], $repodetails['repo'], null,
                            $repodetails['path'] );
    } catch ( Exception $e ) {
        $msg = $e->getMessage();
        echo '<xmp>Caught exception ' . $msg .  "</xmp>";
        return false;
    }
    return $commits;
}

/**
 * bool = booktool_github_change_in_form( $repodetails, $form )
 * - return TRUE/FALSE depending on whether there have been changes
 *   made in the form
 **/

function booktool_github_change_in_form( $repodetails, $form ) {

    // Is there anything in the database?
    // If ( array_key_exists( 'repo', $repodetails ) &&!
    // Array_key_exists( 'path', $repodetails ) ) {!
    // Print "<h1> the repo exists </h1>";!
    // Has the form changed from defaults?
    $repodefault = get_string( 'repo_form_default', 'booktool_github');
    $pathdefault = get_string( 'repo_path_default', 'booktool_github');

    if ( strcmp( $form->repo, $repodefault ) !== 0 &&
            strcmp( $form->path, $pathdefault ) !== 0 ) {

        // Print "<h1> path is different </h1>";!
        // Has the form data changed from content of the database?
        if ( strcmp( $form->repo, $repodetails['repo']) !== 0 &&
                strcmp( $form->path, $repodetails['path']) !== 0 ) {
            // Print "<h1> form data changed </h1>";!
            return true;
        }
    }

    return false;
}

/***************************************************
 * Views
 ***************************************************/

/****************************
 * Display summary of what we know about details of repo
 * STATUS
 * - Basic prototype version. Almost hard coded.
 * - Not really querying data from github
 */

function booktool_github_view_repo_details( $repodetails, $githubuser) {

    $repourl = "https://github.com/" . $repodetails['owner'] . "/" .
                $repodetails['repo'];
    $pathurl = $repourl . "/blob/master/" . $repodetails['path'];
    // Should I remove double / in path_url (but not from http://)!
    $ownerurl = "https://github.com/" . $repodetails['owner'];

    $string = '';

    $table = new html_table();
    $table->head = ['', '' ];

    $table->data[] = ['Repository', '<a href="' . $repourl . '">' .
                                $repodetails['repo'] . '</a>' ];
    $table->data[] = ['File', '<a href="' . $pathurl . '">' .
                                $repodetails['path'] . '</a>' ];

    // Show information about current user.
    $avatarurl = $githubuser->getAvatarUrl();
    $name = $githubuser->getName();
    $username = $githubuser->getLogin();
    $userurl = $githubuser->getHtmlUrl();

    $userhtml = html_writer::start_div( "githubuser" );

    $userhtml .= html_writer::link( $userurl,
                                     $name . '<br /> (' . $username .
                                     ') &nbsp;&nbsp;' );
    $userhtml .= html_writer::empty_tag( 'img', [
                        'src' => $avatarurl,
                        'alt' => 'Avatar for ' . $name,
                        'height' => 20, 'width' => 20 ] );
    $userhtml .= html_writer::end_div();

    $table->data[] = ['User', $userhtml ];

    $string .= html_writer::table( $table );

    return $string;
}



/***************************************************
 * Given book id, github client and repo details
 * display a range of status and historical information about the
 * book and its connection to github
 */

function booktool_github_view_status( $cmid, $githubclient, $repodetails, $urls ) {

    // If there are repo details show the commit information.
    if ( array_key_exists( 'repo', $repodetails ) ) {
        $commits = booktool_github_get_commits( $githubclient, $repodetails);
        // Need to set default value.
        $pushedrevision = $repodetails['pushedrevision'];
        $pushedtime = $repodetails['pushedtime'];

        $bookrevision = booktool_github_get_book_revision( $repodetails );
        $lastgittime = booktool_github_get_last_gittime( $commits );

        /*print "<h3>repo</h3><xmp>"; var_dump($repodetails); print "</xmp>";
        print "<h3> Status situation </h3> ";
        print "<ul> <li> pushed_revision $pushedrevision versus book_revision $bookrevision </li>
        <li> pushed_time $pushedtime versus git_time $lastgittime</li> </ul>"; */

        // Space to add push/pull etc.
        booktool_github_show_push_pull( $cmid, $pushedrevision, $pushedtime, $bookrevision, $lastgittime, $urls );

        if ( ! $commits ) {
            // Fix this up.
            print get_string('form_no_commits', 'booktool_github', $urls);
        } else {
            print get_string('form_history', 'booktool_github', $urls);

            $string = booktool_github_view_commits( $commits );
            echo $string;
        }
    }

}

/***************************************************
 * booktool_github_get_book_revision( $repodetails)
 * - return the revision number for the Moodle book
 */

function booktool_github_get_book_revision( $repodetails) {
    global $DB;

    $result = $DB->get_record( 'book', ['id' => $repodetails['bookid']] );

    if ( ! $result ) {
        return 0;
    } else {
        return $result->revision;
    }
}

/***************************************************
 * booktool_github_get_last_gittime( $commits )
 */

function booktool_github_get_last_gittime( $commits ) {

    $commit = $commits[0]->getCommit();
    $authordetails = $commit->getAuthor();
    $date = $authordetails->getDate();
    // Error checking.

    return strtotime( $date );
}


/***************************************************
 * - Given a a GitHubCommit object display info about each commit
 * - Currently a table where each row matches a commit and shows
 *   - Date changed - not showing it yet
 *   - Message for the commit & link to more information
 *   - Who made the commit
 *
 * TO DO:
 *   - what happens if DateTime errors?
 *   - clean up the mish-mash of html-write and html
 *     a renderer or template would be better
 */

function booktool_github_view_commits( $commits ) {

    $string = '';

    $table = new html_table();
    $table->head = ['Date changed', 'Details', 'Committer'];

    // Message for link to commit details.
    $detailslink = get_string( 'commit_details', 'booktool_github' );
    $detailslink = '<span style="font-size:small">[ ' . $detailslink .
                    ' ] </span>';

    // Each row is based on a single commit to the file.
    foreach ($commits as $commit) {
        // Return GitHubCommitCommit object.
        // Date of commit.
        $commitdetails = $commit->getCommit();
        $messagetext = $commitdetails->getMessage();
        $htmlurl = $commit->getHtmlUrl();

        $message = html_writer::link( $htmlurl, $detailslink );
        $message = '<div class="commit_message"> ' . $messagetext . '</div>' .
                    $message;

        // Author has the full name and date.
        $authordetails = $commitdetails->getAuthor();
        $authorname = $authordetails->getName();
        $datecommit = $authordetails->getDate();
        $date = new DateTime( $datecommit );
        $datedisplay = $date->format( 'D, d M Y H:i:s' );

        // Return GitHubUser object.
        // Get the avatar, username and html url for user.
        $committerdetails = $commit->getCommitter();
        $username = $committerdetails->getLogin();
        $avatarurl = $committerdetails->getAvatarUrl();
        $userurl = $committerdetails->getHtmlUrl();

        $committer = html_writer::start_div( "committer" );
        $image = html_writer::empty_tag( 'img', [
                        'src' => $avatarurl,
                        'alt' => 'Avatar for ' . $username,
                        'height' => 20, 'width' => 20 ] );
        $committer .= html_writer::link( $userurl,
                                            $authorname . '&nbsp;&nbsp;' .
                                            $image . '<br /> (' .
                                            $username . ')' );
        $committer .= html_writer::end_div();

        $row = [ $datedisplay, $message, $committer ];
        $table->data[] = $row;
    }

    $string .= html_writer::table( $table );

    // Debug stuff.
    /*    $string .= "<xmp>";
     *    $string .= print_r($commits, true);
     *    $string .= "</xmp>";
     **/
    return $string;

}

/******************************************************************
 * booktool_github_show_push_pull( $pushedrevision, $pushedtime, $bookrevision, $lastgittime );
 * - figure out whether push pull up to date should be shown
 * - BOOK PUSH
 *   - if pushedrevision is behind current Book revision
 #   - if pushedtime ahead last big commit
 * - GIT PULL
 *   - if repo_details->timepushed is behind most recent commit
 */

function booktool_github_show_push_pull( $cmid, $pushedrevision, $pushedtime,
                                         $bookrevision, $lastgittime, $urls ) {

    $status = '';
    $push = false;
    $pull = false;

    if ( $pushedrevision < $bookrevision ) {
        $status .= get_string('book_revision', 'booktool_github');
        $push = true;
    }
    if ( $pushedtime > $lastgittime ) {
        $status .= get_string('missing_push', 'booktool_github');
        $push = true;
    }
    if ( $pushedtime < $lastgittime ) {
        $status .= get_string('behind_git', 'booktool_github');

        $pull = true;
    }

    if ( ! $push && ! $pull ) {
        $status .= get_string('consistent', 'booktool_github');
    }

    $urls['status'] = $status;
    print get_string('form_status', 'booktool_github', $urls);

    $pushurl = new moodle_url('/mod/book/tool/github/push.php', ['id' => $cmid]);
    $pullurl = new moodle_url('/mod/book/tool/github/pull.php', ['id' => $cmid]);

    $arr = ['push_url' => $pushurl->out(), 'pull_url' => $pullurl->out()];
    print get_string('form_operations', 'booktool_github', $arr);

}



/**
 * $token = booktool_github_get_oauth_token( $url )
 * - return the oauth access token from github
 * - if there isn't one, get one and then redirect back to $url
 * - if one can't be gotten, show why
 */

function booktool_github_get_oauth_token( $id, $url='/mod/book/tool/github/index.php' ) {

    if ( array_key_exists( "github_token", $_SESSION ) ) {
        return $_SESSION["github_token"];
    } else {

        print get_string( 'github_redirect', 'booktool_github' );
        // Redirect to get_oauth.php include passsing CURRENT_URL.
        $url = new moodle_url( '/mod/book/tool/github/get_oauth.php',
                                [ 'id' => $id, 'url' => $url ]);
        redirect( $url );
        return false;
    }
}

/*
 * clientid = booktool_github_get_client_details()
 * - return the base64 client id from github for this tool
 * TO DO: replace this with a call to the database or other location
 */

function booktool_github_get_client_details() {
    global $DB;

    $result = $DB->get_record( 'booktool_github', ['id' => 1] );

    if ( ! $result ) {
        return [ 'clientid' => '',
                  'clientsecret' => '' ];
    } else {
        return ['clientid' => $result->clientid,
                      'clientsecret' => $result->clientsecret ];
    }
}

/*
 * $content = booktool_github_get_file_content( $githubclient, $repodetails )
 * - return the contents of the github file
 */

function booktool_github_get_file_content( $githubclient, $repodetails ) {
    $request = "/repos/" . $repodetails['owner'] . "/" . $repodetails['repo'] .
               "/contents/" . rawurlencode( $repodetails['path'] );

    $data = [];
    $response = [];
    try {
        $response = $githubclient->request( $request, 'GET', $data, 200, 'GitHubReadmeContent'   );
    } catch ( Exception $e ) {
        return 0;
    }

    return base64_decode( $response->getContent() );
}

/*****************************************************************
 * PUSH | PULL functions
 */

function booktool_github_push_book( $githubclient, $repodetails, $message ) {
    global $DB;

    // Get the book data.
    $book = $DB->get_record( 'book', ['id' => $repodetails['bookid']] );
    $select = "bookid=" . $repodetails['bookid'] ." order by pagenum";
    $result = $DB->get_records_select( 'book_chapters', $select);

    // Generate the content.
    $bookcontent = booktool_github_prepare_book_html( $book, $result );

    // Do the push??
    $data = [];
    $data['message'] = $message;
    $data['content'] = base64_encode( $bookcontent );
    $gitdetails = booktool_github_git_details( $githubclient, $repodetails );
    if ( $gitdetails === 0 ) {
        print "<h3> Failure</h3>";
        return false;
    }
    $data['sha'] = $gitdetails->getSha();

    $data['committer'] = [ 'name' => 'Carlos ArceLopera',
                                'email' => 'carlosarcelopera@catalyst-ca.net' ];

    $request = "/repos/" . $repodetails['owner'] . "/" . $repodetails['repo'] .
               "/contents/" . rawurlencode( $repodetails['path'] );

    $data['content'] = base64_encode( $bookcontent );

    try {
        $response = $githubclient->request($request, 'PUT', $data, 200,
                                            'GitHubReadmeContent');
    } catch ( Exception $e ) {
        return false;
    }

    // Need to update the book table.
    // Modify pushedrevision to current book revision.
    // Set pushedtime to latest git time.
    $commits = booktool_github_get_commits( $githubclient, $repodetails);
    $lastgittime = booktool_github_get_last_gittime( $commits );
    // Print "<h3>FROM repo_details</h3><xmp>";var_dump($repodetails);print "</xmp>";!

    $repodetails['pushedtime'] = $lastgittime;
    $repodetails['pushedrevision'] = $book->revision;

    // Print "<h3>TO repo_details</h3><xmp>";var_dump($repodetails);print "</xmp>";!

    return booktool_github_put_repo_details( $repodetails );
}

/*
 * return a modified GitHubReadmeContent object with details about the
 * latest version of the file
 * - add "date" to hold the time the file was last committed
 */

function booktool_github_git_details( $githubclient, $repodetails ) {
    $request = "/repos/" . $repodetails['owner'] . "/" . $repodetails['repo'] .
               "/contents/" . rawurlencode( $repodetails['path'] );

    // Get the basic detail about the file.
    $data = [];
    $response = [];
    try {
        $response = $githubclient->request( $request, 'GET', $data, 200, 'GitHubReadmeContent'   );
    } catch ( Exception $e ) {
        // Print "<h3>First failure</h3>";!
        return 0;
    }

    return $response;
}


// Transform the contents of the book into some sort of single HTML string.
// Important information for each chapter is.
// Pagenum, subchapter, title, content, contentformat, hidden.
// Improtant information for the book.
// Name, intro, introformat, customtitles?
function booktool_github_prepare_book_html( $book, $result ) {
    global $DB;

    $content = "<!DOCTYPE html>\n<html>\n    <head><title>" . $book->name. "</title></head>\n<body>\n";

    // The book forms an article.
    // Title - name of book.
    // Data attributes for other database values.
    $content .= "\n" . '<article title="' . $book->name .
                '" dataintroformat="' . $book->introformat .
                '" datacustomtitles="' . $book->customtitles .
                '" datanumbering="' . $book->numbering .
                '" datanavstyle="' . $book->navstyle .  '">' . "\n" .
       // Head to contain title and intro.
                '    <head><h1>' . $book->name . "</h1>\n" .
                "        <div class= 'intro'>" . $book->intro . "</div>\n" .
                "    </head>\n";

    // Create each chapter as a section within the article.
    // Complication: the next chapter may be a sub-chapter, if that's the.
    // Case, don't want to close the section tag for the chapter.
    $prevchapter = 'none';
    $lastchapter = false;
    $numitems = count( $result );
    $i = 0;

    foreach ($result as $chapter) {
        // Print "<h3> Chapter $i</h3>";!
        // How to set last chapter.
        $lastchapter = ( ++$i === $numitems );
        // Print "<p>LastChapter: $lastchapter </p>";!
        // Add the appropriate HTML and set the value for last chapter.
        $content = generate_chapter_html( $content, $chapter,
                                          $prevchapter, $lastchapter );

        $prevchapter = $chapter->subchapter;
    }

    $content .= "</body>\n</html>";

    return $content;
}

/******************************************************************
 * generate_chapter_html( $content, $chapter, $prevchapter, $lastchapter )
 * - add HTML to $content depending on the data in $chapter and
 *   what the $lastchapter was
 * - return new value for $lastchapter
 */

function generate_chapter_html( $content, $chapter, $prevchapter, $lastchapter ) {

    // <section BODY is always needed
    $body = '    <section title="' . $chapter->title .
             '" datasubchapter="' .  $chapter->subchapter .
             '" datapagenum="' . $chapter->pagenum .
             '" datacontentformat="' . $chapter->contentformat .
             '" datahidden="' . $chapter->hidden . '">' . "\n" .
             '        <head><h1>' . $chapter->title . '</h1></head>'. "\n" .
             '        <div>' . $chapter->content . '</div>' . "\n";

    // First chapter.
    if ( $prevchapter === 'none' ) {
        // print "<p>FIRST CHAPTER.... sub is " . $chapter->subchapter;
        if ( $chapter->subchapter == 0 ) {
            // Section BODY.
            $content .= $body;
            // print "<p>NOT sub chatper</p>\n";
        } else {
            // <section EMPTY <section BODY
            $content .= '    <section></section>' . "\n    " . $body;
            // print "<p>sub chatper</p>\n";
        }
        // In the middle chapters.
    } else if ( ! $lastchapter ) {
        // print "<p>MIDDLE CHAPTER...." . $chapter->subchapter;
        if ( $prevchapter == 0 ) {
            if ( $chapter->subchapter == 0 ) {
                // </section <section BODY
                $content .= '    </section>' . "\n    " . $body;
                // print "<p>NOT sub chatper</p>\n";
            } else {
                // Section BODY.
                $content .= $body;
                // print "<p>sub chatper</p>\n";
            }
        } else {
            if ( $chapter->subchapter == 0 ) {
                // </section #subc </section #chap <section BODY
                $content .= "        </section>\n    </section>\n    " . $body;
                // print "<p>NOT sub chatper</p>\n";
            } else {
                // </section #subc <section BODY
                $content .= "        </section>\n    " . $body;
                // print "<p>sub chatper</p>\n";
            }
        }
        // Last chapter.
    } else {
        // print "<p>LAST CHAPTER...." . $chapter->subchapter;
        if ( $prevchapter == 0) {
            if ( $chapter->subchapter == 0 ) {
                // </section <section BODY </section
                $content .= "    </section>\n    " . $body .
                            "\n    </section>";
                // print "<p>NOT sub chatper</p>\n";
            } else {
                // <section BODY </section #subc </section #chap
                $content .= $body . "\n        </section>\n    </section>";
                // print "<p>sub chatper</p>\n";
            }
        } else {
            if ( $chapter->subchapter == 0 ) {
                // </section #subc </section #chap <section BODY </section
                $content .= "        </section>\n    </section>\n" . $body .
                            "\n    </section>";
                // print "<p>NOT sub chatper</p>\n";
            } else {
                // </section #subc <section BODY </section #subc </section #chap
                $content .= "        </section>\n" . $body .
                            "\n        </section>\n    </section>";
                // print "<p>sub chatper</p>\n";
            }
        }
    }

    return $content;
}



/******************************************************************
 * book_tool_github_pull_book
 */

function booktool_github_pull_book( $githubclient, $repodetails, $book ) {
    global $DB;

    // Retrieve the content of the github file.
    $content = booktool_github_get_file_content($githubclient, $repodetails);
    if ( $content === 0 ) {
        return false;
    }

    // Parse it.
    $gitbook = booktool_github_parse_file_content( $content );

    if ( $gitbook === false ) {
        return false;
    }

    // Update the book table entry.
    booktool_github_update_book_table( $repodetails, $gitbook );

    // Remove old book chapters and add the new ones.
    $result = $DB->delete_records('book_chapters', ['bookid' => $repodetails['bookid']]);

    booktool_github_insert_chapters_table( $repodetails, $gitbook );

    // Update the github_connections table.
    // Pushedtime should equal lastcommit in git.
    // Pushed revision = latest revision from book + 1.
    $commits = booktool_github_get_commits( $githubclient, $repodetails);
    $lastgittime = booktool_github_get_last_gittime( $commits );
    // print "<h3>FROM repo_details</h3><xmp>";var_dump($repodetails);print "</xmp>";

    $repodetails['pushedtime'] = $lastgittime;
    $repodetails['pushedrevision'] = 1 + $book->revision;

    // print "<h3>TO repo_details</h3><xmp>";var_dump($repodetails);print "</xmp>";

    return booktool_github_put_repo_details( $repodetails );
}

/*
 * Insert chapters from git_book into the chapters table
 */

function booktool_github_insert_chapters_table( $repodetails, $gitbook ) {
    global $DB;

    $chapters = [];
    foreach ($gitbook->chapters as $gitchapter) {
        $chapter = (object)$gitchapter;
        $chapter->bookid = $repodetails['bookid'];
        $chapter->timecreated = time();
        $chapter->timemodified = 0;
        $chapter->importsrc = '';
        array_push( $chapters, $chapter );
    }

    return $DB->insert_records( 'book_chapters', $chapters, true );
}

/*
 * update the book table with data from git
 * - intro, name, introformat, customtitles, numbering, navstyle
 *   changed to git value
 * - timemodified - gets updated to now
 * - revision - gets incremented
 */

function booktool_github_update_book_table( $repodetails, $gitbook ) {
    global $DB;

    $updatefields = ['title', 'introformat', 'customtitles',
                            'numbering', 'navstyle'];

    // Get the existing data for the book.
    $book = $DB->get_records( "book", ['id' => $repodetails['bookid'] ]);

    if ( count( $book ) == 0 ) {
        return false;
    }

    $book = $book[$repodetails['bookid']];

    // print "<h3>Changing this</h3><xmp>"; var_dump($book); print "</xmp>";
    // print "<h3>gitbook</h3><xmp>"; var_dump($gitbook); print "</xmp>";

    // print "<h3>book Name</h3><xmp>"; var_dump($book->name); print "</xmp>";

    // print "<h3>gitbook title</h3><xmp>"; var_dump($gitbook->book->title); print "</xmp>";

    // Update the right bits from git
    // foreach ( $updatefields as $change ) {
    // $book->$change = $gitbook->book->$change;
    // }
    $book->name = $gitbook->book->title;
    $book->intro = $gitbook->book->intro;
    $book->introformat = $gitbook->book->dataintroformat;
    $book->customtitles = $gitbook->book->datacustomtitles;
    $book->numbering = $gitbook->book->datanumbering;
    $book->navstyle = $gitbook->book->datanavstyle;
    // Update locally.
    $book->revision++; // Or = 10 + $book->revision!
    $book->timemodified = time();

    // print "<h3>TO</h3><xmp>"; var_dump($book); print "</xmp>";
    return $DB->update_record( "book", $book );
}


/*
 * parse the HTML file
 */
function booktool_github_parse_file_content( $content ) {

    // Book_details.
    // BOOK -> name introformat customtitles.
    // CHAPTERS -> one for each chapter.
    $bookdetails = new StdClass;
    $bookdetails->book = new StdClass;
    $bookdetails->chapters = [];

    $dom = new DOMDocument;
    $dom->loadHTML( $content, LIBXML_NOERROR );
    $document = simplexml_import_dom( $dom );

    $xpath = new DOMXPath($dom);
    // print "<h3>Content</h3><xmp>"; var_dump( $content ); print "</xmp>";
    // print "<h3>DOM</h3><xmp>"; var_dump( $dom ); print "</xmp>";
    // print "<h3>Xpath document</h3><xmp>"; var_dump( $xpath->document ); print "</xmp>";

    // GET BOOK DATA.
    // Attributes other than intro.
    $bookinfo = $xpath->query( "//article");

    // print "<h3>Book info</h3><xmp>"; var_dump( $bookinfo->item(0)->nodeValue); print "</xmp>";
    // print "<h3>Book info title</h3><xmp>"; var_dump( $bookinfo->item(0)->attributes->getNamedItem('title')->nodeValue); print "</xmp>";
    // print "<h3>Book info data-introformat</h3><xmp>"; var_dump( $bookinfo->item(0)->attributes->getNamedItem('data-introformat')->nodeValue); print "</xmp>";
    // print "<h3>Book info data-customtitles</h3><xmp>"; var_dump( $bookinfo->item(0)->attributes->getNamedItem('data-customtitles')->nodeValue); print "</xmp>";
    // print "<h3>Book info data-numbering</h3><xmp>"; var_dump( $bookinfo->item(0)->attributes->getNamedItem('data-numbering')->nodeValue); print "</xmp>";
    // print "<h3>Book info data-navstyle</h3><xmp>"; var_dump( $bookinfo->item(0)->attributes->getNamedItem('data-navstyle')->nodeValue); print "</xmp>";
    if ( $bookinfo === false ) {
        return false;
    }

    foreach ($bookinfo as $book) {
        // print "<h3>Book info</h3><xmp>"; var_dump( $book->item($i)->nodeValue); print "</xmp>";
        $attrnames = ['title', 'dataintroformat',
                            'datacustomtitles', 'datanumbering', 'datanavstyle' ];
        foreach ($attrnames as $name) {
            $attribute = $book->attributes->getNamedItem( $name );
            $bookdetails->book->$name = $attribute->nodeValue;
        }
    }
    // print "<h3>book details</h3><xmp>"; var_dump( $bookdetails ); print "</xmp>";

    // Book_intro.
    $bookintro = $xpath->query( "//div[@class='intro']");
    if ( $bookintro === false ) {
        return false;
    }

    foreach ($bookintro as $intro) {
        $bookdetails->book->intro = booktool_github_dominnerhtml( $intro );
    }

    // print "<h3>book details intro</h3><xmp>"; var_dump( $bookdetails ); print "</xmp>";

    // Remove the headings for chapter titles from chapter content
    $headings = $xpath->query( "//h1");

    // print "<h3>Document </h3><xmp>"; var_dump( $document); print "</xmp>";
    // apparently have to do the dumy array thing for it to work
    // $remove = Array();

    // foreach ( $headings as $heading ) {
    // $remove[] = $heading;
    // }

    foreach ($headings as $heading) {
        $heading->parentNode->removeChild($heading);
    }
    // print "<h3>heading</h3><xmp>"; var_dump( $heading ); print "</xmp>";
    // print "<h3>Document after deletion</h3><xmp>"; var_dump( $document); print "</xmp>";
    // Get the chapter data.
    $chapters = $xpath->query( "//section");

    if ( $chapters === false ) {
        return false;
    }

    foreach ($chapters as $chapter) {
        $newchapter = [];
        $attrnames = ['title', 'datasubchapter', 'datapagenum', 'datahidden',
                            'datacontentformat'];
        foreach ($attrnames as $name) {
            $attribute = $chapter->attributes->getNamedItem($name );
            $newchapter[$name] = $attribute->nodeValue;
        }
        $newchapter['content'] = booktool_github_dominnerhtml( $chapter );
        // print "<h3>Content $newchapter[$name]</h3><xmp>";var_dump($newchapter['content']); print "</xmp>";
        array_push( $bookdetails->chapters, $newchapter );
    }

    // print "<h3>ALL Content from GIT</h3><xmp>"; var_dump( $bookdetails ); print "</xmp>";

    return $bookdetails;
}

function booktool_github_dominnerhtml(DOMNode $element) {
    $innerhtml = "";
    $children  = $element->childNodes;

    foreach ($children as $child) {
        $innerhtml .= $element->ownerDocument->saveHTML($child);
    }
    // Eliminate space.
    $innerhtml = trim($innerhtml);
    // Eliminate 2 div tags.

    return $innerhtml;
}



/*****************************************************************
 * "views" - functions that generate HTML to display
 ****************************************************************/

/*
 * Shows basic instructions about the github tool
 */

function booktool_github_show_instructions( $id ) {

    $giturl = new moodle_url( '/mod/book/tool/github/index.php',
                            ['id' => $id , 'instructions' => 1 ]);
    $bookurl = new moodle_url( '/mod/book/view.php', ['id' => $id ]);

    $urls = ['git_url' => $giturl->out(),
                   'book_url' => $bookurl->out()];

    $content = ['instructions_what_header', 'instructions_what_body',
                      'instructions_why_header', 'instructions_why_body',
                      'instructions_requirements_header',
                      'instructions_requirements_body',
                      'instructions_whatnext_header',
                      'instructions_whatnext_body' ];

    foreach ($content as $display) {
        print get_string( $display, 'booktool_github', $urls );
    }

}

/*****************************************************************
 * Support utils
 ****************************************************************/

// Encode/decode params
// - used to pass multiple paths via oauth as the STATE variable
// - enables github_oauth.php to know the id for the book and the
// the URL to return to
// Accept a hash array and convert it to url encoded string.
function booktool_github_url_encode_params( $params ) {
    $json = json_encode( $params );
    return strtr(base64_encode($json), '+/=', '-_,');
}

// Accept a url encoded string and return a hash array.
function booktool_github_url_decode_params($state) {
    $json = base64_decode(strtr($state, '-_,', '+/='));
    return json_decode( $json );
}





