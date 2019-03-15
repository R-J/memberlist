{include file="partials/header.tpl"}
<table class="DataTable">
    <thead>
        <tr>
            <th></th>
            <th>User</th>
        </tr>
    </thead>
    <tbody>
        {foreach from=$Users item=user}
        <tr class="role-{$user.Roles[0]|lower} Item">
            <td>{$user.UserPhoto}</td>
            <td class="UserName">{$user.UserAnchor}</td>
        </tr>
        {/foreach}
    </tbody>
</table>
{include file="partials/footer.tpl"}