<?php
require 'dienstplan.php';
$dienstplan = new dienstplan();
$dienstplan->generate();
$debug = $dienstplan->getdebug();
session_start();
if(isset($_SESSION['username'])) {
    $dienstplan->add_message('speichere dienstplan für '.$dienstplan->readable_month);
} else {
    session_destroy();
}

?>
<html lang="de">
<head>
    <?php include 'header.html';?>
</head>

<body>
<div class="container">
    <?php include 'navigation.php';?>
    <h1>Der Dienstplan für <?php echo $dienstplan->readable_month; ?></h1>

    <?php if($debug): ?>
        <section class="row">
            <aside><?php echo implode("<br>\n", $debug); ?></aside>
        </section>
    <?php endif; ?>

    <section class="row">
        <aside><?php echo $dienstplan->show_messages(); ?></aside>
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