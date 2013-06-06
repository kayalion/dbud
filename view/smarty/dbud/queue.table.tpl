<table class="table table-striped">
    <thead>
        <tr>
            <th>{translate key="dbud.label.date"}</th>
            <th>{translate key="dbud.label.task"}</th>
            <th>{translate key="dbud.label.state"}</th>
            <th>{translate key="dbud.label.queue"}</th>
            <th>{translate key="dbud.label.slot"}</th>
        </tr>
    </thead>
    <tbody>        
    {foreach $queue as $job}
        <tr>
            <td>{$job->dateAdded|date_format:"Y-m-d H:i:s"}</td>
            <td>{$job->task}</td>
            <td>{if $job->job->status == 'progress'}<span class="label label-warning">{translate key="dbud.state.progress"}{elseif $job->job->status == 'error'}<span class="label label-important">{translate key="dbud.state.error"}{else}<span class="label label-info">{translate key="dbud.state.waiting"}{/if}</span></td>
            <td>{$job->job->queue}</td>
            <td>{if $job->job->status != 'waiting'}---{else}{$job->job->slot} / {$job->job->slots}{/if}</td>
        {if $job->job->description}
        </tr>
        <tr>
            <td colspan="5"><div class="alert alert-error">{$job->job->description|htmlentities|nl2br}</div></td>
        {/if}
        </tr> 
    {/foreach}
    </tbody>
</table>