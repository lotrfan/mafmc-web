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
        $this->addTwigFilter('preg_split', 'pregSplit');
        return;
    }

    function pregSplit($item, $pat, $limit) {
      // return array("$item", "$pat", "$limit");
        return preg_split($pat, $item, $limit);
    }

    // Notes, 
    // To get this working: 
    // - New application
    // - Authorize the application in GAFYD
    // e.g. here: https://admin.google.com/AdminHome?chromeless=1#OGX:ManageOauthClients
    // e.g. here: https://console.developers.google.com/project/gcal-api-access/apiui/credential
    // - ... use 'sub' as ??

    // $calendarName -> the name from config.yml
    // $optArguments -> see https://developers.google.com/google-apps/calendar/v3/reference/events/list for full list
    public function calendarList($calendarName, $count = -1, $optArgs = array(), $useDefaults = true ) {
    
        // This should really throw an error
        if (!is_array($optArgs)) {
            $optArgs = array();
        }

        // use nicer default values
        if ($useDefaults) {
            // show no more than 10 events by default
            // error_log($optArgs['maxResults']);
            // $this->default_value($optArgs['maxResults'], 10);
            // error_log($optArgs['maxResults']);
            
            // sort by start time ascending
            $this->default_value($optArgs['orderBy'], 'startTime');

            // expand recurring events into single event instances
            $this->default_value($optArgs['singleEvents'], true);

            // default to not show any events before today
            $t = new \DateTime(NULL);
            error_log("Datetime is: " . $t->format(\DateTime::RFC3339));
            $this->default_value($optArgs['timeMin'], $t->format(\DateTime::RFC3339) );
        }


        // read from config file
        $calendarUser = $this->config['api_information']['application_user'];
        $appName      = $this->config['api_information']['application_name'];
        $appEmail     = $this->config['api_information']['application_email'];
        $appKeyFile   = $this->config['api_information']['keyfile'];
        $calendars    = $this->config['api_information']['calendars'];

        error_log("calendars are:");
        error_log(var_export($calendars,true));
        error_log(var_export($calendars[$calendarName],true));
        error_log("done...");
        // error or exception
        if (!array_key_exists($calendarName, $calendars)) {
            return new \Twig_Markup("ERROR: calendar [$calendarName] not found in settings", 'UTF-8');
        } else {
            $calendar = $calendars[$calendarName]['id'];
        }
        
        // add jQuery to the page (doesn't really belong here...)
        $this->addJquery();

        // Following the example here: 
        // https://github.com/google/google-api-php-client/blob/master/examples/service-account.php
        // See also: https://developers.google.com/accounts/docs/OAuth2ServiceAccount?hl=ja

        // Get a new client object
        error_log("Calendar: new client");
        $client = new \Google_Client();
        $client->setApplicationName($appName);
        $svc = new \Google_Service_Calendar($client);

        // read in the key
        $key = file_get_contents(dirname(__FILE__) . '/' . $appKeyFile );

        if (isset($_SESSION['service_token'])) {
            $client->setAccessToken($_SESSION['service_token']);
        }

        // set up credentials used to access calendar
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
            error_log("Refreshing token!!!");
            $client->getAuth()->refreshTokenWithAssertion($cred);
            error_log("Token refreshed!!!");
        }
        error_log("DONE refreshing credentials!");

        // error_log("getting token");
        // $_SESSION['service_token'] = $client->getAccessToken();

        // error_log("printing token");
        // error_log(var_export($_SESSION[''], true));
        // error_log($client->getAccessToken());
        // return new \Twig_Markup('...', 'UTF-8');

        // example use of listAcl API
        // error_log("Calendar: get acls");
        // $acl = $svc->acl->listAcl($calendarUser);
        // error_log("Calendar: get acls done");
        // foreach ($acl->getItems() as $rule) {
        //     error_log( $rule->getId() . ': ' . $rule->getRole());
        // }

        // example of how to list a calendar
        // error_log("Listing calendars...");
        // $calendarList = $svc->calendarList;
        // $res = $calendarList->listCalendarList();
        // error_log("ERROR LOG: ");
        // error_log ($res);
        // error_log (var_export($res, true));
        // error_log("ERROR LOG DONE");
        // error_log("Done listing calendars");


        // $events = $svc->events->listEvents($calendarUser);

        error_log("Listing events...");
        // $events = $svc->events->listEvents($calendarToList);
        $events = $svc->events->listEvents($calendar, $optArgs);
        error_log("GOT EVENTS");
        error_log(var_export($events, true));
        error_log("DONE EVENTS");

        $ret = array();

        // get alllll the events for the calendar
        while(true) {
            foreach ($events->getItems() as $event) {
                $ret[] = $event;
                if ($count > 0 && $count == count($ret)) {
                  return $ret;
                }
                error_log( "Got event: " . $event->getSummary());
            }
            // break; 

            // FIXME: re-enable this?
            $pageToken = $events->getNextPageToken();
            if ($pageToken) {
            $optArgs['pageToken'] = $pageToken;
            // $optParams = array('pageToken' => $pageToken);
            error_log("getting more events...");
            $events = $svc->events->listEvents($calendar, $optArgs);
            } else {
            break;
            }
        }

       return $ret;

       $html = var_export($ret, true);
       return new \Twig_Markup($html, 'UTF-8');
    }

function default_value(&$var, $default) {
    if (empty($var)) {
        $var = $default;
    }
}

}


