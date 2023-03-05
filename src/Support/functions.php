<?php
/**
 * Convert all applicable characters to HTML entities.
 *
 * @param string|null $text The string
 *
 * @return string The html encoded string
 */
function html(string $text = null): string
{
    return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function is_german_holiday(DateTime $date): bool
{
   // https://www.expatrio.com/living-germany/german-culture/german-holidays-and-celebrations
   $feste_feiertage = array('first of january' => 'huhu');

   return false;
}


function beweglicheFeiertage( int $year ):array
{
    //Karfreitag - good friday
    $return = array();
    $easterDate = DateTime::createFromFormat('U', easter_date($year) );
    $easterDate->modify('- 1 days');
    $return[] = $easterDate->format('Y-m-d');
    //Ostermontag easter monday
    $easterDate = DateTime::createFromFormat('U', easter_date($year) );
    $easterDate->modify('+ 2 day');
    $return[] = $easterDate->format('Y-m-d');
    //Himmelfahrt ascencion
    $easterDate = DateTime::createFromFormat('U', easter_date($year) );
    $easterDate->modify('+ 40 days');//go to Ascencionday
    $return[] = $easterDate->format('Y-m-d');
    //Pfingstmontag - pentecost monday
    $easterDate = DateTime::createFromFormat('U', easter_date($year) );
    $easterDate->modify('+ 51 days');//go to Pentecost Monday
    $return[] = $easterDate->format('Y-m-d');

    return $return;
}
