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
 * stats-grid.js
 * ------------------------------------
 * Original Author: Andrew E. Bruno (CCR);
 * Contributor(s): -;
 *
 */

Ext.apply(Ext.util.Format,{
    commify: function(n) {
        var str = n+'';
        var parts = str.split('.');
        var x = this._commify(parts[0]);
        var y = parts.length > 1 ? '.' + parts[1] : '';
        return x+y;
    },
    _commify: function(value) {
        if(value.length <= 3) {
            return value;
        } else {
            return this.commify(value.substr(0, value.length-3))+","+value.substr(value.length-3,3);
        }
    }
});
var StatsGrid = function(config){
    Ext.QuickTips.init();

    var tabs = new Ext.TabPanel('stats-tabs', {
        resizeTabs: true,
        minTabWidth: 65,
        preferredTabWidth:80
    });
    tabs.addTab('stats-list', 'Show All');
    tabs.activate('stats-list');
    var dataStore = new Ext.data.Store({
        proxy: new Ext.data.HttpProxy({
            url: config.dataUrl
        }),
        reader: new Ext.data.JsonReader({
            root: config.root,
            totalProperty: 'total',
            id: config.id
        }, [
            {name: config.id, mapping: config.id},
            {name: config.display, mapping: config.display},
            {name: 'jobs', mapping: 'jobs'},
            {name: 'avg_wait', mapping: 'avg_wait'},
            {name: 'wallt', mapping: 'wallt'},
            {name: 'avg_cpus', mapping: 'avg_cpus'},
            {name: 'avg_mem', mapping: 'avg_mem'}
        ]),
        remoteSort: true
    });
    var toolbar = new PBSToolbar({ds: dataStore, tabs: tabs, displayUrl: config.displayUrl});

    var renderLabel = function(value, p, r) {
        return value;
    };

    var renderNumber = function(value) {
        if(value == null) return '';
        return '<div style="text-align: right">'+Ext.util.Format.commify(value)+'</div>';
    };

    var cm = new Ext.grid.ColumnModel([{
        id: config.id,
        header: config.label,
        dataIndex: config.display,
        width: 128,
        renderer: renderLabel,
        css: 'white-space:normal;'
    },{
        header: "# Jobs",
        dataIndex: 'jobs',
        width: 100,
        renderer: renderNumber,
        align: 'right'
    },{
        header: "Avg. Job Size (cpus)",
        dataIndex: 'avg_cpus',
        width: 118,
        renderer: renderNumber,
        align: 'right'
    },{
        header: "Avg. Wait Time (h)",
        dataIndex: 'avg_wait',
        width: 118,
        renderer: renderNumber,
        align: 'right'
    },{
        header: "Wall Time (d)",
        dataIndex: 'wallt',
        width: 128,
        renderer: renderNumber,
        align: 'right'
    },{
        header: "Avg. MEM (m)",
        dataIndex: 'avg_mem',
        width: 128,
        renderer: renderNumber,
        align: 'right'
    }]);

    cm.defaultSortable = true;

    var rsm = new Ext.grid.RowSelectionModel({singleSelect:true});
    //rsm.lock();
    this.grid = new Ext.grid.Grid('stats-grid', {
        ds: dataStore,
        cm: cm,
        selModel: rsm,
        enableColLock:false,
        loadMask: true
    });
    var rz = new Ext.Resizable('stats-grid', {
        wrap:true,
        minHeight:100,
        pinned:true,
        handles: 's'
    });
    rz.on('resize', this.grid.autoSize, this.grid);
    this.grid.addListener('rowdblclick', function(g, rowIndex, e) {
        if(g.getSelectionModel().hasSelection()) {
            var id = g.getSelectionModel().getSelected().id;
            var label = g.getSelectionModel().getSelected().get(config.display);
            var cid = toolbar.clusterCombo.getValue();
            var mid = toolbar.intervalCombo.getValue();
            var t = tabs.addTab('tab-'+id, label, '', true);
            var u = t.setUrl(config.displayUrl, {'id': id, interval_id: mid, cluster_id: cid}, true);
            u.loadScripts = true;
            t.activate();
        }
    });
    this.grid.render();

    var gridFoot = this.grid.getView().getFooterPanel(true);

    var paging = new Ext.PagingToolbar(gridFoot, dataStore, {
        pageSize: 25,
        displayInfo: true,
        displayMsg: 'Displaying '+config.label+' {0} - {1} of {2}',
        emptyMsg: "No "+config.display+"'s to display"
    });
    paging.add('-', 'Search: ');
    var filter = Ext.get(paging.addDom({
        tag: 'input',
        type: 'text',
        size: '30',
        value: '',
        tooltip: 'Filter results by '+config.display,
        cls: 'x-grid-filter'
    }).el);

    filter.on('keypress', function(e) {
        if(e.getKey() == e.ENTER) dataStore.load({
            params: {start: 0, limit: 25}
        });
    });

    filter.on('keyup', function(e) {
        if(e.getKey() == e.BACKSPACE && this.getValue().length == 0) dataStore.load({
            params: {start: 0, limit: 25}
        });
    });
    filter.on('focus', function(){this.dom.select();});

    dataStore.on('beforeload', function() {
        dataStore.baseParams = {
            filter: filter.getValue(),
            cluster_id: toolbar.clusterCombo.getValue(),
            interval_id: toolbar.intervalCombo.getValue()
        };
    });

    dataStore.load({params:{start:0, limit:25}});
};
