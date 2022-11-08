<?php

// Production environment

return function (array $settings): array {
    // Enable caching etc.
    // ...

    // Database name
    // $settings['db']['database'] = 'my_prod_db';

    return $settings;
};
