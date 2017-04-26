<?php
/**
 * Created by PhpStorm.
 * User: galak
 * Date: 13.04.17
 * Time: 22:27
 */
?>
<html lang="de">
<?php include 'header.html';?>
<body>
<div class="container">
    <?php include 'navigation.html';?>
    <h1>Dokumentation</h1>

    <section class="row">
        <article id="main" class="seven columns">
            <h2>Arbeitsweise</h2>
            <p> Dienstplan ist ein dummes Programm -
                es besitzt keine höhere Logik zur Lösung multidimensionaler Probleme oder zur
                Verfahrensoptimierung. Dienstplan verwendet das einfachste bekannte wissenschaftliche
                Prinzip - Versuch und Irrtum.

                Entsprechend sollte der Quellcode auch für nicht-Profis recht einfach zu lesen, ändern oder korrigieren sein.

                <ol>
                    <li>Zunächst erfolgt die Bestimmung des Zielmonats (Vorgabe ist der Folgemonat ausgehend vom aktuellen Datum </li>
                <li>Dann werden die Anzahl der Tage für den Zielmonat ermittelt</li>
                <li>Für jeden Tag des Zielmonats wird die Liste der Mitarbeiter randomisiert</li>
                <li>Nun wird geprüft, ob für diesen Tag explizite Dienstwünsche vorliegen - wenn mehrere Mitarbeiter an diesem Tag Dienst haben wollen entscheidet der Zufall. Der Plan wird besetzt und der nächste Tag ist dran.</li>
                <li>Ohne Dienstwünsche wird jetzt für jeden Mitarbeiter geprüft ob Ausschlußkriterien für diesen Tag vorliegen (Urlaub, Wunsch "kein Dienst", stattgehabter Dienst am Vortag, Mehr Dienste als der Durchschnitt).
                    Wenn Ausschlußkriterien vorliegen, wird der nächste Mitarbeiter der Liste geprüft, sonst der aktuelle Mitarbeiter als Diensthabender eingetragen.
                </li>
                <li>Bei diesem Prinzip kann es vorkommen, dass an einem Tag alle Mitarbeiter Ausschlußkriterien vorweisen und der Dienstplan nicht komplett ist.
                    Für diesen Fall existieren 3 Eskalationsstufen:
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
        <div class="three columns offset-by-one">
            there is a sidebar
            <aside>
                feel free to add a section
            </aside>
            <aside id="debug">
                adding another section is just as easy
            </aside>
        </div>
    </section>
</div>
</body>
</html>