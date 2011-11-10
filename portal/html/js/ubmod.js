Ext.Loader.onReady(function () {

    /**
     * Models
     */
    Ext.define('Interval', {
        extend: 'Ext.data.Model',
        fields: [
            'interval_id',
            'time_interval',
            'start',
            'end'
        ]
    });

    Ext.define('Cluster', {
        extend: 'Ext.data.Model',
        fields: [
            'cluster_id',
            'host',
            'display_name'
        ]
    });

    Ext.define('User', {
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

    Ext.define('Group', {
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

    Ext.define('Queue', {
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
    var intervalStore = Ext.create('Ext.data.Store', {
        model: 'Interval',
        proxy: {
            type: 'ajax',
            url: '/api/rest/json/interval/list',
            reader: {
                type: 'json',
                root: 'data'
            }
        }
    });

    var clusterStore = Ext.create('Ext.data.Store', {
        model: 'Cluster',
        proxy: {
            type: 'ajax',
            url: '/api/rest/json/cluster/list',
            reader: {
                type: 'json',
                root: 'data'
            }
        }
    });

    var userStore = Ext.create('Ext.data.Store', {
        model: 'User',
        proxy: {
            type: 'ajax',
            url: '/api/rest/json/user/list',
            reader: {
                type: 'json',
                root: 'data'
            }
        },
        remoteSort: true,
        pageSize: 25
    });

    var groupStore = Ext.create('Ext.data.Store', {
        model: 'Group',
        proxy: {
            type: 'ajax',
            url: '/api/rest/json/group/list',
            reader: {
                type: 'json',
                root: 'data'
            }
        },
        remoteSort: true,
        pageSize: 25
    });

    var queueStore = Ext.create('Ext.data.Store', {
        model: 'Queue',
        proxy: {
            type: 'ajax',
            url: '/api/rest/json/queue/list',
            reader: {
                type: 'json',
                root: 'data'
            }
        },
        remoteSort: true,
        pageSize: 25
    });

    var createGrid = function () {
        return Ext.create('Ext.grid.Panel', {
            store: store,
            columns: columns,
            renderTo: el,
            width: width,
            height: height
        });
    };

    Ext.namespace('Ubmod');

    Ubmod.app = function () {
        var toolbar, clusterId, intervalId, currentPage;

        var updateContent = function () {
        };

        return {
            init: function () {

                Ext.create('Ext.toolbar.Toolbar', {
                    renderTo: Ext.get('toolbar'),
                    items: [
                        'Period:',
                        {
                            xtype: 'combo',
                            store: intervalStore,
                            displayField: 'time_interval',
                            valueField: 'interval_id',
                            mode: 'local',
                            emptyText: 'Interval...',
                        },
                        {
                            xtype: 'tbspacer',
                            width: 20
                        },
                        'Cluster:',
                        {
                            xtype: 'combo',
                            store: clusterStore,
                            displayField: 'display_name',
                            valueField: 'cluster_id',
                            mode: 'local',
                            emptyText: 'Cluster...',
                        }
                    ]
                });

            },
            setClusterId: function (id) {
                clusterId = id;
                updateContent();
            },
            setIntervalId: function (id) {
                intervalId = id;
                updateContent();
            }
        };
    }();

    Ext.onReady(Ubmod.app.init, Ubmod);

}, window, false);
