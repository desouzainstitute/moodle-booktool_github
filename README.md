# moodle-booktool_github
This is a tool for the Moodle Book module. The aim is to allow the contents of a Book resource to be connected with a single file within a github repository.

The aim is to allow the sharing of the content beyond a single Moodle course. Enabling sharing and potentially the collaborative authoring of the same content from another Moodle course, Moodle instance, or outside of Moodle entirely.

Initial development is part of the Moodle open book project - https://davidtjones.wordpress.com/research/the-moodle-open-book-module-project/

**Current Status**

Is basically working. Will push/pull content for a Moodle book from a github repository. Install the plugin at /mod/book/tool/github

The interface is ugly and incomplete.

*Requirements*

* GitHub account
* Repository

## Steps to Configure and Test the booktool_github plugin

1. First, configure some information that is hard-coded

    + **Getting the Client ID and Client Secret of the app in github:**

        Go to the GitHub account in https://github.com/settings/developers and add a new Oauth App:

            Provide similar information:

                ```
                Name: booktool_github
                Homepage URL: https://github.com/desouzainstitute/moodle-booktool_github
                Application description: Book Tool Github Plugin
                Authorization callback URL: https://path/to/moodle/mod/book/tool/github/get_oauth.php
                ```
        A new Client ID is generated, then you have to generate a new client secret:

        (The following is fake information for you to use as reference)
        ```
      	Client ID : 8f2c45e847b3355d2426
      	Client Secret : 5a2f83459aa0ece52e44b14f81e53a033aef6cd7
        ```
      Then, add this information to the mdl_booktool_github table. With this information you can connect to the GitHub account and access all public and private repositories.
      Finally, go and fix this in code: mod/book/tool/github/get_oauth.php:line 36

      ``` php
      const REDIRECT_URI = 'https://path/to/moodle//mod/book/tool/github/get_oauth.php';
      ```
    + **Decide what is the committer information: Name and email**

        For testing, I have setup the committer information as catalyst-ca and dev+git@catalyst-ca.net which recognizes catalyst GitHub account. This information is also hard-coded at line 606 and 607 on locallib.php
      ``` php
        $data['committer'] = ['name' => 'catalyst-ca',
      'email' => 'dev+git@catalyst-ca.net' ];
      ```

2. To test the plugin:
    + Create a Repository (public or private) and 1 html file in GitHub to have the book information.
    + Create a Book in a sample course in Moodle with information in chapters.
    + Go to the booktool_github plugin inside the book and connect to the previously created repository and file.
    + Push the information which generates a single HTML file in the GitHub repository.
    + Similarly, changes in the GitHub HTML file can be pulled and the information of the book is updated.

## Some clear limitations of the plugin:

+ The major limitation is that the plugin uses a single HTML file to save the information in GitHub. Its strategy is to convert each chapter to a section in the body of the HTML file and add some headings to make the HTML file more readable. Therefore, to be able to modify correctly the HTML file from GitHub we must be careful with its fixed structure.
+ Connection settings are manually entered.
+ Committer details are hard-coded.
+ No Branch information available always uses the main branch and last commit.
+ CSS information is not saved.
