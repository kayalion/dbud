{extends file="app/index"}

{block name="content" append}
    {include file="dbud/repository.helper.actions"}
    {include file="dbud/repository.helper.branches" url="dbud.repository.files"}
    {include file="dbud/repository.helper.tabs" section="files"}

    {$breadcrumbs->getHtml()}

    <div class="navbar" style="margin-bottom: 0">
        <div class="navbar-inner">
            <div class="btn-group pull-right">
                <a href="{url id="dbud.repository.download" parameters=['repository' => $repository->slug, 'branch' => $branch]}{$path}" class="btn">{translate key="dbud.button.download"}</a>
                <a href="{url id="dbud.repository.commits" parameters=['repository' => $repository->slug, 'branch' => $branch]}{$path}" class="btn">{translate key="dbud.button.history"}</a>
            </div>
            <div>
                <span class="navbar-text">{$name}</span>
            </div>
        </div>
    </div>

    
    <table class="table table-bordered table-striped">
    {foreach $files as $file}
        <tr>
            <td>
                <i class="icon icon-{if $file->isDirectory()}folder-open{else}file{/if}"></i>
                <a href="{url id="dbud.repository.files" parameters=['repository' => $repository->slug, 'branch' => $branch]}{$path}/{$file->getName()}">{$file->getName()}</a>
            </td>
            <td class="muted">{$file->size}</td>
            <td class="muted">{$file->mode}</td>
            <td>{$file->commit->date}</td>
            <td>
                {$author = $file->commit->getAuthorAddress()}
                <a href="mailto:{$author->getEmailAddress()}">{$author->getDisplayName()}</a>:
                {$file->commit->message}
            </td>
            <td>
                <div class="pull-right">
                    <a href="{url id="dbud.repository.commit" parameters=['repository' => $repository->slug, 'branch' => $branch, 'revision' => $file->commit->revision]}">{$file->commit->getFriendlyRevision()}</a>
                </div>
            </td> 
        </tr>
    {/foreach}
    </table> 

{/block}