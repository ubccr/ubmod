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
            config = config || {};
            config.editable = false;
            this.addEvents({ load: true });
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
                    this.fireEvent('load');
                }
            });
        }
    });

    Ext.define('Ubmod.widget.Cluster', {
        extend: 'Ext.form.field.ComboBox',

        constructor: function (config) {
            config = config || {};
            config.editable = false;
            this.addEvents({ load: true });
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
                    this.fireEvent('load');
                }
            });
        }
    });

    Ext.define('Ubmod.widget.Toolbar', {
        extend: 'Ext.toolbar.Toolbar',

        constructor: function (config) {
            config = config || {};
            this.addEvents({ load: true, change: true });
            Ubmod.widget.Cluster.superclass.constructor.call(this, config);
        },

        initComponent: function () {
            var onComboLoad, comboArgs;

            // Fire the toolbar load event after both combos have loaded.
            onComboLoad = Ext.bind(function () {
                var count = 0;
                return Ext.bind(function () {
                    count = count + 1;
                    if (count == 2) { this.fireEvent('load'); }
                }, this);
            }, this)();

            comboArgs = {
                listeners: {
                    load: {
                        fn: onComboLoad,
                        scope: this
                    },
                    select: {
                        fn: function () { this.fireEvent('change'); },
                        scope: this
                    }
                }
            };

            this.intervalCombo = Ext.create('Ubmod.widget.Interval', comboArgs);
            this.clusterCombo = Ext.create('Ubmod.widget.Cluster', comboArgs);

            this.renderTo = Ext.get('toolbar');
            this.items = [
                'Period:',
                this.intervalCombo,
                { xtype: 'tbspacer', width: 20 },
                'Cluster:',
                this.clusterCombo
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
        var toolbar, loaded, updateCallback, updateContent;

        updateContent = function () {
            updateCallback(toolbar.getParams());
        };

        return {
            init: function () {

                // Listen for clicks on menu links.
                Ext.select('#menu-list a').each(function (el) {
                    var href = this.getAttribute('href');
                    this.on('click', function (evt, el) {
                        Ext.get('content').load({ url: href, scripts: true });

                        // Update menu CSS classes.
                        Ext.select('#menu-list li').each(function () {
                            this.removeCls('menu-active');
                        });
                        Ext.get(el).parent().addCls('menu-active');

                    }, this, { stopEvent: true });
                });

                toolbar = Ext.create('Ubmod.widget.Toolbar', {
                    listeners: {
                        load: function () {
                            loaded = true;
                            updateContent();
                        },
                        change: updateContent
                    }
                });
            },

            setUpdateCallback: function (cb) {
                updateCallback = cb;
                if (loaded) { updateContent(); }
            }
        };
    }();

    Ext.onReady(Ubmod.app.init, Ubmod);

}, window, false);
