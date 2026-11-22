<?php

return [
    'base_url' => env('DHAN_API_BASE', 'https://api.dhan.co/v2'),
    'access_token' => env('DHAN_ACCESS_TOKEN'),
    'client_id' => env('DHAN_CLIENT_ID'),
    'nifty_index_security_id' => (int) env('DHAN_NIFTY_SECURITY_ID', 13),
    'nifty_exchange_segment' => env('DHAN_NIFTY_EXCHANGE_SEGMENT', 'IDX_I'),
    'nse_master_csv' => env(
        'DHAN_INSTRUMENT_CSV',
        'https://images.dhan.co/api-data/api-scrip-master.csv'
    ),
    'nse_constituents_csv' => env(
        'NIFTY_CONSTITUENTS_CSV',
        'https://nsearchives.nseindia.com/content/indices/ind_nifty50list.csv'
    ),
];