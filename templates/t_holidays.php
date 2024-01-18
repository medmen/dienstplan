<?php $this->setLayout('layout.php'); ?>
<?php $this->addAttribute('css', ['css/normalize.css', 'css/skeleton.css', 'css/flashmessages.css', 'css/style.css', 'css/wishes.css']); ?>
<?php $this->addAttribute('js', ['js/flashmessages.js','js/wishes_helper.js' ]); ?>

<h2><?=$title?></h2>
<form method="post" action="urlaubspeichern">
<input type="hidden" name="target_month" value="<?=$target_month?>">
<table class="tbl_holidays" id="tbl_holidays">
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
            <td colspan="<?=intval($days_in_month) + 1;?>">Legende: U = Urlaub</td>
        </tr>
    </tfoot>
    <tbody>

    <?php
    foreach ($holidays as $name => $holiday_arr) {
        echo "<tr>\n<td class=\"name\">".html($name)."</td>\n";
        for($i = 1; $i <= $days_in_month;  $i++) {
            if(array_key_exists($i, $holiday_arr)) {
                echo "<td class='holiday_".$holiday_arr[$i]."'>
                        <select name='".$name."[]'>
                            <option value>&nbsp;</option>
                            <option ";
                                if ($holiday_arr[$i] == 'U') {echo ' selected ';}
                                echo 'value="'.$i.'_U">U</option>';
                        echo "</select>
                       </td>\n";
            } else {
                echo "<td class='empty'><select name='".$name."[]'>
                        <option selected value>&nbsp;</option>
                        <option value=".$i."_U>U</option>
                        </select></td>\n";
            }
        };
        echo "</tr>\n";
    }
    ?>
    </tbody>
</table>
    <input type="submit" name="submit_holidays" value="Änderungen speichern" onsubmit="disableEmptyInputs(this)">
</form>
