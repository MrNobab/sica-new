# Sica WordPress Visual Package

This theme imports the bundled static Sica HTML files as WordPress Pages and routes Page editing to the custom Sica Visual Editor instead of Gutenberg.

## Install

1. Upload the full WordPress package ZIP to the hosting account.
2. Extract it into the site web root.
3. Open the domain and complete the normal WordPress installer.
4. In WordPress Admin, go to Appearance > Themes and activate **Sica Visual Theme**.
5. On activation, the theme imports the bundled HTML files as Pages, sets `index.html` as the front page, and enables clean permalinks.

## Editing

- Go to Pages and click Edit on a page.
- WordPress redirects to the Sica Visual Editor.
- Select elements with `data-edit-id`, edit them, and save.
- Text and style changes are written to the Page `post_content`.
- Image uploads go to the WordPress Media Library and return normal upload URLs.

## Notes

- Gutenberg is disabled for Pages while this theme is active.
- New uploads use `wp-content/uploads`.
- Original static assets are bundled inside the theme under `static/`.
- The importer is one-time and will not overwrite later edits after the first successful import.
