<?php

return [
    'contact' => [
        'email' => env('BAAKH_CONTACT_EMAIL', 'support@baakh.com'),
        'telephone' => env('BAAKH_CONTACT_PHONE'),
        'contact_type' => 'customer support',
        'street' => env('BAAKH_ADDRESS_STREET', 'Office 428, 4th Floor, Mashreq Center, Expo Center'),
        'locality' => env('BAAKH_ADDRESS_LOCALITY', 'Karachi'),
        'region' => env('BAAKH_ADDRESS_REGION', 'Sindh'),
        'postal_code' => env('BAAKH_ADDRESS_POSTAL_CODE'),
        'country' => env('BAAKH_ADDRESS_COUNTRY', 'PK'),
    ],
    'same_as' => array_values(array_filter([
        'https://x.com/BaakhConnect',
        'https://www.facebook.com/baakhconnect',
        'https://www.instagram.com/baakhconnect',
        env('BAAKH_SAME_AS_EXTRA'),
    ])),
];
