function chartLicenses(json) {

    var diameter = document.getElementById('svg-licenses').offsetWidth;

    var svg = d3.select('#svg-licenses').append('svg'),
        width = 300,//document.getElementById('svg-licenses').offsetWidth,
        height = 300,//document.getElementById('svg-licenses').offsetWidth,
        radius = Math.min(width, height) / 2;

    //var r = 300; // outer radius

    var color = d3.scale.ordinal()
        .range(["#BBDEFB", "#90CAF9", "#64B5F6", "#42A5F5", "#2196F3", "#1E88E5", "#1976D2", "#1565C0", "#0D47A1"]);

    svg
        .attr("width", width)
        .attr("height", height);

    var group = svg.append("g")
        .attr("transform", "translate(" + Math.ceil(width / 2) + ", " + Math.ceil(height / 2) + ")"); // set center of pie

    var arc = d3.svg.arc()
        .innerRadius(radius - 10)
        .outerRadius(0);

    var pie = d3.layout.pie()
        .value(function (d) {
            return d.value;
        });

    var arcs = group.selectAll(".arc")
        .data(pie(json))
        .enter()
        .append("g")
        .attr("class", "arc");

    arcs.append("path")
        .attr("d", arc) // here the arc function works on every record d of data
        .attr("fill", function (d) {
            return color(d.data.value);
        });

    arcs.append("text")
        .attr("transform", function (d) {
            return "translate(" + arc.centroid(d) + ")";
        })
        .attr("text-anchor", "middle")
        .attr('color', '#FFF')
        .text(function (d) {
            return d.data.name;
        });
}