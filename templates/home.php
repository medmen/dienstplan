<?php $this->setLayout('layout.php'); ?>
<?php $this->addAttribute('css', ['css/skeleton.css', 'css/normalize.css', 'css/style.css']); ?>
<?php // $this->addAttribute('js', ['js/.js']); ?>

<h2>hello <?=$title?></h2>
<table>
    <tbody>
<?php
$arr_persons = unserialize($persons);
foreach ($arr_persons as $nam => $arr_vals) {
    echo "<tr>\n";
    if (is_array($arr_vals) and array_key_exists('fullname', $arr_vals)) {
        echo "<td>".html($arr_vals['fullname'])."</td>";
    } else {
        echo "<td>".html($arr_vals)."</td>";
    }
    echo "</tr>\n";
}
?>
    </tbody>
</table>
