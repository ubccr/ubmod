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
     * Models
     */
    Ext.define('Ubmod.model.Interval', {
        extend: 'Ext.data.Model',
        fields: [
            'interval_id',
            'time_interval',
            'start',
            'end'
        ]
    });

    Ext.define('Ubmod.model.Cluster', {
        extend: 'Ext.data.Model',
        fields: [
            'cluster_id',
            'host',
            'display_name'
        ]
    });

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

    Ext.define('Ubmod.model.App', {
        extend: 'Ext.data.Model',
        fields: [
            { name: 'interval', type: 'Ubmod.model.Interval' },
            { name: 'cluster', type: 'Ubmod.model.Cluster' }
        ],

        constructor: function (config) {
            config = config || {};
            this.addEvents({ fieldchanged: true });
            Ubmod.model.App.superclass.constructor.call(this, config);
        },

        set: function (field, value) {
            Ubmod.model.App.superclass.set.call(this, field, value);
            if (!Ext.isObject(field)) {
                this.fireEvent('fieldchanged', field, value);
            }
        },

        isReady: function () {
            return this.get('interval') !== undefined &&
                this.get('cluster') !== undefined;
        },

        getIntervalId: function () {
            return this.get('interval').get('interval_id');
        },

        getClusterId: function () {
            return this.get('cluster').get('cluster_id');
        }
    });

    /**
     * Data stores
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

    Ext.define('Ubmod.store.User', {
        extend: 'Ext.data.Store',

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

    Ext.define('Ubmod.store.Group', {
        extend: 'Ext.data.Store',

        constructor: function (config) {
            config = config || {};
            Ext.apply({
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

    Ext.define('Ubmod.store.Queue', {
        extend: 'Ext.data.Store',

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
     * Widgets
     */
    Ext.define('Ubmod.widget.Interval', {
        extend: 'Ext.form.field.ComboBox',

        constructor: function (config) {
            config = config || {};
            Ext.apply(config, { editable: false });
            Ubmod.widget.Interval.superclass.constructor.call(this, config);
        },

        initComponent: function () {
            this.store = Ext.create('Ubmod.store.Interval');
            this.displayField = 'time_interval';
            this.valueField = 'interval_id';
            this.queryMode = 'local';
            this.emptyText = 'Interval...';

            Ubmod.widget.Interval.superclass.initComponent.call(this);

            this.store.load({
                scope: this,
                callback: function (records) {
                    this.setValue(records[3].get(this.valueField));
                    this.fireEvent('select', this, [records[3]]);
                }
            });
        }
    });

    Ext.define('Ubmod.widget.Cluster', {
        extend: 'Ext.form.field.ComboBox',

        constructor: function (config) {
            config = config || {};
            Ext.apply(config, { editable: false });
            Ubmod.widget.Cluster.superclass.constructor.call(this, config);
        },

        initComponent: function () {
            this.store = Ext.create('Ubmod.store.Cluster');
            this.displayField = 'display_name';
            this.valueField = 'cluster_id';
            this.queryMode = 'local';
            this.emptyText = 'Cluster...';

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

            this.renderTo = Ext.get('toolbar');
            this.items = [
                'Period:',
                this.intervalCombo,
                { xtype: 'tbspacer', width: 20 },
                'Cluster:',
                this.clusterCombo
            ];

            Ubmod.widget.Toolbar.superclass.initComponent.call(this);
        }
    });

    Ext.define('Ubmod.widget.StatsPanel', {
        extend: 'Ext.tab.Panel',

        constructor: function (config) {
            config = config || {};

            this.model = config.model;
            this.store = config.store;
            this.recordFormat = config.recordFormat;
            this.detailTabs = [];

            this.grid = Ext.create('Ubmod.widget.Grid', {
                title: config.gridTitle,
                store: this.store,
                label: this.recordFormat.label,
                labelKey: this.recordFormat.key
            });

            Ext.apply(config, {
                plain: true,
                items: [ this.grid ]
            });

            Ubmod.widget.StatsPanel.superclass.constructor.call(this, config);
        },

        initComponent: function () {
            var listener = function (field) {
                if (field === 'interval' || field === 'cluster') {
                    this.reload();
                }
            };
            this.model.on('fieldchanged', listener, this);
            this.on('destroy', function () {
                this.model.removeListener('fieldchanged', listener, this);
            }, this);

            Ubmod.widget.StatsPanel.superclass.initComponent.call(this);

            this.grid.on('itemdblclick', function (grid, record) {
                this.detailTabs.push({
                    id: record.get(this.recordFormat.id),
                    tab: this.add({
                        title: record.get(this.recordFormat.key),
                        closable: true,
                        loader: {
                            url: this.recordFormat.detailsUrl,
                            autoLoad: true,
                            params: {
                                id: record.get(this.recordFormat.id),
                                interval_id: this.model.getIntervalId(),
                                cluster_id: this.model.getClusterId()
                            }
                        }
                    }).show()
                });
            }, this);

            this.reload();
        },

        reload: function () {
            if (!this.model.isReady()) { return; }

            Ext.merge(this.store.proxy.extraParams, {
                interval_id: this.model.getIntervalId(),
                cluster_id: this.model.getClusterId()
            });
            this.store.load();

            Ext.each(this.detailTabs, function (detail) {
                detail.tab.loader.load({
                    url: this.detailsUrl,
                    params: {
                        id: detail.id,
                        interval_id: this.model.getIntervalId(),
                        cluster_id: this.model.getClusterId()
                    }
                });
            }, this);
        }
    });

    Ext.define('Ubmod.widget.Grid', {
        extend: 'Ext.grid.Panel',

        constructor: function (config) {
            config = config || {};

            this.label = config.label;
            this.labelKey = config.labelKey;

            Ext.apply(config, { height: 400 });

            Ubmod.widget.Grid.superclass.constructor.call(this, config);
        },

        initComponent: function () {
            this.columns = [{
                header: this.label,
                dataIndex: this.labelKey,
                width: 128
            }, {
                header: '# Jobs',
                dataIndex: 'jobs',
                xtype: 'numbercolumn',
                format: '0,000',
                width: 100,
                align: 'right'
            }, {
                header: 'Avg. Job Size (cpus)',
                dataIndex: 'avg_cpus',
                xtype: 'numbercolumn',
                format: '0,000',
                width: 118,
                align: 'right'
            }, {
                header: 'Avg. Wait Time (h)',
                dataIndex: 'avg_wait',
                xtype: 'numbercolumn',
                format: '0,000.00',
                width: 118,
                align: 'right'
            }, {
                header: 'Wall Time (d)',
                dataIndex: 'wallt',
                xtype: 'numbercolumn',
                format: '0,000.0',
                width: 128,
                align: 'right'
            }, {
                header: 'Avg. Mem (MB)',
                dataIndex: 'avg_mem',
                xtype: 'numbercolumn',
                format: '0,000.0',
                width: 128,
                align: 'right'
            }];

            var filter = Ext.create('Ext.form.field.Text', {
                enableKeyEvents: true
            });

            filter.on('keypress', function (text, e) {
                if (e.getKey() === e.ENTER) {
                    Ext.merge(this.store.proxy.extraParams, {
                        filter: text.getValue()
                    });
                    this.store.load();
                }
            }, this);

            filter.on('keyup', function (text, e) {
                if (e.getKey() === e.BACKSPACE &&
                        text.getValue().length === 0) {
                    Ext.merge(this.store.proxy.extraParams, { filter: '' });
                    this.store.load();
                }
            }, this);

            this.dockedItems = [{
                dock: 'bottom',
                xtype: 'pagingtoolbar',
                store: this.store,
                displayInfo: true,
                items: [ '-', 'Search:', filter ]
            }];

            Ubmod.widget.Grid.superclass.initComponent.call(this);
        }
    });

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
            var listener = function (field) {
                if (field === 'interval' || field === 'cluster') {
                    this.reload();
                }
            };
            this.model.on('fieldchanged', listener, this);
            this.on('destroy', function () {
                this.model.removeListener('fieldchanged', listener, this);
            }, this);

            Ubmod.widget.Partial.superclass.initComponent.call(this);

            this.reload();
        },

        reload: function () {
            if (!this.model.isReady()) { return; }
            Ext.get(this.element).load({
                url: this.url,
                params: {
                    interval_id: this.model.getIntervalId(),
                    cluster_id: this.model.getClusterId()
                }
            });
        }
    });

    Ubmod.app = (function () {
        var model, widgets;

        return {
            init: function () {

                model = Ext.create('Ubmod.model.App');
                widgets = [];

                model.on('fieldchanged', function (field, value) {
                    if (field === 'interval') {
                        Ext.get('date-display').update(
                            value.get('start') + ' thru ' + value.get('end')
                        );
                    }
                });

                // Listen for clicks on menu links.
                Ext.select('#menu-list a').each(function () {
                    var href = this.getAttribute('href');
                    this.on('click', function (evt, el) {

                        // Load the new content.
                        Ext.get('content').load({
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

            addPartial: function (config) {
                config.model = model;
                widgets.push(Ext.create('Ubmod.widget.Partial', config));
            },

            addStatsPanel: function (config) {
                config.model = model;
                widgets.push(Ext.create('Ubmod.widget.StatsPanel', config));
            }
        };
    }());

    Ext.onReady(Ubmod.app.init, Ubmod);

}, window, false);
