<?php
 /**
 * @TODO: merge into rules
  *
  * Things to consider:
  * Limit Total depends on people availiable:
  *- if 6 people available and limit_total = 5 rules will fail(!) for months with 31 days
  * Limit We will fail certainly if set below 2:
  * up to 5 weekends exist in months meaning 10 weekend duties(!)
  *
  *
  *This meany: min values for FULL TIME Employees are total:6, we:2, fr:1  for 5 employees
  * going below this may work eventually, but will really stress the algorithm :)
 */

return( array(
            'total' => 6,
            'we' => 2,
            'fr' => 1,
            'max_iterations' => 500
    )
);
