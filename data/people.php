
<?php
/**
 * Created by PhpStorm.
 * User: galak
 * Date: 23.03.17
 * Time: 22:50
 */
/**
$config['people'] = [
    'anton',
    'berta',
    'conny',
    'dick',
    'egon',
    'floppy',
    'guste',
    'harald',
    'irmfried',
    'juste',
    'knöddelkopp',
];
**/
return( array(
        'anton' => ['fullname' => 'Anton Anders', 'pw' => '$2y$10$cv0fitJNDmQdzydZBGcW7eBYqmwqcpSQWMOqt/FiFrTthVqHZqHD6'], // pw chaf666
        'berta' => ['fullname' => 'Berta Besonders', 'pw' => '$2y$10$cv0fitJNDmQdzydZBGcW7eBYqmwqcpSQWMOqt/FiFrTthVqHZqHD6', 'is_admin' => true],
        'conny' => ['fullname' => 'Cornelia Chaos'],
        'dick' => ['firstname' => 'Dirk', 'lastname' => 'Ickinger'],
        'egon' => ['fullname' => 'Egon Eklig'],
        'floppy' => ['fullname' => 'Florian Popp', 'inactive' => true],
        'guste' => ['inactive' => ['start'=> '01.02.2023', 'end' => '31.12.2025']],
        'harald' => ['fullname' => 'Harry Ald'],
//        'irmfried' => ['fullname' => 'Irma Friedrich'],
//        'juste' => ['fullname' => 'Jutta Stenzel'],
    )
);
