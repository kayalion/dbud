{extends file="app/index"}

{block name="content" append}
    {include file="dbud/repository.helper.actions"}
    {include file="dbud/repository.helper.branches" url="dbud.repository.deployment"}
    {include file="dbud/repository.helper.tabs" section="deploy"}

    <h2>{if $server->id}{translate key="dbud.title.server.edit"}{else}{translate key="dbud.title.server.add"}{/if}</h2>

    {include file="app/form.prototype"}

    <form id="{$form->getId()}" class="form-horizontal" action="{$action}" method="POST" enctype="multipart/form-data">
        <fieldset>
            {call formRow form=$form row="name"}

            {call formRow form=$form row="repositoryPath"}

            {call formRow form=$form row="revision"}

            <h3>{translate key="dbud.label.server"}</h3>

            {call formRow form=$form row="protocol"}

            {call formRow form=$form row="remoteHost"}

            {call formRow form=$form row="remotePort"}

            {call formRow form=$form row="remotePath"}

            {call formRow form=$form row="remoteUsername"}

            {call formRow form=$form row="newPassword"}

            {call formRow form=$form row="usePassive"}

            {call formRow form=$form row="useSsl"}

            <h3>{translate key="dbud.title.exclude"}</h3>

            {call formRow form=$form row="exclude"}

            {call formRows form=$form}

            <div class="form-actions">
                <input type="submit" name="submit" class="btn btn-primary" value="{"button.save"|translate}" />
                <input type="submit" name="cancel" class="btn" value="{"button.cancel"|translate}" />
            </div>
        </fieldset>
    </form>
{/block}