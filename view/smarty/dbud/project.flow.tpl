{extends file="app/index"}

{block name="content" append}
    <div class="btn-group">
        <a href="{url id="dbud.project.edit" parameters=["project" => $project->slug]}" class="btn">{translate key="dbud.button.project.edit"}</a>
        <a href="#" class="btn" id="flow-save">{translate key="button.save"}</a>
    </div>

    <div class="row-fluid">
        <div class="span3">
            <h4>{translate key="dbud.title.builder.overview"}</h4>
            {foreach $builders as $builder}
                <div class="r" id="builder-{$builder->id}">
                    <div>
                        <i class="icon icon-wrench"></i>
                        <strong>{$builder->name}</strong>
                    </div>
                    <div>
                        <a href="{url id="dbud.repository.integration" parameters=['repository' => $builder->repository->slug, 'branch' => $builder->branch]}">{$builder->repository->name} ({$builder->branch})</a>
                    </div>
                </div>
            {/foreach}
            <h4>{translate key="dbud.title.server.overview"}</h4>
            {foreach $servers as $server}
                <div class="r" id="server-{$server->id}">
                    <div>
                        <i class="icon icon-globe"></i>
                        <strong>{$server->name}</strong>
                    </div>
                    <div>
                        <a href="{url id="dbud.repository.deployment" parameters=['repository' => $server->repository->slug, 'branch' => $server->branch]}">{$server->repository->name} ({$server->branch})</a>
                    </div>
                </div>
            {/foreach}
        </div>

        <div class="span9">
            <div id="main">
                <div id="render"></div>

                <div class="w" id="repository-0">
                    <div>
                        <i class="icon icon-refresh"></i>
                        <strong>{translate key="dbud.label.repositories.update"}</strong>
                    </div>
                    <div class="ep"></div>
                </div>

            {foreach $project->flow as $flow}
                <div class="w" id="{$flow->getElementId()}">
                    <div>
                        <i class="icon icon-{if $flow->dataType == 'DbudServer'}globe{else}wrench{/if}"></i>
                        <strong>{$flow->data->name}</strong>
                    </div>
                    <div>
                        <a href="{url id="dbud.repository.deployment" parameters=['repository' => $flow->data->repository->slug, 'branch' => $flow->data->branch]}">{$flow->data->repository->name} ({$flow->data->branch})</a>
                    </div>
                    <div>
                        <span class="label label-{$flow->data->state}">{translate key="dbud.state.`$flow->data->state`"}</span> {$flow->data->getFriendlyRevision()}
                    </div>
                    <div class="ep"></div>
                </div>
            {/foreach}
            </div>
        </div>
    </div>
{/block}