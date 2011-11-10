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

    /**
     * Data stores
     */
    Ext.define('Ubmod.store.Interval', {
        extend: 'Ext.data.Store',
        constructor: function (config) {
            config = config || {};

            config.model = 'Ubmod.model.Interval';
            config.buffered = true;
            config.proxy = {
                type: 'ajax',
                url: '/api/rest/json/interval/list',
                reader: { type: 'json', root: 'data' }
            };

            Ubmod.store.Interval.superclass.constructor.call(this, config);
        }
    });

    Ext.define('Ubmod.store.Cluster', {
        extend: 'Ext.data.Store',
        constructor: function (config) {
            config = config || {};

            config.model = 'Ubmod.model.Cluster';
            config.buffered = true;
            config.proxy = {
                type: 'ajax',
                url: '/api/rest/json/cluster/list',
                reader: { type: 'json', root: 'data' }
            };

            Ubmod.store.Cluster.superclass.constructor.call(this, config);
        }
    });

    Ext.define('Ubmod.store.User', {
        extend: 'Ext.data.Store',
        constructor: function (config) {
            config = config || {};

            config.model = 'Ubmod.model.User';
            config.proxy = {
                type: 'ajax',
                url: '/api/rest/json/user/list',
                reader: { type: 'json', root: 'data' }
            };
            config.remoteSort = true;
            config.pageSize = 25;

            Ubmod.store.User.superclass.constructor.call(this, config);
        }
    });

    Ext.define('Ubmod.store.Group', {
        extend: 'Ext.data.Store',
        constructor: function (config) {
            config = config || {};

            config.model = 'Ubmod.model.Group';
            config.proxy = {
                type: 'ajax',
                url: '/api/rest/json/group/list',
                reader: { type: 'json', root: 'data' }
            };
            config.remoteSort = true;
            config.pageSize = 25;

            Ubmod.store.Group.superclass.constructor.call(this, config);
        }
    });

    Ext.define('Ubmod.store.Queue', {
        extend: 'Ext.data.Store',
        constructor: function (config) {
            config = config || {};

            config.model = 'Ubmod.model.Queue';
            config.proxy = {
                type: 'ajax',
                url: '/api/rest/json/queue/list',
                reader: { type: 'json', root: 'data' }
            };
            config.remoteSort = true;
            config.pageSize = 25;

            Ubmod.store.Queue.superclass.constructor.call(this, config);
        }
    });

    /**
     * Widgets
     */
    Ext.define('Ubmod.widget.Interval', {
        extend: 'Ext.form.field.ComboBox',
        constructor: function (config) {
            config = config || {}
            config.editable = false;
            this.addEvents({ load: true });
            Ubmod.widget.Interval.superclass.constructor.call(this, config);
        },
        initComponent: function () {
            var store = Ext.create('Ubmod.store.Interval');

            this.store = store;
            this.displayField = 'time_interval';
            this.valueField = 'interval_id';
            this.queryMode = 'local';
            this.emptyText = 'Interval...';

            Ubmod.widget.Interval.superclass.initComponent.call(this);

            store.load({
                scope: this,
                callback: function (records) {
                    this.setValue(records[3].get(this.valueField));
                    this.fireEvent('load');
                }
            });
        }
    });

    Ext.define('Ubmod.widget.Cluster', {
        extend: 'Ext.form.field.ComboBox',
        constructor: function (config) {
            config = config || {}
            config.editable = false;
            this.addEvents({ load: true });
            Ubmod.widget.Cluster.superclass.constructor.call(this, config);
        },
        initComponent: function () {
            var store = Ext.create('Ubmod.store.Cluster');

            this.store = store;
            this.displayField = 'display_name';
            this.valueField = 'cluster_id';
            this.queryMode = 'local';
            this.emptyText = 'Cluster...';

            Ubmod.widget.Cluster.superclass.initComponent.call(this);

            store.load({
                scope: this,
                callback: function (records) {
                    this.setValue(records[0].get(this.valueField));
                    this.fireEvent('load');
                }
            });
        }
    });


    Ext.define('Ubmod.widget.Toolbar', {
        extend: 'Ext.toolbar.Toolbar',
        constructor: function (config) {
            config = config || {}
            this.addEvents({ load: true, change: true });
            Ubmod.widget.Cluster.superclass.constructor.call(this, config);
        },
        initComponent: function () {

            var cb = Ext.bind(function () {
                var count = 0;
                return Ext.bind(function () {
                    count = count + 1;
                    if (count == 2) { this.fireEvent('load'); }
                }, this);
            }, this)();

            var listeners = {
                load: {
                    fn: function () {
                        cb();
                    },
                    scope: this
                },
                select: {
                    fn: function () {
                        this.fireEvent('change');
                    },
                    scope: this
                }
            };

            this.intervalCombo = Ext.create('Ubmod.widget.Interval', { listeners: listeners });
            this.clusterCombo = Ext.create('Ubmod.widget.Cluster', { listeners: listeners });

            this.renderTo = Ext.get('toolbar');
            this.items = [
                'Period:',
                this.intervalCombo,
                { xtype: 'tbspacer', width: 20 },
                'Cluster:',
                this.clusterCombo,
            ];

            Ubmod.widget.Toolbar.superclass.initComponent.call(this);
        },
        getParams: function () {
            return {
                interval_id: this.intervalCombo.getValue(),
                cluster_id: this.clusterCombo.getValue()
            };
        }
    });

    Ubmod.app = function () {
        var toolbar, currentPage, loaded;

        var updateContent = function () {
            Ext.get(currentPage.el).load({
                url: currentPage.url,
                params: toolbar.getParams()
            });
        };

        return {
            init: function () {
                Ext.select('#menu-list a').each(function (el) {
                    var url = this.getAttribute('href');
                    this.on('click', function (evt, el) {
                        Ext.get('content').load({
                            url: url,
                            scripts: true
                        });
                        Ext.select('#menu-list li').each(function () {
                            this.removeCls('menu-active');
                        });
                        Ext.get(el).parent().addCls('menu-active');

                    }, this, { stopEvent: true });
                });

                toolbar = Ext.create('Ubmod.widget.Toolbar', {
                    listeners: {
                        load: function () { loaded = true; updateContent(); },
                        change: function () { updateContent(); }
                    }
                });
            },
            setPage: function (page) {
                currentPage = page;
                if (loaded) { updateContent(); }
            }
        };
    }();

    Ext.onReady(Ubmod.app.init, Ubmod);

}, window, false);
