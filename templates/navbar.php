<ul id="topnavigation" class="row">
    <li class="two columns"><a href="/">Dienstplan</a></li>
    <li class="two columns"><a href="/urlaub">Urlaub</a></li>
    <li class="two columns"><a href="/wuensche">Wünsche</a></li>
    <?php if(isset($_SESSION['username'])): ?>
        <li class="two columns"><a href="/session/logout"><?php echo $_SESSION['username']; ?> (logout)</a></li>
        <li class="two columns"><a href="/session/changepw">passwort ändern</a></li>
    <?php else: ?>
        <li class="two columns"><a href="/session/login">login</a></li>
    <?php endif ?>
    <li class="two columns"><a href="/doku">Dokumentation</a></li>
</ul>
