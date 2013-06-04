{extends file="app/index"}

{block name="content" append}

    <div class="btn-group">
        <a href="{url id="dbud.project.edit" parameters=["slug" => $project->slug]}" class="btn">{translate key="dbud.button.project.edit"}</a>
        <a href="{url id="dbud.project.queue" parameters=["slug" => $project->slug]}" class="btn">{translate key="dbud.button.queue"}</a>
    </div>
    
    <dl>
        <dt>{translate key="dbud.label.repository"}</dt>
        <dd>{$project->repository}</dd>
        <dt>{translate key="dbud.label.state"}</dt>
        <dd id="state"><div id="state-inner"><span class="label{if $project->state == "cloned"} label-success{/if}{if $project->state =="clone"} label-warning{/if}{if $project->state == "error"} label-important{/if}">{translate key="dbud.state.`$project->state`"}</span></div></dd>
    </dl>
    
    <div class="environments">
        <h3>{translate key="dbud.title.environment.overview"}</h3>
        <p><a href="{url id="dbud.environment.add" parameters=["project" => $project->slug]}" class="btn{if $project->state != "cloned"} disabled{/if}">{translate key="dbud.button.environment.add"}</a></p>
        
        {if $environments}
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>{translate key="dbud.label.environment"}</th>
                    <th>{translate key="dbud.label.branch"}</th>
                    <th>{translate key="dbud.label.mode"}</th>
                </tr>
            </thead>
            <tbody>
            {foreach $environments as $environment}
                <tr>
                    <td><a href="{url id="dbud.environment.detail" parameters=["project" => $project->slug, "slug" => $environment->slug]}">{$environment->name}</a></td>
                    <td>{$environment->branch}</td>
                    <td>{translate key="dbud.mode.`$environment->mode`"}</td>
                </tr>
            {/foreach}
            </tbody>
        </table>
        {/if}
    </div>

    {if $logs}
    <h3>{translate key="dbud.title.activity"}</h3>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>{translate key="dbud.label.date"}</th>
                <th>{translate key="dbud.label.task"}</th>
            </tr>
        </thead>
        <tbody>
        {foreach $logs as $log}
            <tr>    
                <td>{$log->dateAdded|date_format:"Y-m-d H:i:s"}</td>
                <td>{$log->message|nl2br}</td>
            </tr>
        {/foreach}
        </tbody>
    </table>
    {/if}
    
    <h3>{translate key="dbud.title.project.delete"}</h3>
    <p>{translate key="dbud.label.project.delete"}</p>
    <form id="form-project-delete" action="{url id="dbud.project.delete" parameters=["slug" => $project->slug]}" class="form" method="post">
        <input type="submit" value="{translate key="button.delete"}" class="btn btn-danger" />
    </form>
{/block}