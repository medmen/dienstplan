<?php $this->setLayout('layout.php'); ?>
<?php $this->addAttribute('css', ['css/normalize.css', 'css/skeleton.css', 'css/flashmessages.css', 'css/style.css', 'css/wishes.css']); ?>
<?php $this->addAttribute('js', ['js/flashmessages.js','js/wishes_helper.js' ]); ?>

<h2><?=$title?></h2>
<form method="post" action="wuenschespeichern">
<input type="hidden" name="target_month" value="<?=$target_month?>">
<table class="tbl_wishes" id="tbl_wishes">
    <thead>
    <tr>
        <th classs="person">Person</th>
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
                echo "<td class='wish_".$wish_arr[$i]."'>
                        <select name='".$name."[]'>
                            <option value>&nbsp;</option>
                            <option ";
                                if ($wish_arr[$i] == 'D') {echo ' selected ';}
                                echo 'value="'.$i.'_D">D</option>';
                                echo "<option ";
                                if ($wish_arr[$i] == 'F') {echo ' selected ';}
                                echo 'value="'.$i.'_F">F</option>';
                        echo "</select>
                       </td>\n";
            } else {
                echo "<td class='empty'><select name='".$name."[]'>
                        <option selected value>&nbsp;</option>
                        <option value=".$i."_D>D</option>
                        <option value=".$i."_F>F</option>
                        </select></td>\n";
            }
        };
        echo "</tr>\n";
    }
    ?>
    </tbody>
</table>
    <input type="submit" name="submit_wishes" value="Änderungen speichern" onsubmit="disableEmptyInputs(this)">
</form>
