# LOAN &middot; FH Aachen, Campus Jülich

Geräteausleihe und Raumbuchung für einen Hochschulfachbereich. Laravel 9, Blade,
Tailwind, MariaDB oder SQLite.

> [!WARNING]
> **Vor dem Deployment zu klären — Markenrecht.**
> Die Oberfläche verwendet aktuell **nicht** die Positionsmarke der FH Aachen,
> sondern einen Platzhalter aus dem Farbdreiklang (`x-fh-flag`), weil die
> offizielle Marke auf dunklem Grund schwarze Schrift auf Schwarz schreibt.
> Die Freigabe durch die Gestaltungsstelle steht noch aus. Details unten unter
> [Vor dem Deployment zu klären](#vor-dem-deployment-zu-klären).

Dieses Repository ist eine Bearbeitung von
[LOAN](https://github.com/dusanvin/LOAN) von Vincent Dusanek
(CC BY-NC-SA 4.0). Was gegenüber dem Original anders ist, steht unter
[Änderungen gegenüber dem Original](#änderungen-gegenüber-dem-original).

---

## Lokal starten

Vorausgesetzt: PHP 8.1+ mit den Erweiterungen `pdo_sqlite`, `mbstring`, `gd`, `zip`,
dazu Composer und Node 18+.

```bash
composer install
npm install

cp .env.example .env          # Windows: copy .env.example .env
php artisan key:generate

# SQLite reicht zum Ausprobieren. In der .env setzen:
#   DB_CONNECTION=sqlite
#   DB_DATABASE=<absoluter Pfad zu database/database.sqlite>
# und die MySQL-Zeilen auskommentieren.
php -r "touch('database/database.sqlite');"

php artisan migrate --seed    # legt das Schema an und den ersten Admin
php artisan storage:link      # damit hochgeladene Gerätebilder ausgeliefert werden

npm run build                 # Tailwind bauen - ohne diesen Schritt hat die App kein CSS
php artisan serve             # http://127.0.0.1:8000
```

Erster Zugang aus dem Seeder: `admin@test.com` / `geheimespasswort`.
**Vor dem ersten produktiven Einsatz ändern.**

Beim Entwickeln statt `npm run build` lieber `npm run dev` in einem zweiten
Terminal — dann lädt die Seite bei jeder Änderung neu.

### Mit Docker

Noch nicht lauffähig: `compose.yml` stammt aus dem Original und verweist auf
einen Dienst, den es nicht gibt (`depends_on: app` statt `loan`), außerdem
landet xdebug im Image. Wird mit dem Deployment-Setup überarbeitet; dann folgt
hier auch ein Abschnitt zum Betrieb.

---

## Konfiguration

Wer die Instanz betreibt, steht in `config/loan.php` und lässt sich über die
`.env` überschreiben:

```
LOAN_UNIT="Fachbereich 09"
LOAN_UNIT_SHORT="FB09"
LOAN_CAMPUS="Campus Jülich"
```

Die Werte erscheinen auf der Anmeldeseite, unten in der Navigationsleiste und im
Footer. **Leer lassen blendet die jeweilige Angabe aus** — so läuft dieselbe
Installation ohne Codeänderung auch für mehrere Fachbereiche oder hochschulweit.
Die Vorgaben passen zum ersten Einsatzort.

---

## Rollen und Rechte

Drei Rollen, wobei die höhere jeweils alles darf, was die niedrigere darf:

| Rolle | Darf |
|---|---|
| `user` (Standard) | Bestand sehen, selbst ausleihen (nicht in fremde Vormerkungen hinein), selbst vormerken – auch verliehene Geräte –, **nur eigene** Vorgänge verwalten |
| `moderation` | zusätzlich Geräte, Räume und Kategorien pflegen, an beliebige Personen ausgeben (auch über fremde Vormerkungen hinweg, mit Hinweis), fremde Vorgänge bearbeiten, Vormerkungen genehmigen, Protokoll einsehen |
| `administration` | zusätzlich Nutzerverwaltung |

Neue Konten bekommen `user`. Hochstufen geht nur über die Nutzerverwaltung.

Durchgesetzt wird das an zwei Stellen: `role`-Middleware an den Routengruppen
(grobe Trennung) und Policies pro Objekt (Eigentümerprüfung). Die Blade-Templates
blenden mit `@can('manage-inventory')` nur die Knöpfe aus — verlassen darf man
sich darauf nicht, die Prüfung sitzt im Controller.

`tests/Feature/AuthorizationTest.php` deckt jede Regel mit einem Test ab.

---

## Datenmodell

```
categories ──< device_models ──< devices ──< loans
                                    │           │
                                    └──< reservations >── users
rooms ─────────────────────────────────< reservations
```

- **`device_models`** ist der Gerätetyp (*HTC Vive Pro 2*), **`devices`** das
  einzelne Exemplar mit Inventarnummer. Erst dadurch lassen sich fünf baugleiche
  Messgeräte einzeln inventarisieren und gemeinsam finden.
- **`loans`** trägt jeden Ausleihvorgang. Eine offene Ausleihe ist eine Zeile mit
  `returned_at IS NULL`; der Status eines Geräts ergibt sich daraus, statt daneben
  geführt zu werden. Überfällig heißt `due_at < now() AND returned_at IS NULL`.
- **`reservations`** ist polymorph (`reservable_type` / `reservable_id`) und deckt
  Räume und Geräte mit einem Zeitstempelpaar ab. Die Überschneidungsprüfung gibt es
  deshalb nur einmal, in `App\Http\Controllers\Concerns\HandlesReservations`.

Das Modell `Device` hat Accessoren (`status`, `borrower_name`, `group`,
`loan_end_date` …), die die Felder des alten Schemas weiterbedienen. Bestehende
Views laufen dadurch unverändert; **neuer Code greift direkt auf `deviceModel`
und `openLoan` zu.**

---

## Design

Corporate Design der FH Aachen. **Maßgeblich ist das Design System
„FH Aachen Design System"** (Claude-Artifact, `project/tokens.json`) — die Werte
in `tailwind.config.js` und `resources/css/app.css` spiegeln es. Weicht hier
etwas ab, gilt das Design System.

Die Tailwind-Konfiguration hat zwei Ebenen: `fh-*` sind die Tokens des Design
Systems 1:1 und gelten für neuen Code. `gray-*` und `yellow-*` sind eine Brücke
für das Altmarkup, das durchgehend `bg-gray-600` und `text-yellow-700` benutzt —
die Namen bleiben, die Werte sind FH-Werte. Diese Ebene verschwindet, sobald die
letzte Alt-View umgestellt ist.

- **Farbdreiklang** Mint `#00B2A9` (Pantone 326 C), Schwarz, Weiß.
  Mint ist Akzent, kein Grundton — Faustregel aus dem CD: höchstens 10–15 % einer Fläche.
- **Kontrast.** Reines Mint trägt weder Fließtext auf Weiß (2,1:1) noch Weiß als
  Schrift (2,5:1). Die Tailwind-Stufe `yellow-400` ist deshalb reines Mint und nur
  für Flächen und Marker; `yellow-600` (`#007A74`) und `yellow-700` (`#00524E`)
  sind die abgedunkelten Stufen für Text. Schrift auf Mint ist immer Schwarz.
- **Schrift** Hausschrift ist **FF Clan** (Łukasz Dziedzic), lizenzpflichtig und
  nicht mitgeliefert. Im Einsatz ist **Lato** — vom selben Gestalter und der
  nächste freie Ersatz —, geladen über [Bunny Fonts](https://fonts.bunny.net)
  statt Google Fonts wegen der Datenübertragung. Fallback Verdana, wie das CD es
  für Office und Online vorsieht. Liegt Clan als Webfont vor, greift der erste
  Eintrag der `sans`-Familie ohne weitere Änderung.
- **Typo** Modulare Skala ~1,25: `text-display` 60 → `text-h1` 38 → `text-h2` 28
  → `text-h3` 21 → `text-body` 16/18 → `text-small` 14. Linksbündiger
  Flattersatz, nie Blocksatz.
- **Marke** Die Oberfläche zeigt derzeit `x-fh-flag`: den Farbdreiklang als
  Dreiteilung in den Proportionen der Positionsmarke (jedes Feld 1:3). Eines der
  drei Felder ist immer der Untergrund — auf Schwarz tragen Mint und Weiß, auf
  Weiß tragen Mint und Schwarz. Dieselbe Datei funktioniert deshalb auf beiden
  Gründen. **Das ist nicht die Positionsmarke der FH Aachen.**
  Die echte Marke liegt als `public/img/fh-positionsmarke.svg` bereit (Positiv-Version,
  auf dunklem Grund schreibt sie schwarze Schrift auf Schwarz und zerfällt).
  Nicht verzerren, einfärben, drehen oder freistellen.
- **Status** `fh-error` `#B3261E`, `fh-success` `#1E7A45`, `fh-warning` `#9A6B00`.
  Das reine CD kennt nur Mint, Schwarz und Graustufen; diese vier sind im Design
  System als digitale Ergänzung festgelegt und von dort übernommen.
- **Form** Kleine Radien (2 / 4 / 8 px), 1px-Konturen für Struktur, Schwarz für
  Betonung, eine 3px-Mint-Regel als Akzent. Schatten leicht und kühl, nie schwer.
  Keine Verläufe.

Die Namen `gray` und `yellow` in der Tailwind-Konfiguration sind beibehalten,
damit bestehendes Markup ohne Sammelumbenennung greift; die Werte dahinter sind
FH-Werte. Die Graustufen haben einen harten Sprung zwischen `500` und `600` —
das CD kennt Weiß und Schwarz als Pole, keinen Mittelgrau-Brei.

> Die Verwendung des Logos unterliegt der Freigabe des Corporate-Design-Beauftragten
> der FH Aachen. Beratung: Gestaltungsstelle der Stabsstelle Presse, Öffentlichkeits-
> arbeit und Marketing.

---

## Vor dem Deployment zu klären

Alles hier braucht die Freigabe der Gestaltungsstelle der FH Aachen
(Stabsstelle Presse, Öffentlichkeitsarbeit und Marketing). Bis dahin ist der
Stand ein Entwurf und **nicht für den Produktivbetrieb freigegeben.**

| Punkt | Stand | Was zu klären ist |
|---|---|---|
| **Marke** | Platzhalter `x-fh-flag` statt Positionsmarke | Ist die Ableitung aus dem Farbdreiklang zulässig? Wenn nein: **offizielle** Negativ-Version für dunkle Gründe anfordern und den Platzhalter ersetzen. Betrifft `resources/views/components/fh-flag.blade.php` und alles, was es einbindet. Achtung: die im Design System liegende Negativ-Version ist selbst abgeleitet und unbrauchbar (nur der Mint-Block, Rest transparent) — und eine selbst abgeleitete Negativversion ist genau das Umfärben, das das CD untersagt. |
| **Logo-Datei** | `public/img/fh-positionsmarke.svg`, aus einer Download-Quelle | Deren Mint ist `#00B5AD`, das CD nennt `#00B2A9`. Vermutlich nicht die offizielle Datei — über den internen CD-Bereich beziehen. |
| **Statusfarben** | `#B3261E` / `#1E7A45` / `#9A6B00` | ~~Offen~~ — im FH-Design-System als digitale Ergänzung festgelegt, von dort übernommen. Bei der Freigabe nur noch erwähnen, nicht verhandeln. |
| **Hausschrift** | Lato als freier Ersatz für FF Clan | Liegt Clan web-lizenziert vor (woff2)? Dann in `tokens/fonts.css`-Manier einbinden; die `sans`-Familie hat `FH Clan` schon an erster Stelle. |
| **Digitaler Mint-Wert** | `#00B2A9` | Bestätigt als Web-Wert. Falls die offizielle digitale CD abweicht, nur `tailwind.config.js` und `resources/css/app.css` anpassen — alles andere zieht nach. |

## Änderungen gegenüber dem Original

### Sicherheit

Im Original lagen Raum- und Geräteverwaltung in der reinen `auth`-Gruppe: **jedes
angemeldete Konto** konnte Räume und Geräte anlegen und löschen sowie fremde
Raumbuchungen stornieren und umschreiben (`cancelReservation` und
`updateReservation` prüften keinen Eigentümer). Behoben durch:

- Rollenmodell `user` / `moderation` / `administration` mit Hierarchie
- Policies für Device, DeviceModel, Loan, Reservation, Room, Category, User
- neu geschnittene Routengruppen, `authorize()` in allen Controllern
- `$room->update($request->all())` durch geprüfte Eingaben ersetzt
- Eigentümerprüfung für Ausleihen und Vormerkungen
- letztes Administrationskonto lässt sich weder löschen noch herabstufen
- Anmeldung und Sprachwechsel mit Rate-Limit

### Fachlich

- Ausleihe als eigene Entität (`loans`) statt vier Spalten am Gerät → Historie,
  Fälligkeit und Überfälligkeit sind abfragbar
- Gerätetyp und Exemplar getrennt, Anlegen mehrerer Exemplare mit
  durchnummerierten Inventarnummern
- Raum- und Gerätevormerkungen auf einer Tabelle, eine gemeinsame
  Überschneidungsprüfung — **Raumbuchungen hatten vorher gar keine**
- Vormerkungen mit Genehmigungs-Workflow; eine passende eigene Vormerkung wird
  bei der Abholung automatisch eingelöst
- Ausleihe und Vormerkung greifen ineinander: eine Selbstausleihe scheitert an
  offenen oder genehmigten Vormerkungen anderer, die Moderation darf sie
  übergehen; ein verliehenes Gerät lässt sich für die Zeit danach vormerken
- Buchungen laufen in einer Transaktion mit Zeilensperre — keine
  Doppelbuchung bei gleichzeitigen Anfragen (MySQL/MariaDB/PostgreSQL)
- Zeitzone `Europe/Berlin` statt UTC — Eingaben sind Ortszeit
- Wortzählung im Verwendungszweck über `preg_split` statt `str_word_count`
  (letzteres zählt mit Umlauten falsch)
- Route `/log` zeigte auf eine Methode, die es nicht gab (500) — jetzt ein
  echtes Ausleihprotokoll

### Technisch

- `tailwind.config.js` hatte `content: []`, Tailwind baute also nichts; die App
  hing an einer 4 MB großen eingecheckten `public/css/app.css`. Jetzt baut Vite
  das CSS, beide Layouts nutzen `@vite`.
- `phpunit.xml` hatte die SQLite-Testdatenbank auskommentiert — Tests liefen
  gegen die Entwicklungsdatenbank. Jetzt `:memory:`.
- Feature-Tests für Autorisierung und Fachlogik
- Die Icon-Pakete `blade-icons`, `blade-heroicons`, `blade-remix-icon` und
  `blade-ui-kit` entfernt. Keine View hat sie benutzt, aber sie haben bei
  **jeder** Anfrage ihre Ordner mit Tausenden SVG-Dateien durchsucht — unter
  Windows rund 3 Sekunden pro Klick. Alle Symbole sind direkt als SVG im
  Markup eingebaut. Wer ein Icon-Paket wieder aufnimmt: in der Produktion
  unbedingt `php artisan icons:cache`.
- Navigation: „Geräte“ war auch auf „Gebuchte Geräte“ aktiv markiert, weil
  das Muster `devices.*` die Übersichtsseite mit einschloss.
- Die Lizenzangabe im Footer sagte MIT, die `LICENSE` sagt CC BY-NC-SA 4.0 —
  korrigiert.
- Tokens gegen das Design System „FH Aachen Design System" abgeglichen: Mint-Rampe,
  Graustufen, Statusfarben, Radien, Schatten, Fokusring, Typoskala und die
  Hover-Regel der primären Schaltfläche (Mint → mint-700, Schrift auf Weiß).

### Noch offen

Vor einem öffentlichen Betrieb:

- **Impressum und Datenschutzerklärung** stammen noch vom ursprünglichen
  Autor (sein Name, seine Kontaktadresse, sein Hoster). Die Datenschutzerklärung
  behauptet außerdem, es würden keine personenbezogenen Daten verarbeitet —
  falsch, die App speichert Namen, E-Mail-Adressen und Ausleihen. Beides durch
  Angaben der FH ersetzen, die Datenschutzerklärung mit der/dem
  Datenschutzbeauftragten abstimmen
- Das Seeder-Konto `admin@test.com` darf in keiner erreichbaren Instanz existieren
- Die Schrift kommt von Bunny Fonts, jeder Aufruf überträgt die IP-Adresse an
  einen Dritten — selbst hosten (Lato steht unter der OFL) oder in die
  Datenschutzerklärung aufnehmen
- `/product` und `composer.json` nennen noch die MIT-Lizenz
- `CODEOWNERS` verweist auf den ursprünglichen Autor
- `compose.yml` ist nicht lauffähig (siehe [Mit Docker](#mit-docker))

Technisch:

- `public/css/app.css` und `public/js/app.js` sind Altlasten des alten
  Build-Wegs und können weg, sobald der Vite-Build steht
- `webpack.mix.js` und `laravel-mix` werden nicht mehr gebraucht
- Laravel 9 ist seit Februar 2024 ohne Security-Support; ein Upgrade steht an
- Die Formulare für Räume kennen das Feld `capacity` noch nicht
- Der Rest der Views ist noch im alten Stil; Tokens und Rahmen stehen, die
  Umstellung der einzelnen Seiten läuft
- Die Marke ist ein Platzhalter — siehe [Vor dem Deployment zu klären](#vor-dem-deployment-zu-klären)

---

## Tests

```bash
php artisan test
```

Läuft gegen eine SQLite-Datenbank im Arbeitsspeicher und fasst die
Entwicklungsdaten nicht an. Die Seiten rendern mit den gebauten Assets, also
vorher einmal `npm run build`.

`composer.lock` lässt sich auf PHP 8.4 nicht installieren (nette/utils,
nette/schema verlangen < 8.4). Lokal mit PHP 8.2 kein Thema; für
einen neueren Server erledigt sich das mit dem Laravel-Upgrade.

---

## Lizenz

Creative Commons Attribution–NonCommercial–ShareAlike 4.0 International
(CC BY-NC-SA 4.0), wie das Original.

Erlaubt sind Nutzung, Änderung und Weitergabe zu **nichtkommerziellen** Zwecken,
solange die Quelle genannt und abgeleitete Arbeiten unter derselben Lizenz
weitergegeben werden. Der Einsatz in der Hochschullehre ist davon gedeckt.

Die Lizenz erstreckt sich **nicht** auf Marken. Logo und Corporate Design der
FH Aachen sind nicht Teil dieser Lizenz und unterliegen den Vorgaben der
Hochschule.

SPDX-License-Identifier: `CC-BY-NC-SA-4.0`
