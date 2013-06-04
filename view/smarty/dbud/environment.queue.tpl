{extends file="app/index"}

{block name="content" append}

    <dl>
        <dt>{translate key="dbud.label.project"}</dt>
        <dd><a href="{url id="dbud.project.detail" parameters=["slug" => $project->slug]}">{$project->name}</a></dd>
        <dt>{translate key="dbud.label.environment"}</dt>
        <dd><a href="{url id="dbud.environment.detail" parameters=["project" => $project->slug, "slug" => $environment->slug]}">{$environment->name}</a></dd>
    </dl>
    
    <div id="queue"><div id="queue-inner">
    {if $queue}
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>{translate key="dbud.label.date"}</th>
                    <th>{translate key="dbud.label.task"}</th>
                    <th>{translate key="dbud.label.queue"}</th>
                    <th>{translate key="dbud.label.state"}</th>
                    <th>{translate key="dbud.label.slot"}</th>
                </tr>
            </thead>
            <tbody>        
            {foreach $queue as $job}
                <tr>
                    <td>{$job->dateAdded|date_format:"Y-m-d H:i:s"}</td>
                    <td>{$job->task}</td>
                    <td>{$job->status->getQueue()}</td>
                    <td>{if $job->status->isInProgress()}<span class="label label-warning">{translate key="dbud.state.progress"}{elseif $job->status->isError()}<span class="label label-important">{translate key="dbud.state.error"}{else}<span class="label label-info">{translate key="dbud.state.queue"}{/if}</span></td>
                    <td>{if $job->status->isError() || $job->status->isInProgress()}---{else}{$job->status->getSlot()} / {$job->status->getTotalSlots()}{/if}</td>
                {if $job->status->getError()}
                </tr>
                <tr>
                    <td colspan="5"><div class="alert alert-error">{$job->status->getError()|htmlentities|nl2br}</div></td>
                {/if}
                </tr> 
            {/foreach}
            </tbody>
        </table>
    {else}
        <p>{translate key="dbud.label.queue.empty"}</p>    
    {/if}
    </div></div>
{/block}