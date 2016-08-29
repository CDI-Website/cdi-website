(function ($, Drupal) {
  var margin = {top: 0, right: 0, bottom: 0, left: 100};
  var width = $('#explorer-container').width();
  var height = $(document).height() * (4/5);
  var tree_depth = 180; /* Width of each level */
  var nodeHeight = 40;
  var nodeWidth = 40;
  var cache = {};

  var $depth_assessments_count = $('.js-depth-assessments-count');
  var $depth_assessments_link = $('.js-link-depth-assessments');
  var $short_assessments_count = $('.js-short-assessments-count');
  var $short_assessments_link = $('.js-link-short-assessments');
  var $images_count = $('.js-images-count');
  var $images_link = $('.js-link-images');
  var $data_count = $('.js-data-count');
  var $data_link = $('.js-link-data');
  var $resiliency_count = $('.js-resiliency-count');
  var $resiliency_link = $('.js-link-resiliency');
  var $others_count = $('.js-others-count');
  var $others_link = $('.js-link-others');
  var $info_modal = $('#info-modal');
  var $modal_title = $(".js-modal-title");
  var $modal_search_url = $(".js-search-url");
  var $modal_description = $(".js-summary");
  var $modal_themes = $('.js-related-themes');
  var $title = $('.js-title');
  var $related_data = $('.js-related-data');

  var i = 0,
    duration = 400,
    root,
    zoom,
    translate;

  var tree = d3.layout.tree().nodeSize([nodeWidth, nodeHeight]);

  var diagonal = d3.svg.diagonal()
    .projection(function(d) { return [d.y, d.x]; });

  var zoom = d3.behavior.zoom();
  zoom.translate([margin.left, height / 2]);
  /* Sets up the grid, adds zooming/panning */
  var svg = d3.select("#explorer").append("svg")
    .attr("width", width)
    .attr("height", height)
    .call(zoom.center([width / 2, height / 2]).scaleExtent([.25,2]).on("zoom", function () {
      svg.attr("transform", "translate(" + d3.event.translate + ")" + " scale(" + d3.event.scale + ")");
    }))
    .append("g")
    .attr("transform", "translate(" + margin.left + "," + height / 2 + ")");

  zoom.translate([margin.left, height / 2]);
  //Context Menu Options
  var menu = function(data) {
    var toReturn = [];
    toReturn.push({
      title: 'Expand/Retract',
      action: function(elm, d, i){
        toggle(d);
      }
    });
    toReturn.push({
      title: 'View Resource',
      action: function(elm, d, i){
        displayInfoModal(d);
      }
    });

    return toReturn;
  };

  $(document).ready(function(){
    $(document).ajaxStop($.unblockUI);

    $("button[data-action=\"zoom-in\"]").click(function(){
      zoom.scale(zoom.scale() * 1.25);
      svg.attr("transform", "translate(" + zoom.translate() + ")" + " scale(" + zoom.scale() + ")");
    });

    $("button[data-action=\"zoom-out\"]").click(function(){
      zoom.scale(zoom.scale() / 1.25);
      svg.attr("transform", "translate(" + zoom.translate() + ")" + " scale(" + zoom.scale() + ")");
    });

    $("button[data-action=\"re-center\"]").click(function(){
      restart(root);
      update(root);
    });
  });

  //Recursively collapse all children nodes
  function collapse(d) {
    if (d.children) {
      d.all_children = d.children;
      d._children = d.children;
      d._children.forEach(collapse);
      d.children = null;
      d.hidden = true;
    }
  }

  function setChildren(d){
    d.term = d.object_tree.term;
    if(d.object_tree.children && d.object_tree.children.length > 0){
      d.children = d.object_tree.children;
      d.all_children = d.object_tree.children;
      d.children.forEach(setChildren);
    }
  }

  function urldecode(str){
    return decodeURIComponent((str+'').replace(/\+/g, '%20'));
  }

  function start() {
    if(cache["tree"] == undefined) {
      $.blockUI({
        message: '<img style="display: inline;" src="/modules/custom/theme_explorer/images/loader.gif">',
        css: {
          border: 'none',
          padding: '15px',
          backgroundColor: 'rgba( 0, 0, 0, .5 )',
          '-webkit-border-radius': '10px',
          '-moz-border-radius': '10px',
          color: '#fff',
          width: '180px',
          left: '50%',
          transform: 'translateX( -50% )'
        }
      });
      var term = urldecode(getVariableFromURL("id"));
      if(term === undefined || term === ""){
        term = 'Health';
      }
      $title.text(term);
      $.ajax({
        url: "http://ec2-52-38-26-42.us-west-2.compute.amazonaws.com:8080/vocabulary/cdi/health/" + term + ".json",
        success: function (data) {
          root = data;
          cache["tree"] = JSON.parse(JSON.stringify(root));

          root.x0 = height / 2;
          root.y0 = 0;
          root.is_the_root = 1;
          root.all_children = root.children;
          root.children.forEach(setChildren);
          root.children.forEach(function(d) {
            d.hidden = false;
          });
          root.children.forEach(collapse);
          collapse(root);
          root.hidden = false;
          update(root);
          setTimeout(toggle(root), duration);
        },
        error: function() {
          alert(Drupal.t("The resource was not found."));
        }
      });
    }
    else {
      root = JSON.parse(JSON.stringify(cache["tree"]));
      root.x0 = height / 2;
      root.y0 = 0;

      root.is_the_root = 1;
      root.all_children = root.children;
      root.children.forEach(setChildren);
      root.children.forEach(function(d) {
        d.hidden = false;
      });
      root.children.forEach(collapse);
      collapse(root);
      root.hidden = false;

      update(root);
      setTimeout(toggle(root), duration);
    }
  }

  start();

  function update(source) {

    // Compute the new tree layout.
    var nodes = tree.nodes(root).filter(function(d) { return !d.hidden; }).reverse(),
      links = tree.links(nodes);

    // Normalize for fixed-depth.
    nodes.forEach(function(d) { d.y = d.depth * tree_depth; });

    // Update the nodes…
    var node = svg.selectAll("g.node")
      .data(nodes, function(d) { return d.id || (d.id = ++i); });

    // Enter any new nodes at the parent's previous position.
    var nodeEnter = node.enter().append("g")
      .attr("class", "node")
      .attr("transform", function(d) { return "translate(" + source.y0 + "," + source.x0 + ")"; })
      .on("mousedown", d3.contextMenu(menu));

    nodeEnter.append("path")
      .attr("d", hexagon(0,0,5))
      .attr("class", "hex");

    nodeEnter.append("text")
      .attr("dy", "2em")
      .attr("text-anchor", "middle")
      .attr("class", "node-text")
      .text(function(d) {
        if(d._children && d._children.length > 0)
          return d.term + " ( " + d._children.length + " )";
        return d.term;
      })
      .style("fill-opacity", 1);

    // Transition nodes to their new position.
    var nodeUpdate = node.transition()
      .duration(duration)
      .attr("transform", function(d) { return "translate(" + d.y + "," + d.x + ")"; });

    nodeUpdate.select("path.hex")
      .attr("d", hexagon(0,0,12))
      .attr("class", function(d){return d._children ? "hex full" : "hex empty";});

    nodeUpdate.select("text")
      .style("fill-opacity", 1);

    // Transition exiting nodes to the parent's new position.
    var nodeExit = node.exit().transition()
      .duration(duration)
      .attr("transform", function(d) { return "translate(" + source.y + "," + source.x + ")"; })
      .remove();

    nodeExit.select("path.hex")
      .attr("d", hexagon(0,0,5));

    nodeExit.select("text")
      .style("fill-opacity", 1);

    // Update the links…
    var link = svg.selectAll("path.link")
      .data(links, function(d) { return d.target.id; });

    // Enter any new links at the parent's previous position.
    link.enter().insert("path", "g")
      .attr("class", "link")
      .attr("d", function(d) {
        var o = {x: source.x0, y: source.y0};
        return diagonal({source: o, target: o});
      });

    // Transition links to their new position.
    link.transition()
      .duration(duration)
      .attr("d", diagonal);

    // Transition exiting nodes to the parent's new position.
    link.exit().transition()
      .duration(duration)
      .attr("d", function(d) {
        var o = {x: source.x, y: source.y};
        return diagonal({source: o, target: o});
      })
      .remove();

    // Stash the old positions for transition.
    nodes.forEach(function(d) {
      d.x0 = d.x;
      d.y0 = d.y;
    });
  }

  // Toggle children on click.
  function toggle(d) {

    if(!d.children && !d._children){
      return;
    }
    if (d.children) {
      d._children = d.children;
      d.children = null;
      if (d._children) {
        d._children.forEach(function(n) { n.hidden = true; });

        if (d.parent) {
          d.parent.children = d.parent.all_children;
          d.parent.children.forEach(function(n) {
            n.hidden = false;
          });
        }
      }
    } else {
      d.children = d._children;
      d._children = null;
      if (d.children) {
        d.children.forEach(function(n) { n.hidden = false; });

        if (d.parent) {
          d.parent.children = [d,];
          d.parent.children.filter(function(n) { return n !== d; }).forEach(function(n) {
            n.hidden = true;
          });
        }
      }
    }
    update(d);
  }

  function displayInfoModal(d){
    var term, description, children, related;

    if(d.is_the_root == 1){
      term = d.term;
      description = d.description;
      children = d.all_children;
      related = d.term_maps;
    }
    else{
      term = d.object_tree.term;
      description = d.object_tree.description;
      children = d.object_tree.children;
      related = d.object_tree.term_maps;
    }

    $modal_title.text(term);
    $modal_description.text(description);

    $modal_themes.empty();
    if(children != undefined && children.length > 0) {
      $modal_themes.append($('<h4>').text(Drupal.t("Related Themes")).addClass('entity-group-title'));

      var $term_list = $('<ul>').addClass("entity-related-terms js-themes")

      children.forEach(function (child) {
        var $term = $('<li>').addClass("entity-related-term");
        var $term_link = $('<a>').text(child.object_tree.term).attr('href', '/vocabulary/cdi/health?id=' + child.object_tree.term).addClass("btn btn-diamond");
        $term.append($term_link);
        $term_list.append($term);
      });

      $modal_themes.append($term_list);
    }

    $modal_search_url.attr("href", "/faceted-search?search_term=" + term);

    $counts = $('.js-counts');
    $counts.each(function(){
      if(!$(this).parent().parent().hasClass("hidden")){
        $(this).parent().parent().addClass("hidden");
      }
    });
    if($related_data.hasClass("hidden"))
      $related_data.removeClass("hidden");

    var facet_array = {
      "depth_assessments": ["report", "chapter", "finding", "book"],
      "short_assessments": ["article", "webpage"],
      "images": ["image", "figure"],
      "data": ["dataset"],
      "resiliency": ["toolkit", "case_study"],
      "others" : ["person", "organization", "vocabulary"]
    };

    var depth_assessments = 0,
      short_assessments = 0,
      images = 0,
      data = 0,
      resiliency = 0,
      others = 0;


    $.ajax({
      url: "http://ec2-52-38-26-42.us-west-2.compute.amazonaws.com:8080/search.json?q=" + term + "&type=all&count_only=1",
      success: function (counts) {

        counts.forEach(function(count){
          if(facet_array["depth_assessments"].includes(count.type)){
            depth_assessments += count.results_count;
          }
          else if(facet_array["short_assessments"].includes(count.type)){
            short_assessments += count.results_count;
          }
          else if(facet_array["images"].includes(count.type)){
            images += count.results_count;
          }
          else if(facet_array["data"].includes(count.type)){
            data += count.results_count;
          }
          else if(facet_array["resiliency"].includes(count.type)){
              resiliency += count.results_count;
          }
          else if(facet_array["others"].includes(count.type)){
            others += count.results_count;
          }

        });

        var flag = false;
        if(depth_assessments > 0){
          $depth_assessments_count.parent().parent().removeClass("hidden");
          $depth_assessments_count.text(depth_assessments);
          $depth_assessments_link.attr("href", "/faceted-search?search_term=" + term + "&facet=in_depth_assessments");
          flag = true;
        }

        if(short_assessments > 0){
          $short_assessments_count.parent().parent().removeClass("hidden");
          $short_assessments_count.text(short_assessments);
          $short_assessments_link.attr("href", "/faceted-search?search_term=" + term + "&facet=short_assessments");
          flag = true;
        }

        if(images > 0){
          $images_count.parent().parent().removeClass("hidden");
          $images_count.text(images);
          $images_link.attr("href", "/faceted-search?search_term=" + term + "&facet=images");
          flag = true;
        }

        if(data > 0){
          $data_count.parent().parent().removeClass("hidden");
          $data_count.text(data);
          $data_link.attr("href", "/faceted-search?search_term=" + term + "&facet=data");
          flag = true;
        }

        if(resiliency > 0){
          $resiliency_count.parent().parent().removeClass("hidden");
          $resiliency_count.text(resiliency);
          $resiliency_link.attr("href", "/faceted-search?search_term=" + term + "&facet=resiliency");
          flag = true;
        }

        if(others > 0){
          $others_count.parent().parent().removeClass("hidden");
          $others_count.text(others);
          $others_link.attr("href", "/faceted-search?search_term=" + term + "&facet=others");
          flag = true;
        }
        if(flag == false){
          $related_data.addClass("hidden");
        }
        $info_modal.modal('show');
      }
    });


  }

  function restart(){
    start();
  }

  function hexagon(x,y,r) {
    var x1 = x;
    var y1 = y-r;
    var x2 = x+(Math.cos(Math.PI/6)*r);
    var y2 = y-(Math.sin(Math.PI/6)*r);
    var x3 = x+(Math.cos(Math.PI/6)*r);
    var y3 = y+(Math.sin(Math.PI/6)*r);
    var x4 = x;
    var y4 = y+r;
    var x5 = x-(Math.cos(Math.PI/6)*r);
    var y5 = y+(Math.sin(Math.PI/6)*r);
    var x6 = x-(Math.cos(Math.PI/6)*r);
    var y6 = y-(Math.sin(Math.PI/6)*r);

    var path = "M"+x1+" "+y1+" L"+x2+" "+y2+" L"+x3+" "+y3+" L"+x4+" "+y4+" L"+x5+" "+y5+" L"+x6+" "+y6+"z";
    return path;
  }

  /* Function to get values from the URL */
  function getVariableFromURL(param) {
    var vars = {};
    window.location.href.replace( location.hash, "" ).replace(
      /[?&]+([^=&]+)=?([^&]*)?/gi,
      function ( m, key, value ) {
        vars[key] = value !== undefined ? value : "";
      }
    );

    if ( param ) {
      if(vars[param])
        return decodeURIComponent(vars[param]);
      else
        return "";
    }
    return vars;
  }


})(jQuery, Drupal);