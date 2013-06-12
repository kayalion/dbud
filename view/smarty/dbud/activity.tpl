{extends file="app/index"}

{block name="content" append}
<div id="activity">
    <div id="activity-inner">
    {if $activities}
        {pagination pages=$pages page=$page href=$paginationUrl}
        
        <table class="table table-striped">
            <thead>
                <tr>
                    <th class="span4">{translate key="dbud.label.repository"}</th>
                    <th class="span2">{translate key="dbud.label.date"}</th>
                    <th class="span6">{translate key="dbud.label.activity"}</th>
                </tr>
            </thead>
            <tbody>
            {foreach $activities as $activity}
                <tr{if $activity->state == "error" || $activity->state == "warning"} class="{$activity->state}"{/if}>
                    {$description = $activity->getDisplayDescription()}
                
                    <td>
                        <a href="{url id="dbud.repository.detail" parameters=['repository' => $activity->repository->slug]}">{$activity->repository->name}</a>
                        <div class="muted">{$activity->repository->repository}</div>
                    </td>
                    <td>{$activity->dateAdded|date_format:"M j, Y"} <span class="muted">at</span> {$activity->dateAdded|date_format:"H:i:s"}</td>
                    <td>
                        {$activity->getDisplayTeaser()}
                        {if $activity->job && $activity->job->status == 'progress' && !isset($progress[$activity->job->id])}
                            <span class="label label-warning">{translate key="dbud.state.`$activity->job->status`"}</span>
                            {$progress[$activity->job->id] = true}
                        {/if}
                        {if $description}
                            <a href="#" onclick="$('#log-{$activity->id}').toggle(); return false">{translate key="dbud.button.more"}</a>
                        {/if}
                    </td>
                </tr>
                {if $description}
                <tr{if $activity->state == "error" || $activity->state == "warning"} class="{$activity->state}"{/if}>
                    <td colspan="3" id="log-{$activity->id}" class="hide">
                        <div class="console">{$description}</div>
                    </td>
                </tr>
                <tr></tr>
                {/if}
            {/foreach}
            </tbody>
        </table>
        
        {pagination pages=$pages page=$page href=$paginationUrl}
    {else}
    <p>{translate key="dbud.label.activity.none"}</p>
    {/if}
    </div>
</div>
{/block}