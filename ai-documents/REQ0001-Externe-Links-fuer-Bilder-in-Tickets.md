- In der MySQL-Datenbank sollen Tickets eine neue Spalte "SecretString" erhalten. Diese Zeichenkette wird nirgends in der UI angezeigt oder bearbeitet.
- Der SecretString ist eine 50 Zeichen lange zufällige Zeichenkette aus Zahlen und großen und kleinen ASCII-Buchstaben
- Für alle bestehenden Tickets soll der Code nachträglich erstellt werden.

- Bitte erstelle unter public_php_app einen neuen Unterordner "imageview".
- Dort soll es einen Einstiegspunkt index.php geben, der unter Angabe des SecretStrings als Code alle Bilder in einer einfachen Galerie-Ansicht anzeigt, wo die Bilder zu einem Ticket in kleinen Versionen angezeigt werden und dann kann man durch Klick das volle Bild öffnen.
- Außer SecretCode gibt es keine weiteren Sicherheitsmechanismen in dieser "Nur-Ansicht"-Seite


- In der Datei php\ticket_email.php wird der Bilder-Link eingeblendet und kann angeklickt werden.

Ziel der Funktion: 
Die interne Anwendung stellt einen Link zur Verfügung, den wir bei unserer E-Mail-Kommunikation an die Genossenschaft nutzen können, um hochauflösende Bilder zu übermitteln ohne E-Mails zu überfrachten.

Achtung!
Bitte zerstöre oder verändere keine anderen bestehenden Funktionen!
Bitte bearbeite nicht die saus_news.php, die hat mit der Funktion nichts zu tun. Genauso wie die meisten anderen Seiten.

Achtung!
Die Ordner, in denen die Anwendungen "php" und "public_php_app" sich auf den Webservern befinden, heißen dort ggf. anders. Du solltest nach Möglichkeit auf Querverweise verzichten. Wenn es nicht anders möglich ist, dann mache den Verweis konfigurierbar.

Wenn du Konfigurationsdateien im Code erstellst, dann bitte als config.example.php, so dass die echten config.php's dann nicht durch den neuen Code überschrieben werden. Das gilt auch für paths_config.php's. VERSTANDEN?