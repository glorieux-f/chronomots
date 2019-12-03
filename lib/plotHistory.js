(function() {
"use strict";

var Dygraph;
if (window.Dygraph) {
  Dygraph = window.Dygraph;
} else if (typeof(module) !== 'undefined') {
  Dygraph = require('../dygraph');
}

// i.e. is none of (null, undefined, NaN)
function isOK(x) {
  return !!x && !isNaN(x);
};

function plotHistory(e) {

  var ctx = e.drawingContext;
  var points = e.points;
  ctx.fillStyle = e.color;
  // Do the actual plotting.
  for (var i = 0; i < points.length; i++) {
    var p = points[i];
    ctx.beginPath();
    ctx.arc(p.canvasx, p.canvasy, 2, 0, 2 * Math.PI, false);
    ctx.fill();
  }
  // verify points
  for (var i = 0; i < points.length; i++) {
    let p = points[i];
    if (!p || p.canvasy === undefined || isNaN(p.canvasy)) points[i] = null;
  }
  // draw a smoothed line
  ctx.beginPath();
  let past = 0;
  let future = 1;
  let max = points.length - 1;
  for (var i = 0; i <= max; i++) {
    let p = points[i];
    if (!p) continue;
    let sum = 0;
    let count = 0;
    let pos = i;
    let from = Math.max(0, i-past);
    while(--pos >= from) {
      let p2 = points[pos];
      if(!p2) break;
      sum += p2.canvasy;
      count++;
    }
    pos = i;
    let to = Math.min(max, i+future);
    while(pos <= to) {
      let p2 = points[pos];
      if(!p2) break;
      sum += p2.canvasy;
      count++;
      pos++;
    }
    let y = sum / count;
    if(i && !points[i-1]) {
      ctx.moveTo(p.canvasx, y);
    }
    else {
      ctx.lineTo(p.canvasx, y);
    }
  }
  ctx.globalAlpha = 0.15;
  ctx.stroke();

}

Dygraph.plotHistory = plotHistory;
})();
