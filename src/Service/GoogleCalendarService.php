<?php

namespace App\Service;

use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;

class GoogleCalendarService
{
    private Calendar $calendar;

    public function __construct()
    {
        $client = new Client();

        $client->setAuthConfig(
            dirname(__DIR__,2).'/config/google/calendar.json'
        );

        $client->setScopes(Calendar::CALENDAR);

        $this->calendar = new Calendar($client);
    }

    public function createEvent(
        string $titre,
        \DateTime $start,
        \DateTime $end
    )
    {
        $event = new Event([
            'summary' => $titre,
            'start' => [
                'dateTime' => $start->format('c'),
                'timeZone' => 'Africa/Tunis',
            ],
            'end' => [
                'dateTime' => $end->format('c'),
                'timeZone' => 'Africa/Tunis',
            ],
        ]);

        return $this->calendar
            ->events
            ->insert('primary', $event);
    }
}