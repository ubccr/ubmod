<script type="text/javascript">
Ext.onReady(function () {
    Ubmod.app.createTagPanel({
        store: Ext.create('Ubmod.store.UserTags'),
        renderTo: 'tags'
    });
});
</script>
<div id="tags"></div>
