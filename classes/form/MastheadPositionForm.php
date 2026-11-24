<?php

/**
 * @file classes/form/MastheadPositionForm.php
 *
 * @class MastheadPositionForm
 * @brief Single above/below setting controlling where the supplement blocks
 *  render relative to the native Editorial Masthead list.
 */

namespace APP\plugins\generic\editorialMastheadSupplement\classes\form;

use APP\plugins\generic\editorialMastheadSupplement\EditorialMastheadSupplementPlugin;
use PKP\form\Form;
use PKP\form\validation\FormValidatorCSRF;
use PKP\form\validation\FormValidatorPost;

class MastheadPositionForm extends Form
{
    public EditorialMastheadSupplementPlugin $plugin;

    public ?int $contextId;

    public function __construct(EditorialMastheadSupplementPlugin $plugin, ?int $contextId)
    {
        parent::__construct($plugin->getTemplateResource('positionForm.tpl'));

        $this->plugin = $plugin;
        $this->contextId = $contextId;

        $this->addCheck(new FormValidatorPost($this));
        $this->addCheck(new FormValidatorCSRF($this));
    }

    /**
     * @copydoc Form::initData()
     */
    public function initData()
    {
        $this->setData('position', $this->plugin->getPosition($this->contextId));
    }

    /**
     * @copydoc Form::fetch()
     */
    public function fetch($request, $template = null, $display = false)
    {
        // Set here so the options exist on every render, including re-renders after errors.
        $this->setData('pluginName', $this->plugin->getName());
        $this->setData('positionOptions', [
            'above' => __('plugins.generic.editorialMastheadSupplement.position.above'),
            'below' => __('plugins.generic.editorialMastheadSupplement.position.below'),
        ]);
        return parent::fetch($request, $template, $display);
    }

    /**
     * @copydoc Form::readInputData()
     */
    public function readInputData()
    {
        $this->readUserVars(['position']);
    }

    /**
     * @copydoc Form::execute()
     */
    public function execute(...$functionArgs)
    {
        $position = $this->getData('position') === 'above' ? 'above' : 'below';
        $this->plugin->updateSetting($this->contextId, 'mastheadBlocksPosition', $position);
        parent::execute(...$functionArgs);
    }
}
