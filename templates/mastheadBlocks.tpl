{**
 * templates/mastheadBlocks.tpl
 *
 * Renders the editorial masthead supplement blocks. Fetched via PHP and
 * spliced into the native editorialMasthead.tpl output by an output filter
 * (see EditorialMastheadSupplementPlugin::handleTemplateDisplay()).
 *}
<div class="editorial_masthead_supplement">
	{foreach from=$editorialMastheadSupplementBlocks item="block"}
		<div class="editorial_masthead_supplement_block">
			{if $block.title}<h2>{$block.title|escape}</h2>{/if}
			<div class="editorial_masthead_supplement_content">{$block.content}</div>
		</div>
	{/foreach}
</div>
