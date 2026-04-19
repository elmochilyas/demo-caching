# Caching Demo - Database Structure

## Database: SQLite
**File**: `database/database.sqlite`

---

## Tables

### 1. photos
| Column | Type | Constraints | Description |
|--------|------|--------------|--------------|
| id | INTEGER | PRIMARY KEY, AUTOINCREMENT | Unique identifier |
| title | TEXT | NOT NULL | Photo title (max 255) |
| url | TEXT | NOT NULL | Image URL |
| description | TEXT | NULLABLE | Photo description |
| metadata | TEXT | NULLABLE | JSON metadata (camera, location, etc.) |
| created_at | DATETIME | | Timestamp |
| updated_at | DATETIME | | Timestamp |

**Records**: 300 photos

---

### 2. cache_stats
| Column | Type | Constraints | Description |
|--------|------|--------------|--------------|
| id | INTEGER | PRIMARY KEY, AUTOINCREMENT | Unique identifier |
| endpoint | TEXT | NOT NULL | API endpoint name |
| cache_status | TEXT | NOT NULL | HIT, MISS, or N/A |
| response_time_ms | INTEGER | NOT NULL | Response time in milliseconds |
| user_session_id | TEXT | NOT NULL | Session ID |
| created_at | DATETIME | | Timestamp |
| updated_at | DATETIME | | Timestamp |

**Purpose**: Log every request's cache behavior and timing

---

### 3. api_requests
| Column | Type | Constraints | Description |
|--------|------|--------------|--------------|
| id | INTEGER | PRIMARY KEY, AUTOINCREMENT | Unique identifier |
| user_session_id | TEXT | NOT NULL | Session ID |
| endpoint | TEXT | NOT NULL | weather-uncached or weather-cached |
| api_call_count | INTEGER | DEFAULT 0 | External API calls made |
| request_count | INTEGER | DEFAULT 0 | User requests made |
| response_time_ms | INTEGER | DEFAULT 0 | Response time |
| created_at | DATETIME | | Timestamp |
| updated_at | DATETIME | | Timestamp |

**Purpose**: Track user requests vs. actual API calls

---

## Entity Relationship Diagram

```
┌─────────────────┐     ┌──────────────────┐     ┌──────────────────┐
│     photos      │     │   cache_stats    │     │  api_requests   │
├─────────────────┤     ├──────────────────┤     ├──────────────────┤
│ id (PK)         │     │ id (PK)          │     │ id (PK)         │
│ title           │     │ endpoint        │     │ user_session_id │
│ url             │     │ cache_status   │     │ endpoint        │
│ description     │     │ response_time_ms      │ api_call_count  │
│ metadata (JSON) │     │ user_session_id │     │ request_count  │
│ created_at      │     │ created_at     │     │ response_time_ms
│ updated_at      │     │ updated_at     │     │ created_at     │
└─────────────────┘     └──────────────────┘     └──────────────────┘
```

---

## SQL Schema (for reference)

### photos
```sql
CREATE TABLE photos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    url TEXT NOT NULL,
    description TEXT,
    metadata TEXT,
    created_at DATETIME,
    updated_at DATETIME
);
```

### cache_stats
```sql
CREATE TABLE cache_stats (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    endpoint TEXT NOT NULL,
    cache_status TEXT NOT NULL,
    response_time_ms INTEGER NOT NULL,
    user_session_id TEXT NOT NULL,
    created_at DATETIME,
    updated_at DATETIME
);
```

### api_requests
```sql
CREATE TABLE api_requests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_session_id TEXT NOT NULL,
    endpoint TEXT NOT NULL,
    api_call_count INTEGER DEFAULT 0,
    request_count INTEGER DEFAULT 0,
    response_time_ms INTEGER DEFAULT 0,
    created_at DATETIME,
    updated_at DATETIME
);
```

---

## Indexes

No custom indexes defined. Laravel uses auto-increment PRIMARY KEY on `id` column for all tables.

---

## Seeded Data

- **photos**: 300 records (seeded via PhotoSeeder)
- **cache_stats**: Created dynamically on each API request
- **api_requests**: Created dynamically on weather API requests