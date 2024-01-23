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
 * github booktool language strings
 *
 * @package    booktool_github
 * @copyright  2015 David Jones {@link http://djon.es}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['pluginname'] = 'Book github link';
$string['github'] = 'GitHub';

// Booktool_github_view_comments.
$string['commit_details'] = 'commit details';

$string['github_redirect'] = '<h3>Asking for GitHub authorisation</h3><p>To work this tool must have your permission to use your GitHub account to interact with GitHub. Heading off to GitHub to ask you for this permission.</p>';

// Connection form.
$string['form_empty'] = '<h2>Provide GitHub connection details</h2> <p>To work this tool requires two bits of information to work. These are:</p><ol> <li> GitHub <a href="https://help.github.com/articles/github-glossary/#repository">repository</a> <p>e.g. the name of <a href="https://github.com/djplaner/bim2">this repository</a> is <em>bim2</em></li> <li> Path to file in repository. <p>e.g. the path <a href="https://github.com/djplaner/bim2/blob/master/db/log.php">for this file</a> from the <em>bim2</em> repository is <em>db/log.php</em>.  </li> </ol>';
$string['form_complete'] = '<h2>Current GitHub connection details</h2> <p><a href="{$a->book_url}">This book</a> is currently connected to <a href="{$a->git_url}">this file</a> in <a href="{$a->repo_url}">this GitHub repository</a>. View the file as <a href="{$a->rawgit_url}">a web page</a>.</p><p> Use the form below to change the details of this connection.</p>';
$string['form_connection_broken'] = '<h2>No valid GitHub connection</h2> <p>The information provided in the form below is unable to form a working connection to GitHub.</p>';

$string['form_no_commits'] = '<p>There have been no data added to <a href="{$a->git_url}">this file</a> from <a href="{$a->repo_url}">this repository</a>.';
$string['form_history'] = '<h3>Change history</h3> <p>The following provides a summary of the changes that <a href="{$a->git_url}">this file</a> from <a href="{$a->repo_url}">this repository</a> has undergone.</p>';

$string['form_status'] = '<h3>Status</h3> <p>Currently, the relationship between <a href="{$a->book_url}">this book</a> and <a href="{$a->git_url}">the GitHub file</a> is:</p><ul>{$a->status}</ul>';
$string['book_revision'] = '<li> <span style="background-color: #ffff99">The book has been revised since the last push.</span> </li>';
$string['missing_push'] = '<li> <span style="background-color: #ffff99">The GitHub file does not appear to have the latest push from the book.</span> </li>';
$string['behind_git'] = '<li> <span style="background-color: #ffff99">The GitHub file is ahead of the book.</span> </li>';
$string['consistent'] = '<li> <span style="background-color: #66ff66">The Book and the GitHub file are the same.</span> </li>';
$string['form_operations'] = '<h3>GitHub operations</h3> <p>You can:</p><ol> <li> <strong><a href="{$a->push_url}">Push</a></strong> - the content of the book to the GitHub file. </li> <li> <strong><a href="{$a->pull_url}">Pull</a></strong> - the content of the GitHub file back into the book. </li> </ol>';


$string['repo_form_element'] = 'GitHub repository:';
$string['repo_form_default'] = 'Enter name of github repository';
$string['path_form_element'] = 'Path to file in repository:';
$string['repo_path_default'] = 'Enter full path to file';

$string['form_no_change_default_error'] = '<h3>Error saving changes</h3> <p>No changes made to the default values for repository and path. No changes saved. </p> <p>Please modify the repository and path to point to a specific file in a GitHub repository.</p>';
$string['form_repo_not_exist_error'] = '<h3>No such repository</h3> <p>It appears that the repository {$a->owner}/{$a->repo} does not exist.  It should be located <a href="http://github.com/{$a->owner}/{$a->repo}">here</a>.</p><p>Please</p><ol> <li> Visit the github repository via the Web. </li> <li> Ensure that the owner of the repo matches {$a->owner}</li> <li> Ensure that the repository name matches {$a->repo}</li> <li> Make any changes required in the form below. </li> </ol><p>&nbsp;</p>';
$string['form_no_create_file'] = '<h3>Unable to find or create path</h3> <p>It appears that the specified path (<a href="http://github.com/{$a->owner}/{$a->repo}/blob/master/{$a->path}">http://github.com/{$a->owner}/{$a->repo}/blob/master/{$a->path}</a> does not exist and cannot be created.</p><p><p>Please</p><ol> <li> Visit the github repository via the Web. </li> <li> Ensure that the owner of the repo matches {$a->owner}</li> <li> Ensure that the repository name matches {$a->repo}</li> <li> Ensure that the path either exists or can be created. <li> Make any changes required in the form below. </li> </ol><p>&nbsp;</p>';
$string['form_no_database_write'] = '<h3>Unable to update database</h3><p>Unable to save changes to connection details.</p> <p>Please contact your local support to correct this problem.</p> ';

/******************************************************************
 * string for push and pull pages
 */

$string['push_form_crumb'] = 'Push';
$string['push_button'] = 'Push';
$string['push_warning'] = '<h2>Warning about pushing</h2> <p>If you continue with this process the current contents of <a href="{$a->book_url}">this Moodle book</a> will be copied onto GitHub as the next version of <a href="{$a->git_url}">this file</a>. The implications of this action include:</p> <ol> <li> All content of the Moodle book will be available to anyone who can access <a href="{$a->repo_url}">the GitHub repository</a>.</li> </ol> <p style="text-align:center"><strong>Return to:</strong> <a href="{$a->tool_url}">GitHub Tool</a> | <a href="{$a->book_url}">the Book</a></p><h3>Push details</h3> <p>To complete the push:</p><ol><li> Enter a description of what is being pushed. <p>The description is added to the history of the file on GitHub and is useful for understanding the evolution of the file.</p> </li> <li> Hit the "push" button. </li></ol>';
$string['push_form_message'] = 'Description of push: ';
$string['push_form_default_message'] = 'Replace with your description';
$string['push_success'] = '<h3>Push succeeded</h3> <p>Current content of <a href="{$a->book_url}">this book</a> has been pushed to <a href="{$a->git_url}">this file</a> in <a href="{$a->repo_url}">this GitHub repository</a>.</p><p style="text-align:center"><strong>Return to:</strong> <a href="{$a->tool_url}">GitHub Tool</a> | <a href="{$a->book_url}">the Book</a></p>';
$string['push_failure'] = '<h3>Push failed</h3> <p>Unable to push the content of <a href="{$a->book_url}">this book</a> has been pushed to <a href="{$a->git_url}">this file</a> in <a href="{$a->repo_url}">this GitHub repository</a>.</p><p>Check that:</p><ol> <li> Your <a href="{$a->git_user_url}">github account</a> has permission to modify <a href="{$a->git_url}">this file</a> in <a href="{$a->repo_url}">this GitHub repository</a>. </li> </ol><p style="text-align:center"><strong>Return to:</strong> <a href="{$a->tool_url}">GitHub Tool</a> | <a href="{$a->book_url}">the Book</a></p>';

$string['pull_button'] = 'Pull';
$string['pull_form_crumb'] = 'Pull';
$string['pull_warning'] = '<h2>Pull warning</h2> <p>If you continue with this process the current contents of <a href="{$a->book_url}">this Moodle book</a> will be replaced with the contents of <a href="{$a->git_url}">this file</a>. </p><p>Are you sure you wish to do this?</p> <p>If required, will you be able to recover the contents that are replaced? (If you have previously pushed the contents to GitHub, you can) </p>';
$string['pull_warning_unsaved_changes'] = '<h3>Warning</h3> <p>It appears that the latest changes to <a href="{$a->book_url}this Moodle book</a> have not previously been <a href="{$a}">pushed</a> to GitHub. Suggesting some content will be lost.</p>';
$string['pull_success'] = '<h3>Pull succeeded</h3> <p>The content of <a href="{$a->book_url}">this book</a> has been replaced by the content of <a href="{$a->git_url}">this file</a> from <a href="{$a->repo_url}">this GitHub repository</a>.</p><p style="text-align:center"><strong>Return to:</strong> <a href="{$a->tool_url}">GitHub Tool</a> | <a href="{$a->book_url}">the Book</a></p>';
$string['pull_failure'] = '<h3>Pull failed</h3> <p>Unable to replace the content of <a href="{$a->book_url}">this book</a> with the contents of <a href="{$a->git_url}">this file</a> from <a href="{$a->repo_url}">this GitHub repository</a>.</p><p>Check that:</p><ol> <li> Your <a href="{$a->git_user_url}">github account</a> has permission to pull content from <a href="{$a->git_url}">this file</a> in <a href="{$a->repo_url}">this GitHub repository</a>. </li> </ol><p style="text-align:center"><strong>Return to:</strong> <a href="{$a->tool_url}">GitHub Tool</a> | <a href="{$a->book_url}">the Book</a></p>';


/******************************************************************
 * strings for show functions
 */

$string['instructions_what_header'] = '<h2>What?</h2>';
$string['instructions_what_body'] = '<p>This tool allows the content of this <a href="https://docs.moodle.org/29/en/Book_module">Moodle Book</a> to linked to a single file within a <a href="https://guides.github.com/activities/hello-world/#repository">GitHub repository</a>. This link allows the content of the Book to be based on the content of the file in the GitHub repository.</p>';

$string['instructions_why_header'] = '<h2>Why?</h2>';
$string['instructions_why_body'] = '<p>Doing this enhances the Book with features provided by <a href="http://github.com/">GitHub</a>, including:</p><ul>
   <li> version control </li>
  <li> issue tracking </li>
  <li> open sharing beyond the LMS </li> </ul>';

$string['instructions_requirements_header'] = '<h2>Requirements?</h2>';
$string['instructions_requirements_body'] = '<p>Before you can use the GitHub tool with the Book module, you will need:</p><ol> <li> A <a href="https://github.com/join">GitHub account</a>. </li>
    <li> A <a href="https://help.github.com/articles/create-a-repo/">GitHub repository</a>
<p>The repository can be a new repository you created, or an existing repository. The repository can be owned by anyone, but the GithHub account you use will need to have permission to commit changes to the repository. You may need to <a href="https://help.github.com/articles/fork-a-repo/">fork</a> an existing repository to get the necessary permissions.</p>
 </li>
    <li> The path to a specific file within the respository.
<p>The path indiciates which file within the repository will be connected to t
he Book activity.</p></li></ol>';

$string['instructions_whatnext_header'] = '<h2>What next?</h2>';
$string['instructions_whatnext_body'] = '<ol> <li> If you have all the requirements listed above, then <a href="{$a->git_url}">click here</a> to start using the GitHub book tool;<p>Your first task will be to login to your GitHub account.</p> </li> <li> If you want to wait till later, <a href="{$a->book_url}">return to the book</a>. </li> </ol> ';

$string['github:export'] = 'Export book as Github repository';
