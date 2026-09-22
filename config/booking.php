<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Booking Down Payment (DP) Amount
    |--------------------------------------------------------------------------
    |
    | Nominal pembayaran uang muka (DP) per tamu saat melakukan booking.
    |
    */
    'dp_amount' => (int) env('BOOKING_DP_AMOUNT', 40000),

];
