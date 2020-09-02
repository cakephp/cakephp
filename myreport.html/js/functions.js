function loadJSON(filename, callback) {

    var xobj = new XMLHttpRequest();
    xobj.overrideMimeType("application/json");
    xobj.open('GET', filename, true);
    xobj.onreadystatechange = function () {
        if (xobj.readyState == 4 && xobj.status == "200") {
            // Required use of an anonymous callback as .open will NOT return a value but simply returns undefined in asynchronous mode
            callback(xobj.responseText);
        }
    };
    xobj.send(null);
}


function equalsHeightOf(node1, node2) {
    var w1 = node1.style.height;
    node2.style.height = w1 + 'px';
}

function saveSvgAsImage(svg, name, width, height) {
    width = width || 600;
    height = height || 600;
    var img = new Image(),
      serializer = new XMLSerializer(),
      svgStr = serializer.serializeToString(svg);

    img.src = 'data:image/svg+xml;base64,' + window.btoa(svgStr);
    var canvas = document.createElement("canvas");
    document.body.appendChild(canvas);
    canvas.width = width;
    canvas.height = height;
    img.onload = function () {
        canvas.getContext("2d").drawImage(img,0,0, width, height);
        canvas.toBlob(function (blob) {
            saveAs(blob, name + ".png");
        });
    };
    canvas.parentNode.removeChild(canvas);
}