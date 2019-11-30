<?php

$from = 1789;
$to = 2018;



if (!isset($datemax)) $datemax = 2009;
if (!isset($from)) $from = 1900;
if (!isset($to)) $to = $datemax;
if (!isset($smooth)) $smooth = 0;

if (isset($_REQUEST['from']) &&  is_numeric($_REQUEST['from'])) $from = $_REQUEST['from'];
if ($from < 1452) $from = 1452;
if ($from > $datemax) $from = $datemax;

if (isset($_REQUEST['to']) && is_numeric($_REQUEST['to'])) $to = $_REQUEST['to'];
if ($to < 1475) $to = $datemax;
if ($to > $datemax) $to = $datemax;

$pdo = new PDO('sqlite:g1gram.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
$pdo->exec("pragma synchronous = off;");

$q = "vie";
if (isset($_REQUEST['q'])) $q = $_REQUEST['q'];
$words = preg_split("@[ ,]+@", $q);


?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8" />
    <script src="lib/dygraph.min.js">//</script>
    <link rel="stylesheet" type="text/css" href="lib/dygraph.css"/>
    <link rel="stylesheet" type="text/css" href="g1gram.css"/>
<?php
$form = null;
$year = $value = $max = 0;
$maxrank = 1000000;
$min = 1000000;
$data = array();
$select = $pdo->prepare("SELECT rank FROM word WHERE form = ? and year = ?; ");
$select->bindParam(1, $form, PDO::PARAM_STR);
$select->bindParam(2, $year, PDO::PARAM_INT);
$i = 0;
foreach ($words as $form) {
  for ($year=$from; $year <= $to; $year++) {
    $select->execute();
    list($value) = $select->fetch(PDO::FETCH_NUM);
    if(!$value) $value = $maxrank;
    if ($value < 1000000 && $max < $value) $max = $value;
    if ($value && $min > $value) $min = $value;
    $data[$year][$i] = $value;
  }
  $i++;
}
?>
    <script>

data = [
<?php
$first = true;
foreach ($data as $year => $ranks) {
  if ($first) {
    $first = false;
  } else {
    echo ",\n";
  }
  echo "  [", $year, ", ", implode(", ", $ranks), "]";
}
  ?>

];


labels = ["année", "<?= implode('", "', $words)?>"];
const ROLL_STORE = 'g1gramRoll';
var rollPeriod = localStorage.getItem(ROLL_STORE);
if (!rollPeriod) rollPeriod = 3;
// draw the graph with all the configuration
attrs = {
  title : "Index des mots de Google Books en français, variation chronologique (rang 1 = fréquent, 900K = 900 000 = rare)",
  ylabel: "Rang",
  labels: labels,
  // labelsKMB: true,
  legend: "follow",
  labelsSeparateLines: true,
  // ylabel: "occurrence / 100 000 mots",
  // xlabel: "Répartition des années en nombre de mots",
  showRoller: true,
  rollPeriod: rollPeriod,
  drawCallback: function() {
    localStorage.setItem(ROLL_STORE, this.rollPeriod());
  },
  colors:['hsla(0, 50%, 50%, 0.8)', 'hsla(128, 50%, 50%, 0.8)', 'hsla(64, 50%, 50%, 0.8)', 'hsla(192, 50%, 50%, 0.8)', 'hsla(32, 50%, 50%, 0.8)', 'hsla(160, 50%, 50%, 0.8)', 'hsla(224, 50%, 50%, 0.8)', 'hsla(96, 50%, 50%, 0.8)'],
  strokeBorderWidth: 0.5,
  strokeWidth: 5,
  highlightCircleSize: 8,
  drawGapEdgePoints: true,
  logscale: true,
  axes : {
    x: {
      independentTicks: true,
      drawGrid: true,
      // gridLineColor: "rgba( 128, 128, 128, 0.1)",
      // gridLineWidth: 1,
    },
    y: {
      labelsKMB: true,
      independentTicks: true,
      drawGrid: true,
      valueRange: [<?= $max ?>, <?= $min ?>],
      // gridLineColor: "rgba( 128, 128, 128, 0.1)",
      // gridLineWidth: 1,
    },
  },
};

attrs.underlayCallback = function(canvas, area, g) {
  canvas.fillStyle = "rgba(192, 192, 192, 0.2)";
  var periods = [[1562,1598], [1648,1653], [1789,1795], [1814,1815], [1830,1831], [1848,1849], [1870,1871], [1914,1918], [1939,1945], [1968, 1969]];
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
    <form>
      <input size="50" name="q" value="<?= $q ?>" autocomplete="off" autofocus="true"/>
      <input class="year" size="4" name="from" value="<?= $from ?>"/>
      <input class="year" size="4" name="to" value="<?= $to ?>"/>
      <button type="submit">▶</button>
    </form>
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
  let word = "<?php if (isset($words[1])) echo $words[1]; else echo $words[0]; ?>";
  g.setAnnotations(anns.concat([
    {series: word, x: "1789", shortText: "1789", width: "", height: "", cssClass: "annv"},
    {series: word, x: "1815", shortText: "1815", width: "", height: "", cssClass: "annv"},
    {series: word, x: "1830", shortText: "1830", width: "", height: "", cssClass: "annv"},
    {series: word, x: "1848", shortText: "1848", width: "", height: "", cssClass: "annv"},
    {series: word, x: "1870", shortText: "1870", width: "", height: "", cssClass: "annv"},
    {series: word, x: "1914", shortText: "1914", width: "", height: "", cssClass: "annv"},
    {series: word, x: "1939", shortText: "1939", width: "", height: "", cssClass: "annv"},
    {series: word, x: "1968", shortText: "1968", width: "", height: "", cssClass: "annv"},
    <?php
foreach ($words as $form) {
  echo '    {series:"'.$form.'", shortText:"'.$form.'", x: '.$to.', width: "", height: "", cssClass: "ann"},'."\n";
}
     ?>
  ]));
});
    </script>
  </body>
</html>
