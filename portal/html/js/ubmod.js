/*
 * The contents of this file are subject to the University at Buffalo Public
 * License Version 1.0 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.ccr.buffalo.edu/licenses/ubpl.txt
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for
 * the specific language governing rights and limitations under the License.
 *
 * The Original Code is UBMoD.
 *
 * The Initial Developer of the Original Code is Research Foundation of State
 * University of New York, on behalf of University at Buffalo.
 *
 * Portions created by the Initial Developer are Copyright (C) 2007 Research
 * Foundation of State University of New York, on behalf of University at
 * Buffalo.  All Rights Reserved.
 *
 * Alternatively, the contents of this file may be used under the terms of
 * either the GNU General Public License Version 2 (the "GPL"), or the GNU
 * Lesser General Public License Version 2.1 (the "LGPL"), in which case the
 * provisions of the GPL or the LGPL are applicable instead of those above. If
 * you wish to allow use of your version of this file only under the terms of
 * either the GPL or the LGPL, and not to allow others to use your version of
 * this file under the terms of the UBPL, indicate your decision by deleting
 * the provisions above and replace them with the notice and other provisions
 * required by the GPL or the LGPL. If you do not delete the provisions above,
 * a recipient may use your version of this file under the terms of any one of
 * the UBPL, the GPL or the LGPL.
 */

