<?php 
// app/Helpers/DateHelper.php
function sindhi_date($format, $timestamp = null) {
    $translatedMonths = trans('dates.months');
    $translatedDays = trans('dates.days');
    $translatedPeriods = trans('dates.periods');

    if ($timestamp === null) {
        $timestamp = time();
    }

    $formattedDate = date($format, $timestamp);

    // Replace English month names, day names, and periods with Sindhi translated names
    $formattedDate = strtr($formattedDate, $translatedMonths);
    $formattedDate = strtr($formattedDate, $translatedDays);
    $formattedDate = strtr($formattedDate, $translatedPeriods);

    return $formattedDate;
}

 

?>