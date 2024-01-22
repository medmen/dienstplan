<ul id="topnavigation" class="row">
    <li class="two columns"><a href="/">Dienstplan</a></li>
    <li class="two columns"><a href="/urlaub">Urlaub</a></li>
    <li class="two columns"><a href="/wuensche">Wünsche</a></li>
    <?php if($user !== null) : ?>
        <li class="two columns"><a href="/logout"><?php echo $user; ?> (logout)</a></li>
        <li class="two columns"><a href="/users">passwort ändern</a></li>
    <?php else: ?>
        <li class="two columns"><a href="/login">login</a></li>
    <?php endif ?>
    <li class="two columns"><a href="/doku">Dokumentation</a></li>
</ul>
