/**
 * Taskify Date Picker Module
 * Provides centralized daterangepicker initialization for both
 * single date inputs and date range filters with preset ranges.
 *
 * @version 1.1.0
 * @author Taskify Development Team
 */

(function (window) {
    'use strict';

    /**
     * Get parent element for daterangepicker to ensure correct positioning in modals/offcanvas
     * @param {jQuery} $input
     * @returns {jQuery}
     */
    function getParentEl($input) {
        var $parentOverlay = $input.closest(".modal, .offcanvas");
        return $parentOverlay.length ? $parentOverlay : $(document.body);
    }

    /**
     * Standardized Locale Settings
     */
    function getStandardLocale(customLocale) {
        return $.extend({
            format: window.js_date_format || 'YYYY-MM-DD',
            separator: ' - ',
            applyLabel: window.label_apply || 'Apply',
            cancelLabel: window.label_cancel || 'Cancel',
            fromLabel: window.label_from || 'From',
            toLabel: window.label_to || 'To',
            customRangeLabel: window.label_custom_range || 'Custom Range',
            weekLabel: 'W',
            daysOfWeek: moment.weekdaysMin(),
            monthNames: moment.monthsShort(),
            firstDay: moment.localeData().firstDayOfWeek()
        }, customLocale || {});
    }

    /**
     * Standardized Preset Ranges
     */
    function getStandardRanges() {
        var labels = {
            today: window.label_today || 'Today',
            yesterday: window.label_yesterday || 'Yesterday',
            last7Days: window.label_last_7_days || 'Last 7 Days',
            last30Days: window.label_last_30_days || 'Last 30 Days',
            thisMonth: window.label_this_month || 'This Month',
            lastMonth: window.label_last_month || 'Last Month',
            thisYear: window.label_this_year || 'This Year',
            lastYear: window.label_last_year || 'Last Year'
        };

        var ranges = {};
        ranges[labels.today] = [moment(), moment()];
        ranges[labels.yesterday] = [moment().subtract(1, 'days'), moment().subtract(1, 'days')];
        ranges[labels.last7Days] = [moment().subtract(6, 'days'), moment()];
        ranges[labels.last30Days] = [moment().subtract(29, 'days'), moment()];
        ranges[labels.thisMonth] = [moment().startOf('month'), moment().endOf('month')];
        ranges[labels.lastMonth] = [
            moment().subtract(1, 'month').startOf('month'),
            moment().subtract(1, 'month').endOf('month')
        ];
        ranges[labels.thisYear] = [moment().startOf('year'), moment().endOf('year')];
        ranges[labels.lastYear] = [
            moment().subtract(1, 'year').startOf('year'),
            moment().subtract(1, 'year').endOf('year')
        ];

        return ranges;
    }

    /**
     * Initialize standardized Date Range Picker (Filters)
     */
    window.initAdvancedDateRangePicker = function (config) {
        console.log(config);

        if (!config || !config.selector) return;

        var $input = $(config.selector);
        if (!$input.length) return;

        // Destroy existing instance
        if ($input.data('daterangepicker')) {
            $input.data('daterangepicker').remove();
            $input.off('.daterangepicker');
        }

        var locale = getStandardLocale(config.locale);
        var options = {
            autoUpdateInput: false,
            autoApply: config.autoApply || false,
            showDropdowns: config.showDropdowns !== false,
            alwaysShowCalendars: true,
            ranges: getStandardRanges(),
            locale: locale,
            opens: config.opens || 'left',
            parentEl: getParentEl($input)
        };

        if (config.maxSpan) options.maxSpan = { days: config.maxSpan };
        if (config.minDate) options.minDate = config.minDate;
        if (config.maxDate) options.maxDate = config.maxDate;

        $input.daterangepicker(options, config.callback);

        $input.on('apply.daterangepicker', function (ev, picker) {
            var start = picker.startDate.format('YYYY-MM-DD');
            var end = picker.endDate.format('YYYY-MM-DD');

            if (config.hiddenFrom) $(config.hiddenFrom).val(start);
            if (config.hiddenTo) $(config.hiddenTo).val(end);

            $(this).val(
                picker.startDate.format(locale.format) +
                locale.separator +
                picker.endDate.format(locale.format)
            );

            if (config.tableId && $('#' + config.tableId).length) {
                $('#' + config.tableId).bootstrapTable('refresh');
            }

            $(this).trigger('daterange:applied', [start, end]);
        });

        $input.on('cancel.daterangepicker', function (ev, picker) {
            $(this).val('');
            if (config.hiddenFrom) $(config.hiddenFrom).val('');
            if (config.hiddenTo) $(config.hiddenTo).val('');

            if (config.tableId && $('#' + config.tableId).length) {
                $('#' + config.tableId).bootstrapTable('refresh');
            }
            $(this).trigger('daterange:cancelled');
        });

        return $input.data('daterangepicker');
    };

    /**
     * Initialize standardized Single Date Picker (Inputs)
     */
    window.initSingleDatePicker = function (config) {
        if (!config || !config.selector) return;

        var $input = $(config.selector);
        if (!$input.length) return;

        // Destroy existing instance
        if ($input.data('daterangepicker')) {
            $input.data('daterangepicker').remove();
            $input.off('.daterangepicker');
        }

        var isInsideOverlay = $input.closest(".modal, .offcanvas").length > 0;
        var locale = getStandardLocale(config.locale);
        var options = {
            singleDatePicker: true,
            showDropdowns: true,
            autoUpdateInput: !isInsideOverlay && $input.attr("data-defaultDate") !== "false",
            locale: locale,
            parentEl: getParentEl($input)
        };

        if (config.minDate) options.minDate = config.minDate;
        if (config.maxDate) options.maxDate = config.maxDate;
        if ($input.val() !== "") {
            options.startDate = moment($input.val(), locale.format);
        } else if (options.autoUpdateInput) {
            $input.val(moment().format(locale.format));
        }

        $input.daterangepicker(options, config.callback);

        $input.on('apply.daterangepicker', function (ev, picker) {
            $(this).val(picker.startDate.format(locale.format));
            $(this).trigger('change');
        });

        $input.on('cancel.daterangepicker', function () {
            $(this).val('');
            $(this).trigger('change');
        });

        return $input.data('daterangepicker');
    };

    /**
     * Clear date range filters helper
     */
    window.clearDateRangeFilters = function (prefix) {
        var filterTypes = ['date_between', 'start_date_between', 'end_date_between'];
        filterTypes.forEach(function (type) {
            var $input = $('#' + prefix + '_' + type);
            if ($input.length && $input.data('daterangepicker')) {
                $input.val('').trigger('cancel.daterangepicker');
            }
        });
    };

})(window);
