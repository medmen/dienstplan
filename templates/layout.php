<!DOCTYPE html>
<html lang = "de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= html($title ?? 'Slim Tutorial') ?></title>
    <base href="<?= $basePath ?>/"/>
    <?= $this->fetch('css.php', ['assets' => $css ?? []]) ?>
    <?= $this->fetch('js.php', ['assets' => $js ?? []]) ?>
</head>
<body>
    <div class="container">
    <?= $this->fetch('navbar.php') ?>
    <?= $this->fetch('flashmessages.php') ?>
    <?= $content ?>
    <?= $this->fetch('footer.php', ['year' => date('Y')]) ?>
    </div>
</body>
</html>
