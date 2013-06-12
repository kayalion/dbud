{extends file="app/index"}

{block name="content" append}
    {include file="dbud/repository.helper.actions"}
    {include file="dbud/repository.helper.branches" url="dbud.repository.commits"}
    {include file="dbud/repository.helper.tabs" section="history"}

    {$breadcrumbs->getHtml()}

    {$title = ""}
    {foreach $commits as $commit}
        {$commitTitle = $commit->date|date_format:"M j, Y"}
        
        {if !$title}
            <h4>{$commitTitle}</h4>
            <table class="table table-striped">
        {elseif $title != $commitTitle}
            </table>
            <h4>{$commitTitle}</h4>
            <table class="table table-striped">
        {/if}
        
        {$title = $commitTitle}
        
        <tr>
            <td>
                {$date=strtotime($commit->date)}
                <strong>{$commit->message}</strong><br />
                {$author = $commit->getAuthorAddress()}
                {$author = "<a href=\"mailto:`$author->getEmailAddress()`\">`$author->getDisplayName()`</a>"}
                {translate key="dbud.label.authored" author=$author date=$date|date_format:"M j, Y" time=$date|date_format:"H:i:s"}
            </td>
            <td>
                <div class="pull-right">
                    <a href="{url id="dbud.repository.commit" parameters=['repository' => $repository->slug, 'branch' => $branch, 'revision' => $commit->revision]}">{$commit->getFriendlyRevision()}</a>
                </div> 
            </td>
        </tr>
    {/foreach}
    {if $title}
        </table>
    {/if} 

{/block}