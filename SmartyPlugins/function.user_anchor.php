<?php
/**
 * Pass the userAnchor function to Smarty.
 *
 * @param array $params The parameters passed into the function.
 *  - <b>user</b>: The user.
 *  - <b>cssCLass</b>: The CSS class.
 *  - <b>options</b>: Options for the user anchor.
 * @param Smarty $smarty The smarty object rendering the template.
 *
 * @return string The user anchor.
 */
function smarty_function_user_anchor($params, &$smarty) {
    return userAnchor(
        $params['user'] ?? '',
        $params['cssClass'] ?? null,
        $params['options'] ?? null
    );
}
