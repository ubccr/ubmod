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
     * UBMoD namespace.
     */
    Ext.namespace('Ubmod');

    /**
     * Time interval model.
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
     * Cluster model.
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
     * User activity model.
     */
    Ext.define('Ubmod.model.UserActivity', {
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
     * User tags model.
     */
    Ext.define('Ubmod.model.UserTags', {
        extend: 'Ext.data.Model',
        fields: [
            'user_id',
            'user',
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
                url: '/api/rest/json/user/updateTags',
                params: { userId: this.get('user_id'), 'tags[]': tags },
                success: function (response) {
                    var tags = Ext.JSON.decode(response.responseText).tags;
                    this.set('tags', tags);
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
            'group_name',
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
            'queue',
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
            { name: 'tag',      type: 'string' },
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
            'name'
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
         * Override the set method to fire events.
         *
         * @see Ext.data.Model
         */
        set: function (field, value) {
            Ubmod.model.App.superclass.set.call(this, field, value);

            // @see Ext.data.Model.set for implementation details
            if (!Ext.isObject(field)) {

                // Ignore individual dates
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
         * @return {Boolean} True if both fields are defined.
         */
        isReady: function () {
            return this.get('interval') !== undefined &&
                this.get('cluster') !== undefined;
        },

        /**
         * @return {Number} The currently selected interval ID.
         */
        getIntervalId: function () {
            return this.get('interval').get('interval_id');
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
         * @return {Object} The parameters needed for REST requests.
         */
        getRestParams: function () {
            var interval, params;

            interval = this.get('interval');

            params = {
                'interval_id': this.getIntervalId(),
                'cluster_id': this.getClusterId()
            };

            if (interval.isCustomDateRange()) {
                params.start_date = this.getStartDate();
                params.end_date   = this.getEndDate();
            }

            return params;
        }
    });

    /**
     * Data store that reverses sorting.
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
     * Time interval data store.
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
     * Cluster data store.
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
     * User activity data store.
     */
    Ext.define('Ubmod.store.UserActivity', {
        extend: 'Ubmod.data.ReverseSortStore',

        constructor: function (config) {
            config = config || {};
            Ext.apply(config, {
                model: 'Ubmod.model.UserActivity',
                remoteSort: true,
                pageSize: 25,
                proxy: {
                    type: 'ajax',
                    simpleSortMode: true,
                    url: '/api/rest/json/user/activity',
                    reader: { type: 'json', root: 'users' },
                    extraParams: { sort: 'wallt', dir: 'DESC' }
                }
            });
            Ubmod.store.UserActivity.superclass.constructor.call(this, config);
        }
    });

    /**
     * User tag data store.
     */
    Ext.define('Ubmod.store.UserTags', {
        extend: 'Ubmod.data.ReverseSortStore',

        constructor: function (config) {
            config = config || {};
            Ext.apply(config, {
                model: 'Ubmod.model.UserTags',
                remoteSort: true,
                pageSize: 25,
                proxy: {
                    type: 'ajax',
                    simpleSortMode: true,
                    url: '/api/rest/json/user/tags',
                    reader: { type: 'json', root: 'users' },
                    extraParams: { sort: 'user', dir: 'ASC' }
                }
            });
            Ubmod.store.UserTags.superclass.constructor.call(this, config);
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
                url: '/api/rest/json/user/addTag',
                params: { tag: tag, 'userIds[]': userIds },
                success: function () {
                    this.load();
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
        extend: 'Ubmod.data.ReverseSortStore',

        constructor: function (config) {
            config = config || {};
            Ext.apply(config, {
                model: 'Ubmod.model.GroupActivity',
                remoteSort: true,
                pageSize: 25,
                proxy: {
                    type: 'ajax',
                    simpleSortMode: true,
                    url: '/api/rest/json/group/activity',
                    reader: { type: 'json', root: 'groups' },
                    extraParams: { sort: 'wallt', dir: 'DESC' }
                }
            });
            Ubmod.store.GroupActivity.superclass.constructor.call(this, config);
        }
    });

    /**
     * Queue activity data store.
     */
    Ext.define('Ubmod.store.QueueActivity', {
        extend: 'Ubmod.data.ReverseSortStore',

        constructor: function (config) {
            config = config || {};
            Ext.apply(config, {
                model: 'Ubmod.model.QueueActivity',
                remoteSort: true,
                pageSize: 25,
                proxy: {
                    type: 'ajax',
                    simpleSortMode: true,
                    url: '/api/rest/json/queue/activity',
                    reader: { type: 'json', root: 'queues' },
                    extraParams: { sort: 'wallt', dir: 'DESC' }
                }
            });
            Ubmod.store.QueueActivity.superclass.constructor.call(this, config);
        }
    });

    /**
     * Tag activity data store.
     */
    Ext.define('Ubmod.store.TagActivity', {
        extend: 'Ubmod.data.ReverseSortStore',

        constructor: function (config) {
            config = config || {};
            Ext.apply(config, {
                model: 'Ubmod.model.TagActivity',
                remoteSort: true,
                pageSize: 25,
                proxy: {
                    type: 'ajax',
                    simpleSortMode: true,
                    url: '/api/rest/json/tag/activity',
                    reader: { type: 'json', root: 'tags' },
                    extraParams: { sort: 'wallt', dir: 'DESC' }
                }
            });
            Ubmod.store.QueueActivity.superclass.constructor.call(this, config);
        }
    });

    /**
     * Tag store.
     */
    Ext.define('Ubmod.store.Tag', {
        extend: 'Ext.data.Store',
        constructor: function (config) {
            config = config || {};
            Ext.apply(config, {
                model: 'Ubmod.model.Tag',
                proxy: {
                    type: 'ajax',
                    simpleSortMode: true,
                    url: '/api/rest/json/tag/list',
                    reader: { type: 'json', root: 'tags' }
                }
            });
            Ubmod.store.Tag.superclass.constructor.call(this, config);
        }
    });

    /**
     * Time interval combo box.
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
     * Cluster combo box.
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
     * Toolbar for time period and cluster.
     */
    Ext.define('Ubmod.widget.Toolbar', {
        extend: 'Ext.toolbar.Toolbar',

        constructor: function (config) {
            config = config || {};
            Ext.apply(config, { height: 33 });
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

            this.renderTo = Ext.get('toolbar');
            this.items = [
                'Cluster:',
                this.clusterCombo,
                { xtype: 'tbspacer', width: 20 },
                'Period:',
                this.intervalCombo,
                { xtype: 'tbspacer', width: 20 },
                this.dateRange
            ];

            Ubmod.widget.Toolbar.superclass.initComponent.call(this);
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
                var id, params, tab;

                id = record.get(this.recordFormat.id);

                if (this.detailTabs[id] !== undefined) {
                    this.setActiveTab(this.detailTabs[id]);
                    return;
                }

                params = Ext.merge(this.model.getRestParams(), { id: id });

                tab = this.add({
                    title: record.get(this.recordFormat.key),
                    closable: true,
                    loader: {
                        url: this.recordFormat.detailsUrl,
                        autoLoad: true,
                        params: params
                    }
                });

                tab.on('beforeclose', function () {
                    delete this.detailTabs[id];
                }, this);

                tab.show();

                this.detailTabs[id] = tab;
            }, this);

            // XXX Force the tab panel to recalculate it's layout when
            // the grid is resized.
            this.grid.on('resize', this.doLayout, this);

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
                var detailParams = Ext.merge({ id: id }, params);
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

            this.label = config.label;
            this.labelKey = config.labelKey;

            Ubmod.widget.StatsGrid.superclass.constructor.call(this, config);
        },

        initComponent: function () {
            var pagingToolbar;

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

            pagingToolbar = Ext.create('Ubmod.widget.PagingToolbar', {
                dock: 'bottom',
                store: this.store,
                displayInfo: true
            });

            this.dockedItems = [pagingToolbar];

            Ubmod.widget.StatsGrid.superclass.initComponent.call(this);
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
                width: 745,
                plain: true
            });

            Ubmod.widget.TagPanel.superclass.constructor.call(this, config);
        },

        initComponent: function () {
            var userTagGrid, tagStatsGrid;

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

            this.tagActivity = Ext.create('Ubmod.store.TagActivity');

            tagStatsGrid = Ext.create('Ubmod.widget.StatsGrid', {
                title: 'Tag Activity',
                store: this.tagActivity,
                height: 400,
                label: 'Tag',
                labelKey: 'tag'
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
                    closable: true
                });

                tagPanel.on('reportloaded', this.doComponentLayout, this);

                this.add(tagPanel).show();
            }, this);

            this.reload();

            this.items = [userTagGrid, tagStatsGrid];

            Ubmod.widget.TagPanel.superclass.initComponent.call(this);
        },

        reload: function () {
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
            Ext.apply(config, {
                height: 400,
                store: Ext.create('Ubmod.store.UserTags'),
                selModel: Ext.create('Ext.selection.CheckboxModel', {
                    checkOnly: true
                })
            });
            Ubmod.widget.TagGrid.superclass.constructor.call(this, config);
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

            this.columns = [{
                header: 'User',
                dataIndex: 'user',
                menuDisabled: true,
                width: 128
            }, {
                header: 'Tags',
                dataIndex: 'tags',
                renderer: tagRenderer,
                menuDisabled: true,
                width: 574
            }];

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

                this.store.addTag(tag, selection);
            }, this);

            this.dockedItems = [pagingToolbar, tagToolbar];

            Ubmod.widget.TagGrid.superclass.initComponent.call(this);

            this.store.load();
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

            /**
             * @event reportloaded
             * Fires after the report partial has loaded.
             */
            this.addEvents({ reportloaded: true });

            Ext.apply(config, { title: this.tag.get('tag') });

            Ubmod.widget.TagReport.superclass.constructor.call(this, config);
        },

        initComponent: function () {
            var partial;

            partial = Ubmod.app.createPartial({
                url: '/tag/details',
                params: { tag: this.tag.get('tag') }
            });

            partial.on('afterload', function () {
                this.doComponentLayout();
                this.fireEvent('reportloaded');
            }, this);

            this.items = [partial];

            Ubmod.widget.TagReport.superclass.initComponent.call(this);
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
            this.addEvents({ userchanged: true });

            Ext.apply(config, {
                defaults: { margin: 10 },
                title: this.user.get('user')
            });

            Ubmod.widget.UserTags.superclass.constructor.call(this, config);
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
                      this.user.get('user') + '</span></div>'
            });

            // Maps tags to components in the tag panel
            this.tagMap = {};

            this.tagPanel = Ext.create('Ext.form.FieldSet', {
                title: 'Tags'
            });

            Ext.each(this.user.get('tags'), this.addTag, this);

            this.dockedItems = [tagToolbar];
            this.items       = [ userHeader, this.tagPanel ];

            Ubmod.widget.UserTags.superclass.initComponent.call(this);
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
     * Tag auto-completing combo box.
     */
    Ext.define('Ubmod.widget.TagInput', {
        extend: 'Ext.form.field.ComboBox',

        constructor: function (config) {
            config = config || {};

            Ext.apply(config, {
                store: Ext.create('Ubmod.store.Tag'),
                displayField: 'name',
                hideLabel: true,
                hideTrigger: true,
                minChars: 1
            });

            Ubmod.widget.TagInput.superclass.constructor.call(this, config);
        }
    });

    /**
     * Paging toolbar with a search box.
     */
    Ext.define('Ubmod.widget.PagingToolbar', {
        extend: 'Ext.toolbar.Paging',

        constructor: function (config) {
            config = config || {};

            this.key = config.key;

            Ubmod.widget.PagingToolbar.superclass.constructor.call(this,
                config);
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

            Ubmod.widget.PagingToolbar.superclass.initComponent.call(this);
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
            this.addEvents({ addtag: true });

            Ubmod.widget.TaggingToolbar.superclass.constructor.call(this,
                config);
        },

        initComponent: function () {
            var tagInput, addButton;

            tagInput = Ext.create('Ubmod.widget.TagInput');

            addButton = Ext.create('Ext.Button', { text: this.buttonText });

            addButton.on('click', function () {
                this.fireEvent('addtag', tagInput.getValue());
            }, this);

            this.items = [ 'Tag:', tagInput, addButton ];

            Ubmod.widget.TaggingToolbar.superclass.initComponent.call(this);
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

            this.addEvents({ afterload: true });

            Ubmod.widget.Partial.superclass.constructor.call(this, config);
        },

        initComponent: function () {
            var reload = function () { this.reload(); };

            // Add a listener to update the partial when the model
            // parameters have changed. Remove it when the component is
            // destroyed.
            this.model.on('restparamschanged', reload, this);

            this.on('destroy', function () {
                this.model.removeListener('restparamschanged', reload, this);
            }, this);

            // Don't load the initial partial until after the component
            // has been rendered. This ensures the component element has
            // been added to the DOM.
            this.on('afterrender', reload, this);

            Ubmod.widget.Partial.superclass.initComponent.call(this);
        },

        /**
         * Reloads the element.
         */
        reload: function () {
            if (!this.model.isReady()) { return; }

            Ext.get(this.getEl()).load({
                loadMask: 'Loading...',
                url: this.url,
                params: Ext.apply(this.params, this.model.getRestParams()),
                success: function () { this.fireEvent('afterload'); },
                scope: this
            });
        }
    });

    /**
     * Application object.
     */
    Ubmod.app = (function () {
        var model, widgets;

        return {

            /**
             * Initiliaze the application.
             *
             * Creates the app model, adds listeners to that model and
             * the menu links. Also creates the toolbar.
             */
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
             * Creates a component that should be updated whenever the
             * time interval or cluster is changed.
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
             * time interval or cluster is changed.
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
            }
        };
    }());

    Ext.onReady(Ubmod.app.init, Ubmod);

}, window, false);
