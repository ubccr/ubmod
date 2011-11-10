<script type="text/javascript">
Ext.onReady(function () {
    Ubmod.app.createTagPanel({ renderTo: 'tags' });
});
</script>
<div id="tags"></div>
<br />
<div class="chart-desc">
  The "User Tags" table provides the ability to tag users to provide
  filtering functionality. After a tag has been added, it may be selected in
  the toolbar and all charts will only include users that have that tag.
  Double-click a row to open a detail tab for that user that includes the
  ability to remove tags.
</div>
<div class="chart-desc">
  The "Tag Activity" table provides detailed
  information on each tag, including average job size, average wait time,
  and average run time. Clicking once on the headings in each of the columns
  will sort the column (Table) from high to low. A second click will reverse
  the sort. The Search capability allows you to search for a particular tag.
  Press enter in the search bar to filter.  Double-click a row to open a
  detail tab for that tag.
</div>
