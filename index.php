<?php

$datemax = 2019;
$datemin = 1450;
$from = 1770;
$to = 2019;

if (isset($_REQUEST['from']) &&  is_numeric($_REQUEST['from'])) $from = $_REQUEST['from'];
if ($from < $datemin) $from = $datemin;
if ($from > $datemax) $from = $datemax;

if (isset($_REQUEST['to']) && is_numeric($_REQUEST['to'])) $to = $_REQUEST['to'];
if ($to < $datemin) $to = $datemax;
if ($to > $datemax) $to = $datemax;

$pdo = new PDO('sqlite:lexichrone.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
$pdo->exec("pragma synchronous = off;");


$smooth = 0; // no used

$q = "liberté, égalité, fraternité, laïcité";
if (isset($_REQUEST['q']) && $_REQUEST['q']) $q = $_REQUEST['q'];
$words = preg_split("@[ ,]+@", $q);


?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8" />
    <script src="lib/dygraph.js">//</script>
    <script src="lib/plotHistory.js">//</script>
    <link rel="stylesheet" type="text/css" href="lib/dygraph.css"/>
    <link rel="stylesheet" type="text/css" href="lexichrone.css"/>
<?php

// strip unknown words
$select = $pdo->prepare("SELECT count FROM word WHERE form = ?");
$select->bindParam(1, $form, PDO::PARAM_STR);
$form = null;
$forms = array();
foreach ($words as $form) {
  $select->execute();
  if(!$select->fetch(PDO::FETCH_NUM)) continue;
  $forms[] = $form;
}
$table = "lemma";
// test if forms are all in lemma table,
$select = $pdo->prepare("SELECT count FROM lemma WHERE form = ?");
$select->bindParam(1, $form, PDO::PARAM_STR);
foreach ($forms as $form) {
  $select->execute();
  if(!$select->fetch(PDO::FETCH_NUM)) {
    $table = "word";
    break;
  }
}


$year = $value = $max = 0;
$maxrank = 1000000;
$min = 1000000;
$data = array();




$select = $pdo->prepare("SELECT rank FROM $table WHERE form = ? and year = ?; ");
$select->bindParam(1, $form, PDO::PARAM_STR);
$select->bindParam(2, $year, PDO::PARAM_INT);
$i = 0;
$smooth= 10;
$smoothCoef = 1.5;
$deviation = array();
foreach ($forms as $form) {
  $dragged = array_fill(0, $smooth, 0);
  $lastvalue = 0;
  $lastdelta = 0;
  for ($year=$from; $year <= $to; $year++) {
    $select->execute();
    list($value) = $select->fetch(PDO::FETCH_NUM);
    if (!$value) {
      $dragged[$year % $smooth] = 0;
      $data[$year][$i] = "null";
      $deviation[$year][$i] = 0;
    }
    else {
      if ($value < 1000000 && $max < $value) $max = $value;
      if ($value && $min > $value) $min = $value;
      $data[$year][$i] = $value;
      if ($lastvalue) $dragged[$year % $smooth] = ($lastvalue - $value);
      $delta = abs(array_sum($dragged) / $smooth);
      $delta = 2 * $delta;
      if ($delta < $value/20) $delta = 0;
      $deviation[$year][$i] = $delta;
    }
    $lastvalue = $value;
  }
  $i++;
}
?>
    <script>

data = [
<?php
$first = true;
$cols = count($forms);
for ($year=$from; $year <= $to; $year++) {
  if ($first) {
    $first = false;
  } else {
    echo ",\n";
  }
  echo "  [", $year, ", ", implode(", ", $data[$year]), "]";
}
  ?>

];


labels = ["année", "<?= implode('", "', $forms)?>"];
const ROLL_STORE = 'lexichroneRoll';
var rollPeriod = localStorage.getItem(ROLL_STORE);
if (!rollPeriod) rollPeriod = 2;
// draw the graph with all the configuration
attrs = {
  title : "<?= ($table == "lemma")?"Lemmes":"Mots" ?> de Google Books 2019, chronologie et rangs",
  ylabel: "Rang",
  labels: labels,
  legend: "follow",
  labelsSeparateLines: true,
  ylabel: "Rang",
  // errorBars: true,
  plotter: Dygraph.plotHistory,
  fillAlpha: 0.25,
  strokeWidth: 2,
  logscale: true,
  // xlabel: "Répartition des années en nombre de mots",
  colors:[
    'hsla(0, 50%, 50%, 1)',   // 1
    'hsla(225, 50%, 50%, 1)', // 2
    'hsla(90, 60%, 30%, 1)',  // 3
    'hsla(45, 80%, 50%, 1)',  // 4
    'hsla(180, 50%, 40%, 1)', // 5
    'hsla(270, 50%, 50%, 1)', // 6
    'hsla(135, 70%, 50%, 1)',
    'hsla(215, 90%, 50%, 1)',
    'hsla(0, 30%, 50%, 1)',
  ],
  highlightSeriesOpts: {
    // strokeWidth: 3
  },
  // showRoller: true,
  rollPeriod: 1,
  drawCallback: function() {
    localStorage.setItem(ROLL_STORE, this.rollPeriod());
  },
  /*
  pointSize: 6,
  drawPointCallback: function (g, name, ctx, canvasx, canvasy, color, radius) {
    ctx.fillStyle = color;
    ctx.beginPath();
    ctx.globalAlpha = 0.1;
    ctx.arc(canvasx, canvasy, radius, 0, 2 * Math.PI, false);
    ctx.fill();
    ctx.beginPath();
    ctx.fillStyle = "#000";
    ctx.globalAlpha = 1;
    ctx.arc(canvasx, canvasy, 1.5, 0, 2 * Math.PI, false);
    ctx.fill();
  },
  */
  axes : {
    x: {
      independentTicks: true,
      drawGrid: true,
      // gridLineColor: "rgba( 128, 128, 128, 0.1)",
      // gridLineWidth: 1,
      ticker: function(a, b, pixels, opts, dygraph, vals) {
        return [
          {"v": 1648, "label": 1648},
          {"v": 1715, "label": 1715},
          {"v": 1762, "label": 1762},
          {"v": 1789, "label": "1789        "},
          {"v": 1795, "label": "        1795"},
          {"v": 1815, "label": 1815},
          {"v": 1830, "label": 1830},
          {"v": 1848, "label": 1848},
          {"v": 1870, "label": 1870},
          {"v": 1900, "label": 1900},
          {"v": 1914, "label": "1914        "},
          {"v": 1918, "label": "        1918"},
          {"v": 1939, "label": "1939        "},
          {"v": 1945, "label": "        1945"},
          {"v": 1968, "label": 1968},
          {"v": 1989, "label": 1989},
          {"v": 2005, "label": 2005},
          {"v": 2018, "label": 2019},
        ]
      },
    },
    y: {
      axisLabelFormatter: function(num) {
        return num.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1 ');
      },
      drawGrid: true,
      //valueRange: [<?= 220000 ?>, <?= min($min * 0.9, 50) ?>],
      valueRange: [<?= $max * 1.1 ?>, <?= $min * 0.9 ?>],
      // gridLineColor: "rgba( 128, 128, 128, 0.1)",
      // gridLineWidth: 1,
    },
  },
};
// ’Evénements
attrs.underlayCallback = function(canvas, area, g) {
  canvas.fillStyle = "rgba(192, 192, 192, 0.2)";
  var periods = [[1562,1598], [1648,1653], [1789,1795], [1870,1871], [1914,1918], [1939,1945]];
  var lim = periods.length;
  for (var i = 0; i < lim; i++) {
    var bottom_left = g.toDomCoords(periods[i][0], -20);
    var top_right = g.toDomCoords(periods[i][1], +20);
    var left = bottom_left[0];
    var right = top_right[0];
    canvas.fillRect(left, area.y, right - left, area.h);
  }
};
    </script>
  </head>
  <body>
    <header id="header">
      <?php include("header.php") ?>
      <form>
        <input size="50" name="q" value="<?= $q ?>" autocomplete="off" autofocus="true"/>
        <input class="year" size="4" name="from" value="<?= $from ?>"/>
        <input class="year" size="4" name="to" value="<?= $to ?>"/>
        <button type="submit">▶</button>
        <?php
        $gq = preg_split("@[ ,]+@", $q);
        $gq = implode(", ", $gq);
         ?>
        Voir dans <a target="_blank" href="https://books.google.com/ngrams/graph?content=<?= $gq ?>&amp;year_start=<?= $from ?>&amp;year_end=<?= $to ?>&amp;corpus=30&amp;smoothing=3">Google Books NGram Viewer</a>
      </form>
    </header>
    <div id="chart" class="dygraph"></div>

<?php

/*

$select = $pdo->prepare(
  "SELECT year, form, count FROM word WHERE year = ? ORDER BY COUNT DESC"
);
$year = 1789;
$row = array();
$select->bindParam(1, $year, PDO::PARAM_INT);
for ($year; $year < 2019; $year++) {
  $lim = 1000;
  echo "<td>", $year;
  $select->execute();
  while($lim-- > 0) {
    $row = $select->fetch();
  }
  echo "</td>";
}
*/

?>
    <script>
var div = document.getElementById("chart");
var g = new Dygraph(div, data, attrs);
g.ready(function() {
  var anns = g.annotations();
  let word = "<?php if (isset($forms[1])) echo $forms[1]; else echo $forms[0]; ?>";
  g.setAnnotations(anns.concat([
    // {series: word, x: "1789", shortText: "1789", width: "", height: "", cssClass: "annv"},
    // {series: word, x: "1815", shortText: "1815", width: "", height: "", cssClass: "annv"},
    // {series: word, x: "1830", shortText: "1830", width: "", height: "", cssClass: "annv"},
    // {series: word, x: "1848", shortText: "1848", width: "", height: "", cssClass: "annv"},
    // {series: word, x: "1870", shortText: "1870", width: "", height: "", cssClass: "annv"},
    // {series: word, x: "1914", shortText: "1914", width: "", height: "", cssClass: "annv"},
    // {series: word, x: "1939", shortText: "1939", width: "", height: "", cssClass: "annv"},
    // {series: word, x: "1968", shortText: "1968", width: "", height: "", cssClass: "annv"},
    // {series: word, x: "1989", shortText: "1989", width: "", height: "", cssClass: "annv"},
    <?php
foreach ($forms as $form) {
  echo '    {series:"'.$form.'", shortText:"'.$form.'", x: '.$to.', width: "", height: "", cssClass: "ann"},'."\n";
}
     ?>
  ]));
});
    </script>
  </body>
</html>
