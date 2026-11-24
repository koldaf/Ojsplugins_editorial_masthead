<?php

/**
 * @file controllers/grid/EditorialMastheadBlockGridRow.php
 *
 * @class EditorialMastheadBlockGridRow
 * @brief Handle masthead supplement block grid row requests (edit/delete).
 */

namespace APP\plugins\generic\editorialMastheadSupplement\controllers\grid;

use PKP\controllers\grid\GridRow;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\linkAction\request\RemoteActionConfirmationModal;

class EditorialMastheadBlockGridRow extends GridRow
{
    /**
     * @copydoc GridRow::initialize()
     *
     * @param null|mixed $template
     */
    public function initialize($request, $template = null)
    {
        parent::initialize($request, $template);

        $blockId = $this->getId();
        if (!empty($blockId)) {
            $router = $request->getRouter();

            $this->addAction(
                new LinkAction(
                    'editBlock',
                    new AjaxModal(
                        $router->url($request, null, null, 'editBlock', null, ['blockId' => $blockId]),
                        __('grid.action.edit'),
                        null,
                        true
                    ),
                    __('grid.action.edit'),
                    'edit'
                )
            );

            $this->addAction(
                new LinkAction(
                    'deleteBlock',
                    new RemoteActionConfirmationModal(
                        $request->getSession(),
                        __('common.confirmDelete'),
                        __('grid.action.delete'),
                        $router->url($request, null, null, 'deleteBlock', null, ['blockId' => $blockId]),
                        'negative'
                    ),
                    __('grid.action.delete'),
                    'delete'
                )
            );
        }
    }
}
