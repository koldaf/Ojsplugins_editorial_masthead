<?php

/**
 * @file EditorialMastheadSupplementPlugin.php
 *
 * @class EditorialMastheadSupplementPlugin
 * @brief Adds editable, locale-aware WYSIWYG blocks above or below the native
 *  Editorial Masthead page, without requiring the people listed to be real
 *  registered OJS users.
 */

namespace APP\plugins\generic\editorialMastheadSupplement;

use APP\core\Application;
use APP\template\TemplateManager;
use PKP\core\JSONMessage;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;

class EditorialMastheadSupplementPlugin extends GenericPlugin
{
    /** @var bool Guards against the output filter splicing content twice in one request. */
    protected bool $blocksInjected = false;

    /**
     * @copydoc Plugin::register()
     *
     * @param null|mixed $mainContextId
     */
    public function register($category, $path, $mainContextId = null)
    {
        if (!parent::register($category, $path, $mainContextId)) {
            return false;
        }

        // Don't register hooks if the system isn't installed, or is
        // performing an upgrade, to avoid DB access before the schema exists.
        if (Application::isUnderMaintenance()) {
            return true;
        }

        if ($this->getEnabled($mainContextId)) {
            // This hook routes requests to the block-manager grid handler.
            Hook::add('LoadComponentHandler', [$this, 'setupGridHandler']);

            // This hook lets us splice the rendered supplement blocks into
            // the native Editorial Masthead page output.
            Hook::add('TemplateManager::display', [$this, 'handleTemplateDisplay']);
        }

        return true;
    }

    /**
     * @copydoc Plugin::getDisplayName()
     */
    public function getDisplayName()
    {
        return __('plugins.generic.editorialMastheadSupplement.displayName');
    }

    /**
     * @copydoc Plugin::getDescription()
     */
    public function getDescription()
    {
        return __('plugins.generic.editorialMastheadSupplement.description');
    }

    /**
     * Permit requests to the masthead block grid handler.
     *
     * @param string $hookName
     * @param array $params
     */
    public function setupGridHandler($hookName, $params)
    {
        $component = & $params[0];
        $componentInstance = & $params[2];
        if ($component == 'plugins.generic.editorialMastheadSupplement.controllers.grid.EditorialMastheadBlockGridHandler') {
            $componentInstance = new controllers\grid\EditorialMastheadBlockGridHandler($this);
            return true;
        }
        return false;
    }

    /**
     * @copydoc Plugin::getActions()
     */
    public function getActions($request, $actionArgs): array
    {
        $actions = parent::getActions($request, $actionArgs);
        if (!$this->getEnabled()) {
            return $actions;
        }

        $router = $request->getRouter();

        $manageBlocksAction = new LinkAction(
            'manageMastheadBlocks',
            new AjaxModal(
                $router->url($request, null, null, 'manage', null, [
                    'plugin' => $this->getName(),
                    'category' => $this->getCategory(),
                    'verb' => 'blocks',
                ]),
                $this->getDisplayName(),
            ),
            __('plugins.generic.editorialMastheadSupplement.manageBlocks')
        );

        $positionAction = new LinkAction(
            'editorialMastheadPosition',
            new AjaxModal(
                $router->url($request, null, null, 'manage', null, [
                    'plugin' => $this->getName(),
                    'category' => $this->getCategory(),
                    'verb' => 'position',
                ]),
                $this->getDisplayName(),
            ),
            __('plugins.generic.editorialMastheadSupplement.position')
        );

        return array_merge([$manageBlocksAction, $positionAction], $actions);
    }

    /**
     * @copydoc Plugin::manage()
     */
    public function manage($args, $request): JSONMessage
    {
        switch ($request->getUserVar('verb')) {
            case 'position':
                return $this->managePosition($args, $request);
            case 'blocks':
                $templateMgr = TemplateManager::getManager($request);
                $dispatcher = $request->getDispatcher();
                return $templateMgr->fetchAjax(
                    'editorialMastheadBlockGridUrlGridContainer',
                    $dispatcher->url(
                        $request,
                        Application::ROUTE_COMPONENT,
                        null,
                        'plugins.generic.editorialMastheadSupplement.controllers.grid.EditorialMastheadBlockGridHandler',
                        'fetchGrid'
                    )
                );
        }
        return parent::manage($args, $request);
    }

