/*
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * The Original Code is UBMoD.
 *
 * The Initial Developer of the Original Code is Research Foundation of State
 * University of New York, on behalf of University at Buffalo.
 *
 * Portions created by the Initial Developer are Copyright (C) 2007 Research
 * Foundation of State University of New York, on behalf of University at
 * Buffalo.  All Rights Reserved.
 */

/**
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2012
 */
Ext.Loader.onReady(function () {

    /**
     * Local blank image.
     */
    Ext.BLANK_IMAGE_URL = Ubmod.baseUrl + '/images/s.gif';

    /**
     * Time interval model.
     */
    Ext.define('Ubmod.model.TimeInterval', {
        extend: 'Ext.data.Model',
        fields: [
            { name: 'interval_id', type: 'int' },
            { name: 'name',        type: 'string' },
            { name: 'start',       type: 'string' },
            { name: 'end',         type: 'string' },
            { name: 'is_custom',   type: 'boolean' }
        ],

        /**
         * @return {Boolean} True if this is a custom date range.
         */
        isCustomDateRange: function () {
            return this.get('is_custom');
        }
    });

    /**
     * Cluster model.
     */
    Ext.define('Ubmod.model.Cluster', {
        extend: 'Ext.data.Model',
        fields: [
            { name: 'cluster_id', type: 'int', useNull: true },
            { name: 'name',       type: 'string' },
            {
                name: 'display_name',
                type: 'string',

                // If the cluster doesn't have a display_name, use the
                // name in its place.
                convert: function (value, record) {
                    return value || record.get('name');
                }
            }
        ]
    });

    /**
     * User activity model.
     */
    Ext.define('Ubmod.model.UserActivity', {
        extend: 'Ext.data.Model',
        fields: [
            'user_id',
            'name',
            'display_name',
            'jobs',
            'avg_wait',
            'wallt',
            'avg_cpus',
            'avg_mem'
        ]
    });

    /**
     * User tags model.
     */
    Ext.define('Ubmod.model.UserTags', {
        extend: 'Ext.data.Model',
        fields: [
            'user_id',
            'name',
            'display_name',
            'group',
            'tags'
        ],

        /**
         * Add a tag to this user.
         *
         * @param {String} tag The tag to add.
         * @param {Function} success (optional) Success callback function.
         * @param {Object} scope (optional) Success callback scope.
         */
        addTag: function (tag, success, scope) {
            var tags = this.get('tags');

            tags.push(tag);

            this.updateTags(tags, success, scope);
        },

        /**
         * Remove a tag from this user.
         *
         * @param {String} tag The tag to remove.
         * @param {Function} success (optional) Success callback function.
         * @param {Object} scope (optional) Success callback scope.
         */
        removeTag: function (tag, success, scope) {
            var tags = [];

            Ext.each(this.get('tags'), function (currentTag) {
                if (currentTag !== tag) {
                    tags.push(currentTag);
                }
            }, this);

            this.updateTags(tags, success, scope);
        },

        /**
         * Update the tags for this user.
         *
         * @param {Array} tags The tags.
         * @param {Function} success (optional) Success callback function.
         * @param {Object} scope (optional) Success callback scope.
         */
        updateTags: function (tags, success, scope) {
            success = success || function () {};
            scope = scope || this;

            Ext.Ajax.request({
                url: Ubmod.baseUrl + '/api/rest/json/user/updateTags',
                params: { userId: this.get('user_id'), 'tags[]': tags },
                success: function (response) {
                    var tags = Ext.JSON.decode(response.responseText).tags;
                    this.set('tags', tags);

                    Ext.StoreManager.lookup('tagStore').load();

                    success.call(scope);
                },
                scope: this
            });
        }
    });

    /**
     * Group activity model.
     */
    Ext.define('Ubmod.model.GroupActivity', {
        extend: 'Ext.data.Model',
        fields: [
            'group_id',
            'name',
            'display_name',
            'jobs',
            'avg_wait',
            'wallt',
            'avg_cpus',
            'avg_mem'
        ]
    });

    /**
     * Queue activity model.
     */
    Ext.define('Ubmod.model.QueueActivity', {
        extend: 'Ext.data.Model',
        fields: [
            'queue_id',
            'name',
            'display_name',
            'jobs',
            'avg_wait',
            'wallt',
            'avg_cpus',
            'avg_mem'
        ]
    });

    /**
     * Tag activity model.
     */
    Ext.define('Ubmod.model.TagActivity', {
        extend: 'Ext.data.Model',
        fields: [
            { name: 'name',     type: 'string' },
            { name: 'jobs',     type: 'int' },
            { name: 'avg_wait', type: 'float' },
            { name: 'wallt',    type: 'float' },
            { name: 'avg_cpus', type: 'float' },
            { name: 'avg_mem',  type: 'float' }
        ]
    });

    /**
     * Tag model.
     */
    Ext.define('Ubmod.model.Tag', {
        extend: 'Ext.data.Model',
        fields: [
            { name: 'tag_id',    type: 'int' },
            { name: 'parent_id', type: 'int', useNull: true },
            { name: 'name',      type: 'string' },
            { name: 'key',       type: 'string' },
            { name: 'value',     type: 'string' },
            { name: 'children',  type: 'auto' },
            { name: 'leaf',      type: 'boolean', defaultValue: false }
        ],
        idProperty: 'tag_id'
    });

    /**
     * Tag key model.
     */
    Ext.define('Ubmod.model.TagKey', {
        extend: 'Ext.data.Model',
        fields: [
            { name: 'name', type: 'string' }
        ]
    });

    /**
     * Application model.
     *
     * Stores the state of the application and provides an event to
     * signal a change in state.
     */
    Ext.define('Ubmod.model.App', {
        extend: 'Ext.data.Model',
        fields: [
            { name: 'interval',  type: 'auto' },
            { name: 'cluster',   type: 'auto' },
            { name: 'startDate', type: 'string' },
            { name: 'endDate',   type: 'string' },
            { name: 'tag',       type: 'string' }
        ],

        constructor: function (config) {
            config = config || {};

            this.addEvents(

                /**
                 * @event restparamschanged
                 * Fired after any of the parameters needed for REST
                 * requests have changed.
                 */
                'restparamschanged',

                /**
                 * @event intervalchanged
                 * Fired after the time interval is changed.
                 * @param {Ubmod.model.TimeInterval} interval The new
                 *   time interval.
                 */
                'intervalchanged',

                /**
                 * @event daterangechanged
                 * Fired after the start and end date are changed.
                 * @param {String} startDate The new start date.
                 * @param {String} endDate The new end date.
                 */
                'daterangechanged'
            );

            this.callParent([config]);
        },

        /**
         * @return {Boolean} True if both fields are defined.
         */
        isReady: function () {
            return this.get('interval') && this.get('cluster');
        },

        /**
         * Set the current time interval.
         *
         * @param {Ubmod.model.TimeInterval} interval The new time
         *   interval.
         */
        setInterval: function (interval) {
            var start, end;

            this.set('interval', interval);

            // If the new interval is not a custom date range, update
            // the start and end dates so that they may be used when
            // a custom date range is selected.
            if (!interval.isCustomDateRange()) {
                start = interval.get('start');
                end   = interval.get('end');

                this.set('startDate', start);
                this.set('endDate',   end);

                // These events are only fired for non-custom date
                // ranges beacuse the parameters for custom date ranges
                // aren't considered changed until new dates have been
                // set.
                this.fireEvent('restparamschanged');
                this.fireEvent('daterangechanged', start, end);
            }

            this.fireEvent('intervalchanged', interval);
        },

        /**
         * Set the current cluster.
         *
         * @param {Ubmod.model.Cluster} cluster The new cluster.
         */
        setCluster: function (cluster) {
            this.set('cluster', cluster);
            this.fireEvent('restparamschanged');
        },

        /**
         * @return {Number} The currently selected cluster ID.
         */
        getClusterId: function () {
            return this.get('cluster').get('cluster_id');
        },

        /**
         * @return {String} The currently selected start date.
         */
        getStartDate: function () {
            return this.get('startDate');
        },

        /**
         * @return {String} The currently selected end date.
         */
        getEndDate: function () {
            return this.get('endDate');
        },

        /**
         * Set both the start and end date.
         *
         * Fires the daterangechanged event.
         * Fires the restparamschanged event.
         *
         * @param {Date} startDate The new start date.
         * @param {Date} endDate The new end date.
         */
        setDates: function (startDate, endDate) {
            var startYear = startDate.getFullYear(),
                startMonth = startDate.getMonth() + 1,
                startDay = startDate.getDate(),
                endYear = endDate.getFullYear(),
                endMonth = endDate.getMonth() + 1,
                endDay = endDate.getDate(),
                start,
                end;

            // Add zero padding
            if (startDay   < 10) { startDay   = '0' + startDay; }
            if (endDay     < 10) { endDay     = '0' + endDay; }
            if (startMonth < 10) { startMonth = '0' + startMonth; }
            if (endMonth   < 10) { endMonth   = '0' + endMonth; }

            start = startMonth + '/' + startDay + '/' + startYear;
            end   = endMonth   + '/' + endDay   + '/' + endYear;

            this.set('startDate', start);
            this.set('endDate', end);

            this.fireEvent('daterangechanged', start, end);
            this.fireEvent('restparamschanged');
        },

        /**
         * Get the currently selected tag.
         *
         * @return {String} The current tag.
         */
        getTag: function () {
            return this.get('tag');
        },

        /**
         * Set the current tag.
         *
         * @param {String} tag The new tag.
         */
        setTag: function (tag) {
            this.set('tag', tag);
            this.fireEvent('restparamschanged');
        },

        /**
         * @return {Object} The parameters defining the currently
         *   selected time interval.
         */
        getIntervalRestParams: function () {
            var interval, params;

            interval = this.get('interval');

            params = { interval_id: interval.get('interval_id') };

            if (interval.isCustomDateRange()) {
                Ext.apply(params, {
                    start_date: this.getStartDate(),
                    end_date:   this.getEndDate()
                });
            }

            return params;
        },

        /**
         * @return {Object} The parameters needed for REST requests.
         */
        getRestParams: function () {
            var params = {
                cluster_id: this.getClusterId(),
                tag:        this.getTag()
            };

            Ext.apply(params, this.getIntervalRestParams());

            return params;
        }
    });

    /**
     * Time interval data store.
     */
    Ext.define('Ubmod.store.TimeInterval', {
        extend: 'Ext.data.Store',
        model: 'Ubmod.model.TimeInterval',
        buffered: true,
        proxy: {
            type: 'ajax',
            url: Ubmod.baseUrl + '/api/rest/jsonstore/interval/list',
            reader: { type: 'json', root: 'results' }
        }
    });

    /**
     * Cluster data store.
     */
    Ext.define('Ubmod.store.Cluster', {
        extend: 'Ext.data.Store',
        model: 'Ubmod.model.Cluster',
        buffered: true,
        proxy: {
            type: 'ajax',
            url: Ubmod.baseUrl + '/api/rest/jsonstore/cluster/list',
            reader: { type: 'json', root: 'results' }
        }
    });

    /**
     * User activity data store.
     */
    Ext.define('Ubmod.store.UserActivity', {
        extend: 'Ext.data.Store',
        model: 'Ubmod.model.UserActivity',
        remoteSort: true,
        pageSize: 25,
        sorters: [{ property: 'wallt', direction: 'DESC' }],
        proxy: {
            type: 'ajax',
            simpleSortMode: true,
            url: Ubmod.baseUrl + '/api/rest/jsonstore/job/activity',
            reader: { type: 'json', root: 'results' },
            extraParams: { model: 'user' }
        }
    });

    /**
     * User tag data store.
     */
    Ext.define('Ubmod.store.UserTags', {
        extend: 'Ext.data.Store',
        model: 'Ubmod.model.UserTags',
        remoteSort: true,
        pageSize: 25,
        sorters: [{ property: 'name', direction: 'ASC' }],
        proxy: {
            type: 'ajax',
            simpleSortMode: true,
            url: Ubmod.baseUrl + '/api/rest/jsonstore/user/tags',
            reader: { type: 'json', root: 'results' }
        },

        /**
         * Add a tag to one or more users.
         *
         * @param {String} tag The tag to add.
         * @param {Array} users The users to add the tag to.
         * @param {Function} success (optional) Success callback function.
         * @param {Object} scope (optional) Success function scope.
         */
        addTag: function (tag, users, success, scope) {
            var userIds;

            success = success || function () {};
            scope = scope || this;

            userIds = [];
            Ext.each(users, function (user) {
                userIds.push(user.get('user_id'));
            });

            Ext.Ajax.request({
                url: Ubmod.baseUrl + '/api/rest/json/user/addTag',
                params: { tag: tag, 'userIds[]': userIds },
                success: function () {
                    this.load();

                    Ext.StoreManager.lookup('tagStore').load();

                    success.call(scope);
                },
                scope: this
            });
        }
    });

    /**
     * Group activity data store.
     */
    Ext.define('Ubmod.store.GroupActivity', {
        extend: 'Ext.data.Store',
        model: 'Ubmod.model.GroupActivity',
        remoteSort: true,
        pageSize: 25,
        sorters: [{ property: 'wallt', direction: 'DESC' }],
        proxy: {
            type: 'ajax',
            simpleSortMode: true,
            url: Ubmod.baseUrl + '/api/rest/jsonstore/job/activity',
            reader: { type: 'json', root: 'results' },
            extraParams: { model: 'group' }
        }
    });

    /**
     * Queue activity data store.
     */
    Ext.define('Ubmod.store.QueueActivity', {
        extend: 'Ext.data.Store',
        model: 'Ubmod.model.QueueActivity',
        remoteSort: true,
        pageSize: 25,
        sorters: [{ property: 'wallt', direction: 'DESC' }],
        proxy: {
            type: 'ajax',
            simpleSortMode: true,
            url: Ubmod.baseUrl + '/api/rest/jsonstore/job/activity',
            reader: { type: 'json', root: 'results' },
            extraParams: { model: 'queue' }
        }
    });

    /**
     * Tag activity data store.
     */
    Ext.define('Ubmod.store.TagActivity', {
        extend: 'Ext.data.Store',
        model: 'Ubmod.model.TagActivity',
        remoteSort: true,
        pageSize: 25,
        sorters: [{ property: 'wallt', direction: 'DESC' }],
        proxy: {
            type: 'ajax',
            simpleSortMode: true,
            url: Ubmod.baseUrl + '/api/rest/jsonstore/tag/activity',
            reader: { type: 'json', root: 'results' }
        }
    });

    /**
     * Tag store.
     */
    Ext.define('Ubmod.store.Tag', {
        extend: 'Ext.data.Store',
        model: 'Ubmod.model.Tag',
        autoLoad: true,
        storeId: 'tagStore',
        proxy: {
            type: 'ajax',
            simpleSortMode: true,
            url: Ubmod.baseUrl + '/api/rest/jsonstore/tag/list',
            reader: { type: 'json', root: 'results' }
        }
    });

    /**
     * Tag key store.
     */
    Ext.define('Ubmod.store.TagKey', {
        extend: 'Ext.data.Store',
        model: 'Ubmod.model.TagKey',
        proxy: {
            type: 'ajax',
            simpleSortMode: true,
            url: Ubmod.baseUrl + '/api/rest/jsonstore/tag/keyList',
            reader: { type: 'json', root: 'results' }
        }
    });

    /**
     * Tag tree store.
     */
    Ext.define('Ubmod.store.TagTree', {
        extend: 'Ext.data.TreeStore',
        model: 'Ubmod.model.Tag',
        proxy: {
            type: 'ajax',
            simpleSortMode: true,
            api: {
                create: Ubmod.baseUrl + '/api/rest/jsonstore/tag/createTree',
                read: Ubmod.baseUrl + '/api/rest/jsonstore/tag/tree',
                update: Ubmod.baseUrl + '/api/rest/jsonstore/tag/updateTree',
                destroy: Ubmod.baseUrl + '/api/rest/jsonstore/tag/deleteTree'
            },
            reader: { type: 'json', root: 'results' }
        }
    });

    /**
     * Time interval combo box.
     */
    Ext.define('Ubmod.widget.TimeInterval', {
        extend: 'Ext.form.field.ComboBox',
        editable: false,
        store: Ext.create('Ubmod.store.TimeInterval'),
        displayField: 'name',
        valueField: 'interval_id',
        queryMode: 'remote',
        emptyText: 'Period...',

        initComponent: function () {
            this.callParent(arguments);

            this.store.load({
                scope: this,
                callback: function (records) {

                    // Default to the fifth record (Last 365 days).
                    var i = 4;
                    this.select(records[i]);
                    this.fireEvent('select', this, [records[i]]);
                }
            });
        }
    });

    /**
     * Cluster combo box.
     */
    Ext.define('Ubmod.widget.Cluster', {
        extend: 'Ext.form.field.ComboBox',
        editable: false,
        store: Ext.create('Ubmod.store.Cluster'),
        displayField: 'display_name',
        valueField: 'cluster_id',
        queryMode: 'remote',
        emptyText: 'Cluster...',

        initComponent: function () {
            this.callParent(arguments);

            this.store.load({
                scope: this,
                callback: function (records) {
                    this.select(records[0]);
                    this.fireEvent('select', this, [records[0]]);
                }
            });
        }
    });

    /**
     * Toolbar used to select filters that are applied to queries.
     */
    Ext.define('Ubmod.widget.Toolbar', {
        extend: 'Ext.toolbar.Toolbar',

        constructor: function (config) {
            config = config || {};
            Ext.apply(config, { height: 33 });
            this.model = config.model;
            this.callParent([config]);
        },

        initComponent: function () {
            var clearTagButton;

            this.intervalCombo = Ext.create('Ubmod.widget.TimeInterval');
            this.intervalCombo.on('select', function (combo, records) {
                this.model.setInterval(records[0]);
            }, this);

            this.clusterCombo = Ext.create('Ubmod.widget.Cluster');
            this.clusterCombo.on('select', function (combo, records) {
                this.model.setCluster(records[0]);
            }, this);

            this.startDate    = Ext.create('Ext.form.field.Date');
            this.endDate      = Ext.create('Ext.form.field.Date');
            this.updateButton = Ext.create('Ext.Button', { text: 'Update' });

            this.updateButton.on('click', function () {
                this.model.setDates(this.startDate.getValue(),
                    this.endDate.getValue());
            }, this);

            this.model.on('intervalchanged', function (interval) {
                if (interval.isCustomDateRange()) {
                    this.startDate.setValue(this.model.get('startDate'));
                    this.endDate.setValue(this.model.get('endDate'));
                    this.dateRange.show();
                } else {
                    this.dateRange.hide();
                }
            }, this);

            this.dateRange = Ext.create('Ext.container.Container', {
                layout: { type: 'hbox', align: 'middle' },
                width: 500,
                hidden: true,
                items: [
                    { xtype: 'tbtext', text: 'Date Range:' },
                    this.startDate,
                    { xtype: 'tbtext', text: 'to' },
                    this.endDate,
                    { xtype: 'tbspacer', width: 5 },
                    this.updateButton
                ]
            });

            this.tagInput = Ext.create('Ubmod.widget.TagInput', {
                enableKeyEvents: true
            });
            this.tagInput.on('select', function (input) {
                this.model.setTag(input.getRawValue());
            }, this);
            this.tagInput.on('keyup', function (input, e) {
                var value = input.getRawValue();
                if (e.getKey() === e.BACKSPACE && value.length === 0) {
                    this.model.setTag('');

                    // Set the input value, otherwise the previously
                    // selected value cannot be selected again.
                    this.tagInput.setValue('');
                }
            }, this);

            clearTagButton = Ext.create('Ext.Button', { text: 'Clear Tag' });
            clearTagButton.on('click', function () {
                this.tagInput.setValue('');
                this.model.setTag('');
            }, this);

            this.renderTo = Ext.get('toolbar');
            this.items = [
                'Cluster:',
                this.clusterCombo,
                { xtype: 'tbspacer', width: 20 },
                'Period:',
                this.intervalCombo,
                { xtype: 'tbspacer', width: 20 },
                this.dateRange,
                'Tag:',
                this.tagInput,

                // The container is used to change the style of the
                // button.
                { xtype: 'container', items: [ clearTagButton ] }
            ];

            this.callParent(arguments);
        }
    });

    /**
     * Tab panel for stats grid and detail pages.
     */
    Ext.define('Ubmod.widget.StatsPanel', {
        extend: 'Ext.tab.Panel',

        constructor: function (config) {
            config = config || {};

            this.model = config.model;
            this.store = config.store;
            this.recordFormat = config.recordFormat;
            this.detailTabs = {};

            this.grid = Ext.create('Ubmod.widget.StatsGrid', {
                forceFit: true,
                title: config.gridTitle,
                store: this.store,
                label: this.recordFormat.label,
                labelKey: this.recordFormat.key,
                downloadUrl: config.downloadUrl
            });

            Ext.apply(config, {
                activeTab: 0,
                plain: true,
                width: 745,
                height: 400,
                resizable: {
                    constrainTo: Ext.getBody(),
                    pinned: true,
                    handles: 's'
                },
                padding: '0 0 6 0',
                items: this.grid
            });

            this.callParent([config]);
        },

        initComponent: function () {
            var listener = function () { this.reload(); };
            this.model.on('restparamschanged', listener, this);
            this.on('beforedestroy', function () {
                this.model.removeListener(
                    'restparamschanged',
                    listener,
                    this
                );
                this.removeAll();
            }, this);

            this.callParent(arguments);

            this.grid.on('itemdblclick', function (grid, record) {
                var id, params, tab;

                id = record.get(this.recordFormat.id);

                if (this.detailTabs[id] !== undefined) {
                    this.setActiveTab(this.detailTabs[id]);
                    return;
                }

                params = {};
                params[this.recordFormat.id] = id;
                params = Ext.merge(params, this.model.getRestParams());

                tab = this.add({
                    title: record.get(this.recordFormat.key),
                    closable: true,
                    autoScroll: true,
                    loader: {
                        url: this.recordFormat.detailsUrl,
                        autoLoad: true,
                        params: params,
                        scripts: true
                    }
                });

                tab.on('beforeclose', function () {
                    delete this.detailTabs[id];
                }, this);

                tab.show();

                this.detailTabs[id] = tab;
            }, this);

            this.reload();
        },

        /**
         * Reloads all tabs.
         */
        reload: function () {
            if (!this.model.isReady()) { return; }

            var params = this.model.getRestParams();
            Ext.merge(this.store.proxy.extraParams, params);
            this.store.load();

            Ext.Object.each(this.detailTabs, function (id, tab) {
                var detailParams = {};
                detailParams[this.recordFormat.id] = id;
                detailParams = Ext.merge(detailParams, params);
                tab.loader.load({
                    url: this.detailsUrl,
                    params: detailParams
                });
            }, this);
        }
    });

    /**
     * Stats grid.
     */
    Ext.define('Ubmod.widget.StatsGrid', {
        extend: 'Ext.grid.Panel',

        constructor: function (config) {
            config = config || {};

            this.label       = config.label;
            this.labelKey    = config.labelKey;
            this.downloadUrl = config.downloadUrl;

            this.callParent([config]);
        },

        initComponent: function () {
            var dockedItems = [],
                downloadButton,
                downloadFn,
                urlTemplate;

            // XXX possibleSortStates is not documented, but is used by
            // Ext.grid.column.Column to determine what direction is
            // used for sorting when a column header is clicked.
            // Putting "DESC" first in the list results in the column
            // being ordered in the descending direction on the first
            // click.
            this.columns = {
                defaults: {
                    xtype: 'numbercolumn',
                    format: '0,000.0',
                    align: 'right',
                    possibleSortStates: ['DESC', 'ASC'],
                    menuDisabled: true
                },
                items: [
                    {
                        xtype: 'gridcolumn',
                        header: this.label,
                        dataIndex: this.labelKey,
                        possibleSortStates: ['ASC', 'DESC'],
                        align: 'left',
                        width: 128
                    },
                    {
                        header: '# Jobs',
                        dataIndex: 'jobs',
                        format: '0,000',
                        width: 96
                    },
                    {
                        header: 'Avg. Job Size (cpus)',
                        dataIndex: 'avg_cpus',
                        width: 118
                    },
                    {
                        header: 'Avg. Wait Time (h)',
                        dataIndex: 'avg_wait',
                        width: 118
                    },
                    {
                        header: 'Wall Time (d)',
                        dataIndex: 'wallt',
                        width: 128
                    },
                    {
                        header: 'Avg. Mem (MB)',
                        dataIndex: 'avg_mem',
                        width: 128
                    }
                ]
            };

            // If a download URL is supplied add a toolbar with a
            // download button.
            if (this.downloadUrl) {
                urlTemplate = new Ext.XTemplate(this.downloadUrl);

                downloadFn = function (format) {
                    var url = urlTemplate.apply({ format: format }),
                        params = this.store.proxy.extraParams,
                        querySegments = [],
                        gridState = this.getState();

                    if (gridState.sort !== undefined) {
                        params.sort = gridState.sort.property;
                        params.dir  = gridState.sort.direction;
                    }

                    Ext.Object.each(params, function (key, value) {
                        if (value !== null) {
                            var encodedValue = encodeURIComponent(value);
                            querySegments.push(key + '=' + encodedValue);
                        }
                    });

                    window.location = url + '?' + querySegments.join('&');
                };

                downloadButton = Ext.create('Ext.Button', {
                    text: 'Export Data',
                    menu: [
                        {
                            text: 'CSV - Comma Separated Values',
                            listeners: {
                                click: {
                                    scope: this,
                                    fn: function () {
                                        downloadFn.call(this, 'csv');
                                    }
                                }
                            }
                        },
                        {
                            text: 'TSV - Tab Separated Values',
                            listeners: {
                                click: {
                                    scope: this,
                                    fn: function () {
                                        downloadFn.call(this, 'tsv');
                                    }
                                }
                            }
                        },
                        {
                            text: 'XLS - Microsoft Excel',
                            listeners: {
                                click: {
                                    scope: this,
                                    fn: function () {
                                        downloadFn.call(this, 'xls');
                                    }
                                }
                            }
                        }
                    ]
                });

                dockedItems.push(Ext.create('Ext.toolbar.Toolbar', {
                    dock: 'top',
                    items: ['->', downloadButton]
                }));
            }

            dockedItems.push(Ext.create('Ubmod.widget.PagingToolbar', {
                dock: 'bottom',
                store: this.store,
                displayInfo: true
            }));

            this.dockedItems = dockedItems;

            this.callParent(arguments);
        }
    });

    /**
     * Panel for various tag views.
     */
    Ext.define('Ubmod.widget.TagPanel', {
        extend: 'Ext.tab.Panel',
        constructor: function (config) {
            config = config || {};

            this.model = config.model;

            Ext.apply(config, {
                activeTab: 0,
                plain: true,
                width: 745,
                height: 400,
                resizable: {
                    constrainTo: Ext.getBody(),
                    pinned: true,
                    handles: 's'
                },
                padding: '0 0 6 0'
            });

            this.callParent([config]);
        },

        initComponent: function () {
            var userTagGrid, tagStatsGrid, tagTreePanel;

            // User tags

            userTagGrid = Ext.create('Ubmod.widget.TagGrid', {
                title: 'User Tags'
            });

            userTagGrid.on('itemdblclick', function (grid, record) {
                var foundTab, userPanel;

                // Check if there is already a tab for this user
                this.items.each(function (item) {
                    if (item.user === record) {
                        this.setActiveTab(item);
                        foundTab = true;
                        return false;
                    }
                }, this);

                if (foundTab) { return; }

                userPanel = Ext.create('Ubmod.widget.UserTags', {
                    user: record,
                    closable: true
                });

                userPanel.on('userchanged', function () {
                    userTagGrid.store.load();
                    tagStatsGrid.store.load();
                }, this);

                this.add(userPanel).show();
            }, this);

            userTagGrid.on('userschanged', function () {
                tagStatsGrid.store.load();
            });

            // Tag activity

            this.tagActivity = Ext.create('Ubmod.store.TagActivity');

            tagStatsGrid = Ext.create('Ubmod.widget.StatsGrid', {
                title: 'Tag Activity',
                store: this.tagActivity,
                label: 'Tag',
                labelKey: 'name',
                downloadUrl: Ubmod.baseUrl + '/api/rest/{format}/tag/activity'
            });

            tagStatsGrid.on('itemdblclick', function (grid, record) {
                var foundTab, tagPanel;

                // Check if there is already a tab for this tag
                this.items.each(function (item) {
                    if (item.tag === record) {
                        this.setActiveTab(item);
                        foundTab = true;
                        return false;
                    }
                }, this);

                if (foundTab) { return; }

                tagPanel = Ext.create('Ubmod.widget.TagReport', {
                    tag: record,
                    closable: true,
                    autoScroll: true
                });

                this.add(tagPanel).show();
            }, this);

            tagStatsGrid.on('afterrender', this.reload, this);

            // Tag hierarchy

            tagTreePanel = Ext.create('Ubmod.widget.TagTreePanel', {
                title: 'Tag Hierarchy'
            });

            // Listeners

            this.on('beforedestroy', function () {
                this.model.removeListener('restparamschanged', this.reload,
                    this);
                this.removeAll();
            }, this);

            this.model.on('restparamschanged', this.reload, this);

            // Items

            this.items = [userTagGrid, tagStatsGrid, tagTreePanel];

            this.callParent(arguments);
        },

        reload: function () {
            if (!this.model.isReady()) { return; }

            var params = this.model.getRestParams();
            Ext.merge(this.tagActivity.proxy.extraParams, params);
            this.tagActivity.load();
        }
    });

    /**
     * Tag grid.
     */
    Ext.define('Ubmod.widget.TagGrid', {
        extend: 'Ext.grid.Panel',

        constructor: function (config) {
            config = config || {};

            /**
             * @event userschanged
             * Fires when a tag has been added to one or more users.
             * @param {Array} users The users that changed.
             */
            this.addEvents('userschanged');

            Ext.apply(config, {
                store: Ext.create('Ubmod.store.UserTags'),
                selModel: Ext.create('Ext.selection.CheckboxModel', {
                    checkOnly: true
                })
            });

            this.callParent([config]);
        },

        initComponent: function () {
            var tagRenderer, pagingToolbar, tagToolbar;

            tagRenderer = function (tags) {
                var output = '';

                Ext.each(tags, function (tag) {
                    if (output !== '') { output += ', '; }
                    output += tag;
                });

                return output;
            };

            this.columns = {
                defaults: {
                    menuDisabled: true
                },
                items: [
                    {
                        header: 'User',
                        dataIndex: 'name',
                        width: 128
                    },
                    {
                        header: 'Name',
                        dataIndex: 'display_name',
                        width: 128
                    },
                    {
                        header: 'Group',
                        dataIndex: 'group',
                        width: 128
                    },
                    {
                        header: 'Tags',
                        dataIndex: 'tags',
                        renderer: tagRenderer,
                        width: 318
                    }
                ]
            };

            pagingToolbar = Ext.create('Ubmod.widget.PagingToolbar', {
                dock: 'bottom',
                store: this.store,
                displayInfo: true
            });

            tagToolbar = Ext.create('Ubmod.widget.TaggingToolbar', {
                dock: 'top',
                buttonText: 'Add Tag to Selected Users'
            });

            tagToolbar.on('addtag', function (tag) {
                var selection = this.getSelectionModel().getSelection();

                if (tag === '') {
                    Ext.Msg.alert('Error', 'Please enter a tag');
                    return;
                }

                if (selection.length === 0) {
                    Ext.Msg.alert('Error', 'Please select one or more users');
                    return;
                }

                this.store.addTag(tag, selection, function () {
                    this.fireEvent('userschanged', selection);
                }, this);
            }, this);

            this.dockedItems = [pagingToolbar, tagToolbar];

            this.callParent(arguments);

            this.store.load();
        }
    });

    /**
     * Tag tree panel.
     */
    Ext.define('Ubmod.widget.TagTreePanel', {
        extend: 'Ext.tree.Panel',

        constructor: function (config) {
            config = config || {};

            Ext.apply(config, {
                store: Ext.create('Ubmod.store.TagTree'),
                displayField: 'name',
                rootVisible: false,
                viewConfig: {
                    plugins: {
                        ptype: 'treeviewdragdrop',
                        appendOnly: true
                    },
                    listeners: {
                        itemdblclick: {
                            scope: this,
                            fn: function (view, record) {
                                this.editTag(record);
                            }
                        },
                        drop: {
                            scope: this,
                            fn: function () {
                                this.down('[name="save"]').enable();
                            }
                        }
                    }
                }
            });

            this.callParent([config]);
        },

        initComponent: function () {
            var toolbar = Ext.create('Ext.toolbar.Toolbar', {
                items: [
                    {
                        text: 'New Tag',
                        scope: this,
                        handler: this.newTag
                    },
                    {
                        text: 'Edit Tag',
                        scope: this,
                        handler: this.editSelectedTags
                    },
                    {
                        text: 'Delete Tag',
                        scope: this,
                        handler: this.removeSelectedTags
                    },
                    {
                        text: 'Expand All',
                        scope: this,
                        handler: this.expandAll
                    },
                    {
                        text: 'Collapse All',
                        scope: this,
                        handler: this.collapseAll
                    },
                    '->',
                    {
                        text: 'Save Changes',
                        name: 'save',
                        disabled: true,
                        scope: this,
                        handler: this.save
                    }
                ]
            });

            this.dockedItems = [toolbar];

            this.callParent(arguments);
        },

        newTag: function () {
            var view = Ext.create('Ubmod.widget.TagEditor');

            view.down('button[action=save]').on('click', function () {
                var form = view.down('form'),
                    record = form.getRecord(),
                    values = form.getValues();

                record.set(values);
                this.addTag(record);

                // TODO: Add to tag store.

                view.close();
            }, this);
        },

        addTag: function (tag) {
            var store = this.store,
                parentId = tag.get('parent_id'),
                parent;

            parent = Ext.isEmpty(parentId) ?
                    store.getRootNode() :
                    store.getNodeById(parentId);

            parent.appendChild(tag);
            tag.phantom = true;

            this.enableSaveButton();
        },

        editSelectedTags: function () {
            var sm = this.getSelectionModel(),
                tags = sm.getSelection();

            if (tags.length === 0) { return; }

            Ext.each(tags, function (tag) {
                this.editTag(tag);
            }, this);
        },

        editTag: function (tag) {
            var view = Ext.create('Ubmod.widget.TagEditor', { tag: tag });

            view.down('button[action=save]').on('click', function () {
                var form   = view.down('form'),
                    record = form.getRecord(),
                    values = form.getValues();

                record.set(values);

                this.updateTag(record);

                view.close();
            }, this);
        },

        updateTag: function (tag) {
            var store    = this.store,
                node     = store.getNodeById(tag.get('tag_id')),
                parentId = tag.get('parent_id'),
                parent;

            parent = Ext.isEmpty(parentId) ?
                    store.getRootNode() :
                    store.getNodeById(parentId);

            parent.appendChild(tag);

            this.enableSaveButton();
        },

        removeSelectedTags: function () {
            var sm = this.getSelectionModel(),
                tags = sm.getSelection();

            if (tags.length === 0) { return; }

            Ext.each(tags, function (tag) {
                var msg = 'Are you sure you want to delete tag "' +
                    tag.get('name') + '"';

                Ext.Msg.show({
                    title: 'Delete tag?',
                    msg: msg,
                    buttons: Ext.Msg.OKCANCEL,
                    scope: this,
                    fn: function (buttonId) {
                        if (buttonId === "ok") {
                            this.removeTag(tag);
                        }
                    }
                });
            }, this);
        },

        removeTag: function (tag) {
            tag.remove();
            this.enableSaveButton();
            // TODO: Remove from tag store.
        },

        save: function () {
            this.store.sync();
            this.disableSaveButton();
            // TODO: Reload tag store.
        },

        enableSaveButton: function () {
            this.down('[name="save"]').enable();
        },

        disableSaveButton: function () {
            this.down('[name="save"]').disable();
        }
    });

    /**
     * Edit or create tag window.
     */
    Ext.define('Ubmod.widget.TagEditor', {
        extend: 'Ext.window.Window',

        border: false,
        layout: 'fit',
        autoShow: true,
        modal: true,

        constructor: function (config) {
            config = config || {};

            if (Ext.isEmpty(config.tag)) {
                this.title = 'New Tag';
                config.tag = Ext.create('Ubmod.model.Tag', {
                    expandable: false
                });
            } else {
                this.title = 'Edit Tag';
            }

            this.tag = config.tag;

            this.callParent([config]);
        },

        initComponent: function () {
            this.items = [
                {
                    xtype: 'form',
                    bodyPadding: 5,
                    items: [
                        {
                            xtype: 'textfield',
                            name: 'name',
                            fieldLabel: 'Name'
                        },
                        {
                            xtype: 'checkbox',
                            fieldLabel: 'No Parent',
                            scope: this,
                            handler: function (checkbox, checked) {
                                var combo = this.down('[name="parent_id"]');
                                if (checked) {
                                    combo.clearValue();
                                    combo.disable();
                                } else {
                                    combo.enable();
                                }
                            }
                        },
                        {
                            xtype: 'tagcombo',
                            name: 'parent_id',
                            fieldLabel: 'Parent'
                        }
                    ]
                }
            ];

            this.buttons = [
                {
                    text: 'Save',
                    action: 'save'
                },
                {
                    text: 'Cancel',
                    scope: this,
                    handler: this.close
                }
            ];

            this.callParent(arguments);

            this.down('form').loadRecord(this.tag);
        }
    });

    /**
     * Tag report panel.
     */
    Ext.define('Ubmod.widget.TagReport', {
        extend: 'Ext.panel.Panel',
        constructor: function (config) {
            config = config || {};

            this.tag = config.tag;

            Ext.apply(config, { title: this.tag.get('tag') });

            this.callParent([config]);
        },

        initComponent: function () {
            var partial;

            partial = Ubmod.app.createPartial({
                url: Ubmod.baseUrl + '/tag/details',
                params: { tag: this.tag.get('tag') }
            });

            this.items = [partial];

            this.callParent(arguments);
        }
    });

    /**
     * Panel for editing tags for a single user.
     */
    Ext.define('Ubmod.widget.UserTags', {
        extend: 'Ext.panel.Panel',

        constructor: function (config) {
            this.user = config.user;

            /**
             * @event userchanged
             * Fires when a tag has been added or removed from the user.
             * @param {Ubmod.model.UserTags} user The user that changed.
             */
            this.addEvents('userchanged');

            Ext.apply(config, {
                defaults: { margin: 10 },
                title: this.user.get('name')
            });

            this.callParent([config]);
        },

        initComponent: function () {
            var tagToolbar, userHeader;

            tagToolbar = Ext.create('Ubmod.widget.TaggingToolbar', {
                dock: 'top',
                buttonText: 'Add Tag to User'
            });

            tagToolbar.on('addtag', function (tag) {
                if (this.tagMap[tag] !== undefined) {
                    return;
                }

                this.tagPanel.setLoading(true);
                this.user.addTag(tag, function () {
                    this.addTag(tag);
                    this.fireEvent('userchanged', this.user);
                    this.tagPanel.setLoading(false);
                }, this);
            }, this);

            userHeader = Ext.create('Ext.Component', {
                html: '<div style="padding-top:5px;" class="labelHeading">' +
                      'User: <span class="labelHeader">' +
                      this.user.get('name') + '</span></div>'
            });

            // Maps tags to components in the tag panel
            this.tagMap = {};

            this.tagPanel = Ext.create('Ext.form.FieldSet', {
                title: 'Tags'
            });

            Ext.each(this.user.get('tags'), this.addTag, this);

            this.dockedItems = [tagToolbar];
            this.items       = [ userHeader, this.tagPanel ];

            this.callParent(arguments);
        },

        /**
         * Add a tag to the tag list.
         *
         * Also adds a remove button that will remove the tag from the
         * list when it is clicked.
         *
         * @param {String} tag The tag to add.
         */
        addTag: function (tag) {
            var button = Ext.create('Ext.Button', { text: 'Remove' });

            button.on('click', function () {
                this.tagPanel.setLoading(true);
                this.user.removeTag(tag, function () {
                    this.removeTag(tag);
                    this.fireEvent('userchanged', this.user);
                    this.tagPanel.setLoading(false);
                }, this);
            }, this);

            this.tagMap[tag] = this.tagPanel.add({
                xtype: 'container',
                layout: { type: 'hbox', align: 'middle' },
                defaults: { margin: 1 },
                items: [
                    button,
                    { xtype: 'component', html: tag }
                ]
            });
        },

        /**
         * Remove a tag from the tag list.
         *
         * @param {String} tag The tag to remove.
         */
        removeTag: function (tag) {
            this.tagPanel.remove(this.tagMap[tag]);

            delete this.tagMap[tag];
        }
    });

    /**
     * Panel used to display charts determined by tag keys.
     *
     * TODO: Rename this component
     */
    Ext.define('Ubmod.widget.TagKeyPanel', {
        extend: 'Ext.Container',
        alias: 'widget.tagkeypanel',

        constructor: function (config) {
            config = config || {};

            this.model = config.model;

            this.callParent([config]);
        },

        initComponent: function () {
            this.tagKeyInput = Ext.create('Ubmod.widget.TagKeyInput', {
                margin: '0 0 0 5'
            });

            this.tagKeyInput.on('select', function () {
                this.reload(
                    {
                        chart_type: 'tag',
                        tag_key: this.tagKeyInput.getValue()
                    },
                    { clear: true, add: this.tagKeyInput.getValue() }
                );

                this.tagKeyInput.clearValue();
            }, this);

            this.breadcrumbs = Ext.create('Ext.Container', {
                layout: { type: 'hbox', align: 'middle' },
                margin: '5 0 5 0'
            });
            this.breadcrumbBuffer = null;

            this.report = Ext.create('Ubmod.widget.Partial', {
                model: this.model,
                url: Ubmod.baseUrl + '/tag/keyDetails'
            });

            this.items = [
                {
                    xtype: 'container',
                    layout: { type: 'hbox', align: 'middle' },
                    items: [
                        { xtype: 'component', html: 'Tag Key:' },
                        this.tagKeyInput
                    ]
                },
                this.breadcrumbs,
                this.report
            ];

            this.callParent(arguments);
        },

        /**
         * Reload the charts.
         *
         * @param {Object} params The chart parameters.
         * @param {Object} bcOptions Breadcrumb options.
         */
        reload: function (params, bcOptions) {
            bcOptions = bcOptions || {};

            if (bcOptions.clear) {
                this.clearBreadcrumbs(bcOptions.count);
                this.breadcrumbBuffer = null;
            }

            if (bcOptions.add) {
                if (Ext.isObject(this.breadcrumbBuffer)) {
                    this.addBreadcrumb(
                        this.breadcrumbBuffer.label,
                        this.breadcrumbBuffer.params,
                        true
                    );
                }

                this.breadcrumbBuffer = {
                    label: bcOptions.add,
                    params: params
                };

                this.addBreadcrumb(bcOptions.add, params, false);
            }

            this.report.params = params;
            this.report.reload();
        },

        /**
         * Add a breadcrumb to the panel.
         *
         * @param {String} label The breadcrumb label.
         * @param {Object} param The chart parameters.
         * @param {Boolean} clickable Is the breadcrumb clickable?
         */
        addBreadcrumb: function (label, params, clickable) {
            var options, breadcrumb, count;

            options = { border: false, html: label };

            if (clickable) {
                // XXX Assumes that a clickable breadcrumb is always
                // replacing a non-clickable breadcrumb.
                count = this.breadcrumbs.items.getCount();
                this.clearBreadcrumbs(count - 2);

                options.style = {
                    color: '#0000ff',
                    textDecoration: 'underline',
                    cursor: 'pointer'
                };
            }

            breadcrumb = Ext.create('Ext.Component', options);

            count = this.breadcrumbs.items.getCount();

            this.breadcrumbs.add([
                {
                    xtype: 'component',
                    border: false,
                    margin: '0 5 0 5',
                    html: '>'
                },
                breadcrumb
            ]);

            if (clickable) {
                // Ext.Component does not have a "click" event, so use
                // the element instead.
                breadcrumb.getEl().on('click', function () {
                    this.reload(params, {
                        clear: true,
                        count: count,
                        add: label
                    });
                }, this);
            }
        },

        /**
         * Remove some or all breadcrumbs.
         *
         * @param {Number} excludeCount The number of items that should
         *     not be removed.
         */
        clearBreadcrumbs: function (excludeCount) {
            var count, i;

            if (excludeCount) {
                count = this.breadcrumbs.items.getCount();

                for (i = count; i > excludeCount; i = i - 1) {
                    this.breadcrumbs.remove(
                        this.breadcrumbs.items.getAt(i - 1),
                        true
                    );
                }
            } else {
                this.breadcrumbs.removeAll();
            }
        }
    });

    /**
     * Tag auto-completing combo box.
     */
    Ext.define('Ubmod.widget.TagInput', {
        extend: 'Ext.form.field.ComboBox',
        alias: 'widget.tagcombo',
        store: 'tagStore',
        displayField: 'name',
        valueField: 'tag_id',
        emptyText: 'Tag...',
        allowBlank: true,
        forceSelection: true
    });

    /**
     * Tag key auto-completing combo box.
     */
    Ext.define('Ubmod.widget.TagKeyInput', {
        extend: 'Ext.form.field.ComboBox',
        store: Ext.create('Ubmod.store.TagKey'),
        displayField: 'name',
        emptyText: 'Tag Key...',
        hideLabel: true,
        minChars: 1
    });

    /**
     * Paging toolbar with a search box.
     */
    Ext.define('Ubmod.widget.PagingToolbar', {
        extend: 'Ext.toolbar.Paging',

        constructor: function (config) {
            config = config || {};

            this.key = config.key;

            this.callParent([config]);
        },

        initComponent: function () {
            var filter = Ext.create('Ext.form.field.Text', {
                enableKeyEvents: true
            });

            filter.on('keypress', function (text, e) {
                if (e.getKey() === e.ENTER) {
                    this.applyFilter(text.getValue());
                }
            }, this);

            filter.on('keyup', function (text, e) {
                if (e.getKey() === e.BACKSPACE &&
                        text.getValue().length === 0) {
                    this.clearFilter();
                }
            }, this);

            this.items = [ '-', 'Search:', filter ];

            this.callParent(arguments);
        },

        /**
         * Filter the store by the given keyword.
         *
         * Filters out any records whose key field does not contain the
         * keyword as a substring. Also moves the store to the first
         * page.
         *
         * @param {String} keyword The string to filter for.
         */
        applyFilter: function (keyword) {
            Ext.apply(this.store.proxy.extraParams, { filter: keyword });
            this.moveFirst();
        },

        /**
         * Remove any filters from the store.
         *
         * Also moves the store to the first page.
         */
        clearFilter: function () {
            Ext.apply(this.store.proxy.extraParams, { filter: '' });
            this.moveFirst();
        }
    });

    /**
     * Toolbar with a tag input.
     */
    Ext.define('Ubmod.widget.TaggingToolbar', {
        extend: 'Ext.toolbar.Toolbar',

        constructor: function (config) {
            config = config || {};

            this.buttonText = config.buttonText || 'Add Tag';

            /**
             * @event addtag
             * Fires when a tag should be added.
             * @param {String} tag The text entered in the toolbar.
             */
            this.addEvents('addtag');

            this.callParent([config]);
        },

        initComponent: function () {
            var tagInput, addButton;

            tagInput = Ext.create('Ubmod.widget.TagInput');

            addButton = Ext.create('Ext.Button', { text: this.buttonText });

            addButton.on('click', function () {
                this.fireEvent('addtag', tagInput.getRawValue());
            }, this);

            this.items = [ 'Tag:', tagInput, addButton ];

            this.callParent(arguments);
        }
    });

    /**
     * Component used for loading pages using AJAX.
     */
    Ext.define('Ubmod.widget.Partial', {
        extend: 'Ext.Component',

        constructor: function (config) {
            config = config || {};

            this.model  = config.model;
            this.url    = config.url;
            this.params = config.params || {};

            this.callParent([config]);
        },

        initComponent: function () {
            var reload = function () { this.reload(); };

            // Add a listener to update the partial when the model
            // parameters have changed. Remove it when the component is
            // destroyed.
            this.model.on('restparamschanged', reload, this);

            this.on('beforedestroy', function () {
                this.model.removeListener('restparamschanged', reload, this);
            }, this);

            // Don't load the initial partial until after the component
            // has been rendered. This ensures the component element has
            // been added to the DOM.
            this.on('afterrender', reload, this);

            this.callParent(arguments);
        },

        /**
         * Reloads the element.
         */
        reload: function () {
            if (!this.model.isReady()) { return; }

            Ext.get(this.getEl()).load({
                loadMask: 'Loading...',
                url: this.url,
                scripts: true,
                params: Ext.apply(this.model.getRestParams(), this.params)
            });
        }
    });

    /**
     * Chart image map.
     */
    Ext.define('Ubmod.ChartMap', {
        id: null,
        imageId: null,
        areas: null,
        toolTip: null,

        constructor: function (config) {
            var map, mapTpl, areaTpl;

            this.id      = config.id;
            this.imageId = config.imageId;
            this.areas   = {};

            // Create map and area HTML elements.
            mapTpl = new Ext.Template('<map id="{id}" name="{id}"></map>');

            map = mapTpl.append(Ext.getBody(), { id: this.id });

            areaTpl = new Ext.Template(
                '<area id="{id}" shape="{shape}" coords="{coords}" />'
            );

            Ext.each(config.areas, function (item) {
                var area = areaTpl.append(map, item);

                this.areas[item.id] = item;

                // If the area has params add the click listener to
                // drill-down.
                if (!Ext.isEmpty(item.params)) {
                    Ext.EventManager.addListener(area, 'click', function () {
                        var panels = Ext.ComponentQuery.query(
                            '[xtype="tagkeypanel"]'
                        );

                        if (panels.length !== 0) {
                            panels[0].reload(item.params, {
                                add: item.label
                            });
                        }
                    });
                }
            }, this);

            Ext.getDom(this.imageId).useMap = '#' + this.id;

            this.toolTip = Ext.create('Ext.tip.ToolTip', {
                target: map,
                trackMouse: true,
                showDelay: 200,
                hideDelay: 0,
                renderTo: Ext.getBody(),
                delegate: 'area',
                listeners: {
                    beforeshow: {
                        scope: this,
                        fn: function (tip) {
                            var area = this.areas[tip.triggerElement.id];
                            tip.update(area.title + ': ' + area.value);
                        }
                    }
                }
            });

            this.callParent([config]);
        },

        /**
         * Associate an image with this map.
         *
         * @param {String} imageId The id of the image HTML element.
         */
        setImage: function (imageId) {
            var image;

            image = Ext.getDom(this.imageId);

            if (!Ext.isEmpty(image)) {
                image.useMap = '';
            }

            image = Ext.getDom(imageId);

            if (!Ext.isEmpty(image)) {
                image.useMap = '#' + this.id;
                this.imageId = imageId;
            } else {
                Ext.Error.raise('Failed to set image "' + imageId + '"');
            }
        },

        /**
         * Destroy the chart map.
         */
        destroy: function () {
            var image;

            image = Ext.getDom(this.imageId);
            if (!Ext.isEmpty(image)) {
                image.useMap = '';
            }

            this.toolTip.destroy();

            // Remove all listeners from map areas
            Ext.Object.each(this.areas, function (id) {
                var el = Ext.getDom(id);
                Ext.EventManager.removeAll(el);
            }, this);

            Ext.removeNode(Ext.getDom(this.id));
        }
    });

    /**
     * Application object.
     */
    Ubmod.app = (function () {
        var model, widgets, chartMaps;

        return {

            /**
             * Initiliaze the application.
             *
             * Creates the app model, adds listeners to that model and
             * the menu links. Also creates the toolbar.
             */
            init: function () {

                // Register tag store with StoreManager.
                Ext.create('Ubmod.store.Tag');

                model = Ext.create('Ubmod.model.App');
                widgets = [];
                chartMaps = {};

                model.on('daterangechanged', function (start, end) {
                    Ext.get('date-display').update(start + ' thru ' + end);
                });

                // Listen for clicks on menu links.
                Ext.select('#menu-list a').each(function () {
                    var href = this.getAttribute('href');
                    this.on('click', function (evt, el) {

                        Ext.each(widgets, function () { this.destroy(); });
                        widgets = [];

                        Ext.Object.each(chartMaps, function (key, value) {
                            value.destroy();
                        });
                        chartMaps = {};

                        // Load the new content.
                        Ext.get('content').load({
                            loadMask: 'Loading...',
                            url: href,
                            scripts: true
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
             * Creates a component that should be updated whenever the
             * app model is changed.
             *
             * @param {Object} config Constructor arguments.
             *
             * @return {Ubmod.widget.Partial}
             */
            createPartial: function (config) {
                var partial;

                config.model = model;
                partial = Ext.create('Ubmod.widget.Partial', config);
                widgets.push(partial);

                return partial;
            },

            /**
             * Creates a stats panel that should be updated whenever the
             * app model is changed.
             *
             * @param {Object} config Constructor arguments.
             *
             * @return {Ubmod.widget.StatsPanel}
             */
            createStatsPanel: function (config) {
                var panel;

                config.model = model;
                panel = Ext.create('Ubmod.widget.StatsPanel', config);
                widgets.push(panel);

                return panel;
            },

            /**
             * Creates a tag management panel.
             *
             * @param {Object} config Constructor arguments.
             *
             * @return {Ubmod.widget.TagPanel}
             */
            createTagPanel: function (config) {
                var panel;

                config.model = model;
                panel = Ext.create('Ubmod.widget.TagPanel', config);
                widgets.push(panel);

                return panel;
            },

            /**
             * Creates a tag key report panel.
             *
             * @param {Object} config Constructor arguments.
             *
             * @return {Ubmod.widget.TagKeyPanel}
             */
            createTagKeyPanel: function (config) {
                var panel;

                config.model = model;
                panel = Ext.create('Ubmod.widget.TagKeyPanel', config);
                widgets.push(panel);

                return panel;
            },

            /**
             * Extend the height of the current main panel.
             */
            extendPanelHeight: function () {
                var panels, panel, position, bodyHeight, panelHeight,
                    newHeight;

                panels = Ext.ComponentQuery.query('tabpanel');

                if (panels.length === 0) {
                    return;
                }

                panel = panels[0];

                position = panel.getPosition();
                panelHeight = panel.getHeight();

                bodyHeight = Ext.getBody().getViewSize().height;

                newHeight = bodyHeight - position[1];

                if (newHeight > panelHeight) {
                    panel.setHeight(newHeight);
                }
            },

            /**
             * Load a chart.
             *
             * @param {String} id The img tag id.
             * @param {String} model The data model identifier.
             * @param {String} type The chart type identifier.
             * @param {Object} params The chart parameters.
             */
            loadChart: function (id, model, type, params) {
                Ext.Ajax.request({
                    url: Ubmod.baseUrl + '/api/rest/json/chart/cache',
                    params: {
                        model: model,
                        type: type,
                        params: Ext.encode(params)
                    },
                    success: function (response) {
                        var retval = Ext.JSON.decode(response.responseText);
                        Ext.select('#' + id).set({ src: retval.img_url });
                        if (type !== 'stackedArea' && type !== 'monthly') {
                            Ext.defer(
                                this.loadChartMap,
                                200,
                                this,
                                [id, retval.id, retval.map_url]
                            );
                        }
                    },
                    failure: function () {
                        Ext.Msg.alert('Error', 'Failed to load chart');
                    },
                    timeout: 120000,
                    scope: this
                });
            },

            loadChartMap: function (imageId, cacheId, url, retryCount) {
                var mapId = 'chart-map-' + cacheId;

                if (chartMaps[cacheId] !== undefined) {
                    chartMaps[cacheId].setImage(imageId);
                    return;
                }

                Ext.Ajax.request({
                    url: url,
                    success: function (response) {
                        var areas = Ext.decode(response.responseText);

                        if (areas.length === 0) {
                            retryCount =
                                Ext.isEmpty(retryCount) ? 1 : retryCount + 1;

                            if (retryCount < 4) {
                                Ext.defer(
                                    this.loadChartMap,
                                    300 * retryCount,
                                    this,
                                    [imageId, cacheId, url, retryCount]
                                );
                            }

                            return;
                        }

                        chartMaps[cacheId] = Ext.create('Ubmod.ChartMap', {
                            id: mapId,
                            imageId: imageId,
                            areas: areas
                        });
                    },
                    failure: function () {
                        Ext.Msg.alert('Error', 'Failed to load chart map');
                    },
                    scope: this
                });
            }
        };
    }());

    Ext.onReady(Ubmod.app.init, Ubmod);

}, window, false);

