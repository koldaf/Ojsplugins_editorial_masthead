{**
 * templates/editBlockForm.tpl
 *
 * Form for adding/editing a masthead supplement block.
 *}
<script>
	$(function() {ldelim}
		$('#editorialMastheadBlockForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="editorialMastheadBlockForm" method="post" action="{url router=PKP\core\PKPApplication::ROUTE_COMPONENT component="plugins.generic.editorialMastheadSupplement.controllers.grid.EditorialMastheadBlockGridHandler" op="updateBlock" existingBlockId=$existingBlockId}">
	{csrf}
	{include file="common/formErrors.tpl"}
	{fbvFormArea id="editorialMastheadBlockFormArea" class="border"}
		{fbvFormSection}
			{fbvElement type="text" label="plugins.generic.editorialMastheadSupplement.blockTitle" id="blockTitle" multilingual="true" value=$blockTitle}
		{/fbvFormSection}
		{fbvFormSection label="plugins.generic.editorialMastheadSupplement.blockContent" for="blockContent"}
			{fbvElement type="textarea" multilingual=true name="blockContent" id="blockContent" value=$blockContent rich=true height=$fbvStyles.height.TALL}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormButtons submitText="common.save"}
</form>
