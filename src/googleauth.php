<?php

class GoogleAuth
{
    private Google\Client $client;
    private Google\Service\YouTube $youtubeService;

    function __construct()
    {
        $this->client = new Google\Client();
        $this->client->setApplicationName("Client_Library_Examples");
        $this->client->setDeveloperKey("YOUR_APP_KEY");
        $this->youtubeService = new Google\Service\Youtube($this->client);
    }

    function auth()
    {
        session_start();
        $this->client->setAuthConfig('client_secrets.json');
        $this->client->addScope(Google\Service\Youtube::YOUTUBE_READONLY);

        if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
            $this->downloadFeed();
        } else {
            $redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/oauth2callback.php';
            header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
        }
        session_write_close();
    }

    function authCallback($code)
    {
        session_start();
        if (!isset($code)) {
            $auth_url = $this->client->createAuthUrl();
            header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
        } else {
            $this->client->authenticate($code);
            $_SESSION['access_token'] = $this->client->getAccessToken();
            $redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/';
            header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
        }
        session_write_close();
    }

    function downloadFeed()
    {
        
    }
}