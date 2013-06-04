{extends file="app/index"}

{block name="content" append}
    {include file="app/form.prototype"}

    <form id="{$form->getId()}" class="form-horizontal" action="{$action}" method="POST" enctype="multipart/form-data">
        <fieldset>
            {call formRows form=$form}
            
            <div class="form-actions">
                <input type="submit" name="submit" class="btn btn-primary" value="{"button.next"|translate}" />
                <input type="submit" name="cancel" class="btn" value="{"button.cancel"|translate}" />
            </div>
        </fieldset>
    </form>
{/block}