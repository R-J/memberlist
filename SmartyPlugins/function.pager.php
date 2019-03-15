<?php
/**
 * Write a pager.
 *
 * @param array $params The parameters passed into the function.
 *  - <b>type</b>: less|more will define the id of the pager.
 * @param Smarty $smarty The smarty object rendering the template.
 *
 * @return string The pager markup.
 */
function smarty_function_pager($params, &$smarty) {
    $type = $params['type'] ?? 'more';
    if ($type != 'more') {
        $type = 'less';
    }
    return PagerModule::current()->toString($type);
}
