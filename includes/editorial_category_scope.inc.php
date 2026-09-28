<?php
if (!function_exists('editorialCollectCategoryIds')) {
    /**
     * Collect active editorial category branches while tolerating legacy rows
     * whose parent relationship is incomplete.
     */
    function editorialCollectCategoryIds(array $categoryRows, array $rootSlugs, array $fallbackSlugs = array())
    {
        $byParent = array();
        $bySlug = array();

        foreach ($categoryRows as $category) {
            $category['id'] = isset($category['id']) ? (int)$category['id'] : 0;
            $category['parent_id'] = isset($category['parent_id']) ? (int)$category['parent_id'] : 0;
            $category['slug'] = isset($category['slug']) ? (string)$category['slug'] : '';
            if ($category['id'] <= 0) continue;

            $byParent[$category['parent_id']][] = $category;
            if ($category['slug'] !== '') $bySlug[$category['slug']][] = $category;
        }

        $categoryIds = array();
        $visited = array();
        $collectBranch = function ($categoryId) use (&$collectBranch, &$categoryIds, &$visited, $byParent) {
            $categoryId = (int)$categoryId;
            if ($categoryId <= 0 || isset($visited[$categoryId])) return;

            $visited[$categoryId] = true;
            $categoryIds[] = $categoryId;
            if (empty($byParent[$categoryId])) return;

            foreach ($byParent[$categoryId] as $child) {
                $collectBranch((int)$child['id']);
            }
        };

        foreach ($rootSlugs as $slug) {
            if (empty($bySlug[$slug])) continue;
            foreach ($bySlug[$slug] as $category) $collectBranch((int)$category['id']);
        }

        foreach ($fallbackSlugs as $slug) {
            if (empty($bySlug[$slug])) continue;
            foreach ($bySlug[$slug] as $category) $collectBranch((int)$category['id']);
        }

        return array_values(array_unique($categoryIds));
    }
}
