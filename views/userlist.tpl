{include file="partials/header.tpl"}
<table class="DataTable">
    <thead>
        <tr>
            <th colspan="2">User</th>
            {* <th>Posts</th> *}
            <th>Joined</th>
        </tr>
    </thead>
    <tbody>
        {foreach from=$Users item=user}
        <tr class="role-{$user.Roles[0]|lower} gender-{$user.Gender|lower} Item">
            <td colspan="2" class="UserName">{$user.UserPhoto} {$user.UserAnchor}</td>
            {* <td>{$user.CountPosts}</td> *}
            <td class="JoinDate">{$user.DateInserted|date_format:"%B %e"}</td>
        </tr>
        {/foreach}
    </tbody>
</table>
{include file="partials/footer.tpl"}