<?php $this->setLayout('layout.php'); ?>
<?php $this->addAttribute('css', ['css/skeleton.css', 'css/normalize.css', 'css/flashmessages.css', 'css/style.css', 'css/login.css']); ?>
<?php $this->addAttribute('js', ['js/flashmessages.js']); ?>

<h2><?=$title?></h2>

<section>
       <form action="login" method="POST">
           <fieldset id="logindata">
               <legend>Anmelden</legend>
               <ol>
                   <li>
                       <label for="login_username">Benutzername:</label>
                       <input type="text" id="login_username" name="username">
                   </li>
                   <li>
                       <label for="login_pw">Passwort:</label>
                       <input type="password" id="login_pw" name="password">
                   </li>
               </ol>
           </fieldset>
           <fieldset id="buttons">
               <input type="submit" id="submit" name="submit" value="Login">
           </fieldset>
    </form>
</section>
