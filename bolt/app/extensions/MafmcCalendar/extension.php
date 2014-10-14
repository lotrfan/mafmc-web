<?php
/**
 * MafmcCalendar extension for Bolt.
 *
 * @author Jacob Tolar <jacob@sheckel.net>
 */

namespace MafmcCalendar;

// FIXME: this is not great...
require dirname(__FILE__)  . '/' . 'vendor/autoload.php';

error_log(var_export(get_declared_classes(), true));

class Extension extends \Bolt\BaseExtension
{

    public function info()
    {
        return array(
            'name' => "MafmcCalendar",
            'description' => "Calendar events for MAFMC",
            'author' => "Jacob Tolar",
            'link' => "http://bolt.cm",
            'version' => "0.1",
            'required_bolt_version' => "1.2.0",
            'highest_bolt_version' => "1.4.0",
            'type' => "General",
            'first_releasedate' => null,
            'latest_releasedate' => null,
            'priority' => 10
        );
    }

    public function initialize()
    {
        $this->app['htmlsnippets'] = true;
        $this->addTwigFunction('calendar', 'calendarList');
        return;
    }

// Notes, 
// To get this working: 
// - New application
// - Authorize the application in GAFYD
// e.g. here: https://admin.google.com/AdminHome?chromeless=1#OGX:ManageOauthClients
// e.g. here: https://console.developers.google.com/project/gcal-api-access/apiui/credential
// - ... use 'sub' as ??
    public function calendarList()
    {

        $calendarUser = $this->config['api_information']['application_user'];
        $appName = $this->config['api_information']['application_name'];
        $appEmail = $this->config['api_information']['application_email'];
        $appKeyFile = $this->config['api_information']['keyfile'];
        
        error_log("Calendar: $calendarUser");

        // Probably doesn't belong here, but...
        error_log("Calendar: addjquery");
        $this->addJquery();

        // Following the example here: 
        // https://github.com/google/google-api-php-client/blob/master/examples/service-account.php
        // See also: https://developers.google.com/accounts/docs/OAuth2ServiceAccount?hl=ja

        error_log("Calendar: new client");
        $client = new \Google_Client();
        $client->setApplicationName($appName);
        $svc = new \Google_Service_Calendar($client);
        $key = file_get_contents(dirname(__FILE__) . '/' . $appKeyFile );

        error_log("Calendar: set credentials");

        if (isset($_SESSION['service_token'])) {
            $client->setAccessToken($_SESSION['service_token']);
        }

        error_log("Calendar: new credentials");
        $cred = new \Google_Auth_AssertionCredentials(
          $appEmail,
          array('https://www.googleapis.com/auth/calendar'),
          $key
        );

        $cred->sub = $calendarUser;
        $cred->prn = $calendarUser;

        $client->setAssertionCredentials($cred);

        error_log("Calendar: potentially refresh credentials");
        if($client->getAuth()->isAccessTokenExpired()) {
            $client->getAuth()->refreshTokenWithAssertion($cred);
        }
        $_SESSION['service_token'] = $client->getAccessToken();

        error_log("Calendar: get acls");
        $acl = $svc->acl->listAcl($calendarUser);
        error_log("Calendar: get acls done");

        foreach ($acl->getItems() as $rule) {
            error_log( $rule->getId() . ': ' . $rule->getRole());
        }

        $calendarList = $svc->calendarList;
        $res = $calendarList->listCalendarList();
        error_log("ERROR LOG: ");
        error_log (var_export($res), true);
        error_log("ERROR LOG DONE");




        error_log("trying...");
        $events = $svc->events->listEvents($calendarUser);
        error_log("GOT EVENTS");
        error_log(var_export($events, true));
        error_log("DONE EVENTS");

        while(true) {
            foreach ($events->getItems() as $event) {
                error_log( $event->getSummary());
            }
            $pageToken = $events->getNextPageToken();
            if ($pageToken) {
                $optParams = array('pageToken' => $pageToken);
                $events = $svc->events->listEvents('primary', $optParams);
            } else {
                break;
            }
        }

       $html = var_export($res, true);
       return new \Twig_Markup($html, 'UTF-8');
    }
}
