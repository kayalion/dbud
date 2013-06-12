{extends file="app/index"}

{block name="content" append}
    <p>{translate key="dbud.label.repository.update"}</p>
    
    <dl>
        <dt>{translate key="dbud.label.repository"}</dt>
        <dd>{$repository->repository}</dd>
    </dl>

    <form action="{url id="dbud.repository.update" parameters=['repository' => $repository->slug]}" class="form" method="post">
        <div class="form-actions">
            <input type="submit" value="{translate key="button.update"}" class="btn btn-primary" />
            <input type="submit" name="cancel" value="{translate key="button.cancel"}" class="btn" />
        </div>
    </form>
{/block}