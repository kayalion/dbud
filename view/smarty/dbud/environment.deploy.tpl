{extends file="app/index"}

{block name="content" append}

    <dl>
        <dt>{translate key="dbud.label.project"}</dt>
        <dd><a href="{url id="dbud.project.detail" parameters=["slug" => $project->slug]}">{$project->name}</a></dd>
        <dt>{translate key="dbud.label.branch"}</dt>
        <dd>{$environment->branch}</dd>
    </dl>

    <h3>{translate key="dbud.button.deploy"}</h3>
    
    {include file="app/form.prototype"}

    <form id="{$form->getId()}" class="form-horizontal" action="{$action}" method="POST" enctype="multipart/form-data">
        <fieldset>
            {call formRows form=$form}
            
            <div class="form-actions">
                <input type="submit" name="submit" class="btn btn-primary" value="{"dbud.button.deploy"|translate}" />
                <input type="submit" name="cancel" class="btn" value="{"button.cancel"|translate}" />
            </div>
        </fieldset>
    </form>
{/block}