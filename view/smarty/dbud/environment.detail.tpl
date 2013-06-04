{extends file="app/index"}

{block name="content" append}

    <div class="btn-group">
        <a href="{url id="dbud.environment.edit" parameters=["project" => $project->slug, "slug" => $environment->slug]}" class="btn">{translate key="dbud.button.environment.edit"}</a>
        <a href="{url id="dbud.environment.deploy" parameters=["project" => $project->slug, "slug" => $environment->slug]}" class="btn{if !$servers} disabled{/if}">{translate key="dbud.button.deploy"}</a>
        <a href="{url id="dbud.environment.queue" parameters=["project" => $project->slug, "slug" => $environment->slug]}" class="btn{if !$servers} disabled{/if}">{translate key="dbud.button.queue"}</a>
    </div>
    
    <dl>
        <dt>{translate key="dbud.label.project"}</dt>
        <dd><a href="{url id="dbud.project.detail" parameters=["slug" => $project->slug]}">{$project->name}</a></dd>
        <dt>{translate key="dbud.label.branch"}</dt>
        <dd>{$environment->branch}</dd>
        <dt>{translate key="dbud.label.mode"}</dt>
        <dd>{if $environment->mode == "auto"}<a href="#modal-mode-auto" role="button" data-toggle="modal">{translate key="dbud.mode.`$environment->mode`"}</a>{else}{translate key="dbud.mode.`$environment->mode`"}{/if}</dd>
    </dl>
    
    <div id="modal-mode-auto" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="modal-mode-auto-label" aria-hidden="true">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            <h3 id="modal-mode-auto-label">{translate key="dbud.title.mode.auto"}</h3>
        </div>
        <div class="modal-body">
            {url var="hookUrl" id="dbud.environment.deploy.auto" parameters=["project" => $project->slug, "slug" => $environment->slug, "code" => $deployCode]}
            <p>{translate key="dbud.label.deployment.auto" url=$hookUrl}</p>
        </div>
    </div>    
    
    <div class="servers">
        <h3>{translate key="dbud.title.server.overview"}</h3>
        
        {if !$servers}
        <p>{translate key="dbud.label.servers.none"}</p>
        {/if}
        
        <p><a href="{url id="dbud.server.add" parameters=["project" => $project->slug, "environment" => $environment->slug]}" class="btn">{translate key="dbud.button.server.add"}</a></p>
        
        {if $servers}
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>{translate key="dbud.label.server"}</th>
                    <th>{translate key="dbud.label.dsn"}</th>
                    <th>{translate key="dbud.label.revision"}</th>
                </tr>
            </thead>
            <tbody>        
            {foreach $servers as $server}
                <tr>
                    <td><a href="{url id="dbud.server.detail" parameters=["project" => $project->slug, "environment" => $environment->slug, "slug" => $server->slug]}">{$server->name}</a></td>
                    <td>{$server->getDsn()}</td> 
                    <td>{if $server->revision}{$server->revision}{else}---{/if}</td>
                </tr> 
            {/foreach}
            </tbody>
        </table>    
        {/if}    
    </div>
    
    <h3>{translate key="dbud.title.environment.delete"}</h3>
    <p>{translate key="dbud.label.environment.delete"}</p>
    <form id="form-environment-delete" action="{url id="dbud.environment.delete" parameters=["project" => $project->slug, "slug" => $environment->slug]}" class="form" method="post">
        <input type="submit" value="{translate key="button.delete"}" class="btn btn-danger" />
    </form>    
{/block}