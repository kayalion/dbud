{extends file="app/index"}

{block name="content" append}

    <div>
        <a href="{url id="dbud.server.edit" parameters=["project" => $project->slug, "environment" => $environment->slug, "slug" => $server->slug]}" class="btn">{translate key="dbud.button.server.edit"}</a>
    </div>
    
    <dl>
        <dt>{translate key="dbud.label.project"}</dt>
        <dd><a href="{url id="dbud.project.detail" parameters=["slug" => $project->slug]}">{$project->name}</a></dd>
        <dt>{translate key="dbud.label.environment"}</dt>
        <dd><a href="{url id="dbud.environment.detail" parameters=["project" => $project->slug, "slug" => $environment->slug]}">{$environment->name}</a></dd>
        <dt>{translate key="dbud.label.protocol"}</dt>
        <dd>{translate key="dbud.protocol.`$server->protocol`"}</dd>
        <dt>{translate key="dbud.label.dsn"}</dt>
        <dd>{$server->getDsn()}</dd>
        <dt>{translate key="dbud.label.revision"}</dt>
        <dd>{if $server->revision}{$server->revision}{else}---{/if}</dd>
    </dl>
    
    <h3>{translate key="dbud.title.server.delete"}</h3>
    <p>{translate key="dbud.label.server.delete"}</p>
    <form id="form-server-delete" action="{url id="dbud.server.delete" parameters=["project" => $project->slug, "environment" => $environment->slug, "slug" => $server->slug]}" class="form" method="post">
        <input type="submit" value="{translate key="button.delete"}" class="btn btn-danger" />
    </form>    
    
{/block}