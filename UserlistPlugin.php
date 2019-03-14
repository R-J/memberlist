<?php

class UserlistPlugin extends Gdn_Plugin {
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
        Gdn::config()->touch('Userlist.PerPage', 30);
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
     * Add a link to the Userlist to the custom menu.
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
                'Permission' => 'Plugins.Userlist.View',
                'Url' => '/vanilla/userlist',
                'Text' => Gdn::translate('Userlist')
            ]
        ];
    }

    /**
     * Add option to opt out from userlist to profile.
     *
     * @param ProfileController $sender Instance of the calling class.
     *
     * @return void.
     * @throws Gdn_UserException
     */
    public function profileController_userList_create($sender) {
        // TODO: this part has not been taken care of yet.
        $sender->permission('Garden.SignIn.Allow');

        $sender->getUserInfo('', '', Gdn::session()->UserID, false);
        $sender->editMode(true);

        // Set the breadcrumbs.
        $sender->setData(
            'Breadcrumbs',
            [
                ['Name' => t('Profile'), 'Url' => '/profile'],
                ['Name' => t('Userlist Settings'), 'Url' => '/profile/userlist']
            ]
        );
 
        $userListModel = new UserListModel();
        if ($sender->Form->authenticatedPostBack()) {
            $userListModel->saveProfileSettings($sender->Form->formValues());
        }
        $sender->setData(
            'Fields',
            $userListModel->getUsersOptOutFields()
        );

        $sender->render('profile', '', '/plugins/userlist');
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
        $orderFields = '',
        $orderDirection = 'asc',
        $limit = 30,
        $offset = 0
    ) {
        $cacheKey = "Userlist_{$orderFields}_{$orderDirection}_{$limit}_{$offset}";
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
     * Determine user role and try to get a view with that name. Default to "userlist".
     *
     * @param VanillaController $sender Instance of the calling class.
     *
     * @return void.
     */
    public function vanillaController_userlist_create($sender,$args) {
        // Ensure view permissions.
        $sender->permission('Plugins.Userlist.View');

        // Add breadcrumbs.
        $sender->setData(
            'Breadcrumbs',
            [['Name' => Gdn::translate('Userlist'), 'Url' => '/vanilla/userlist']]
        );

        // Add modules
        $sender->addModule('DiscussionFilterModule');
        $sender->addModule('NewDiscussionModule');
        $sender->addModule('CategoriesModule');
        $sender->addModule('BookmarkedModule');

        // Create pager.
        $page = $args[0] ?? 'p1';
        // Determine offset from $Page
        list($offset, $limit) = offsetLimit(
            $page,
            Gdn::config('Userlist.PerPage', 30)
            , true
        );
        // Configure pager.
        PagerModule::current()->configure($offset, $limit, false, 'vanilla/userlist/{Page}');

        // Set canonical URL
        $sender->canonicalUrl(url('vanilla/userlist/'.pageNumber($offset, $limit, true, false)));

        // Fetch sort options.

        // TODO: Handle page requests with sort and paging.
        $sender->setData('Users', $this->getConsolidatedUserData('', 'asc', $limit, $offset));

        // Determine the view to use.
        $userID = Gdn::session()->UserID;
        if ($userID == 0) {
            $viewName = 'guest';
        } else {
            $userRoles = Gdn::userModel()->getRoles($userID)->resultArray();
            $viewName = strtolower($userRoles[0]['Name']);
        }
        // If a role view does not exist, use default "userlist"
        if (!file_exists(__DIR__."/views/{$viewName}")) {
            $viewName = 'userlist';
        }
        $sender->render($viewName, '', 'plugins/userlist');
    }
}
