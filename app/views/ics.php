<?php

use \Eluceo\iCal\Component\{Calendar, Event};


$vCalendar = new Calendar('www.example.com');

$vEvent = new Event();


$vEvent
    ->setDtStart(new \DateTime('2012-12-24'))
    ->setDtEnd(new \DateTime('2012-12-24'))
    ->setNoTime(true)
    ->setSummary('Christmas')
;


$vCalendar->addComponent($vEvent);


header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="cal.ics"');


echo $vCalendar->render();
