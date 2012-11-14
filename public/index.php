<?php


require '../vendor/autoload.php';

// Prepare app
$app = new \Slim\Slim(array(
    'templates.path' => '../templates',
    'log.level' => 4,
    'log.enabled' => true,
    'log.writer' => new \Slim\Extras\Log\DateTimeFileWriter(array(
        'path' => '../logs',
        'name_format' => 'y-m-d'
    ))
));

// Prepare view
\Slim\Extras\Views\Twig::$twigOptions = array(
    'charset' => 'utf-8',
    'cache' => realpath('../templates/cache'),
    'auto_reload' => true,
    'strict_variables' => false,
    'autoescape' => true,
    'debug' => true
);


$app->view(new \Slim\Extras\Views\Twig());

// Define routes

/*
 * Homepage route. All we do here is show the list
 */
$app->get('/', function () use ($app) {

    // get current calendars. they are stored as an ini file in ../config/calendars
    $finder = new \Symfony\Component\Finder\Finder();
    $calendars_ini  = $finder->files()->name("*.ini")->in('../config/calendars/');

    $calendars = array();
    foreach($calendars_ini as $calendar_ini)
    {
        // create a slug from config filename
        $calendars[] = array('slug'=> str_replace('.ini', '', basename($calendar_ini)), 'data'=>new \Jam\Config\Ini($calendar_ini, 'calendar'));
    }

    // show the list of calendars
    $app->render('index.html.twig', array('calendars' => $calendars));
});

/*
 * Route for getting a specific calendar conf, parsing the origin, and shooting back an iCal file
 */

$app->get('/calendar/:slug', function($slug) use ($app) {


    $calendar_ini = new \Jam\Config\Ini('../config/calendars/'.$slug.'.ini', 'calendar');

    // start up curl
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $calendar_ini->url);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $calendar_ini->url_post_data);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    $calendar_html = curl_exec($curl);

    // create the dom crawler object
    $crawler = new \Symfony\Component\DomCrawler\Crawler();
    $crawler->addContent($calendar_html);

    // start up the ICS we are going to send the user
    $ics = new \Eluceo\iCal\Component\Calendar($slug);
    date_default_timezone_set($calendar_ini->timezone);


    // get the list of days on which there are events we want to create entries for
    $days = $crawler->filterXPath($calendar_ini->xpath_days);
    $days->each(function($day, $i) use ($crawler, $calendar_ini, $ics) {

        // get all the events for the day.
        // rational for this is that usually the date will be in a single <td> with the events
        $xpath_events_day = str_replace('%%DAY%%', $day->nodeValue, $calendar_ini->xpath_events);
        $events = $crawler->filterXpath($xpath_events_day);

        $events->each(function($event, $i) use ($calendar_ini, $day, $ics) {

            $event = new \Symfony\Component\DomCrawler\Crawler($event);
            var_dump($event->filterXpath($calendar_ini->xpath_event_start_day)->text());
            //exception is thrown here about an empty node set.

            //die();
            //$ics_event_data['start_day'] = new \DateTime($event->filterXpath($calendar_ini->xpath_event_start_day)->text());
            //$ics_event_data['end_day'] = new \DateTime($event->filterXpath($calendar_ini->xpath_event_end_day)->text());
            //$ics_event_data['description'] = '';//$event->filterXpath($calendar_ini->xpath_event_description)->text();
            //$ics_event_data['start_time'] = '';//new \DateTime($event->filterXpath($calendar_ini->xpath_event_start_time)->text());
            //$ics_event_data['end_time'] = '';//new \DateTime($event->filterXpath($calendar_ini->xpath_event_end_time)->text());


             $ics_event = new \Eluceo\iCal\Component\Event();
             $ics_event->setDtStart($ics_event_data['start_time']);
             $ics_event->setDtStart($ics_event_data['end_time']);

             $ics_event->setDescription($ics_event_data['description']);
             $ics->addEvent($ics_event);
        });
    });




   // header('Content-Type: text/calendar; charset=utf-8');
   // header('Content-Disposition: attachment; filename="'.$slug.'.ics"');
    echo $vCalendar->render();

});

// Run app
$app->run();
