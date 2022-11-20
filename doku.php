<html lang="de">
<head>
    <?php include 'header.html';?>
    <script src="public/js/jquery-3.2.1.min.js"></script>
    <script src="public/js/jquery.toc.min.js"></script>
</head>
<body>
<div class="container" id="container">
    <?php include 'navigation.php';?>
    <h1>Dokumentation</h1>

    <section class="row">
        <article id="main" class="seven columns">
            <h2>Arbeitsweise</h2>
            <p> Dienstplan ist ein dummes Programm -
                es besitzt keine höhere Logik zur Lösung multidimensionaler Probleme oder zur
                Verfahrensoptimierung. Dienstplan verwendet das einfachste bekannte wissenschaftliche
                Prinzip - Versuch und Irrtum.

                Entsprechend sollte der Quellcode auch für nicht-Profis recht einfach zu lesen, ändern oder korrigieren sein.

                <h3>Programmschritte</h3>
                <ol>
                <li>Zunächst erfolgt die Bestimmung des Zielmonats (Vorgabe ist der Folgemonat ausgehend vom aktuellen Datum </li>
                <li>Dann werden die Anzahl der Tage für den Zielmonat ermittelt</li>
                <li>Für jeden Tag des Zielmonats wird die Liste der Mitarbeiter randomisiert</li>
                <li>Nun wird geprüft, ob für diesen Tag explizite Dienstwünsche vorliegen - wenn mehrere Mitarbeiter an diesem Tag Dienst haben wollen entscheidet der Zufall. Der Plan wird besetzt und der nächste Tag ist dran.</li>
                <li>Ohne Dienstwünsche wird jetzt für jeden Mitarbeiter geprüft ob Ausschlußkriterien für diesen Tag vorliegen (Urlaub, Wunsch "kein Dienst", stattgehabter Dienst am Vortag, Mehr Dienste als der Durchschnitt).
                    Wenn Ausschlußkriterien vorliegen, wird der nächste Mitarbeiter der Liste geprüft, sonst der aktuelle Mitarbeiter als Diensthabender eingetragen.
                </li>
                <li>Bei diesem Prinzip kann es vorkommen, dass an einem Tag alle Mitarbeiter Ausschlußkriterien vorweisen und der Dienstplan nicht komplett ist.
                    Für diesen Fall existieren 3
                    <h4>Eskalationsstufen:</h4>
                    <ol>
                        <li>Der Dienstplan wird nochmal komplett neu erstellt,
                            aufgrund der Randomisierung ergeben sich irgendwann wahrscheinlich funktionierende Kombinationen. </li>
                        <li>
                            Ausschlußkriterien werden priorisiert und weniger wichtige Kriterien nun ignoriert (etwa die Obergrenze an Wochenenddiensten)
                        </li>
                        <li>
                            Eine Kombination aus beiden vorigen Stufen
                        </li>
                    </ol>
                </li>
                <li>
                    <h3>Statistiken</h3>
                    Statistiken für den aktuellen Dienstplan werden automatisch ermittelt und angezeigt.
                    Der Dienstplan für den Zielmonat wird angezeigt und auf Wunsch
                    <ol>
                        <li>verworfen und komplett neu erstellt</li>
                        <li>automatisch gespeichert</li>
                        <li>an alle Mitarbeiter versandt (email)</li>
                        <li>aggregierte Statistiken erstellt/aktualisiert</li>
                    </ol>
                </li>

                </ol>


            </p>

        </article>
        <div class="three columns offset-by-two">
            <aside>
                <h2>Übersicht</h2>
                <ul id="toc">
                    <script type="text/javascript">
                        $("#toc").toc({content: "div.container"});
                    </script>
                </ul>
            </aside>
        </div>
    </section>
    <section class="row">
            <article id="main" class="seven columns">
                <h2>Guten Tag</h2>
                <p>
                    Ich bin ein kleines, Programm zur Verbesserung der Welt.<br>
                    Getreu der Linux-Philosophie will ich aber nicht gleich die ganze Welt verbessern, sondern nur genau
                    eine Aufgabe lösen, aber diese mit Bravour und Eleganz.<br>
                    Meine Aufgabe: die Erstellung eines Dienstplans für eine Krankenhaus-Abteilung.
                    Genauer gesagt, eine Röntgenabteilung.
                </p>
                <h2> Wieso es mich gibt </h2>
                <p>
                    Diesen Job haben bisher Menschen gemacht und sich dabei meist Stunden um die Ohren geschlagen,
                    nur um hinterher dem Gemecker der lieben Kollegen ausgesetzt zu sein weil sich ein Schusselfehler
                    eingeschlichen hat oder nicht alle Wünsche berücksichtigt werden konnten.

                    Dabei ist das Problem deutlich komlizierter als es auf den ersten Blick aussieht:
                    Es sind jede Menge Regeln zu befolgen (jeder soll möglichst gleich wenig belastet werden,
                    die ungeliebten Wochenenden will auch keiner öfter im Dienst verbringen als nötig,
                    Wünsche wann kein Dienst stattfinden kann sind für den Familienfrieden essentiell
                    und wer im Urlaub ist kann keine Dienste machen).

                    Und da kommm ich ins Spiel: als Programm bin ich super darin, objektiv zu sein und strikt auf alle Regeln zu achten.
                    Ich erstelle und pflege auch nebenbei Statistiken und bin sicher nicht bockig wenn der
                    ganze Plan "mal eben schnell" neu erstellt werden muss weil sich ein paar Rahmenbedingungen geändert haben.
                    Geschwindigkeit ist für mich auch keine Hexerei - ich brauche nur Millisikunden wo Menschen sich Studen den Kopf zerbrechen.
                </p>
                <h2> Woher ich komme </h2>
                <p>
                    Erschaffen hat mich ein Hobbyprogrammierer und Mediziner,
                    daher hat meine Erschaffung auch so lange gedauert und ich
                    werde meinem Anspruch an Perfektion wohl nie vollständig gerecht werden können.
                </p>
                <h2> Wer kann mich nutzen</h2>
                <p>
                    Ich hoffe, dass ich für jeden nützlich bin, der ein Dienstplan-Problem gelöst haben möchte
                    und einen Computerbildschirm von der richtigen Seite ansehen kann.
                    Darum koste ich auch nichts und lasse mich einfach so herunterladen und benutzen.
                    Für die Juristen unter Euch: ich stehe unter der MIT-Lizenz
                </p>
                <h2> Was wenn ich ein Problem nicht lösen kann oder sogar neue Probleme mache?</h2>
                <p>
                    Dann wendet Euch doch hilfesuchend an meinen Programmierer. Der lässt sich manchmal per email an
                    galak[at]gmx.net erreichen.
                    Ich selber bin nur ein dummes, schlaues Programm ohne schlechtes Gewissen...
                </p>
            </article>
            <div class="three columns offset-by-two">
                <aside>
                    <h2>die Fakten</h2>
                    <ul>
                        <li>&copy; galak 2017</li>
                        <li>Lizenz: MIT</li>
                        <li>Programmiersprache: PHP</li>
                        <li>Updates/Download: github</li>
                        <li>Voraussetzung: Webserver</li>
                        <li>Kontakt: <a href="mailto:galak@gmx.net">galak@gmx.net</a></li>
                    </ul>
                </aside>
            </div>
        </section>
</div>
</body>
</html>
