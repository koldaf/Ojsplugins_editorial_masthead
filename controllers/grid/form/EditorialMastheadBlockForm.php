<?php

/**
 * @file controllers/grid/form/EditorialMastheadBlockForm.php
 *
 * @class EditorialMastheadBlockForm
 * @brief Form for journal/press managers to add, edit, and delete masthead
 *  supplement blocks (locale-aware title + WYSIWYG content).
 */

namespace APP\plugins\generic\editorialMastheadSupplement\controllers\grid\form;

use APP\plugins\generic\editorialMastheadSupplement\EditorialMastheadSupplementPlugin;
use PKP\form\Form;
use PKP\form\validation\FormValidatorLocale;
use PKP\form\validation\FormValidatorCSRF;
use PKP\form\validation\FormValidatorPost;

class EditorialMastheadBlockForm extends Form
{
    public EditorialMastheadSupplementPlugin $plugin;

    public ?int $contextId;

    public ?string $blockId;

    public function __construct(EditorialMastheadSupplementPlugin $plugin, ?int $contextId, ?string $blockId = null)
    {
        parent::__construct($plugin->getTemplateResource('editBlockForm.tpl'));

        $this->plugin = $plugin;
        $this->contextId = $contextId;
        $this->blockId = $blockId;

        $this->addCheck(new FormValidatorPost($this));
        $this->addCheck(new FormValidatorCSRF($this));
        $this->addCheck(new FormValidatorLocale($this, 'blockTitle', 'required', 'plugins.generic.editorialMastheadSupplement.titleRequired'));
    }

    /**
     * Initialize form data from the existing block, if editing one.
     */
    public function initData()
    {
        $title = null;
        $content = null;

        if ($this->blockId) {
            $block = $this->findBlock();
            if ($block) {
                $title = $block['title'];
                $content = $block['content'];
            }
        }

        $this->setData('blockTitle', $title);
        $this->setData('blockContent', $content);
        $this->setData('existingBlockId', $this->blockId);
    }

    /**
     * @copydoc Form::readInputData()
     */
    public function readInputData()
    {
        $this->readUserVars(['blockTitle', 'blockContent']);
        // Keep the edit target when the form is re-rendered after a failed validation.
        $this->setData('existingBlockId', $this->blockId);
    }

    /**
     * @copydoc Form::execute()
     */
    public function execute(...$functionArgs)
    {
        $blocks = $this->plugin->getBlocks($this->contextId);

        if ($this->blockId) {
            foreach ($blocks as &$block) {
                if ($block['id'] === $this->blockId) {
                    $block['title'] = $this->getData('blockTitle');
                    $block['content'] = $this->getData('blockContent');
                    break;
                }
            }
            unset($block);
        } else {
            $maxSeq = 0;
            foreach ($blocks as $block) {
                $maxSeq = max($maxSeq, $block['seq'] ?? 0);
            }
            $blocks[] = [
                'id' => 'block_' . bin2hex(random_bytes(8)),
                'title' => $this->getData('blockTitle'),
                'content' => $this->getData('blockContent'),
                'seq' => $maxSeq + 1,
            ];
        }

        $this->plugin->setBlocks($this->contextId, $blocks);

        parent::execute(...$functionArgs);
    }

    /**
     * Find the block being edited by ID.
     */
    protected function findBlock(): ?array
    {
        foreach ($this->plugin->getBlocks($this->contextId) as $block) {
            if ($block['id'] === $this->blockId) {
                return $block;
            }
        }
        return null;
    }
}
