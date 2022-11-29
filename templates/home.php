<?php $this->setLayout('layout.php'); ?>
<?php $this->addAttribute('css', ['css/skeleton.css', 'css/normalize.css', 'css/style.css']); ?>
<?php // $this->addAttribute('js', ['js/.js']); ?>

<h2>hello <?=$title?></h2>
<table>
    <tbody>
<?php
foreach ($dienstplan as $day => $person) {
    echo "<tr>\n<td>".html($day)."</td><td>".html($person)."</td></tr>\n";
}
?>
    </tbody>
</table>
