<?php

class MemberlistPlugin extends Gdn_Plugin {
    /**
     * Init structure changes.
     *
     * @return void.
     */
    public function setup() {
        $this->structure();
    }

    /**
     * Set default config values.
     *
     * @return void.
     */
    public function structure() {
        Gdn::config()->touch('Memberlist.PerPage', 30);
        Gdn::config()->touch(
            'Memberlist.AllowedSorts',
            [
                'Name',
                'DateInserted',
                'CountPosts',
                'DateLastActive',
                'Role'
            ]
        );
    }

    /**
     * Add folder for custom smarty functions.
     *
     * @param Smarty $sender Instance of the Smarty templating engine.
     *
     * @return void.
     */
    public function gdn_smarty_init_handler($sender) {
        $sender->addPluginsDir(__DIR__.'/SmartyPlugins');
    }

    /**
     * Add a link to the Memberlist to the custom menu.
     *
     * @param Gdn_Controller $sender Instance of the calling class.
     *
     * @return void.
     */
    public function base_render_before($sender) {
        if (Gdn_Theme::inSection('Dashboard')) {
            return;
        }
        $sender->Menu->Items[] = [
            [
                'Permission' => 'Plugins.Memberlist.View',
                'Url' => '/vanilla/memberlist',
                'Text' => Gdn::translate('Memberlist')
            ]
        ];
    }

    /**
     * Get all data for users.
     *
     * Try to get data from cache, refresh cached data otherwise. Tables User
     * and public Roles are fetched, as well as ProfileExtender information.
     *
     * @param string $orderFields The column that results should be sorted by.
     * @param string $orderDirection Direction of sort.
     * @param integer $limit How many datasets should be fetched.
     * @param boolean $offset Offset of the results.
     *
     * @return array User data.
     */
    public function getConsolidatedUserData(
        string $orderFields = 'Name',
        string $orderDirection = 'asc',
        int $limit = 30,
        int $offset = 0
    ): array {
        $cacheKey = "Memberlist_{$orderFields}_{$orderDirection}_{$limit}_{$offset}";
        // Return cached values if possible.
        $users = Gdn::cache()->get($cacheKey);
        if ($users !== Gdn_Cache::CACHEOP_FAILURE) {
            return $users;
        }

        // Get all users.
        $userModel = Gdn::userModel();
        $users = $userModel->getWhere(
            ['Banned' => 0, 'Deleted' => 0],
            $orderFields,
            $orderDirection,
            $limit,
            $offset
        )->resultArray();

        // Add Roles, UserMeta and PhotoUrl per user.
        $userMetaModel = Gdn::userMetaModel();
        $roleModel = new RoleModel();
        array_walk(
            $users,
            function (&$user) use ($roleModel, $userMetaModel) {
                // Fields nobody should know of.
                unset($user['Password']);
                unset($user['HashMethod']);
                unset($user['Preferences']);
                unset($user['Permissions']);
                // Add Roles.
                $user['Roles'] = $roleModel->getPublicUserRoles($user['UserID']);
                // Add ProfileExtender fields
                $user['ProfileExtender']= $userMetaModel->getUserMeta(
                    $user['UserID'],
                    'Profile.%'
                );
                // Prepare the user photo.
                if ($user['Photo']) {
                    if (!isUrl($user['Photo'])) {
                        $user['PhotoUrl'] = Gdn_Upload::url(changeBasename($user['Photo'], 'n%s'));
                    } else {
                        $user['PhotoUrl'] = $user['Photo'];
                    }
                } else {
                    $user['PhotoUrl'] = UserModel::getDefaultAvatarUrl($user);
                }
                // $user['ProfileUrl'] = profileUrl($user);
                $user['UserUrl'] = userUrl($user);
                $user['UserPhoto'] = userPhoto($user, ['Size' => 'Small']);
                $user['UserPhotoUrl'] = userPhotoUrl($user);
                $user['UserAnchor'] = userAnchor($user);
                $user['CountPosts'] = $user['CountDiscussions'] + $user['CountComments'];
            }
        );

        // Cache for later usage.
        Gdn::cache()->store(
            $cacheKey,
            $users,
            [Gdn_Cache::FEATURE_EXPIRY => 360] // Cache for 5 minutes
        );

        return $users;
    }

