{extends file="app/index"}

{block name="content" append}
    {if $logs}
    <table class="table table-striped">
        <thead>
            <tr>
                <th>{translate key="dbud.label.project"}</th>
                <th>{translate key="dbud.label.date"}</th>
                <th>{translate key="dbud.label.task"}</th>
            </tr>
        </thead>
        <tbody>
        {foreach $logs as $log}
            <tr>    
                <td><a href="{url id="dbud.project.detail" parameters=["slug" => $log->project->slug]}">{$log->project->name}</a></td>
                <td>{$log->dateAdded|date_format:"Y-m-d H:i:s"}</td>
                <td>{$log->message|htmlentities|nl2br}</td>
            </tr>
        {/foreach}
        </tbody>
    </table>
    {else}
    <p>{translate key="dbud.label.activity.none"}</p>
    {/if}
{/block}