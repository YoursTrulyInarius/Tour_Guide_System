<?php
/**
 * Official Philippine Holidays for 2026 (Proclamation No. 1006)
 */
function getPhilippineHolidays($year = 2026) {
    if ($year != 2026) return []; // Proclamation only covers 2026

    return [
        '2026-01-01' => "New Year's Day",
        '2026-02-17' => "Chinese New Year",
        '2026-02-25' => "EDSA People Power Anniversary",
        '2026-04-02' => "Maundy Thursday",
        '2026-04-03' => "Good Friday",
        '2026-04-04' => "Black Saturday",
        '2026-04-09' => "Araw ng Kagitingan",
        '2026-05-01' => "Labor Day",
        '2026-06-12' => "Independence Day",
        '2026-08-21' => "Ninoy Aquino Day",
        '2026-08-31' => "National Heroes Day",
        '2026-11-01' => "All Saints' Day",
        '2026-11-02' => "All Souls' Day",
        '2026-11-30' => "Bonifacio Day",
        '2026-12-08' => "Feast of the Immaculate Conception",
        '2026-12-24' => "Christmas Eve",
        '2026-12-25' => "Christmas Day",
        '2026-12-30' => "Rizal Day",
        '2026-12-31' => "Last Day of the Year",
        // Note: Eid'l Fitr and Eid'l Adha are TBD by separate proclamations
    ];
}

function isPhilippineHoliday($date) {
    $holidays = getPhilippineHolidays(date('Y', strtotime($date)));
    return $holidays[$date] ?? false;
}
