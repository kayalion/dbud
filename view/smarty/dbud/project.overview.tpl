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
                <th>{translate key="dbud.label.name"}</th>
            </tr>
        </thead>
        <tbody>
    {foreach $projects as $project}
            <tr>
                <td>
                    <a href="{url id="dbud.project.detail" parameters=['project' => $project->slug]}">{$project->name}</a>
                </td>
            </tr>
    {/foreach}
        </tbody>
    </table>
    {/if}

{/block}