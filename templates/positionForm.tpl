{**
 * templates/positionForm.tpl
 *
 * Settings form: where the masthead supplement blocks render relative to
 * the native Editorial Masthead list.
 *}
<script>
	$(function() {ldelim}
		$('#editorialMastheadPositionForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="editorialMastheadPositionForm" method="post" action="{url router=PKP\core\PKPApplication::ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="position" save=true}">
	{csrf}
	{include file="common/formErrors.tpl"}
	{fbvFormArea id="editorialMastheadPositionFormArea"}
		{fbvFormSection description="plugins.generic.editorialMastheadSupplement.position.description"}
			{fbvElement type="select" id="position" name="position" from=$positionOptions selected=$position translate=false}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormButtons submitText="common.save"}
</form>
