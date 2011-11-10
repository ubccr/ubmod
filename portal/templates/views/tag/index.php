<script type="text/javascript">
Ext.onReady(function () {
    Ubmod.app.addTagPanel({
        store: Ext.create('Ubmod.store.UserTags'),
        renderTo: 'tags'
    });
});
</script>
<div id="tags"></div>