    /**
     * Handle the "Position" settings link action: a single above/below
     * setting controlling where the supplement blocks render relative to
     * the native masthead list.
     */
    protected function managePosition($args, $request): JSONMessage
    {
        $context = $request->getContext();
        $contextId = $context ? $context->getId() : \PKP\core\PKPApplication::SITE_CONTEXT_ID;

        $form = new classes\form\MastheadPositionForm($this, $contextId);

        if ($request->getUserVar('save')) {
            $form->readInputData();
            if ($form->validate()) {
                $form->execute();
                return new JSONMessage(true);
            }
        } else {
            $form->initData();
        }

        return new JSONMessage(true, $form->fetch($request));
    }

    /**
     * Read the ordered list of supplement blocks for a context.
     */
    public function getBlocks(?int $contextId): array
    {
        $blocks = $this->getSetting($contextId, 'mastheadBlocks');
        if (!is_array($blocks)) {
            return [];
        }
        usort($blocks, fn ($a, $b) => ($a['seq'] ?? 0) <=> ($b['seq'] ?? 0));
        return $blocks;
    }

    /**
     * Persist the full ordered list of supplement blocks for a context.
     */
    public function setBlocks(?int $contextId, array $blocks): void
    {
        $this->updateSetting($contextId, 'mastheadBlocks', $blocks, 'object');
    }

    /**
     * Read the configured render position ('above' or 'below') for a context.
     */
    public function getPosition(?int $contextId): string
    {
        $position = $this->getSetting($contextId, 'mastheadBlocksPosition');
        return $position === 'above' ? 'above' : 'below';
    }

    /**
     * Fired on every TemplateManager::display() call. When the Editorial
     * Masthead page is about to render, register a one-shot Smarty output
     * filter that splices our rendered blocks into the final HTML. This
     * must be done via an output filter (not by mutating the hook's own
     * $output argument) because that argument is null at this point in
     * core's display() flow -- the real rendering hasn't happened yet.
     */
    public function handleTemplateDisplay($hookName, $args)
    {
        /** @var TemplateManager $templateMgr */
        $templateMgr = $args[0];
        $template = $args[1];

        if ($template !== 'frontend/pages/editorialMasthead.tpl') {
            return false;
        }

        $request = Application::get()->getRequest();
        $context = $request->getContext();
        if (!$context) {
            return false;
        }

        $contextId = $context->getId();
        $blocks = $this->getBlocks($contextId);
        if (empty($blocks)) {
            return false;
        }

        $position = $this->getPosition($contextId);
        $blocksHtml = $this->renderBlocks($templateMgr, $request, $blocks);

        $templateMgr->registerFilter('output', function ($output) use ($blocksHtml, $position) {
            if ($this->blocksInjected || trim($blocksHtml) === '') {
                return $output;
            }

            if ($position === 'above') {
                $anchor = '<h1>' . __('common.editorialMasthead') . '</h1>';
                $replacement = $anchor . $blocksHtml;
            } else {
                $anchor = '</div><!-- .page -->';
                $replacement = $blocksHtml . $anchor;
            }

            if (strpos($output, $anchor) === false) {
                // Core template changed underneath us -- fail soft rather
                // than breaking the page.
                return $output;
            }

            $this->blocksInjected = true;
            return str_replace($anchor, $replacement, $output);
        }, false);

        return false;
    }

    /**
     * Render the supplement blocks list into HTML via this plugin's own
     * template, so the blocks adopt normal page styling.
     */
    protected function renderBlocks(TemplateManager $templateMgr, $request, array $blocks): string
    {
        $locale = \PKP\facades\Locale::getLocale();
        $primaryLocale = \PKP\facades\Locale::getPrimaryLocale();

        $renderedBlocks = [];
        foreach ($blocks as $block) {
            $title = $block['title'][$locale] ?? $block['title'][$primaryLocale] ?? '';
            $content = $block['content'][$locale] ?? $block['content'][$primaryLocale] ?? '';
            if ($title === '' && $content === '') {
                continue;
            }
            $renderedBlocks[] = ['title' => $title, 'content' => $content];
        }

        if (empty($renderedBlocks)) {
            return '';
        }

        $templateMgr->assign('editorialMastheadSupplementBlocks', $renderedBlocks);
        return $templateMgr->fetch($this->getTemplateResource('mastheadBlocks.tpl'));
    }
}
