(function ($, Drupal) {
  var url_base = "http://ec2-52-38-26-42.us-west-2.compute.amazonaws.com:8080";
  var item_limit_per_page = 6;
  var limit_text = 450;
  var hasSearchedBefore = false;
  var $grid = $(".grid");
  var $pagination = $("#pagination");
  var $filters_tabs = $("#filters-tabs");
  var $results_filter = $(".results-filter");
  var $filters_dropdown = $("#filters-dropdown");
  var $search_term = $("#search-term-field");
  var $search_button = $("#search-button");
  var $results_bucket = $(".results-bucket");
  var $info = $('.js-info');

  var facet_array = {
    "in_depth_assessments": ["report", "chapter", "finding", "book"],
    "short_assessments": ["article", "webpage"],
    "images": ["image", "figure"],
    "data": ["dataset"],
    "resiliency" : ["toolkit", "case_study"],
    "others" : ["person", "organization", "vocabulary"]
  };

  var facet_array_labels = {
    "in_depth_assessments": Drupal.t("In-Depth Assessments"),
    "short_assessments": Drupal.t("Short Assessments"),
    "images": Drupal.t("Images/Figures"),
    "data": Drupal.t("Datasets"),
    "resiliency" : Drupal.t("Resiliency"),
    "others" : Drupal.t("Others")
  };

  var plural_facet_labels = {
    "report" : Drupal.t("reports"),
    "chapter" : Drupal.t("chapters"),
    "finding" : Drupal.t("findings"),
    "book" : Drupal.t("books"),
    "article" : Drupal.t("articles"),
    "webpage" : Drupal.t("webpages"),
    "image" : Drupal.t("images"),
    "figure" : Drupal.t("figures"),
    "dataset" : Drupal.t("datasets"),
    "organization" : Drupal.t("organizations"),
    "person" : Drupal.t("people"),
    "toolkit" : Drupal.t("toolkits"),
    "vocabulary" : Drupal.t("vocabularies"),
    "case_study" : Drupal.t("case studies")
  };

  var result_counts = {};
  $.each(facet_array, function (key, value){
    result_counts[key + "_count"] = 0;
  });
  result_counts["total_count"] = 0;

  var masonryOptions;

  $(document).ready(function () {
    $(document).ajaxStop($.unblockUI);

    $(window).on('popstate', function(event){
      var state = event.originalEvent.state;
      console.log(state);
      $search_term.val(state.term);
      selectFacet(state.facet);
      search(state.term, state.page, JSON.parse(LZString.decompress(state.data)));
    });

    /* Handles the facet click */
    $filters_tabs.on("shown.bs.tab", function () {
      search($search_term.val(), 1);
      $filters_dropdown.find('[selected="selected"]').removeAttr('selected');
      $filters_dropdown.find('[data-facet="' + getSelectedFacetName() + '"]').attr('selected', 'selected');
    });

    masonryOptions = {
      itemSelector: ".grid-item",
      columnWidth: ".col-md-6",
      percentPosition: true
    };

    $grid.masonry( masonryOptions );

    $grid.imagesLoaded( function() {
      $grid.masonry();
    });

    $search_term.val(urldecode(getVariableFromURL("search_term")));

    /*Handles the search button click */
    $search_button.submit(function (e) {
      e.preventDefault();
      if ($search_term.val() != "") {
        search($search_term.val(), 1);
      }
    });

    /*Handles the initial page load with parameters */
    if ($search_term.val() != "") {
      var pageNumber = getVariableFromURL("page");
      if(pageNumber == ""){
        pageNumber = 1;
      }
      else{
        pageNumber = parseInt(pageNumber);
        if(pageNumber === NaN || pageNumber < 1)
          pageNumber = 1;
      }
      var facet = getVariableFromURL("facet");
      if(facet == ""){
        facet = "in_depth_assessments";
      }
      selectFacet(facet);

      search($search_term.val(), pageNumber);
    }

    /* Handles filter select change */
    $filters_dropdown.on('change', function() {
      var selected_facet = $(this).find('option:selected').data('facet');
      $filters_tabs.find('[data-facet="' + selected_facet + '"]').trigger('click');
      updateInfoText(selected_facet);
    });
  });

  /* Try to select the facet from param, if it's not one of the buckets, select first bucket */
  function selectFacet(facetName){
    $results_filter.removeClass("active");
    if(facetName in facet_array) {
      $filters_tabs.find("[data-facet=\"" + facetName + "\"]").addClass("active");
    }
    else $('#filters-tabs li:not(.hidden):first').addClass("active");
  }

  function urldecode(str){
    return decodeURIComponent((str+'').replace(/\+/g, '%20'));
  }

  function updateInfoText(facetName){
    if(facetName == undefined){
      $info.addClass("hidden");
    }
    else {
      var text = facet_array_labels[facetName] + " contains ";
      if (facet_array[facetName].length == 1) {
        text += humanize(pluralize(facet_array[facetName][0])) + ".";
      }
      else if (facet_array[facetName].length == 2) {
        text += humanize(pluralize(facet_array[facetName][0])) + " and " + humanize(pluralize(facet_array[facetName][1]) + ".");
      }
      else {
        $.each(facet_array[facetName], function (index, facet) {
          if (index != facet_array[facetName].length - 1)
            text += humanize(pluralize(facet)) + ", ";
          else
            text += "and " + humanize(pluralize(facet)) + ".";
        });
      }
      $('.js-facet-description').text(text);
      $info.removeClass("hidden");
    }
  }


  function humanize(str) {
    var frags = str.split('_');
    for (i=0; i<frags.length; i++) {
      frags[i] = frags[i].charAt(0).toUpperCase() + frags[i].slice(1);
    }
    return frags.join(' ');
  }

  function pluralize(str){
    return plural_facet_labels[str];
  }

  function getSelectedFacetName() {
    return $filters_tabs.find(".active").attr("data-facet");
  }

  function getSelectedFacetArray() {
    if(getSelectedFacetName() == undefined)
      return facet_array["in_depth_assessments"];
    return facet_array[getSelectedFacetName()];

  }

  /*Search database by search term and type */
  function search(search_term, page, data) {

    $results_bucket.each(function () {
      if (!$(this).hasClass("hidden")) {
        $(this).addClass("hidden");
      }
    });

    $pagination.addClass("hidden");
    if (data === undefined) {
      var typeQuery = getSelectedFacetArray().join("&type=");
      var encodedSearchTerm = encodeURI(search_term);
      /*Blocks UI during the ajax request */
      $.blockUI({
        message: '<img style="display: inline;" src="/modules/custom/faceted_search/images/loader.gif">',
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
      $.when(
        $.get(url_base + "/search.json?q=" + encodedSearchTerm + "&format=brief&with=files&type=" + typeQuery + "&per_page=" + item_limit_per_page + "&page=" + page),
        $.get(url_base + "/search.json?q=" + encodedSearchTerm + "&format=brief&with=files&type=all&count_only=1"),
        $.get(url_base + "/search.json?q=" + encodedSearchTerm + "&format=brief&with=files&type=" + typeQuery + "&featured_only=1")
      ).then(function (search_results, counts, featured) {
        data = {};
        var all_facets = [];
        $.each(facet_array, function(facets) {
          all_facets = all_facets.concat(facet_array[facets]);
        });

        var filteredCounts = [];
        counts[0].forEach(function(count){
          if($.inArray(count.type, all_facets) > -1){
            filteredCounts.push({
              'results_count' : count.results_count,
              'type' : count.type
            });
          }
        });

        /* Cache Counts */
        data.counts = filteredCounts;

        /* Cache Results */
        var results = {};
        //Only use featured results on first page
        if(page == 1) {
          featured[0].forEach(function (item) {
            if (results[item.type] === undefined) {
              results[item.type] = [];
            }
            item.featured = "featured";
            results[item.type].push(item);
          });
        }

        search_results[0].forEach(function(item){
          if($.inArray(item.type, all_facets) > -1) {
            if (results[item.type] === undefined) {
              results[item.type] = [];
            }
            results[item.type].push(item);
            return results;
          }
        });
        data.results = results;

        /* Looks like duplicated code, but need to happen sequentially in the when statement */
        var stateObject = {
          "data" : LZString.compress(JSON.stringify(data)),
          "page" : parseInt(page),
          "term" : search_term,
          "facet" : getSelectedFacetName()
        };
        if(hasSearchedBefore)
          history.pushState(stateObject, null, "?search_term=" + search_term + '&page=' + page + '&facet=' + getSelectedFacetName());
        else
          history.replaceState(stateObject, null, "?search_term=" + search_term + '&page=' + page + '&facet=' + getSelectedFacetName());
        updateResultTotals(data.counts, search_term, page);
        updateResults(data.results, getSelectedFacetArray());
        updateInfoText(getSelectedFacetName());
        hasSearchedBefore = true;
      });
    }
    else{
      updateResultTotals(data.counts, search_term, page);
      updateResults(data.results, getSelectedFacetArray());
      updateInfoText(getSelectedFacetName());
      hasSearchedBefore = true;
    }

  }


  /*Update the results label counters */
  function updateResultTotals(counts, search_term, page) {
    /*Handle the count updates */
    result_counts.total_count = 0;

    /*Initialize counters and max variables to 0 */
    $.each(facet_array, function (key, value){
      result_counts[key + "_count"] = 0;
      result_counts[key + "_max"] = 0;
    });

    /*Calculate the max facet and count totals for each bucket*/
    counts.forEach(function (item) {
      var type = item.type;
      $("span[data-count=\"" + type + "\"]").text(item.results_count);
      $.each(facet_array, function (key, value){
        if($.inArray(type, value) > -1){
          if(result_counts[key + "_max"] < item.results_count){
            result_counts[key + "_max"] = item.results_count;
          }
          result_counts[key + "_count"] += item.results_count;
          result_counts.total_count += item.results_count;
        }
      });
    });

    /* No results found */
    if (result_counts.total_count == 0) {
      $("span[data-count=\"none\"]").text(search_term + ".");
      $("#results_none").parent().removeClass("hidden");
    }

    $('.results-filter').removeClass("hidden");

    /*Update the count label for each facet in the facet_array */
    $.each(facet_array, function (key, value) {
      var count_text = " (" + result_counts[key + "_count"] + ")";
      var $tab = $(".js-count_" + key);
      var $option = $filters_dropdown.find('[data-facet=' + key + ']');

      $tab.text( count_text );
      $option.text( $option.data('name') + count_text );

      if(result_counts[key + "_count"] == 0){
        var bucket = $('.js-bucket_' + key);
        if(!bucket.hasClass("hidden")){
          bucket.addClass("hidden");
        }
      }
    });

    //Check in case there were no active tabs, ie. there were no results
    if($filters_tabs.find('.active').length == 0 ){
      $('#filters-tabs li:first').addClass("active");
    }
    //Find first bucket with results and set it active
    if($filters_tabs.find('.active').hasClass("hidden")) {
      $results_filter.removeClass("active");
      $first = $('#filters-tabs li:not(.hidden):first');
      $first.addClass("active");
    }


    $("#result-totals").html(result_counts.total_count + " results for <strong>" + search_term + "</strong>" );

    /*Display pagination, but only when there are actually results */
    if ($pagination.hasClass("hidden") && result_counts[getSelectedFacetName() + "_max"] > 0) {
      var facetName = getSelectedFacetName();
      var num_pages = Math.ceil(result_counts[facetName + "_max"] / item_limit_per_page);
      if(page > num_pages){
        search(search_term, 1);
        return;
      }
      /*Handles pagination click */
      if($pagination.data("twbs-pagination")){
        $pagination.twbsPagination('destroy');
      }

      $pagination.twbsPagination({
        startPage: parseInt(page),
        totalPages: num_pages,
        visiblePages: 6,
        initiateStartPageClick: false,
        onPageClick: function(event, p){
          search(search_term, p);
        }
      });
      $pagination.removeClass("hidden");
    }

    return result_counts.total_count;
  }

  /*The information to display for each type */
  function itemInfo(data) {
    var $grid_item = $("<li>").addClass("grid-item col-xs-12 col-md-6");
    var $grid_item_link;
    if(data.type === 'article'){
      $grid_item_link = $("<a>").attr("href", "/content/article?id=" + encodeURIComponent(data.identifier)).addClass("grid-link");
    }
    else if(data.type === 'vocabulary'){
      $grid_item_link = $("<a>").attr("href", "/vocabulary?id=" + encodeURIComponent(data.identifier)).addClass("grid-link");
    }
    else if(data.type === 'case_study' || data.type === 'toolkit'){
      $grid_item_link = $("<a>").attr("href", data.url).attr("target", "_blank").addClass("grid-link");
    }
    else {
      $grid_item_link = $("<a>").attr("href", "/content" + encodeURI(data.uri)).addClass("grid-link");
    }

    if(data.featured && data.featured === "featured"){
      $grid_item.addClass("grid-item-featured");
    }

    var images = data.files;

    if (images !== undefined && images.length > 0)
    {
      $grid_item_link.append($("<img>").attr("src", images[0].thumbnail_href).addClass("grid-thumbnail"));
    }
    $grid_item_link.append($("<h3>").text(data.display_name).addClass("grid-title"));
    $grid_item_link.append($("<div>").text(data.description).addClass("grid-content")).succinct({size: limit_text});
    $grid_item.append($grid_item_link);
    return $grid_item;
  }

  /*Pass selected facets to show results for */
  function updateResults(data, selected_facets) {
    if(selected_facets != undefined) {
      selected_facets.forEach(function (facet) {
        if (data[facet] !== undefined) {
          if (data[facet].length > 0) {
            $results = $("ul[data-results=\"" + facet + "\"]");
            $results.parent().removeClass("hidden");
            $results.masonry("remove", $results.find(".grid-item"));
            $results.masonry();
            for (var i = 0; i < data[facet].length; i++) {
              var $item = itemInfo(data[facet][i]);
              $results.append($item);
              $results.masonry("appended", $item);
              $results.imagesLoaded(function () {
                $results.masonry();
              });

              $grid.imagesLoaded(function () {
                $grid.masonry();
              });
            }
          }
        }
      });
    }
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