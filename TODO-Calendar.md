Phase 1 – Anforderungen & Domain festziehen

Kalender-Scope definieren

Pro User? Pro Device? (z. B. „Bewässerungs-Plan“ pro Device)>> Pro user aber es muss mit allen User-Devices Kommuniziert werden können, damit ein Event zb die Bewässerung auslösen kann

Zeitzone: global (APP_TIMEZONE) vs. pro Benutzer >>> Globale Timezone

Event-Typen definieren

Simple Events (Start/Ende)

Ganztägige Events

Wiederholungen (optional: iCal RRULE)

Status: geplant / aktiv / erledigt / gecancelt

Farb-/Kategorie-System (z. B. „Maintenance“, „Measurement“, „Reminder“)

Multi-Tenancy Regeln festlegen

Events gehören immer zu user_id UND optional device_id

Team-Sharing: Zugriff auch über users_devices-Pivot (owner/editor/viewer)

Phase 2 – Datenbankdesign & Migrations

Migration calendars (optional, wenn mehrere Kalender pro Nutzer)

id, user_id (FK), name, color, is_default, created_at, updated_at

Migration events

id (PK)

user_id (FK → users)

device_id (FK → devices, nullable)

calendar_id (FK → calendars, nullable)

title (string)

description (text, nullable)

start_at (datetime)

end_at (datetime, nullable)

all_day (boolean)

status (enum/string: planned|active|done|canceled)

color (string, nullable)

meta (json, nullable – z. B. wiederkehrende Regeln, externe IDs)

created_at, updated_at

Optional: Migration event_participants (für Team-Sharing / Gäste)

event_id, user_id, role (owner|editor|viewer)

Migrations ausführen

php artisan migrate

Phase 3 – Eloquent-Modelle & Beziehungen

Model Event

$fillable: alle relevanten Felder

Beziehungen:

user() → belongsTo(User::class)

device() → belongsTo(Device::class)

calendar() → belongsTo(Calendar::class)

participants() → belongsToMany(User::class) (optional)

Accessors:

getDurationAttribute (Ende-Start)

getIsPastAttribute, getIsTodayAttribute, getIsFutureAttribute

Model Calendar (falls genutzt)

user() → belongsTo(User::class)

events() → hasMany(Event::class)

User-Modell erweitern

events() → hasMany(Event::class)

calendars() → hasMany(Calendar::class)

Device-Modell erweitern (falls Device-Kalender)

events() → hasMany(Event::class)

Phase 4 – Policies & Berechtigungen

EventPolicy erstellen

view(User $user, Event $event):

User = Owner oder Teilnehmer oder via users_devices auf Device berechtigt

create(User $user):

Nur eingeloggte User; ggf. Device-Bezug prüfen

update(User $user, Event $event)

delete(User $user, Event $event)

reorder(User $user, Event $event) (für Drag&Drop-Move)

Policy registrieren

In AuthServiceProvider / AppServiceProvider (je nach Setup)

Phase 5 – Routes & Controller-Schicht (REST + Ajax)

Web-Routes (routes/web.php)

GET /calendar → Monatsansicht / Dashboard-Kalender

GET /calendar/events → Event-Feed (JSON für Frontend)

API-ähnliche Routes for CRUD

POST /calendar/events → Event anlegen

PUT/PATCH /calendar/events/{event} → Event aktualisieren (inkl. Drag&Drop)

DELETE /calendar/events/{event} → Event löschen

EventController

index() → Events nach Zeitraum (start/end Query-Parameter) gefiltert zurückgeben

store() → neues Event (inkl. Validation)

update() → Titel/Zeiten/Status anpassen

destroy() → löschen

move() / resize() (optional separater Endpoint) → nur Zeiten ändern (für Drag&Drop)

Request-Klassen

StoreEventRequest

UpdateEventRequest

Validiere: Start < Ende, max. Länge, bekannte Statuswerte etc.

Phase 6 – Livewire/Volt-Komponenten & State-Management

Livewire Component: CalendarView

Props/State:

currentDate (Referenz-Monat)

viewMode (month|week|day)

selectedDeviceId / selectedCalendarId

events (Collection für den sichtbaren Zeitraum)

Methoden:

goToPrevious(), goToNext(), goToToday()

loadEventsForRange(start, end)

createEvent(...)

updateEvent(...)

