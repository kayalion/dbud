{extends file="app/index"}

{block name="content" append}
    {include file="dbud/repository.helper.actions"}
    {include file="dbud/repository.helper.branches" url="dbud.repository.files"}
    {include file="dbud/repository.helper.tabs" section="files"}

    <ul class="nav nav-tabs">
        <li class="active"><a href="{url id="dbud.repository.files" parameters=['repository' => $repository->slug, 'branch' => $branch]}">{translate key="dbud.button.files"}</a></li>
        <li><a href="{url id="dbud.repository.commits" parameters=['repository' => $repository->slug, 'branch' => $branch]}">{translate key="dbud.button.commits"}</a></li>
        <li><a href="{url id="dbud.repository.deployment" parameters=['repository' => $repository->slug, 'branch' => $branch]}">{translate key="dbud.button.deployment"}</a></li>
    </ul>

    {$breadcrumbs->getHtml()}

    <div class="navbar" style="margin-bottom: 0">
        <div class="navbar-inner">
            <div class="btn-group pull-right">
                <a href="{url id="dbud.repository.download" parameters=['repository' => $repository->slug, 'branch' => $branch]}{$path}" class="btn">{translate key="dbud.button.download"}</a>
                <a href="{url id="dbud.repository.commits" parameters=['repository' => $repository->slug, 'branch' => $branch]}{$path}" class="btn">{translate key="dbud.button.history"}</a>
            </div>
            <div>
                <span class="navbar-text">{$name} <span class="muted">{if $lines !== false} | {if $lines == 1}{translate key="dbud.label.line" lines=$lines}{else}{translate key="dbud.label.lines" lines=$lines}{/if}{/if} | {$size}</span></span>
            </div>
        </div>
    </div>
    {if $content}
    <pre class="prettyprint linenums">{$content|htmlentities}</pre>
    {/if}
    
{/block}