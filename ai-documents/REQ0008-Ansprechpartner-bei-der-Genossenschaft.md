# Ansprechpartner bei der Genossenschaft

## Kurze Zusammenfassung

Bei der Genossenschaft haben wir bestimmte Ansprechpartner. Es wäre schön, wenn wir eine 1:m Beziehung zwischen 
Tickets und Ansprechpartnern bei der Genossenschaft abbilden könnten. 

## Aktueller Zustand

- Tickets enthalten keine Hinweise zu den Ansprechpartnern

## Gewünschter zukünftiger Zustand

- Wir benötigen im internen Portal eine Ansprechpartner-Liste, mit einer Bearbeiten-Funktion.
- Es soll in den Ticket-Details möglich sein, Ansprechpartner der Genossenschaft zu verknüpfen. Außerdem werden natürlich die bereits verknüpften Ansprechpartner angezeigt.

Ansprechpartner haben:
- Einen Namen
- Ein optionales Feld für die E-Mail-Adresse
- Ein optionales Feld für die Haupttelefonnummer
- Ein Notizfeld, in dem weitere Kontaktinformationen eingetragen werden können (unstrukturiert, z.B. Anschrift, Fax ...)
- Ein Notizfeld, in dem die Zuständigkeiten des Ansprechpartners angegeben werden
- Ansprechpartner sollen deaktiviert werden können, wodurch die Verknüpfungen bestehen bleiben. Allerdings werden deaktivierte Ansprechpartner grau angezeigt. 
- Deaktivierte Ansprechpartner sollen bei der Auswahl von weiteren Ansprechpartnern für ein Ticket nicht angezeigt werden.
- Ausgewählte und bereits verknüpfte Ansprechpartner werden aber zu dem Ticket weiter angezeigt, so dass wir auch historisch die Ansprechpartner sehen. 
- In der Liste der gewählten, verknüpften Ansprechpartner sollen die Namen, Email-Adresse und Haupttelefonnummer aufgelistet werden. Es soll ein "Info" Icon geben. Wenn man da klickt wird einem die Zuständigkeitsinformation angezeigt.

- Wenn ein Ansprechpartner verknüpft wird, so soll ein Snapshot seiner Daten als Kommentar in die Tickethistorie eingefügt werden "Ansprechpartner X wurde hinzugefügt (Telefon: X, Email: X)".
- Wenn ein Ansprechpartner ent-knüpft wird, so soll ein Snapshot seiner Daten als Kommentar in die Tickethistorie eingefügt werden "Ansprechpartner X wurde entfernt (Telefon: X, Email: X)".
- Die Kommentare werden jeweils mit den Daten des aktuell angemeldeten Benutzers gezeigt.

## Implementationshinweise

- Das interne Portal ist die Anwendung in diesem Verzeichnis: ./php
- php\ticket_view.php ist die Detail-Anzeige der Tickets
- Bitte füge im Header einen Menüpunkt hinzu, damit ich auch auf die Seite Ansprechpartner komme.
- Es soll keine Löschkaskade in der DB geben, Ansprechpartner und Tickets sind nur "locker" gebunden. 2 Sets an Daten die verknüpft werden oder nicht.
- Ansprechpartner können nicht gelöscht werden, nur deaktiviert