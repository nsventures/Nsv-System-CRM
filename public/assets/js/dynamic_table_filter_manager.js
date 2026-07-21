class TableFilterSync {
    constructor(config) {
        this.tableId = config.tableId;
        this.dataType = config.dataType || config.tableId.replace('_table', '');
        this.filters = config.filters || [];
        this.preserveParams = config.preserveParams || [];
        this.debounceMs = config.debounceMs || 300;
        this.queryParamsFn = config.queryParamsFn || this.defaultQueryParams.bind(this);
        this.debug = true;
        this.updateTimeout = null;
        this.lastParams = null;
        this.$table = $(`#${this.tableId} `);
        this.filterCache = new Map();
        if (!this.$table.length) {
            console.warn(`[TableFilterSync] Table ${this.tableId} not found`);
            return;
        }
        $(document).ready(() => {
            this.init();
        });
    }

    init() {
        if (!this.$table.length) return;
        this.waitForDependencies(() => {
            this.setFiltersFromUrl();
            this.attachEventListeners();
            this.attachPaginationListeners();
            window.addEventListener('popstate', () => this.setFiltersFromUrl());
        });
    }

    waitForDependencies(callback) {
        if (!this.filters.length) {
            callback();
            return;
        }
        const checkInterval = 100;
        const maxAttempts = 50;
        let attempts = 0;
        const check = () => {
            let allReady = true;
            for (const filter of this.filters) {
                const $selector = this.getCachedSelector(filter.selector);
                if ($selector.length === 0) continue;
                if (filter.type === 'select2' && !$selector.hasClass('select2-hidden-accessible')) {
                    allReady = false;
                } else if (filter.type === 'tom-select' && !$selector[0].tomselect) {
                    allReady = false;
                } else if (filter.type === 'daterangepicker' && !$selector.data('daterangepicker')) {
                    allReady = false;
                }
                // Simple select doesn't require dependency check
            }
            if (allReady || attempts >= maxAttempts) {
                callback();
            } else {
                attempts++;
                setTimeout(check, checkInterval);
            }
        };
        check();
    }

    getCachedSelector(selector) {
        if (!this.filterCache.has(selector)) {
            this.filterCache.set(selector, $(selector));
        }
        return this.filterCache.get(selector);
    }

    getFilterParams() {
        const params = new URLSearchParams();
        const tableOptions = this.$table.bootstrapTable('getOptions') || {};
        const pageNumber = tableOptions.pageNumber || 1;
        const pageSize = tableOptions.pageSize || 10;

        for (const filter of this.filters) {
            const $selector = this.getCachedSelector(filter.selector);
            if ($selector.length === 0) continue;
            if (filter.type === 'select2' || filter.type === 'tom-select') {
                let values = $selector.val() || [];
                // If it's tom-select it might be an array or string
                const valuesArray = Array.isArray(values) ? values : (typeof values === 'string' && values ? values.split(',') : []);
                valuesArray.forEach(value => params.append(`${filter.name}[]`, value));
            } else if (filter.type === 'daterangepicker') {
                const from = this.getCachedSelector(filter.hiddenFrom).val();
                const to = this.getCachedSelector(filter.hiddenTo).val();
                // Use hidden input names directly (e.g., start_date, end_date)
                if (from) params.set(filter.hiddenFrom.replace('#', ''), from);
                if (to) params.set(filter.hiddenTo.replace('#', ''), to);
            } else if (filter.type === 'month') {
                const value = $selector.val();
                if (value) params.set(filter.name, value);
            } else if (filter.type === 'select' || filter.type === 'text' || filter.type === 'hidden') {
                const value = $selector.val();
                if (value) params.set(filter.name, value);
            }
        }

        params.set('page', pageNumber);
        params.set('limit', pageSize);

        const currentParams = new URLSearchParams(window.location.search);
        for (const param of this.preserveParams) {
            if (currentParams.has(param)) {
                params.set(param, currentParams.get(param));
            }
        }
        return params;
    }

    updateUrl() {
        const params = this.getFilterParams();
        const paramsString = params.toString();
        if (this.lastParams === paramsString) return;
        this.lastParams = paramsString;
        window.history.pushState({ path: `${window.location.pathname}?${paramsString} ` }, '', `${window.location.pathname}?${paramsString} `);
    }

    setFiltersFromUrl() {
        if (window.location.search && window.location.search.length > 1) {
            const urlParams = new URLSearchParams(window.location.search);

            const promises = this.filters.map(filter => new Promise(resolve => {
                const $selector = this.getCachedSelector(filter.selector);
                if ($selector.length === 0) {
                    resolve();
                    return;
                }
                if (filter.type === 'select2' || filter.type === 'tom-select') {
                    const values = urlParams.getAll(`${filter.name}[]`);
                    if (values.length) {
                        this.preFetchSelect2Options(filter, values, (data) => {
                            if (filter.type === 'tom-select' && $selector[0].tomselect) {
                                if (data && data.results) {
                                    data.results.forEach(item => {
                                        $selector[0].tomselect.addOption({ id: item.id, text: item.text });
                                    });
                                }
                                $selector[0].tomselect.setValue(values, true);
                                $selector.trigger('change');
                            } else {
                                $selector.val(values).trigger('change');
                            }
                            resolve();
                        });
                    } else {
                        if (filter.type === 'tom-select' && $selector[0].tomselect) {
                            $selector[0].tomselect.clear(true);
                            $selector.trigger('change');
                        } else {
                            $selector.val(null).trigger('change');
                        }
                        resolve();
                    }
                } else if (filter.type === 'daterangepicker') {
                    // Use hidden input names for URL params
                    const from = urlParams.get(filter.hiddenFrom.replace('#', ''));
                    const to = urlParams.get(filter.hiddenTo.replace('#', ''));
                    if (from && to) {
                        const picker = $selector.data('daterangepicker');
                        if (picker) {
                            picker.setStartDate(moment(from));
                            picker.setEndDate(moment(to));
                            const fmt = picker.locale && picker.locale.format ? picker.locale.format : 'YYYY-MM-DD';
                            const sep = picker.locale && picker.locale.separator ? picker.locale.separator : ' - ';
                            $selector.val(moment(from).format(fmt) + sep + moment(to).format(fmt));
                        }
                        this.getCachedSelector(filter.hiddenFrom).val(from);
                        this.getCachedSelector(filter.hiddenTo).val(to);
                    } else {
                        $selector.val('');
                        this.getCachedSelector(filter.hiddenFrom).val('');
                        this.getCachedSelector(filter.hiddenTo).val('');
                    }
                    resolve();
                } else if (filter.type === 'month') {
                    const value = urlParams.get(filter.name);
                    if (value && moment(value, 'YYYY-MM', true).isValid()) {
                        $selector.val(value).trigger('change');
                    } else {
                        $selector.val('').trigger('change');
                    }
                    resolve();
                } else if (filter.type === 'select' || filter.type === 'text' || filter.type === 'hidden') {
                    const value = urlParams.get(filter.name);
                    $selector.val(value || '').trigger('change'); // Added .trigger('change')
                    resolve();
                }

            }));

            const page = urlParams.get('page') || 1;
            const limit = urlParams.get('limit') || 10;
            this.$table.bootstrapTable('refresh', {
                pageNumber: parseInt(page),
                pageSize: parseInt(limit)
            });

            Promise.all(promises).then(() => {
                this.lastParams = this.getFilterParams().toString();
            });
        }
    }

    preFetchSelect2Options(filter, values, callback) {
        if (!filter.ajaxType) {
            callback();
            return;
        }
        $.ajax({
            url: '/search',
            dataType: 'json',
            data: {
                type: filter.ajaxType,
                ids: values.join(','),
                considerWorkspace: true,
                initial: true,
                limit: values.length || 10
            },
            success: data => {
                const $selector = this.getCachedSelector(filter.selector);
                if (filter.type === 'select2') {
                    data.results.forEach(item => {
                        if ($selector.find(`option[value='${item.id}']`).length === 0) {
                            $selector.append(new Option(item.text, item.id, true, true));
                        }
                    });
                }
                callback(data);
            },
            error: () => callback()
        });
    }

    attachEventListeners() {
        for (const filter of this.filters) {
            const $selector = this.getCachedSelector(filter.selector);
            if ($selector.length === 0) continue;
            if (filter.type === 'select2' || filter.type === 'tom-select') {
                $selector.on('change', () => this.debounceUpdate(true));
            } else if (filter.type === 'daterangepicker') {
                $selector.on('apply.daterangepicker cancel.daterangepicker', () => this.debounceUpdate(true));
            } else if (filter.type === 'month') {
                $selector.on('change', () => this.debounceUpdate(true));
            } else if (filter.type === 'select' || filter.type === 'text' || filter.type === 'hidden') {
                $selector.on('change', () => this.debounceUpdate(true));
            }
        }
    }

    attachPaginationListeners() {
        this.$table.on('page-change.bs.table', () => this.debounceUpdate(true));
        this.$table.on('post-body.bs.table', () => this.debounceUpdate(false));
    }

    debounceUpdate(shouldRefresh = false) {
        clearTimeout(this.updateTimeout);
        this.updateTimeout = setTimeout(() => {
            this.updateUrl();
            if (shouldRefresh) {
                this.$table.bootstrapTable('refresh');
            }
        }, this.debounceMs);
    }

    defaultQueryParams(p) {
        const params = {
            page: p.offset / p.limit + 1,
            limit: p.limit,
            sort: p.sort,
            order: p.order,
            offset: p.offset,
            search: p.search
        };
        for (const filter of this.filters) {
            const $selector = this.getCachedSelector(filter.selector);
            if ($selector.length === 0) continue;
            if (filter.type === 'select2' || filter.type === 'tom-select') {
                let values = $selector.val() || [];
                const valuesArray = Array.isArray(values) ? values : (typeof values === 'string' && values ? values.split(',') : []);
                params[filter.name] = valuesArray;
            } else if (filter.type === 'daterangepicker') {
                params[filter.hiddenFrom.replace('#', '')] = this.getCachedSelector(filter.hiddenFrom).val();
                params[filter.hiddenTo.replace('#', '')] = this.getCachedSelector(filter.hiddenTo).val();
            } else if (filter.type === 'month') {
                params[filter.name] = $selector.val();
            } else if (filter.type === 'select' || filter.type === 'text' || filter.type === 'hidden') {
                params[filter.name] = $selector.val();
            }
        }
        return params;
    }
}
