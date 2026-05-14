-- panda_waitlist — Phase-8 pilot recruitment list.
--
-- A signup from panda.shira.farm/index lands here. The Mwangi cron job
-- (Phase-8) will read this table to identify who to onboard into the
-- 200-farmer Meru + Kirinyaga pilot.
--
-- Idempotent on email — re-submitting just updates the row's
-- updated_at, doesn't create a duplicate row. Status is admin-managed
-- once farmers are contacted.

CREATE TABLE IF NOT EXISTS panda_waitlist (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    phone TEXT,
    county TEXT,
    acreage REAL,
    crop TEXT,
    status TEXT NOT NULL DEFAULT 'new'
        CHECK (status IN ('new', 'contacted', 'enrolled', 'declined', 'lost')),
    notes TEXT,
    source TEXT NOT NULL DEFAULT 'website',
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS panda_waitlist_status_idx ON panda_waitlist(status);
CREATE INDEX IF NOT EXISTS panda_waitlist_county_idx ON panda_waitlist(county);
CREATE INDEX IF NOT EXISTS panda_waitlist_created_at_idx ON panda_waitlist(created_at DESC);
