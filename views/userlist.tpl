{debug}
<style>
    .Userlist li {
        display: flex;
        align-items: center;
    }
</style>
<ul class="UserlistData">
    {foreach from=$Users item=user}
    <li class="Role{$user.Roles[0]}">
        {$user.UserPhoto}
        {$user.UserAnchor}
    {/foreach}
</ul>