{extends file="app/index"}

{block name="content" append}
    <p>{translate key="dbud.label.server.delete"}</p>
    
    <dl>
        <dt>{translate key="dbud.label.repository"}</dt>
        <dd>{$repository->repository}</dd>
        <dt>{translate key="dbud.label.branch"}</dt>
        <dd>{$branch}</dd>
        <dt>{translate key="dbud.label.server"}</dt>
        <dd>{$server->name}</dd>
        <dt>{translate key="dbud.label.protocol"}</dt>
        <dd>{translate key="dbud.protocol.`$server->protocol`"}</dd>
        <dt>{translate key="dbud.label.dsn"}</dt>
        <dd>{$server->getDsn()}</dd>
    </dl>

    <form action="{url id="dbud.server.delete" parameters=['repository' => $repository->slug, 'branch' => $branch, 'server' => $server->slug]}" class="form" method="post">
        <div class="form-actions">
            <input type="submit" value="{translate key="button.delete"}" class="btn btn-danger" />
            <input type="submit" name="cancel" value="{translate key="button.cancel"}" class="btn" />
        </div>
    </form>
{/block}