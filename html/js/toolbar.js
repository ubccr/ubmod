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
 *
 * ------------------------------------
 * toolbar.js
 * ------------------------------------
 * Original Author: Andrew E. Bruno (CCR);
 * Contributor(s): -;
 *
 */

var PBSToolbar = function(config){
    Ext.QuickTips.init();
    this.dataStore = config.ds;
    this.tabs = config.tabs;
    this.displayUrl = config.displayUrl;
    this.el = config.el;
    this.hideCluster = config.hideCluster;

    this.intervalStore = new Ext.data.Store({
        proxy: new Ext.data.HttpProxy({
            url: '/api/rest/json/interval/list'
        }),
        reader: new Ext.data.JsonReader({
            root: 'data',
            totalProperty: 'total',
            id: 'interval_id'
        }, [
            {name: 'interval_id', mapping: 'interval_id'},
            {name: 'time_interval', mapping: 'time_interval'},
            {name: 'start', mapping: 'start'},
            {name: 'end', mapping: 'end'}
        ]),
        remoteSort: true
    });

    this.intervalCombo = new Ext.form.ComboBox({
        store: this.intervalStore,
        displayField:'time_interval',
        valueField:'interval_id',
        typeAhead: true,
        mode: 'local',
        triggerAction: 'all',
        emptyText:'Time period...',
        width: 100,
        selectOnFocus:true
    });

    this.intervalStore.on('load', function() {
        var rec = this.intervalStore.getById(3);
        this.intervalCombo.setValue('3');
        this.updateDate(rec);
    }, this, {single: true});
    this.intervalStore.load();

    this.clusterStore = new Ext.data.Store({
        proxy: new Ext.data.HttpProxy({
            url: '/api/rest/json/cluster/list'
        }),
        reader: new Ext.data.JsonReader({
            root: 'data',
            totalProperty: 'total',
            id: 'cluster_id'
        }, [
            {name: 'cluster_id', mapping: 'cluster_id'},
            {name: 'host', mapping: 'host'},
            {name: 'display_name', mapping: 'display_name'}
        ]),
        remoteSort: true
    });

    this.clusterCombo = new Ext.form.ComboBox({
        store: this.clusterStore,
        displayField:'display_name',
        valueField:'cluster_id',
        typeAhead: true,
        mode: 'local',
        triggerAction: 'all',
        emptyText:'Cluster...',
        width: 130,
        selectOnFocus:true
    });

    if(this.hideCluster == null) {
        this.clusterStore.on('load', function() {
            var rec = this.clusterStore.getAt(0);
            this.clusterCombo.setValue(rec.get("cluster_id"));
        }, this, {single: true});
        this.clusterStore.load();
    }

    this.tb = new Ext.Toolbar('toolbar');
    this.tb.addText("Period: ");
    this.tb.addField(this.intervalCombo);
    if(this.hideCluster == null) {
        this.tb.addText("&nbsp;&nbsp;&nbsp;&nbsp;Cluster: ");
        this.tb.addField(this.clusterCombo);
    }

    if(this.dataStore != null) {
        if(this.hideCluster == null) this.clusterCombo.on("select", this.refresh, this);
        this.intervalCombo.on("select", this.refreshMeasure, this);
    } else if(this.el != null) {
        if(this.hideCluster == null) this.clusterCombo.on("select", this.update, this);
        this.intervalCombo.on("select", this.updateMeasure, this);
    }
};

PBSToolbar.prototype = {
    updateDate : function(record) {
        var el = Ext.get('date-display');
        el.update(record.get('start')+' thru '+record.get('end'));
    },
    refreshMeasure : function(combo, record, index) {
        this.refresh(combo, record, index);
        this.updateDate(record);
    },
    updateMeasure : function(combo, record, index) {
        var el = Ext.get('cat');
        if(el != null) {
            this.updateFilter(el.dom.innerHTML);
        } else {
            this.update(combo, record, index);
        }
        this.updateDate(record);
    },
    refresh : function(combo, record, index) {
        this.dataStore.load({
            params: {
                cluster_id: this.clusterCombo.getValue(),
                interval_id: this.intervalCombo.getValue(),
                start: 0,
                limit: 25
            }
        });
        if(this.tabs != null) {
            if(this.tabs.getCount() > 1) {
                for(var i = 1; i < this.tabs.getCount(); i++) {
                    var tab = this.tabs.getTab(i);
                    var id = tab.id;
                    var parts = id.split('-');
                    var updater = tab.getUpdateManager();
                    updater.update(this.displayUrl, {id: parts[1], cluster_id: this.clusterCombo.getValue(), interval_id: this.intervalCombo.getValue()});
                }
            }
        }
    },
    update : function(combo, record, index, filter) {
        var e = Ext.get(this.el);
        var updater = e.getUpdateManager();
        var params = {
            cluster_id: this.clusterCombo.getValue(),
            interval_id: this.intervalCombo.getValue()
        };
        if(filter != null) {
            params['cat'] = filter;
        }
        updater.update(this.displayUrl, params);
    },
    updateFilter : function(filter) {
        this.update(null,null,null,filter);
    }
};