moveEvent(eventId, newStart, newEnd) (für Drag&Drop)

Lifecycle:

mount() → Initialdatum setzen, erste Events laden

Livewire Component: EventForm (Modal)

Felder: title, description, start_at, end_at, all_day, calendar_id, device_id, color, status

Modes: Create / Edit

Emit-Events: eventSaved, eventDeleted, eventUpdated

Integration in Dashboard

Kalender-Component im bestehenden Layout (resources/views/components/layouts/app.blade.php) einbetten
Phase 7 – UI/UX-Design des Kalenders

Grundlayout

Monatsansicht als Grid (7 Spalten = Wochentage, 5–6 Reihen)

Kopfbereich mit:

Monatsname + Jahr

Buttons: <, heute, >

Switch für Ansicht: Monat/Woche/Tag

Sidebar:

Filter nach Kalender / Device

Legende der Farben / Kategorien

Darstellung der Events

Ein Event-„Badge“ in der Tageszelle:

Farbe = Kategorie / Status

Kurztext = Titel (ggf. gekürzt)

Tooltip/Popover bei Hover:

Voller Titel, Zeiten, Device, Beschreibung

Event-Interaktion

Klick auf leeren Day-Slot → EventForm „Create“

Klick auf Event-Badge → EventForm „Edit“

Delete-Button nur bei Berechtigung

UX-Details

Loading-States (Spinner/Placeholder beim Nachladen von Events)

Fehlermeldungen / Validierung direkt im Modal

Visuelles Feedback bei Drag&Drop (z. B. leichtes Ghosting/Highlight)

Phase 8 – Drag & Drop Implementierung

JavaScript-Grundlage wählen

Simple: HTML5 Drag&Drop + AlpineJS

Oder: externe Lib (z. B. interact.js / Sortable) – im Vite-Build einbinden

Drag-Quelle definieren

Event-Badge wird draggable=true

data-event-id, data-original-start, data-original-end

Drop-Ziele definieren

Jede Tageszelle erhält Drop-Handler

Berechne neue start_at/end_at basierend auf Ziel-Datum (und ggf. Uhrzeit bei Week/Day-View)

Ajax/Livewire-Call

Bei drop → JS ruft Livewire-Action moveEvent(eventId, newStart, newEnd)

Server:

Policy reorder prüfen

Zeiten aktualisieren, speichern

Client:

UI sofort updaten (optimistic) oder nach Response neu laden

Resize (optional)

Event-Badge bekommt „Grip“ unten

Ziehen verändert end_at

Analog zu moveEvent, jedoch mit resizeEvent

Phase 9 – Event-Management (CRUD-Flow)

Create

Button „+ Event“ oder Klick auf Tag → öffnet EventForm mit voreingestelltem Datum

Form sendet an store() / Livewire-save():

Validieren

Event::create([...])

UI Events neu laden

Read/List

EventController@index() gibt Events im Zeitraum start/end zurück

Nutzen für:

Monatsgrid

Liste „nächste Events“

Update

Edit-Modus im Modal

Update durch:

Form „Speichern“

Drag&Drop / Resize

Delete

Delete-Button im Modal

Confirm-Dialog

EventController@destroy() ruft Policy + delete()

Statuswechsel

Quick-Actions (z. B. Checkbox „Done“)

Optional: Kontextmenü auf Event-Badge

Phase 10 – Realtime & Integration mit vorhandener Infrastruktur

WebSockets (Laravel Reverb) nutzen

Broadcast-Event EventCreated, EventUpdated, EventDeleted

Livewire/JS Listener:

Bei Broadcast → Events neu laden oder gezielt aktualisieren

Verknüpfung mit bestehenden IoT-Funktionalitäten

Option in Event: „führt Command aus“ (z. B. Spray/Füllung planen)

Feld meta.command mit Device-Command

CRON/Queue-Job:

checkt fällige Events und erzeugt Einträge in commands-Tabelle

Phase 11 – Tests & QA

Feature-Tests

User kann eigene Events erstellen, sehen, updaten, löschen

User kann NICHT Events von fremden Usern bearbeiten

Team-Sharing: User mit viewer/editor Rollen hat korrekten Zugriff

Policy-Tests

EventPolicy in allen Zuständen (owner, shared, fremd)

JS/UX

Manuelles Durchtesten von:

Drag&Drop

Resize

Event-Form

Filter / Ansicht Wechsel
