<?php

return [
    'password' => [
        'minimum_length' => 16,
        'maximum_length' => 128,
        'history_limit' => (int) env('PASSWORD_HISTORY_LIMIT', 5),
        'check_compromised' => (bool) env('PASSWORD_COMPROMISED_CHECK', true),
        'blocked_sha256' => [
            '2e2b24f8ee40bb847fe85bb23336a39ef5948e6b49d897419ced68766b16967a',
            '7a51d064a1a216a692f753fcdab276e4ff201a01d8b66f56d50d4d719fd0dc87',
            'c91286e1aa92ccbb754e16b0167dba9bfa8311a5fa3d52d43e562938096680d4',
            '6b196120750961b5d88c7ed7dbb96f6159b5ba044c2fb202005201398d639db4',
            'da36a99d44d47f2a70a42d4d7d3216d443ae405ed5627d057c5a11eeee597bff',
            '21b37e299e6265a1ceea97502824efb147c64d1afa92b1a786826e9d2ee852be',
            'c072e19b20265eb9e29ba4e89be28800f2758bfa9c3e162ed6c772b75bd469c6',
        ],
    ],
];
