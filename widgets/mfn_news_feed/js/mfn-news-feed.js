jQuery(document).ready(function($) {
    var payload = mfn_news_feed_params.payload || [];
    var query = JSON.parse(payload.query) || '';
    var request_url = mfn_news_feed_params.request_url;
    var dropdown = $(".dropdown");

    (function loadState() {
        $(".dropdown .option").each(function () {
            if ($(this).data("option") === query["m-tags"]) {
                $(this).hide();
            }
        });
        var checkTags = ['regulatory', '-regulatory'];

        if (checkTags.includes(query["m-tags"])) {
            if (query["m-tags"] === "regulatory") {
                $(".dropdown span div").text("Regulatory");
                $(".dropdown ul li").filter('[data-option="regulatory"]').addClass("mfn-tag-selected");
            } else if (query["m-tags"] === "-regulatory") {
                $(".dropdown  span div").text("Non-Regulatory");
                $(".dropdown ul li").filter('[data-option="-regulatory"]').addClass("mfn-tag-selected");
            }
        }
    })();

    function handleTags(filterByTags) {

            /*
                        var hasTags = [];
                        var hasNotTags = [];

                        filterByTags.split(',').forEach(function (tag) {
                            if (tag === '') {
                                return
                            }
                            if (tag.indexOf('-') === 0 || tag.indexOf('!') === 0) {
                                tag = tag.substring(1);
                                if (tag.indexOf('-') !== 0) {
                                    tag = 'mfn-' + tag;
                                }
                                hasNotTags.push(tag);
                                return
                            }
                            if (tag.indexOf('mfn-') !== 0) {
                                tag = 'mfn-' + tag;
                            }
                            hasTags.push(tag);
                        });
            */

            payload.hasTags = '';
            payload.hasNotTags = '';

            if (filterByTags === 'regulatory') {
                payload.hasTags = "mfn-regulatory";
            } else if (filterByTags === '-regulatory') {
                payload.hasNotTags = "mfn-regulatory";
            }

    }



    function _refresh(filterByTags, filterByYear, instance_id) {

        var currentState = {
            [instance_id]: {
                filterByTags: filterByTags,
                filterByYear: filterByYear
            }
        }

        // handle tags pre request
        handleTags(currentState[instance_id].filterByTags);

        payload.year = currentState[instance_id].filterByYear;

        alert(payload.hasTags + payload.hasNotTags + payload.year)

        jQuery.ajax({
            url: request_url,
            type: "GET",
            data: {
                payload: payload,
                instance_id: instance_id
            },
            success: function (response) {
               var obj = JSON.parse(response);
               $('#mfn-list-' + instance_id).html(obj.html);
            },
            error: function (e) {
                return "There was an error refreshing the news items: " + e;
            }
        });
    }

    // on click outside
    $(this).click(function() {
        if (dropdown.not($(this))) dropdown.children("ul").removeClass('mfn-open');
    });

    function updateDropdownState(e, instance_id) {
        // update selected text
        $(".dropdown[data-mfn-id='" + instance_id + "'] span div").text(e.text());
        // update query param
        query['m-tags'] = e.data('option');
        // push state
        history.pushState(null, null, obj_to_querystring(query))
    }

    function obj_to_querystring(query) {
        return "?" + Object.keys(query).map(function(key) {
            return key + '=' + query[key]
        }).join('&');
    }

    // on dropdown click
    dropdown.unbind().click(function (event) {
        var instance_id = $(this).data("mfn-id");
        event.stopPropagation();
        var ul = $(this).children("ul");
        ul.toggleClass('mfn-open');
        $(".dropdown-arrow-wrapper").toggleClass("rotate-arrow");
        // on click dropdown item
        ul.children("li").unbind().click(function (event) {
            event.preventDefault();

            ul.children("li").not($(this)).show();
            $(this).hide();

            ul.children("li").removeClass("mfn-tag-selected");
            $(this).addClass("mfn-tag-selected");

            var option = $(this).data('option');

            // call ajax refresh
            _refresh(option, query['m-year'], instance_id);
            updateDropdownState($(this), instance_id);
        });
    });

    // on year selector click
    $(".mfn-newsfeed-year-selector a").unbind().on("click", function (event) {
        event.preventDefault();
        var instance_id = $(this).closest("div").data('mfn-id');
        var current_tag = $(".dropdown[data-mfn-id='"+instance_id+"']").find("li.mfn-tag-selected").data('option') || '';
        $(".mfn-newsfeed-year-selector a").removeClass("mfn-year-selected");
        $(this).addClass("mfn-year-selected");

        // update query param
        query['m-year'] = $(this).text();

        // push state
        history.pushState(null, null, obj_to_querystring(query))

        // call ajax refresh
        _refresh(current_tag, $(this).text(), instance_id);
    })
});