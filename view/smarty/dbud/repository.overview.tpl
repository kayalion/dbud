{extends file="app/index"}

{block name="content" append}

    {if !$repositories}
    <p>{translate key="dbud.label.repositories.none"}</p>
    {/if}
    <p><a href="{url id="dbud.repository.add"}" class="btn">{translate key="dbud.button.repository.add"}</a></p>
    
    {if $repositories}
    <table class="table table-striped">
        <thead>
            <tr>
                <th>{translate key="dbud.label.repository"}</th>
                <th>{translate key="dbud.label.description"}</th>
            </tr>
        </thead>
        <tbody>
    {foreach $repositories as $repository}
            <tr{if $repository->state == "error"} class="error"{/if}>
                <td>
                    <a href="{url id="dbud.repository.detail" parameters=['repository' => $repository->slug]}">{$repository->name}</a>
                    <div class="muted">{$repository->repository}</div>
                </td>
                <td>{$repository->description}</td>
            </tr>
    {/foreach}
        </tbody>
    </table>
    {/if}

{/block}