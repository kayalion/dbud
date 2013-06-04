{extends file="app/index"}

{block name="content" append}
    {include file="app/form.prototype"}

    <form id="{$form->getId()}" class="form-horizontal" action="{$action}" method="POST" enctype="multipart/form-data">
        <fieldset>
            {call formRow form=$form row="name"}
            
            <h3>{translate key="dbud.label.server"}</h3>
            
            {call formRow form=$form row="protocol"}
            
            {call formRow form=$form row="remoteHost"}
            
            {call formRow form=$form row="remotePort"}
            
            {call formRow form=$form row="remoteUsername"}
            
            {call formRow form=$form row="newPassword"}
            
            <div class="control-group">
                <label class="control-label"></label>
                <div class="controls">
                    {call formControl form=$form row="useKey"}
                    <a href="#modal-ssh-key" role="button" data-toggle="modal">{translate key="dbud.button.server.ssh.key"}</a>
                </div>
            </div>
            
            <div id="modal-ssh-key" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="modal-ssh-key-label" aria-hidden="true">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                    <h3 id="modal-ssh-key-label">{translate key="dbud.title.ssh.key"}</h3>
                </div>
                <div class="modal-body">
                    <p>{translate key="dbud.label.ssh.key"}</p>
                    <div class="row-fluid">
                        <textarea name="_ssh-key" rows="10" class="span12">{$publicKey}</textarea>
                    </div>
                </div>
            </div>                 
            
            <h3>{translate key="dbud.title.commands"}</h3>
            
            {call formRow form=$form row="commands"}
            
            {call formRows form=$form}
            
            <div class="form-actions">
                <input type="submit" name="submit" class="btn btn-primary" value="{"button.submit"|translate}" />
                <input type="submit" name="cancel" class="btn" value="{"button.cancel"|translate}" />
            </div>
        </fieldset>
    </form>
{/block}