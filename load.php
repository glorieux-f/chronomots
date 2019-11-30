<?php
mb_internal_encoding ("UTF-8");
G1gram::lexique();
G1gram::connect("g1gram.sqlite");
G1gram::global();
G1gram::walk();
// G1gram::ranks();

class G1gram {
  /** lien à la base de donnée */
  static $pdo;
  /** Nombre de mots par an */
  static $years = array();
  /** dicolecte */
  static $dic;

  /**
   * Charger les stats globales
   */
  static public function global()
  {
    self::$pdo->beginTransaction();
    $insert = self::$pdo->prepare(
      "INSERT INTO year (id, count) VALUES (?, ?)"
    );
    $id = $year = 0;
    $insert->bindParam(1, $id, PDO::PARAM_INT);
    $insert->bindParam(2, $count, PDO::PARAM_INT);
    $handle = fopen("data/googlebooks-fre-all-totalcounts-20120701.txt", "r");
    fgetcsv($handle, 1000, ","); // skip first line
    while (($line = fgets($handle, 4096)) !== FALSE) {
      list($id, $count) = explode(",", $line);
      // $insert->execute();
      $years[$id] = $count;
    }
    self::$pdo->commit();
    self::$years = $years;
  }

  /**
   * Charger lexique en mémoire
   */
  static public function lexique()
  {
    // echo "mem=",memory_get_usage(),"\n";
    $i = 0;
    $dic = array();
    $rank = array();
    $id = $fid = $flexion = $lemma = null;
    $handle = fopen("datalecte/lexique.txt", "r");
    while (list($id, $fid, $flexion, $lemma) = fscanf($handle, "%s\t%s\t%s\t%s")) {
      $i++;
      if (isset($dic[$flexion])) continue;
      $dic[$flexion] = $lemma;
      // $rank[$flexion] = $i;
      // echo $i,". ",$flexion," - ",$lemma,"\n";
    }
    fclose($handle);
    self::$dic = $dic;
    echo "lexique lignes=",$i,"\n";
  }

  static public function walk()
  {
    // gzopen 112s.
    // gzfile 111s.
    // fopen("compress.zlib://") 104s.
    // fopen+getcsv timeout 179 s.
    $glob = dirname(__FILE__).'/data/googlebooks-fre-all-1gram-20120701-*.gz';
    $years = self::$years;
    $dic = self::$dic;
    self::$pdo->beginTransaction();
    $word = self::$pdo->prepare(
      "INSERT INTO word (year, form, count, lemma) VALUES (?, ?, ?, ?)"
    );
    $more = self::$pdo->prepare(
      "INSERT INTO more (year, form, count) VALUES (?, ?, ?)"
    );
    $form = $allograph = $lemma = $year = $count = null;
    $word->bindParam(1, $year, PDO::PARAM_INT);
    $word->bindParam(2, $form, PDO::PARAM_STR);
    $word->bindParam(3, $count, PDO::PARAM_INT);
    $word->bindParam(4, $lemma, PDO::PARAM_STR);
    $more->bindParam(1, $year, PDO::PARAM_INT);
    $more->bindParam(2, $allograph, PDO::PARAM_STR);
    $more->bindParam(3, $count, PDO::PARAM_INT);
    $lastform = "";
    $lastcount = 0;
    foreach(glob($glob) as $srcFile) {
      $start = microtime(true);
      fwrite(STDERR, $srcFile);
      $handle = fopen("compress.zlib://".$srcFile, "r");
      if(!$handle) exit("pb avec ".$srcFile);
      while ((list($form, $year, $count) = fscanf($handle, "%s\t%d\t%d")) !== FALSE) {
        $show = true;
        if ($form[0] == '.') {
          $show = false;
        }
        else if (preg_match("@[\[\]\-\"\\/*%§^<>_0123456789',{}~();:|«»“”?!•=+►♦□■°]@u", $form)) {
          $show = false;
        }
        else if (isset($dic[$form])) {
          $show = false;
          // $lemma = $dic[$form];
          // $word->execute();
        }
        else {
          $allograph = mb_strtolower($form);
          if (isset($dic[$allograph])) {
            $more->execute();
            $show = false;
          }
          else if ($allograph[0] == 'e') {
            $allograph = "é".mb_substr($allograph, 1);
            if (isset($dic[$allograph])) {
              $form = $allograph;
              $more->execute();
              $show = false;
            }
            else {

            }
          }
        }
        if(!$show) {
          if($lastcount) echo "\t",$lastcount,"\n";
          $lastcount = 0;
          continue;
        }

        if ($lastform == $form) {
          $lastcount += $count;
          continue;
        }
        if($lastcount) echo "\t",$lastcount,"\n";
        echo $form;
        $lastcount = $count;
        $lastform = $form;
      }
      fwrite(STDERR, " ".(microtime(true) - $start)."\n");
    }
    self::$pdo->commit();
  }
  /**
   * Connexion à la base de données
   */
  static function connect($sqlfile, $create=false)
  {
    $dsn = "sqlite:".$sqlfile;
    if($create && file_exists($sqlfile)) unlink($sqlfile);
    // create database
    if (!file_exists($sqlfile)) { // if base do no exists, create it
      echo "Base, création ".$sqlfile."\n";
      if (!file_exists($dir = dirname($sqlfile))) {
        mkdir($dir, 0775, true);
        @chmod($dir, 0775);  // let @, if www-data is not owner but allowed to write
      }
      self::$pdo = new PDO($dsn);
      self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      @chmod($sqlfile, 0775);
      self::$pdo->exec(file_get_contents(dirname(__FILE__)."/G1gram.sql"));
      return;
    }
    else {
      // absolute path needed ?
      self::$pdo = new PDO($dsn);
      self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
  }


  static function ranks()
  {
    $pdo = self::$pdo;
    // Ces commandes sont très longues et ont été appliquée à la main dans la console SQLite
    $sql = <<<AAA
      CREATE UNIQUE INDEX word_form ON word(form, year);
      CREATE INDEX word_count ON word(year, count);
      -- trop long
      -- UPDATE word SET rank = (SELECT COUNT(*) FROM word AS w2 WHERE w2.year = word.year AND w2.count >= word.count);

      SELECT year, lemma, SUM(count) as sum FROM word GROUP BY year, lemma ORDER BY year, sum DESC LIMIT 1000;

      CREATE TABLE lemma(
        year        INTEGER,
        wordid      INTEGER,
        rank        INTEGER,
        id          INTEGER, -- rowid auto
        PRIMARY KEY(id ASC)
      );
      INSERT INTO ranks (year, wordid)
      SELECT year, id FROM word
      ORDER BY year, count DESC;
      CREATE UNIQUE INDEX ranks_wordid ON ranks(wordid);
      CREATE INDEX ranks_year ON ranks(year);
    AAA;

    self::$pdo->beginTransaction();
    $select = $pdo->prepare(
      "SELECT year, id FROM ranks"
    );
    $update = $pdo->prepare(
      "UPDATE ranks SET rank = 1 + id - ? WHERE id = ? "
    );
    $update->bindParam(1, $yearid, PDO::PARAM_INT);
    $update->bindParam(2, $id, PDO::PARAM_INT);
    $year = $yearid = $id = $lastyear = 0;
    $select->execute();
    while(list($year, $id) = $select->fetch()) {
      if ($lastyear != $year) {
        $yearid = $id;
        $lastyear = $year;
        echo $year,"\n";
      }
      $update->execute();
    }
    self::$pdo->commit();
    $sql = <<<AAA
    UPDATE word SET rank=(SELECT rank FROM ranks WHERE wordid=word.id);
    AAA;

  }
}



 ?>
