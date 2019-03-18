# Memberlist Plugin for Vanilla Forums

The plugin adds a new permission to Vanilla. After you have activated nothing will change for your users. Only after you have given the "View" permission of this plugin to your users, they will be able to see the memberlist.

As of the time of creation of this plugin, the Keystone theme only shows the custom_menu (where the memberlist link is added to) in the mobile menu. You would have to think of solving that problem by yourself. If you are using a custom theme, ensure that it includes the custom_menu.

This plugin allows (and encourages!) to create your own views. You can copy one of the existing files in the views folder and use them. By naming the view file according to the users roles, you can define who will see which information. Every user with a role that has no dedicated view will either see `memberlist.php`, `memberlist.tpl`, `_memberlist.php` or `_memberlist.tpl`. So if you want to have a default view for all users, name that file "memberlist.php"/"memberlist.tpl".

Some of the examples make use of the "partials" subfolder. They are just an example. Feel free to built up your custom view however you like it.

You can also add your own Smarty functions if needed. put them in the "SmartyPlugins" folder.