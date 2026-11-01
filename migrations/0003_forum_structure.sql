-- Forum structure is now Category → Subcategory → Topic: sub-boards are flattened into normal subcategories.
UPDATE {forum_boards} SET parent_id = 0 WHERE parent_id <> 0
