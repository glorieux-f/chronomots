window.onload = function()

{
  document.onselectstart = function() {return false;} // ie
  document.onmousedown = function() {return false;} // mozilla
}

window.onload = function()
{
  var element = document.getElementById('content');
  element.onselectstart = function () { return false; } // ie
  element.onmousedown = function () { return false; } // mozilla
}
