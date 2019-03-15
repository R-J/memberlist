<style>
    th {
        text-align: left;
    }
</style>
<div class="MemberList">
<h2>{t c="Users"}</h2>
{pager type=less}
<table class="DataTable">
    <thead>
        <tr>
            <th></th>
            <th>{sortorder_link text=User sort=Name}</th>
            <th>Posts</th>
            <th>{sortorder_link text=Discussions sort=CountDiscussions}</th>
            <th>{sortorder_link text=Comments sort=CountComments}</th>
            <th class="JoinDate">{sortorder_link text="Join Date" sort=DateInserted}</th>
        </tr>
    </thead>
    <tbody>
        {foreach from=$Users item=user}
        <tr class="role-{$user.Roles[0]|lower} gender-{$user.Gender|lower} Item">
            <td>{$user.UserPhoto}</td>
            <td>{$user.UserAnchor}</td>
            <td>{$user.CountPosts}</td>
            <td>{$user.CountDiscussions}</td>
            <td>{$user.CountComments}</td>
            <td class="JoinDate">{$user.DateInserted|date_format:"%B %e"}</td>
        </tr>
        {/foreach}
    </tbody>
</table>
{pager type=more}
</div>
