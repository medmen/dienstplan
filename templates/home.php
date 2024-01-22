<?php $this->setLayout('layout.php'); ?>
<?php $this->addAttribute('css', ['css/skeleton.css', 'css/normalize.css', 'css/flashmessages.css', 'css/style.css']); ?>
<?php $this->addAttribute('js', ['js/flashmessages.js']); ?>

<h2>hello <?=$title?></h2>
<table class="tbl_dienstplan">
    <tbody>
<?php
foreach ($dienstplan as $day => $person) {
    echo "<tr>\n<td class=\"day\">".html($day)."</td><td class=\"person\">".html($person)."</td></tr>\n";
}
?>
    </tbody>
</table>
