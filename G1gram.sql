PRAGMA encoding = 'UTF-8';
PRAGMA page_size = 8192;
-- ne pas vérifier l’intégrité au chargement
PRAGMA foreign_keys = OFF;

CREATE TABLE word (
  -- word
  form        TEXT NOT NULL,
  lemma       TEXT NOT NULL,
  year        INTEGER NOT NULL,
  count       INTEGER NOT NULL,
  freq        REAL NOT NULL,
  rank        INTEGER,
  ranklog     REAL,

  id          INTEGER, -- rowid auto
  PRIMARY KEY(id ASC)
);
-- CREATE UNIQUE INDEX word_form ON word(form, year);
-- CREATE INDEX word_count ON word(year, count DESC, form);
CREATE INDEX word_lemma ON word(year, lemma);

CREATE TABLE more (
  -- word
  form        TEXT NOT NULL,
  year        INTEGER NOT NULL,
  count       INTEGER NOT NULL,

  id          INTEGER, -- rowid auto
  PRIMARY KEY(id ASC)
);



CREATE TABLE year (
  count       INTEGER NOT NULL,
  id          INTEGER, -- rowid auto
  PRIMARY KEY(id ASC)
);

CREATE TABLE lemma (
  -- word
  year        INTEGER NOT NULL,
  entry       TEXT NOT NULL,
  count       INTEGER NOT NULL,
  rank        INTEGER,
  freq        REAL,
  ranklog     REAL,

  id          INTEGER, -- rowid auto
  PRIMARY KEY(id ASC)
);
