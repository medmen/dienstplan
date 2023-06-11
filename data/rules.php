<?php
/**
 * @TODO: merge into rules
 */
return( array(
    'rules' => [
        'max_duties' => ['type'=> 'int', 'value' => 5],
        'max_weekend_duties' => ['type'=> 'int', 'value' => 2, 'explain' => 'Max Anzahl von Wochenend-Diensten (Sa,So & Feiertag)'],
        'max_friday_duties' => ['type'=> 'int', 'value' => 2, 'explain' => 'Max Anzahl von Diensten am Freitag'],
        'max_iterations' => ['type'=> 'int', 'value' => 500, 'explain' => 'Max Versuche bis Algorithmus aufgibt']
    ]
)
);
