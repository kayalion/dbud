{extends file="app/index"}

{block name="content" append}

    {if !$projects}
    <p>{translate key="dbud.label.projects.none"}</p>
    {/if}
    <p><a href="{url id="dbud.project.add"}" class="btn">{translate key="dbud.button.project.add"}</a></p>
    
    {if $projects}
    <table class="table table-striped">
        <thead>
            <tr>
                <th>{translate key="dbud.label.project"}</th>
                <th>{translate key="dbud.label.repository"}</th>
                <th>{translate key="dbud.label.state"}</th>
            </tr>
        </thead>
        <tbody>
    {foreach $projects as $project}
            <tr>
                <td><a href="{url id="dbud.project.detail" parameters=['slug' => $project->slug]}">{$project->name}</a></td>
                <td>{$project->repository}</td>
                <td><span class="label{if $project->state == "cloned"} label-success{/if}{if $project->state =="clone"} label-warning{/if}{if $project->state == "error"} label-important{/if}">{translate key="dbud.state.`$project->state`"}</span></td>
            </tr>
    {/foreach}
        </tbody>
    </table>
    {/if}

{/block}