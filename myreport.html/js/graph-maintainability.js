function chartMaintainability(withoutComment)
{
    var chartId = withoutComment ? 'svg-maintainability-without-comments' : 'svg-maintainability';

    var diameter = document.getElementById(chartId).offsetWidth;

    var json = {
        name: 'chart',
        children: classes
    };

    var svg = d3.select('#' + chartId).append('svg')
        .attr('width', diameter)
        .attr('height', diameter);

    var bubble = d3.layout.pack()
        .size([diameter, diameter])
        .padding(3)
        .value(function (d) {
            return d.ccn;
        });

    var nodes = bubble.nodes(json)
        .filter(function (d) {
            return !d.children;
        }); // filter out the outer bubble*

    var vis = svg.selectAll('circle')
        .data(nodes, function (d) {
            return d.name;
        });

    vis.enter().append('circle')
        .attr('transform', function (d) {
            return 'translate(' + d.x + ',' + d.y + ')';
        })
        .attr('r', function (d) {
            return d.r;
        })
        .style("fill", function (d) {
            if (true === withoutComment) {
                if (d.mIwoC > 65) {
                    return '#8BC34A';
                } else if (d.mIwoC > 53) {
                    return '#FFC107';
                } else {
                    return '#F44336';
                }
            } else {
                if (d.mi > 85) {
                    return '#8BC34A';
                } else if (d.mi > 69) {
                    return '#FFC107';
                } else {
                    return '#F44336';
                }
            }
        })
        .on('mouseover', function (d) {
            var text = '';
            if (true === withoutComment) {
                text = '<strong>' + d.name + '</strong>'
                    + "<br />Cyclomatic Complexity : " + d.ccn
                    + "<br />Maintainability Index (w/o comments): " + d.mIwoC;
            } else {
                text = '<strong>' + d.name + '</strong>'
                    + "<br />Cyclomatic Complexity : " + d.ccn
                    + "<br />Maintainability Index: " + d.mi;
            }
            d3.select('.tooltip').html(text);
            d3.select(".tooltip")
                .style("opacity", 1)
                .style("z-index", 1);
        })
        .on('mousemove', function () {
            d3.select(".tooltip")
                .style("left", (d3.event.pageX + 5) + "px")
                .style("top", (d3.event.pageY + 5) + "px");
        })
        .on('mouseout', function () {
            d3.select(".tooltip")
                .style("opacity", 0)
                .style("z-index", -1);
        });

    d3.select("body")
        .append("div")
        .attr("class", "tooltip")
        .style("opacity", 0);

    // button for saving image
    var button = d3.select('#' + chartId).append('button');
    button
      .classed('btn-save-image', true)
      .text('download')
      .on('click', function () {
        var svg = d3.select('#' + chartId + ' svg')[0][0];
        var nameImage = (withoutComment)
            ? 'PhpMetrics maintainability without comments / complexity'
            : 'PhpMetrics maintainability / complexity';
        saveSvgAsImage(svg, nameImage, 1900, 1900);
      });
}
