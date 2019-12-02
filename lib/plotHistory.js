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
  // draw a smoothed line
  ctx.beginPath();
  let lastPoint = null;
  let sum = 0;
  let pos = 0;
  let smooth = 4;
  for (var i = 0; i < points.length; i++) {
    let p = points[i];
    p = (p && !!p.canvasy && !isNaN(p.canvasy))?p:null;
    if (!p) {
      sum = pos = 0;
    }
    if(!lastPoint) {
      if(p) ctx.moveTo(p.canvasx, p.canvasy);
    }
    else if(p) {
      if (pos == smooth) {
        sum -= points[i - pos].canvasy;
        sum += p.canvasy;
      }
      else {
        pos++;
        sum += p.canvasy;
      }
      let y = sum / pos;
      ctx.lineTo(p.canvasx, y);
    }
    lastPoint = p;
  }
  ctx.globalAlpha = 0.15;
  ctx.stroke();

}

Dygraph.plotHistory = plotHistory;
})();
