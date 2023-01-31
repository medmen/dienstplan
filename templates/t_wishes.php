<?php $this->setLayout('layout.php'); ?>
<?php $this->addAttribute('css', ['css/skeleton.css', 'css/normalize.css', 'css/flashmessages.css', 'css/style.css']); ?>
<?php $this->addAttribute('js', ['js/flashmessages.js']); ?>

<h2>hello <?=$title?></h2>
<table class="tbl_wishes">
    <thead>
    <tr>
        <th classs="person">Person</th>
        <?php for($i = 1; $i <= $days_in_month;  $i++) {
            echo "<th class=\"day\">$i</th>";
        };
        ?>
    </tr>
    </thead>
    <tfoot>
        <tr>
            <td colspan="<?=intval($days_in_month) + 1;?>">Legende: D = Dienstwunsch, F = Kein Dienst</td>
        </tr>
    </tfoot>
    <tbody>
    <?php
    foreach ($wishes as $name => $wish_arr) {
        echo "<tr>\n<td class=\"name\">".html($name)."</td>\n";
        for($i = 1; $i <= $days_in_month;  $i++) {
            if(array_key_exists($i, $wish_arr)) {
                echo "<td class='wish_".$wish_arr[$i]."'>$wish_arr[$i]</td>\n";
            }
        };
        echo "</tr>\n";
    }
    ?>
    </tbody>
</table>
