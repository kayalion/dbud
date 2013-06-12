{extends file="app/index"}

{block name="content" append}
    {include file="dbud/repository.helper.actions"}
    {include file="dbud/repository.helper.branches" url="dbud.repository.commits"}
    {include file="dbud/repository.helper.tabs" section="history"}

    <dl>
        <dt>{translate key="dbud.label.revision"}</dt>    
        <dd>{$commit->revision}</dd>
        <dt>{translate key="dbud.label.date"}</dt>    
        <dd>{$commit->date}</dd>
        <dt>{translate key="dbud.label.author"}</dt>    
        <dd>
            {$author = $commit->getAuthorAddress()}
            <a href="mailto:{$author->getEmailAddress()}">{$author->getDisplayName()}</a>
        </dd>
    </dl>
{/block}