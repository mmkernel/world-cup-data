# World Cup Data WordPress Plugin

Display FIFA World Cup 2026 fixtures, results, standings, and team-specific matches on your WordPress website using the football-data.org API.

Built with the free football-data.org tier in mind, including caching and frontend filtering to minimize API requests.

[![WorldCupData](https://masterymesh.com/blog/wp-content/uploads/2026/06/worldcup-data-frontend.webp)](https://masterymesh.com/blog/worldcup-data-wordpress-plugin/)

## Keywords

World Cup 2026, FIFA World Cup, WordPress Plugin,
World Cup Fixtures, World Cup Results,
World Cup Standings, Football Data API,
Sports WordPress Plugin

## Features

✅ Upcoming World Cup matches

✅ Live matches

✅ Match results

✅ Group standings and tables

✅ Team/Country filter

✅ Team flags

✅ Timezone selection

✅ Language selection

✅ API response caching

✅ Mobile-friendly responsive design

✅ WordPress shortcode support

✅ Compatible with football-data.org Free Tier

---

## Requirements

- WordPress 6.0+
- PHP 8.0+
- football-data.org API key

Create a free account and obtain your API key:

https://www.football-data.org/

---

## Installation

### Method 1: Upload ZIP

1. Download the plugin ZIP.
2. Go to **WordPress Admin → Plugins → Add New → Upload Plugin**
3. Upload the ZIP file.
4. Activate the plugin.

### Method 2: Manual Installation

1. Download the plugin folder "world-cup-data".
2. Upload the plugin folder to:

```
wp-content/plugins/
```

3. Activate the plugin from:

```
WordPress Admin → Plugins
```

---

## Configuration

Navigate to:

```
Settings → World Cup Data
```

[![WorldCupData](https://masterymesh.com/blog/wp-content/uploads/2026/06/worldcup-data-settings.webp)](https://masterymesh.com/blog/worldcup-data-wordpress-plugin/)

### API Token

Enter your football-data.org API token.

The token is stored securely in WordPress and used only for server-side API requests.

### Timezone

Select the timezone used for displaying match dates and kickoff times.

Examples:

- Europe/Berlin
- Europe/Zagreb
- America/New_York

### Language

Select the preferred frontend language.

Currently supported:

- English
- German
- France
- Spanish
- Croatian

Additional languages will be added in future releases.

### Cache Duration

Configure how long API responses should be stored before refreshing.

Default:

```
30 minutes
```

Recommended for Free Tier users.

---

# Shortcodes

## Full World Cup Interface

Displays tabs, filtering, fixtures, results, and standings.

```text
[worldcup]
```

---

## Open Specific Tab

```text
[worldcup default_tab="results"]
```

Supported tabs:

- upcoming
- live
- results
- tables

Example:

```text
[worldcup default_tab="live"]
```

---

## Today's Matches

Displays only today's World Cup matches.

Perfect for homepage usage.

```text
[worldcup_today]
```

---

## Today's Matches with Options

```text
[worldcup_today show_finished="yes" limit="5" title="Today's World Cup Matches"]
```

### Attributes

| Attribute     | Description              |
| ------------- | ------------------------ |
| show_finished | Include finished matches |
| limit         | Limit displayed matches  |
| title         | Custom section title     |

---

# Team Filtering

The plugin automatically generates a team filter from World Cup participants.

Examples:

- Germany
- Croatia
- Brazil
- Argentina
- France

Visitors can instantly filter matches without generating additional API requests.

---

# URL Parameters

The plugin supports preselected filters and tabs via URL parameters.

## Preselect Team

```text
?team=Croatia
```

Example:

```text
https://example.com/world-cup/?team=Croatia
```

---

## Preselect Team and Tab

```text
?team=Brazil&tab=results
```

Example:

```text
https://example.com/world-cup/?team=Brazil&tab=results
```

Supported tabs:

- upcoming
- live
- results
- tables

---

# Caching

To stay within football-data.org free tier limits, API responses are cached using WordPress transients.

Cached data is reused for:

- Team filtering
- Tab switching
- Today's matches shortcode

No unnecessary API calls are made during frontend interactions.

---

# Supported Data

The plugin currently displays:

- Upcoming fixtures
- Live matches
- Match results
- Group standings
- Team flags
- Match dates and times

---

# Roadmap

Planned features:

- Additional language packs
- Match countdowns
- Knockout bracket view
- Team pages
- Player statistics
- Multiple competition support
- Custom color themes

---

# Changelog

## Version 1.0.0

- Initial release
- football-data.org integration
- World Cup fixtures
- Live matches
- Results
- Standings
- Team filtering
- Timezone support
- Language settings
- Homepage shortcode
- Caching support

---

# Credits

Data provided by:

https://www.football-data.org/

This plugin is not affiliated with FIFA or football-data.org.
