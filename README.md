# Editorial Masthead Supplement

An OJS generic plugin that adds your own formatted content blocks to the native
**Editorial Masthead** page (About > Editorial Masthead). Use it for people and
groups who should be listed but don't have, or shouldn't need, an OJS user
account: advisory board members, honorary editors, past editors, partner
institutions, and so on.

Each block has a title and rich-text content, both translatable per language.
Blocks can be placed **above** or **below** the native masthead list.

## Requirements

- OJS 3.4 or later (tested on OJS 3.5.0-6)
- PHP 8.0 or later

## Installation

1. The plugin folder **must be named `editorialMastheadSupplement`**. Upload or
   copy it to:

   ```
   <your OJS root>/plugins/generic/editorialMastheadSupplement/
   ```

   Or package it as `editorialMastheadSupplement-1.0.0.tar.gz` (one top-level
   folder with that name) and upload it under
   **Website > Plugins > Upload a New Plugin**.

2. If you copied the files by hand, register the plugin once from the OJS root:

   ```
   php lib/pkp/tools/installPluginVersion.php plugins/generic/editorialMastheadSupplement/version.xml
   ```

3. Go to **Website > Plugins > Installed Plugins > Generic Plugins**, find
   **Editorial Masthead Supplement**, and tick the box to enable it.

## How to use

Open the plugin's settings menu by clicking the blue arrow beside its name in
the plugin list. It has two links.

### Add a block

1. Click **Manage Masthead Blocks**.
2. Click **Add Block**.
3. Enter a **Block Title**, for example "Advisory Board". The title is required.
4. Enter the **Content** in the editor: names, affiliations, links, lists, a
   table, whatever you need.
5. Click **Save**.

### Edit, delete, reorder

In the **Manage Masthead Blocks** list:

- **Edit**: click the arrow at the left of a row, then **Edit**.
- **Delete**: click the arrow, then **Delete**, and confirm. Deletion cannot be undone.
- **Reorder**: drag a row up or down. Blocks appear on the page in this order.

### Choose where blocks appear

Click **Position** and choose:

- **Above the editorial masthead list**: blocks appear directly under the page heading.
- **Below the editorial masthead list**: blocks appear after the native list (the default).

The setting applies to all blocks.

### Check the result

Visit `https://<your-journal>/about/editorialMasthead`. Your blocks show with
the same styling as the rest of the page.

## Multiple languages

If your journal supports more than one language, the title and content fields
show a tab or switch for each language.

- The title is required in the journal's **primary** language.
- Readers see their own language when you've filled it in. Otherwise they see the primary language.
- A block with no title and no content in either language is not shown.

## Styling

Each block is wrapped in these classes, so you can style them from your theme
or **Website > Appearance > Advanced > Custom stylesheet**:

```
.editorial_masthead_supplement           (wrapper around all blocks)
.editorial_masthead_supplement_block     (one block)
.editorial_masthead_supplement_content   (the rich-text body)
```

## Troubleshooting

| Problem | Likely cause |
| --- | --- |
| Plugin is missing from the list | The folder isn't named `editorialMastheadSupplement`, or `index.php` is missing. |
| Plugin is listed but the settings links don't work | Folder name is wrong, or the plugin was copied by hand and not registered (step 2 above). |
| Blocks are saved but don't appear | The plugin isn't enabled for this journal, or the page you're viewing isn't the native Editorial Masthead page. A custom theme that changes the masthead template can also stop the blocks appearing. |
| Block title error on save | Fill in the title in the journal's primary language. |

## Limitations

- All blocks for a journal are stored in a single plugin setting, which holds
  about 64 KB on MySQL. Keep content reasonably short and upload images normally
  instead of pasting inline (base64) images.
- Blocks are listed on the Editorial Masthead page only, not elsewhere on the site.
- Each journal manages its own blocks; they are not shared between journals.
- Only journal managers can add, edit or delete blocks.
