<?php $this->setLayout('layout.php'); ?>
<?php $this->addAttribute('css', ['css/skeleton.css', 'css/normalize.css', 'css/flashmessages.css', 'css/style.css', 'css/dienstplan.css']); ?>
<?php $this->addAttribute('js', ['js/flashmessages.js']); ?>

<h2><?=$title?></h2>
<table class="tbl_dienstplan">
    <thead>
    <tr>
        <?php foreach($calendarmonth as $day => $weekday) {
            switch($weekday) {
                case "05":
                    echo "<th class=\"fr\">";
                    break;
                case "06":
                case "07":
                    echo "<th class=\"we\">";
                    break;
                default:
                    echo "<th class=\"weekday\">";
            }
            echo $day;
        };
        ?>
        </th>
    </tr>
    </thead>
    <tbody>
        <tr>
            <?php
            foreach ($dienstplan as $day => $person) {
                // echo "<tr>\n<td class=\"day\">".html($day)."</td><td class=\"person\">".html($person)."</td></tr>\n";
                echo "<td class=\"person\">".html($person)."</td>\n";
            }
            ?>
        </tr>
    </tbody>
    <tfoot>
    <tr>
        <td colspan="<?=count($calendarmonth);?>" style="text-align: center;"> buy me a coffee </td>
    </tr>
    </tfoot>
</table>
