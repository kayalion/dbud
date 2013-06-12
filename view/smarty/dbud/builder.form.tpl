{extends file="app/index"}

{block name="content" append}
    {include file="dbud/repository.helper.actions"}
    {include file="dbud/repository.helper.branches" url="dbud.repository.integration"}
    {include file="dbud/repository.helper.tabs" section="integration"}

    <h2>{if $builder->id}{translate key="dbud.title.builder.edit"}{else}{translate key="dbud.title.builder.add"}{/if}</h2>

    {include file="app/form.prototype"}

    <form id="{$form->getId()}" class="form-horizontal" action="{$action}" method="POST" enctype="multipart/form-data">
        <fieldset>
        
            {call formRows form=$form}
            
            <div class="form-actions">
                <input type="submit" name="submit" class="btn btn-primary" value="{"button.save"|translate}" />
                <input type="submit" name="cancel" class="btn" value="{"button.cancel"|translate}" />
            </div>
        </fieldset>
    </form>
{/block}