# nextcloudTaskToMap

Display a map with all the locations fetched from the nextcloud tasks DB (ideal for vacations wishlists !)

![Alt text](/imgs/nextcloud.png?raw=true "Tasks in Nextcloud")

![Alt text](/imgs/page.png?raw=true "View of the final page")

## Format

There is no restriction for the text, you can add multiple lines, links. This data will be displayed in the info window.
In order to add the gps position of the new point, you need to following this convention `GPS:lat, lon`. It must be on a new line.

```
Nice waterfall in a lava field
Wiki: https://en.wikipedia.org/wiki/Hj%C3%A1lparfoss
GPS:64.116085, -19.851270
```

## Installation

You need to update few parameters in the file `connect.php`

- SQL server
- Nextcloud database (if you don't use the default one)
- SQL user
- SQL password
- Calendar ID
- API key of Google Maps

You can find the ID of the calendar in the table oc_calendars.

![Alt text](/imgs/table.png?raw=true "Table oc_calendar")
