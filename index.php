<?php
require 'dienstplan.php';
$dienstplan = new dienstplan();
$dienstplan->generate();
?>
<html lang="de">
<?php include 'header.html';?>
<body>
<div class="container">
    <?php include 'navigation.html';?>
    <h1>DER DIENSTPLAN</h1>

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