/**
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 */
Ext.Loader.onReady(function () {

    /**
     * UBMoD namespace
     */
    Ext.namespace('Ubmod');

    /**
     * Time interval model
     */
    Ext.define('Ubmod.model.Interval', {
        extend: 'Ext.data.Model',
        fields: [
            'interval_id',
            'time_interval',
            'start',
            'end'
        ],

        isCustomDateRange: function () {
            return this.get('start') === null || this.get('end') === null;
        }
    });

    /**
     * Cluster model
     */
    Ext.define('Ubmod.model.Cluster', {
        extend: 'Ext.data.Model',
        fields: [
            'cluster_id',
            'host',
            'display_name'
        ]
    });

    /**
     * User activity model
     */
    Ext.define('Ubmod.model.User', {
        extend: 'Ext.data.Model',
        fields: [
            'user_id',
            'user',
            'jobs',
            'avg_wait',
            'wallt',
            'avg_cpus',
            'avg_mem'
        ]
    });

    /**
     * Group activity model
     */
    Ext.define('Ubmod.model.Group', {
        extend: 'Ext.data.Model',
        fields: [
            'group_id',
            'group_name',
            'jobs',
            'avg_wait',
            'wallt',
            'avg_cpus',
            'avg_mem'
        ]
    });

    /**
     * Queue activity model
     */
    Ext.define('Ubmod.model.Queue', {
        extend: 'Ext.data.Model',
        fields: [
            'queue_id',
            'queue',
            'jobs',
            'avg_wait',
            'wallt',
            'avg_cpus',
            'avg_mem'
        ]
    });

    /**
     * Application model
     *
     * Stores the state of the application and provides an event to
     * signal a change in state.
     */
    Ext.define('Ubmod.model.App', {
        extend: 'Ext.data.Model',
        fields: [
            { name: 'interval', type: 'Ubmod.model.Interval' },
            { name: 'cluster', type: 'Ubmod.model.Cluster' },
            { name: 'startDate', type: 'string' },
            { name: 'endDate', type: 'string' }
        ],

        constructor: function (config) {
            config = config || {};
            this.addEvents({
                restparamschanged: true,
                intervalchanged: true,
                daterangechanged: true
            });
            Ubmod.model.App.superclass.constructor.call(this, config);
        },

        /**
         * Override the set method to fire events
         *
         * @see Ext.data.Model
         */
        set: function (field, value) {
            Ubmod.model.App.superclass.set.call(this, field, value);

            // @see Ext.data.Model.set for implementation details
            if (!Ext.isObject(field)) {

                // Ingore individual dates
                if (field === 'startDate' || field === 'endDate') {
                    return;
                }

                if (field === 'interval') {
                    this.fireEvent('intervalchanged', value);

                    if (value.isCustomDateRange()) {

                        // Prevent restparamschanged event
                        return;

                    } else {
                        this.set('startDate', value.get('start'));
                        this.set('endDate', value.get('end'));

                        this.fireEvent('daterangechanged', value.get('start'),
                                value.get('end'));
                    }
                }

                this.fireEvent('restparamschanged');
            }
        },

        /**
         * @return {boolean} True if both fields are defined
         */
        isReady: function () {
            return this.get('interval') !== undefined &&
                this.get('cluster') !== undefined;
        },

        /**
         * @return {integer} The currently selected interval ID
         */
        getIntervalId: function () {
            return this.get('interval').get('interval_id');
        },

        /**
         * @return {integer} The currently selected cluster ID
         */
        getClusterId: function () {
            return this.get('cluster').get('cluster_id');
        },

        /**
         * @return {string} The currently selected start date
         */
        getStartDate: function () {
            return this.get('startDate');
        },

        /**
         * @return {string} The currently selected end date
         */
        getEndDate: function () {
            return this.get('endDate');
        },

        /**
         * Set both the start and end date
         *
         * Fires the daterangechanged event
         * Fires the restparamschanged event
         *
         * @param {Date} startDate The new start date
         * @param {Date} endDate The new end date
         */
        setDates: function (startDate, endDate) {
            var startYear = startDate.getFullYear(),
                startMonth = startDate.getMonth() + 1,
                startDay = startDate.getDate(),
                endYear = endDate.getFullYear(),
                endMonth = endDate.getMonth() + 1,
                endDay = endDate.getDate(),
                startDate = startMonth + '/' + startDay + '/' + startYear,
                endDate = endMonth + '/' + endDay + '/' + endYear;

            this.set('startDate', startDate);
            this.set('endDate', endDate);

            this.fireEvent('daterangechanged', startDate, endDate);
            this.fireEvent('restparamschanged');
        },

        /**
         * @param {Date} endDate The new end date
         */
        setEndDate: function (endDate) {
        },

        /**
         * @return {object} The parameters needed for REST requests
         */
        getRestParams: function () {
            var interval, params;

            interval = this.get('interval');

            params = {
                'interval_id': this.getIntervalId(),
                'cluster_id': this.getClusterId(),
            };

            if (interval.isCustomDateRange()) {
                params.start_date = this.getStartDate();
                params.end_date   = this.getEndDate();
            }

            return params;
        }
    });

    /**
     * Data store that reverses sorting
     */
    Ext.define('Ubmod.data.ReverseSortStore', {
        extend: 'Ext.data.Store',

        sort: function (sorters, direction, where, doSort) {
            if (Ext.isObject(sorters) && sorters.direction !== undefined) {
                sorters.direction =
                    sorters.direction === 'ASC' ? 'DESC' : 'ASC';
            } else if (Ext.isArray(sorters) && sorters.length > 0) {
                sorters[0].direction =
                    sorters[0].direction === 'ASC' ? 'DESC' : 'ASC';
            } else if (Ext.isString(sorters)) {
                direction = direction === 'ASC' ? 'DESC' : 'ASC';
            }
            return Ubmod.data.ReverseSortStore.superclass.sort.call(this,
                sorters, direction, where, doSort);
        }
    });

    /**
     * Time interval data store
     */
    Ext.define('Ubmod.store.Interval', {
        extend: 'Ext.data.Store',

        constructor: function (config) {
            config = config || {};
            Ext.apply(config, {
                model: 'Ubmod.model.Interval',
                buffered: true,
                proxy: {
                    type: 'ajax',
                    url: '/api/rest/json/interval/list',
                    reader: { type: 'json', root: 'data' }
                }
            });
            Ubmod.store.Interval.superclass.constructor.call(this, config);
        }
    });

    /**
     * Cluster data store
     */
    Ext.define('Ubmod.store.Cluster', {
        extend: 'Ext.data.Store',

        constructor: function (config) {
            config = config || {};
            Ext.apply(config, {
                model: 'Ubmod.model.Cluster',
                buffered: true,
                proxy: {
                    type: 'ajax',
                    url: '/api/rest/json/cluster/list',
                    reader: { type: 'json', root: 'data' }
                }
            });
            Ubmod.store.Cluster.superclass.constructor.call(this, config);
        }
    });

    /**
     * User activity data store
     */
    Ext.define('Ubmod.store.User', {
        extend: 'Ubmod.data.ReverseSortStore',

        constructor: function (config) {
            config = config || {};
            Ext.apply(config, {
                model: 'Ubmod.model.User',
                remoteSort: true,
                pageSize: 25,
                proxy: {
                    type: 'ajax',
                    simpleSortMode: true,
                    url: '/api/rest/json/user/list',
                    reader: { type: 'json', root: 'users' },
                    extraParams: { sort: 'wallt', dir: 'DESC' }
                }
            });
            Ubmod.store.User.superclass.constructor.call(this, config);
        }
    });

    /**
     * Group activity data store
     */
    Ext.define('Ubmod.store.Group', {
        extend: 'Ubmod.data.ReverseSortStore',

        constructor: function (config) {
            config = config || {};
            Ext.apply(config, {
                model: 'Ubmod.model.Group',
                remoteSort: true,
                pageSize: 25,
                proxy: {
                    type: 'ajax',
                    simpleSortMode: true,
                    url: '/api/rest/json/group/list',
                    reader: { type: 'json', root: 'groups' },
                    extraParams: { sort: 'wallt', dir: 'DESC' }
                }
            });
            Ubmod.store.Group.superclass.constructor.call(this, config);
        }
    });

    /**
     * Queue activity data store
     */
    Ext.define('Ubmod.store.Queue', {
        extend: 'Ubmod.data.ReverseSortStore',

        constructor: function (config) {
            config = config || {};
            Ext.apply(config, {
                model: 'Ubmod.model.Queue',
                remoteSort: true,
                pageSize: 25,
                proxy: {
                    type: 'ajax',
                    simpleSortMode: true,
                    url: '/api/rest/json/queue/list',
                    reader: { type: 'json', root: 'queues' },
                    extraParams: { sort: 'wallt', dir: 'DESC' }
                }
            });
            Ubmod.store.Queue.superclass.constructor.call(this, config);
        }
    });

    /**
     * Time interval combo box
     */
    Ext.define('Ubmod.widget.Interval', {
        extend: 'Ext.form.field.ComboBox',

        constructor: function (config) {
            config = config || {};
            Ext.apply(config, {
                editable: false,
                store: Ext.create('Ubmod.store.Interval'),
                displayField: 'time_interval',
                valueField: 'interval_id',
                queryMode: 'local',
                emptyText: 'Interval...'
            });
            Ubmod.widget.Interval.superclass.constructor.call(this, config);
        },

        initComponent: function () {
            Ubmod.widget.Interval.superclass.initComponent.call(this);

            this.store.load({
                scope: this,
                callback: function (records) {
                    // Default to the fifth record (Last 365 days).
                    var i = 4;
                    this.setValue(records[i].get(this.valueField));
                    this.fireEvent('select', this, [records[i]]);
                }
            });
        }
    });

    /**
     * Cluster combo box
     */
    Ext.define('Ubmod.widget.Cluster', {
        extend: 'Ext.form.field.ComboBox',

        constructor: function (config) {
            config = config || {};
            Ext.apply(config, {
                editable: false,
                store: Ext.create('Ubmod.store.Cluster'),
                displayField: 'display_name',
                valueField: 'cluster_id',
                queryMode: 'local',
                emptyText: 'Cluster...'
            });
            Ubmod.widget.Cluster.superclass.constructor.call(this, config);
        },

        initComponent: function () {
            Ubmod.widget.Cluster.superclass.initComponent.call(this);

            this.store.load({
                scope: this,
                callback: function (records) {
                    this.setValue(records[0].get(this.valueField));
                    this.fireEvent('select', this, [records[0]]);
                }
            });
        }
    });

    /**
     * Toolbar for time period and cluster
     */
    Ext.define('Ubmod.widget.Toolbar', {
        extend: 'Ext.toolbar.Toolbar',

        constructor: function (config) {
            config = config || {};
            this.model = config.model;
            Ubmod.widget.Cluster.superclass.constructor.call(this, config);
        },

        initComponent: function () {
            this.intervalCombo = Ext.create('Ubmod.widget.Interval');
            this.intervalCombo.on('select', function (combo, records) {
                this.model.set('interval', records[0]);
            }, this);

            this.clusterCombo = Ext.create('Ubmod.widget.Cluster');
            this.clusterCombo.on('select', function (combo, records) {
                this.model.set('cluster', records[0]);
            }, this);

            this.startDate = Ext.create('Ext.form.field.Date');
            this.endDate   = Ext.create('Ext.form.field.Date');

            this.updateButton = Ext.create('Ext.Button', {
                text: 'Update',
                hidden: true
            });

            this.updateButton.on('click', function () {
                this.model.setDates(this.startDate.getValue(),
                    this.endDate.getValue());
            }, this);

            this.model.on('intervalchanged', function (interval) {
                if (interval.isCustomDateRange()) {
                    this.startDate.setReadOnly(false);
                    this.endDate.setReadOnly(false);
                    this.updateButton.show();
                } else {
                    this.updateButton.hide();
                    this.startDate.setReadOnly(true);
                    this.endDate.setReadOnly(true);
                    this.startDate.setValue(interval.get('start'));
                    this.endDate.setValue(interval.get('end'));
                }
            }, this);

            this.renderTo = Ext.get('toolbar');
            this.items = [
                'Cluster:',
                this.clusterCombo,
                { xtype: 'tbspacer', width: 20 },
                'Period:',
                this.intervalCombo,
                { xtype: 'tbspacer', width: 20 },
                'Date Range:',
                this.startDate,
                'to',
                this.endDate,
                this.updateButton
            ];

            Ubmod.widget.Toolbar.superclass.initComponent.call(this);
        }
    });

    /**
     * Tab panel for stats grid and detail pages
     */
    Ext.define('Ubmod.widget.StatsPanel', {
        extend: 'Ext.tab.Panel',

        constructor: function (config) {
            config = config || {};

            this.model = config.model;
            this.store = config.store;
            this.recordFormat = config.recordFormat;
            this.detailTabs = [];

            this.grid = Ext.create('Ubmod.widget.Grid', {
                height: 400,
                forceFit: true,
                padding: '0 0 6 0',
                resizable: { pinned: true, handles: 's' },
                title: config.gridTitle,
                store: this.store,
                label: this.recordFormat.label,
                labelKey: this.recordFormat.key
            });

            Ext.apply(config, {
                width: 745,
                plain: true,
                items: this.grid
            });

            Ubmod.widget.StatsPanel.superclass.constructor.call(this, config);
        },

        initComponent: function () {
            var listener = function () { this.reload(); };
            this.model.on('restparamschanged', listener, this);
            this.on('destroy', function () {
                this.model.removeListener('restparamschanged', listener, this);
            }, this);

            Ubmod.widget.StatsPanel.superclass.initComponent.call(this);

            this.grid.on('itemdblclick', function (grid, record) {
                var params = Ext.merge(this.model.getRestParams(), {
                    id: record.get(this.recordFormat.id)
                });
                this.detailTabs.push({
                    id: record.get(this.recordFormat.id),
                    tab: this.add({
                        title: record.get(this.recordFormat.key),
                        closable: true,
                        loader: {
                            url: this.recordFormat.detailsUrl,
                            autoLoad: true,
                            params: params
                        }
                    }).show()
                });
            }, this);

            // XXX Force the tab panel to recalculate it's layout when
            // the grid is resized.
            this.grid.on('resize', this.doLayout, this);

            this.reload();
        },

        /**
         * Reloads all tabs
         */
        reload: function () {
            if (!this.model.isReady()) { return; }

            var params = this.model.getRestParams();
            Ext.merge(this.store.proxy.extraParams, params);
            this.store.load();

            Ext.each(this.detailTabs, function (detail) {
                var detailParams = Ext.merge({ id: detail.id }, params);
                detail.tab.loader.load({
                    url: this.detailsUrl,
                    params: detailParams
                });
            }, this);
        }
    });

    /**
     * Stats grid
     */
    Ext.define('Ubmod.widget.Grid', {
        extend: 'Ext.grid.Panel',

        constructor: function (config) {
            config = config || {};

            this.label = config.label;
            this.labelKey = config.labelKey;

            Ubmod.widget.Grid.superclass.constructor.call(this, config);
        },

        initComponent: function () {
            this.columns = [{
                header: this.label,
                dataIndex: this.labelKey,
                menuDisabled: true,
                width: 128
            }, {
                header: '# Jobs',
                dataIndex: 'jobs',
                xtype: 'numbercolumn',
                format: '0,000',
                menuDisabled: true,
                width: 96,
                align: 'right'
            }, {
                header: 'Avg. Job Size (cpus)',
                dataIndex: 'avg_cpus',
                xtype: 'numbercolumn',
                format: '0,000.0',
                menuDisabled: true,
                width: 118,
                align: 'right'
            }, {
                header: 'Avg. Wait Time (h)',
                dataIndex: 'avg_wait',
                xtype: 'numbercolumn',
                format: '0,000.0',
                menuDisabled: true,
                width: 118,
                align: 'right'
            }, {
                header: 'Wall Time (d)',
                dataIndex: 'wallt',
                xtype: 'numbercolumn',
                format: '0,000.0',
                menuDisabled: true,
                width: 128,
                align: 'right'
            }, {
                header: 'Avg. Mem (MB)',
                dataIndex: 'avg_mem',
                xtype: 'numbercolumn',
                format: '0,000.0',
                menuDisabled: true,
                width: 128,
                align: 'right'
            }];

            var pagingToolbar, filter;

            filter = Ext.create('Ext.form.field.Text', {
                enableKeyEvents: true
            });

            filter.on('keypress', function (text, e) {
                if (e.getKey() === e.ENTER) {
                    pagingToolbar.moveFirst();
                    Ext.merge(this.store.proxy.extraParams, {
                        filter: text.getValue()
                    });
                    this.store.load();
                }
            }, this);

            filter.on('keyup', function (text, e) {
                if (e.getKey() === e.BACKSPACE &&
                        text.getValue().length === 0) {
                    pagingToolbar.moveFirst();
                    Ext.merge(this.store.proxy.extraParams, { filter: '' });
                    this.store.load();
                }
            }, this);

            pagingToolbar = Ext.create('Ext.toolbar.Paging', {
                dock: 'bottom',
                store: this.store,
                displayInfo: true,
                items: [ '-', 'Search:', filter ]
            });

            this.dockedItems = [pagingToolbar];

            Ubmod.widget.Grid.superclass.initComponent.call(this);
        }
    });

    /**
     * Component used for loading pages using AJAX
     */
    Ext.define('Ubmod.widget.Partial', {
        extend: 'Ext.Component',

        constructor: function (config) {
            config = config || {};
            this.model = config.model;
            this.url = config.url;
            this.element = config.element;
            Ubmod.widget.Partial.superclass.constructor.call(this, config);
        },

        initComponent: function () {
            var listener = function () { this.reload(); };
            this.model.on('restparamschanged', listener, this);
            this.on('destroy', function () {
                this.model.removeListener('restparamschanged', listener, this);
            }, this);

            Ubmod.widget.Partial.superclass.initComponent.call(this);

            this.reload();
        },

        /**
         * Reloads the element
         */
        reload: function () {
            if (!this.model.isReady()) { return; }
            Ext.get(this.element).load({
                loadMask: 'Loading...',
                url: this.url,
                params: this.model.getRestParams()
            });
        }
    });

    /**
     * Application object
     */
    Ubmod.app = (function () {
        var model, widgets;

        return {
            init: function () {

                model = Ext.create('Ubmod.model.App');
                widgets = [];

                model.on('daterangechanged', function (start, end) {
                    Ext.get('date-display').update(start + ' thru ' + end);
                });

                // Listen for clicks on menu links.
                Ext.select('#menu-list a').each(function () {
                    var href = this.getAttribute('href');
                    this.on('click', function (evt, el) {

                        // Load the new content.
                        Ext.get('content').load({
                            loadMask: 'Loading...',
                            url: href,
                            scripts: true,
                            success: function () {
                                // Destroy any existing widgets.
                                Ext.each(widgets, function () {
                                    this.destroy();
                                });
                                widgets = [];
                            }
                        });

                        // Update menu CSS classes.
                        Ext.select('#menu-list li').each(function () {
                            this.removeCls('menu-active');
                        });
                        Ext.get(el).parent().addCls('menu-active');

                    }, this, { stopEvent: true });
                });

                Ext.create('Ubmod.widget.Toolbar', { model: model });
            },

            /**
             * Add an element that should be updated whenever the time
             * interval or cluster is changed
             */
            addPartial: function (config) {
                config.model = model;
                widgets.push(Ext.create('Ubmod.widget.Partial', config));
            },

            /**
             * Add a stats panel that should be updated whenever the time
             * interval or cluster is changed
             */
            addStatsPanel: function (config) {
                config.model = model;
                widgets.push(Ext.create('Ubmod.widget.StatsPanel', config));
            }
        };
    }());

    Ext.onReady(Ubmod.app.init, Ubmod);

}, window, false);
