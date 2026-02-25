<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Giới hạn gia hạn tin đăng
    |--------------------------------------------------------------------------
    | Số lần tối đa cho phép gia hạn 1 tin đăng.
    */
    'max_post_renew' => (int) env('BDS_MAX_POST_RENEW', 3),
];
