<?php
require 'dienstplan.php';
$dienstplan = new dienstplan();
$monat = new DateTime($dienstplan->target_year.'-'.$dienstplan->target_month);
setlocale(LC_TIME, 'de_DE.UTF-8');
$monat_formatiert = strftime("%B %Y", $monat->getTimestamp());
$dienstplan->generate();
?>
<html lang="de">
<?php include 'header.html';?>
<body>
<div class="container">
    <?php include 'navigation.html';?>
    <h1>Der Dienstplan für <?php echo $monat_formatiert; ?></h1>
    <section class="row">
        <aside><?php echo $dienstplan->message; ?></aside>
    </section>

    <section class="row">
    <article id="main" class="seven columns">
        <?php   echo $dienstplan->display('dienstplan'); ?>
    </article>
    <div class="three columns offset-by-one">
        <aside>
            <?php   echo $dienstplan->display('statistics'); ?>
        </aside>
        <aside id="debug">
            <?php   echo $dienstplan->display('debug'); ?>
        </aside>
    </div>
</section>
</div>
</body>
</html>