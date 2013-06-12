{extends file="app/index"}

{block name="content" append}
    {include file="dbud/repository.helper.actions"}

    <dl>
        <dt>{translate key="dbud.label.repository"}</dt>
        <dd><a href="{url id="dbud.repository.detail" parameters=['repository' => $repository->slug]}">{$repository->repository}</a></dd>
    </dl>
    
    <div id="queue"><div id="queue-inner">
    {if $queue}
        {include file="dbud/queue.table"}
    {else}
        <p>{translate key="dbud.label.queue.empty"}</p>    
    {/if}
    </div></div>
{/block}