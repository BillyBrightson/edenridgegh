<?php
declare(strict_types=1);

/**
 * Initial schema — content, media, gallery, leads, users and operations.
 */
return static function (PDO $db): void {
    $db->exec(<<<SQL
    CREATE TABLE pages (
      id            INTEGER PRIMARY KEY,
      slug          TEXT NOT NULL UNIQUE,
      title         TEXT NOT NULL,
      is_published  INTEGER NOT NULL DEFAULT 1,
      created_at    TEXT NOT NULL,
      updated_at    TEXT NOT NULL
    );
    SQL);

    $db->exec(<<<SQL
    CREATE TABLE sections (
      id            INTEGER PRIMARY KEY,
      page_id       INTEGER NOT NULL REFERENCES pages(id) ON DELETE CASCADE,
      key           TEXT NOT NULL,
      type          TEXT NOT NULL,
      title         TEXT NOT NULL,
      content       TEXT NOT NULL DEFAULT '{}',
      draft_content TEXT,
      sort_order    INTEGER NOT NULL DEFAULT 0,
      is_published  INTEGER NOT NULL DEFAULT 1,
      updated_by    INTEGER REFERENCES users(id),
      updated_at    TEXT NOT NULL,
      UNIQUE(page_id, key)
    );
    SQL);

    $db->exec(<<<SQL
    CREATE TABLE section_revisions (
      id          INTEGER PRIMARY KEY,
      section_id  INTEGER NOT NULL REFERENCES sections(id) ON DELETE CASCADE,
      content     TEXT NOT NULL,
      user_id     INTEGER REFERENCES users(id),
      note        TEXT,
      created_at  TEXT NOT NULL
    );
    SQL);
    $db->exec('CREATE INDEX idx_revisions_section ON section_revisions(section_id, created_at DESC)');

    $db->exec(<<<SQL
    CREATE TABLE media (
      id            INTEGER PRIMARY KEY,
      filename      TEXT NOT NULL,
      original_name TEXT NOT NULL,
      mime          TEXT NOT NULL,
      ext           TEXT NOT NULL,
      bytes         INTEGER NOT NULL,
      width         INTEGER,
      height        INTEGER,
      alt           TEXT DEFAULT '',
      caption       TEXT DEFAULT '',
      lqip          TEXT,
      focal_x       REAL DEFAULT 0.5,
      focal_y       REAL DEFAULT 0.5,
      hash          TEXT,
      uploaded_by   INTEGER REFERENCES users(id),
      created_at    TEXT NOT NULL
    );
    SQL);
    $db->exec('CREATE INDEX idx_media_created ON media(created_at DESC)');
    $db->exec('CREATE UNIQUE INDEX idx_media_hash ON media(hash)');

    $db->exec(<<<SQL
    CREATE TABLE media_variants (
      id        INTEGER PRIMARY KEY,
      media_id  INTEGER NOT NULL REFERENCES media(id) ON DELETE CASCADE,
      label     TEXT NOT NULL,
      format    TEXT NOT NULL,
      width     INTEGER NOT NULL,
      height    INTEGER NOT NULL,
      path      TEXT NOT NULL,
      bytes     INTEGER NOT NULL
    );
    SQL);
    $db->exec('CREATE INDEX idx_variants_media ON media_variants(media_id, width)');

    $db->exec(<<<SQL
    CREATE TABLE gallery_categories (
      id         INTEGER PRIMARY KEY,
      slug       TEXT NOT NULL UNIQUE,
      label      TEXT NOT NULL,
      sort_order INTEGER NOT NULL DEFAULT 0
    );
    SQL);

    $db->exec(<<<SQL
    CREATE TABLE gallery_items (
      id           INTEGER PRIMARY KEY,
      media_id     INTEGER NOT NULL REFERENCES media(id) ON DELETE CASCADE,
      category_id  INTEGER REFERENCES gallery_categories(id) ON DELETE SET NULL,
      title        TEXT DEFAULT '',
      caption      TEXT DEFAULT '',
      sort_order   INTEGER NOT NULL DEFAULT 0,
      is_published INTEGER NOT NULL DEFAULT 1
    );
    SQL);

    $db->exec(<<<SQL
    CREATE TABLE enquiries (
      id             INTEGER PRIMARY KEY,
      name           TEXT NOT NULL,
      email          TEXT NOT NULL,
      phone          TEXT,
      interest       TEXT,
      message        TEXT,
      status         TEXT NOT NULL DEFAULT 'new',
      video_request  INTEGER NOT NULL DEFAULT 0,
      source_page    TEXT,
      referrer       TEXT,
      utm_source     TEXT,
      utm_medium     TEXT,
      utm_campaign   TEXT,
      ip_hash        TEXT,
      user_agent     TEXT,
      is_spam        INTEGER NOT NULL DEFAULT 0,
      deleted_at     TEXT,
      created_at     TEXT NOT NULL
    );
    SQL);
    $db->exec('CREATE INDEX idx_enquiries_created ON enquiries(created_at DESC)');
    $db->exec('CREATE INDEX idx_enquiries_status ON enquiries(status)');

    $db->exec(<<<SQL
    CREATE TABLE enquiry_notes (
      id         INTEGER PRIMARY KEY,
      enquiry_id INTEGER NOT NULL REFERENCES enquiries(id) ON DELETE CASCADE,
      user_id    INTEGER REFERENCES users(id),
      body       TEXT NOT NULL,
      created_at TEXT NOT NULL
    );
    SQL);

    $db->exec(<<<SQL
    CREATE TABLE mail_outbox (
      id         INTEGER PRIMARY KEY,
      to_email   TEXT NOT NULL,
      subject    TEXT NOT NULL,
      body_html  TEXT NOT NULL,
      reply_to   TEXT,
      attempts   INTEGER NOT NULL DEFAULT 0,
      last_error TEXT,
      sent_at    TEXT,
      created_at TEXT NOT NULL
    );
    SQL);

    $db->exec(<<<SQL
    CREATE TABLE users (
      id            INTEGER PRIMARY KEY,
      name          TEXT NOT NULL,
      email         TEXT NOT NULL UNIQUE,
      password_hash TEXT NOT NULL,
      role          TEXT NOT NULL DEFAULT 'editor',
      must_reset    INTEGER NOT NULL DEFAULT 0,
      totp_secret   TEXT,
      last_login_at TEXT,
      is_active     INTEGER NOT NULL DEFAULT 1,
      created_at    TEXT NOT NULL
    );
    SQL);

    $db->exec(<<<SQL
    CREATE TABLE auth_tokens (
      id            INTEGER PRIMARY KEY,
      user_id       INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
      selector      TEXT NOT NULL UNIQUE,
      verifier_hash TEXT NOT NULL,
      purpose       TEXT NOT NULL,
      expires_at    TEXT NOT NULL,
      created_at    TEXT NOT NULL
    );
    SQL);

    $db->exec(<<<SQL
    CREATE TABLE login_attempts (
      id         INTEGER PRIMARY KEY,
      identifier TEXT NOT NULL,
      succeeded  INTEGER NOT NULL,
      created_at TEXT NOT NULL
    );
    SQL);
    $db->exec('CREATE INDEX idx_login_attempts ON login_attempts(identifier, created_at)');

    $db->exec(<<<SQL
    CREATE TABLE settings (
      key        TEXT PRIMARY KEY,
      value      TEXT NOT NULL,
      type       TEXT NOT NULL DEFAULT 'string',
      updated_at TEXT NOT NULL
    );
    SQL);

    $db->exec(<<<SQL
    CREATE TABLE seo_meta (
      page_slug         TEXT PRIMARY KEY,
      title             TEXT DEFAULT '',
      description       TEXT DEFAULT '',
      canonical         TEXT DEFAULT '',
      og_title          TEXT DEFAULT '',
      og_description    TEXT DEFAULT '',
      og_image_media_id INTEGER REFERENCES media(id) ON DELETE SET NULL,
      twitter_card      TEXT DEFAULT 'summary_large_image',
      noindex           INTEGER NOT NULL DEFAULT 0,
      updated_at        TEXT
    );
    SQL);

    $db->exec(<<<SQL
    CREATE TABLE activity_log (
      id         INTEGER PRIMARY KEY,
      user_id    INTEGER REFERENCES users(id),
      action     TEXT NOT NULL,
      entity     TEXT,
      entity_id  INTEGER,
      meta       TEXT,
      ip_hash    TEXT,
      created_at TEXT NOT NULL
    );
    SQL);
    $db->exec('CREATE INDEX idx_activity_created ON activity_log(created_at DESC)');

    $db->exec(<<<SQL
    CREATE TABLE video_requests (
      id         INTEGER PRIMARY KEY,
      enquiry_id INTEGER REFERENCES enquiries(id) ON DELETE CASCADE,
      revealed   INTEGER NOT NULL DEFAULT 0,
      created_at TEXT NOT NULL
    );
    SQL);
};
