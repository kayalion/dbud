{extends file="app/index"}

{block name="content" append}
    {include file="dbud/repository.helper.actions"}

    <dl>
        <dt>{translate key="dbud.label.repository"}</dt>
        <dd><a href="{url id="dbud.repository.detail" parameters=['repository' => $repository->slug]}">{$repository->repository}</a></dd>
    {if $repository->description}
        <dt>{translate key="dbud.label.description"}</dt>    
        <dd>{$repository->description}</dd>
    {/if}
        <dt>{translate key="dbud.label.state"}</dt>    
        <dd><span class="label{if $repository->state == "ready"} label-success{/if}{if $repository->state =="working"} label-warning{/if}{if $repository->state == "error"} label-important{/if}">{translate key="dbud.state.`$repository->state`"}</span></dd>
    </dl>
{/block}