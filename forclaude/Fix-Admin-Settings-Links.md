# Fix Admin Settings Links
mod_readaloud has category of admin settings in the Moodle admin UI, not a single page. This is the same way that mod_quiz works.
When the plugins are listed at:
/admin/search.php#linkmodules
the category of settings pages is expanded and visible.

However in the listing of plugins at:
/admin/plugins.php
mod_readaloud does not display a "settings" link at all, but mod_quiz does.
It seems mod_quiz directs users to the main quiz settings page. 

In readaloud we want to do the same.