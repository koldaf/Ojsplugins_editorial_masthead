<?php

/**
 * @file controllers/grid/EditorialMastheadBlockGridHandler.php
 *
 * @class EditorialMastheadBlockGridHandler
 * @brief Handle masthead supplement block grid requests: list, add, edit,
 *  delete, and drag-reorder blocks. Blocks are not backed by a DAO/DB table
 *  -- they live entirely inside the plugin's own settings as a single
 *  ordered array, mirroring the core Custom Block Manager plugin's approach
 *  but collapsed into one setting since order is encoded by a `seq` field.
 */

namespace APP\plugins\generic\editorialMastheadSupplement\controllers\grid;

use APP\plugins\generic\editorialMastheadSupplement\controllers\grid\form\EditorialMastheadBlockForm;
use APP\plugins\generic\editorialMastheadSupplement\EditorialMastheadSupplementPlugin;
use PKP\controllers\grid\feature\OrderGridItemsFeature;
use PKP\controllers\grid\GridColumn;
use PKP\controllers\grid\GridHandler;
use PKP\core\JSONMessage;
use PKP\core\PKPApplication;
use PKP\db\DAO;
use PKP\facades\Locale;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\security\authorization\ContextAccessPolicy;
use PKP\security\Role;

class EditorialMastheadBlockGridHandler extends GridHandler
{
    /** @var EditorialMastheadSupplementPlugin */
    public $plugin;

    public function __construct(EditorialMastheadSupplementPlugin $plugin)
    {
        parent::__construct();
        $this->addRoleAssignment(
            [Role::ROLE_ID_MANAGER],
            ['fetchGrid', 'fetchRow', 'addBlock', 'editBlock', 'updateBlock', 'deleteBlock', 'saveSequence']
        );
        $this->plugin = $plugin;
    }

    /**
     * @copydoc PKPHandler::authorize()
     */
    public function authorize($request, &$args, $roleAssignments)
    {
        $this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));
        return parent::authorize($request, $args, $roleAssignments);
    }

    /**
     * @copydoc GridHandler::initialize()
     *
     * @param null|mixed $args
     */
    public function initialize($request, $args = null)
    {
        parent::initialize($request, $args);

        $this->setTitle('plugins.generic.editorialMastheadSupplement.manageBlocks');
        $this->setEmptyRowText('plugins.generic.editorialMastheadSupplement.noneCreated');

        $contextId = $this->getContextId($request);
        $locale = Locale::getLocale();
        $primaryLocale = Locale::getPrimaryLocale();

        $gridData = [];
        foreach ($this->plugin->getBlocks($contextId) as $block) {
            $title = $block['title'][$locale] ?? $block['title'][$primaryLocale] ?? '';
            $gridData[$block['id']] = [
                'id' => $block['id'],
                'title' => $title !== '' ? $title : __('plugins.generic.editorialMastheadSupplement.untitledBlock'),
                'seq' => $block['seq'] ?? 0,
            ];
        }
        $this->setGridDataElements($gridData);

        $router = $request->getRouter();
        $this->addAction(
            new LinkAction(
                'addBlock',
                new AjaxModal(
                    $router->url($request, null, null, 'addBlock'),
                    __('plugins.generic.editorialMastheadSupplement.addBlock'),
                ),
                __('plugins.generic.editorialMastheadSupplement.addBlock'),
                'add_item'
            )
        );

        $this->addColumn(
            new GridColumn(
                'title',
                'plugins.generic.editorialMastheadSupplement.blockTitle',
                null,
                'controllers/grid/gridCell.tpl'
            )
        );
    }

    /**
     * Resolve the current context ID, falling back to the site-wide
     * context if this plugin is somehow managed outside a journal/press.
     */
    protected function getContextId($request): ?int
    {
        $context = $request->getContext();
        return $context ? $context->getId() : PKPApplication::SITE_CONTEXT_ID;
    }

    /**
     * @copydoc GridHandler::getRowInstance()
     */
    public function getRowInstance()
    {
        return new EditorialMastheadBlockGridRow();
    }

    //
    // Drag-reorder support
    //
    /**
     * @copydoc GridHandler::initFeatures()
     */
    public function initFeatures($request, $args)
    {
        return [new OrderGridItemsFeature()];
    }

    /**
     * @copydoc GridHandler::getDataElementSequence()
     */
    public function getDataElementSequence($gridDataElement)
    {
        return (int) ($gridDataElement['seq'] ?? 0);
    }

    /**
     * @copydoc GridHandler::setDataElementSequence()
     */
    public function setDataElementSequence($request, $rowId, $gridDataElement, $newSequence)
    {
        $contextId = $this->getContextId($request);
        $blocks = $this->plugin->getBlocks($contextId);
        foreach ($blocks as &$block) {
            if ($block['id'] === $rowId) {
                $block['seq'] = (int) $newSequence;
                break;
            }
        }
        unset($block);
        $this->plugin->setBlocks($contextId, $blocks);
    }

    //
    // Public Grid Actions
    //
    /**
     * Add a new masthead block (delegates to editBlock with no existing ID).
     */
    public function addBlock($args, $request)
    {
        return $this->editBlock($args, $request);
    }

    /**
     * Present the add/edit form for a masthead block.
     */
    public function editBlock($args, $request)
    {
        $blockId = $request->getUserVar('blockId');
        $contextId = $this->getContextId($request);
        $this->setupTemplate($request);

        $form = new EditorialMastheadBlockForm($this->plugin, $contextId, $blockId ?: null);
        $form->initData();
        return new JSONMessage(true, $form->fetch($request));
    }

    /**
     * Save a masthead block (new or existing).
     */
    public function updateBlock($args, $request)
    {
        $blockId = $request->getUserVar('existingBlockId');
        $contextId = $this->getContextId($request);
        $this->setupTemplate($request);

        $form = new EditorialMastheadBlockForm($this->plugin, $contextId, $blockId ?: null);
        $form->readInputData();

        if ($form->validate()) {
            $form->execute();
            return DAO::getDataChangedEvent();
        }
        return new JSONMessage(true, $form->fetch($request));
    }

    /**
     * Delete a masthead block.
     */
    public function deleteBlock($args, $request)
    {
        if (!$request->checkCSRF()) {
            return new JSONMessage(false);
        }

        $blockId = $request->getUserVar('blockId');
        $contextId = $this->getContextId($request);

        $blocks = array_values(array_filter(
            $this->plugin->getBlocks($contextId),
            fn ($block) => $block['id'] !== $blockId
        ));
        $this->plugin->setBlocks($contextId, $blocks);

        return DAO::getDataChangedEvent();
    }
}
