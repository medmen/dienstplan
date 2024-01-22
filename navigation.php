<ul id="topnavigation" class="row">
    <li class="two columns"><a href="index.php">Dienstplan</a></li>
    <li class="two columns"><a href="urlaub.php">Urlaub</a></li>
    <li class="two columns"><a href="src/Worker/Wishes.php">Wünsche</a></li>
<?php if(isset($_SESSION['username'])): ?>
    <li class="two columns"><a href="login.php?logout=logout"><?php echo $_SESSION['username']; ?> (logout)</a></li>
    <li class="two columns"><a href="login.php?changepw=changepw">passwort ändern</a></li>
<?php else: ?>
    <li class="two columns"><a href="login.php">login</a></li>
<?php endif ?>
    <li class="two columns"><a href="doku.php">Dokumentation</a></li>
</ul>
