{extends file="app/index"}

{block name="content" append}
    {include file="dbud/repository.helper.actions"}
    {include file="dbud/repository.helper.branches" url="dbud.repository.deployment"}
    {include file="dbud/repository.helper.tabs" section="deploy"}

    <div class="btn-group">
        <a href="{url id="dbud.server.add" parameters=["repository" => $repository->slug, "branch" => $branch]}" class="btn">{translate key="dbud.button.server.add"}</a>
        <a href="{url id="dbud.repository.deploy" parameters=['repository' => $repository->slug, "branch" => $branch]}" class="btn{if !$servers} disabled{/if}">{translate key="dbud.button.deploy"}</a>
    </div>

    <p>&nbsp;</p>

    {if $servers}
        <table class="table table-striped">
            <tbody>
            {foreach $servers as $server}
                <tr>
                    <td>
                        <div class="btn-group pull-left" style="margin-right: 12px">
                            <a class="btn dropdown-toggle" data-toggle="dropdown" href="#">
                                <i class="icon icon-cog"></i>
                                <span class="caret"></span>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a href="{url id="dbud.server.edit" parameters=["repository" => $repository->slug, "branch" => $branch, "server" => $server->slug]}">{translate key="dbud.button.server.edit"}</a></li>
                                <li><a href="{url id="dbud.server.delete" parameters=["repository" => $repository->slug, "branch" => $branch, "server" => $server->slug]}">{translate key="dbud.button.server.delete"}</a></li>
                            </ul>
                        </div>
                        {$server->name}<br />
                        <span class="muted">{$server->repositoryPath} &rarr; {$server->getDsn()}</span>
                    </td>
                    <td>
                        <div class="text-right">
                        {if $server->revision}
                            <span class="label{if $server->state == "ok"} label-success{/if}{if $server->state =="working"} label-warning{/if}{if $server->state == "error"} label-important{/if}">{translate key="dbud.state.`$server->state`"}</span>
                            <a href="{url id="dbud.repository.commit" parameters=['repository' => $repository->slug, 'branch' => $branch, 'revision' => $server->revision]}">{$server->getFriendlyRevision()}</a><br />
                            {translate key="dbud.label.deployed" date=$server->dateDeployed|date_format:"j M Y" time=$server->dateDeployed|date_format:"H:i:s"}
                        {else}
                            ---
                        {/if}
                        </div>
                    </td>
                </tr>
            {/foreach}
            </tbody>
        </table>

        <a href="#modal-mode-auto" role="button" data-toggle="modal">{translate key="dbud.label.deployment.auto"}</a>

        <div id="modal-mode-auto" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="modal-mode-auto-label" aria-hidden="true">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h3 id="modal-mode-auto-label">{translate key="dbud.title.mode.auto"}</h3>
            </div>
            <div class="modal-body">
                {url var="hookUrl" id="dbud.repository.update.auto" parameters=['repository' => $repository->slug, "code" => $deployCode]}
                <p>{translate key="dbud.label.deployment.auto.description" url=$hookUrl}</p>
            </div>
        </div>
    {else}
        <p>{translate key="dbud.label.servers.none"}</p>
    {/if}
    </div>
{/block}