    /**
     * Get number of users.
     *
     * Try to get data from cache, refresh cached data otherwise.
     *
     * @return integer Number of users.
     */
    public function getUserCount(): int {
        $userCount = Gdn::cache()->get('Memberlist_Usercount');
        if ($userCount !== Gdn_Cache::CACHEOP_FAILURE) {
            return $userCount;
        }

        // Get user count.
        $userCount = Gdn::userModel()->getCountWhere(
            ['Banned' => 0, 'Deleted' => 0]
        );

        // Cache for later usage.
        Gdn::cache()->store(
            'Memberlist_Usercount',
            $userCount,
            [Gdn_Cache::FEATURE_EXPIRY => 360] // Cache for 5 minutes
        );

        return $userCount;
    }


    /**
     * Determine user role and try to get a view with that name. Default to "memberlist".
     *
     * @param VanillaController $sender Instance of the calling class.
     * @param mixed $args The slug.
     *
     * @return void.
     */
    public function vanillaController_memberlist_create(object $sender, array $args) {
        // Ensure view permissions.
        $sender->permission('Plugins.Memberlist.View');

        // Add breadcrumbs.
        $sender->setData(
            'Breadcrumbs',
            [['Name' => Gdn::translate('Memberlist'), 'Url' => '/vanilla/memberlist']]
        );

        // Add modules
        $sender->addModule('DiscussionFilterModule');
        $sender->addModule('NewDiscussionModule');
        $sender->addModule('CategoriesModule');
        $sender->addModule('BookmarkedModule');

        // Fetch sort options.
        $sort = Gdn::request()->get('sort', 'Name');
        if (!in_array($sort, Gdn::config('Memberlist.AllowedSorts', ['Name']))) {
            $sort = '';
            $order = 'asc';
            $pagerGetQueryString = '';
        } else {
            $order = Gdn::request()->get('order', 'asc');
            if (!in_array($order, ['asc', 'desc'])) {
                $order = 'asc';
            }
            $pagerGetQueryString = '?'.http_build_query(
                ['sort' => $sort, 'order' => $order]
            );
        }

        // Create pager.
        $page = $args[0] ?? 'p1';
        // Determine offset from $Page
        list($offset, $limit) = offsetLimit(
            $page,
            Gdn::config('Memberlist.PerPage', 30)
            , true
        );
        // Configure pager.
        PagerModule::current()->configure(
            $offset,
            $limit,
            false,
            'vanilla/memberlist/{Page}'.$pagerGetQueryString
        );
        PagerModule::current()->TotalRecords = $this->getUserCount();

        // Set canonical URL
        $sender->canonicalUrl(url('vanilla/memberlist/'.pageNumber($offset, $limit, true, false)));
        $sender->setData(
            'Users',
            $this->getConsolidatedUserData(
                $sort,
                $order,
                $limit,
                $offset
            )
        );

        $this->render($this->getRoleView(Gdn::session()->UserID));
    }


    /**
     * Return the name of (an existing) view depending on the users role.
     *
     * @param integer $userID The session users ID.
     *
     * @return string The path of the view to render.
     */
    private function getRoleView(int $userID = 0): string {
        $viewNames = [];
        // Determine users role.
        if ($userID == 0) {
            $viewNames[0] = 'guest';
        } else {
            $userRoles = Gdn::userModel()->getRoles($userID)->resultArray();
            $viewNames[0] = strtolower($userRoles[0]['Name']);
        }

        $viewNames[1] = 'memberlist';
        $viewNames[2] = '_memberlist';

        foreach ($viewNames as $viewName) {
            $view = __DIR__.'/views/'.$viewName;
            if (is_readable($view.'.php') && is_file($view.'.php')) {
                return $viewName;
            }
            if (is_readable($view.'.tpl') && is_file($view.'.tpl')) {
                return $viewName;
            }
        }
        return '';
    }
}
