# Analyse des structures de retour pour Correction 6

## class-alert404-detector.php

### collect_payload(): array<string, string|int|null>
Retourne:
```
[
  'url' => string (from gather)
  'full_url' => string (from gather)
  'method' => string (from gather)
  'ip' => string (from gather)
  'referrer' => string (from gather)
  'user_agent' => string (from gather)
  'language' => array|string (from gather)
  'browser' => array<string, string>
  'os' => array<string, string>
  'device' => string
  'user_readable' => string
  'wordpress' => array<string, mixed>
  'timestamp' => string
  'timestamp_unix' => int
  OR fallback:
  'ip' => string
  'url' => string
  'error' => string
]
```
**Type**: `array<string, mixed>`

## class-alert404-request-info.php

### gather(): array<string, mixed>
Structure complexe avec imbrications - retourne clés strings et valeurs variées

### extract_ips_from_header(string, string): array<int, string>
Retourne tableau d'IPs extraites (array_map et explode)

## class-alert404-settings.php

### sanitize_options(array $input): array<string, mixed>
Retourne options nettoyées (clés strings, valeurs variées)

### sanitize_smtp_options(array $input): array<string, mixed>
Retourne options SMTP nettoyées

## class-alert404-smtp-handler.php

### get_smtp_config(): array<string, string|int>
```
[
  'host' => string
  'port' => int|string (cast to int in usage)
  'username' => string
  'password' => string
  'encryption' => string
  'from_email' => string
  'from_name' => string
]
```
**Type**: `array<string, string|int>`

### test_connection(): array<string, bool|string>
```
[
  'success' => bool
  'message' => string
]
```
**Type**: `array<string, bool|string>`

## class-alert404-storage.php

### get_stats(int): array<string, mixed>
```
Array of rows from wpdb::get_results with 'ARRAY_A'
Each row is: ['id', 'url', 'ip', 'referrer', 'user_agent', 'user_agent_readable', 'timestamp']
```
**Type**: `array<int, array<string, mixed>>`

### get_stats_by_date(string): array<string, mixed>
**Type**: `array<int, array<string, mixed>>`

### get_top_urls(int): array<string, int>
```
Built from wpdb result:
$result[(string)$row['url']] = (int)$row['count']
```
**Type**: `array<string, int>`

### get_top_ips(int): array<string, int>
```
Built from wpdb result:
$result[(string)$row['ip']] = (int)$row['count']
```
**Type**: `array<string, int>`

### get_recent_ips(int): array<string, int>
**Type**: `array<string, int>`

### get_count_by_referrer(int): array<string, int>
**Type**: `array<string, int>`

## class-alert404-user-agent-parser.php

### parse(string): array<string, array<string, string>>
```
[
  'browser' => ['name' => string, 'version' => string]
  'os' => ['name' => string, 'version' => string]
  'device_type' => string
]
```
**Type**: `array<string, mixed>` (mixte car device_type est string)

### detect_browser(string): array<string, string>
```
['name' => string, 'version' => string]
```

### detect_os(string): array<string, string>
```
['name' => string, 'version' => string]
```

### get_structured_info(string): array<string, mixed>
```
[
  'browser_name' => string
  'browser_version' => string
  'os_name' => string
  'os_version' => string
  'device_type' => string
  'readable' => string
]
```
