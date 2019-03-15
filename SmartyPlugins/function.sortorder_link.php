<?php
/**
 * Returns a get request for an ordered member list
 *
 * @param array $params The parameters passed into the function.
 *  - <b>sort</b>: The field name to sort the list by.
 *  - <b>order</b>: The sort order (asc|desc).
 *  - <b>text</b>: The text to show in the link.
 *  - <b>class</b>: CSS class of the link.
 * @param Smarty $smarty The smarty object rendering the template.
 *
 * @return A http query.
 */
function smarty_function_sortorder_link($params, &$smarty) {
    $sort = $params['sort'] ?? '';
    $text = $params['text'] ?? $sort;
    if ($sort == '') {
        return $text;
    }

    $get = Gdn::request()->get();
    $get['sort'] = $sort;

    if (isset($get['order'])) {
        if ($get['order'] == 'asc') {
            // order parameter already set and "asc"
            $get['order'] = 'desc';
        } else {
            // order parameter already set and "desc"
            $get['order'] = 'asc';
        }
    } else {
        // order parameter not set, get from tpl file, default to "asc"
        $get['order'] = $params['order'] ?? 'asc';
    }

    return anchor(
        $text,
        Gdn::request()->path().'?'.http_build_query($get),
        $params['class'] ?? ''
    );
}
