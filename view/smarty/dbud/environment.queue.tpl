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
        {include file="dbud/queue.table"}
    {else}
        <p>{translate key="dbud.label.queue.empty"}</p>    
    {/if}
    </div></div>
{/block}