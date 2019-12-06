(function($) {
    $.fn.instantSearch = function(config) {
        return this.each(function() {
            initInstantSearch(this, $.extend(true, defaultConfig, config || {}));
        });
    };

    var defaultConfig = {//default fallback config
        minQueryLength: 3,//minimal input
        maxPreviewItems: 10,//max returned items
        previewDelay: 500,//delay before display, in milisec
        noItemsFoundMessage: 'No results found.'//displayed text
    };

    $searchField = $('.search-field');

    defaultConfig = {
        minQueryLength: $searchField.attr('data-min-input-length'),
        maxPreviewItems: $searchField.attr('data-max-prewiew-items'),
        previewDelay: $searchField.attr('data-prewiew-delay'),
        noItemsFoundMessage: $searchField.attr('data-noresult-text')
    };

    function debounce(fn, delay) {

        var timer = null;
        return function () {
            var context = this, args = arguments;
            clearTimeout(timer);
            $('#results').text(' ');
            timer = setTimeout(function () {
                fn.apply(context, args);
            }, delay);
        };
    }

    var initInstantSearch = function(el, config) {
        var $input = $(el);
        var $form = $input.closest('form');
        var $preview = $('<ul class="search-preview list-group">').appendTo($form);


        var setPreviewItems = function(items) {
            $preview.empty();

            $.each(items, function(index, item) {
                if (index > config.maxPreviewItems) {
                    return;
                }

                addItemToPreview(item);
            });
        }

        var addItemToPreview = function(item) {

            var $link = $('<a>').attr('href', item.url).text(item.title);
            var $calendar = $('<i>').attr('class', 'fa fa-calendar');
            var $date = $('<span>').attr('class', 'media-meta pull-right small').text(item.date).prepend($calendar);
            var $title = $('<h3>').attr('class', 'm-b-0').append($link).append($date);
            var $preview = $('<p>').html(item.preview).text();
            var $result = $('#results');

            $result.append($title).append($preview);
        }

        var noItemsFound = function() {

            var $result = $('#results').text(config.noItemsFoundMessage);

            $preview.empty();
            $preview.append($result);
        }

        var updatePreview = function() {

            var query = $.trim($input.val()).replace(/\s{2,}/g, ' ');

            if (query.length < config.minQueryLength) {
                $preview.empty();
                return;
            }

            $.getJSON($form.attr('action') + '?' + $form.serialize(), function(items) {

                if (items.length === 0) {
                    noItemsFound();
                    return;
                }

                setPreviewItems(items);

            });
        }

        $input.focusout(function(e) {
            $preview.fadeOut();
        });

        $input.focusin(function(e) {
            $preview.fadeIn();
            updatePreview();
        });

        $input.keyup(debounce(updatePreview, config.previewDelay));
    }
})(window.jQuery);

$(function() {
    $('.search-field').instantSearch();
});
