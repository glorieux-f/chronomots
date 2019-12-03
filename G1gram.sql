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
  freq        REAL,
  rank        INTEGER,
  ranklog     REAL,

  id          INTEGER, -- rowid auto
  PRIMARY KEY(id ASC)
);
CREATE UNIQUE INDEX word_form ON word(form, year);
CREATE INDEX word_count ON word(year, count DESC, form);
CREATE INDEX word_rank ON word(year, rank);
CREATE INDEX word_lemma ON word(year, lemma);

CREATE TABLE more (
  -- word
  form        TEXT NOT NULL,
  year        INTEGER NOT NULL,
  count       INTEGER NOT NULL,

  id          INTEGER, -- rowid auto
  PRIMARY KEY(id ASC)
);

CREATE TABLE lemma (
  year        INTEGER NOT NULL,
  form        TEXT NOT NULL,
  count       INTEGER NOT NULL,
  rank        INTEGER,
  freq        REAL,

  id          INTEGER,
  PRIMARY KEY(id ASC)
);
CREATE INDEX lemma_count ON lemma(year, count DESC);
CREATE UNIQUE INDEX lemma_form ON lemma(form, year);
CREATE INDEX lemma_yearRank ON lemma(year, rank DESC);



CREATE TABLE year (
  count       INTEGER NOT NULL,
  id          INTEGER, -- rowid auto
  PRIMARY KEY(id ASC)
);
